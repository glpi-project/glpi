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

namespace tests\units\Glpi\ContentTemplates\Parameters;

use Glpi\ContentTemplates\Parameters\TicketParameters;
use Glpi\Tests\AbstractParametersTest;
use Glpi\Tests\Glpi\SLMTrait;

class TicketParametersTest extends AbstractParametersTest
{
    use SLMTrait;

    public function testGetValues(): void
    {
        // Arrange
        $this->login();

        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);
        $ola_group_id = $this->createItem(\Group::class, ['name' => 'ola_assigned_group'])->fields['id'];
        // - create associatied objects
        $this->createItem('ITILCategory', [
            'name' => 'category_testGetValues',
        ]);
        $itilcategories_id = getItemByTypeName('ITILCategory', 'category_testGetValues', true);

        $this->createItem('Location', [
            'name' => 'location_testGetValues',
        ]);
        $locations_id = getItemByTypeName('Location', 'location_testGetValues', true);

        $this->createItems('SLA', [
            [
                'name'            => 'sla_tto_testGetValue',
                'entities_id'     => $test_entity_id,
                'type'            => 1,
                'number_time'     => 10,
                'definition_time' => 'minute',
            ],
            [
                'name'            => 'sla_ttr_testGetValue',
                'entities_id'     => $test_entity_id,
                'type'            => 0,
                'number_time'     => 3,
                'definition_time' => 'hour',
            ],
        ]);
        $slas_id_tto = getItemByTypeName('SLA', 'sla_tto_testGetValue', true);
        $slas_id_ttr = getItemByTypeName('SLA', 'sla_ttr_testGetValue', true);
        $slms = $this->createSLM();

        $this->createItems(\OLA::class, [
            [
                'name'            => 'ola_tto_testGetValue',
                'entities_id'     => $test_entity_id,
                'type'            => \SLM::TTO,
                'number_time'     => 15,
                'definition_time' => 'minute',
                'groups_id'       => $ola_group_id,
                'slms_id'        => $slms->getID(),
            ],
            [
                'name'            => 'ola_ttr_testGetValue',
                'entities_id'     => $test_entity_id,
                'type'            => \SLM::TTR,
                'number_time'     => 4,
                'definition_time' => 'hour',
                'groups_id'       => $ola_group_id,
                'slms_id'        => $slms->getID(),
            ],
            [
                'name'            => 'ola_ttr_testGetValue_2',
                'entities_id'     => $test_entity_id,
                'type'            => \SLM::TTR,
                'number_time'     => 6,
                'definition_time' => 'hour',
                'groups_id'       => $ola_group_id,
                'slms_id'        => $slms->getID(),
            ],
        ]);
        $olas_id_tto = getItemByTypeName('OLA', 'ola_tto_testGetValue', true);
        $olas_id_ttr = getItemByTypeName('OLA', 'ola_ttr_testGetValue', true);
        $olas_id_ttr2 = getItemByTypeName('OLA', 'ola_ttr_testGetValue_2', true);

        $requester_groups_id = getItemByTypeName('Group', '_test_group_1', true);
        $observer_users_id1  = getItemByTypeName('User', 'normal', true);
        $observer_users_id2  = getItemByTypeName('User', 'post-only', true);
        $assigned_groups_id  = getItemByTypeName('Group', '_test_group_2', true);
        $suppliers_id        = getItemByTypeName('Supplier', '_suplier01_name', true);

        $now = date('Y-m-d H:i:s');
        $created_ticket = $this->createItem('Ticket', [
            'name'                  => 'ticket_testGetValues',
            'content'               => '<p>ticket_testGetValues content</p>',
            'entities_id'           => $test_entity_id,
            'itilcategories_id'     => $itilcategories_id,
            'locations_id'          => $locations_id,
            'slas_id_tto'           => $slas_id_tto,
            'slas_id_ttr'           => $slas_id_ttr,
            // add olas
            '_olas_id' => [$olas_id_tto, $olas_id_ttr, $olas_id_ttr2],
            '_la_update'            => true,

            'date'                  => $now,
            '_groups_id_requester'  => [$requester_groups_id],
            '_users_id_observer'    => [$observer_users_id1, $observer_users_id2],
            '_groups_id_assign'     => [$assigned_groups_id],
            '_suppliers_id_assign'  => [$suppliers_id],
        ]);
        $tickets_id = $created_ticket->getID();

        // add knowbaseItem for ticket
        $this->createItem(\KnowbaseItem_Item::class, [
            'knowbaseitems_id' => getItemByTypeName(\KnowbaseItem::class, '_knowbaseitem01', true),
            'itemtype'         => 'Ticket',
            'items_id'         => $tickets_id,
        ]);

        // Act
        $parameters = new TicketParameters();
        $values = $parameters->getValues(getItemByTypeName(\Ticket::class, 'ticket_testGetValues'));
        $this->assertEquals(
            [
                'id'        => $tickets_id,
                'ref'       => "#$tickets_id",
                'link'      => "<a href=\"/front/ticket.form.php?id=$tickets_id\" data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"ticket_testGetValues\">ticket_testGetValues</a>",
                'name'      => 'ticket_testGetValues',
                'content'   => '<p>ticket_testGetValues content</p>',
                'date'      => $now,
                'solvedate' => null,
                'closedate' => null,
                'status'    => 'Processing (assigned)',
                'urgency'   => 'Medium',
                'impact'    => 'Medium',
                'priority'  => 'Medium',
                'entity'    => [
                    'id'           => $test_entity_id,
                    'name'         => '_test_child_2',
                    'completename' => 'Root entity > _test_root_entity > _test_child_2',
                ],
                'itilcategory' => [
                    'id'           => $itilcategories_id,
                    'name'         => 'category_testGetValues',
                    'completename' => 'category_testGetValues',
                ],
                'requesters' => [
                    'users'  => [],
                    'groups' => [
                        [
                            'id'           => $requester_groups_id,
                            'name'         => '_test_group_1',
                            'completename' => '_test_group_1',
                        ],
                    ],
                ],
                'observers' => [
                    'users'  => [
                        [
                            'id'         => $observer_users_id1,
                            'login'      => 'normal',
                            'fullname'   => 'normal',
                            'email'      => '',
                            'phone'      => null,
                            'phone2'     => null,
                            'mobile'     => null,
                            'firstname'  => null,
                            'realname'   => null,
                            'registration_number' => null,
                            'used_items' => [],
                        ],
                        [
                            'id'         => $observer_users_id2,
                            'login'      => 'post-only',
                            'fullname'   => 'post-only',
                            'email'      => '',
                            'phone'      => null,
                            'phone2'     => null,
                            'mobile'     => null,
                            'firstname'  => null,
                            'realname'   => null,
                            'registration_number' => null,
                            'used_items' => [],
                        ],
                    ],
                    'groups' => [],
                ],
                'assignees' => [
                    'users'     => [],
                    'groups' => [
                        [
                            'id'           => $assigned_groups_id,
                            'name'         => '_test_group_2',
                            'completename' => '_test_group_1 > _test_group_2',
                        ],
                    ],
                    'suppliers' => [
                        [
                            'id'       => $suppliers_id,
                            'name'     => '_suplier01_name',
                            'address'  => null,
                            'city'     => null,
                            'postcode' => null,
                            'state'    => null,
                            'country'  => null,
                            'phone'    => '0123456789',
                            'fax'      => '0123456787',
                            'email'    => 'info@_supplier01_name.com',
                            'website'  => null,
                        ],
                    ],
                ],
                'type'      => 'Incident',
                'global_validation' => "Not subject to approval",
                'tto' => date('Y-m-d H:i:s', strtotime($now) + 10 * 60),
                'ttr' => date('Y-m-d H:i:s', strtotime($now) + 3 * 3600),
                'sla_tto' => [
                    'id'       => $slas_id_tto,
                    'name'     => 'sla_tto_testGetValue',
                    'type'     => 'Time to own',
                    'duration' => 10,
                    'unit'     => 'minutes',
                ],
                'sla_ttr' => [
                    'id'       => $slas_id_ttr,
                    'name'     => 'sla_ttr_testGetValue',
                    'type'     => 'Time to resolve',
                    'duration' => 3,
                    'unit'     => 'hours',
                ],

                'olas_tto' => [
                    [
                        'id'       => $olas_id_tto,
                        'name'     => 'ola_tto_testGetValue',
                        'type'     => 'Time to own',
                        'duration' => 15,
                        'unit'     => 'minutes',
                    ],
                ],
                'olas_ttr' => [
                    [
                        'id'       => $olas_id_ttr,
                        'name'     => 'ola_ttr_testGetValue',
                        'type'     => 'Time to resolve',
                        'duration' => 4,
                        'unit'     => 'hours',
                    ],
                    [
                        'id'       => $olas_id_ttr2,
                        'name'     => 'ola_ttr_testGetValue_2',
                        'type'     => 'Time to resolve',
                        'duration' => 6,
                        'unit'     => 'hours',
                    ],
                ],
                'requesttype' => [
                    'id'   => 1,
                    'name' => 'Helpdesk',
                ],
                'location' => [
                    'id'           => $locations_id,
                    'name'         => 'location_testGetValues',
                    'completename' => 'location_testGetValues',
                ],
                'knowbaseitems' => [
                    [
                        'id' => getItemByTypeName(\KnowbaseItem::class, '_knowbaseitem01', true),
                        'name' => '_knowbaseitem01',
                        'answer' => "Answer for Knowledge base entry _knowbaseitem01 apple juice turnover",
                        'link' => "<a href=\"/front/knowbaseitem.form.php?id=1\" data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"_knowbaseitem01\">_knowbaseitem01</a>",
                    ],
                ],
                'assets'        => [],
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
