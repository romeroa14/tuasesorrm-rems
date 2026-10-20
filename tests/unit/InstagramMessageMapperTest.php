<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\InstagramMessageMapper;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class InstagramMessageMapperTest extends CIUnitTestCase
{
    private const ACCOUNT = '17841407224141177';

    public function testCustomerMessageIsInboundFromLead(): void
    {
        $row = InstagramMessageMapper::toRow([
            'id'           => 'mid.1',
            'created_time' => '2026-10-16T14:05:09+0000',
            'from'         => ['id' => '555', 'username' => 'cliente'],
            'message'      => 'Hola, quiero info de Onyx',
        ], self::ACCOUNT);

        $this->assertSame([
            'external_message_id' => 'mid.1',
            'direction'           => 'inbound',
            'sender_type'         => 'lead',
            'customer_id'         => '555',
            'content'             => 'Hola, quiero info de Onyx',
            'content_type'        => 'text',
            'created_at'          => '2026-10-16 14:05:09',
        ], $row);
    }

    public function testAccountMessageIsOutboundFromAgentWithoutAdvisorId(): void
    {
        $row = InstagramMessageMapper::toRow([
            'id'           => 'mid.2',
            'created_time' => '2026-10-16T10:00:00-0400',
            'from'         => ['id' => self::ACCOUNT],
            'to'           => ['data' => [['id' => '555']]],
            'message'      => "Hola cómo está?\nValery Mendoza | Asesores RM",
        ], self::ACCOUNT);

        $this->assertSame('outbound', $row['direction']);
        $this->assertSame('agent', $row['sender_type']);
        $this->assertSame('555', $row['customer_id']);
        $this->assertSame('2026-10-16 14:00:00', $row['created_at']);
    }

    public function testMessageWithoutTextKeepsAPlaceholder(): void
    {
        $row = InstagramMessageMapper::toRow([
            'id'           => 'mid.3',
            'created_time' => '2026-10-17T08:00:00+0000',
            'from'         => ['id' => '555'],
            'message'      => '',
        ], self::ACCOUNT);

        $this->assertSame('[attachment]', $row['content']);
        $this->assertSame('attachment', $row['content_type']);
    }

    public function testRejectsMessagesWithoutIdOrDate(): void
    {
        $this->assertNull(InstagramMessageMapper::toRow(['from' => ['id' => '555']], self::ACCOUNT));
    }
}
