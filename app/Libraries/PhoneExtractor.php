<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Deterministic phone extraction from free text (DM chats).
 * PHP port of agente/agente/contact_extract.py so both sides agree on formats.
 */
final class PhoneExtractor
{
    private const DIGIT_RUN = '/(?:\+?\s*58\s*)?(?:\(?\s*0?\s*4\s*\d\s*\d\s*\)?[\s\-\.]*)?\d(?:[\s\-\.\/]*\d){8,13}/u';

    private const STRICT_VE = '/(?:\+58|0058|58)[\s\-]?(?:4\d{2})[\s\-]?\d{7}|(?:^|[^\d])0?4\d{2}[\s\-]?\d{3}[\s\-]?\d{4}(?:[^\d]|$)/u';

    /**
     * @return list<string> de-duplicated candidates, Venezuelan mobiles as 58XXXXXXXXXX
     */
    public static function extract(string ...$parts): array
    {
        $blob = implode("\n", array_filter($parts, static fn (string $p): bool => $p !== ''));
        if (trim($blob) === '') {
            return [];
        }

        $out = [];
        $add = static function (?string $canonical) use (&$out): void {
            if ($canonical !== null && $canonical !== '' && ! in_array($canonical, $out, true)) {
                $out[] = $canonical;
            }
        };

        if (preg_match_all(self::STRICT_VE, $blob, $m) > 0) {
            foreach ($m[0] as $frag) {
                $d = self::digitsOnly($frag);
                $norm = self::normalize($d);
                $add($norm ?? (strlen($d) >= 10 && strlen($d) <= 12 ? $d : null));
            }
        }

        if (preg_match_all(self::DIGIT_RUN, $blob, $m) > 0) {
            foreach ($m[0] as $frag) {
                $d = self::digitsOnly($frag);
                if (strlen($d) < 10) {
                    continue;
                }
                $norm = self::normalize($d);
                if ($norm !== null) {
                    $add($norm);
                } elseif (strlen($d) <= 15) {
                    $add($d);
                }
            }
        }

        return $out;
    }

    private static function digitsOnly(string $s): string
    {
        return (string) preg_replace('/\D/', '', $s);
    }

    private static function normalize(string $digits): ?string
    {
        if ($digits === '') {
            return null;
        }
        $d = $digits;
        if (str_starts_with($d, '58') && strlen($d) >= 12) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, '0') && strlen($d) >= 11) {
            $d = substr($d, 1);
        }
        if (strlen($d) === 10 && $d[0] === '4') {
            return '58' . $d;
        }
        if (strlen($d) === 11 && str_starts_with($d, '58')) {
            return $d;
        }
        if (strlen($d) === 12 && str_starts_with($d, '584')) {
            return $d;
        }
        if (strlen($d) >= 10 && strlen($d) <= 12) {
            return $d;
        }

        return null;
    }
}
