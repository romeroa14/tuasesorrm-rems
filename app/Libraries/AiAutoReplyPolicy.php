<?php

namespace App\Libraries;

/**
 * Pure rules for the per-conversation auto-reply: mode and human handoff.
 */
class AiAutoReplyPolicy
{
    public const MODE_DRY_RUN = 'dry_run';
    public const MODE_LIVE    = 'live';

    private const HUMAN_REQUEST = '/\b(asesor|agente humano|humano|persona|ejecutivo|vendedor)\b.*\b(hablar|hablo|comunic|pasa|pásame|pasame|atienda|contacte)|\b(hablar|hablo|comunic|pasa|pásame|pasame)\b.*\b(asesor|humano|persona|ejecutivo|vendedor)\b|\b(me\s+(pueden|puede|podr[ií]an)\s+llamar|ll[aá]men?me|ll[aá]mame)\b/iu';

    /** dry_run unless AI_AUTO_REPLY_MODE is exactly "live". */
    public static function mode(): string
    {
        $mode = strtolower(trim((string) getenv('AI_AUTO_REPLY_MODE')));

        return $mode === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_DRY_RUN;
    }

    public static function asksForHuman(string $text): bool
    {
        return preg_match(self::HUMAN_REQUEST, $text) === 1;
    }

    /** @return string|null handoff reason, or null when the agent may keep answering */
    public static function handoffReason(?string $intentionLabel, string $inboundText): ?string
    {
        if (self::asksForHuman($inboundText)) {
            return 'pedido_humano';
        }

        if (strtolower((string) $intentionLabel) === 'caliente') {
            return 'score_caliente';
        }

        return null;
    }
}
