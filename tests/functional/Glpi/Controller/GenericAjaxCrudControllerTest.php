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

namespace tests\units\Glpi\Controller;

use DbTestCase;
use Glpi\Controller\GenericAjaxCrudController;
use Glpi\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class GenericAjaxCrudControllerTest extends DbTestCase
{
    /**
     * Data provider for the testHandleRequest method
     *
     * @return iterable
     */
    protected function testHandleRequestProvider(): iterable
    {
        $invalid_id = 9990;

        // Create an existing test subject
        $entity = $this->getTestRootEntity();
        $form_1 = $this->createItem(Form::class, [
            'name'        => 'Form 1 name',
            'header'      => 'Form 1 header',
            'entities_id' => $entity->getID(),
            'is_recursive' => 1,
            'is_active'    => 1,
        ]);

        // First tests set: invalid inputs (general)
        yield [
            'content' => json_encode([]),
            'expected_status' => 400,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Invalid id"],
                ],
            ],
        ];
        yield [
            'content' => json_encode(['id' => $invalid_id]),
            'expected_status' => 400,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Invalid itemtype"],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id' => $invalid_id,
                'itemtype' => 'Computer',
            ]),
            'expected_status' => 403,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Forbidden itemtype"],
                ],
            ],
        ];
        yield [
            'content'   => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
            ]),
            'expected_status' => 400,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Invalid action"],
                ],
            ],
        ];

        // Second tests set: update request
        yield [
            'user'  => 'normal',  // Switch to another user that can't update the form
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (first update)',
            ]),
            'expected_status' => 403,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["You don&#039;t have permission to perform this action."],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (second update)',
            ]),
            'expected_status' => 404,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Item not found"],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (third update)',
            ]),
            'expected_status' => 200,
            'expected_body'   => [
                'friendlyname' => "Form 1 name (third update)",
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => [],
                ],
            ],
        ];
        // We can't test the "Failed to update item" response because it doesn't
        // seem to be a way to send an invalid update request that isn't already
        // caught by the previous errors checks
        // Could be done if we had a whitelisted object with an unicity check

        // Third tests set: delete request
        yield [
            'user'  => 'normal',  // Switch to another user that can't delete the form
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ]),
            'expected_status' => 403,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["You don&#039;t have permission to perform this action."],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ]),
            'expected_status' => 404,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Item not found"],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ]),
            'expected_status' => 200,
            'expected_body'   => [
                'is_deleted' => true,
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => [],
                ],
            ],
        ];
        // We can't test the "Failed to delete item" response it because doesn't
        // seem to be a way to send an invalid delete request that isn't already
        // caught by the previous errors checks

        // Fourth tests set: restore request
        yield [
            'user'  => 'normal',  // Switch to another user that can't restore the form
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ]),
            'expected_status' => 403,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["You don&#039;t have permission to perform this action."],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ]),
            'expected_status' => 404,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Item not found"],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ]),
            'expected_status' => 200,
            'expected_body'   => [
                'is_deleted' => false,
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => [],
                ],
            ],
        ];
        // We can't test the "Failed to restore item" response because it doesn't
        // seem to be a way to send an invalid delete request that isn't already
        // caught by the previous errors checks

        // Fifth tests set: purge request
        yield [
            'user'  => 'normal',  // Switch to another user that can't purge the form
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ]),
            'expected_status' => 403,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["You don&#039;t have permission to perform this action."],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ]),
            'expected_status' => 404,
            'expected_body'   => [
                'messages' => [
                    'info'    => [],
                    'warning' => [],
                    'error'   => ["Item not found"],
                ],
            ],
        ];
        yield [
            'content' => json_encode([
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ]),
            'expected_status' => 200,
            'expected_body'   => [
                'redirect' => "/front/form/form.php",
            ],
        ];
        // We can't test the "Failed to purge item" response because it doesn't
        // seem to be a way to send an invalid purge request that isn't already
        // caught by the previous errors checks
    }

    /**
     * Test the handleRequest method
     *
     * @return void
     */
    public function testHandleRequest(): void
    {
        foreach ($this->testHandleRequestProvider() as $row) {
            $content = $row['content'];
            $expected_status = $row['expected_status'];
            $expected_body   = $row['expected_body'];
            $user = $row['user'] ?? TU_USER;

            if ($user === TU_USER) {
                $this->login();
            } elseif ($user === "normal") {
                $this->login("normal", "normal");
            }

            $controller = new GenericAjaxCrudController();
            $request = new Request(content: $content);
            $request->headers->set('Content-Type', 'application/json');
            $response = $controller($request);

            // Validate return code
            $this->assertEquals(
                $expected_status,
                $response->getStatusCode()
            );

            // Validate headers
            $this->assertEquals(
                'application/json',
                $response->headers->get('Content-Type')
            );

            // Validate body
            $this->assertEquals(
                $expected_body,
                json_decode($response->getContent(), true)
            );
        }
    }
}
