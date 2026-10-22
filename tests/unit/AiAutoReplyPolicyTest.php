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

    public function testOnlyAnExplicitClientRequestTriggersHandoff(): void
    {
        $this->assertSame('pedido_humano', AiAutoReplyPolicy::handoffReason('frio', 'quiero hablar con un asesor'));
        $this->assertSame('pedido_humano', AiAutoReplyPolicy::handoffReason('caliente', 'me pueden llamar?'));
    }

    public function testHotScoreAloneDoesNotTriggerHandoff(): void
    {
        $this->assertNull(AiAutoReplyPolicy::handoffReason('caliente', 'busco casa en Caracas hasta 40.000 $'));
        $this->assertNull(AiAutoReplyPolicy::handoffReason('tibio', 'busco casa'));
    }

    public function testConversationSwitchDecidesAndEnvIsOnlyAKillSwitch(): void
    {
        putenv('AI_AUTO_REPLY_MODE');
        $this->assertSame('live', AiAutoReplyPolicy::mode(), 'without .env the per-conversation switch is enough');
        putenv('AI_AUTO_REPLY_MODE=live');
        $this->assertSame('live', AiAutoReplyPolicy::mode());
        putenv('AI_AUTO_REPLY_MODE=dry_run');
        $this->assertSame('dry_run', AiAutoReplyPolicy::mode());
        putenv('AI_AUTO_REPLY_MODE');
    }

    public function testMarkdownIsStrippedForInstagram(): void
    {
        $md = "He encontrado opciones:\n\n### Caracas\n1. **Apartamento** en Zona Oeste\n- Precio: __38.000__ USD\n* [Ver ficha](https://x.com/p/1)\n`ok`";
        $plain = AiAutoReplyPolicy::plainText($md);
        $this->assertStringNotContainsString('###', $plain);
        $this->assertStringNotContainsString('**', $plain);
        $this->assertStringNotContainsString('__', $plain);
        $this->assertStringNotContainsString('`', $plain);
        $this->assertStringContainsString('Caracas', $plain);
        $this->assertStringContainsString('1. Apartamento en Zona Oeste', $plain);
        $this->assertStringContainsString('• Precio: 38.000 USD', $plain);
        $this->assertStringContainsString('Ver ficha: https://x.com/p/1', $plain);
    }

    public function testBotEchoIsRecognizedByContentIgnoringWhitespace(): void
    {
        $drafts = ["Hola,\n\nbusco opciones  para usted", 'otra respuesta'];
        $this->assertTrue(AiAutoReplyPolicy::isBotEcho('Hola, busco opciones para usted', $drafts));
        $this->assertFalse(AiAutoReplyPolicy::isBotEcho('Me comunico yo con usted', $drafts));
        $this->assertFalse(AiAutoReplyPolicy::isBotEcho('', $drafts));
        $this->assertFalse(AiAutoReplyPolicy::isBotEcho('algo', []));
    }

    public function testPlainTextLeavesNormalTextUntouched(): void
    {
        $this->assertSame('Hola, ¿en qué zona busca?', AiAutoReplyPolicy::plainText('Hola, ¿en qué zona busca?'));
    }
}
