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

namespace tests\units\Glpi\RichText;

use Glpi\RichText\VideoEmbedRenderer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class VideoEmbedRendererTest extends GLPITestCase
{
    /**
     * Exact iframe markup produced by {@see VideoEmbedRenderer::render()}.
     * `%s` placeholders: already-escaped src, already-escaped title.
     * Kept here as an independent copy so any change to the production
     * template is caught by the assertSame comparisons below.
     */
    private const IFRAME = '<div class="video-embed-wrapper"><iframe src="%s" title="%s"'
        . ' loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"'
        . ' sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"'
        . ' frameborder="0"></iframe></div>';

    public static function renderProvider(): iterable
    {
        yield 'YouTube nominal' => [
            'youtube', 'dQw4w9WgXcQ', null,
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'YouTube video player',
        ];
        yield 'YouTube with start offset' => [
            'youtube', 'dQw4w9WgXcQ', 75,
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=75', 'YouTube video player',
        ];
        yield 'Dailymotion nominal' => [
            'dailymotion', 'x7ufrcj', null,
            'https://www.dailymotion.com/embed/video/x7ufrcj', 'Dailymotion video player',
        ];
        yield 'Vimeo nominal (dnt=1)' => [
            'vimeo', '76979871', null,
            'https://player.vimeo.com/video/76979871?dnt=1', 'Vimeo video player',
        ];
        yield 'Vimeo start offset uses & separator' => [
            'vimeo', '76979871', 42,
            'https://player.vimeo.com/video/76979871?dnt=1&start=42', 'Vimeo video player',
        ];
        yield 'Underscore and dash in id are accepted' => [
            'youtube', 'A_B-c1234567', null,
            'https://www.youtube-nocookie.com/embed/A_B-c1234567', 'YouTube video player',
        ];
    }

    #[DataProvider('renderProvider')]
    public function testRenderProducesSandboxedIframe(
        string $provider,
        string $video_id,
        ?int $start,
        string $expected_src,
        string $expected_title,
    ): void {
        $this->assertSame(
            sprintf(self::IFRAME, htmlescape($expected_src), htmlescape($expected_title)),
            VideoEmbedRenderer::render($provider, $video_id, $start),
        );
    }

    public static function rejectedRenderProvider(): iterable
    {
        yield 'Unknown provider' => ['twitch', 'abc12345', null];
        yield 'Empty id'         => ['youtube', '', null];
        yield 'Path traversal'   => ['youtube', '../../etc/passwd', null];
        yield 'Quote injection'  => ['youtube', 'abc"><script>', null];
        yield 'Whitespace in id' => ['youtube', 'abc 12345', null];
        yield 'Id over 32 chars' => ['youtube', str_repeat('a', 33), null];
        yield 'Slash in id'      => ['youtube', 'abc/def', null];
    }

    #[DataProvider('rejectedRenderProvider')]
    public function testRenderReturnsEmptyForInvalidInputs(
        string $provider,
        string $video_id,
        ?int $start,
    ): void {
        $this->assertSame('', VideoEmbedRenderer::render($provider, $video_id, $start));
    }

    public static function renderAllProvider(): iterable
    {
        yield 'unrelated html untouched' => [
            '<p>Hello <strong>world</strong></p>',
            '<p>Hello <strong>world</strong></p>',
        ];
        yield 'replaces every placeholder' => [
            '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
            . '<p>Between</p>'
            . '<div data-video-provider="vimeo" data-video-id="123456789"></div>',
            sprintf(self::IFRAME, 'https://www.youtube-nocookie.com/embed/aaa11111111', 'YouTube video player')
            . '<p>Between</p>'
            . sprintf(self::IFRAME, 'https://player.vimeo.com/video/123456789?dnt=1', 'Vimeo video player'),
        ];
        yield 'unknown provider dropped' => [
            '<p>Before</p><div data-video-provider="evil" data-video-id="xxxx"></div><p>After</p>',
            '<p>Before</p><p>After</p>',
        ];
        yield 'unsafe id dropped' => [
            '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            '',
        ];
        yield 'missing data-video-id dropped' => [
            '<div data-video-provider="youtube"></div>',
            '',
        ];
        yield 'no placeholder passthrough' => [
            '<p>No video here</p>',
            '<p>No video here</p>',
        ];
        // Tampered placeholder (the editor's atom node never produces children):
        // drop the whole element, including any smuggled child such as an iframe
        // surviving GLPI_ALLOW_IFRAME_IN_RICH_TEXT=true.
        yield 'non-empty body with smuggled iframe dropped' => [
            '<p>Before</p>'
            . '<div data-video-provider="youtube" data-video-id="abc12345678"><iframe src="https://evil.example/x"></iframe></div>'
            . '<p>After</p>',
            '<p>Before</p><p>After</p>',
        ];
        yield 'text body dropped' => [
            '<div data-video-provider="youtube" data-video-id="abc12345678">Some text</div>',
            '',
        ];
        // Nested <div> in the body breaks the atom-node contract. Drop the whole
        // block, including the OUTER </div> — the lazy regex used previously
        // stopped at the first </div> and leaked a stray closing tag.
        yield 'nested div tampered dropped' => [
            '<p>Before</p>'
            . '<div data-video-provider="youtube" data-video-id="abc12345678"><div class="inner">x</div></div>'
            . '<p>After</p>',
            '<p>Before</p><p>After</p>',
        ];
        // Unbalanced placeholder (no matching </div>): drop the opening tag — it
        // carries the data-video-* attributes — and keep scanning so any later
        // valid placeholder is still processed.
        yield 'unclosed placeholder keeps trailing html' => [
            '<p>Before</p>'
            . '<div data-video-provider="youtube" data-video-id="abc12345678">'
            . '<p>Trailing paragraph</p>'
            . '<div data-video-provider="vimeo" data-video-id="76979871"></div>',
            '<p>Before</p><p>Trailing paragraph</p>'
            . sprintf(self::IFRAME, 'https://player.vimeo.com/video/76979871?dnt=1', 'Vimeo video player'),
        ];
    }

    #[DataProvider('renderAllProvider')]
    public function testRenderAll(string $input, string $expected): void
    {
        $this->assertSame($expected, VideoEmbedRenderer::renderAll($input));
    }

    public static function renderAllAsTextProvider(): iterable
    {
        yield 'substitutes watch url' => [
            '<p>See:</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>',
            '<p>See:</p>[YouTube: https://www.youtube.com/watch?v=dQw4w9WgXcQ]',
        ];
        yield 'handles all providers' => [
            '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
            . '<div data-video-provider="dailymotion" data-video-id="x7ufrcj"></div>'
            . '<div data-video-provider="vimeo" data-video-id="76979871"></div>',
            '[YouTube: https://www.youtube.com/watch?v=aaa11111111]'
            . '[Dailymotion: https://www.dailymotion.com/video/x7ufrcj]'
            . '[Vimeo: https://vimeo.com/76979871]',
        ];
        yield 'unknown provider dropped' => [
            '<p>Before</p><div data-video-provider="evil" data-video-id="x"></div><p>After</p>',
            '<p>Before</p><p>After</p>',
        ];
        yield 'malformed id dropped' => [
            '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            '',
        ];
        yield 'no placeholder passthrough' => [
            '<p>No video</p>',
            '<p>No video</p>',
        ];
    }

    #[DataProvider('renderAllAsTextProvider')]
    public function testRenderAllAsText(string $input, string $expected): void
    {
        $this->assertSame($expected, VideoEmbedRenderer::renderAllAsText($input));
    }

    public static function renderAllAsLinkProvider(): iterable
    {
        yield 'anchor to watch url' => [
            '<p>See:</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>',
            '<p>See:</p><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" rel="noopener noreferrer">https://www.youtube.com/watch?v=dQw4w9WgXcQ</a>',
        ];
        yield 'handles all providers' => [
            '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
            . '<div data-video-provider="dailymotion" data-video-id="x7ufrcj"></div>'
            . '<div data-video-provider="vimeo" data-video-id="76979871"></div>',
            '<a href="https://www.youtube.com/watch?v=aaa11111111" rel="noopener noreferrer">https://www.youtube.com/watch?v=aaa11111111</a>'
            . '<a href="https://www.dailymotion.com/video/x7ufrcj" rel="noopener noreferrer">https://www.dailymotion.com/video/x7ufrcj</a>'
            . '<a href="https://vimeo.com/76979871" rel="noopener noreferrer">https://vimeo.com/76979871</a>',
        ];
        // YouTube watch URLs use &t=NNs as the seek suffix; the & is escaped on output.
        yield 'start offset applied to href' => [
            '<div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ" data-video-start="90"></div>',
            '<a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;t=90s" rel="noopener noreferrer">https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;t=90s</a>',
        ];
        yield 'unknown provider dropped' => [
            '<p>Before</p><div data-video-provider="evil" data-video-id="x"></div><p>After</p>',
            '<p>Before</p><p>After</p>',
        ];
        yield 'malformed id dropped' => [
            '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            '',
        ];
        // Tampered placeholder — drop the whole element so any smuggled child
        // (e.g. an iframe to evil.example) cannot survive through the <a>.
        yield 'non-empty body with smuggled iframe dropped' => [
            '<div data-video-provider="youtube" data-video-id="abc12345678"><iframe src="https://evil.example/x"></iframe></div>',
            '',
        ];
        yield 'no placeholder passthrough' => [
            '<p>No video</p>',
            '<p>No video</p>',
        ];
    }

    #[DataProvider('renderAllAsLinkProvider')]
    public function testRenderAllAsLink(string $input, string $expected): void
    {
        $this->assertSame($expected, VideoEmbedRenderer::renderAllAsLink($input));
    }
}
