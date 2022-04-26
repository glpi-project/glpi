<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace tests\units;

use Contract;
use ContractType;
use Ticket_Contract;

// Force import because of atoum autoloader not working
require_once 'RuleCommonITILObject.php';

/* Test for inc/ruleticket.class.php */

class RuleTicket extends RuleCommonITILObject
{
    public function testGetCriteria()
    {
        $rule = $this->getRuleInstance();
        $criteria = $rule->getCriterias();
        $this->array($criteria)->size->isGreaterThan(20);
    }

    public function testGetActions()
    {
        $rule = $this->getRuleInstance();
        $actions  = $rule->getActions();
        $this->array($actions)->size->isGreaterThan(20);
    }

    public function testDefaultRuleExists()
    {
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from item',
                    'is_active' => 0
                ]
            )
        )->isIdenticalTo(1);
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from use',
                    'is_active' => 1
                ]
            )
        )->isIdenticalTo(0);
    }

    public function testHeaderCriteria(): void
    {
        $this->login();

        $ruleticket = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test x-priority',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $ruleticket::getType(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_x-priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => '1',
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

        // Create ITIL Object with "x-priority" = 1 in "_head" property
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add([
            'name'              => 'test x-priority header',
            'content'           => 'test x-priority header',
            '_head'             => [
                'x-priority' => '1'
            ]
        ]);

        // Verify ITIL Object has priority 5
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['priority'])->isEqualTo(5);

        // Retest ITIL Object with different x-priority
        $itil_id = $itil->add([
            'name'              => 'test x-priority header',
            'content'           => 'test x-priority header',
            '_head'             => [
                'x-priority' => '2'
            ]
        ]);

        // Verify ITIL Object does not have priority 5
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['priority'])->isNotEqualTo(5);
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
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

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
            'condition' => \Rule::PATTERN_IS,
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
        $category = new \ITILCategory();
        $category_id = $category->add($category_input = [
            "name" => "group1",
            "code" => "R"
        ]);
        $this->checkInput($category, $category_id, $category_input);

       // Create a ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        // Check that the rule was not executed yet
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_value);

        // Update ticket
        $update_1_res = $ticket->update([
            'id' => $ticket->fields['id'],
            'content' => 'content update 1',
        ]);
        $this->boolean($update_1_res)->isTrue();

        // Check that rule was not executed yet
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_value);

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
        $this->boolean($update_2_res)->isTrue();

        // Check that rule was executed correctly
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo($rule_value);

       // Create a second ticket with the contract linked
        $ticket_2 = new \Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id,
            '_contracts_id'     => $contract_id,
        ]);
        unset($ticket_input_2['_contracts_id']); // Remove temporary field as the "checkInput" method will not be able to find it
        $this->checkInput($ticket_2, $tickets_id_2, $ticket_input_2);

        // Check that the rule was executed correctly
        $this->boolean($ticket_2->getFromDB($tickets_id_2))->isTrue();
        $this->integer($ticket_2->fields['impact'])->isEqualTo($rule_value);
    }

    public function testGroupRequesterAssignFromDefaultUserAndLocationFromUserOnUpdate()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

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
            'condition' => \Rule::PATTERN_EXISTS,
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
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id, $group_input);

        //Load user tech
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'tech', true));

        //add user to group
        $group_user = new \Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

        //add default group to user
        $user->fields['groups_id'] = $group_id;
        $this->boolean($user->update($user->fields))->isTrue();

        //create new location
        $location = new \Location();
        $location_id = $location->add($location_input = [
            "name" => "location1",
        ]);
        $this->checkInput($location, $location_id, $location_input);

        //add location to user
        $user->fields['locations_id'] = $location_id;
        $this->boolean($user->update($user->fields))->isTrue();

        // Create ITIL Object
        $itil = $this->getITILObjectInstance();
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $itil_id = $itil->add($itil_input = [
            'name'             => 'Add group requester if requester have default group',
            'content'          => 'test',
            '_users_id_requester' => $user->fields['id']
        ]);
        unset($itil_input['_users_id_requester']); // _users_id_requester is stored in glpi_*_users table, so remove it
        $this->checkInput($itil, $itil_id, $itil_input);

        //locations_id must be set to 0
        $this->integer($itil->fields['locations_id'])->isIdenticalTo(0);

        //load ITILGroup (expected false)
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //Update ITIL Object to trigger rule
        $itil->update($itil_input = [
            'id' => $itil_id,
            'content' => 'test on update'
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);

        //load ITILGroup
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //locations_id must be set to
        $itil->getFromDB($itil_id);
        $this->integer($itil->fields['locations_id'])->isIdenticalTo($location_id);
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

        $this->dump($suppliers_id);

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

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
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_observer',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_requester',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_observer',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_suppliers_id_assign',
            'condition' => \Rule::PATTERN_IS,
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
                $ticket = new \Ticket();
                $tickets_id = $ticket->add([
                    'name'    => 'test actors',
                    'content' => 'test actors',
                    '_actors' => [
                        $actortype => [
                            [
                                'itemtype' => $actoritemtype,
                                'items_id' => $items_id,
                            ]
                        ]
                    ],
                ]);
                $ticket->getFromDB($tickets_id);
                $this->integer($ticket->fields['locations_id'])->isEqualTo($locations_id);
            }
        }

        // test also suppliers for assign
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'test actors supplier',
            'content' => 'test actors supplier',
            '_actors' => [
                'assign' => [
                    [
                        'itemtype' => 'Supplier',
                        'items_id' => $suppliers_id,
                    ]
                ]
            ],
        ]);
        $ticket->getFromDB($tickets_id);
        $this->integer($ticket->fields['locations_id'])->isEqualTo($locations_id);
    }
}
