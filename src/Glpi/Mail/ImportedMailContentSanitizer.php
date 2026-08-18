<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Mail;

use Safe\Exceptions\IconvException;
use Safe\Exceptions\MbstringException;
use Throwable;

use function Safe\iconv;
use function Safe\mb_convert_encoding;
use function Safe\preg_match;
use function Safe\preg_replace;

final class ImportedMailContentSanitizer
{
    /**
     * @var null|array<string, string>
     */
    private static ?array $mojibake_map = null;

    private const FALLBACK_ENCODINGS = [
        'Windows-1252',
        'ISO-8859-1',
        'ISO-8859-15',
    ];

    private const UTF_8_BOM = "\xEF\xBB\xBF";

    private const MOJIBAKE_REPAIR_CHARS = [
        'á',
        'à',
        'â',
        'ã',
        'ä',
        'é',
        'è',
        'ê',
        'ë',
        'í',
        'ì',
        'î',
        'ï',
        'ó',
        'ò',
        'ô',
        'õ',
        'ö',
        'ú',
        'ù',
        'û',
        'ü',
        'ç',
        'ñ',
        'Á',
        'À',
        'Â',
        'Ã',
        'Ä',
        'É',
        'È',
        'Ê',
        'Ë',
        'Í',
        'Ì',
        'Î',
        'Ï',
        'Ó',
        'Ò',
        'Ô',
        'Õ',
        'Ö',
        'Ú',
        'Ù',
        'Û',
        'Ü',
        'Ç',
        'Ñ',
        '€',
        '£',
        '§',
        '©',
        '®',
        '°',
        'ª',
        'º',
        '–',
        '—',
        '‘',
        '’',
        '“',
        '”',
        '…',
        "\u{00A0}",
    ];

    public function sanitize(string $content, ?string $declared_charset = null): ImportedMailContentSanitizationResult
    {
        $original = $content;
        $steps = [];
        $source_encoding = null;

        if ($content === '') {
            return new ImportedMailContentSanitizationResult($content, false, [], null);
        }

        if (str_starts_with($content, self::UTF_8_BOM)) {
            $content = substr($content, strlen(self::UTF_8_BOM));
            $steps[] = 'strip_utf8_bom';
        }

        foreach ($this->getUtf16BomCharsets($content) as $charset => $bom) {
            $converted = $this->convertEncoding(substr($content, strlen($bom)), $charset);
            if ($converted !== null && $this->isValidUtf8($converted)) {
                $content = $converted;
                $source_encoding = $charset;
                $steps[] = 'convert_' . strtolower(str_replace('-', '_', $charset));
                break;
            }
        }

        if (!$this->isValidUtf8($content)) {
            foreach ($this->getCandidateEncodings($declared_charset) as $encoding) {
                $converted = $this->convertEncoding($content, $encoding);
                if ($converted !== null && $this->isValidUtf8($converted)) {
                    $content = $converted;
                    $source_encoding = $encoding;
                    $steps[] = 'convert_' . strtolower(str_replace('-', '_', $encoding));
                    break;
                }
            }
        }

        if (!$this->isValidUtf8($content)) {
            try {
                $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $content);
                $content = $cleaned;
            } catch (IconvException) {
                $converted = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                if (is_string($converted)) {
                    $content = $converted;
                }
            }
            $steps[] = 'drop_invalid_utf8_bytes';
        }

        $content_without_controls = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        if (is_string($content_without_controls) && $content_without_controls !== $content) {
            $content = $content_without_controls;
            $steps[] = 'strip_control_bytes';
        }

        $repaired_content = $this->repairMojibake($content);
        if ($repaired_content !== $content) {
            $content = $repaired_content;
            $steps[] = 'repair_mojibake';
        }

        if (!$this->isValidUtf8($content)) {
            try {
                $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $content);
                $content = $cleaned;
            } catch (IconvException) {
            }
            $steps[] = 'final_utf8_guard';
        }

        return new ImportedMailContentSanitizationResult(
            $content,
            $content !== $original,
            array_values(array_unique($steps)),
            $source_encoding
        );
    }

    /**
     * @return string[]
     */
    private function getCandidateEncodings(?string $declared_charset): array
    {
        $candidates = [];
        if ($declared_charset !== null) {
            $normalized = $this->normalizeCharset($declared_charset);
            if ($normalized !== null && strtoupper($normalized) !== 'UTF-8') {
                $candidates[] = $normalized;
            }
        }

        foreach (self::FALLBACK_ENCODINGS as $encoding) {
            $candidates[] = $encoding;
        }

        return array_values(array_unique($candidates));
    }

    private function normalizeCharset(string $charset): ?string
    {
        $charset = trim($charset, " \t\n\r\0\x0B\"'");
        if ($charset === '') {
            return null;
        }

        if (preg_match('/^WINDOWS-(?<codepage>\d{4})$/i', $charset, $matches) === 1) {
            return isset($matches['codepage']) ? 'CP' . $matches['codepage'] : $charset;
        }

        if (strtoupper($charset) === 'ISO-8859-8-I') {
            return 'ISO-8859-8';
        }

        if (in_array(strtolower($charset), ['ks_c_5601-1987', 'ks_c_5601-1989'], true)) {
            return 'UHC';
        }

        return $charset;
    }

    private function convertEncoding(string $content, string $source_encoding): ?string
    {
        try {
            $converted = mb_convert_encoding($content, 'UTF-8', $source_encoding);
        } catch (MbstringException) {
            try {
                $converted = iconv($source_encoding, 'UTF-8//IGNORE', $content);
            } catch (IconvException) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return is_string($converted) ? $converted : null;
    }

    private function repairMojibake(string $content): string
    {
        $map = $this->getMojibakeMap();
        $current_score = $this->getMojibakeScore($content, $map);

        if ($current_score === 0) {
            return $content;
        }

        $candidate = strtr($content, $map);
        $candidate_score = $this->getMojibakeScore($candidate, $map);

        return $candidate_score < $current_score ? $candidate : $content;
    }

    /**
     * @return array<string, string>
     */
    private function getMojibakeMap(): array
    {
        if (self::$mojibake_map !== null) {
            return self::$mojibake_map;
        }

        $map = [
            'ï»¿' => '',
            'ï¿½' => '�',
        ];

        foreach (self::MOJIBAKE_REPAIR_CHARS as $char) {
            foreach (['ISO-8859-1', 'Windows-1252'] as $encoding) {
                $mojibake = $this->convertEncoding($char, $encoding);
                if ($mojibake !== null && $mojibake !== $char) {
                    $map[$mojibake] = $char;
                }
            }
        }

        self::$mojibake_map = $map;

        return self::$mojibake_map;
    }

    /**
     * @param array<string, string> $map
     */
    private function getMojibakeScore(string $content, array $map): int
    {
        $score = 0;
        foreach (array_keys($map) as $token) {
            if ($token !== '') {
                $score += substr_count($content, $token);
            }
        }

        return $score;
    }

    private function isValidUtf8(string $content): bool
    {
        return mb_check_encoding($content, 'UTF-8');
    }

    /**
     * @return array<string, string>
     */
    private function getUtf16BomCharsets(string $content): array
    {
        $charsets = [];
        if (str_starts_with($content, "\xFF\xFE")) {
            $charsets['UTF-16LE'] = "\xFF\xFE";
        }
        if (str_starts_with($content, "\xFE\xFF")) {
            $charsets['UTF-16BE'] = "\xFE\xFF";
        }

        return $charsets;
    }
}
