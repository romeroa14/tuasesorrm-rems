<?php

namespace App\Controllers;

use App\Libraries\AtcActor;
use App\Models\User;
use CodeIgniter\Controller;
use Firebase\JWT\JWT;

/**
 * API del teléfono de ATC. Lee la misma base del CRM y no incluye finanzas.
 */
class AtcApiController extends Controller
{
    private const TOKEN_TTL = 604800;

    public function login()
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $email = trim((string) ($payload['email'] ?? $this->request->getPost('email') ?? ''));
        $password = (string) ($payload['password'] ?? $this->request->getPost('password') ?? '');

        $secret = $this->jwtSecret();
        if ($secret === null) {
            return $this->fail('La API móvil no está configurada.', 503);
        }

        $user = (new User())->findByEmail($email);
        if (! is_array($user) || ! password_verify($password, (string) ($user['password'] ?? ''))) {
            return $this->fail('Correo o contraseña incorrectos.', 401);
        }

        $token = JWT::encode([
            'iat' => time(),
            'exp' => time() + self::TOKEN_TTL,
            'sub' => (int) $user['id'],
        ], $secret, 'HS256');

        return $this->ok([
            'token' => $token,
            'user' => $this->publicUser($user),
        ]);
    }

    public function me()
    {
        $user = $this->actor();
        if ($user === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        return $this->ok(['user' => $this->publicUser($user)]);
    }

    public function dashboard()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        $db = \Config\Database::connect();

        $leadsTotal = (int) $db->query('SELECT COUNT(*) AS n FROM leads')->getRow()->n;
        $threads = (int) $db->query("SELECT COUNT(*) AS n FROM conversations WHERE channel = 'instagram'")->getRow()->n;
        $caracasMidnightUtc = "DATE_ADD(DATE(DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 HOUR)), INTERVAL 4 HOUR)";
        $leadsToday = (int) $db->query("SELECT COUNT(*) AS n FROM leads WHERE created_at >= $caracasMidnightUtc")->getRow()->n;
        $threadsToday = (int) $db->query("SELECT COUNT(*) AS n FROM conversations WHERE channel = 'instagram' AND created_at >= $caracasMidnightUtc")->getRow()->n;

        $sinAsignar = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL
              AND (l.pipeline_status_id IS NULL OR l.pipeline_status_id = 0)
        ")->getRow()->n;

        $sugeridos = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL
              AND l.pipeline_status_id IS NOT NULL
              AND l.pipeline_status_id > 0
        ")->getRow()->n;

        $conAgente = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN assignedclients ac ON ac.lead_id = l.id
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
        ")->getRow()->n;

        $potenciales = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
        ")->getRow()->n;
        $potencialesHoy = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            WHERE l.created_at >= $caracasMidnightUtc
        ")->getRow()->n;
        $sinAtender = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL
        ")->getRow()->n;

        $temperature = $db->query("
            SELECT COALESCE(NULLIF(intention_label, ''), 'sin temperatura') AS label, COUNT(*) AS total
            FROM leads
            GROUP BY COALESCE(NULLIF(intention_label, ''), 'sin temperatura')
            ORDER BY total DESC
        ")->getResultArray();

        $pipeline = $db->query("
            SELECT ts.id, ts.emoji, ts.name, ts.is_active,
                   COUNT(DISTINCT ac.lead_id) AS assigned
            FROM trackingstatus ts
            LEFT JOIN assignedclients ac ON ac.trackingstatus_id = ts.id
            GROUP BY ts.id, ts.emoji, ts.name, ts.is_active
            ORDER BY ts.id
        ")->getResultArray();

        $accounts = $db->query("
            SELECT
                recipient_ig_id,
                COALESCE(NULLIF(recipient_ig_username, ''), 'sin cuenta') AS username,
                COUNT(*) AS threads,
                COUNT(DISTINCT lead_id) AS leads,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_threads,
                SUM(CASE WHEN unread_count > 0 THEN 1 ELSE 0 END) AS with_unread
            FROM conversations
            WHERE channel = 'instagram'
            GROUP BY recipient_ig_id, recipient_ig_username
            ORDER BY threads DESC
        ")->getResultArray();

        $cross = $db->query("
            SELECT
                COALESCE(NULLIF(c.recipient_ig_username, ''), 'sin cuenta') AS account,
                COALESCE(NULLIF(l.intention_label, ''), 'sin temperatura') AS temperature,
                COUNT(DISTINCT l.id) AS total
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            GROUP BY account, temperature
            ORDER BY account, total DESC
        ")->getResultArray();

        $propertyStatus = $db->query("
            SELECT s.id, s.name, COUNT(p.id_properties) AS total
            FROM status s
            LEFT JOIN properties p ON p.status = s.id
            GROUP BY s.id, s.name
            ORDER BY s.id
        ")->getResultArray();

        $propertyBusiness = $db->query("
            SELECT b.name, COUNT(*) AS total
            FROM properties p
            INNER JOIN businessmodel b ON b.id = p.business_model
            WHERE p.status = 1
            GROUP BY b.id, b.name
            ORDER BY total DESC
        ")->getResultArray();

        $propertyHousing = $db->query("
            SELECT h.name, COUNT(*) AS total
            FROM properties p
            INNER JOIN housingtype h ON h.id = p.housing_type
            WHERE p.status = 1
            GROUP BY h.id, h.name
            ORDER BY total DESC
            LIMIT 8
        ")->getResultArray();

        return $this->ok([
            'generated_at' => date('c'),
            'leads' => [
                'total' => $leadsTotal,
                'today' => $leadsToday,
                'instagram_threads' => $threads,
                'instagram_threads_today' => $threadsToday,
                'sin_asignar' => $sinAsignar,
                'clasificados_sin_agente' => $sugeridos,
                'con_agente' => $conAgente,
                'potenciales' => $potenciales,
                'potenciales_hoy' => $potencialesHoy,
                'sin_atender' => $sinAtender,
                'atendidos' => $conAgente,
                'conversaciones' => $threads,
                'conversaciones_hoy' => $threadsToday,
            ],
            'temperature' => $this->ints($temperature, ['total']),
            'pipeline' => $this->ints($pipeline, ['id', 'is_active', 'assigned']),
            'accounts' => $this->ints($accounts, ['threads', 'leads', 'open_threads', 'with_unread']),
            'cross_account_temperature' => $this->ints($cross, ['total']),
            'properties' => [
                'by_status' => $this->ints($propertyStatus, ['id', 'total']),
                'approved_by_business' => $this->ints($propertyBusiness, ['total']),
                'approved_by_housing' => $this->ints($propertyHousing, ['total']),
            ],
        ]);
    }

    public function pipeline()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        $db = \Config\Database::connect();
        $account = trim((string) $this->request->getGet('account'));
        $accountSql = '';
        $accountBind = [];
        if ($account !== '') {
            $accountSql = ' AND c.recipient_ig_username = ?';
            $accountBind[] = $account;
        }
        $columns = [
            'unassigned' => [
                'id' => 'unassigned',
                'emoji' => '',
                'name' => 'Sin asignar',
                'count' => 0,
                'leads' => [],
            ],
        ];

        foreach ($db->query('SELECT id, emoji, name FROM trackingstatus WHERE is_active = 1 ORDER BY id')->getResultArray() as $status) {
            $columns[(string) $status['id']] = [
                'id' => (int) $status['id'],
                'emoji' => '',
                'name' => $status['name'],
                'count' => 0,
                'leads' => [],
            ];
        }

        if ($account === '') {
            $assignedCounts = $db->query('
                SELECT ac.trackingstatus_id AS id, COUNT(DISTINCT ac.lead_id) AS n
                FROM assignedclients ac
                INNER JOIN leads l ON l.id = ac.lead_id
                GROUP BY ac.trackingstatus_id
            ')->getResultArray();
        } else {
            $assignedCounts = $db->query("
                SELECT ac.trackingstatus_id AS id, COUNT(DISTINCT ac.lead_id) AS n
                FROM assignedclients ac
                INNER JOIN leads l ON l.id = ac.lead_id
                INNER JOIN conversations c ON c.lead_id = ac.lead_id AND c.channel = 'instagram'
                WHERE 1 = 1 {$accountSql}
                GROUP BY ac.trackingstatus_id
            ", $accountBind)->getResultArray();
        }
        foreach ($assignedCounts as $row) {
            $key = (string) $row['id'];
            if (isset($columns[$key])) {
                $columns[$key]['count'] = (int) $row['n'];
            }
        }

        $suggestedCounts = $db->query("
            SELECT l.pipeline_status_id AS id, COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL AND l.pipeline_status_id IS NOT NULL AND l.pipeline_status_id > 0 {$accountSql}
            GROUP BY l.pipeline_status_id
        ", $accountBind)->getResultArray();
        foreach ($suggestedCounts as $row) {
            $key = (string) $row['id'];
            if (isset($columns[$key])) {
                $columns[$key]['count'] += (int) $row['n'];
            }
        }

        $columns['unassigned']['count'] = (int) $db->query("
            SELECT COUNT(DISTINCT l.id) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            WHERE ac.id IS NULL AND (l.pipeline_status_id IS NULL OR l.pipeline_status_id = 0) {$accountSql}
        ", $accountBind)->getRow()->n;

        $rows = $db->query("
            SELECT c.lead_id, c.last_message_at, c.recipient_ig_username, c.unread_count, c.ad_title, c.ad_id, c.referral_source,
                   l.name, l.phone, l.profile_pic, l.intention_label, l.instagram_username, l.pipeline_status_id, l.created_at,
                   ac.trackingstatus_id, ac.assigned_id, u.full_name AS agent_name,
                   (SELECT 1 FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'ai' LIMIT 1) AS has_ai_reply
            FROM conversations c
            INNER JOIN leads l ON l.id = c.lead_id
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            LEFT JOIN users u ON u.id = ac.assigned_id
            WHERE c.channel = 'instagram' {$accountSql}
            ORDER BY c.last_message_at DESC
            LIMIT 120
        ", $accountBind)->getResultArray();

        $seen = [];
        foreach ($rows as $row) {
            $leadId = (int) $row['lead_id'];
            if (isset($seen[$leadId])) {
                continue;
            }
            $seen[$leadId] = true;
            $key = 'unassigned';
            if ($row['trackingstatus_id'] !== null) {
                $key = (string) $row['trackingstatus_id'];
            } elseif ((int) $row['pipeline_status_id'] > 0) {
                $key = (string) $row['pipeline_status_id'];
            }
            if (! isset($columns[$key]) || count($columns[$key]['leads']) >= 12) {
                continue;
            }
            $columns[$key]['leads'][] = $this->leadCard($row);
        }

        $accounts = $db->query("
            SELECT COALESCE(NULLIF(recipient_ig_username, ''), 'sin cuenta') AS username, COUNT(DISTINCT lead_id) AS leads
            FROM conversations
            WHERE channel = 'instagram'
            GROUP BY username
            ORDER BY leads DESC
        ")->getResultArray();

        return $this->ok([
            'columns' => array_values($columns),
            'accounts' => $this->ints($accounts, ['leads']),
        ]);
    }

    public function leads()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $q = trim((string) $this->request->getGet('q'));
        $account = trim((string) $this->request->getGet('account'));
        $temperature = trim((string) $this->request->getGet('temperature'));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $where = ["c.channel = 'instagram'", 'c.id = (SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = \'instagram\')'];
        $bind = [];
        if ($q !== '') {
            $where[] = '(l.name LIKE ? OR l.instagram_username LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        if ($account !== '') {
            $where[] = 'c.recipient_ig_username = ?';
            $bind[] = $account;
        }
        if ($temperature !== '') {
            $where[] = 'l.intention_label = ?';
            $bind[] = $temperature;
        }

        $sqlWhere = implode(' AND ', $where);
        $db = \Config\Database::connect();
        $total = (int) $db->query("
            SELECT COUNT(*) AS n
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id
            WHERE {$sqlWhere}
        ", $bind)->getRow()->n;

        $rows = $db->query("
            SELECT l.id AS lead_id, l.name, l.phone, l.profile_pic, l.intention_label, l.instagram_username, l.created_at,
                   c.last_message_at, c.recipient_ig_username, c.unread_count, c.ad_title, c.ad_id, c.referral_source, l.pipeline_status_id,
                   ac.trackingstatus_id, ac.assigned_id, u.full_name AS agent_name,
                   (SELECT 1 FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'ai' LIMIT 1) AS has_ai_reply
            FROM leads l
            INNER JOIN conversations c ON c.lead_id = l.id
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            LEFT JOIN users u ON u.id = ac.assigned_id
            WHERE {$sqlWhere}
            ORDER BY c.last_message_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $bind)->getResultArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->leadCard($row);
        }

        return $this->ok([
            'page' => $page,
            'total' => $total,
            'items' => $items,
        ]);
    }

    public function lead($id)
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        $db = \Config\Database::connect();
        $row = $db->query("
            SELECT l.id, l.name, l.phone, l.email, l.instagram_username, l.profile_pic,
                   l.intention_label, l.intention_score, l.interest_type, l.budget_detected,
                   l.zone_interest, l.created_at, l.visit_at, l.property_id, l.pipeline_status_id,
                   c.id AS conversation_id, c.recipient_ig_username, c.last_message_at, c.unread_count,
                   c.ad_id, c.ad_title, c.ad_photo_url, c.referral_source,
                   ts.name AS status_name, ts.emoji AS status_emoji,
                   u.full_name AS agent_name, ac.assigned_id,
                   pr.address AS property_address, ci.name AS property_city
            FROM leads l
            LEFT JOIN conversations c ON c.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
            )
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            LEFT JOIN users u ON u.id = ac.assigned_id
            LEFT JOIN trackingstatus ts ON ts.id = COALESCE(ac.trackingstatus_id, l.pipeline_status_id)
            LEFT JOIN properties pr ON pr.id_properties = l.property_id
            LEFT JOIN city ci ON ci.id = pr.city
            WHERE l.id = ?
            LIMIT 1
        ", [(int) $id])->getRowArray();

        if (! $row) {
            return $this->fail('No encontramos ese lead.', 404);
        }

        $messages = [];
        if (! empty($row['conversation_id'])) {
            $messages = $db->query("
                SELECT direction, content, created_at
                FROM messages
                WHERE conversation_id = ?
                ORDER BY id DESC
                LIMIT 40
            ", [(int) $row['conversation_id']])->getResultArray();
            $messages = array_reverse($messages);
        }

        $hasAi = false;
        if (! empty($row['conversation_id'])) {
            $aiRow = $db->query(
                "SELECT id FROM messages WHERE conversation_id = ? AND sender_type = 'ai' LIMIT 1",
                [(int) $row['conversation_id']]
            )->getRowArray();
            $hasAi = $aiRow !== null;
        }
        $attendance = \App\Libraries\AtcAgentHandoff::present(
            isset($row['agent_name']) ? (string) $row['agent_name'] : null,
            $row['assigned_id'] ?? null,
            $row['phone'] ?? null,
            $hasAi
        );

        return $this->ok([
            'lead' => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'instagram_username' => $row['instagram_username'],
                'photo' => $this->photoUrl($row['profile_pic'] ?? null),
                'temperature' => $row['intention_label'],
                'score' => $row['intention_score'] !== null ? (int) $row['intention_score'] : null,
                'interest_type' => $row['interest_type'],
                'budget_detected' => $row['budget_detected'],
                'zone_interest' => $row['zone_interest'],
                'created_at' => $row['created_at'],
                'visit_at' => $row['visit_at'] ?? null,
                'property_id' => (int) ($row['property_id'] ?? 0),
                'property_code' => ((int) ($row['property_id'] ?? 0)) > 0 ? 'RM' . str_pad((string) $row['property_id'], 4, '0', STR_PAD_LEFT) : '',
                'property_city' => $row['property_city'] ?? '',
                'property_address' => $row['property_address'] ?? '',
                'account' => $row['recipient_ig_username'],
                'last_message_at' => $row['last_message_at'],
                'unread' => (int) ($row['unread_count'] ?? 0),
                'status_name' => $row['status_name'],
                'status_emoji' => $row['status_emoji'],
                'ad_id' => $row['ad_id'] ?? null,
                'ad_title' => $row['ad_title'] ?? null,
                'ad_photo' => $this->photoUrl($row['ad_photo_url'] ?? null),
                'referral_source' => $row['referral_source'] ?? null,
                'agent_name' => $attendance['agent_name'],
                'phone_ready' => $attendance['phone_ready'],
                'assigned_id' => $attendance['assigned_id'],
            ],
            'messages' => $messages,
        ]);
    }


    public function linkProperty($id)
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $propertyId = (int) ($payload['property_id'] ?? 0);
        $db = \Config\Database::connect();
        $lead = $db->query('SELECT id FROM leads WHERE id = ?', [(int) $id])->getRowArray();
        if (! $lead) {
            return $this->fail('No encontramos ese lead.', 404);
        }
        if ($propertyId > 0) {
            $property = $db->query('SELECT id_properties FROM properties WHERE id_properties = ?', [$propertyId])->getRowArray();
            if (! $property) {
                return $this->fail('No encontramos ese inmueble.', 404);
            }
        }
        $db->query(
            'UPDATE leads SET property_id = ?, updated_at = ? WHERE id = ?',
            [$propertyId > 0 ? $propertyId : null, date('Y-m-d H:i:s'), (int) $id]
        );

        return $this->ok(['property_id' => $propertyId]);
    }

    public function properties()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $status = (int) $this->request->getGet('status');
        $limit = 25;
        $offset = ($page - 1) * $limit;
        $where = '1 = 1';
        $bind = [];
        if ($status > 0) {
            $where = 'p.status = ?';
            $bind[] = $status;
        }
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if ($q !== '') {
            $propertyId = 0;
            if (preg_match('/^RM\s*(\d+)$/i', $q, $match)) {
                $propertyId = (int) $match[1];
            } elseif (ctype_digit($q)) {
                $propertyId = (int) $q;
            }
            $where .= ' AND (p.address LIKE ? OR c.name LIKE ? OR p.id_properties = ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
            $bind[] = $propertyId;
            $limit = 12;
        }

        $db = \Config\Database::connect();
        $total = (int) $db->query("SELECT COUNT(*) AS n FROM properties p LEFT JOIN city c ON c.id = p.city WHERE {$where}", $bind)->getRow()->n;
        $rows = $db->query("
            SELECT p.id_properties, p.address, p.price, p.bedrooms, p.bathrooms, p.meters_construction,
                   h.name AS housing, b.name AS business, s.name AS status_name, s.id AS status_id,
                   c.name AS city
            FROM properties p
            LEFT JOIN housingtype h ON h.id = p.housing_type
            LEFT JOIN businessmodel b ON b.id = p.business_model
            LEFT JOIN status s ON s.id = p.status
            LEFT JOIN city c ON c.id = p.city
            WHERE {$where}
            ORDER BY p.id_properties DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $bind)->getResultArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id_properties'],
                'code' => 'RM' . str_pad((string) $row['id_properties'], 4, '0', STR_PAD_LEFT),
                'address' => $row['address'],
                'price' => $row['price'],
                'bedrooms' => $row['bedrooms'] !== null ? (int) $row['bedrooms'] : null,
                'bathrooms' => $row['bathrooms'] !== null ? (int) $row['bathrooms'] : null,
                'meters' => $row['meters_construction'],
                'housing' => $row['housing'],
                'business' => $row['business'],
                'status' => $row['status_name'],
                'status_id' => $row['status_id'] !== null ? (int) $row['status_id'] : null,
                'city' => $row['city'],
            ];
        }

        return $this->ok(['page' => $page, 'total' => $total, 'items' => $items]);
    }


    public function agents()
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if (! $this->isAtcSupervisor($actor)) {
            return $this->fail('Solo Bárbara asigna la atención a otro agente.', 403);
        }
        $rows = \Config\Database::connect()->query(
            "SELECT id, full_name FROM users WHERE id_fk_rol = 7 AND status = 'activo' ORDER BY full_name"
        )->getResultArray();

        return $this->ok(['items' => $this->ints($rows, ['id'])]);
    }


    public function claim($id)
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if ((int) ($actor['id_fk_rol'] ?? 0) !== 7) {
            return $this->fail('Solo un agente de atención puede responder este lead.', 403);
        }

        $db = \Config\Database::connect();
        $lead = $db->query('SELECT id FROM leads WHERE id = ?', [(int) $id])->getRowArray();
        if (! $lead) {
            return $this->fail('No encontramos ese lead.', 404);
        }

        $actorId = (int) $actor['id'];
        $existing = $db->query('SELECT id, assigned_id FROM assignedclients WHERE lead_id = ?', [(int) $id])->getRowArray();
        if ($existing && (int) ($existing['assigned_id'] ?? 0) > 0 && (int) $existing['assigned_id'] !== $actorId) {
            $other = $db->query('SELECT full_name FROM users WHERE id = ?', [(int) $existing['assigned_id']])->getRowArray();
            $name = trim((string) ($other['full_name'] ?? 'otro agente'));

            return $this->fail('Este lead ya lo atiende ' . $name . '.', 409);
        }

        if ($existing) {
            $db->table('assignedclients')->update([
                'delegate_id' => $actorId,
                'assigned_id' => $actorId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $existing['id']]);
        } else {
            $db->table('assignedclients')->insert([
                'delegate_id' => $actorId,
                'assigned_id' => $actorId,
                'lead_id' => (int) $id,
                'trackingstatus_id' => 1,
                'assignment_at' => date('Y-m-d'),
                'first_contact_at' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->query('UPDATE conversations SET assigned_to = ?, status = ? WHERE lead_id = ?', [$actorId, 'assigned', (int) $id]);
        $db->query('UPDATE conversations SET ai_auto_reply = 0 WHERE lead_id = ? AND channel = ?', [(int) $id, 'instagram']);

        return $this->ok([
            'assigned_id' => $actorId,
            'agent_name' => $actor['full_name'] ?? '',
        ]);
    }

    public function agenda()
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $db = \Config\Database::connect();
        $ids = $this->scheduleStatusIds($db);
        $rows = $db->query(
            "SELECT l.id, l.name, l.visit_at, l.property_id,
                    c.recipient_ig_username,
                    u.full_name AS agent_name,
                    p.address AS property_address,
                    ci.name AS property_city
             FROM leads l
             LEFT JOIN conversations c ON c.id = (
                 SELECT MAX(id) FROM conversations WHERE lead_id = l.id AND channel = 'instagram'
             )
             LEFT JOIN assignedclients ac ON ac.lead_id = l.id
             LEFT JOIN users u ON u.id = ac.assigned_id
             LEFT JOIN properties p ON p.id_properties = l.property_id
             LEFT JOIN city ci ON ci.id = p.city
             WHERE l.visit_at IS NOT NULL
                OR l.pipeline_status_id IN (?, ?)
                OR ac.trackingstatus_id IN (?, ?)
             ORDER BY (l.visit_at IS NULL) DESC, l.visit_at ASC, l.id DESC",
            [$ids['want'], $ids['set'], $ids['want'], $ids['set']]
        )->getResultArray();
        $items = [];
        foreach ($rows as $row) {
            $propertyId = (int) ($row['property_id'] ?? 0);
            $city = trim((string) ($row['property_city'] ?? ''));
            $address = trim((string) ($row['property_address'] ?? ''));
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'] ?? '',
                'account' => $row['recipient_ig_username'] ?? '',
                'agent_name' => $row['agent_name'] ?? '',
                'visit_at' => $row['visit_at'],
                'property_code' => $propertyId > 0 ? 'RM' . str_pad((string) $propertyId, 4, '0', STR_PAD_LEFT) : '',
                'property_city' => $city,
                'property_address' => $address,
            ];
        }

        return $this->ok(['items' => $items]);
    }

    public function schedule($id)
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $db = \Config\Database::connect();
        $lead = $db->query('SELECT id FROM leads WHERE id = ?', [(int) $id])->getRowArray();
        if (! $lead) {
            return $this->fail('No encontramos ese lead.', 404);
        }
        $ids = $this->scheduleStatusIds($db);
        $raw = trim((string) ($payload['visit_at'] ?? ''));
        $stored = null;
        $statusId = $ids['want'];
        if ($raw !== '') {
            $when = \DateTime::createFromFormat('Y-m-d H:i', $raw);
            if (! $when) {
                return $this->fail('La hora de la visita no es válida.', 422);
            }
            $when->modify('+4 hours');
            $stored = $when->format('Y-m-d H:i:s');
            $statusId = $ids['set'];
        }
        $now = date('Y-m-d H:i:s');
        $db->query(
            'UPDATE leads SET pipeline_status_id = ?, visit_at = ?, updated_at = ? WHERE id = ?',
            [$statusId, $stored, $now, (int) $id]
        );
        $db->table('assignedclients')->where('lead_id', (int) $id)->update([
            'trackingstatus_id' => $statusId,
            'updated_at' => $now,
        ]);

        return $this->ok(['visit_at' => $stored, 'status_id' => $statusId]);
    }

    private function scheduleStatusIds($db): array
    {
        $want = 11;
        $set = 16;
        $rows = $db->query('SELECT id, name FROM trackingstatus WHERE is_active = 1')->getResultArray();
        foreach ($rows as $row) {
            $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
            if (str_contains($name, 'quiere') && str_contains($name, 'agend')) {
                $want = (int) $row['id'];
            } elseif ($name === 'agendado') {
                $set = (int) $row['id'];
            }
        }

        return ['want' => $want, 'set' => $set];
    }

    public function assign($id)
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if (! $this->isAtcSupervisor($actor)) {
            return $this->fail('Solo Bárbara asigna la atención a otro agente.', 403);
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $assignedId = (int) ($payload['user_id'] ?? 0);
        $db = \Config\Database::connect();
        $agent = $db->query("SELECT id, full_name FROM users WHERE id = ? AND id_fk_rol = 7 AND status = 'activo'", [$assignedId])->getRowArray();
        if (! $agent) {
            return $this->fail('Elige un agente de atención al cliente.', 422);
        }
        $lead = $db->query('SELECT id FROM leads WHERE id = ?', [(int) $id])->getRowArray();
        if (! $lead) {
            return $this->fail('No encontramos ese lead.', 404);
        }
        $existing = $db->query('SELECT id, trackingstatus_id FROM assignedclients WHERE lead_id = ?', [(int) $id])->getRowArray();
        if ($existing) {
            $db->table('assignedclients')->update([
                'delegate_id' => (int) $actor['id'],
                'assigned_id' => $assignedId,
            ], ['id' => (int) $existing['id']]);
        } else {
            $db->table('assignedclients')->insert([
                'delegate_id' => (int) $actor['id'],
                'assigned_id' => $assignedId,
                'lead_id' => (int) $id,
                'trackingstatus_id' => 1,
                'assignment_at' => date('Y-m-d'),
                'first_contact_at' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->query('UPDATE conversations SET assigned_to = ?, status = ? WHERE lead_id = ?', [$assignedId, 'assigned', (int) $id]);

        return $this->ok(['assigned_id' => $assignedId, 'agent_name' => $agent['full_name']]);
    }

    public function exportLeads()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $account = trim((string) $this->request->getGet('account'));
        $sql = "
            SELECT l.name, l.instagram_username, l.phone, l.intention_label,
                   c.recipient_ig_username, c.ad_title, c.last_message_at,
                   ts.name AS status_name
            FROM leads l
            INNER JOIN conversations c ON c.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
            )
            LEFT JOIN assignedclients ac ON ac.lead_id = l.id
            LEFT JOIN trackingstatus ts ON ts.id = COALESCE(ac.trackingstatus_id, l.pipeline_status_id)
        ";
        $bind = [];
        if ($account !== '') {
            $sql .= ' WHERE c.recipient_ig_username = ?';
            $bind[] = $account;
        }
        $sql .= ' ORDER BY c.last_message_at DESC';
        $rows = \Config\Database::connect()->query($sql, $bind)->getResultArray();
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['nombre', 'instagram', 'cuenta', 'telefono', 'temperatura', 'estatus', 'anuncio', 'ultimo_mensaje'], ';');
        foreach ($rows as $row) {
            fputcsv($handle, [
                $this->csvCell($row['name'] ?? ''),
                $this->csvCell($row['instagram_username'] ?? ''),
                $this->csvCell($row['recipient_ig_username'] ?? ''),
                $this->csvCell($row['phone'] ?? ''),
                $this->csvCell($row['intention_label'] ?? ''),
                $this->csvCell($row['status_name'] ?? ''),
                $this->csvCell($row['ad_title'] ?? ''),
                $this->csvCell($row['last_message_at'] ?? ''),
            ], ';');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->withCors($this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="leads.csv"')
            ->setBody($csv));
    }

    private function csvCell($value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    public function stats()
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $db = \Config\Database::connect();
        $self = (int) ($actor['id_fk_rol'] ?? 0) === 7;
        $bind = $self ? [(int) $actor['id']] : [];
        $whereSelf = $self ? 'AND u.id = ?' : '';
        $sellers = $db->query("
            SELECT u.id AS agent_id, u.full_name AS agent_name,
                   COALESCE(NULLIF(cv.recipient_ig_username, ''), 'sin cuenta') AS account,
                   COUNT(DISTINCT l.id) AS leads
            FROM assignedclients ac
            INNER JOIN leads l ON l.id = ac.lead_id
            INNER JOIN users u ON u.id = ac.assigned_id AND u.id_fk_rol = 7
            LEFT JOIN conversations cv ON cv.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
            )
            WHERE 1 = 1 {$whereSelf}
            GROUP BY u.id, u.full_name, account
            ORDER BY account, u.full_name
        ", $bind)->getResultArray();
        $unassigned = [];
        if (! $self) {
            $unassigned = $db->query("
                SELECT COALESCE(NULLIF(cv.recipient_ig_username, ''), 'sin cuenta') AS account,
                       COUNT(DISTINCT l.id) AS leads
                FROM leads l
                INNER JOIN conversations cv ON cv.id = (
                    SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
                )
                LEFT JOIN assignedclients ac ON ac.lead_id = l.id
                WHERE ac.id IS NULL
                GROUP BY account
            ")->getResultArray();
        }
        $reportWhere = $self ? 'WHERE r.agent_id = ?' : '';
        $reports = $db->query("
            SELECT r.agent_id, u.full_name AS agent_name, r.leads_count, r.created_at,
                   r.interactuo, r.interactuo_nota, r.seguimiento, r.seguimiento_nota,
                   r.amable, r.amable_nota, r.flojo, r.flojo_nota, r.correccion
            FROM atc_service_reports r
            INNER JOIN (
                SELECT agent_id, MAX(id) AS id FROM atc_service_reports GROUP BY agent_id
            ) ultimo ON ultimo.id = r.id
            INNER JOIN users u ON u.id = r.agent_id
            {$reportWhere}
            ORDER BY u.full_name
        ", $bind)->getResultArray();

        return $this->ok([
            'can_report' => $this->canAssign($actor),
            'sellers' => $this->ints($sellers, ['agent_id', 'leads']),
            'unassigned' => $this->ints($unassigned, ['leads']),
            'reports' => $this->ints($reports, ['agent_id', 'leads_count']),
        ]);
    }

    public function serviceReport()
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if (! $this->canAssign($actor)) {
            return $this->fail('Solo un administrador genera el reporte de atención.', 403);
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $account = trim((string) ($payload['account'] ?? ''));
        $db = \Config\Database::connect();
        $sql = "
            SELECT DISTINCT u.id, u.full_name
            FROM assignedclients ac
            INNER JOIN leads l ON l.id = ac.lead_id
            INNER JOIN users u ON u.id = ac.assigned_id AND u.id_fk_rol = 7 AND u.status = 'activo'
            INNER JOIN conversations cv ON cv.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
            )
        ";
        $bind = [];
        if ($account !== '') {
            $sql .= ' WHERE cv.recipient_ig_username = ?';
            $bind[] = $account;
        }
        $sql .= ' ORDER BY u.full_name';
        $agents = $db->query($sql, $bind)->getResultArray();
        if ($agents === []) {
            return $this->fail('No hay un ATC con leads atendidos en esta cuenta.', 422);
        }
        $sections = [];
        foreach ($agents as $agent) {
            $agentId = (int) $agent['id'];
            try {
                $cases = $this->attentionCases($db, $agentId, $account);
                $review = $this->reviewAttention((string) $agent['full_name'], $cases);
            } catch (\Throwable $e) {
                log_message('error', 'Reporte de atención ATC ' . $agentId . ': ' . mb_substr($e->getMessage(), 0, 300));

                return $this->fail('No se pudo generar el reporte de atención.', 502);
            }
            $db->table('atc_service_reports')->insert([
                'agent_id' => $agentId,
                'generated_by' => (int) $actor['id'],
                'leads_count' => count($review['clientes']),
                'interactuo' => $review['interactuo'],
                'interactuo_nota' => $review['interactuo_nota'],
                'seguimiento' => $review['seguimiento'],
                'seguimiento_nota' => $review['seguimiento_nota'],
                'amable' => $review['amable'],
                'amable_nota' => $review['amable_nota'],
                'flojo' => $review['flojo'],
                'flojo_nota' => $review['flojo_nota'],
                'correccion' => $review['correccion'],
                'diagnostico' => json_encode([
                    'clientes' => array_map(static function (array $cliente): array {
                        return [
                            'id' => $cliente['id'],
                            'nombre' => $cliente['name'],
                            'descripcion' => $cliente['descripcion'],
                        ];
                    }, $review['clientes']),
                    'mejor' => [
                        'id' => $review['mejor']['id'],
                        'nombre' => $review['mejor']['name'],
                        'analisis' => $review['mejor']['analisis'],
                    ],
                    'peor' => [
                        'id' => $review['peor']['id'],
                        'nombre' => $review['peor']['name'],
                        'analisis' => $review['peor']['analisis'],
                        'misma' => $review['peor']['misma'],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $sections[] = [
                'agent_name' => (string) $agent['full_name'],
                'review' => $review,
            ];
        }
        $pdf = $this->attentionPdf($sections, $account);

        return $this->withCors($this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="REPORTE ATC.pdf"')
            ->setBody($pdf));
    }

    private function attentionCases($db, int $agentId, string $account): array
    {
        $sql = "
            SELECT l.id AS lead_id, l.name, l.phone, cv.recipient_ig_username AS account,
                   m.id AS message_id, m.direction, m.content, m.created_at
            FROM assignedclients ac
            INNER JOIN leads l ON l.id = ac.lead_id
            INNER JOIN conversations cv ON cv.id = (
                SELECT MAX(c2.id) FROM conversations c2 WHERE c2.lead_id = l.id AND c2.channel = 'instagram'
            )
            LEFT JOIN messages m ON m.conversation_id = cv.id
            WHERE ac.assigned_id = ?
        ";
        $bind = [$agentId];
        if ($account !== '') {
            $sql .= ' AND cv.recipient_ig_username = ?';
            $bind[] = $account;
        }
        $sql .= ' ORDER BY l.id ASC, m.id ASC';
        $rows = $db->query($sql, $bind)->getResultArray();
        $byLead = [];
        foreach ($rows as $row) {
            $id = (int) $row['lead_id'];
            if (! isset($byLead[$id])) {
                $name = trim((string) ($row['name'] ?? ''));
                $byLead[$id] = [
                    'id' => $id,
                    'name' => $name !== '' ? $name : 'Sin nombre',
                    'phone' => trim((string) ($row['phone'] ?? '')),
                    'account' => trim((string) ($row['account'] ?? '')),
                    'last_id' => 0,
                    'lines' => [],
                    'seen' => [],
                ];
            }
            $messageId = (int) ($row['message_id'] ?? 0);
            if ($messageId < 1 || isset($byLead[$id]['seen'][$messageId])) {
                continue;
            }
            $byLead[$id]['seen'][$messageId] = true;
            if ($messageId > $byLead[$id]['last_id']) {
                $byLead[$id]['last_id'] = $messageId;
            }
            $text = preg_replace('/\s+/u', ' ', (string) ($row['content'] ?? '')) ?? '';
            $text = trim($text);
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > 220) {
                $text = mb_substr($text, 0, 220) . '…';
            }
            $when = $this->caracasStamp((string) ($row['created_at'] ?? ''));
            $who = ($row['direction'] ?? '') === 'outbound' ? 'ATC' : 'Cliente';
            $byLead[$id]['lines'][] = trim($when . ' ' . $who . ': ' . $text);
        }
        $cases = array_values($byLead);
        usort($cases, static function (array $a, array $b): int {
            return $b['last_id'] <=> $a['last_id'];
        });
        $cases = array_reverse(array_slice($cases, 0, 30));
        foreach ($cases as &$case) {
            $case['text'] = implode("\n", array_slice($case['lines'], -8));
            unset($case['lines'], $case['seen'], $case['last_id']);
        }
        unset($case);

        return $cases;
    }

    private function reviewAttention(string $agentName, array $cases): array
    {
        if ($cases === []) {
            return $this->blankActivityReview($cases);
        }
        $chunks = [];
        $budget = 9000;
        foreach ($cases as $case) {
            $block = '[' . $case['id'] . '] ' . $case['name'] . "\n" . $case['text'];
            if (mb_strlen($block) + 2 > $budget) {
                break;
            }
            $chunks[] = $block;
            $budget -= mb_strlen($block) + 2;
        }
        $transcript = trim(implode("\n\n", $chunks));
        if ($transcript === '') {
            return $this->blankActivityReview($cases);
        }
        $system = <<<'PROMPT'
Eres el supervisor de la Coordinación de Atención al Cliente de una inmobiliaria. Armas el consolidado de un ATC a partir de sus conversaciones de Instagram. No inventes clientes, inmuebles, citas ni frases. Si un dato no está en la conversación, no lo escribas.

Responde únicamente JSON:
{"clientes":[{"id":0,"descripcion":""}],"mejor":{"id":0,"analisis":""},"peor":{"id":0,"analisis":""}}

clientes: un objeto por cada lead, con su mismo id.
descripcion: una sola línea corta, como en un parte de actividades, formato "motivo - resultado". Tono de ejemplo, sin copiarlo: "consulta de oficina - pedir plan de financiamiento", "no interesada", "agendó visita". Máximo 90 caracteres, sin punto final.

mejor: el id de la conversación mejor atendida. analisis: 3 a 5 frases sobre lo que el ATC hizo bien, con una cita literal breve entre comillas.
peor: el id de la conversación peor atendida. analisis: 3 a 5 frases sobre lo que hizo mal o dejó pendiente, con una cita literal breve entre comillas.
Si solo hay un lead, mejor y peor usan ese mismo id: mejor explica lo que estuvo bien y peor lo que quedó deficiente en esa única conversación.
Si hay más de un lead, mejor y peor son ids distintos. La mejor es donde respondió y dejó un paso siguiente. La peor es donde ignoró la pregunta, no retomó el hilo o dejó morir el interés.
PROMPT;
        $json = \App\Libraries\DeepSeekClient::chatCompletions([
            'model' => \App\Libraries\DeepSeekClient::defaultModel(),
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "ATC: {$agentName}\n\n{$transcript}"],
            ],
        ]);
        $content = (string) ($json['choices'][0]['message']['content'] ?? '');
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException('El agente no devolvió el reporte.');
        }
        $parsed = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($parsed)) {
            throw new \RuntimeException('El agente no devolvió el reporte.');
        }

        return $this->activityReview($parsed, $cases);
    }

    private function blankActivityReview(array $cases): array
    {
        $clientes = [];
        foreach ($cases as $case) {
            $clientes[] = [
                'id' => $case['id'],
                'name' => $case['name'],
                'phone' => $case['phone'],
                'account' => $case['account'],
                'descripcion' => 'sin mensajes en la conversación',
            ];
        }
        $first = $cases[0] ?? ['id' => 0, 'name' => 'Sin cliente', 'phone' => ''];
        $note = $cases === []
            ? 'No hay conversaciones atendidas en esta cuenta.'
            : 'No hay mensajes para comparar conversaciones.';

        return $this->reviewPayload($clientes, [
            'id' => $first['id'],
            'name' => $first['name'],
            'phone' => $first['phone'],
            'analisis' => $note,
        ], [
            'id' => $first['id'],
            'name' => $first['name'],
            'phone' => $first['phone'],
            'analisis' => $note,
            'misma' => true,
        ]);
    }

    private function activityReview(array $parsed, array $cases): array
    {
        $byId = [];
        foreach ($cases as $case) {
            $byId[$case['id']] = $case;
        }
        $described = [];
        $pending = [];
        $items = $parsed['clientes'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $text = $this->clip($item['descripcion'] ?? '', 120);
                $id = $this->caseId($item, $byId);
                if ($id > 0 && $text !== '') {
                    $described[$id] = $text;
                } else {
                    $pending[] = $text;
                }
            }
            if ($described === [] && count($pending) === count($cases)) {
                foreach ($cases as $index => $case) {
                    if ($pending[$index] !== '') {
                        $described[$case['id']] = $pending[$index];
                    }
                }
            }
        }
        $clientes = [];
        foreach ($cases as $case) {
            $clientes[] = [
                'id' => $case['id'],
                'name' => $case['name'],
                'phone' => $case['phone'],
                'account' => $case['account'],
                'descripcion' => $described[$case['id']] ?? 'consulta por Instagram - sin detalle',
            ];
        }
        $mejorId = $this->caseId(is_array($parsed['mejor'] ?? null) ? $parsed['mejor'] : [], $byId);
        if (! isset($byId[$mejorId])) {
            $mejorId = $cases[0]['id'];
        }
        $peorRaw = is_array($parsed['peor'] ?? null) ? $parsed['peor'] : [];
        $peorId = $this->caseId($peorRaw, $byId);
        if (! isset($byId[$peorId])) {
            $peorId = $mejorId;
        }
        if (count($cases) === 1) {
            $mejorId = $cases[0]['id'];
            $peorId = $cases[0]['id'];
        }
        $mejorAnalisis = $this->clip($parsed['mejor']['analisis'] ?? '', 900);
        $peorAnalisis = $this->clip($peorRaw['analisis'] ?? '', 900);
        if ($mejorAnalisis === '') {
            $mejorAnalisis = 'No hubo una cita suficiente para marcar la mejor conversación.';
        }
        if ($peorAnalisis === '') {
            $peorAnalisis = 'No hubo una cita suficiente para marcar la parte deficiente.';
        }

        return $this->reviewPayload($clientes, [
            'id' => $mejorId,
            'name' => $byId[$mejorId]['name'],
            'phone' => $byId[$mejorId]['phone'],
            'analisis' => $mejorAnalisis,
        ], [
            'id' => $peorId,
            'name' => $byId[$peorId]['name'],
            'phone' => $byId[$peorId]['phone'],
            'analisis' => $peorAnalisis,
            'misma' => count($cases) === 1 || $mejorId === $peorId,
        ]);
    }

    private function reviewPayload(array $clientes, array $mejor, array $peor): array
    {
        return [
            'clientes' => $clientes,
            'mejor' => $mejor,
            'peor' => $peor,
            'interactuo' => 'sí',
            'interactuo_nota' => $this->clip($mejor['analisis'], 500),
            'seguimiento' => 'sí',
            'seguimiento_nota' => 'El consolidado y el análisis van en el PDF.',
            'amable' => 'sí',
            'amable_nota' => 'El consolidado y el análisis van en el PDF.',
            'flojo' => 'no',
            'flojo_nota' => $this->clip($peor['analisis'], 500),
            'correccion' => $this->clip($peor['analisis'], 500),
        ];
    }

    private function caseId($item, array $byId): int
    {
        if (! is_array($item)) {
            return 0;
        }
        $id = (int) ($item['id'] ?? 0);
        if (isset($byId[$id])) {
            return $id;
        }
        $name = mb_strtolower(trim((string) ($item['nombre'] ?? $item['cliente'] ?? '')));
        if ($name === '') {
            return 0;
        }
        foreach ($byId as $case) {
            if (mb_strtolower($case['name']) === $name) {
                return (int) $case['id'];
            }
        }

        return 0;
    }

    private function clip($value, int $limit): string
    {
        $text = trim((string) $value);
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit);
        }

        return $text;
    }

    private function attentionPdf(array $sections, string $account): string
    {
        $when = new \DateTime('now', new \DateTimeZone('America/Caracas'));
        $days = ['DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
        $title = 'REPORTE ' . $days[(int) $when->format('w')] . ' ' . $when->format('d-m-y');
        $cuenta = $account === '' ? 'Todas las cuentas' : '@' . $account;
        $rows = '';
        $analysis = '';
        foreach ($sections as $section) {
            $review = $section['review'];
            $details = '<p class="reg">registros: ' . count($review['clientes']) . '</p>';
            $number = 1;
            foreach ($review['clientes'] as $cliente) {
                $phone = $cliente['phone'] !== '' ? $cliente['phone'] : 'sin teléfono';
                $details .= '<p class="item"><span class="num">' . $number . '</span><br>'
                    . $this->pdfText($cliente['name']) . '<br>'
                    . $this->pdfText($phone) . '<br>'
                    . '<span class="desc">' . $this->pdfText($cliente['descripcion']) . '</span>';
                if ($account === '' && $cliente['account'] !== '') {
                    $details .= '<br><span class="quiet">@' . $this->pdfText($cliente['account']) . '</span>';
                }
                $details .= '</p>';
                $number++;
            }
            if ($review['clientes'] === []) {
                $details .= '<p class="quiet">Sin clientes atendidos.</p>';
            }
            $rows .= '<tr><td>' . $this->pdfText($section['agent_name']) . '</td>'
                . '<td>Atención, Captación y Visitas</td><td>' . $details . '</td></tr>';
            $peorTitle = ! empty($review['peor']['misma'])
                ? 'Lo más deficiente de esa conversación'
                : 'Peor conversación';
            $analysis .= '<h2>Análisis · ' . $this->pdfText($section['agent_name']) . '</h2>';
            if (! empty($review['peor']['misma'])) {
                $analysis .= '<p class="quiet">Solo hay una conversación atendida. El análisis separa lo que se hizo bien y lo que quedó deficiente.</p>';
            }
            $analysis .= '<h3>Mejor conversación</h3>' . $this->pdfPerson($review['mejor']);
            $analysis .= '<h3>' . $this->pdfText($peorTitle) . '</h3>' . $this->pdfPerson($review['peor']);
        }
        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;color:#0B1C30;font-size:11px;}'
            . 'h1{font-size:16px;text-align:center;margin:0;}'
            . '.sub{text-align:center;margin:4px 0 0;font-size:11px;}'
            . '.cuenta{text-align:center;color:#45464D;margin:2px 0 0;}'
            . 'table{width:100%;border-collapse:collapse;margin-top:16px;}'
            . 'th{background:#0F172A;color:#fff;font-size:10px;text-align:left;padding:6px;}'
            . 'td{border:1px solid #0F172A;vertical-align:top;padding:8px;}'
            . '.reg{margin:0 0 8px;font-weight:bold;}'
            . '.item{margin:0 0 10px;}'
            . '.num{font-weight:bold;}'
            . '.quiet{color:#45464D;}'
            . 'h2{font-size:13px;margin:18px 0 4px;}'
            . 'h3{font-size:12px;margin:12px 0 2px;}'
            . '.who{font-weight:bold;margin:0 0 4px;}'
            . '</style></head><body>';
        $html .= '<h1>' . $this->pdfText($title) . '</h1>';
        $html .= '<p class="sub">Coordinación de Atención al Cliente (ATC) | Consolidado de Actividades</p>';
        $html .= '<p class="cuenta">' . $this->pdfText($cuenta) . '</p>';
        $html .= '<table><thead><tr><th style="width:22%">ATC</th><th style="width:28%">Gestión / Tipo de Acción</th><th>Detalles de Clientes, Requerimientos y Citas</th></tr></thead><tbody>'
            . $rows . '</tbody></table>';
        $html .= $analysis . '</body></html>';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter');
        $dompdf->render();

        return $dompdf->output();
    }

    private function pdfPerson(array $person): string
    {
        $phone = ($person['phone'] ?? '') !== '' ? $person['phone'] : 'sin teléfono';

        return '<p class="who">' . $this->pdfText($person['name'] ?? '') . '<br>' . $this->pdfText($phone) . '</p>'
            . '<p>' . $this->pdfText($person['analisis'] ?? '') . '</p>';
    }

    private function pdfText($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function caracasStamp(string $stored): string
    {
        $when = \DateTime::createFromFormat('Y-m-d H:i:s', substr($stored, 0, 19), new \DateTimeZone('UTC'));
        if (! $when) {
            return '';
        }
        $when->setTimezone(new \DateTimeZone('America/Caracas'));

        return $when->format('Y-m-d H:i');
    }

    private function canAssign(array $actor): bool
    {
        return in_array((int) ($actor['id_fk_rol'] ?? 0), [2, 3, 5, 6, 8], true);
    }

    private function isAtcSupervisor(array $actor): bool
    {
        return (int) ($actor['id'] ?? 0) === 110;
    }

    private function actor(): ?array
    {
        $id = AtcActor::$userId;
        if ($id < 1) {
            return null;
        }

        $user = (new User())
            ->select('users.id, users.full_name, users.email, users.phone, users.status, users.id_fk_rol, roles.description AS rol_name')
            ->join('roles', 'roles.id = users.id_fk_rol')
            ->where('users.id', $id)
            ->where('users.status', 'activo')
            ->first();

        return is_array($user) ? $user : null;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function publicUser(array $user): array
    {
        $roleId = (int) ($user['id_fk_rol'] ?? 0);

        return [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'role' => $user['rol_name'] ?? '',
            'role_id' => $roleId,
            'can_assign' => (int) ($user['id'] ?? 0) === 110,
            'can_claim' => $roleId === 7,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function leadCard(array $row): array
    {
        $statusId = $row['trackingstatus_id'] ?? null;
        if ($statusId === null && (int) ($row['pipeline_status_id'] ?? 0) > 0) {
            $statusId = (int) $row['pipeline_status_id'];
        }

        $attendance = \App\Libraries\AtcAgentHandoff::present(
            isset($row['agent_name']) ? (string) $row['agent_name'] : null,
            $row['assigned_id'] ?? null,
            $row['phone'] ?? null,
            ! empty($row['has_ai_reply'])
        );

        return [
            'id' => (int) $row['lead_id'],
            'name' => $row['name'],
            'phone' => $row['phone'] ?? null,
            'photo' => $this->photoUrl($row['profile_pic'] ?? null),
            'temperature' => $row['intention_label'],
            'instagram_username' => $row['instagram_username'],
            'account' => $row['recipient_ig_username'],
            'last_message_at' => $row['last_message_at'],
            'unread' => (int) ($row['unread_count'] ?? 0),
            'status_id' => $statusId !== null ? (int) $statusId : null,
            'created_at' => $row['created_at'] ?? null,
            'ad_title' => $row['ad_title'] ?? null,
            'ad_id' => $row['ad_id'] ?? null,
            'referral_source' => $row['referral_source'] ?? null,
            'agent_name' => $attendance['agent_name'],
            'phone_ready' => $attendance['phone_ready'],
        ];
    }

    private function photoUrl(?string $path): ?string
    {
        if (! is_string($path) || ! preg_match('#^/uploads/crm-(?:avatars|ads)/\d+\.(jpg|png|webp|gif)$#', $path)) {
            return null;
        }

        return 'https://rems.asesoresrm.com.ve' . $path;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $intKeys
     * @return list<array<string, mixed>>
     */
    private function ints(array $rows, array $intKeys): array
    {
        foreach ($rows as &$row) {
            foreach ($intKeys as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null) {
                    $row[$key] = (int) $row[$key];
                }
            }
        }
        unset($row);

        return $rows;
    }

    private function jwtSecret(): ?string
    {
        $secret = getenv('JWT_SECRET');
        if (! is_string($secret) || trim($secret) === '') {
            return null;
        }

        return trim($secret);
    }

    /**
     * @param array<string, mixed> $data
     */

    public function reply($id)
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $content = trim((string) ($payload['content'] ?? $this->request->getPost('content') ?? ''));
        if ($content === '') {
            return $this->fail('Escribe un mensaje.', 422);
        }
        if (mb_strlen($content) > 1000) {
            return $this->fail('El mensaje es demasiado largo.', 422);
        }

        $db = \Config\Database::connect();
        $conversation = $db->query(
            "SELECT id, channel, recipient_ig_id, external_id FROM conversations WHERE lead_id = ? AND channel = 'instagram' ORDER BY id DESC LIMIT 1",
            [(int) $id]
        )->getRowArray();
        if (! $conversation) {
            return $this->fail('Este lead no tiene conversación de Instagram.', 404);
        }

        $conversationId = (int) $conversation['id'];
        $lockName = 'crmsend' . $conversationId;
        $locked = $db->query('SELECT GET_LOCK(?, 25) AS locked', [$lockName])->getRowArray();
        if ((int) ($locked['locked'] ?? 0) !== 1) {
            return $this->fail('Ya hay un envío en curso. Espera un momento.', 409);
        }

        try {
            $duplicate = $db->query(
                "SELECT id FROM messages WHERE conversation_id = ? AND direction = 'outbound' AND content = ? AND created_at >= (NOW() - INTERVAL 25 SECOND) ORDER BY id DESC LIMIT 1",
                [$conversationId, $content]
            )->getRowArray();
            if ($duplicate) {
                return $this->ok(['duplicate' => true, 'message_id' => (int) $duplicate['id']]);
            }

            $externalMessageId = '';
            $recipientIgId = (string) ($conversation['recipient_ig_id'] ?? '');
            $customerId = (string) ($conversation['external_id'] ?? '');
            if ($recipientIgId === '' || $customerId === '') {
                return $this->fail('Esta conversación no se puede responder por Instagram.', 422);
            }
            $sendResult = \App\Libraries\MetaInstagramSend::sendTextMessage($recipientIgId, $customerId, $content);
            if (! ($sendResult['ok'] ?? false)) {
                return $this->fail((string) ($sendResult['error'] ?? 'No se pudo enviar el mensaje.'), 502);
            }
            $externalMessageId = (string) ($sendResult['message_id'] ?? '');

            $db->table('messages')->insert([
                'conversation_id' => $conversationId,
                'direction' => 'outbound',
                'sender_type' => 'agent',
                'sender_id' => (int) $actor['id'],
                'content' => $content,
                'content_type' => 'text',
                'external_message_id' => $externalMessageId !== '' ? $externalMessageId : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('conversations')->update([
                'last_message_at' => date('Y-m-d H:i:s'),
                'status' => 'assigned',
                'assigned_to' => (int) $actor['id'],
                'ai_auto_reply' => 0,
            ], ['id' => $conversationId]);

            $pythonAiUrl = getenv('PYTHON_AI_WEBHOOK_URL');
            if ($pythonAiUrl) {
                try {
                    $client = \Config\Services::curlrequest();
                    $client->post($pythonAiUrl . '/handoff', [
                        'json' => [
                            'conversation_id' => $conversationId,
                            'external_id' => $conversation['external_id'],
                            'action' => 'pause',
                        ],
                        'timeout' => 3,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to send handoff webhook: ' . $e->getMessage());
                }
            }

            return $this->ok(['duplicate' => false, 'sent_at' => date('c')]);
        } finally {
            $db->query('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    public function statuses()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $rows = \Config\Database::connect()->query('SELECT id, emoji, name, classifier_hint, is_active FROM trackingstatus ORDER BY id')->getResultArray();

        return $this->ok(['items' => $this->ints($rows, ['id', 'is_active'])]);
    }

    public function createStatus()
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $fields = $this->statusFields();
        if ($fields === null) {
            return $this->fail('Escribe el nombre del estatus.', 422);
        }
        $fields['is_active'] = 1;
        $model = new \App\Models\TrackingStatus();
        $model->insert($fields);

        return $this->ok(['id' => (int) $model->getInsertID()]);
    }

    public function updateStatus($id)
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $fields = $this->statusFields();
        if ($fields === null) {
            return $this->fail('Escribe el nombre del estatus.', 422);
        }
        (new \App\Models\TrackingStatus())->update((int) $id, $fields);

        return $this->ok(['id' => (int) $id]);
    }

    public function toggleStatus($id)
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        $db = \Config\Database::connect();
        $row = $db->query('SELECT id, is_active FROM trackingstatus WHERE id = ?', [(int) $id])->getRowArray();
        if (! $row) {
            return $this->fail('No encontramos ese estatus.', 404);
        }
        $next = (int) $row['is_active'] === 1 ? 0 : 1;
        $db->table('trackingstatus')->update(['is_active' => $next], ['id' => (int) $id]);

        return $this->ok(['is_active' => $next]);
    }

    public function deleteStatus($id)
    {
        if ($this->actor() === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if ((int) $id === 1) {
            return $this->fail('No se puede eliminar el estatus inicial.', 422);
        }
        \Config\Database::connect()->table('trackingstatus')->delete(['id' => (int) $id]);

        return $this->ok(['deleted' => (int) $id]);
    }

    private function statusFields(): ?array
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return null;
        }
        $emoji = trim((string) ($payload['emoji'] ?? ''));

        return [
            'name' => mb_substr($name, 0, 255),
            'emoji' => '',
            'classifier_hint' => mb_substr(trim((string) ($payload['classifier_hint'] ?? '')), 0, 255),
        ];
    }

    public function aiAgent()
    {
        if ($denied = $this->denyUnlessRole2()) {
            return $denied;
        }
        $row = (new \App\Models\AiCustomerAgent())->row();
        if ($row === null) {
            return $this->fail('No hay un agente configurado.', 404);
        }

        return $this->ok($this->aiAgentPayload($row));
    }

    public function saveAiAgent()
    {
        if ($denied = $this->denyUnlessRole2()) {
            return $denied;
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $soul = trim((string) ($payload['soul'] ?? ''));
        $username = (string) ($payload['username'] ?? '');
        if ($soul === '' || trim($username) === '') {
            return $this->fail('Falta el texto del agente o la cuenta.', 422);
        }
        $enabledRaw = $payload['enabled'] ?? null;
        $enabledOk = $enabledRaw === 0 || $enabledRaw === 1 || $enabledRaw === '0' || $enabledRaw === '1';
        if (! $enabledOk) {
            return $this->fail('El interruptor debe ser 0 o 1.', 422);
        }
        $account = \App\Libraries\AiCustomerAgentAccounts::find($username);
        if ($account === null) {
            return $this->fail('Esa cuenta no está en el mapa de Instagram.', 422);
        }
        $saved = (new \App\Models\AiCustomerAgent())->saveConfiguration(
            $soul,
            $account['username'],
            (int) $enabledRaw === 1 ? 1 : 0,
            $account['recipient_ig_id']
        );
        if ($saved === null) {
            return $this->fail('No hay un agente configurado.', 404);
        }

        return $this->ok($this->aiAgentPayload($saved));
    }

    public function sandboxAiAgent()
    {
        if ($denied = $this->denyUnlessRole2()) {
            return $denied;
        }
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->fail('Escribe un mensaje para probar.', 422);
        }
        $history = $payload['history'] ?? [];
        if (! is_array($history)) {
            $history = [];
        }
        $row = (new \App\Models\AiCustomerAgent())->row();
        if ($row === null) {
            return $this->fail('No hay un agente configurado.', 404);
        }
        $result = (new \App\Libraries\AiAgentSandbox(new \App\Libraries\CatalogAgentClient()))->run(
            2,
            $row,
            $message,
            $history,
            isset($payload['soul']) ? (string) $payload['soul'] : null
        );
        if (($result['ok'] ?? true) === false) {
            return $this->fail('No se pudo probar el agente.', 502);
        }

        return $this->ok([
            'reply' => $result['reply'] ?? '',
            'trace' => $result['trace'] ?? [],
            'extracted_phones' => $result['extracted_phones'] ?? [],
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function aiAgentPayload(array $row): array
    {
        return [
            'soul' => (string) ($row['soul'] ?? ''),
            'enabled' => (int) ($row['enabled'] ?? 0) === 1 ? 1 : 0,
            'username' => (string) ($row['instagram_username'] ?? ''),
            'recipient_ig_id' => $row['recipient_ig_id'] ?? null,
            'accounts' => \App\Libraries\AiCustomerAgentAccounts::list(),
        ];
    }

    private function denyUnlessRole2()
    {
        $actor = $this->actor();
        if ($actor === null) {
            return $this->fail('Sesión vencida.', 401);
        }
        if (! \App\Libraries\AiAgentAccess::allows((int) ($actor['id_fk_rol'] ?? 0))) {
            return $this->fail('No autorizado.', 403);
        }

        return null;
    }

    public function preflight()
    {
        return $this->withCors($this->response)->setStatusCode(204);
    }

    private function ok(array $data)
    {
        return $this->withCors($this->response)->setJSON(['status' => 'success'] + $data);
    }

    private function fail(string $message, int $code)
    {
        return $this->withCors($this->response->setStatusCode($code))->setJSON([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    private function withCors($response)
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Max-Age', '86400');

        return $response;
    }
}
