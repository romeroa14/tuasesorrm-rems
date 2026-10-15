<?php
/** @var array<string, mixed> $quota */
/** @var string $date_line */
/** @var string $company_name */
/** @var string $company_rif */
/** @var string $header_company_line */
/** @var string $representative_name */
/** @var string $representative_id */
/** @var string $client_name */
/** @var string $client_national_id */
/** @var string $client_national_id_signature */
/** @var string $amount_words */
/** @var string $amount_formatted */
/** @var string $concept */
/** @var bool $missing_national_id */
/** @var string $footer_address */
/** @var string $footer_phone */
/** @var string $footer_website */
/** @var string $footer_instagram */
/** @var array<string, string> $assets */
$forPdf = $forPdf ?? false;
$assets = $assets ?? [];
$instagramLines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($footer_instagram ?? '')))));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo <?= esc($receipt_number ?? '') ?></title>
    <style>
        @page {
            size: letter portrait;
            margin: 0;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            color: #222;
        }
        body {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.35;
            background: <?= $forPdf ? '#fff' : '#525659' ?>;
        }
        .page-shell {
            padding: <?= $forPdf ? '0' : '24px 16px 32px' ?>;
        }
        .receipt-sheet {
            position: relative;
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto;
            padding: 0.55in 0.72in 0.45in;
            background: #fff;
            overflow: hidden;
            <?= $forPdf ? '' : 'box-shadow: 0 2px 14px rgba(0,0,0,.35);' ?>
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 4.8in;
            transform: translate(-50%, -52%);
            opacity: 0.07;
            z-index: 0;
            text-align: center;
        }
        .watermark img {
            width: 100%;
            height: auto;
        }
        .receipt-content {
            position: relative;
            z-index: 1;
            min-height: 9.8in;
        }
        .receipt-header {
            width: 100%;
            margin-bottom: 0.18in;
        }
        .receipt-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-header td {
            vertical-align: top;
            border: 0;
            padding: 0;
        }
        .logo-cell {
            width: 42%;
        }
        .logo-cell img {
            width: 1.55in;
            height: auto;
        }
        .header-right {
            text-align: right;
            padding-top: 0.05in;
        }
        .header-company img {
            width: 2.35in;
            height: auto;
            display: inline-block;
        }
        .header-slogan img {
            width: 1.35in;
            height: auto;
            display: inline-block;
            margin: 0.04in 0 0.06in;
        }
        .header-date {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.01em;
        }
        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0.22in 0 0.28in;
            letter-spacing: 0.06em;
        }
        .body-text {
            text-align: justify;
            font-size: 10.5pt;
            line-height: 1.42;
            margin: 0 0 0.35in;
        }
        .body-text strong {
            font-weight: bold;
        }
        .signatures {
            width: 100%;
            margin-top: 0.55in;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            border: 0;
            padding: 0;
            font-size: 10pt;
            line-height: 1.35;
        }
        .sig-left {
            text-align: center;
            padding-right: 0.2in;
        }
        .sig-right {
            text-align: center;
            padding-left: 0.2in;
        }
        .sig-name {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 0.04in;
        }
        .sig-id {
            font-weight: bold;
            font-size: 10pt;
        }
        .sig-label {
            font-size: 9.5pt;
            margin-bottom: 0.05in;
        }
        .receipt-footer {
            position: absolute;
            left: 0.72in;
            right: 0.72in;
            bottom: 0.38in;
        }
        .footer-rule {
            border: 0;
            border-top: 1px solid #888;
            margin: 0 0 0.12in;
        }
        .footer-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-grid td {
            vertical-align: top;
            border: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.25;
        }
        .footer-qr img {
            width: 0.72in;
            height: 0.72in;
        }
        .footer-contact {
            padding: 0 0.12in;
        }
        .footer-contact-row {
            margin-bottom: 0.04in;
        }
        .footer-contact-row img.icon {
            width: 11px;
            height: 11px;
            vertical-align: -1px;
            margin-right: 4px;
        }
        .footer-contact em {
            font-style: italic;
            font-weight: bold;
        }
        .footer-website {
            font-weight: bold;
            font-size: 9pt;
        }
        .footer-social {
            text-align: right;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .toolbar {
            max-width: 8.5in;
            margin: 0 auto 12px;
            text-align: right;
        }
        .toolbar button, .toolbar a {
            display: inline-block;
            margin-left: 8px;
            padding: 8px 14px;
            text-decoration: none;
            border: 1px solid #ccc;
            background: #fff;
            color: #333;
            font-size: 12px;
            border-radius: 4px;
        }
        .warning {
            max-width: 8.5in;
            margin: 0 auto 12px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px 12px;
            font-size: 11pt;
            border-radius: 4px;
        }
        @media print {
            body { background: #fff; }
            .page-shell { padding: 0; }
            .receipt-sheet { box-shadow: none; }
            .toolbar, .warning { display: none !important; }
        }
    </style>
</head>
<body>
<div class="page-shell">
<?php if (! $forPdf): ?>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="<?= esc(base_url('/app/finance/quotas/receipt/' . ($quota['id'] ?? ''))) ?>/pdf" target="_blank">Descargar PDF</a>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>
    <?php if (! empty($missing_national_id)): ?>
        <div class="warning">
            Falta la cédula del cliente en el CRM. Actualiza el campo <strong>Cédula</strong> en el lead para que aparezca en el recibo.
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="receipt-sheet">
    <?php if (! empty($assets['logo'])): ?>
    <div class="watermark">
        <img src="<?= esc($assets['logo']) ?>" alt="">
    </div>
    <?php endif; ?>

    <div class="receipt-content">
        <header class="receipt-header">
            <table>
                <tr>
                    <td class="logo-cell">
                        <?php if (! empty($assets['logo'])): ?>
                            <img src="<?= esc($assets['logo']) ?>" alt="ASESORES RM">
                        <?php endif; ?>
                    </td>
                    <td class="header-right">
                        <?php if (! empty($assets['header_company'])): ?>
                            <div class="header-company"><img src="<?= esc($assets['header_company']) ?>" alt="<?= esc($header_company_line ?? '') ?>"></div>
                        <?php else: ?>
                            <div><?= esc($header_company_line ?? '') ?></div>
                        <?php endif; ?>
                        <?php if (! empty($assets['slogan'])): ?>
                            <div class="header-slogan"><img src="<?= esc($assets['slogan']) ?>" alt="¡De la mano contigo!"></div>
                        <?php else: ?>
                            <div><em>¡De la mano contigo!</em></div>
                        <?php endif; ?>
                        <div class="header-date"><?= esc($date_line) ?></div>
                    </td>
                </tr>
            </table>
        </header>

        <h1 class="doc-title">RECIBO</h1>

        <p class="body-text">
            La Sociedad Mercantil <strong><?= esc(rtrim((string) $company_name, ',')) ?></strong>, inscrita ante el Registro Mercantil Séptimo del
            Distrito Capital, en fecha 11 de octubre del año dos mil trece (2013), Tomo 177-A, Número 34,
            e inscrita en el Registro de Información Fiscal (RIF) Nro. <?= esc($company_rif) ?>, representada en este
            acto por el ciudadano: <strong><?= esc($representative_name) ?></strong> venezolana, mayor de edad, de estado civil
            soltera, de este domicilio, titular de la Cédula de Identidad Nro. <strong><?= esc($representative_id) ?></strong>, en su carácter
            de Gerente General RECIBE del ciudadano <strong><?= esc($client_name) ?></strong> venezolano, mayor
            de edad, de estado civil soltero, de este domicilio, titulares de la Cedula de Identidad N° <strong><?= esc($client_national_id) ?></strong>, la cantidad de <strong><?= esc($amount_words) ?>
            (<?= esc($amount_formatted) ?>)</strong>. El concepto <?= esc($concept) ?>.
        </p>

        <div class="signatures">
            <table>
                <tr>
                    <td class="sig-left">
                        <div class="sig-name"><?= esc($client_name) ?></div>
                        <div class="sig-id">N° <?= esc($client_national_id_signature) ?></div>
                    </td>
                    <td class="sig-right">
                        <div class="sig-label">POR "ASESORES RM"</div>
                        <div class="sig-name"><?= esc($representative_name) ?></div>
                        <div class="sig-id"><?= esc($representative_id) ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <footer class="receipt-footer">
        <hr class="footer-rule">
        <div class="footer-grid">
            <table>
                <tr>
                    <td style="width:14%;">
                        <?php if (! empty($assets['qr'])): ?>
                            <div class="footer-qr"><img src="<?= esc($assets['qr']) ?>" alt="QR"></div>
                        <?php endif; ?>
                    </td>
                    <td style="width:56%;" class="footer-contact">
                        <div class="footer-contact-row">
                            <?php if (! empty($assets['icon_location'])): ?>
                                <img class="icon" src="<?= esc($assets['icon_location']) ?>" alt="">
                            <?php endif; ?>
                            <em><?= esc($footer_address ?? '') ?></em>
                        </div>
                        <div class="footer-contact-row">
                            Teléfonos.: <?= esc($footer_phone ?? '') ?>
                            &nbsp;&nbsp;&nbsp;
                            <span class="footer-website"><?= esc($footer_website ?? '') ?></span>
                        </div>
                    </td>
                    <td style="width:30%;" class="footer-social">
                        <?php foreach ($instagramLines as $line): ?>
                            <?= esc($line) ?><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </div>
    </footer>
</div>
</div>
</body>
</html>
