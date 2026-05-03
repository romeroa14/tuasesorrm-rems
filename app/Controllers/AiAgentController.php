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

        $outgoing = [
            'message' => $message,
            'history' => $history,
            'debug' => $debug,
        ];

        try {
            $client = Services::curlrequest(['timeout' => 120, 'http_errors' => false]);
            $r = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body' => json_encode($outgoing, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AiAgentController::api_chat upstream error: ' . $e->getMessage());

            return $this->response->setStatusCode(502)->setJSON([
                'status' => 'error',
                'message' => 'No se pudo contactar al servicio de agente.',
                'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ]);
        }

        $code = $r->getStatusCode();
        $body = (string) $r->getBody();
        $json = json_decode($body, true);

        if (is_array($json)) {
            return $this->response->setStatusCode($code)->setJSON($json);
        }

        return $this->response->setStatusCode($code >= 400 ? $code : 502)->setJSON([
            'status' => 'error',
            'message' => 'Respuesta no JSON del agente.',
            'raw' => ENVIRONMENT === 'development' ? mb_substr($body, 0, 2000) : null,
        ]);
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
