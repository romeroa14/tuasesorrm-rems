<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FinanceAuthorization;
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
        if (mb_strlen($message) > 4000) {
            $message = mb_substr($message, 0, 4000);
        }

        $userId = $this->currentUserId();
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Sesión no válida.',
            ]);
        }

        $this->ensureAgentThreads();
        $requestedThread = isset($payload['thread_id']) ? (int) $payload['thread_id'] : 0;
        $thread = $requestedThread > 0 ? $this->ownedThread($requestedThread, $userId) : null;
        if ($requestedThread > 0 && $thread === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Esa conversación no existe.',
            ]);
        }

        $createdThread = false;
        if ($thread === null) {
            $requestedThread = $this->insertThread($userId, $this->threadTitle($message));
            $createdThread = true;
        }

        $history = $this->threadHistory($requestedThread);
        $debug = ! empty($payload['debug']);

        $url = getenv('AI_AGENT_URL');
        $url = ($url !== false && trim((string) $url) !== '')
            ? trim((string) $url)
            : self::DEFAULT_AGENT_CHAT_URL;

        $financeAuth = new FinanceAuthorization();
        $outgoing = [
            'message' => $message,
            'history' => $history,
            'debug' => $debug,
            'finance' => [
                'access' => $financeAuth->canAccess(),
                'dashboard' => $financeAuth->canViewDashboard(),
                'reports' => $financeAuth->canViewReports(),
                'income' => $financeAuth->canViewIncome(),
                'expense' => $financeAuth->canViewExpense(),
            ],
        ];

        try {
            $client = Services::curlrequest(['timeout' => 120, 'http_errors' => false]);
            $r = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'Authorization' => 'Bearer ' . (string) getenv('AGENT_API_TOKEN'),
                ],
                'body' => json_encode($outgoing, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            if ($createdThread) {
                $this->deleteThreadRows($requestedThread, $userId);
            }
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

        if (is_array($json) && $code < 400 && isset($json['reply'])) {
            $this->insertMessage($requestedThread, 'user', $message);
            $this->insertMessage($requestedThread, 'assistant', (string) $json['reply']);
            $json['thread_id'] = $requestedThread;
            $json['title'] = $this->touchThread($requestedThread, $userId);

            return $this->response->setStatusCode($code)->setJSON($json);
        }

        if ($createdThread) {
            $this->deleteThreadRows($requestedThread, $userId);
        }

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

    public function threads()
    {
        $userId = $this->currentUserId();
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error']);
        }
        $this->ensureAgentThreads();
        $rows = db_connect()->table('ai_agent_threads')
            ->select('id, title, updated_at')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->limit(80)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['threads' => $rows]);
    }

    public function thread($id)
    {
        $this->ensureAgentThreads();
        $userId = $this->currentUserId();
        $thread = $this->ownedThread((int) $id, $userId);
        if ($thread === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Esa conversación no existe.',
            ]);
        }
        $messages = db_connect()->table('ai_agent_messages')
            ->select('role, content, created_at')
            ->where('thread_id', (int) $thread['id'])
            ->orderBy('id', 'ASC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'thread' => [
                'id' => (int) $thread['id'],
                'title' => $thread['title'],
                'updated_at' => $thread['updated_at'],
            ],
            'messages' => $messages,
        ]);
    }

    public function delete_thread($id)
    {
        $this->ensureAgentThreads();
        $userId = $this->currentUserId();
        if ($this->ownedThread((int) $id, $userId) === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Esa conversación no existe.',
            ]);
        }
        $this->deleteThreadRows((int) $id, $userId);

        return $this->response->setJSON(['status' => 'ok']);
    }

    private function currentUserId(): int
    {
        $id = session()->get('id');

        return is_numeric($id) ? (int) $id : 0;
    }

    private function ensureAgentThreads(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $db = db_connect();
        $db->query("CREATE TABLE IF NOT EXISTS ai_agent_threads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(160) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY ai_agent_threads_user (user_id, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS ai_agent_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id INT UNSIGNED NOT NULL,
            role VARCHAR(16) NOT NULL,
            content MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY ai_agent_messages_thread (thread_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ready = true;
    }

    private function ownedThread(int $threadId, int $userId): ?array
    {
        if ($threadId < 1 || $userId < 1) {
            return null;
        }
        $row = db_connect()->table('ai_agent_threads')
            ->where('id', $threadId)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function insertThread(int $userId, string $title): int
    {
        $now = date('Y-m-d H:i:s');
        db_connect()->table('ai_agent_threads')->insert([
            'user_id' => $userId,
            'title' => $title,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) db_connect()->insertID();
    }

    private function insertMessage(int $threadId, string $role, string $content): void
    {
        db_connect()->table('ai_agent_messages')->insert([
            'thread_id' => $threadId,
            'role' => $role,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function touchThread(int $threadId, int $userId): string
    {
        $thread = $this->ownedThread($threadId, $userId);
        $title = $thread['title'] ?? 'Conversación';
        db_connect()->table('ai_agent_threads')->where('id', $threadId)->update([
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $title;
    }

    private function threadHistory(int $threadId): array
    {
        $rows = db_connect()->table('ai_agent_messages')
            ->select('role, content')
            ->where('thread_id', $threadId)
            ->orderBy('id', 'DESC')
            ->limit(self::MAX_HISTORY_MESSAGES)
            ->get()
            ->getResultArray();

        return $this->normalizeHistory(array_reverse($rows));
    }

    private function threadTitle(string $message): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $message) ?? '');
        if ($title === '') {
            return 'Nueva conversación';
        }

        return mb_strlen($title) > 80 ? mb_substr($title, 0, 77) . '...' : $title;
    }

    private function deleteThreadRows(int $threadId, int $userId): void
    {
        $db = db_connect();
        $db->table('ai_agent_messages')->where('thread_id', $threadId)->delete();
        $db->table('ai_agent_threads')->where('id', $threadId)->where('user_id', $userId)->delete();
    }
}
