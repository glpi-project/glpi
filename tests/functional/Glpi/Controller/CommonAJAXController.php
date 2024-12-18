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

namespace tests\units\Glpi\Controller;

use DbTestCase;
use Glpi\Form\Form;
use Glpi\Http\Response;

class CommonAjaxController extends DbTestCase
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
            'input' => [],
            'expected_response' => new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Invalid id"],
                    ]
                ])
            )
        ];
        yield [
            'input' => ['id' => $invalid_id],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Forbidden itemtype"],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id' => $invalid_id,
                'itemtype' => 'Computer'
            ],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Forbidden itemtype"],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType()
            ],
            'expected_response' => new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Invalid action"],
                    ]
                ])
            )
        ];

        // Second tests set: update request
        yield [
            'user'  => 'normal',  // Switch to another user that can't update the form
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (first update)',
            ],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["You don't have permission to perform this action."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (second update)',
            ],
            'expected_response' => new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Item not found."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'update',
                'name'     => 'Form 1 name (third update)',
            ],
            'expected_response' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'friendlyname' => "Form 1 name (third update)",
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => [],
                    ]
                ])
            )
        ];
        // We can't test the "Failed to update item" response because it doesn't
        // seem to be a way to send an invalid update request that isn't already
        // caught by the previous errors checks
        // Could be done if we had a whitelisted object with an unicity check

        // Third tests set: delete request
        yield [
            'user'  => 'normal',  // Switch to another user that can't delete the form
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["You don't have permission to perform this action."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ],
            'expected_response' => new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Item not found."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'delete',
            ],
            'expected_response' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'is_deleted' => true,
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => [],
                    ]
                ])
            )
        ];
        // We can't test the "Failed to delete item" response it because doesn't
        // seem to be a way to send an invalid delete request that isn't already
        // caught by the previous errors checks

        // Fourth tests set: restore request
        yield [
            'user'  => 'normal',  // Switch to another user that can't restore the form
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["You don't have permission to perform this action."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ],
            'expected_response' => new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Item not found."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'restore',
            ],
            'expected_response' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'is_deleted' => false,
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => [],
                    ]
                ])
            )
        ];
        // We can't test the "Failed to restore item" response because it doesn't
        // seem to be a way to send an invalid delete request that isn't already
        // caught by the previous errors checks

        // Fifth tests set: purge request
        yield [
            'user'  => 'normal',  // Switch to another user that can't purge the form
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ],
            'expected_response' => new Response(
                403,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["You don't have permission to perform this action."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $invalid_id,
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ],
            'expected_response' => new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'messages' => [
                        'info'    => [],
                        'warning' => [],
                        'error'   => ["Item not found."],
                    ]
                ])
            )
        ];
        yield [
            'input' => [
                'id'       => $form_1->getID(),
                'itemtype' => $form_1->getType(),
                '_action'  => 'purge',
            ],
            'expected_response' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'redirect' => "/glpi/front/form/form.php"
                ])
            )
        ];
        // We can't test the "Failed to purge item" response because it doesn't
        // seem to be a way to send an invalid purge request that isn't already
        // caught by the previous errors checks
    }

    /**
     * Test the handleRequest method
     *
     * @dataProvider testHandleRequestProvider
     *
     * @param array    $input             Request content
     * @param Response $expected_response Expected response object
     * @param string   $user              Logged in user:
     *                                     - TU_USER (default)
     *                                     - "normal"
     *
     * @return void
     */
    public function testHandleRequest(
        array $input,
        Response $expected_response,
        string $user = TU_USER,
    ): void {
        if ($user === TU_USER) {
            $this->login();
        } elseif ($user === "normal") {
            $this->login("normal", "normal");
        }

        $controller = new \Glpi\Controller\CommonAjaxController();
        $response = $controller->handleRequest($input);

        // Validate return code
        $this
            ->integer($response->getStatusCode())
            ->isEqualTo($expected_response->getStatusCode())
        ;

        // Validate body
        $this
            ->array($response->getHeaders())
            ->isEqualTo($expected_response->getHeaders())
        ;

        // Validate headers
        $this
            ->string((string) $response->getBody())
            ->isEqualTo((string) $expected_response->getBody())
        ;
    }
}
