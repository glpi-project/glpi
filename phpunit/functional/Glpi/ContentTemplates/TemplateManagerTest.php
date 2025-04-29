<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Change;
use CommonITILActor;
use DbTestCase;
use Glpi\ContentTemplates\TemplateManager;
use Glpi\Toolbox\Sanitizer;
use Problem;
use Ticket;

/**
 * Functionnals test to make sure content templates work as expected
 */
class TemplateManagerTest extends DbTestCase
{
    public static function testTemplatesProvider(): array
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
                'error'     => 'Tag "set" is not allowed in "template" at line 1.',
            ],
            [
                'content'   => "Test syntax error {{",
                'params'    => [],
                'expected'  => "",
                'error'     => 'Unexpected token "end of template" of value "" in "template" at line 1.',
                'validation_error' => 'Invalid twig template syntax',
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
        $manager = new TemplateManager();

        if ($error !== null) {
            $this->expectExceptionMessage($error);
        }

        $html = $manager->render($content, $params);
        $this->assertEquals($expected, $html);
    }

    /**
     * @dataProvider testTemplatesProvider
     */
    public function testValidate(
        string $content,
        array $params,
        string $expected,
        ?string $error = null,
        ?string $validation_error = null
    ): void {
        $manager = new TemplateManager();
        $err_msg = null;
        $is_valid = $manager->validate($content, $err_msg);
        $this->assertEquals(empty($error), $is_valid);

        // Handle error if needed
        if ($error !== null) {
            $this->assertStringContainsString($validation_error ?? $error, $err_msg);
        }
    }

    public function testGetSecurityPolicy(): void
    {
        // Not much to test here, maybe keepk this for code coverage ?
        $manager = new TemplateManager();
        $this->assertInstanceOf(\Twig\Sandbox\SecurityPolicy::class, $manager->getSecurityPolicy());
    }

    /**
     * Get all possible CommonITILObject classes.
     *
     * @return array
     */
    public static function commonITILObjectclassesProvider(): array
    {
        return [
            [Ticket::class],
            [Change::class],
            [Problem::class],
        ];
    }

    /**
     * Verify that actors are rendered correctly on creation (commonitilobject)
     *
     * @dataProvider commonITILObjectclassesProvider
     *
     * @return void
     */
    public function testActorsOnCreate(string $common_itil_object_class): void
    {
        $this->login();
        $user = getItemByTypeName('User', TU_USER);

        /** @var \CommonITILObject $common_itil_object */
        $common_itil_object = new $common_itil_object_class();
        $this->assertTrue($common_itil_object instanceof \CommonITILObject);

        // Test entity
        $entities_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create task template
        $template_key = strtolower($common_itil_object_class);
        $task_template = $this->createItem("TaskTemplate", [
            'entities_id' => $entities_id,
            'name'        => 'task template',
            'content'     => "{{ $template_key.id }} {% for user in $template_key.requesters.users %}{{ user.login }}{% endfor %}",
        ]);

        // Create commonitil with specified task template
        /** @var \CommonITILObject $common_itil_object */
        $common_itil_object = $this->createItem($common_itil_object::getType(), [
            'entities_id'       => $entities_id,
            'name'              => 'test commonitil',
            'content'           => 'test commonitil',
            '_tasktemplates_id' => [$task_template->getID()],
            '_actors'            => [
                'requester' => [
                    ['itemtype' => 'User', 'items_id' => $user->getID()],
                ],
            ],
        ]);

        // Validate requester
        $actors = $common_itil_object->getITILActors();
        $this->assertEquals(
            [CommonITILActor::REQUESTER],
            $actors[$user->getID()]
        );

        // Get task
        $task_type = $common_itil_object->getTaskClass();
        $tasks = (new $task_type())->find([
            $common_itil_object->getForeignKeyField() => $common_itil_object->getId(),
        ]);
        $this->assertCount(1, $tasks);
        $task = array_pop($tasks);
        $this->assertEquals(
            "<p>{$common_itil_object->getId()} {$user->fields['name']}</p>",
            Sanitizer::unsanitize($task['content'])
        );
    }
}
