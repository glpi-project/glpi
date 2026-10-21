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

use Glpi\RichText\MermaidRenderer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MermaidRendererTest extends GLPITestCase
{
    private const LINK = '<p><a href="http://localhost/glpi/front/knowbaseitem.form.php?id=12">See the diagram in the article</a></p>';

    /**
     * @return iterable<string, array{input: string, expected: string}>
     */
    public static function addArticleLinksProvider(): iterable
    {
        yield 'link added after the diagram source' => [
            'input'    => "<p>Before</p><pre><code class=\"language-mermaid\">flowchart TD\n    A --&gt; B</code></pre><p>After</p>",
            'expected' => "<p>Before</p><pre><code class=\"language-mermaid\">flowchart TD\n    A --&gt; B</code></pre>" . self::LINK . '<p>After</p>',
        ];
        yield 'link added after each diagram' => [
            'input'    => '<pre><code class="language-mermaid">A</code></pre><pre><code class="language-mermaid">B</code></pre>',
            'expected' => '<pre><code class="language-mermaid">A</code></pre>' . self::LINK . '<pre><code class="language-mermaid">B</code></pre>' . self::LINK,
        ];
        yield 'other code blocks kept' => [
            'input'    => '<pre><code class="language-php">echo 1;</code></pre><pre><code>mermaid</code></pre>',
            'expected' => '<pre><code class="language-php">echo 1;</code></pre><pre><code>mermaid</code></pre>',
        ];
        yield 'inline code kept' => [
            'input'    => '<p><code class="language-mermaid">A --&gt; B</code></p>',
            'expected' => '<p><code class="language-mermaid">A --&gt; B</code></p>',
        ];
    }

    #[DataProvider('addArticleLinksProvider')]
    public function testAddArticleLinks(string $input, string $expected): void
    {
        $this->assertSame(
            $expected,
            (new MermaidRenderer())->addArticleLinks($input, 'http://localhost/glpi/front/knowbaseitem.form.php?id=12')
        );
    }

    public function testAddArticleLinksEscapesUrl(): void
    {
        $this->assertSame(
            '<pre><code class="language-mermaid">A</code></pre><p><a href="/a?b=1&amp;c=&quot;x&quot;">See the diagram in the article</a></p>',
            (new MermaidRenderer())->addArticleLinks('<pre><code class="language-mermaid">A</code></pre>', '/a?b=1&c="x"')
        );
    }
}
