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
        . ' loading="lazy" allowfullscreen'
        . ' sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>';

    /**
     * Exact `<video>` markup produced by {@see VideoEmbedRenderer::renderDirectVideo()}.
     * `%s` placeholders: already-escaped src, already-escaped title.
     */
    private const VIDEO = '<div class="video-embed-wrapper"><video src="%s" title="%s" controls'
        . ' preload="metadata"></video></div>';

    public static function renderProvider(): iterable
    {
        yield 'YouTube nominal' => [
            'provider'       => 'youtube',
            'video_id'       => 'dQw4w9WgXcQ',
            'expected_src'   => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'expected_title' => 'YouTube video player',
        ];
        yield 'Underscore and dash in id are accepted' => [
            'provider'       => 'youtube',
            'video_id'       => 'A_B-c1234567',
            'expected_src'   => 'https://www.youtube-nocookie.com/embed/A_B-c1234567',
            'expected_title' => 'YouTube video player',
        ];
    }

    #[DataProvider('renderProvider')]
    public function testRenderProducesSandboxedIframe(
        string $provider,
        string $video_id,
        string $expected_src,
        string $expected_title,
    ): void {
        $this->assertSame(
            sprintf(self::IFRAME, htmlescape($expected_src), htmlescape($expected_title)),
            (new VideoEmbedRenderer())->render($provider, $video_id),
        );
    }

    public static function rejectedRenderProvider(): iterable
    {
        yield 'Unknown provider' => ['provider' => 'twitch', 'video_id' => 'abc12345'];
        yield 'Empty id'         => ['provider' => 'youtube', 'video_id' => ''];
        yield 'Path traversal'   => ['provider' => 'youtube', 'video_id' => '../../etc/passwd'];
        yield 'Quote injection'  => ['provider' => 'youtube', 'video_id' => 'abc"><script>'];
        yield 'Whitespace in id' => ['provider' => 'youtube', 'video_id' => 'abc 12345'];
        yield 'Id over 32 chars' => ['provider' => 'youtube', 'video_id' => str_repeat('a', 33)];
        yield 'Slash in id'      => ['provider' => 'youtube', 'video_id' => 'abc/def'];
    }

    #[DataProvider('rejectedRenderProvider')]
    public function testRenderReturnsEmptyForInvalidInputs(
        string $provider,
        string $video_id,
    ): void {
        $this->assertSame('', (new VideoEmbedRenderer())->render($provider, $video_id));
    }

    public static function renderDirectVideoProvider(): iterable
    {
        yield 'mp4 over https'        => ['https://cdn.example.com/clip.mp4'];
        yield 'webm over http'        => ['http://cdn.example.com/clip.webm'];
        yield 'ogg with query string' => ['https://cdn.example.com/clip.ogv?token=abc'];
        yield 'uppercase extension'   => ['https://cdn.example.com/CLIP.MP4'];
    }

    #[DataProvider('renderDirectVideoProvider')]
    public function testRenderDirectVideoProducesVideoElement(string $src): void
    {
        $this->assertSame(
            sprintf(self::VIDEO, htmlescape($src), htmlescape('Embedded video')),
            (new VideoEmbedRenderer())->renderDirectVideo($src),
        );
    }

    public static function rejectedDirectVideoProvider(): iterable
    {
        yield 'javascript scheme'      => ['javascript:alert(1)//clip.mp4'];
        yield 'data scheme'            => ['data:video/mp4;base64,AAAA'];
        yield 'no scheme'              => ['cdn.example.com/clip.mp4'];
        yield 'disallowed extension'   => ['https://cdn.example.com/clip.exe'];
        yield 'no extension'           => ['https://cdn.example.com/clip'];
        yield 'extension not at end'   => ['https://cdn.example.com/clip.mp4/extra'];
        yield 'quote injection'        => ['https://cdn.example.com/clip.mp4"><script>'];
        yield 'empty string'           => [''];
    }

    #[DataProvider('rejectedDirectVideoProvider')]
    public function testRenderDirectVideoReturnsEmptyForInvalidInputs(string $src): void
    {
        $this->assertSame('', (new VideoEmbedRenderer())->renderDirectVideo($src));
    }

    public static function renderAllProvider(): iterable
    {
        yield 'unrelated html untouched' => [
            'input'    => '<p>Hello <strong>world</strong></p>',
            'expected' => '<p>Hello <strong>world</strong></p>',
        ];
        yield 'replaces every placeholder' => [
            'input' => '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
                . '<p>Between</p>'
                . '<div data-video-provider="video" data-video-src="https://cdn.example.com/clip.mp4"></div>',
            'expected' => sprintf(self::IFRAME, 'https://www.youtube-nocookie.com/embed/aaa11111111', 'YouTube video player')
                . '<p>Between</p>'
                . sprintf(self::VIDEO, 'https://cdn.example.com/clip.mp4', 'Embedded video'),
        ];
        yield 'direct video invalid src dropped' => [
            'input'    => '<div data-video-provider="video" data-video-src="javascript:alert(1)//x.mp4"></div>',
            'expected' => '',
        ];
        yield 'direct video missing src dropped' => [
            'input'    => '<div data-video-provider="video"></div>',
            'expected' => '',
        ];
        yield 'unknown provider dropped' => [
            'input'    => '<p>Before</p><div data-video-provider="evil" data-video-id="xxxx"></div><p>After</p>',
            'expected' => '<p>Before</p><p>After</p>',
        ];
        yield 'unsafe id dropped' => [
            'input'    => '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            'expected' => '',
        ];
        yield 'missing data-video-id dropped' => [
            'input'    => '<div data-video-provider="youtube"></div>',
            'expected' => '',
        ];
        yield 'no placeholder passthrough' => [
            'input'    => '<p>No video here</p>',
            'expected' => '<p>No video here</p>',
        ];
        // renderAll only materializes EMPTY placeholders (the editor's atom node
        // never produces children); a <div data-video-provider> with a body is left as-is.
        yield 'non-empty placeholder left untouched' => [
            'input'    => '<p>Before</p><div data-video-provider="youtube" data-video-id="abc12345678">Some text</div><p>After</p>',
            'expected' => '<p>Before</p><div data-video-provider="youtube" data-video-id="abc12345678">Some text</div><p>After</p>',
        ];
        yield 'nested div placeholder left untouched' => [
            'input'    => '<p>Before</p><div data-video-provider="youtube" data-video-id="abc12345678"><div class="inner">x</div></div><p>After</p>',
            'expected' => '<p>Before</p><div data-video-provider="youtube" data-video-id="abc12345678"><div class="inner">x</div></div><p>After</p>',
        ];
        // An empty placeholder is still materialized even when it follows an
        // unclosed non-empty one; the non-empty opening tag is inert and kept.
        yield 'empty placeholder after an unclosed one is still rendered' => [
            'input' => '<p>Before</p>'
                . '<div data-video-provider="youtube" data-video-id="abc12345678">'
                . '<p>Trailing paragraph</p>'
                . '<div data-video-provider="youtube" data-video-id="bbb22222222"></div>',
            'expected' => '<p>Before</p>'
                . '<div data-video-provider="youtube" data-video-id="abc12345678">'
                . '<p>Trailing paragraph</p>'
                . sprintf(self::IFRAME, 'https://www.youtube-nocookie.com/embed/bbb22222222', 'YouTube video player'),
        ];
    }

    #[DataProvider('renderAllProvider')]
    public function testRenderAll(string $input, string $expected): void
    {
        $this->assertSame($expected, (new VideoEmbedRenderer())->renderAll($input));
    }

    public static function renderAllAsTextProvider(): iterable
    {
        yield 'substitutes watch url' => [
            'input'    => '<p>See:</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>',
            'expected' => '<p>See:</p>[YouTube: https://www.youtube.com/watch?v=dQw4w9WgXcQ]',
        ];
        yield 'handles all providers' => [
            'input' => '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
                . '<div data-video-provider="video" data-video-src="https://cdn.example.com/clip.mp4"></div>',
            'expected' => '[YouTube: https://www.youtube.com/watch?v=aaa11111111]'
                . '[Video: https://cdn.example.com/clip.mp4]',
        ];
        yield 'unknown provider dropped' => [
            'input'    => '<p>Before</p><div data-video-provider="evil" data-video-id="x"></div><p>After</p>',
            'expected' => '<p>Before</p><p>After</p>',
        ];
        yield 'malformed id dropped' => [
            'input'    => '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            'expected' => '',
        ];
        yield 'no placeholder passthrough' => [
            'input'    => '<p>No video</p>',
            'expected' => '<p>No video</p>',
        ];
    }

    #[DataProvider('renderAllAsTextProvider')]
    public function testRenderAllAsText(string $input, string $expected): void
    {
        $this->assertSame($expected, (new VideoEmbedRenderer())->renderAllAsText($input));
    }

    public static function renderAllAsLinkProvider(): iterable
    {
        yield 'anchor to watch url' => [
            'input'    => '<p>See:</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>',
            'expected' => '<p>See:</p><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" rel="noopener noreferrer">https://www.youtube.com/watch?v=dQw4w9WgXcQ</a>',
        ];
        yield 'handles all providers' => [
            'input' => '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
                . '<div data-video-provider="video" data-video-src="https://cdn.example.com/clip.mp4"></div>',
            'expected' => '<a href="https://www.youtube.com/watch?v=aaa11111111" rel="noopener noreferrer">https://www.youtube.com/watch?v=aaa11111111</a>'
                . '<a href="https://cdn.example.com/clip.mp4" rel="noopener noreferrer">https://cdn.example.com/clip.mp4</a>',
        ];
        yield 'unknown provider dropped' => [
            'input'    => '<p>Before</p><div data-video-provider="evil" data-video-id="x"></div><p>After</p>',
            'expected' => '<p>Before</p><p>After</p>',
        ];
        yield 'malformed id dropped' => [
            'input'    => '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>',
            'expected' => '',
        ];
        // Non-empty placeholder left untouched: only empty placeholders are rewritten.
        yield 'non-empty placeholder left untouched' => [
            'input'    => '<div data-video-provider="youtube" data-video-id="abc12345678">Some text</div>',
            'expected' => '<div data-video-provider="youtube" data-video-id="abc12345678">Some text</div>',
        ];
        yield 'no placeholder passthrough' => [
            'input'    => '<p>No video</p>',
            'expected' => '<p>No video</p>',
        ];
    }

    #[DataProvider('renderAllAsLinkProvider')]
    public function testRenderAllAsLink(string $input, string $expected): void
    {
        $this->assertSame($expected, (new VideoEmbedRenderer())->renderAllAsLink($input));
    }
}
