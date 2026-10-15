<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceFinancingInstallment;
use App\Models\FinanceFinancingPlan;
use App\Models\FinanceQuota;
use App\Models\Leads;
use Dompdf\Dompdf;
use Dompdf\Options;
use InvalidArgumentException;

class FinanceReceiptService
{
    /**
     * @return array<string, mixed>
     */
    public function buildContext(int $quotaId): array
    {
        $quota = (new FinanceQuota())->find($quotaId);
        if (! is_array($quota)) {
            throw new InvalidArgumentException('Cuota no encontrada.');
        }

        if (($quota['type'] ?? '') !== 'received') {
            throw new InvalidArgumentException('Solo las cuotas recibidas generan recibo.');
        }

        $lead = null;
        if (! empty($quota['lead_id'])) {
            $lead = (new Leads())->find((int) $quota['lead_id']);
        }

        $plan = null;
        if (! empty($quota['financing_plan_id'])) {
            $plan = (new FinanceFinancingPlan())->find((int) $quota['financing_plan_id']);
        }

        $installment = null;
        if (! empty($quota['installment_id'])) {
            $installment = (new FinanceFinancingInstallment())->find((int) $quota['installment_id']);
        }

        $amountUsd = (float) ($quota['amount_usd'] ?? $quota['amount'] ?? 0);
        $paymentDate = (string) ($quota['payment_date'] ?? $quota['receipt_date'] ?? date('Y-m-d'));
        $timestamp = strtotime($paymentDate) ?: time();

        $clientName = strtoupper(trim((string) ($lead['name'] ?? $quota['name'] ?? 'CLIENTE')));
        $nationalIdRaw = (string) ($lead['national_id'] ?? '');
        $nationalId = $this->formatNationalId($nationalIdRaw);
        $nationalIdSignature = $this->formatClientNationalIdSignature($nationalIdRaw);

        return [
            'quota'              => $quota,
            'lead'               => $lead,
            'plan'               => $plan,
            'installment'        => $installment,
            'receipt_number'     => (string) ($quota['receipt_number'] ?? ''),
            'city'               => getenv('FINANCE_RECEIPT_CITY') ?: 'CARACAS',
            'date_line'          => $this->formatReceiptDateLine($timestamp),
            'company_name'       => getenv('FINANCE_RECEIPT_COMPANY_NAME') ?: 'ASESORES RXI C.A,',
            'company_rif'        => getenv('FINANCE_RECEIPT_RIF') ?: 'J-403576750',
            'header_company_line'=> getenv('FINANCE_RECEIPT_HEADER_LINE') ?: 'ASESORES RM XXI, C.A. J-40357675-0',
            'representative_name'=> getenv('FINANCE_RECEIPT_REP_NAME') ?: 'ROSSANA MIGLIORE',
            'representative_id'  => $this->formatRepresentativeNationalId(getenv('FINANCE_RECEIPT_REP_ID') ?: 'V-15.161.333'),
            'client_name'        => $clientName,
            'client_national_id' => $nationalId,
            'client_national_id_signature' => $nationalIdSignature,
            'amount_usd'         => $amountUsd,
            'amount_formatted'   => FinanceAmountWords::formatUsdAmount($amountUsd),
            'amount_words'       => FinanceAmountWords::usdInWords($amountUsd),
            'concept'            => $this->buildConcept($quota, $installment, $plan),
            'missing_national_id'=> $nationalId === '____________',
            'footer_address'     => getenv('FINANCE_RECEIPT_ADDRESS') ?: 'Calle Madrid de la Mercedes, Centro Financiero Madrid, piso 11, oficina 1107, Miranda - Baruta, ZP 1010',
            'footer_phone'       => getenv('FINANCE_RECEIPT_PHONE') ?: '0424.141.3908',
            'footer_website'     => getenv('FINANCE_RECEIPT_WEBSITE') ?: 'www.tuasesorrm.com.ve',
            'footer_instagram'   => getenv('FINANCE_RECEIPT_INSTAGRAM') ?: "@tuasesorrm\n@realtors.rm\n@tiendainmueblerm",
        ];
    }

    public function renderHtml(int $quotaId, bool $forPdf = false): string
    {
        $context = $this->buildContext($quotaId);
        $context['forPdf'] = $forPdf;
        $context['assets'] = $this->receiptAssets($forPdf);

        return view('auth/finance/quota_receipt', $context);
    }

    public function generatePdf(int $quotaId): string
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $html = $this->renderHtml($quotaId, true);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->setChroot([ROOTPATH, FCPATH, WRITEPATH]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $pdf = (string) $dompdf->output();
        if (! str_starts_with($pdf, '%PDF')) {
            throw new \RuntimeException('No se pudo generar el PDF del recibo.');
        }

        return $pdf;
    }

    private function formatReceiptDateLine(int $timestamp): string
    {
        $months = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        $city = strtoupper(getenv('FINANCE_RECEIPT_CITY') ?: 'CARACAS');
        $day = (int) date('j', $timestamp);
        $month = $months[(int) date('n', $timestamp)] ?? strtoupper(date('F', $timestamp));
        $year = date('Y', $timestamp);

        return $city . ', ' . $day . ' DE ' . $month . ' DE ' . $year;
    }

    private function formatNationalId(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            return '____________';
        }

        $prefix = 'V';
        if (preg_match('/^([VEJP])-?\s*(.+)$/', $raw, $matches)) {
            $prefix = $matches[1];
            $raw = $matches[2];
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return '____________';
        }

        return $prefix . $this->formatVenezuelanIdNumber($digits);
    }

    private function formatClientNationalIdSignature(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            return '____________';
        }

        $prefix = 'V';
        if (preg_match('/^([VEJP])-?\s*(.+)$/', $raw, $matches)) {
            $prefix = $matches[1];
            $raw = $matches[2];
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return '____________';
        }

        return $prefix . '- ' . $this->formatVenezuelanIdNumber($digits);
    }

    /**
     * @return array<string, string>
     */
    private function receiptAssets(bool $forPdf): array
    {
        $files = [
            'logo'           => 'logo-main.jpg',
            'header_company' => 'header-company.png',
            'slogan'         => 'slogan.png',
            'qr'             => 'qr-code.png',
            'icon_location'  => 'icon-location.jpg',
        ];

        $assets = [];
        foreach ($files as $key => $filename) {
            $assets[$key] = $this->receiptAssetUri($filename, $forPdf);
        }

        return $assets;
    }

    private function receiptAssetUri(string $filename, bool $forPdf): string
    {
        $path = FCPATH . 'assets/finance/receipt/' . $filename;
        if (! is_file($path)) {
            return '';
        }

        if ($forPdf) {
            $mime = mime_content_type($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
        }

        return base_url('assets/finance/receipt/' . $filename);
    }

    private function formatRepresentativeNationalId(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            return '';
        }

        $prefix = 'V';
        if (preg_match('/^([VEJP])-?\s*(.+)$/', $raw, $matches)) {
            $prefix = $matches[1];
            $raw = $matches[2];
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return $raw;
        }

        return $prefix . '- ' . $this->formatVenezuelanIdNumber($digits);
    }

    private function formatVenezuelanIdNumber(string $digits): string
    {
        $length = strlen($digits);
        if ($length <= 3) {
            return $digits;
        }

        $last = substr($digits, -3);
        $middle = substr($digits, -6, 3);
        $first = substr($digits, 0, $length - 6);

        if ($first === '') {
            return $middle . '.' . $last;
        }

        return $first . '.' . $middle . '.' . $last;
    }

    /**
     * @param array<string, mixed>|null $quota
     * @param array<string, mixed>|null $installment
     * @param array<string, mixed>|null $plan
     */
    private function buildConcept(?array $quota, ?array $installment, ?array $plan): string
    {
        $propertyPart = $this->buildPropertyPhrase($plan);

        if (is_array($installment)) {
            $installmentNumber = (int) ($installment['installment_number'] ?? 0);
            $month = (int) ($quota['period_month'] ?? 0);
            $year = (int) ($quota['period_year'] ?? 0);
            $monthName = $this->monthNameEs($month);

            if ($installmentNumber > 0 && $monthName !== '') {
                return 'CUOTA N° ' . $installmentNumber
                    . ' correspondiente al mes de ' . strtoupper($monthName)
                    . ($year > 0 ? ' de ' . $year : '')
                    . $propertyPart;
            }
        }

        if (is_array($plan)) {
            $downPayment = (float) ($plan['down_payment'] ?? 0);
            $paid = (float) ($quota['amount_usd'] ?? $quota['amount'] ?? 0);
            $total = (float) ($plan['total_price'] ?? 0);
            if ($downPayment > 0 && abs($paid - $downPayment) < 0.02) {
                $pct = $total > 0 ? (int) round($downPayment / $total * 100) : 0;

                return 'INICIAL correspondiente al ' . $pct . '%' . $propertyPart;
            }
        }

        $month = (int) ($quota['period_month'] ?? 0);
        $year = (int) ($quota['period_year'] ?? 0);
        $monthName = $this->monthNameEs($month);
        if ($monthName !== '') {
            return 'PAGO correspondiente al mes de ' . strtoupper($monthName)
                . ($year > 0 ? ' de ' . $year : '')
                . $propertyPart;
        }

        return 'PAGO' . $propertyPart;
    }

    /**
     * @param array<string, mixed>|null $plan
     */
    private function buildPropertyPhrase(?array $plan): string
    {
        if (! is_array($plan)) {
            return '';
        }

        $unitRef = trim((string) ($plan['unit_ref'] ?? $plan['property_ref'] ?? ''));
        $project = trim((string) ($plan['project_name'] ?? ''));
        if ($unitRef === '' && $project === '') {
            return '';
        }

        $unitType = 'unidad';
        $haystack = strtolower($unitRef . ' ' . ($plan['notes'] ?? ''));
        if (str_contains($haystack, 'ofic')) {
            $unitType = 'oficina';
        } elseif (str_contains($haystack, 'apart') || str_contains($haystack, 'apto')) {
            $unitType = 'apartamento';
        }

        if ($unitRef !== '' && $project !== '') {
            return ', por la compra de la ' . $unitType . ' signada nro ' . $unitRef . ' en ' . strtoupper($project);
        }

        if ($project !== '') {
            return ', por la compra en ' . strtoupper($project);
        }

        return ', por la compra de la ' . $unitType . ' signada nro ' . $unitRef;
    }

    private function monthNameEs(int $month): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $months[$month] ?? '';
    }
}
