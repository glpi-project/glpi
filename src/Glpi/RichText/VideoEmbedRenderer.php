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
    public function render(string $provider, string $video_id, ?int $start = null): string
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

        $title = sprintf(__('%s video player'), $this->getProviderDisplayName($provider));

        // `allow-same-origin` is required (else the opaque-origin frame can't read its own storage
        // and the player won't start); safe here as `$src` is always a cross-origin provider host,
        // so the Same-Origin Policy still blocks parent-DOM access. Strict CSP needs these in `frame-src`.
        return sprintf(
            '<div class="video-embed-wrapper">'
            . '<iframe src="%s" title="%s" loading="lazy" allowfullscreen'
            . ' sandbox="allow-scripts allow-same-origin allow-presentation"'
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
    public function renderAll(string $sanitized_html): string
    {
        if (!str_contains($sanitized_html, 'data-video-provider')) {
            return $sanitized_html;
        }

        return $this->replacePlaceholders(
            $sanitized_html,
            function (string $provider, string $opening, string $body): string {
                if (trim($body) !== '') {
                    return '';
                }
                $video_id = $this->extractAttribute($opening, 'data-video-id');
                if ($video_id === null) {
                    return '';
                }
                $start_raw = $this->extractAttribute($opening, 'data-video-start');
                $start = ($start_raw !== null && ctype_digit($start_raw)) ? (int) $start_raw : null;

                return $this->render($provider, $video_id, $start);
            }
        );
    }

    /**
     * Plaintext fallback so video-only KB articles don't collapse to empty
     * search snippets / plaintext notifications.
     */
    public function renderAllAsText(string $html): string
    {
        if (!str_contains($html, 'data-video-provider')) {
            return $html;
        }

        return $this->replacePlaceholders(
            $html,
            function (string $provider, string $opening, string $body): string {
                $watch_url = $this->buildWatchUrlFromPlaceholder($provider, $opening, $body);
                if ($watch_url === null) {
                    return '';
                }

                return sprintf(
                    '[%s: %s]',
                    $this->getProviderDisplayName($provider),
                    $watch_url
                );
            }
        );
    }

    /**
     * HTML fallback for callers that paste KB content into a rich-text editor
     * (e.g. the "Use as solution" workflow): a sanitizer-safe `<a>` to the
     * provider's canonical watch URL. Same allowlist as {@see self::renderAllAsText()};
     * href and text are built from the validated id + hardcoded templates and
     * are htmlescape'd on output.
     */
    public function renderAllAsLink(string $html): string
    {
        if (!str_contains($html, 'data-video-provider')) {
            return $html;
        }

        return $this->replacePlaceholders(
            $html,
            function (string $provider, string $opening, string $body): string {
                $watch_url = $this->buildWatchUrlFromPlaceholder($provider, $opening, $body);
                if ($watch_url === null) {
                    return '';
                }

                $escaped = htmlescape($watch_url);
                return sprintf('<a href="%s" rel="noopener noreferrer">%s</a>', $escaped, $escaped);
            }
        );
    }

    /**
     * Validate a placeholder and build its canonical watch URL, or null if the
     * placeholder is tampered (non-empty body, unknown provider, malformed id).
     * Shared by {@see self::renderAllAsText()} and {@see self::renderAllAsLink()}.
     */
    private function buildWatchUrlFromPlaceholder(string $provider, string $opening, string $body): ?string
    {
        if (trim($body) !== '' || !isset(self::PROVIDER_WATCH_TEMPLATES[$provider])) {
            return null;
        }
        $video_id = $this->extractAttribute($opening, 'data-video-id');
        if ($video_id === null || preg_match(self::VIDEO_ID_PATTERN, $video_id) !== 1) {
            return null;
        }
        $start_raw = $this->extractAttribute($opening, 'data-video-start');
        $start = ($start_raw !== null && ctype_digit($start_raw)) ? (int) $start_raw : null;

        $watch_url = sprintf(self::PROVIDER_WATCH_TEMPLATES[$provider], rawurlencode($video_id));
        if ($start !== null && $start > 0) {
            $watch_url .= $this->buildWatchStartSuffix($provider, $start);
        }

        return $watch_url;
    }

    /**
     * Seek suffix to append to a provider's canonical watch URL.
     * Each provider has its own convention for the timestamp parameter.
     */
    private function buildWatchStartSuffix(string $provider, int $start): string
    {
        return match ($provider) {
            'youtube'     => '&t=' . $start . 's',
            'dailymotion' => '?start=' . $start,
            'vimeo'       => '#t=' . $start . 's',
            default       => '',
        };
    }

    /**
     * Walk the HTML and replace each `<div data-video-provider=...>...</div>`
     * block by whatever $render() returns. Nested `<div>` elements inside the
     * body are balanced so the matching outer `</div>` is consumed too —
     * preventing stray closing tags when a tampered placeholder contains a
     * child div.
     *
     * @param callable(string $provider, string $opening, string $body): string $render
     */
    private function replacePlaceholders(string $html, callable $render): string
    {
        $result = '';
        $offset = 0;
        $length = strlen($html);

        while ($offset < $length) {
            if (
                preg_match(
                    '#<div\b[^>]*\bdata-video-provider="([^"]+)"[^>]*>#i',
                    substr($html, $offset),
                    $matches
                ) !== 1
                || !isset($matches[0], $matches[1])
            ) {
                break;
            }
            $opening = $matches[0];
            $provider = $matches[1];
            $opening_start = (int) strpos($html, $opening, $offset);
            $opening_end = $opening_start + strlen($opening);

            $close_start = $this->findMatchingDivClose($html, $opening_end);
            if ($close_start === null) {
                // Unbalanced placeholder: drop the opening tag (it carries
                // the data-video-* attributes) and keep scanning. Falling
                // through would leak `<div data-video-provider="...">` into
                // the output via the trailing substr().
                $result .= substr($html, $offset, $opening_start - $offset);
                $offset = $opening_end;
                continue;
            }

            $body = substr($html, $opening_end, $close_start - $opening_end);

            $result .= substr($html, $offset, $opening_start - $offset);
            $result .= $render($provider, $opening, $body);

            $offset = $close_start + 6; // strlen('</div>')
        }

        return $result . substr($html, $offset);
    }

    /**
     * Offset of the `</div>` that closes the `<div>` opened just before
     * $offset, accounting for nested `<div>` children. Null if unbalanced.
     */
    private function findMatchingDivClose(string $html, int $offset): ?int
    {
        $depth = 1;
        $cursor = $offset;
        $length = strlen($html);

        while ($cursor < $length) {
            $next_open = stripos($html, '<div', $cursor);
            $next_close = stripos($html, '</div>', $cursor);

            if ($next_close === false) {
                return null;
            }

            if ($next_open !== false && $next_open < $next_close) {
                $tag_end = strpos($html, '>', $next_open);
                if ($tag_end === false) {
                    return null;
                }
                $depth++;
                $cursor = $tag_end + 1;
                continue;
            }

            $depth--;
            if ($depth === 0) {
                return $next_close;
            }
            $cursor = $next_close + 6;
        }

        return null;
    }

    /**
     * Extract a double-quoted attribute value from a single HTML tag string.
     *
     * @param string $tag  e.g. '<div data-video-id="abc"></div>'
     * @param string $attr Attribute name to look for.
     *
     * @return string|null Attribute value, or null if absent.
     */
    private function extractAttribute(string $tag, string $attr): ?string
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
    private function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'youtube'     => 'YouTube',
            'dailymotion' => 'Dailymotion',
            'vimeo'       => 'Vimeo',
            default       => $provider,
        };
    }
}
