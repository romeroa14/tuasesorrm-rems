#!/usr/bin/env php
<?php

/**
 * Emite JSON payload tipo Meta Instagram webhook (stdout).
 *
 * Uso:
 *   export IG_ENTRY_ID=17841407185102827
 *   export SENDER_PSID=1234567890
 *   php scripts/crm-ai-webhook-json.php "Texto del usuario"
 */

declare(strict_types=1);

$text = $argv[1] ?? 'Hola, busco información de una propiedad';
$entry = getenv('IG_ENTRY_ID') ?: '17841407185102827';
$sender = getenv('SENDER_PSID') ?: '999888777666';
$ts = (int) (microtime(true) * 1000);
$mid = 'demo.mid.' . bin2hex(random_bytes(6));

$payload = [
    'object' => 'instagram',
    'entry'  => [
        [
            'id'        => $entry,
            'messaging' => [
                [
                    'sender'    => ['id' => $sender],
                    'recipient' => ['id' => $entry],
                    'timestamp' => $ts,
                    'message'   => [
                        'mid'  => $mid,
                        'text' => $text,
                    ],
                ],
            ],
        ],
    ],
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
