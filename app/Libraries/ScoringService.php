<?php
namespace App\Libraries;

use App\Models\Message;
use App\Models\IntentionLog;
use App\Models\Leads;

class ScoringService
{
    protected $messageModel;
    protected $intentionLogModel;
    protected $leadsModel;

    // Keywords and their score weights
    protected $positiveKeywords = [
        'comprar' => 15, 'compra' => 15, 'adquirir' => 15,
        'precio' => 10, 'costo' => 10, 'cuánto' => 10, 'cuanto' => 10,
        'visitar' => 20, 'visita' => 20, 'ver el inmueble' => 20, 'agendar' => 25,
        'presupuesto' => 15, 'financiamiento' => 15, 'crédito' => 15, 'credito' => 15,
        'interesado' => 12, 'interesada' => 12, 'me interesa' => 15,
        'disponible' => 8, 'disponibilidad' => 8,
        'habitaciones' => 8, 'habitación' => 8, 'metros' => 8,
        'urgente' => 15, 'pronto' => 10, 'lo antes posible' => 15,
        'alquilar' => 12, 'alquiler' => 12, 'arrendar' => 12,
        'negociar' => 10, 'oferta' => 10,
        'documentos' => 20, 'papeles' => 18, 'escritura' => 18,
        'mudanza' => 15, 'mudarme' => 15,
    ];

    protected $negativeKeywords = [
        'solo pregunto' => -10, 'curiosidad' => -8, 'no estoy seguro' => -5,
        'no me interesa' => -20, 'no gracias' => -15,
        'muy caro' => -8, 'fuera de presupuesto' => -10,
        'después' => -5, 'luego' => -5, 'otro momento' => -8,
    ];

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->intentionLogModel = new IntentionLog();
        $this->leadsModel = new Leads();
    }

    /**
     * Score a lead based on all conversation messages
     */
    public function scoreConversation(int $conversationId, int $leadId): array
    {
        $messages = $this->messageModel->where('conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        if (empty($messages)) {
            return ['score' => 0, 'label' => 'frio', 'reasoning' => 'Sin mensajes del lead'];
        }

        $score = 0;
        $reasons = [];
        $detectedInterest = null;
        $detectedBudget = null;
        $detectedZone = null;

        foreach ($messages as $msg) {
            $content = mb_strtolower($msg['content']);

            // Positive keywords
            foreach ($this->positiveKeywords as $keyword => $weight) {
                if (strpos($content, $keyword) !== false) {
                    $score += $weight;
                    $reasons[] = "Mencionó '{$keyword}' (+{$weight})";
                }
            }

            // Negative keywords
            foreach ($this->negativeKeywords as $keyword => $weight) {
                if (strpos($content, $keyword) !== false) {
                    $score += $weight; // weight is already negative
                    $reasons[] = "Mencionó '{$keyword}' ({$weight})";
                }
            }

            // Detect interest type
            if (preg_match('/(comprar|compra|adquirir|venta)/i', $content)) {
                $detectedInterest = 'compra';
            } elseif (preg_match('/(alquil|arrend|rent)/i', $content)) {
                $detectedInterest = 'alquiler';
            }

            // Detect budget
            if (preg_match('/\$?\s*(\d{1,3}(?:[.,]\d{3})*(?:\.\d{2})?)\s*(mil|k|usd|dolares|dólares)?/i', $content, $matches)) {
                $amount = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
                if (!empty($matches[2]) && in_array(strtolower($matches[2]), ['mil', 'k'])) {
                    $amount *= 1000;
                }
                if ($amount > 100) {
                    $detectedBudget = $amount;
                    $score += 15;
                    $reasons[] = "Presupuesto detectado: \${$amount} (+15)";
                }
            }

            // Detect zone interest
            $zones = ['las mercedes', 'chacao', 'altamira', 'la castellana', 'el rosal', 'los palos grandes', 'santa monica', 'el paraiso', 'la california', 'petare', 'baruta', 'el hatillo', 'los samanes', 'higuerote', 'la bonita', 'caracas', 'miranda', 'vargas', 'la guaira'];
            foreach ($zones as $zone) {
                if (strpos($content, $zone) !== false) {
                    $detectedZone = ucwords($zone);
                    $score += 10;
                    $reasons[] = "Zona de interés: {$detectedZone} (+10)";
                    break;
                }
            }
        }

        // Bonus for message count (engagement)
        $msgCount = count($messages);
        if ($msgCount >= 3) { $score += 5; $reasons[] = "3+ mensajes (+5)"; }
        if ($msgCount >= 5) { $score += 5; $reasons[] = "5+ mensajes (+5)"; }
        if ($msgCount >= 10) { $score += 10; $reasons[] = "10+ mensajes (+10)"; }

        // Bonus for response speed
        if (count($messages) >= 2) {
            $first = strtotime($messages[0]['created_at']);
            $last = strtotime($messages[count($messages) - 1]['created_at']);
            $hours = ($last - $first) / 3600;
            if ($hours < 1 && $msgCount >= 3) {
                $score += 10;
                $reasons[] = "Respuestas rápidas (+10)";
            }
        }

        // Clamp score 0-100
        $score = max(0, min(100, $score));

        // Determine label
        $label = $this->getLabel($score);

        // Get previous score
        $lead = $this->leadsModel->find($leadId);
        $previousScore = $lead ? ($lead['intention_score'] ?? 0) : 0;

        // Update lead
        $this->leadsModel->update($leadId, [
            'intention_score' => $score,
            'intention_label' => $label,
            'interest_type' => $detectedInterest,
            'budget_detected' => $detectedBudget,
            'zone_interest' => $detectedZone,
        ]);

        // Log the scoring
        $lastMessage = end($messages);
        $this->intentionLogModel->insert([
            'lead_id' => $leadId,
            'previous_score' => $previousScore,
            'new_score' => $score,
            'new_label' => $label,
            'trigger_message_id' => $lastMessage['id'] ?? null,
            'ai_reasoning' => implode('; ', $reasons),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'score' => $score,
            'label' => $label,
            'interest_type' => $detectedInterest,
            'budget_detected' => $detectedBudget,
            'zone_interest' => $detectedZone,
            'reasoning' => implode('; ', $reasons),
        ];
    }

    protected function getLabel(int $score): string
    {
        if ($score >= 76) return 'listo';
        if ($score >= 51) return 'caliente';
        if ($score >= 26) return 'tibio';
        return 'frio';
    }
}
