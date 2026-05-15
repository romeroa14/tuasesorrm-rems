<?php

namespace App\Libraries;

use App\Models\Message;
use App\Models\IntentionLog;
use App\Models\Leads;

/**
 * Análisis de intención mediante DeepSeek LLM con fallback keyword.
 * Reemplazo progresivo del ScoringService.
 */
class AiScoringService
{
    protected $messageModel;
    protected $intentionLogModel;
    protected $leadsModel;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->intentionLogModel = new IntentionLog();
        $this->leadsModel = new Leads();
    }

    /**
     * Analiza la conversación y actualiza intention_score + intention_label en el lead.
     * Usa DeepSeek si está disponible, fallback a keywords.
     */
    public function scoreConversation(int $conversationId, int $leadId): array
    {
        $messages = $this->messageModel->where('conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        if (empty($messages)) {
            return $this->saveResult($leadId, 0, 'frio', 'Sin mensajes del lead');
        }

        // Build conversation history for LLM
        $history = '';
        foreach ($messages as $msg) {
            $history .= "Cliente: {$msg['content']}\n";
            // Limit history to last 1000 chars
            if (strlen($history) > 1000) break;
        }

        // Try LLM first
        try {
            $result = $this->analyzeWithLLM($history);
            if ($result !== null) {
                $this->extractLeadData($messages, $leadId);
                return $this->saveResult($leadId, $result['score'], $result['label'], $result['reasoning']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'AiScoringService LLM failed: ' . $e->getMessage());
        }

        // Fallback: keyword scoring
        $scorer = new ScoringService();
        return $scorer->scoreConversation($conversationId, $leadId);
    }

    /**
     * Analyze text with DeepSeek.
     */
    private function analyzeWithLLM(string $history): ?array
    {
        $prompt = <<<PROMPT
Eres un clasificador de intención de compra inmobiliaria para Asesores RM (Venezuela).
Analiza el mensaje del cliente y clasifica en UNA categoría:

- frio (0-25): Saludo, sin interés explícito en propiedades
- tibio (26-50): Pregunta información general, precios, disponibilidad
- caliente (51-75): Quiere visitar, agendar cita, detalles específicos
- listo (76-100): Listo para comprar, tiene presupuesto, quiere concretar

Además extrae: interest_type (compra/alquiler/null), budget_detected (número sin formato o null), zone_interest (zona mencionada o null)

Responde SOLO JSON:
{"label": "categoria", "score": 0-100, "interest": null, "budget": null, "zone": null}

Mensaje:
{$history}
PROMPT;

        $response = DeepSeekClient::chatCompletions([
            'model' => getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un clasificador JSON. Responde siempre con JSON válido.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300,
            'temperature' => 0.1,
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if (!is_array($parsed) || !isset($parsed['label'], $parsed['score'])) {
            return null;
        }

        $validLabels = ['frio', 'tibio', 'caliente', 'listo'];
        $label = in_array($parsed['label'], $validLabels) ? $parsed['label'] : 'frio';
        $score = max(0, min(100, (int) $parsed['score']));

        return [
            'label' => $label,
            'score' => $score,
            'reasoning' => 'LLM: ' . ($parsed['reasoning'] ?? ''),
            'interest' => $parsed['interest'] ?? null,
            'budget' => $parsed['budget'] ?? null,
            'zone' => $parsed['zone'] ?? null,
        ];
    }

    /**
     * Extrae datos del lead detectados por LLM (interest, budget, zone).
     */
    private function extractLeadData(array $messages, int $leadId): void
    {
        $text = implode(' ', array_column($messages, 'content'));
        if (strlen($text) > 2000) $text = substr($text, -2000);

        try {
            $prompt = "Del siguiente texto extrae SOLO JSON:\n"
                . "{\"interest\": \"compra|alquiler|null\", \"budget\": \"número|detectado|null\", \"zone\": \"zona|null\"}\n"
                . "Texto: {$text}";

            $response = DeepSeekClient::chatCompletions([
                'model' => getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 200,
                'temperature' => 0.1,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($content, true);
            if (!is_array($parsed)) return;

            $update = [];
            if (!empty($parsed['interest'])) $update['interest_type'] = $parsed['interest'];
            if (!empty($parsed['budget'])) $update['budget_detected'] = preg_replace('/[^0-9.]/', '', (string) $parsed['budget']);
            if (!empty($parsed['zone'])) $update['zone_interest'] = $parsed['zone'];

            if (!empty($update)) {
                $this->leadsModel->update($leadId, $update);
            }
        } catch (\Throwable $e) {
            log_message('error', 'AiScoringService::extractLeadData: ' . $e->getMessage());
        }
    }

    /**
     * Guarda el resultado en leads + intention_log.
     */
    private function saveResult(int $leadId, int $score, string $label, string $reasoning): array
    {
        $lead = $this->leadsModel->find($leadId);
        if (!$lead) {
            return ['score' => 0, 'label' => 'frio', 'reasoning' => 'Lead no encontrado'];
        }

        $oldScore = (int) ($lead['intention_score'] ?? 0);
        $oldLabel = $lead['intention_label'] ?? 'frio';

        $this->leadsModel->update($leadId, [
            'intention_score' => $score,
            'intention_label' => $label,
        ]);

        $this->intentionLogModel->insert([
            'lead_id' => $leadId,
            'previous_score' => $oldScore,
            'new_score' => $score,
            'previous_label' => $oldLabel,
            'new_label' => $label,
            'trigger_message' => substr($reasoning, 0, 255),
        ]);

        return ['score' => $score, 'label' => $label, 'reasoning' => $reasoning];
    }
}
