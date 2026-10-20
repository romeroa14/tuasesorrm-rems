<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\AiAutoReplyPolicy;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AiAutoReplyPolicyTest extends CIUnitTestCase
{
    public function testExplicitHumanRequestIsDetected(): void
    {
        foreach (['Quiero hablar con un asesor', 'prefiero hablar con una persona', 'pásame con un humano', 'me pueden llamar?'] as $text) {
            $this->assertTrue(AiAutoReplyPolicy::asksForHuman($text), $text);
        }
    }

    public function testNormalMessagesAreNotHandoff(): void
    {
        foreach (['Busco apartamento en Altamira', 'cuál es el precio?', 'tienen algo de 2 habitaciones'] as $text) {
            $this->assertFalse(AiAutoReplyPolicy::asksForHuman($text), $text);
        }
    }

    public function testHotScoreTriggersHandoff(): void
    {
        $this->assertSame('score_caliente', AiAutoReplyPolicy::handoffReason('caliente', 'ok'));
        $this->assertSame('pedido_humano', AiAutoReplyPolicy::handoffReason('frio', 'quiero hablar con un asesor'));
        $this->assertNull(AiAutoReplyPolicy::handoffReason('tibio', 'busco casa'));
    }

    public function testModeDefaultsToDryRun(): void
    {
        putenv('AI_AUTO_REPLY_MODE');
        $this->assertSame('dry_run', AiAutoReplyPolicy::mode());
        putenv('AI_AUTO_REPLY_MODE=live');
        $this->assertSame('live', AiAutoReplyPolicy::mode());
        putenv('AI_AUTO_REPLY_MODE=cualquier-cosa');
        $this->assertSame('dry_run', AiAutoReplyPolicy::mode());
        putenv('AI_AUTO_REPLY_MODE');
    }
}
