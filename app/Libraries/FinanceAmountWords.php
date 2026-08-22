<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Convierte montos USD a palabras en español (formato recibo legal venezolano).
 */
class FinanceAmountWords
{
    public static function formatUsdAmount(float $amount): string
    {
        return 'USD ' . number_format($amount, 2, ',', '.');
    }

    public static function usdInWords(float $amount): string
    {
        $whole = (int) floor(abs($amount));
        $cents = (int) round((abs($amount) - $whole) * 100);

        $words = self::integerToWordsEs($whole);
        $result = strtoupper($words) . ' DOLARES AMERICANOS';

        if ($cents > 0) {
            $result .= ' CON ' . strtoupper(self::integerToWordsEs($cents)) . ' CENTAVOS';
        }

        return $result;
    }

    public static function integerToWordsEs(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        if ($number < 0) {
            return 'menos ' . self::integerToWordsEs(abs($number));
        }

        $parts = [];

        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $parts[] = $millions === 1 ? 'un millón' : self::integerToWordsEs($millions) . ' millones';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $parts[] = $thousands === 1 ? 'mil' : self::below1000($thousands) . ' mil';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = self::below1000($number);
        }

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    }

    private static function below1000(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        if ($number === 100) {
            return 'cien';
        }

        $parts = [];

        if ($number >= 100) {
            $h = intdiv($number, 100);
            if ($h === 1) {
                $parts[] = 'ciento';
            } elseif ($h === 5) {
                $parts[] = 'quinientos';
            } elseif ($h === 7) {
                $parts[] = 'setecientos';
            } elseif ($h === 9) {
                $parts[] = 'novecientos';
            } else {
                $parts[] = self::units($h) . 'cientos';
            }
            $number %= 100;
        }

        if ($number >= 30) {
            $t = intdiv($number, 10) * 10;
            $u = $number % 10;
            if ($u === 0) {
                $parts[] = self::tens($t);
            } else {
                $parts[] = self::tens($t) . ' y ' . self::units($u);
            }

            return trim(implode(' ', $parts));
        }

        if ($number >= 16 && $number <= 19) {
            $parts[] = self::teens($number);

            return trim(implode(' ', $parts));
        }

        if ($number >= 10 && $number <= 15) {
            $parts[] = self::specialTeens($number);

            return trim(implode(' ', $parts));
        }

        if ($number > 0) {
            $parts[] = self::units($number);
        }

        return trim(implode(' ', $parts));
    }

    private static function units(int $n): string
    {
        $map = [
            1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco',
            6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve',
        ];

        return $map[$n] ?? '';
    }

    private static function specialTeens(int $n): string
    {
        $map = [
            10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
        ];

        return $map[$n] ?? '';
    }

    private static function teens(int $n): string
    {
        $map = [
            16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
        ];

        return $map[$n] ?? '';
    }

    private static function tens(int $n): string
    {
        $map = [
            20 => 'veinte', 30 => 'treinta', 40 => 'cuarenta', 50 => 'cincuenta',
            60 => 'sesenta', 70 => 'setenta', 80 => 'ochenta', 90 => 'noventa',
        ];

        return $map[$n] ?? '';
    }
}
