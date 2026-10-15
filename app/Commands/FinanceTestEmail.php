<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\FinanceMailService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Prueba el envío SMTP (Resend u otro) configurado en .env.
 *
 * Uso:
 *   php spark finance:test-email --to=cobranza@tuasesorrm.com.ve
 */
class FinanceTestEmail extends BaseCommand
{
    protected $group       = 'Finance';

    protected $name        = 'finance:test-email';

    protected $usage       = 'finance:test-email --to=email@dominio.com';

    protected $description   = 'Envía un correo de prueba usando SMTP/Resend de finanzas';

    protected $options     = [
        '--to' => 'Correo destino (obligatorio)',
    ];

    public function run(array $params)
    {
        $to = trim((string) (CLI::getOption('to') ?? ''));
        if ($to === '') {
            foreach ($params as $param) {
                if (str_starts_with((string) $param, '--to=')) {
                    $to = trim(substr((string) $param, 5));
                    break;
                }
            }
        }
        if ($to === '' && isset($params[0]) && ! str_starts_with((string) $params[0], '-')) {
            $to = trim((string) $params[0]);
        }
        if ($to === '') {
            CLI::error('Indica --to=correo@ejemplo.com');

            return 1;
        }

        $mail = new FinanceMailService();
        $smtp = $mail->resolveSmtpSettings();

        CLI::write('Proveedor: ' . ($smtp['provider'] ?? 'smtp'), 'cyan');
        CLI::write('Host: ' . ($smtp['host'] ?? '—'));
        CLI::write('Puerto: ' . (string) ($smtp['port'] ?? '—'));
        CLI::write('Usuario SMTP: ' . ($smtp['user'] !== '' ? $smtp['user'] : '—'));
        CLI::write('From: ' . ($smtp['from_email'] !== '' ? $smtp['from_email'] : '—'));
        CLI::newLine();

        if (($smtp['error'] ?? null) !== null) {
            CLI::error('Configuración incompleta: ' . $smtp['error']);

            return 1;
        }

        $subject = 'Prueba SMTP Finanzas — Asesores RM';
        $body = '<p>Este es un correo de prueba del módulo de finanzas (recibos de cuota).</p>'
            . '<p>Si lo recibes, SMTP/Resend está configurado correctamente.</p>';

        CLI::write('Enviando a ' . $to . ' ...', 'yellow');
        $result = $mail->sendHtml($to, $subject, $body);

        if (! empty($result['sent'])) {
            CLI::write('Correo enviado correctamente.', 'green');

            return 0;
        }

        CLI::error('Fallo: ' . (string) ($result['error'] ?? 'desconocido'));

        return 1;
    }
}
