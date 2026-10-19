<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\CrmAttentionReport;
use App\Libraries\PhoneExtractor;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CRM reports: attention audit per advisor and export of captured phone numbers.
 */
class CrmReportsController extends BaseController
{
    private const IN_CHUNK = 500;

    public function index()
    {
        if (! $this->canViewReports()) {
            return redirect()->to(base_url('/app/dashboard'));
        }

        $data = [
            'title'  => 'Reportes CRM',
            'slogan' => ' | Asesores RM',
            'view'   => 'auth/crm/reports',
        ];

        return view('template/header/header', $data)
            . view('template/sidebar/sidebar', $data)
            . view('template/navbar/navbar', $data)
            . view('auth/crm/reports', $data)
            . view('template/footer/footer', $data);
    }

    /** GET /app/crm/reports/data?from=YYYY-MM-DD&to=YYYY-MM-DD */
    public function api_attention(): ResponseInterface
    {
        if (! $this->canViewReports()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Sin permiso']);
        }

        [$from, $to] = $this->range();
        $conversations = $this->conversationsInRange($from, $to);
        $outbound      = $this->outboundTexts(array_column($conversations, 'id'));

        $input = [];
        foreach ($conversations as $c) {
            $texts   = $outbound[$c['id']] ?? [];
            $input[] = [
                'id'             => (int) $c['id'],
                'human_replied'  => $texts !== [],
                'outbound_texts' => $texts,
            ];
        }

        $summary = (new CrmAttentionReport($this->advisorMap()))->summarize($input);

        $rows = [];
        foreach ($summary['advisors'] as $name => $row) {
            $rows[] = ['advisor' => $name] + $row;
        }
        usort($rows, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);
        $summary['advisors'] = $rows;

        return $this->response->setJSON(['status' => 'success', 'from' => $from, 'to' => $to, 'data' => $summary]);
    }

    /** GET /app/crm/reports/phones?from=YYYY-MM-DD&to=YYYY-MM-DD  (CSV download) */
    public function export_phones(): ResponseInterface
    {
        if (! $this->canViewReports()) {
            return $this->response->setStatusCode(403)->setBody('Sin permiso');
        }

        [$from, $to] = $this->range();
        $conversations = $this->conversationsInRange($from, $to);
        $ids           = array_column($conversations, 'id');
        $byId          = array_column($conversations, null, 'id');
        $report        = new CrmAttentionReport($this->advisorMap());
        $outbound      = $this->outboundTexts($ids);

        /** @var array<string, array<string, string>> $found canonical number => row */
        $found = [];
        $add   = function (string $number, string $source, array $c, string $date) use (&$found, $report, $outbound): void {
            if (isset($found[$number])) {
                return;
            }
            $advisors        = [];
            foreach ($outbound[$c['id']] ?? [] as $text) {
                foreach ($report->advisorsIn($text) as $name) {
                    $advisors[$name] = true;
                }
            }
            $found[$number] = [
                'number'   => $number,
                'source'   => $source,
                'client'   => (string) ($c['lead_name'] ?? ''),
                'ig_user'  => (string) ($c['instagram_username'] ?? $c['external_username'] ?? ''),
                'account'  => (string) ($c['recipient_ig_username'] ?? ''),
                'advisors' => implode(' / ', array_keys($advisors)),
                'date'     => $date,
                'conv_id'  => (string) $c['id'],
            ];
        };

        foreach ($this->inboundMessages($ids) as $m) {
            foreach (PhoneExtractor::extract((string) $m['content']) as $number) {
                $add($number, 'chat', $byId[$m['conversation_id']], (string) $m['created_at']);
            }
        }
        foreach ($conversations as $c) {
            $phone = trim((string) ($c['lead_phone'] ?? ''));
            foreach ($phone === '' ? [] : PhoneExtractor::extract($phone) as $number) {
                $add($number, 'lead', $c, (string) $c['created_at']);
            }
        }

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['Numero', 'Origen', 'Cliente', 'Usuario IG', 'Cuenta receptora', 'Asesor(es)', 'Fecha', 'Conversacion']);
        foreach ($found as $row) {
            fputcsv($fh, array_map([self::class, 'csvSafe'], array_values($row)));
        }
        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="numeros-whatsapp_' . $from . '_' . $to . '.csv"')
            ->setBody($csv);
    }

    private function canViewReports(): bool
    {
        $allowed = config('CrmReports')->allowedRoles;

        return (bool) session()->get('loggedIn') && in_array((int) session()->get('id_fk_rol'), $allowed, true);
    }

    /** @return array{0: string, 1: string} inclusive Y-m-d range, defaults to the current month */
    private function range(): array
    {
        $valid = static fn (?string $d): bool => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 && strtotime($d) !== false;
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');
        $from  = $valid($from) ? $from : date('Y-m-01');
        $to    = $valid($to) ? $to : date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /** @return array<string, list<string>> */
    private function advisorMap(): array
    {
        return config('CrmReports')->advisors;
    }

    /** @return list<array<string, mixed>> */
    private function conversationsInRange(string $from, string $to): array
    {
        return db_connect()->table('conversations c')
            ->select('c.id, c.created_at, c.external_username, c.recipient_ig_username, l.name AS lead_name, l.phone AS lead_phone, l.instagram_username')
            ->join('leads l', 'l.id = c.lead_id', 'left')
            ->where('c.created_at >=', $from . ' 00:00:00')
            ->where('c.created_at <=', $to . ' 23:59:59')
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Human (non-AI) outbound texts per conversation, in message order.
     *
     * @param list<int|string> $ids
     *
     * @return array<int, list<string>>
     */
    private function outboundTexts(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, self::IN_CHUNK) as $chunk) {
            $rows = db_connect()->table('messages')
                ->select('conversation_id, content')
                ->where('direction', 'outbound')
                ->where('sender_type', 'agent')
                ->whereIn('conversation_id', $chunk)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $out[(int) $r['conversation_id']][] = (string) $r['content'];
            }
        }

        return $out;
    }

    /**
     * @param list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function inboundMessages(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, self::IN_CHUNK) as $chunk) {
            $rows = db_connect()->table('messages')
                ->select('conversation_id, content, created_at')
                ->where('direction', 'inbound')
                ->where('sender_type', 'lead')
                ->whereIn('conversation_id', $chunk)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            array_push($out, ...$rows);
        }

        return $out;
    }

    /** Neutralise spreadsheet formula injection in exported cells. */
    private static function csvSafe(string $value): string
    {
        return $value !== '' && strpbrk($value[0], "=+-@\t\r") !== false ? "'" . $value : $value;
    }
}
