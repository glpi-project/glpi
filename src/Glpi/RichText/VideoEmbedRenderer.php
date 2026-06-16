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
 * Reconstructs sandboxed iframes (YouTube) and `<video>` elements (direct file
 * URLs) from the inert `<div data-video-provider ...>` placeholders stored by
 * the KB Tiptap editor, using a hard-coded provider allowlist so no
 * user-supplied media ever traverses the HTML sanitizer.
 */
final class VideoEmbedRenderer
{
    /**
     * Synthetic provider key for a direct video file URL embedded as `<video>`;
     * the URL lives in `data-video-src` instead of a `data-video-id`.
     */
    private const DIRECT_VIDEO_PROVIDER = 'video';

    /**
     * Provider key → embed URL `sprintf` template. Privacy-friendly default:
     * youtube-nocookie.
     */
    private const PROVIDER_URL_TEMPLATES = [
        'youtube' => 'https://www.youtube-nocookie.com/embed/%s',
    ];

    /**
     * Provider key → canonical watch URL template, used by the plaintext fallback.
     */
    private const PROVIDER_WATCH_TEMPLATES = [
        'youtube' => 'https://www.youtube.com/watch?v=%s',
    ];

    /**
     * Strict pattern for accepted video IDs.
     */
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    /**
     * Accepted direct video file URL: http(s) scheme + allowlisted extension
     * (optionally followed by a query string or fragment).
     */
    private const DIRECT_VIDEO_URL_PATTERN = '#^https?://[^\s<>"]+\.(?:mp4|webm|ogg|ogv|mov)(?:[?\#][^\s<>"]*)?$#i';

    /**
     * @param string $provider Must be a key of {@see self::PROVIDER_URL_TEMPLATES}.
     * @param string $video_id Must match {@see self::VIDEO_ID_PATTERN}.
     *
     * @return string Safe iframe HTML, or empty string on invalid input.
     */
    public function render(string $provider, string $video_id): string
    {
        if (!isset(self::PROVIDER_URL_TEMPLATES[$provider])) {
            return '';
        }
        if (!preg_match(self::VIDEO_ID_PATTERN, $video_id)) {
            return '';
        }

        $src = sprintf(self::PROVIDER_URL_TEMPLATES[$provider], rawurlencode($video_id));
        $title = sprintf(__('%s video player'), $this->getProviderDisplayName($provider));

        // `allow-same-origin` is required (else the opaque-origin frame can't read its own storage
        // and the player won't start); safe here as `$src` is always a cross-origin provider host,
        // so the Same-Origin Policy still blocks parent-DOM access. Strict CSP needs these in `frame-src`.
        return sprintf(
            '<div class="video-embed-wrapper">'
            . '<iframe src="%s" title="%s" loading="lazy" allowfullscreen'
            . ' sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>'
            . '</div>',
            htmlescape($src),
            htmlescape($title)
        );
    }

    /**
     * Render a direct video file URL as a native `<video>` element.
     *
     * @param string $src Must match {@see self::DIRECT_VIDEO_URL_PATTERN}.
     *
     * @return string Safe `<video>` HTML, or empty string on invalid input.
     */
    public function renderDirectVideo(string $src): string
    {
        if (!$this->isValidDirectVideoSrc($src)) {
            return '';
        }

        return sprintf(
            '<div class="video-embed-wrapper">'
            . '<video src="%s" title="%s" controls preload="metadata"></video>'
            . '</div>',
            htmlescape($src),
            htmlescape(__('Embedded video'))
        );
    }

    /**
     * Whether $src is an acceptable direct video file URL.
     */
    private function isValidDirectVideoSrc(string $src): bool
    {
        return preg_match(self::DIRECT_VIDEO_URL_PATTERN, $src) === 1;
    }

    /**
     * Replace each placeholder by its iframe / `<video>`. Placeholders with an
     * unknown provider or malformed id/src are dropped.
     */
    public function renderAll(string $sanitized_html): string
    {
        if (!str_contains($sanitized_html, 'data-video-provider')) {
            return $sanitized_html;
        }

        return $this->replacePlaceholders(
            $sanitized_html,
            function (string $provider, string $opening): string {
                if ($provider === self::DIRECT_VIDEO_PROVIDER) {
                    $src = $this->extractAttribute($opening, 'data-video-src');
                    return $src !== null ? $this->renderDirectVideo($src) : '';
                }
                $video_id = $this->extractAttribute($opening, 'data-video-id');
                if ($video_id === null) {
                    return '';
                }

                return $this->render($provider, $video_id);
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
            function (string $provider, string $opening): string {
                $watch_url = $this->buildWatchUrlFromPlaceholder($provider, $opening);
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
            function (string $provider, string $opening): string {
                $watch_url = $this->buildWatchUrlFromPlaceholder($provider, $opening);
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
     * provider is unknown or the id/src is malformed.
     * Shared by {@see self::renderAllAsText()} and {@see self::renderAllAsLink()}.
     */
    private function buildWatchUrlFromPlaceholder(string $provider, string $opening): ?string
    {
        if ($provider === self::DIRECT_VIDEO_PROVIDER) {
            $src = $this->extractAttribute($opening, 'data-video-src');
            return ($src !== null && $this->isValidDirectVideoSrc($src)) ? $src : null;
        }
        if (!isset(self::PROVIDER_WATCH_TEMPLATES[$provider])) {
            return null;
        }
        $video_id = $this->extractAttribute($opening, 'data-video-id');
        if ($video_id === null || preg_match(self::VIDEO_ID_PATTERN, $video_id) !== 1) {
            return null;
        }

        return sprintf(self::PROVIDER_WATCH_TEMPLATES[$provider], rawurlencode($video_id));
    }

    /**
     * Materialize each EMPTY `<div data-video-provider=...></div>` placeholder —
     * the only form the editor's atom node produces — via $render(). Non-empty
     * such divs are left as-is: the HTML-emitting callers ({@see self::renderAll()},
     * {@see self::renderAllAsLink()}) pass already-sanitized input, and
     * {@see self::renderAllAsText()} only emits plain text.
     *
     * @param callable(string $provider, string $opening): string $render
     */
    private function replacePlaceholders(string $html, callable $render): string
    {
        return preg_replace_callback(
            '#<div\b[^>]*\bdata-video-provider="([^"]+)"[^>]*>\s*</div>#i',
            static fn(array $m): string => $render($m[1], $m[0]),
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
            'youtube'                   => 'YouTube',
            self::DIRECT_VIDEO_PROVIDER => __('Video'),
            default                     => $provider,
        };
    }
}
