<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\CrmAttentionReport;
use App\Libraries\CrmPhoneExport;
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

    /** GET /app/crm/reports/data?from=&to=&mode=conversation|crm&accounts[]= */
    public function api_attention(): ResponseInterface
    {
        if (! $this->canViewReports()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Sin permiso']);
        }

        [$from, $to]  = $this->range();
        $crmAvailable = $this->crmInUse();
        $mode         = $this->mode($crmAvailable);
        $accounts     = $this->accounts();

        $conversations = $this->conversationsInRange($from, $to, $accounts);
        $ids           = array_column($conversations, 'id');
        $outbound      = $this->outboundTexts($ids);
        $report        = new CrmAttentionReport();

        if ($mode === 'crm') {
            $senders = $this->crmSenders($ids);
            $input   = [];
            foreach ($conversations as $c) {
                $input[] = [
                    'id'            => (int) $c['id'],
                    'human_replied' => isset($outbound[(int) $c['id']]),
                    'participants'  => $senders[(int) $c['id']] ?? [],
                ];
            }
            $summary = $report->summarizeParticipants($input);
        } else {
            $summary = $report->summarizeConversations($this->conversationInput($conversations, $outbound));
        }
        unset($summary['conversations']);

        return $this->response->setJSON([
            'status'             => 'success',
            'mode'               => $mode,
            'crm_available'      => $crmAvailable,
            'from'               => $from,
            'to'                 => $to,
            'accounts'           => $accounts,
            'accounts_available' => $this->accountsInRange($from, $to),
            'data'               => $summary,
        ]);
    }

    /** GET /app/crm/reports/phones?from=&to=&mode=conversation|crm&accounts[]=  (CSV download) */
    public function export_phones(): ResponseInterface
    {
        if (! $this->canViewReports()) {
            return $this->response->setStatusCode(403)->setBody('Sin permiso');
        }

        [$from, $to]   = $this->range();
        $mode          = $this->mode($this->crmInUse());
        $conversations = $this->conversationsInRange($from, $to, $this->accounts());
        $ids           = array_column($conversations, 'id');

        $attribution = $mode === 'crm'
            ? $this->crmSenders($ids)
            : (new CrmAttentionReport())->summarizeConversations(
                $this->conversationInput($conversations, $this->outboundTexts($ids))
            )['conversations'];

        $csv = CrmPhoneExport::csv(CrmPhoneExport::rows($conversations, $this->inboundMessages($ids), $attribution));

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="numeros-whatsapp_' . $mode . '_' . $from . '_' . $to . '.csv"')
            ->setBody($csv);
    }

    /** "crm" only once replies are sent from the CRM; otherwise the conversation (signature) mode. */
    private function mode(bool $crmAvailable): string
    {
        return $this->request->getGet('mode') === 'crm' && $crmAvailable ? 'crm' : 'conversation';
    }

    /** @return list<string> selected receiving accounts; empty means all */
    private function accounts(): array
    {
        $raw = $this->request->getGet('accounts');
        $raw = is_array($raw) ? $raw : ($raw === null || $raw === '' ? [] : [$raw]);

        return array_values(array_unique(array_filter(
            array_map(static fn ($a): string => trim((string) $a), $raw),
            static fn (string $a): bool => preg_match('/^[A-Za-z0-9._]{1,60}$/', $a) === 1
        )));
    }

    /** @return list<array{account: string, conversations: int}> */
    private function accountsInRange(string $from, string $to): array
    {
        $rows = db_connect()->table('conversations')
            ->select('recipient_ig_username AS account, COUNT(*) AS conversations')
            ->where('created_at >=', $from . ' 00:00:00')
            ->where('created_at <=', $to . ' 23:59:59')
            ->where('recipient_ig_username IS NOT NULL')
            ->where("recipient_ig_username <> ''")
            ->groupBy('recipient_ig_username')
            ->orderBy('conversations', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $r): array => ['account' => (string) $r['account'], 'conversations' => (int) $r['conversations']], $rows);
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

    /**
     * @param list<array<string, mixed>>  $conversations
     * @param array<int, list<string>>    $outbound
     *
     * @return list<array{id: int, human_replied: bool, outbound_texts: list<string>}>
     */
    private function conversationInput(array $conversations, array $outbound): array
    {
        $input = [];
        foreach ($conversations as $c) {
            $texts   = $outbound[(int) $c['id']] ?? [];
            $input[] = ['id' => (int) $c['id'], 'human_replied' => $texts !== [], 'outbound_texts' => $texts];
        }

        return $input;
    }

    /** True once any reply has been sent from the CRM (it carries the CRM user id). */
    private function crmInUse(): bool
    {
        return db_connect()->table('messages')
            ->where('direction', 'outbound')
            ->where('sender_type', 'agent')
            ->where('sender_id IS NOT NULL')
            ->countAllResults() > 0;
    }

    /**
     * CRM users who replied from the CRM, per conversation, in reply order.
     *
     * @param list<int|string> $ids
     *
     * @return array<int, list<string>>
     */
    private function crmSenders(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, self::IN_CHUNK) as $chunk) {
            $rows = db_connect()->table('messages m')
                ->select('m.conversation_id, u.full_name')
                ->join('users u', 'u.id = m.sender_id', 'inner')
                ->where('m.direction', 'outbound')
                ->where('m.sender_type', 'agent')
                ->whereIn('m.conversation_id', $chunk)
                ->orderBy('m.id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $name = trim((string) $r['full_name']);
                $cid  = (int) $r['conversation_id'];
                if ($name !== '' && ! in_array($name, $out[$cid] ?? [], true)) {
                    $out[$cid][] = $name;
                }
            }
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function conversationsInRange(string $from, string $to, array $accounts = []): array
    {
        $builder = db_connect()->table('conversations c');
        if ($accounts !== []) {
            $builder->whereIn('c.recipient_ig_username', $accounts);
        }

        return $builder
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
}
