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
    public static function renderProvider(): iterable
    {
        yield 'YouTube nominal' => [
            'provider'        => 'youtube',
            'video_id'        => 'dQw4w9WgXcQ',
            'start'           => null,
            'expected_src'    => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'expected_title'  => 'YouTube video player',
        ];
        yield 'YouTube with start offset' => [
            'provider'        => 'youtube',
            'video_id'        => 'dQw4w9WgXcQ',
            'start'           => 75,
            'expected_src'    => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=75',
            'expected_title'  => 'YouTube video player',
        ];
        yield 'Dailymotion nominal' => [
            'provider'        => 'dailymotion',
            'video_id'        => 'x7ufrcj',
            'start'           => null,
            'expected_src'    => 'https://www.dailymotion.com/embed/video/x7ufrcj',
            'expected_title'  => 'Dailymotion video player',
        ];
        yield 'Vimeo nominal (dnt=1)' => [
            'provider'        => 'vimeo',
            'video_id'        => '76979871',
            'start'           => null,
            'expected_src'    => 'https://player.vimeo.com/video/76979871?dnt=1',
            'expected_title'  => 'Vimeo video player',
        ];
        yield 'Vimeo start offset uses & separator' => [
            'provider'        => 'vimeo',
            'video_id'        => '76979871',
            'start'           => 42,
            'expected_src'    => 'https://player.vimeo.com/video/76979871?dnt=1&start=42',
            'expected_title'  => 'Vimeo video player',
        ];
        yield 'Underscore and dash in id are accepted' => [
            'provider'        => 'youtube',
            'video_id'        => 'A_B-c1234567',
            'start'           => null,
            'expected_src'    => 'https://www.youtube-nocookie.com/embed/A_B-c1234567',
            'expected_title'  => 'YouTube video player',
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
        $html = VideoEmbedRenderer::render($provider, $video_id, $start);

        $this->assertStringContainsString('<div class="video-embed-wrapper">', $html);
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('src="' . htmlescape($expected_src) . '"', $html);
        $this->assertStringContainsString('title="' . htmlescape($expected_title) . '"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('allowfullscreen', $html);
        $this->assertStringContainsString('referrerpolicy="strict-origin-when-cross-origin"', $html);
        $this->assertStringContainsString('sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"', $html);
        $this->assertStringContainsString('frameborder="0"', $html);
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

    public function testRenderAllLeavesUnrelatedHtmlUntouched(): void
    {
        $html = '<p>Hello <strong>world</strong></p>';
        $this->assertSame($html, VideoEmbedRenderer::renderAll($html));
    }

    public function testRenderAllReplacesAllPlaceholders(): void
    {
        $html = '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
              . '<p>Between</p>'
              . '<div data-video-provider="vimeo" data-video-id="123456789"></div>';

        $rendered = VideoEmbedRenderer::renderAll($html);

        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/aaa11111111', $rendered);
        $this->assertStringContainsString('https://player.vimeo.com/video/123456789?dnt=1', $rendered);
        $this->assertStringContainsString('<p>Between</p>', $rendered);
        $this->assertStringNotContainsString('data-video-provider', $rendered);
    }

    public function testRenderAllDropsUnknownProviderPlaceholders(): void
    {
        $html = '<p>Before</p><div data-video-provider="evil" data-video-id="xxxx"></div><p>After</p>';
        $rendered = VideoEmbedRenderer::renderAll($html);

        $this->assertStringNotContainsString('data-video-provider', $rendered);
        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringContainsString('<p>Before</p>', $rendered);
        $this->assertStringContainsString('<p>After</p>', $rendered);
    }

    public function testRenderAllDropsPlaceholdersWithUnsafeIds(): void
    {
        $html = '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>';
        $rendered = VideoEmbedRenderer::renderAll($html);

        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringNotContainsString('etc/passwd', $rendered);
    }

    public function testRenderAllIgnoresMissingDataVideoIdAttribute(): void
    {
        $html = '<div data-video-provider="youtube"></div>';
        $rendered = VideoEmbedRenderer::renderAll($html);

        $this->assertSame('', $rendered);
    }

    public function testRenderAllShortCircuitsWhenNoPlaceholder(): void
    {
        $html = '<p>No video here</p>';
        $this->assertSame($html, VideoEmbedRenderer::renderAll($html));
    }

    public function testRenderAllDropsPlaceholderWithNonEmptyBody(): void
    {
        // Tampered placeholder (the editor's atom node never produces
        // children). Drop the whole element — including any smuggled child
        // such as an iframe surviving GLPI_ALLOW_IFRAME_IN_RICH_TEXT=true.
        $html = '<p>Before</p>'
              . '<div data-video-provider="youtube" data-video-id="abc12345678"><iframe src="https://evil.example/x"></iframe></div>'
              . '<p>After</p>';

        $rendered = VideoEmbedRenderer::renderAll($html);

        $this->assertStringNotContainsString('evil.example', $rendered);
        $this->assertStringNotContainsString('data-video-provider', $rendered);
        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringContainsString('<p>Before</p>', $rendered);
        $this->assertStringContainsString('<p>After</p>', $rendered);
    }

    public function testRenderAllDropsPlaceholderWithTextBody(): void
    {
        $html = '<div data-video-provider="youtube" data-video-id="abc12345678">Some text</div>';
        $this->assertSame('', VideoEmbedRenderer::renderAll($html));
    }

    public function testRenderAllAsTextSubstitutesWatchUrl(): void
    {
        $html = '<p>See:</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>';
        $rendered = VideoEmbedRenderer::renderAllAsText($html);

        $this->assertStringContainsString('[YouTube: https://www.youtube.com/watch?v=dQw4w9WgXcQ]', $rendered);
        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringNotContainsString('data-video-provider', $rendered);
    }

    public function testRenderAllAsTextHandlesAllProviders(): void
    {
        $html = '<div data-video-provider="youtube" data-video-id="aaa11111111"></div>'
              . '<div data-video-provider="dailymotion" data-video-id="x7ufrcj"></div>'
              . '<div data-video-provider="vimeo" data-video-id="76979871"></div>';

        $rendered = VideoEmbedRenderer::renderAllAsText($html);

        $this->assertStringContainsString('[YouTube: https://www.youtube.com/watch?v=aaa11111111]', $rendered);
        $this->assertStringContainsString('[Dailymotion: https://www.dailymotion.com/video/x7ufrcj]', $rendered);
        $this->assertStringContainsString('[Vimeo: https://vimeo.com/76979871]', $rendered);
    }

    public function testRenderAllAsTextDropsUnknownProvider(): void
    {
        $html = '<p>Before</p><div data-video-provider="evil" data-video-id="x"></div><p>After</p>';
        $rendered = VideoEmbedRenderer::renderAllAsText($html);

        $this->assertStringNotContainsString('evil', $rendered);
        $this->assertStringContainsString('<p>Before</p>', $rendered);
        $this->assertStringContainsString('<p>After</p>', $rendered);
    }

    public function testRenderAllAsTextDropsMalformedId(): void
    {
        $html = '<div data-video-provider="youtube" data-video-id="../../etc/passwd"></div>';
        $this->assertSame('', VideoEmbedRenderer::renderAllAsText($html));
    }

    public function testRenderAllAsTextShortCircuitsWhenNoPlaceholder(): void
    {
        $html = '<p>No video</p>';
        $this->assertSame($html, VideoEmbedRenderer::renderAllAsText($html));
    }
}
