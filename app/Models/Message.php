<?php
namespace App\Models;
use CodeIgniter\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'conversation_id', 'direction', 'sender_type', 'sender_id',
        'content', 'content_type', 'media_url', 'external_message_id', 'read_at'
    ];
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    public function getByConversation($conversationId, $limit = 50, $offset = 0)
    {
        return $this->select('messages.*, users.full_name as sender_name')
            ->join('users', 'users.id = messages.sender_id', 'left')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->findAll($limit, $offset);
    }

    public function getLastMessage($conversationId)
    {
        return $this->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function markAsRead($conversationId)
    {
        return $this->where('conversation_id', $conversationId)
            ->where('read_at IS NULL')
            ->where('direction', 'inbound')
            ->set('read_at', date('Y-m-d H:i:s'))
            ->update();
    }
}
