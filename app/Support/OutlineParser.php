<?php

namespace App\Support;

/**
 * Turns a pasted AACCUP outline into areas and parameters.
 *
 *   Area I. Vision, Mission, Goals and Objectives   -> area
 *   A.  Statement of Vision ...                      -> parameter of the area above
 *   Task Force/Working Committee                     -> area without parameters
 *
 * "Parameters", "Areas:" and visit titles are skipped. Parameter letters are
 * re-assigned A, B, C... so typos in the source lettering don't carry over.
 */
class OutlineParser
{
    public static function parse(string $text): array
    {
        $areas = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));

            if ($line === '' || preg_match('/^(parameters|areas)\s*:?$/i', $line) || preg_match('/\bsurvey\b|^revisit all areas$/i', $line)) {
                continue;
            }

            if ($areas && preg_match('/^[A-Z][.)]\s*(.+)$/', $line, $m)) {
                $areas[count($areas) - 1]['parameters'][] = $m[1];
                continue;
            }

            $areas[] = ['name' => $line, 'code' => self::areaCode($line), 'parameters' => []];
        }

        return $areas;
    }

    public static function areaCode(string $name): string
    {
        if (preg_match('/^Area\s+([IVXLC]+)\b/i', $name, $m)) {
            return 'AREA_' . strtoupper($m[1]);
        }

        return substr(strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_')), 0, 40);
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    public static function letter(int $index): string
    {
        $out = '';
        for ($i = $index + 1; $i > 0; $i = intdiv($i - 1, 26)) {
            $out = chr(65 + ($i - 1) % 26) . $out;
        }

        return $out;
    }
}
