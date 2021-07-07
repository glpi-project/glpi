<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\ContentTemplates;

use Glpi\ContentTemplates\TemplateManager as CoreTemplateManager;
use GLPITestCase;
use Twig\Sandbox\SecurityPolicy;

class TemplateManager extends GLPITestCase
{
   protected function testTemplatesProvider(): array {
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
            'error'     => 'Invalid twig template: Tag "set" is not allowed in "template" at line 1.',
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
            'sanitized' => true
         ],
         [
            'content'   => '&#60;h1&#62;Test sanitized template 2&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
            'params'    => ['content' => 'Item content should not be unsanitized: &#60;--'],
            'expected'  => '<h1>Test sanitized template 2</h1><hr />Item content should not be unsanitized: &#60;--',
            'error'     => null,
            'sanitized' => true
         ],
         [
            'content'   => '&#60;h1&#62;Test misused sanitized input&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
            'params'    => ['content' => '<p>Item content</p>'],
            'expected'  => '&#60;h1&#62;Test misused sanitized input&#60;/h1&#62;&#60;hr /&#62;<p>Item content</p>',
            'error'     => null,
            'sanitized' => false
         ],
         [
            'content'   => "&#60;p&#62;Test sanitized template {% if count &#62; 5 %}&#60;b&#62;++&#60;/b&#62;{% endif %}&#60;/p&#62;",
            'params'    => ['count' => 25],
            'expected'  => "<p>Test sanitized template <b>++</b></p>",
            'error'     => null,
            'sanitized' => true
         ],
         [
            'content'   => "Test invalid on missing unsanitizing {% if count &#62; 5 %}test{% endif %}",
            'params'    => [],
            'expected'  => "",
            'error'     => 'Invalid twig template syntax',
         ],
         [
            'content'   => '&#60;h1 onclick="alert(1);"&#62;Test safe HTML2&#60;/h1&#62;&#60;hr /&#62;{{content|raw}}',
            'params'    => ['content' => 'Fill this form:<iframe src="phishing.php"></iframe>'],
            'expected'  => '<h1>Test safe HTML2</h1><hr />Fill this form:',
            'error'     => null,
            'sanitized' => true
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
      ?string $error = null,
      bool $sanitized = false
   ): void {
      $html = CoreTemplateManager::render($content, $params, $sanitized);
      $this->string($html)->isEqualTo($expected);

      // Handle error if neeced
      if ($error !== null) {
         $errors = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR];
         unset($_SESSION['MESSAGE_AFTER_REDIRECT']);
         $this->array($errors)->hasSize(1);
         $this->string($errors[0])->isEqualTo($error);
      }
   }

   /**
    * @dataProvider testTemplatesProvider
    */
   public function testValidate(
      string $content,
      array $params,
      string $expected,
      ?string $error = null,
      bool $sanitized = false
   ): void {
      $is_valid = CoreTemplateManager::validate($content, 'field', $sanitized);
      $this->boolean($is_valid)->isEqualTo(empty($error));

      // Handle error if neeced
      if ($error !== null) {
         $errors = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR];
         unset($_SESSION['MESSAGE_AFTER_REDIRECT']);
         $this->array($errors)->hasSize(1);
         $this->string($errors[0])->contains($error);
      }
   }

   public function testGetSecurityPolicy(): void {
      // Not much to test here, maybe keepk this for code coverage ?
      $this->object(CoreTemplateManager::getSecurityPolicy())->isInstanceOf(SecurityPolicy::class);
   }
}
