<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\CrmAiPropertyToolRunner;
use App\Libraries\DeepSeekClient;
use App\Libraries\CrmPipelineEnrollment;
use App\Models\Conversation;
use App\Models\Funnels;
use App\Models\Leads;
use App\Models\Message;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Demo del agente ATC con DeepSeek + tools sobre tabla properties (solo lectura).
 *
 * Uso:
 *   php spark ai:crm-agent-demo --list-tables
 *   php spark ai:crm-agent-demo --catalog-approved
 *   php spark ai:crm-agent-demo --catalog-approved --catalog-limit=1000
 *   php spark ai:crm-agent-demo --message="Casas aprobadas hasta 200000 con 3 dormitorios"
 *   php spark ai:crm-agent-demo --simulate-inbound --message="¿Tienen aptos en renta?" --recipient-entry-id=17841407185102827
 *   php spark ai:crm-agent-demo --conversation-id=12 --message="Resume opciones más baratas"
 *
 * Webhook (simular POST como Meta): ver scripts/crm-ai-webhook-demo.sh
 */
class AiCrmAgentDemo extends BaseCommand
{
    protected $group       = 'CRM';
    protected $name        = 'ai:crm-agent-demo';
    protected $usage       = 'ai:crm-agent-demo [opciones]';
    protected $description = 'DeepSeek + tools BD (properties): demo sin exponer API keys';

    public function run(array $params)
    {
        $argv = $_SERVER['argv'] ?? [];

        if ($this->flagPresent($argv, '--list-tables')) {
            $this->listTables();

            return;
        }

        if ($this->flagPresent($argv, '--catalog-approved')) {
            $lim = $this->optionInt($argv, '--catalog-limit') ?? 500;
            $this->dumpApprovedCatalog(max(1, min(2000, $lim)));

            return;
        }

        $message = $this->optionValue($argv, '--message');
        if ($message === null || trim($message) === '') {
            CLI::error('Indica --message="tu pregunta", o --list-tables, o --catalog-approved.');
            CLI::write('Ejemplo: php spark ai:crm-agent-demo --message="Propiedades hasta 150000"', 'yellow');

            return;
        }

        $conversationId = $this->optionInt($argv, '--conversation-id');
        $simulate       = $this->flagPresent($argv, '--simulate-inbound');

        if ($simulate) {
            $recipient = $this->optionValue($argv, '--recipient-entry-id') ?? $this->defaultRecipientEntryId();
            if ($recipient === '') {
                CLI::error('Para --simulate-inbound define --recipient-entry-id=... (instagram_business_account.id del webhook) o META_WEBHOOK_ALLOWED_RECIPIENT_IG_IDS en .env.');

                return;
            }
            $conversationId = $this->simulateInboundMessage($message, $recipient);
            CLI::write('Simulación inbound: conversation_id=' . $conversationId, 'green');
            CLI::newLine();
        }

        $priorOpenAi = [];
        $latestUser    = trim($message);

        if ($conversationId !== null && $conversationId > 0) {
            $priorOpenAi = $this->conversationHistoryForModel($conversationId);
            if ($simulate) {
                $latestUser = '';
            }
        }

        if ($latestUser === '' && $priorOpenAi === []) {
            CLI::error('Sin contexto de mensajes para el modelo.');

            return;
        }

        try {
            $reply = $this->runDeepSeekAgentTurn($latestUser, $priorOpenAi);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());

            return;
        }

        CLI::write('--- Respuesta del agente ---', 'cyan');
        CLI::write($reply);
        CLI::newLine();
        CLI::write('Prueba webhook: bash scripts/crm-ai-webhook-demo.sh', 'dark_gray');
    }

    /**
     * @param list<string> $argv
     */
    private function flagPresent(array $argv, string $flag): bool
    {
        foreach ($argv as $a) {
            if ($a === $flag || str_starts_with((string) $a, $flag . '=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $argv
     */
    private function optionValue(array $argv, string $prefix): ?string
    {
        foreach ($argv as $a) {
            $a = (string) $a;
            if (str_starts_with($a, $prefix . '=')) {
                return substr($a, strlen($prefix) + 1);
            }
        }

        return null;
    }

    /**
     * @param list<string> $argv
     */
    private function optionInt(array $argv, string $prefix): ?int
    {
        $v = $this->optionValue($argv, $prefix);
        if ($v === null || ! ctype_digit($v)) {
            return null;
        }

        return (int) $v;
    }

    private function listTables(): void
    {
        CLI::write('Tablas en la BD actual (MySQL):', 'yellow');
        CLI::newLine();

        try {
            $db = Database::connect();
            $q = $db->query('SHOW TABLES');
            $rows = $q->getResultArray();
            $i = 1;
            foreach ($rows as $row) {
                $name = reset($row);
                CLI::write(sprintf('  %3d) %s', $i, (string) $name));
                $i++;
            }
            CLI::newLine();
            CLI::write('CRM / ATC relevantes: conversations, messages, leads, properties (+ joins municipality, state, status, …)', 'cyan');
        } catch (\Throwable $e) {
            CLI::error('No se pudo conectar a MySQL: ' . $e->getMessage());
        }
    }

    private function dumpApprovedCatalog(int $maxRows): void
    {
        CLI::write(
            'Catálogo que ve el agente (solo status.name = «Aprobado»). Mismas columnas que la tool search_properties.',
            'yellow'
        );
        CLI::write('Límite filas: ' . max(1, min(2000, $maxRows)), 'dark_gray');
        CLI::newLine();

        try {
            $rows = CrmAiPropertyToolRunner::listApprovedCatalogSnapshot($maxRows);
            CLI::write(json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            CLI::newLine();
            CLI::write('Total filas: ' . count($rows), 'green');
        } catch (\Throwable $e) {
            CLI::error('Error consultando propiedades: ' . $e->getMessage());
        }
    }

    private function defaultRecipientEntryId(): string
    {
        $raw = getenv('META_WEBHOOK_ALLOWED_RECIPIENT_IG_IDS');
        if ($raw === false || trim((string) $raw) === '') {
            return '';
        }
        $parts = array_filter(array_map('trim', explode(',', trim((string) $raw))));

        return $parts[0] ?? '';
    }

    private function simulateInboundMessage(string $text, string $recipientIgId): int
    {
        $externalId = 'spark_demo_' . bin2hex(random_bytes(6));

        $funnelModel = new Funnels();
        $igFunnel = $funnelModel->where('name LIKE', '%Instagram DM%')->first();
        $funnelId = $igFunnel ? $igFunnel['id'] : 33;

        $leadsModel = new Leads();
        $leadId = $leadsModel->insert([
            'name'               => 'Demo IA ' . substr($externalId, -6),
            'phone'              => '',
            'email'              => '',
            'instagram_username' => $externalId,
            'id_user'            => 1,
            'id_funnel'          => $funnelId,
            'id_housingtype'     => 1,
            'id_businessmodel'   => 1,
            'observation'        => 'Lead demo spark ai:crm-agent-demo --simulate-inbound',
            'status'             => 'Activo',
            'intention_score'    => 0,
            'intention_label'    => 'frio',
        ]);

        $convModel = new Conversation();
        $conversationId = (int) $convModel->insert([
            'lead_id'               => $leadId,
            'channel'               => 'instagram',
            'external_id'           => $externalId,
            'external_username'     => $externalId,
            'recipient_ig_id'       => $recipientIgId,
            'recipient_ig_username' => null,
            'status'                => 'open',
            'last_message_at'       => date('Y-m-d H:i:s'),
            'unread_count'          => 1,
        ]);

        $msgModel = new Message();
        $msgModel->insert([
            'conversation_id'     => $conversationId,
            'direction'           => 'inbound',
            'sender_type'         => 'lead',
            'content'             => $text,
            'content_type'        => 'text',
            'external_message_id' => 'spark_mid_' . bin2hex(random_bytes(4)),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        CrmPipelineEnrollment::ensureLeadOnPipeline((int) $leadId);

        return $conversationId;
    }

    /**
     * @return list<array{role:string, content:string}>
     */
    private function conversationHistoryForModel(int $conversationId): array
    {
        $msgModel = new Message();
        $rows = $msgModel->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->limit(24)
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $role = ($r['direction'] ?? '') === 'outbound' ? 'assistant' : 'user';
            $content = (string) ($r['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $out[] = ['role' => $role, 'content' => $content];
        }

        return $out;
    }

    /**
     * @param list<array{role:string, content:string}> $priorMessages Historial previo (ej. desde messages). Si $latestUserMessage es '', el último turno debe estar ya en priorMessages.
     */
    private function runDeepSeekAgentTurn(string $latestUserMessage, array $priorMessages): string
    {
        $system = <<<'TXT'
Eres un asistente de atención al cliente inmobiliario (CRM REMS). Responde en español, con tono profesional y breve.
Solo puedes basarte en datos devueltos por las herramientas (catálogo interno). No inventes precios ni direcciones.
Si las herramientas no devuelven resultados, dilo y pide un criterio más claro (presupuesto, zona, dormitorios).
TXT;

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $priorMessages
        );

        if ($latestUserMessage !== '') {
            $messages[] = ['role' => 'user', 'content' => $latestUserMessage];
        }

        $tools = CrmAiPropertyToolRunner::toolDefinitions();

        $maxIterations = 8;
        $lastAssistantText = '';

        for ($i = 0; $i < $maxIterations; $i++) {
            $payload = [
                'model'       => DeepSeekClient::defaultModel(),
                'messages'    => $messages,
                'tools'       => $tools,
                'tool_choice' => 'auto',
            ];

            $resp = DeepSeekClient::chatCompletions($payload);
            $choice = $resp['choices'][0] ?? null;
            $msg = is_array($choice) ? ($choice['message'] ?? null) : null;

            if (! is_array($msg)) {
                throw new \RuntimeException('Respuesta DeepSeek sin message.');
            }

            $toolCalls = $msg['tool_calls'] ?? null;

            if (is_array($toolCalls) && $toolCalls !== []) {
                $messages[] = $msg;

                foreach ($toolCalls as $tc) {
                    if (! is_array($tc)) {
                        continue;
                    }
                    $id = (string) ($tc['id'] ?? '');
                    $fn = $tc['function'] ?? null;
                    $name = is_array($fn) ? (string) ($fn['name'] ?? '') : '';
                    $args = is_array($fn) ? (string) ($fn['arguments'] ?? '{}') : '{}';
                    $result = CrmAiPropertyToolRunner::execute($name, $args);
                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $id,
                        'content'      => $result,
                    ];
                }

                continue;
            }

            $lastAssistantText = (string) ($msg['content'] ?? '');

            break;
        }

        return $lastAssistantText !== '' ? $lastAssistantText : '(Sin texto final del modelo)';
    }
}
