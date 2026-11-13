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

namespace tests\units\Glpi\Application\View\NodeVisitor;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Security\ContentSecurityPolicy\NonceContext;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CspNonceNodeVisitorTest extends GLPITestCase
{
    public static function provideTemplates(): iterable
    {
        yield 'script tag' => [
            '<script>foo();</script>',
            '<script nonce="{nonce}">foo();</script>',
        ];
        yield 'script tag with attributes' => [
            '<script type="module" src="foo.js"></script>',
            '<script nonce="{nonce}" type="module" src="foo.js"></script>',
        ];
        yield 'uppercase script tag' => [
            '<SCRIPT>foo();</SCRIPT>',
            '<SCRIPT nonce="{nonce}">foo();</SCRIPT>',
        ];
        yield 'multiple script tags' => [
            '<div><script>foo();</script>{{ 1 + 1 }}<script>bar();</script></div>',
            '<div><script nonce="{nonce}">foo();</script>2<script nonce="{nonce}">bar();</script></div>',
        ];
        yield 'script tag in a set block' => [
            '{% set js %}<script>foo();</script>{% endset %}{{ js }}',
            '<script nonce="{nonce}">foo();</script>',
        ];
        yield 'existing nonce is kept' => [
            '<script nonce="manual">foo();</script>',
            '<script nonce="manual">foo();</script>',
        ];
        yield 'other tags are ignored' => [
            '<scripts></scripts><noscript></noscript>',
            '<scripts></scripts><noscript></noscript>',
        ];
    }

    #[DataProvider('provideTemplates')]
    public function testNonceIsAddedToScriptTags(string $template, string $expected): void
    {
        $nonce = NonceContext::getServiceInstance()->getNonce();
        $expected = \str_replace('{nonce}', \htmlescape($nonce), $expected);

        $html = TemplateRenderer::getInstance()->renderFromStringTemplate($template);
        self::assertSame($expected, $html);
    }
}
