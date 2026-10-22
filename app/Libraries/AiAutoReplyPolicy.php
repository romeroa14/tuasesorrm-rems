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

    /**
     * The per-conversation switch (ai_auto_reply, saved from the app) is what activates the agent.
     * AI_AUTO_REPLY_MODE=dry_run is only an emergency kill switch: drafts are logged, nothing is sent.
     */
    public static function mode(): string
    {
        $mode = strtolower(trim((string) getenv('AI_AUTO_REPLY_MODE')));

        return $mode === self::MODE_DRY_RUN ? self::MODE_DRY_RUN : self::MODE_LIVE;
    }

    /**
     * True when an outbound message captured from Meta is just the echo of a reply the bot sent.
     *
     * @param list<string> $recentBotDrafts
     */
    public static function isBotEcho(string $content, array $recentBotDrafts): bool
    {
        $norm = static fn (string $s): string => trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        $content = $norm($content);
        if ($content === '') {
            return false;
        }

        foreach ($recentBotDrafts as $draft) {
            if ($norm($draft) === $content) {
                return true;
            }
        }

        return false;
    }

    /** Instagram DMs do not render Markdown: strip it and keep lists readable. */
    public static function plainText(string $text): string
    {
        $text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/', '$1: $2', $text) ?? $text;
        $text = preg_replace('/(\*\*|__)(.+?)\1/su', '$2', $text) ?? $text;
        $text = preg_replace('/^\s*[-*]\s+/m', '• ', $text) ?? $text;
        $text = str_replace('`', '', $text);

        return trim($text);
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
