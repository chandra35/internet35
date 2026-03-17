<?php

namespace App\Imports\Concerns;

trait SmartNikCleaner
{
    /**
     * Smart-clean a numeric ID string (NIK / No KK).
     * - Remove spaces, dots, commas, quotes, dashes
     * - Replace common OCR/typo letters with digits: O/o→0, I/l→1, S/s→5, B→8, G/g→6, Z/z→2, T→7
     * - Trim to max length if still too long after cleaning
     *
     * Returns [cleaned_value, was_modified, corrections[]]
     */
    protected function smartCleanNumericId(string $raw): array
    {
        if (empty($raw)) {
            return ['', false, []];
        }

        $original = $raw;
        $corrections = [];

        // Step 1: Remove whitespace
        $cleaned = preg_replace('/\s+/', '', $raw);
        if ($cleaned !== $raw) {
            $corrections[] = 'hapus spasi';
        }

        // Step 2: Remove common stray characters (dots, commas, quotes, dashes, apostrophes)
        $beforePunct = $cleaned;
        $cleaned = preg_replace('/[.,\'"\-`\x{2018}\x{2019}\x{201C}\x{201D}]+/u', '', $cleaned);
        if ($cleaned !== $beforePunct) {
            $corrections[] = 'hapus tanda baca';
        }

        // Step 3: Replace look-alike letters with digits
        $letterMap = [
            'O' => '0', 'o' => '0',
            'I' => '1', 'l' => '1', '|' => '1',
            'S' => '5', 's' => '5',
            'B' => '8',
            'G' => '6', 'g' => '6',
            'Z' => '2', 'z' => '2',
            'T' => '7',
            'b' => '6',
            'D' => '0',
        ];

        $beforeLetters = $cleaned;
        $replaced = [];
        $result = '';
        for ($i = 0; $i < strlen($cleaned); $i++) {
            $ch = $cleaned[$i];
            if (isset($letterMap[$ch])) {
                $digit = $letterMap[$ch];
                $replaced[$ch] = $digit;
                $result .= $digit;
            } else {
                $result .= $ch;
            }
        }
        $cleaned = $result;

        if (!empty($replaced)) {
            $pairs = [];
            foreach ($replaced as $from => $to) {
                $pairs[] = "{$from}→{$to}";
            }
            $corrections[] = 'koreksi huruf: ' . implode(', ', $pairs);
        }

        // Step 4: Remove any remaining non-digit characters
        $beforeNonDigit = $cleaned;
        $cleaned = preg_replace('/\D/', '', $cleaned);
        if ($cleaned !== $beforeNonDigit) {
            $corrections[] = 'hapus karakter non-angka';
        }

        $wasModified = $cleaned !== preg_replace('/\D/', '', $original);

        return [$cleaned, $wasModified, $corrections];
    }
}
