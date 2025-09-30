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

namespace tests\units;

use CommonITILObject;
use Contract;
use ContractType;
use Entity;
use ITILCategory;
use ITILFollowup;
use ITILFollowupTemplate;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use Rule;
use RuleAction;
use RuleCriteria;
use TaskTemplate;
use Ticket;
use Ticket_Contract;

// Force import because of autoloader not working
require_once __DIR__ . '/../abstracts/RuleCommonITILObjectTest.php';

/* Test for inc/ruleticket.class.php */

class RuleTicketTest extends RuleCommonITILObjectTest
{
    public function testGetCriteria()
    {
        $rule = $this->getRuleInstance();
        $criteria = $rule->getCriterias();
        $this->assertGreaterThan(20, count($criteria));
    }

    public function testGetActions()
    {
        $rule = $this->getRuleInstance();
        $actions  = $rule->getActions();
        $this->assertGreaterThan(20, count($actions));
    }

    public function testDefaultRuleExists()
    {
        $this->assertSame(
            1,
            countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from item',
                    'is_active' => 0,
                ]
            )
        );
        $this->assertSame(
            0,
            countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from use',
                    'is_active' => 1,
                ]
            )
        );
    }

    public function testAssignContract()
    {
        $this->login();

        // Create contract1 "zabbix"
        $contractTest1 = new Contract();
        $contracttest1_id = $contractTest1->add($contractTest1_input = [
            "name"                  => "zabbix",
            "entities_id"           => 0,
        ]);
        $this->checkInput($contractTest1, $contracttest1_id, $contractTest1_input);

        // Create rule for create regex action
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associate contract with  : glpi',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        // Create criteria to match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'itilcategories_id',
            'condition' => Rule::REGEX_MATCH,
            'pattern'   => '/(zabbix)/',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to assign contract1
        $action_id1 = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => 'assign_contract',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id1, $action_input);

        // Create category for ticket
        $category = new ITILCategory();
        $category_id = $category->add($category_input = [
            "name" => "zabbix",
        ]);
        $this->checkInput($category, $category_id, $category_input);

        // Create ticket to match rule on create
        $ticketCreate = new Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test zabbix',
            'content'           => 'test zabbix',
            'itilcategories_id' => $category_id,
        ]);

        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);
        $this->assertGreaterThan(0, $ticketsCreate_id);

        // Check for one associated element
        $this->assertEquals(
            1,
            countElementsInTable(
                Ticket_Contract::getTable(),
                ['contracts_id'  => $contracttest1_id,
                    'tickets_id' => $ticketsCreate_id,
                ]
            )
        );
    }

    public static function mailHeaderCriteriaProvider()
    {
        return [
            [
                "pattern"  => 'pattern_priority',
                "header"   => 'x-priority',
            ],
            [
                "pattern"  => 'pattern_from',
                "header"   => 'from',
            ],
            [
                "pattern"  => 'pattern_to',
                "header"   => 'to',
            ],
            [
                "pattern"  => 'pattern_reply-to',
                "header"   => 'reply-to',
            ],
            [
                "pattern"  => 'pattern_in-reply-to',
                "header"   => 'in-reply-to',
            ],
            [
                "pattern"  => 'pattern_subject',
                "header"   => 'subject',
            ],
        ];
    }

    #[DataProvider('mailHeaderCriteriaProvider')]
    public function testMailHeaderCriteria(
        string $pattern,
        string $header
    ) {
        // clean right singleton
        \SingletonRuleList::getInstance("RuleTicket", 0)->load = 0;
        \SingletonRuleList::getInstance("RuleTicket", 0)->list = [];

        $this->login();

        $ruleticket = $this->getRuleInstance();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test ' . $header,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $ruleticket::getType(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => "_" . $header,
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $pattern,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to put priority to very high
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'priority',
            'value'       => 5,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Create ITIL Object with header value in "_head" property
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add([
            'name'              => 'test ' . $header . ' header',
            'content'           => 'test ' . $header . ' header',
            '_head'             => [
                $header => $pattern,
            ],
        ]);

        // Verify ITIL Object has priority 5
        $this->assertTrue($itil->getFromDB($itil_id));
        $this->assertEquals(5, $itil->fields['priority']);

        // Retest ITIL Object with different header value
        $itil_id = $itil->add([
            'name'              => 'test ' . $header . ' header',
            'content'           => 'test ' . $header . ' header',
            '_head'             => [
                $header => 'header_foo_bar',
            ],
        ]);

        // Verify ITIL Object does not have priority 5
        $this->assertTrue($itil->getFromDB($itil_id));
        $this->assertNotEquals(5, $itil->fields['priority']);
    }

    /**
     * Test contract type criteria
     */
    public function testContractType()
    {
        $this->login();

        // Create contract type (we need its id to setup the rule)
        $contract_type = new ContractType();
        $contract_type_input = [
            'name'        => 'test_contract',
        ];
        $contract_type_id = $contract_type->add($contract_type_input);
        $this->checkInput($contract_type, $contract_type_id, $contract_type_input);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test contract type',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        // Create criteria to check if category code is R
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_contract_types',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $contract_type_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to put impact to very low
        $rule_value = 2;
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => $rule_value,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Create new group
        $category = new ITILCategory();
        $category_id = $category->add($category_input = [
            "name" => "group1",
            "code" => "R",
        ]);
        $this->checkInput($category, $category_id, $category_input);

        // Create a ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        // Check that the rule was not executed yet
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertNotEquals($rule_value, $ticket->fields['impact']);

        // Update ticket
        $update_1_res = $ticket->update([
            'id' => $ticket->fields['id'],
            'content' => 'content update 1',
        ]);
        $this->assertTrue($update_1_res);

        // Check that rule was not executed yet
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertNotEquals($rule_value, $ticket->fields['impact']);

        // Create contract
        $contract = new Contract();
        $contract_input = [
            'name'             => 'test_contract',
            'contracttypes_id' => $contract_type_id,
            'entities_id'      => getItemByTypeName('Entity', '_test_root_entity', true),
        ];
        $contract_id = $contract->add($contract_input);
        $this->checkInput($contract, $contract_id, $contract_input);

        // Link contract to ticket
        $ticketcontract = new Ticket_Contract();
        $ticketcontract_input = [
            'contracts_id' => $contract_id,
            'tickets_id'   => $ticket->fields['id'],
        ];
        $ticketcontract_id = $ticketcontract->add($ticketcontract_input);
        $this->checkInput($ticketcontract, $ticketcontract_id, $ticketcontract_input);

        // Update ticket a second time
        $update_2_res = $ticket->update([
            'id' => $ticket->fields['id'],
            'content' => 'content update 2',
        ]);
        $this->assertTrue($update_2_res);

        // Check that rule was executed correctly
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals($rule_value, $ticket->fields['impact']);

        // Create a second ticket with the contract linked
        $ticket_2 = new Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id,
            '_contracts_id'     => $contract_id,
        ]);
        unset($ticket_input_2['_contracts_id']); // Remove temporary field as the "checkInput" method will not be able to find it
        $this->checkInput($ticket_2, $tickets_id_2, $ticket_input_2);

        // Check that the rule was executed correctly
        $this->assertTrue($ticket_2->getFromDB($tickets_id_2));
        $this->assertEquals($rule_value, $ticket_2->fields['impact']);
    }

    public function testGroupRequesterAssignFromDefaultUserAndLocationFromUserOnUpdate()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test group requester from user on update',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check an update
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => Rule::PATTERN_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to put default user group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'defaultfromuser',
            'field'       => '_groups_id_requester',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create action to put user location as ITIL Object location
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'fromuser',
            'field'       => 'locations_id',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create new group
        $group = new \Group();
        $group_id = $group->add($group_input = [
            "name" => "group1",
            "is_requester" => true,
        ]);
        $this->checkInput($group, $group_id, $group_input);

        //Load user tech
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'tech', true));

        //add user to group
        $group_user = new \Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id,
            "users_id"  => $user->fields['id'],
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

        //add default group to user
        $user->fields['groups_id'] = $group_id;
        $this->assertTrue($user->update($user->fields));

        //create new location
        $location = new Location();
        $location_id = $location->add($location_input = [
            "name" => "location1",
        ]);
        $this->checkInput($location, $location_id, $location_input);

        //add location to user
        $user->fields['locations_id'] = $location_id;
        $this->assertTrue($user->update($user->fields));

        // Create ITIL Object
        $itil = $this->getITILObjectInstance();
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $itil_id = $itil->add($itil_input = [
            'name'             => 'Add group requester if requester have default group',
            'content'          => 'test',
            '_users_id_requester' => $user->fields['id'],
        ]);
        unset($itil_input['_users_id_requester']); // _users_id_requester is stored in glpi_*_users table, so remove it
        $this->checkInput($itil, $itil_id, $itil_input);

        //locations_id must be set to 0
        $this->assertSame(0, $itil->fields['locations_id']);

        //load ITILGroup (expected false)
        $itil_group = $this->getITILLinkInstance('Group');
        $this->assertFalse(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id,
                'type'      => \CommonITILActor::REQUESTER,
            ])
        );

        //Update ITIL Object to trigger rule
        $itil->update($itil_input = [
            'id' => $itil_id,
            'content' => 'test on update',
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);

        //load ITILGroup
        $itil_group = $this->getITILLinkInstance('Group');
        $this->assertTrue(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id,
                'type'      => \CommonITILActor::REQUESTER,
            ])
        );

        //locations_id must be set to
        $itil->getFromDB($itil_id);
        $this->assertSame($location_id, $itil->fields['locations_id']);
    }

    public function testNewActors()
    {
        $this->login();

        $tech_id   = getItemByTypeName('User', "tech", true);
        $groups_id = getItemByTypeName('Group', '_test_group_1', true);

        $supplier = new \Supplier();
        $suppliers_id = $supplier->add([
            'name'        => 'Supplier 1',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $suppliers_id);

        $location = new Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->assertGreaterThan(0, $locations_id);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testNewActors',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        //create criteria to check
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_requester',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_observer',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_assign',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_requester',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_observer',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_assign',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_suppliers_id_assign',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $suppliers_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to add group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // test all common actors
        foreach (['User', 'Group'] as $actoritemtype) {
            $items_id = ($actoritemtype == "User") ? $tech_id : $groups_id;
            foreach (['requester', 'observer', 'assign'] as $actortype) {
                $ticket = new Ticket();
                $tickets_id = $ticket->add([
                    'name'    => 'test actors',
                    'content' => 'test actors',
                    '_actors' => [
                        $actortype => [
                            [
                                'itemtype' => $actoritemtype,
                                'items_id' => $items_id,
                            ],
                        ],
                    ],
                ]);
                $ticket->getFromDB($tickets_id);
                $this->assertEquals($locations_id, $ticket->fields['locations_id']);
            }
        }

        // test also suppliers for assign
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'test actors supplier',
            'content' => 'test actors supplier',
            '_actors' => [
                'assign' => [
                    [
                        'itemtype' => 'Supplier',
                        'items_id' => $suppliers_id,
                    ],
                ],
            ],
        ]);
        $ticket->getFromDB($tickets_id);
        $this->assertEquals($locations_id, $ticket->fields['locations_id']);
    }

    public function testAssignManager()
    {
        $this->login();

        // create users
        $user = new \User();
        $manager_id = $user->add([
            'name' => 'test manager',
        ]);
        $this->assertGreaterThan(0, $manager_id);
        $user_id = $user->add([
            'name' => 'test user',
            'users_id_supervisor' => $manager_id,
        ]);
        $this->assertGreaterThan(0, $user_id);

        // check manager
        $user->getFromDB($user_id);
        $this->assertEquals($manager_id, $user->fields['users_id_supervisor']);

        // create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testManager',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        //create criteria to check
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'itilcategories_id',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => 0,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to manager
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => '_users_id_observer',
            'value'       => 'requester_manager',
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // create ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'test manager',
            'content' => 'test manager',
            '_users_id_requester' => [$user_id],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // check manager
        $ticket_user = new \Ticket_User();
        $this->assertTrue(
            $ticket_user->getFromDBByCrit([
                'tickets_id'    => $tickets_id,
                'users_id'      => $manager_id,
                'type'          => \CommonITILActor::OBSERVER,
            ])
        );
    }

    public function testAssignProject()
    {
        $this->login();

        //create project "project"
        $projectTest1 = new \Project();
        $projecttest1_id = $projectTest1->add($projectTest1_input = [
            "name"                  => "project",
        ]);
        $this->checkInput($projectTest1, $projecttest1_id, $projectTest1_input);

        // Add rule for create / update trigger (and assign action)
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associated element : project',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE + \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        // Create criteria to check if content contain key word
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => Rule::PATTERN_CONTAIN,
            'pattern'   => 'project',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to add project
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'assign_project',
            'value'       => $projecttest1_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create ticket to match rule on create
        $ticketCreate = new Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test project',
            'content'           => 'test project',
        ]);
        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);

        //check for one associated element
        $this->assertEquals(
            1,
            countElementsInTable(
                \Itil_Project::getTable(),
                ['itemtype'  =>  Ticket::getType(),
                    'projects_id'   => $projecttest1_id,
                    'items_id' => $ticketsCreate_id,
                ]
            )
        );

        //create ticket to match rule on update
        $ticketUpdate = new Ticket();
        $ticketsUpdate_id = $ticketUpdate->add($ticketUpdate_input = [
            'name'              => 'test',
            'content'           => 'test',
        ]);
        $this->checkInput($ticketUpdate, $ticketsUpdate_id, $ticketUpdate_input);

        //no project associated
        $this->assertEquals(
            0,
            countElementsInTable(
                \Itil_Project::getTable(),
                ['itemtype'  =>  Ticket::getType(),
                    'projects_id'   => $projecttest1_id,
                    'items_id' => $ticketsUpdate_id,
                ]
            )
        );

        //update ticket content to match rule
        $ticketUpdate->update(
            [
                'id'      => $ticketsUpdate_id,
                'name'    => 'test erp',
                'content' => 'project',
            ]
        );

        //check for one associated element
        $this->assertEquals(
            1,
            countElementsInTable(
                \Itil_Project::getTable(),
                ['itemtype'  =>  Ticket::getType(),
                    'projects_id'   => $projecttest1_id,
                    'items_id' => $ticketsUpdate_id,
                ]
            )
        );
    }

    public function testFollowupTemplateAssignFromGroup()
    {
        $this->login();

        // Create rule
        $rule_ticket = new \RuleTicket();
        $rule_ticket_id = $rule_ticket->add([
            'name'         => 'test group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $rule_ticket_id);

        //create group that matches the rule
        $group = new \Group();
        $group_id1 = $group->add($group_input = [
            "name"         => "group1",
            "is_requester" => true,
        ]);
        $this->checkInput($group, $group_id1, $group_input);

        //create group that doesn't match the rule
        $group_id2 = $group->add($group_input = [
            "name"         => "group2",
            "is_requester" => true,
        ]);
        $this->checkInput($group, $group_id2, $group_input);

        // Create criteria to check if requester group is group1
        $rule_criteria = new RuleCriteria();
        $rule_criteria_id = $rule_criteria->add([
            'rules_id'  => $rule_ticket_id,
            'criteria'  => '_groups_id_requester',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $group_id1,
        ]);
        $this->assertGreaterThan(0, $rule_criteria_id);

        // Create followup template
        $followup_template = new ITILFollowupTemplate();
        $followup_template_id = $followup_template->add([
            'content' => "<p>test testFollowupTemplateAssignFromGroup</p>",
        ]);
        $this->assertGreaterThan(0, $followup_template_id);

        // Add action to rule
        $rule_action = new RuleAction();
        $rule_action_id = $rule_action->add([
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $followup_template_id,
        ]);
        $this->assertGreaterThan(0, $rule_action_id);

        // Create ticket
        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name'              => 'test',
            'content'           => 'test',
            '_groups_id_requester' => [$group_id1],
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        //link between group1 and ticket will exist
        $ticketGroup = new \Group_Ticket();
        $this->assertTrue(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER,
            ])
        );

        // Check that followup was added
        $this->assertEquals(
            1,
            countElementsInTable(
                ITILFollowup::getTable(),
                ['itemtype' => Ticket::getType(), 'items_id' => $ticket_id]
            )
        );

        // Add group2 to ticket
        $ticket->update([
            'id'                  => $ticket_id,
            '_groups_id_requester' => [$group_id1, $group_id2],
        ]);

        //link between group2 and ticket will exist
        $this->assertTrue(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER,
            ])
        );

        // Check that followup was added
        $this->assertEquals(
            2,
            countElementsInTable(
                ITILFollowup::getTable(),
                ['itemtype' => Ticket::getType(), 'items_id' => $ticket_id]
            )
        );

        // Add user to ticket
        $user = new \User();
        $user_id = $user->add([
            'name' => 'test',
        ]);
        $this->assertGreaterThan(0, $user_id);

        $ticket->update([
            'id'                  => $ticket_id,
            '_users_id_requester' => [$user_id],
        ]);

        //link between user and ticket will exist
        $ticketUser = new \Ticket_User();
        $this->assertTrue(
            $ticketUser->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'users_id'           => $user_id,
                'type'               => \CommonITILActor::REQUESTER,
            ])
        );

        // Check that followup was NOT added
        $this->assertEquals(
            2,
            countElementsInTable(
                ITILFollowup::getTable(),
                ['itemtype' => Ticket::getType(), 'items_id' => $ticket_id]
            )
        );
    }

    public function testSLACriterion()
    {
        $this->login('glpi', 'glpi');

        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => "test rule SLA",
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $slm = new \SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => 0, //24/24 7/7
            ]
        );
        $this->assertGreaterThan(0, $slm_id);

        // prepare sla/ola inputs
        $sla_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTR",
            'comment'         => $this->getUniqueString(),
            'type'            => \SLM::TTR,
            'number_time'     => 4,
            'definition_time' => 'day',
        ];

        // add SLA (TTR)
        $sla    = new \SLA();
        $slas_id_ttr = $sla->add($sla_in);
        $this->checkInput($sla, $slas_id_ttr, $sla_in);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'slas_id_ttr',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $slas_id_ttr,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'urgency',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => 5,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create new location
        $location = new Location();
        $location_id = $location->add($location_input = [
            "name" => "location1",
        ]);
        $this->checkInput($location, $location_id, $location_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $location_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        //create ticket to match rule
        $ticket = new Ticket();
        $ticket_id = $ticket->add($ticket_input = [
            'name'              => 'test SLA',
            'content'           => 'test SLA',
            'slas_id_ttr'       => $slas_id_ttr,
            'urgency'           => 5,
        ]);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->assertSame($location_id, $ticket->fields['locations_id']);

        //create ticket to not match rule
        $ticket = new Ticket();
        $ticket_id = $ticket->add($ticket_input = [
            'name'              => 'test SLA',
            'content'           => 'test SLA',
            'slas_id_ttr'       => $slas_id_ttr,
        ]);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->assertSame(0, $ticket->fields['locations_id']);

        //update URGENCY to match rule
        $this->assertTrue($ticket->update($ticket_input = [
            'id'                => $ticket_id,
            'urgency'           => 5,
        ]));

        $ticket->getFromDB($ticket_id);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->assertSame($location_id, $ticket->fields['locations_id']);
    }

    /**
     * Data provider for testAssignLocationFromUser
     *
     * @return iterable
     */
    protected function testAssignLocationFromUserProvider(): iterable
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity');
        $user = getItemByTypeName('User', TU_USER);

        // Create rule
        $rule = $this->createItem("RuleTicket", [
            'name'        => "test rule SLA",
            'match'       => 'AND',
            'is_active'   => 1,
            'sub_type'    => 'RuleTicket',
            'condition'   => \RuleTicket::ONADD,
            'entities_id' => $entity->getID(),
        ]);
        $this->createItem("RuleCriteria", [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'locations_id',
            'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
            'pattern'   => 1,
        ]);
        $this->createItem("RuleCriteria", [
            'rules_id'  => $rule->getID(),
            'criteria'  => '_locations_id_of_requester',
            'condition' => Rule::PATTERN_EXISTS,
            'pattern'   => 1,
        ]);
        $this->createItem("RuleAction", [
            'rules_id'    => $rule->getID(),
            'action_type' => 'fromuser',
            'field'       => 'locations_id',
            'value'       => 1,
        ]);

        // Create location and set it to our user
        $user_location = $this->createItem('Location', [
            'name'        => 'User location',
            'entities_id' => $entity->getID(),
        ]);
        $this->updateItem('User', $user->getID(), [
            'locations_id' => $user_location->getID(),
        ]);

        // Create another location
        $other_location = $this->createItem('Location', [
            'name'        => 'Other location',
            'entities_id' => $entity->getID(),
        ]);

        // Create a ticket without location, should trigger the rule and set the user location
        yield [null, $user_location->getID()];

        // Create a ticket with a specific location, should not trigger the rule
        yield [$other_location->getID(), $other_location->getID()];
    }

    /**
     * Test the following rule:
     * IF ticket location is not set AND Requester has a location
     * THEN set location from requester
     *
     * @param int|null $input_locations_id               Input location
     * @param int      $expected_location_after_creation Ticket final location after the rule are processed
     *
     * @return void
     */
    public function testAssignLocationFromUser(): void
    {
        $provider = $this->testAssignLocationFromUserProvider();
        foreach ($provider as $row) {
            [$input_locations_id, $expected_location_after_creation] = $row;

            $input = [
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'name' => 'test ticket',
                'content' => 'test ticket',
                '_actors' => [
                    // Requester is needed for the criteria on the requester's location
                    'requester' => [
                        [
                            'itemtype' => 'User',
                            'items_id' => getItemByTypeName('User', TU_USER, true),
                        ],
                    ],
                    // Must have an assigned tech for the test to be meaningfull as this
                    // will trigger some post_update code that will run the rules again
                    'assign' => [
                        [
                            'itemtype' => 'User',
                            'items_id' => getItemByTypeName('User', TU_USER, true),
                        ],
                    ],
                ],
            ];

            if (!is_null($input_locations_id)) {
                $input['locations_id'] = $input_locations_id;
            }

            $ticket = $this->createItem('Ticket', $input);
            $ticket->getFromDB($ticket->getID());
            $this->assertEquals($expected_location_after_creation, $ticket->fields['locations_id']);
        }
    }

    /**
     * Test writer criterion in rules.
     */
    public function testWriterCriterion()
    {
        $this->login();

        $user_id = (int) getItemByTypeName('User', '_test_user', true);

        $requesttypes_id = $this->createItem('RequestType', [
            'name' => 'requesttype_' . __METHOD__,
        ])->getID();

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test writer criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'users_id_recipient',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => $user_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'requesttypes_id',
            'value'       => $requesttypes_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // Check ticket that trigger rule on creation
        $ticket = new Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => __METHOD__,
            'content'          => __METHOD__,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($user_id, (int) $ticket->getField('users_id_recipient'));
        $this->assertEquals($requesttypes_id, (int) $ticket->getField('requesttypes_id'));
    }

    public function testDoNotComputeStatusFollowupWithRule()
    {
        $this->login('glpi', 'glpi');

        $followuptemplate = new ITILFollowupTemplate();
        $templateid = $followuptemplate->add($templateinput = [
            'name' => 'followuptemplate_' . __FUNCTION__,
            'content' => 'Test',
        ]);
        $this->checkInput($followuptemplate, $templateid, $templateinput);

        // Create the rule to change status and add a followup template
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test do not compute status followup',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => Rule::PATTERN_CONTAIN,
            'pattern'   => 'test',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'status',
            'value'       => CommonITILObject::WAITING,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        $act2_id = $ruleaction->add($act2_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $templateid,
        ]);
        $this->checkInput($ruleaction, $act2_id, $act2_input);

        $user1 = new \User();
        $user1->getFromDBbyName('glpi');
        $this->assertGreaterThan(0, $user1->getID());

        $user2 = new \User();
        $user2->getFromDBbyName('tech');
        $this->assertGreaterThan(0, $user2->getID());

        $ticket = new Ticket();
        // Create ticket with two actors (requester and technician)
        $tickets_id = $ticket->add([
            'name' => 'test ticket ' . __FUNCTION__,
            'content' => __FUNCTION__,
            '_actors' => [
                'requester' => [
                    [
                        'items_id' => $user1->getID(),
                        'itemtype' => 'User',
                    ],
                ],
                'assign' => [
                    [
                        'items_id' => $user2->getID(),
                        'itemtype' => 'User',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);

        $this->assertEquals(CommonITILObject::WAITING, $ticket->fields['status']);

        // Create followup without _do_not_compute_status
        $this->createItem('ITILFollowup', [
            'itemtype'               => $ticket::getType(),
            'items_id'               => $tickets_id,
            'content'                => 'simple followup content',
            'date'                   => '2015-01-01 00:00:00',
        ]);

        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);

        $this->assertEquals(CommonITILObject::ASSIGNED, $ticket->fields['status']);
    }


    /**
     * Test action of a rule ticket that add a followup to a ticket
     * and do not compute take into account delay.
     */
    public function testDoNotComputeTakeIntoAccountWithFollowupTemplateWithRule()
    {
        $this->login('glpi', 'glpi');

        // Create followup template
        $followuptemplate = new ITILFollowupTemplate();
        $templateid = $followuptemplate->add($templateinput = [
            'name' => 'followuptemplate_' . __FUNCTION__,
            'content' => 'Test',
        ]);
        $this->checkInput($followuptemplate, $templateid, $templateinput);

        // Create the rule to add a followup template and to not compute takeintoaccount delay
        $ruleticket = new \RuleTicket();
        $rulecrit   = new RuleCriteria();
        $ruleaction = new RuleAction();

        // create rule
        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test do not compute takeintoaccount with followup template',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        // create criteria to check if title contain 'test' key word
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => Rule::PATTERN_CONTAIN,
            'pattern'   => 'test',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // add action to assign followup templates
        $act2_id = $ruleaction->add($act2_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $templateid,
        ]);
        $this->checkInput($ruleaction, $act2_id, $act2_input);

        // add action to not compute takeintoaccount delay
        $act3_id = $ruleaction->add($act3_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'takeintoaccount_delay_stat',
            'field'       => 'do_not_compute',
            'value'       => true,
        ]);
        $this->checkInput($ruleaction, $act3_id, $act3_input);

        // Load user glpi
        $user1 = new \User();
        $user1->getFromDBbyName('glpi');
        $this->assertGreaterThan(0, $user1->getID());

        // Create ticket
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test ticket ' . __FUNCTION__,
            'content' => __FUNCTION__,
            '_actors' => [
                'requester' => [
                    [
                        'items_id' => $user1->getID(),
                        'itemtype' => 'User',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // reload ticket
        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);

        // check that ticket status is always new
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);
        // check that ticket takeintoaccount delay is not computed
        $this->assertEquals(0, $ticket->fields['takeintoaccount_delay_stat']);
        // followup well added
        $this->assertEquals(
            1,
            countElementsInTable(
                ITILFollowup::getTable(),
                ['itemtype' => Ticket::getType(), 'items_id' => $tickets_id]
            )
        );
    }

    /**
     * Test the action of a rule that adds a closed ticket
     * upon creation and adds a task.
     */
    public function testTaskTemplateDoesNotReopenAClosedTicketWithRule()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');

        // Set entity auto assign mode to category hardware to auto assign group to add group to ticket
        $this->updateItem(Entity::class, 0, [
            'auto_assign_mode' => Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
        ]);

        $group = $this->createItem(\Group::class, [
            'name' => 'Test group',
        ]);

        $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'groups_id' => $group->getID(),
        ]);

        // Create rule to add a task template
        // when the title contains 'close' key word
        $rule = $this->createItem(Rule::class, [
            'name'         => 'test do not compute status task with rule',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);

        $this->createItem(RuleCriteria::class, [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'name',
            'condition' => Rule::PATTERN_CONTAIN,
            'pattern'   => 'close',
        ]);

        $task_template = $this->createItem(TaskTemplate::class, [
            'name' => 'Task template',
            'content' => 'Test task template content',
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);

        $this->createItem(RuleAction::class, [
            'rules_id'    => $rule->getID(),
            'action_type' => 'append',
            'field'       => 'task_template',
            'value'       => $task_template->getID(),
        ]);

        $this->login('tech', 'tech');

        //First create a ticket with the key word 'close' in title to trigger the rule without close rule action
        // to check that the task is well added and that the ticket is in assigned status
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'test ticket close',
            'content' => 'test ticket close content',
            'entities_id' => 0,
            'itilcategories_id' => getItemByTypeName(ITILCategory::class, 'Test category')->getID(),
            '_actors'     => [
                'requester' => [
                    [
                        'itemtype' => \User::class,
                        'items_id' => $user->getID(),
                    ],
                ],
            ],
        ]);

        // Check that task is added
        $this->assertEquals(
            1,
            countElementsInTable(
                \TicketTask::getTable(),
                ['tickets_id' => $ticket->getID()]
            )
        );

        // Check that the ticket has the right group and user assigned
        $this->assertEquals(
            1,
            countElementsInTable(
                \Ticket_User::getTable(),
                ['tickets_id' => $ticket->getID(), 'users_id' => $user->getID()]
            )
        );

        $this->assertEquals(
            1,
            countElementsInTable(
                \Group_Ticket::getTable(),
                ['tickets_id' => $ticket->getID(), 'groups_id' => $group->getID()]
            )
        );

        // Check that ticket status is assigned
        $this->assertEquals(CommonITILObject::ASSIGNED, $ticket->fields['status']);

        // Now add the rule action to close the ticket
        // when the title contains 'close' key word
        $this->createItem(RuleAction::class, [
            'rules_id'    => $rule->getID(),
            'action_type' => 'assign',
            'field'       => 'status',
            'value'       => CommonITILObject::CLOSED,
        ]);

        // Create again a ticket with the key word 'close' in title to trigger the rule with close rule action
        // to check that the task is well added and that the ticket is in closed status
        // and not reopened by task template
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'test ticket close',
            'content' => 'test ticket close content',
            'entities_id' => 0,
            'itilcategories_id' => getItemByTypeName(ITILCategory::class, 'Test category')->getID(),
            '_actors'     => [
                'requester' => [
                    [
                        'itemtype' => \User::class,
                        'items_id' => $user->getID(),
                    ],
                ],
            ],
        ]);

        // Check that task is added
        $this->assertEquals(
            1,
            countElementsInTable(
                \TicketTask::getTable(),
                ['tickets_id' => $ticket->getID()]
            )
        );

        // Check that the ticket has the right group and user assigned
        $this->assertEquals(
            1,
            countElementsInTable(
                \Ticket_User::getTable(),
                ['tickets_id' => $ticket->getID(), 'users_id' => $user->getID()]
            )
        );

        $this->assertEquals(
            1,
            countElementsInTable(
                \Group_Ticket::getTable(),
                ['tickets_id' => $ticket->getID(), 'groups_id' => $group->getID()]
            )
        );

        // Check that ticket status is closed
        $this->assertEquals(CommonITILObject::CLOSED, $ticket->fields['status']);
    }
}
