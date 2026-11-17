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

namespace tests\functional\Tools\CspNonce;

use FriendsOfTwig\Twigcs\Lexer;
use FriendsOfTwig\Twigcs\TwigPort\Source;
use FriendsOfTwig\Twigcs\Validator\Violation;
use Glpi\Tests\GLPITestCase;
use Glpi\Tools\CspNonce\MissingNonceFinder;
use Glpi\Tools\CspNonce\TwigcsRule;
use PHPUnit\Framework\Attributes\DataProvider;

class MissingNonceFinderTest extends GLPITestCase
{
    public static function invalidProvider(): iterable
    {
        yield 'bare script' => ['<script>alert(1);</script>', 1, 0, '`<script>` must carry the CSP nonce'];
        yield 'script with attributes' => ['<p></p><script type="module" defer>x();</script>', 1, 7, '`<script type="module" defer>` must carry'];
        yield 'external script' => ['<script src="{{ js_path(file) }}"></script>', 1, 0, 'must carry the CSP nonce'];
        yield 'twig expression containing a ">"' => [
            '<script type="{{ a > b ? \'module\' : \'text/javascript\' }}"></script>',
            1,
            0,
            'must carry the CSP nonce',
        ];
        yield 'multi line tag' => ["<div>\n    <script\n        type=\"module\"\n    >\n</script>", 2, 4, '`<script type="module" >` must carry'];
        yield 'foreign nonce' => ['<script nonce="{{ some_nonce }}"></script>', 1, 0, 'must be `{{ csp_nonce() }}`'];
        yield 'nonce in another attribute' => ['<script data-nonce="{{ csp_nonce() }}"></script>', 1, 0, 'must carry the CSP nonce'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidScriptsAreReported(string $source, int $line, int $column, string $message): void
    {
        $missing = MissingNonceFinder::find($source);

        $this->assertCount(1, $missing);
        $this->assertSame($line, $missing[0]['line']);
        $this->assertSame($column, $missing[0]['column']);
        $this->assertStringContainsString($message, $missing[0]['message']);
    }

    public static function validProvider(): iterable
    {
        yield 'nonce' => ['<script nonce="{{ csp_nonce() }}">alert(1);</script>'];
        yield 'nonce with single quotes and whitespace control' => ["<script type='module' nonce='{{- csp_nonce() -}}'></script>"];
        yield 'nonce after other attributes' => ['<script type="module" nonce="{{ csp_nonce() }}"></script>'];
        yield 'script in a twig comment' => ["{# <script>alert(1);</script> #}\n{#\n<script>\n#}"];
        yield 'not a script tag' => ['<scripts></scripts><noscript>Enable JS</noscript>'];
    }

    #[DataProvider('validProvider')]
    public function testValidScriptsAreAccepted(string $source): void
    {
        $this->assertSame([], MissingNonceFinder::find($source));
    }

    public function testEveryInvalidScriptIsReported(): void
    {
        $missing = MissingNonceFinder::find(
            "<script>a();</script>\n<script nonce=\"{{ csp_nonce() }}\"></script>\n<script>b();</script>",
        );

        $this->assertSame([1, 3], array_column($missing, 'line'));
    }

    public function testTwigcsRule(): void
    {
        $source = new Source(
            "{% if a %}\n    <script type=\"{{ a > b ? 1 : 2 }}\">x();</script>\n{% endif %}",
            'template.html.twig',
            'template.html.twig',
        );
        $tokens = (new Lexer())->tokenize($source);

        $violations = (new TwigcsRule(Violation::SEVERITY_ERROR))->check($tokens);

        $this->assertCount(1, $violations);
        $this->assertSame('template.html.twig', $violations[0]->getFilename());
        $this->assertSame(2, $violations[0]->getLine());
        $this->assertSame(4, $violations[0]->getColumn());
        $this->assertSame(Violation::SEVERITY_ERROR, $violations[0]->getSeverity());
    }
}
