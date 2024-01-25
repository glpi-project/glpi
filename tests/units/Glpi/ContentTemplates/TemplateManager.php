<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units\Glpi\ContentTemplates;

use GLPITestCase;
use Twig\Sandbox\SecurityPolicy;

class TemplateManager extends GLPITestCase
{
    protected function testTemplatesProvider(): array
    {
        return [
            [
                'content'   => "{{ test_var }}",
                'params'    => ['test_var' => 'test_value'],
                'expected'  => "<p>test_value</p>",
            ],
            [
                'content'   => "Test var: {{ test_var }}",
                'params'    => ['test_var' => 'test_value'],
                'expected'  => "<p>Test var: test_value</p>",
            ],
            [
                'content'   => "Test condition: {% if test_condition == true %}TRUE{% else %}FALSE{% endif %}",
                'params'    => ['test_condition' => 'true'],
                'expected'  => "<p>Test condition: TRUE</p>",
            ],
            [
                'content'   => "Test condition: {% if test_condition == true %}TRUE{% else %}FALSE{% endif %}",
                'params'    => ['test_condition' => 'false'],
                'expected'  => "<p>Test condition: TRUE</p>",
            ],
            [
                'content'   => "Test for: {% for item in items %}{{ item }} {% else %}no items{% endfor %}",
                'params'    => ['items' => ['a', 'b', 'c', 'd', 'e']],
                'expected'  => "<p>Test for: a b c d e </p>",
            ],
            [
                'content'   => "Test for: {% for item in items %}{{ item }} {% else %}no items{% endfor %}",
                'params'    => ['items' => []],
                'expected'  => "<p>Test for: no items</p>",
            ],
            [
                'content'   => "Test forbidden tag: {% set var = 'value' %}",
                'params'    => [],
                'expected'  => "",
                'error'     => 'Invalid twig template (Tag "set" is not allowed in "template" at line 1.)',
            ],
            [
                'content'   => "Test syntax error {{",
                'params'    => [],
                'expected'  => "",
                'error'     => 'Invalid twig template syntax',
            ],
            [
                'content'   => '&#60;h1&#62;Test sanitized template&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
                'params'    => ['content' => '<p>Item content</p>'],
                'expected'  => '<h1>Test sanitized template</h1><hr /><p>Item content</p>',
                'error'     => null,
            ],
            [
                'content'   => '&#60;h1&#62;Test sanitized template 2&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
                'params'    => ['content' => 'Item content should not be unsanitized: &#60;--'],
                'expected'  => '<h1>Test sanitized template 2</h1><hr />Item content should not be unsanitized: &#60;--',
                'error'     => null,
            ],
            [
                'content'   => "&#60;p&#62;Test sanitized template {% if count &#62; 5 %}&#60;b&#62;++&#60;/b&#62;{% endif %}&#60;/p&#62;",
                'params'    => ['count' => 25],
                'expected'  => "<p>Test sanitized template <b>++</b></p>",
                'error'     => null,
            ],
            [
                'content'   => '&#60;h1 onclick="alert(1);"&#62;Test safe HTML2&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
                'params'    => ['content' => 'Fill this form:<iframe src="phishing.php"></iframe>'],
                'expected'  => '<h1>Test safe HTML2</h1><hr />Fill this form:',
                'error'     => null,
            ],
        ];
    }

    /**
     * @dataProvider testTemplatesProvider
     */
    public function testRender(
        string $content,
        array $params,
        string $expected,
        ?string $error = null
    ): void {
        $manager = $this->newTestedInstance();

        $html = null;

        if ($error !== null) {
            $this->exception(
                function () use ($manager, $content, $params, &$html) {
                    $html = $manager->render($content, $params);
                }
            );
            return;
        } else {
            $html = $manager->render($content, $params);
        }

        $this->string($html)->isEqualTo($expected);
    }

    /**
     * @dataProvider testTemplatesProvider
     */
    public function testValidate(
        string $content,
        array $params,
        string $expected,
        ?string $error = null
    ): void {
        $manager = $this->newTestedInstance();
        $err_msg = null;
        $is_valid = $manager->validate($content, $err_msg);
        $this->boolean($is_valid)->isEqualTo(empty($error));

       // Handle error if neeced
        if ($error !== null) {
            $this->string($err_msg)->contains($error);
        }
    }

    public function testGetSecurityPolicy(): void
    {
       // Not much to test here, maybe keepk this for code coverage ?
        $manager = $this->newTestedInstance();
        $this->object($manager->getSecurityPolicy())->isInstanceOf(SecurityPolicy::class);
    }
}
