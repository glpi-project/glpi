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

namespace tests\units;

use DbTestCase;

/* Test for inc/ruleticket.class.php */

class RuleAsset extends DbTestCase
{
    public function testGetCriteria()
    {
        $rule = new \RuleAsset();
        $criteria = $rule->getCriterias();
        $this->array($criteria)->size->isGreaterThanOrEqualTo(8);
    }

    public function testGetActions()
    {
        $rule = new \RuleAsset();
        $actions  = $rule->getActions();
        $this->array($actions)->size->isGreaterThanOrEqualTo(8);
    }


    public function testTriggerAdd()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // prepare rule
        $this->createRuleComment(\RuleAsset::ONADD);

        // test create ticket (trigger on title)
        $computer = new \Computer();
        $computers_id = $computer->add($computer_input = [
            'name'        => "computer",
            '_auto'       => 1,
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->string((string)$computer->getField('comment'))->isEqualTo('comment1');

        $computers_id = $computer->add($computer_input = [
            'name'        => "computer2",
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 0,
            '_auto'       => 0
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->string((string)$computer->getField('comment'))->isEqualTo('');

        $monitor = new \Monitor();
        $monitors_id = $monitor->add($monitor_input = [
            'name'        => "monitor",
            'contact'     => 'tech',
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            '_auto'       => 1
        ]);
        $this->boolean((bool)$monitor->getFromDB($monitors_id))->isTrue();

        //Rule only apply to computers
        $this->string((string)$monitor->getField('comment'))->isEqualTo('');
        $this->integer((int)$monitor->getField('users_id'))->isEqualTo(0);

        $computers_id = $computer->add($computer_input = [
            'name'        => "computer3",
            'contact'     => 'tech@AD',
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            '_auto'       => 1
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

        //User rule should apply (extract @domain from the name)
        $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);
        $this->string((string)$computer->getField('comment'))->isEqualTo('comment1');

        $computers_id = $computer->add($computer_input = [
            'name'        => "computer3",
            'contact'     => 'tech@AD',
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            '_auto'       => 1
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

        //User rule should apply (extract the first user from the list)
        $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);

        $computers_id = $computer->add($computer_input = [
            'name'        => "computer4",
            'contact'     => 'tech,glpi',
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            '_auto'       => 1
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

        //User rule should apply (extract the first user from the list)
        $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);

        $computers_id = $computer->add($computer_input = [
            'name'        => "computer5",
            'contact'     => 'tech2',
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            '_auto'       => 1
        ]);
        $this->integer((int)$computers_id)->isGreaterThan(0);
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

        //User rule should apply (extract @domain from the name) but should not
        //find any user, so users_id is set to 0
        $this->integer((int)$computer->getField('users_id'))->isEqualTo(0);
    }

    public function testTriggerUpdate()
    {
        global $CFG_GLPI;

        $this->login();
        $this->setEntity('_test_root_entity', true);

        $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // prepare rule
        $this->createRuleComment(\RuleAsset::ONUPDATE);
        $this->createRuleLocation(\RuleAsset::ONUPDATE);

        foreach ($CFG_GLPI['asset_types'] as $itemtype) {
            $item     = new $itemtype();
            $item_input = [
                'name'        => "$itemtype 1",
                'entities_id' => $root_ent_id,
                'is_dynamic'  => 1,
                'comment'     => 'mycomment'
            ];
            if ($itemtype == 'SoftwareLicense') {
                $item_input['softwares_id'] = 1;
            }
            $items_id = $item->add($item_input);

            // Trigger update
            $update = $item->update([
                'id'    => $item->getID(),
                'name'  => 'updated name',
                '_auto' => 1,
            ]);
            $this->boolean($update)->isTrue();

            $this->integer((int)$items_id)->isGreaterThan(0);
            $this->boolean((bool)$item->getFromDB($items_id))->isTrue();
            if ($itemtype == 'Computer') {
                $this->string((string)$item->getField('comment'))->isEqualTo('comment1');
            } else {
                $this->string((string)$item->getField('comment'))->isEqualTo('mycomment');
            }
            $this->integer((int)$item->getField('locations_id'))->isGreaterThan(0);
        }
    }

    private function createRuleComment($condition)
    {
        $ruleasset  = new \RuleAsset();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruleid = $ruleasset->add($ruleinput = [
            'name'         => "rule comment",
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleasset, $ruleid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruleid,
            'criteria'  => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => "Computer"
        ]);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruleid,
            'criteria'  => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 1
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruleid,
            'action_type' => 'assign',
            'field'       => 'comment',
            'value'       => 'comment1'
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
    }

    private function createRuleLocation($condition)
    {
        $ruleasset  = new \RuleAsset();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruleid = $ruleasset->add($ruleinput = [
            'name'         => "rule location",
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleasset, $ruleid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruleid,
            'criteria'  => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => "*"
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruleid,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => 1
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
    }

    public function testUserLocationAssignFromRule()
    {
        $this->login();

       // Create solution template
        $location     = new \Location();
        $locations_id = $location->add([
            'name' => "test"
        ]);
        $this->integer($locations_id)->isGreaterThan(0);
        $user     = new \User();
        $users_id = $user->add([
            'name'         => "user test",
            'locations_id' => $locations_id
        ]);
        $this->integer($users_id)->isGreaterThan(0);

       // Create rule
        $rule_asset_em = new \RuleAsset();
        $rule_asset_id = $rule_asset_em->add($ruletinput = [
            'name'         => "test to assign location from user",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_asset_id)->isGreaterThan(0);

       // Add condition (priority = 5) to rule
        $rule_criteria_em = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria_em->add($crit_input = [
            'rules_id'  => $rule_asset_id,
            'criteria'  => 'users_id',
            'condition' => \Rule::PATTERN_EXISTS,
            'pattern'   => '',
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

       // Add action to rule
        $rule_action_em = new \RuleAction();
        $rule_action_id = $rule_action_em->add($act_input = [
            'rules_id'    => $rule_asset_id,
            'action_type' => 'fromuser',
            'field'       => 'locations_id',
            'value'       => 1,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

       // Test on creation
        $computer_em = new \Computer();
        $computer_id = $computer_em->add([
            'name'        => 'test',
            'entities_id' => 0,
            'users_id'    => $users_id,
        ]);
        $this->integer($computer_id)->isGreaterThan(0);
        $this->boolean($computer_em->getFromDB($computer_id))->isTrue();
        $this->integer($computer_em->getField('locations_id'))->isEqualTo($locations_id);

       // Test on update
        $computer_em = new \Computer();
        $computer_id = $computer_em->add([
            'name'        => 'test2',
            'entities_id' => 0,
        ]);
        $this->integer($computer_id)->isGreaterThan(0);
        $this->boolean($computer_em->getFromDB($computer_id))->isTrue();
        $this->integer($computer_em->getField('locations_id'))->isNotEqualTo($locations_id);

        $update = $computer_em->update([
            'id'       => $computer_id,
            'users_id' => $users_id,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($computer_em->getFromDB($computer_id))->isTrue();
        $this->integer($computer_em->getField('locations_id'))->isEqualTo($locations_id);
    }

    public function testGroupUserAssignFromDefaultUser()
    {
        $this->login();

       // Create rule
        $ruleasset  = new \RuleAsset();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleasset->add($ruletinput = [
            'name'         => 'test default group from user criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleasset, $ruletid, $ruletinput);

       //create criteria to check if group requester already define
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'groups_id',
            'condition' => \Rule::PATTERN_DOES_NOT_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

       //create action to put default user group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'defaultfromuser',
            'field'       => 'groups_id',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

       //create new group
        $group    = new \Group();
        $group_id = $group->add($group_input = [
            "name"         => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id, $group_input);

       //Load user tech
        $user = getItemByTypeName('User', 'tech');

       //add user to group
        $group_user    = new \Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

       //add default group to user
        $user->fields['groups_id'] = $group_id;
        $this->boolean($user->update($user->fields))->isTrue();

       // Check ticket that trigger rule on creation
        $computer     = new \Computer();
        $computers_id = $computer->add($computer_input = [
            'name'        => 'test',
            'entities_id' => 0,
            'users_id'    => $user->fields['id']
        ]);
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->getField('groups_id'))->isEqualTo($user->getField('groups_id'));
    }

    public function testFirstGroupUserAssignFromUser()
    {
        $this->login();

       // Create rule
        $ruleasset  = new \RuleAsset();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleasset->add($ruletinput = [
            'name'         => 'test first group from user criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleasset, $ruletid, $ruletinput);

       //create criteria to check if group requester already define
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'groups_id',
            'condition' => \Rule::PATTERN_DOES_NOT_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

       //create action to put default user group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'firstgroupfromuser',
            'field'       => 'groups_id',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

       //create new group
        $group    = new \Group();
        $group_id = $group->add($group_input = [
            "name"         => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id, $group_input);

       //create second group
        $group2    = new \Group();
        $group_id2 = $group2->add($group_input2 = [
            "name"         => "group2",
            "is_requester" => true
        ]);
        $this->checkInput($group2, $group_id2, $group_input2);

       //Load user tech
        $user = getItemByTypeName('User', 'tech');

       //add user to group
        $group_user    = new \Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id2,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

       // Check ticket that trigger rule on creation
        $computer     = new \Computer();
        $computers_id = $computer->add($computer_input = [
            'name'        => 'test',
            'entities_id' => 0,
            'users_id'    => $user->fields['id']
        ]);
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->getField('groups_id'))->isEqualTo($group_id);
    }
}
