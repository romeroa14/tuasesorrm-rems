<?php

declare(strict_types=1);

namespace App\Controllers;

use Config\Services;

/**
 * Vista interna + proxy al microservicio Python `agente` (POST /v1/chat).
 */
class AiAgentController extends BaseController
{
    private const DEFAULT_AGENT_CHAT_URL = 'http://127.0.0.1:8090/v1/chat';

    private const MAX_HISTORY_MESSAGES = 80;

    /**
     * Página Agente chat (módulo AI).
     */
    public function agent_chat()
    {
        $data = [
            'title' => 'Agente IA',
            'slogan' => ' | Asesores RM',
            'view' => 'auth/ai/agent_chat',
        ];

        return view('template/header/header', $data)
            . view('template/sidebar/sidebar', $data)
            . view('template/navbar/navbar', $data)
            . view('auth/ai/agent_chat', $data)
            . view('template/footer/footer', $data);
    }

    /**
     * Proxifica JSON al servicio agente (evita CORS y oculta URL interna al navegador).
     */
    public function api_chat()
    {
        if (! $this->request->is('json')) {
            $raw = $this->request->getBody();
            $decoded = json_decode($raw, true);

            if (! is_array($decoded)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Se esperaba JSON (Content-Type application/json).',
                ]);
            }
            $payload = $decoded;
        } else {
            $payload = $this->request->getJSON(true);
            if (! is_array($payload)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Cuerpo JSON inválido.',
                ]);
            }
        }

        $message = isset($payload['message']) ? trim((string) $payload['message']) : '';
        if ($message === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'El campo message es obligatorio.',
            ]);
        }

        $history = $this->normalizeHistory($payload['history'] ?? []);
        $debug = ! empty($payload['debug']);

        $url = getenv('AI_AGENT_URL');
        $url = ($url !== false && trim((string) $url) !== '')
            ? trim((string) $url)
            : self::DEFAULT_AGENT_CHAT_URL;

        // Try Python agent first, fallback to PHP DeepSeek
        if ($url !== self::DEFAULT_AGENT_CHAT_URL && $url !== '') {
            $result = $this->callPythonAgent($message, $history, $debug, $url);
            if ($result !== null) return $result;
        }

        // Fallback: use PHP DeepSeekClient directly
        return $this->callPhpDeepSeek($message, $history, $debug);
    }

    /**
     * Try calling the Python agent microservice.
     */
    private function callPythonAgent(string $message, array $history, bool $debug, string $url): ?ResponseInterface
    {
        $outgoing = ['message' => $message, 'history' => $history, 'debug' => $debug];

        try {
            $client = Services::curlrequest(['timeout' => 120, 'http_errors' => false]);
            $r = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body' => json_encode($outgoing, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'Python agent unreachable, falling back to PHP DeepSeek: ' . $e->getMessage());
            return null;
        }

        $code = $r->getStatusCode();
        $body = (string) $r->getBody();
        $json = json_decode($body, true);

        if (is_array($json)) {
            return $this->response->setStatusCode($code)->setJSON($json);
        }

        return null;
    }

    /**
     * Direct PHP DeepSeek + tools as fallback.
     */
    private function callPhpDeepSeek(string $message, array $history, bool $debug): ResponseInterface
    {
        $tools = \App\Libraries\CrmAiPropertyToolRunner::toolDefinitions();

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => file_exists(ROOTPATH . 'agente/agente/prompts.py')
            ? $this->extractSystemPrompt()
            : 'Eres el asistente virtual de Asesores RM. Ayudas a clientes a encontrar propiedades.'];

        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = \App\Libraries\DeepSeekClient::chatCompletions([
                'model' => getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat',
                'messages' => $messages,
                'tools' => $tools,
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ]);

            $choice = $response['choices'][0] ?? [];
            $reply = $choice['message']['content'] ?? '';
            $toolCalls = $choice['message']['tool_calls'] ?? [];
            $toolResults = [];

            // Handle tool calls
            foreach ($toolCalls as $tc) {
                $fn = $tc['function'];
                $args = json_decode($fn['arguments'] ?? '{}', true) ?: [];
                $result = \App\Libraries\CrmAiPropertyToolRunner::execute($fn['name'], $fn['arguments'] ?? '{}');
                $toolResults[] = [
                    'name' => $fn['name'],
                    'arguments' => $fn['arguments'],
                    'result' => $result,
                ];

                // Feed tool result back to LLM for final answer
                $messages[] = $choice['message'];
                $messages[] = [
                    'role' => 'tool',
                    'content' => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
                    'tool_call_id' => $tc['id'],
                ];

                $finalResponse = \App\Libraries\DeepSeekClient::chatCompletions([
                    'model' => getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat',
                    'messages' => $messages,
                    'max_tokens' => 2000,
                    'temperature' => 0.7,
                ]);
                $reply = $finalResponse['choices'][0]['message']['content'] ?? $reply;
            }

            return $this->response->setJSON([
                'reply' => $reply ?: 'Lo siento, no pude procesar tu solicitud.',
                'debug' => $debug ? $toolResults : null,
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'AiAgentController::callPhpDeepSeek: ' . $e->getMessage());
            return $this->response->setStatusCode(502)->setJSON([
                'status' => 'error',
                'message' => 'Error al contactar el servicio de IA.',
                'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * Extract system prompt from Python prompts.py file.
     */
    private function extractSystemPrompt(): string
    {
        $path = ROOTPATH . 'agente/agente/prompts.py';
        if (!file_exists($path)) return '';

        $content = file_get_contents($path);
        // Extract the SYSTEM_PROMPT string between triple quotes
        if (preg_match('/SYSTEM_PROMPT\s*=\s*"""(.*?)"""/s', $content, $m)) {
            return trim($m[1]);
        }
        return '';
    }
    }

    /**
     * @param mixed $history
     *
     * @return list<array{role: string, content: string}>
     */
    private function normalizeHistory($history): array
    {
        if (! is_array($history)) {
            return [];
        }

        $out = [];
        foreach ($history as $item) {
            if (! is_array($item)) {
                continue;
            }
            $role = isset($item['role']) ? strtolower(trim((string) $item['role'])) : '';
            $content = isset($item['content']) ? (string) $item['content'] : '';

            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }

            $out[] = ['role' => $role, 'content' => $content];

            if (count($out) >= self::MAX_HISTORY_MESSAGES) {
                break;
            }
        }

        return $out;
    }
}
