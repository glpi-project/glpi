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

namespace Glpi\RichText;

use function Safe\preg_match;
use function Safe\preg_replace_callback;

/**
 * Reconstructs sandboxed iframes from the inert `<div data-video-provider ...>`
 * placeholders stored by the KB Tiptap editor, using a hard-coded provider
 * allowlist so no user-supplied iframe ever traverses the HTML sanitizer.
 */
final class VideoEmbedRenderer
{
    /**
     * Provider key → embed URL `sprintf` template. Privacy-friendly defaults:
     * youtube-nocookie, vimeo dnt=1.
     */
    private const PROVIDER_URL_TEMPLATES = [
        'youtube'     => 'https://www.youtube-nocookie.com/embed/%s',
        'dailymotion' => 'https://www.dailymotion.com/embed/video/%s',
        'vimeo'       => 'https://player.vimeo.com/video/%s?dnt=1',
    ];

    /**
     * Provider key → canonical watch URL template, used by the plaintext fallback.
     */
    private const PROVIDER_WATCH_TEMPLATES = [
        'youtube'     => 'https://www.youtube.com/watch?v=%s',
        'dailymotion' => 'https://www.dailymotion.com/video/%s',
        'vimeo'       => 'https://vimeo.com/%s',
    ];

    /**
     * Strict pattern for accepted video IDs.
     */
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    /**
     * @param string   $provider Must be a key of {@see self::PROVIDER_URL_TEMPLATES}.
     * @param string   $video_id Must match {@see self::VIDEO_ID_PATTERN}.
     * @param int|null $start    Playback offset in seconds.
     *
     * @return string Safe iframe HTML, or empty string on invalid input.
     */
    public static function render(string $provider, string $video_id, ?int $start = null): string
    {
        if (!isset(self::PROVIDER_URL_TEMPLATES[$provider])) {
            return '';
        }
        if (!preg_match(self::VIDEO_ID_PATTERN, $video_id)) {
            return '';
        }

        $src = sprintf(self::PROVIDER_URL_TEMPLATES[$provider], rawurlencode($video_id));
        if ($start !== null && $start > 0) {
            $separator = str_contains($src, '?') ? '&' : '?';
            $src .= $separator . 'start=' . $start;
        }

        $title = sprintf(__('%s video player'), self::getProviderDisplayName($provider));

        return sprintf(
            '<div class="video-embed-wrapper">'
            . '<iframe src="%s" title="%s" loading="lazy" allowfullscreen'
            . ' referrerpolicy="strict-origin-when-cross-origin"'
            . ' sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"'
            . ' frameborder="0"></iframe>'
            . '</div>',
            htmlescape($src),
            htmlescape($title)
        );
    }

    /**
     * Replace each placeholder by its iframe. Tampered placeholders
     * (non-whitespace body — the atom node never produces children) and those
     * with an unknown provider or malformed id are dropped.
     */
    public static function renderAll(string $sanitized_html): string
    {
        if (!str_contains($sanitized_html, 'data-video-provider')) {
            return $sanitized_html;
        }

        return preg_replace_callback(
            '#<div\b[^>]*\bdata-video-provider="([^"]+)"[^>]*>([\s\S]*?)</div>#i',
            static function (array $matches): string {
                if (trim($matches[2]) !== '') {
                    return '';
                }

                $tag = $matches[0];
                $provider = $matches[1];

                $video_id = self::extractAttribute($tag, 'data-video-id');
                if ($video_id === null) {
                    return '';
                }

                $start_raw = self::extractAttribute($tag, 'data-video-start');
                $start = null;
                if ($start_raw !== null && ctype_digit($start_raw)) {
                    $start = (int) $start_raw;
                }

                return self::render($provider, $video_id, $start);
            },
            $sanitized_html
        );
    }

    /**
     * Plaintext fallback so video-only KB articles don't collapse to empty
     * search snippets / plaintext notifications.
     */
    public static function renderAllAsText(string $html): string
    {
        if (!str_contains($html, 'data-video-provider')) {
            return $html;
        }

        return preg_replace_callback(
            '#<div\b[^>]*\bdata-video-provider="([^"]+)"[^>]*>[\s\S]*?</div>#i',
            static function (array $matches): string {
                $tag = $matches[0];
                $provider = $matches[1];
                if (!isset(self::PROVIDER_WATCH_TEMPLATES[$provider])) {
                    return '';
                }

                $video_id = self::extractAttribute($tag, 'data-video-id');
                if ($video_id === null || preg_match(self::VIDEO_ID_PATTERN, $video_id) !== 1) {
                    return '';
                }

                return sprintf(
                    '[%s: %s]',
                    self::getProviderDisplayName($provider),
                    sprintf(self::PROVIDER_WATCH_TEMPLATES[$provider], rawurlencode($video_id))
                );
            },
            $html
        );
    }

    /**
     * Extract a double-quoted attribute value from a single HTML tag string.
     *
     * @param string $tag  e.g. '<div data-video-id="abc"></div>'
     * @param string $attr Attribute name to look for.
     *
     * @return string|null Attribute value, or null if absent.
     */
    private static function extractAttribute(string $tag, string $attr): ?string
    {
        $pattern = '/\b' . preg_quote($attr, '/') . '="([^"]*)"/i';
        if (preg_match($pattern, $tag, $m) === 1 && isset($m[1])) {
            return $m[1];
        }
        return null;
    }

    /**
     * Display name for a supported provider key.
     */
    private static function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'youtube'     => 'YouTube',
            'dailymotion' => 'Dailymotion',
            'vimeo'       => 'Vimeo',
            default       => $provider,
        };
    }
}
