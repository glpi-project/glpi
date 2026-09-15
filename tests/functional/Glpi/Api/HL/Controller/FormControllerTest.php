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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Ramsey\Uuid\Rfc4122\UuidV4;

class FormControllerTest extends HLAPITestCase
{
    public function testCRUDForm()
    {
        $this->api->autoTestCRUD('/Form');
    }

    private function createSuperAdminOnlyForm(): int
    {
        global $DB;

        $DB->insert(Form::getTable(), [
            'uuid' => UuidV4::uuid4()->toString(),
            'name' => 'Super-admin only',
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'render_layout' => 'step_by_step',
            'submit_button_visibility_strategy' => 'always_visible',
            'submit_button_conditions' => '[]',
        ]);
        $form_id = $DB->insertId();
        $section_uuid = UuidV4::uuid4()->toString();
        $DB->insert(Section::getTable(), [
            'uuid' => $section_uuid,
            'forms_forms_id' => $form_id,
            'name' => 'Section 1',
            'conditions' => '[]',
        ]);
        $section_id = $DB->insertId();
        $DB->insert(Question::getTable(), [
            'uuid' => UuidV4::uuid4()->toString(),
            'forms_sections_id' => $section_id,
            'forms_sections_uuid' => $section_uuid,
            'name' => 'Question 1',
            'type' => QuestionTypeShortText::class,
            'visibility_strategy' => 'always_visible',
            'conditions' => '[]',
            'validation_strategy' => 'no_validation',
            'validation_conditions' => '[]',
        ]);
        $DB->insert(FormAccessControl::getTable(), [
            'forms_forms_id' => $form_id,
            'strategy' => AllowList::class,
            'config' => '{"user_ids":[],"group_ids":[],"profile_ids":[4]}',
            'is_active' => 1,
        ]);

        return $form_id;
    }

    private function createEveryoneForm(): int
    {
        global $DB;

        $DB->insert(Form::getTable(), [
            'uuid' => UuidV4::uuid4()->toString(),
            'name' => 'Everyone can view',
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'render_layout' => 'step_by_step',
            'submit_button_visibility_strategy' => 'always_visible',
            'submit_button_conditions' => '[]',
        ]);
        $form_id = $DB->insertId();
        $section_uuid = UuidV4::uuid4()->toString();
        $DB->insert(Section::getTable(), [
            'uuid' => $section_uuid,
            'forms_forms_id' => $form_id,
            'name' => 'Section 1',
            'conditions' => '[]',
        ]);
        $section_id = $DB->insertId();
        $DB->insert(Question::getTable(), [
            'uuid' => UuidV4::uuid4()->toString(),
            'forms_sections_id' => $section_id,
            'forms_sections_uuid' => $section_uuid,
            'name' => 'Question 1',
            'type' => QuestionTypeShortText::class,
            'visibility_strategy' => 'always_visible',
            'conditions' => '[]',
            'validation_strategy' => 'no_validation',
            'validation_conditions' => '[]',
        ]);
        $DB->insert(FormAccessControl::getTable(), [
            'forms_forms_id' => $form_id,
            'strategy' => AllowList::class,
            'config' => '{"user_ids":["all"],"group_ids":[],"profile_ids":[]}',
            'is_active' => 1,
        ]);

        return $form_id;
    }

    public function testCRUDFormNoRights()
    {
        $super_admin_form_id = $this->createSuperAdminOnlyForm();
        $everyone_form_id = $this->createEveryoneForm();

        $this->login('post-only', 'postonly');

        $this->api->call(new Request('GET', '/Form'), function ($call) use ($everyone_form_id, $super_admin_form_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($everyone_form_id, $super_admin_form_id) {
                    $form_ids = array_column($content, 'id');
                    $this->assertContains($everyone_form_id, $form_ids);
                    $this->assertNotContains($super_admin_form_id, $form_ids);
                });
        });

        $this->api->call(new Request('GET', "/Form/{$super_admin_form_id}"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('GET', "/Form/{$everyone_form_id}"), function ($call) {
            $call->response->isOK();
        });

        // Cannot update or delete any form
        $this->api->call(new Request('PATCH', "/Form/{$super_admin_form_id}"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('PATCH', "/Form/{$everyone_form_id}"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Form/{$super_admin_form_id}"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Form/{$everyone_form_id}"), function ($call) {
            $call->response->isAccessDenied();
        });

        // Cannot create new form
        $create_request = new Request('POST', '/Form');
        $create_request->setParameter('uuid', UuidV4::uuid4()->toString());
        $create_request->setParameter('name', 'Forbidden');
        $create_request->setParameter('is_active', 1);
        $create_request->setParameter('entities_id', $this->getTestRootEntity(true));
        $create_request->setParameter('render_layout', 'step_by_step');
        $create_request->setParameter('submit_button_visibility_strategy', 'always_visible');
        $create_request->setParameter('submit_button_conditions', '[]');
        $this->api->call($create_request, function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public function testCRUDFormSection()
    {
        $everyone_form_id = $this->createEveryoneForm();
        $this->api->autoTestCRUD(
            endpoint: "/Form/{$everyone_form_id}/Section",
            create_params: ['name' => 'New section'],
            update_params: ['name' => 'Updated section name']
        );
    }

    public function testCRUDFormSectionNoRights()
    {
        $super_admin_form_id = $this->createSuperAdminOnlyForm();
        $everyone_form_id = $this->createEveryoneForm();

        $this->login('post-only', 'postonly');

        $this->api->call(new Request('GET', "/Form/{$super_admin_form_id}/Section"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('GET', "/Form/{$everyone_form_id}/Section"), function ($call) {
            $call->response->isOK();
        });

        $super_admin_form_section = array_column(getAllDataFromTable(Section::getTable(), ['forms_forms_id' => $super_admin_form_id]), 'id');
        $everyone_form_section = array_column(getAllDataFromTable(Section::getTable(), ['forms_forms_id' => $everyone_form_id]), 'id');
        $super_admin_form_section = reset($super_admin_form_section);
        $everyone_form_section = reset($everyone_form_section);

        // Cannot create, update or delete any section in any form
        $this->api->call(new Request('POST', "/Form/{$super_admin_form_id}/Section"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('POST', "/Form/{$everyone_form_id}/Section"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('PATCH', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('PATCH', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('DELETE', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}"), function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public function testCRUDFormQuestion()
    {
        $everyone_form_id = $this->createEveryoneForm();
        $everyone_form_section = array_column(getAllDataFromTable(Section::getTable(), ['forms_forms_id' => $everyone_form_id]), 'id');
        $everyone_form_section = reset($everyone_form_section);

        $this->api->autoTestCRUD(
            endpoint: "/Form/{$everyone_form_id}/Section/{$everyone_form_section}/Question",
            create_params: [
                'name' => 'New question',
                'type' => QuestionTypeShortText::class,
                'visibility_strategy' => 'always_visible',
                'visibility_conditions' => '[]',
                'validation_strategy' => 'no_validation',
                'validation_conditions' => '[]',
            ],
            update_params: ['name' => 'Updated question name']
        );
    }

    public function testCRUDFormQuestionNoRights()
    {
        $super_admin_form_id = $this->createSuperAdminOnlyForm();
        $everyone_form_id = $this->createEveryoneForm();

        $super_admin_form_section = array_column(getAllDataFromTable(Section::getTable(), ['forms_forms_id' => $super_admin_form_id]), 'id');
        $everyone_form_section = array_column(getAllDataFromTable(Section::getTable(), ['forms_forms_id' => $everyone_form_id]), 'id');
        $super_admin_form_section = reset($super_admin_form_section);
        $everyone_form_section = reset($everyone_form_section);

        $this->login('post-only', 'postonly');

        $this->api->call(new Request('GET', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}/Question"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('GET', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}/Question"), function ($call) {
            $call->response->isOK();
        });

        $super_admin_form_question = array_column(getAllDataFromTable(Question::getTable(), ['forms_sections_id' => $super_admin_form_section]), 'id');
        $everyone_form_question = array_column(getAllDataFromTable(Question::getTable(), ['forms_sections_id' => $everyone_form_section]), 'id');
        $super_admin_form_question = reset($super_admin_form_question);
        $everyone_form_question = reset($everyone_form_question);

        // Cannot create, update or delete any question in any form
        $this->api->call(new Request('POST', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}/Question"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('POST', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}/Question"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('PATCH', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}/Question/{$super_admin_form_question}"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('PATCH', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}/Question/{$everyone_form_question}"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Form/{$super_admin_form_id}/Section/{$super_admin_form_section}/Question/{$super_admin_form_question}"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('DELETE', "/Form/{$everyone_form_id}/Section/{$everyone_form_section}/Question/{$everyone_form_question}"), function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public function testGraphQL()
    {
        $graphql = <<<GRAPHQL
            query {
                Form {
                    id name form_description illustration is_recursive is_active is_deleted is_pinned is_draft
				    category { id name }
				    entity { id name }
				    sections { id name questions { id name type } }
                }
            }
GRAPHQL;
        $request = new Request('POST', '/graphql', [], $graphql);
        $everyone_form_id = $this->createEveryoneForm();
        $super_admin_form_id = $this->createSuperAdminOnlyForm();

        $this->login();
        $this->api->call($request, function ($call) use ($everyone_form_id, $super_admin_form_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($everyone_form_id, $super_admin_form_id) {
                    $this->assertArrayHasKey('data', $content);
                    $this->assertArrayHasKey('Form', $content['data']);

                    $forms_ids = array_column($content['data']['Form'], 'id');
                    $this->assertContains($everyone_form_id, $forms_ids);
                    $this->assertContains($super_admin_form_id, $forms_ids);

                    // Ensure each form has at least one section and each section has at least one question to identify join issues
                    foreach ($content['data']['Form'] as $form) {
                        $this->assertNotEmpty($form['sections']);
                        foreach ($form['sections'] as $section) {
                            $this->assertNotEmpty($section['questions']);
                        }
                    }
                });
        });
    }

    public function testGraphQLNoRight()
    {
        $graphql = <<<GRAPHQL
            query {
                Form {
                    id name form_description illustration is_recursive is_active is_deleted is_pinned is_draft
				    category { id name }
				    entity { id name }
				    sections { id name questions { id name type } }
                }
            }
GRAPHQL;
        $request = new Request('POST', '/graphql', [], $graphql);
        $everyone_form_id = $this->createEveryoneForm();
        $super_admin_form_id = $this->createSuperAdminOnlyForm();

        $this->login('post-only', 'postonly');
        $this->api->call($request, function ($call) use ($everyone_form_id, $super_admin_form_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($everyone_form_id, $super_admin_form_id) {
                    $this->assertArrayHasKey('data', $content);
                    $this->assertArrayHasKey('Form', $content['data']);

                    $this->assertContains($everyone_form_id, array_column($content['data']['Form'], 'id'));
                    $this->assertNotContains($super_admin_form_id, array_column($content['data']['Form'], 'id'));

                    // Ensure each form has at least one section and each section has at least one question to identify join issues
                    foreach ($content['data']['Form'] as $form) {
                        $this->assertNotEmpty($form['sections']);
                        foreach ($form['sections'] as $section) {
                            $this->assertNotEmpty($section['questions']);
                        }
                    }
                });
        });
    }

    public function testNoDirectGraphQLQueryOnChildSchemas()
    {
        $child_schemas = ['FormSection', 'FormQuestion', 'FormAccessControl'];
        $this->login();
        foreach ($child_schemas as $child) {
            $graphql = "query { $child { id } }";
            $request = new Request('POST', '/graphql', [], $graphql);
            $this->api->call($request, function ($call) use ($child) {
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($child) {
                        $this->assertArrayHasKey('errors', $content);
                        $this->assertStringContainsString("Cannot query field \"$child\" on type \"Query\"", $content['errors'][0]['message']);
                    });
            });
        }
    }
}
