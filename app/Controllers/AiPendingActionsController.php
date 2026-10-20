<?php

namespace App\Controllers;

use App\Libraries\AiPendingActionService;

/**
 * Advisor review of actions proposed by the AI agent (phone, visit).
 */
class AiPendingActionsController extends BaseController
{
    public function index(int $conversationId)
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => (new AiPendingActionService())->pendingForConversation($conversationId),
        ]);
    }

    public function approve(int $id)
    {
        $result = (new AiPendingActionService())->approve($id, (int) session()->get('id'));

        return $this->response->setStatusCode($result['ok'] ? 200 : 422)->setJSON([
            'status'  => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ]);
    }

    public function reject(int $id)
    {
        $result = (new AiPendingActionService())->reject($id, (int) session()->get('id'));

        return $this->response->setStatusCode($result['ok'] ? 200 : 422)->setJSON([
            'status'  => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ]);
    }
}
