<?php
/** @var array<string, mixed> $quota */
/** @var string $date_line */
/** @var string $company_name */
/** @var string $company_rif */
/** @var string $representative_name */
/** @var string $representative_id */
/** @var string $client_name */
/** @var string $client_national_id */
/** @var string $amount_words */
/** @var string $amount_formatted */
/** @var string $concept */
/** @var bool $missing_national_id */
$forPdf = $forPdf ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo <?= esc($receipt_number ?? '') ?></title>
    <style>
        @page { margin: 2.2cm 2.4cm; }
        body {
            font-family: "DejaVu Serif", "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.45;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .date-line {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 28px;
            letter-spacing: 0.02em;
        }
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 32px;
            letter-spacing: 0.08em;
        }
        .body-text {
            text-align: justify;
            text-justify: inter-word;
            font-size: 12pt;
        }
        .toolbar {
            margin-bottom: 16px;
            text-align: right;
        }
        .toolbar button, .toolbar a {
            display: inline-block;
            margin-left: 8px;
            padding: 8px 14px;
            text-decoration: none;
            border: 1px solid #333;
            background: #fff;
            color: #333;
            font-size: 12px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 11pt;
        }
        @media print {
            .toolbar, .warning { display: none; }
        }
    </style>
</head>
<body>
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

<div class="date-line"><?= esc($date_line) ?></div>
<h1 class="title">RECIBO</h1>

<p class="body-text">
    La Sociedad Mercantil <?= esc($company_name) ?>, inscrita ante el Registro Mercantil Séptimo del
    Distrito Capital, en fecha 11 de octubre del año dos mil trece (2013), Tomo 177-A, Número 34,
    e inscrita en el Registro de Información Fiscal (RIF) Nro. <?= esc($company_rif) ?>, representada en este
    acto por el ciudadano: <?= esc($representative_name) ?> venezolana, mayor de edad, de estado civil
    soltera, de este domicilio, titular de la Cédula de Identidad Nro. <?= esc($representative_id) ?>, en su carácter
    de Gerente General RECIBE del ciudadano <?= esc($client_name) ?> venezolano, mayor
    de edad, de estado civil soltero, de este domicilio, titulares de la Cédula de Identidad N° <?= esc($client_national_id) ?>, la cantidad de <?= esc($amount_words) ?>
    (<?= esc($amount_formatted) ?>). El concepto <?= esc($concept) ?>.
</p>
</body>
</html>
