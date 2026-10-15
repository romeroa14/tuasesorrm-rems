<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Ubica un lead sin agente en una columna activa del catálogo trackingstatus.
 * Jev devuelve una opción tipada y una confianza. Por debajo del umbral el lead sigue en Sin asignar.
 */
class JevPipelineClassifier
{
    public const CONFIDENCE_MIN = 0.75;

    public function placeUnassignedLead(int $leadId, int $conversationId): ?int
    {
        $report = $this->report($leadId, $conversationId, true);
        $statusId = $report['status_id'] ?? null;

        return is_int($statusId) ? $statusId : null;
    }

    /**
     * @return array{status_id:?int,status_name:string,confidence:float,applied:bool,reason:string}
     */
    public function report(int $leadId, int $conversationId, bool $persist): array
    {
        $empty = [
            'status_id' => null,
            'status_name' => '',
            'confidence' => 0.0,
            'applied' => false,
            'reason' => 'sin decision',
        ];

        $key = getenv('TYPESAFE_API_KEY');
        if ($leadId < 1 || $conversationId < 1 || $key === false || trim((string) $key) === '') {
            $empty['reason'] = 'sin clave o sin conversacion';

            return $empty;
        }

        $db = \Config\Database::connect();
        $assigned = $db->query('SELECT id FROM assignedclients WHERE lead_id = ? LIMIT 1', [$leadId])->getRowArray();
        if ($assigned) {
            $empty['reason'] = 'ya tiene agente';

            return $empty;
        }

        $statuses = $db->query(
            'SELECT id, name, classifier_hint FROM trackingstatus WHERE is_active = 1 ORDER BY id ASC'
        )->getResultArray();
        if (count($statuses) < 2) {
            $empty['reason'] = 'faltan estatus activos';

            return $empty;
        }

        $messages = $db->query(
            'SELECT content FROM messages WHERE conversation_id = ? AND direction = ? ORDER BY id DESC LIMIT 8',
            [$conversationId, 'inbound']
        )->getResultArray();
        $lines = [];
        foreach (array_reverse($messages) as $message) {
            $line = trim((string) ($message['content'] ?? ''));
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        $state = trim(implode("\n", $lines));
        if ($state === '') {
            $empty['reason'] = 'sin mensajes del cliente';

            return $empty;
        }
        if (strlen($state) > 2000) {
            $state = substr($state, -2000);
        }

        $criteria = [];
        $names = [];
        foreach ($statuses as $status) {
            $hint = trim((string) ($status['classifier_hint'] ?? ''));
            $criteria[(string) $status['id']] = $hint !== '' ? $hint : (string) $status['name'];
            $names[(string) $status['id']] = (string) $status['name'];
        }

        $answer = $this->ask(trim((string) $key), $state, $criteria);
        if ($answer === null) {
            $empty['reason'] = 'jev no respondio';

            return $empty;
        }

        $choice = (string) ($answer['choice'] ?? '');
        $confidence = (float) ($answer['confidence'] ?? 0);
        $statusName = $names[$choice] ?? '';
        if (! isset($criteria[$choice])) {
            return [
                'status_id' => null,
                'status_name' => '',
                'confidence' => $confidence,
                'applied' => false,
                'reason' => 'opcion fuera del catalogo',
            ];
        }
        if ($confidence < self::CONFIDENCE_MIN) {
            return [
                'status_id' => (int) $choice,
                'status_name' => $statusName,
                'confidence' => $confidence,
                'applied' => false,
                'reason' => 'confianza por debajo de 0.75',
            ];
        }

        $statusId = (int) $choice;
        if ($persist) {
            $db->table('leads')->update(['pipeline_status_id' => $statusId], ['id' => $leadId]);
        }

        return [
            'status_id' => $statusId,
            'status_name' => $statusName,
            'confidence' => $confidence,
            'applied' => $persist,
            'reason' => $persist ? 'ubicado en el pipeline' : 'solo lectura',
        ];
    }

    /**
     * @param array<string, string> $criteria
     * @return array<string, mixed>|null
     */
    private function ask(string $key, string $state, array $criteria): ?array
    {
        try {
            $client = \Config\Services::curlrequest(['timeout' => 8, 'http_errors' => false]);
            $response = $client->post('https://api.typesafe.ai/v1/systemone', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'jev-latest',
                    'state' => $state,
                    'questions' => [
                        'pipeline_status' => [
                            'type' => 'choice',
                            'instructions' => 'Elige el estatus de seguimiento que describe lo que el cliente dijo. No inventes una opción.',
                            'criteria' => $criteria,
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'JevPipelineClassifier ' . $e->getMessage());

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            log_message('error', 'JevPipelineClassifier HTTP ' . $response->getStatusCode());

            return null;
        }

        $body = json_decode((string) $response->getBody(), true);
        $answer = is_array($body) ? ($body['answers']['pipeline_status'] ?? null) : null;

        return is_array($answer) ? $answer : null;
    }
}
