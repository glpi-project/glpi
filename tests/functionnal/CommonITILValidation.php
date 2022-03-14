<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

/* Test for inc/commonitilvalidation.class.php */

class CommonITILValidation extends DbTestCase
{
    public function testGroupUserApproval()
    {
        $this->login();

        /** Create a group with two users */
        $group = new \Group();
        $gid = (int)$group->add([
            'name'   => 'Test group'
        ]);
        $this->integer($gid)->isGreaterThan(0);

        $uid1 = getItemByTypeName('User', 'glpi', true);
        $uid2 = getItemByTypeName('User', 'tech', true);
        $user = new \User();
        $uid3 = (int)$user->add([
            'name'   => 'approval'
        ]);
        $this->integer($uid3)->isGreaterThan(0);
        $profile = new \Profile_User();
        $this->integer(
            (int)$profile->add([
                'users_id'     => $uid3,
                'profiles_id'  => getItemByTypeName('Profile', 'admin', true),
                'entities_id'  => 0
            ])
        )->isGreaterThan(0);

        $guser = new \Group_User();
        $this->integer(
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid1
            ])
        )->isGreaterThan(0);

        $guser = new \Group_User();
        $this->integer(
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid2
            ])
        )->isGreaterThan(0);

        $guser = new \Group_User();
        $this->integer(
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid3
            ])
        )->isGreaterThan(0);

        /** Create a rule on ticket creation and update that will
         * request an approval from previouly created group */
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $condition  = \RuleTicket::ONUPDATE + \RuleTicket::ONADD;
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => "test rule add",
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $gid
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'add_validation',
            'field'       => 'groups_id_validate',
            'value'       => $gid
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        /** Create a ticket, no approval requested */
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => "test ticket, will not trigger on rule",
            'content' => "test"
        ]);
        $tid = $tickets_id; //keep trace of this one
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::NONE);

        $this->integer(countElementsInTable(
            \TicketValidation::getTable(),
            ['tickets_id' => $tickets_id]
        ))->isEqualTo(0);

        /** Create a ticket, approval requested */
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'               => "test ticket, approval will be added",
            'content'            => "test",
            '_groups_id_assign'  => $gid
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        $this->integer(countElementsInTable(
            \TicketValidation::getTable(),
            ['tickets_id' => $tickets_id]
        ))->isEqualTo(2);

        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::WAITING);

        $ticket->getFromDB($tid);
        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::NONE);

       // update ticket title and trigger rule on title updating
        $this->boolean(
            $ticket->update([
                'id'                 => $tid,
                'name'               => 'test ticket, approval will be also added',
                '_itil_assign'       => ['_type' => 'group', 'groups_id' => $gid],
                'global_validation'  => \CommonITILValidation::NONE
            ])
        )->isTrue();

        $this->integer(countElementsInTable(
            \TicketValidation::getTable(),
            ['tickets_id' => $tid]
        ))->isEqualTo(2);

        $this->boolean($ticket->getFromDB($tid))->isTrue();
        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::WAITING);

        $this->login('glpi', 'glpi');
        $ticket->getFromDB($tid);

        $validation = new \TicketValidation();
        $this->boolean(
            $validation->getFromDBByCrit([
                'tickets_id'         => $tid,
                'users_id_validate'  => getItemByTypeName('User', 'glpi', true)
            ])
        )->isTrue();

        $this->boolean(
            $validation->update([
                'id'           => $validation->fields['id'],
                'tickets_id'   => $tid,
                'status'       => \CommonITILValidation::ACCEPTED
            ])
        )->isTrue();

        $this->boolean($ticket->getFromDB($tid))->isTrue();
        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::ACCEPTED);

       //refuse other one
        $validation = new \TicketValidation();
        $this->boolean(
            $validation->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'users_id_validate'  => getItemByTypeName('User', 'glpi', true)
            ])
        )->isTrue();

        $res = $validation->update([
            'id'           => $validation->fields['id'],
            'tickets_id'   => $tickets_id,
            'status'       => \CommonITILValidation::REFUSED
        ]);

        $this->hasSessionMessages(ERROR, ['If approval is denied, specify a reason.']);
        $this->boolean($res)->isFalse();

       //retry with comment / img paste and doc upload
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
        $filename_img = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $filename_txt = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename_img);
        copy(__DIR__ . '/../fixtures/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename_txt);

        $this->boolean(
            $validation->update([
                'id'                 => $validation->fields['id'],
                'tickets_id'         => $tickets_id,
                'status'             => \CommonITILValidation::REFUSED,
                'comment_validation' => 'Meh &lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000"'
            . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
                '_filename' => [
                    $filename_img,
                    $filename_txt
                ],
                '_tag_filename' => [
                    '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
                    '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
                ],
                '_prefix_filename' => [
                    '5e5e92ffd9bd91.11111111',
                    '5e5e92ffd9bd91.11111111',
                ]
            ])
        )->isTrue();

       //check document upload
        $this->integer(countElementsInTable(
            \Document_Item::getTable(),
            ['itemtype' =>  \TicketValidation::getType()]
        ))->isEqualTo(2);

        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::REFUSED);

       //require 100% for global status to be changed
        /** Create a ticket, approval requested */
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'               => "test ticket, approval will be added",
            'content'            => "test",
            '_groups_id_assign'  => $gid,
            'validation_percent' => 100
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        $this->integer(countElementsInTable(
            \TicketValidation::getTable(),
            ['tickets_id' => $tickets_id]
        ))->isEqualTo(2);

        $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::WAITING);

       /* FIXME: works well from UI, but not from here
       $this->login('glpi', 'glpi');
       $ticket->getFromDB($tickets_id);

       $validation = new \TicketValidation();
       $this->boolean(
         $validation->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'users_id_validate'  => getItemByTypeName('User', 'glpi', true)
         ])
       )->isTrue();

       $this->boolean(
         $validation->update([
            'id'           => $validation->fields['id'],
            'tickets_id'   => $tickets_id,
            'status'       => \CommonITILValidation::ACCEPTED
         ])
       )->isTrue();

       $this->boolean($ticket->getFromDB($tid))->isTrue();
       $this->integer((int)$ticket->getField('global_validation'))->isEqualTo(\CommonITILValidation::WAITING);*/
    }

    protected function testComputeValidationProvider(): array
    {
        return [
         // 100% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 90,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 100,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::REFUSED,
            ],
         // 50% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 50,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 50,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 60,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::REFUSED,
            ],
         // 0% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::REFUSED,
            ],
        ];
    }

    /**
     * @dataprovider testComputeValidationProvider
     */
    public function testComputeValidation(
        int $accepted,
        int $refused,
        int $validation_percent,
        int $result
    ): void {
        $test_result = \CommonITILValidation::computeValidation(
            $accepted,
            $refused,
            $validation_percent
        );

        $this->integer($test_result)->isEqualTo($result);
    }

    public function testCanValidateUser()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'      => 'testCanValidateUser',
            'content'   => 'testCanValidateUser',
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $validation = new \TicketValidation();

        // Test the current user cannot approve since there are no approvals
        $this->boolean($validation::canValidate($tickets_id))->isFalse();

        // Add user approval for current user
        $validations_id_1 = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'User',
            'items_id_target'       => $_SESSION['glpiID'],
            'comment_submission'    => 'testCanValidateUser',
        ]);
        $this->integer($validations_id_1)->isGreaterThan(0);
        $this->boolean($validation::canValidate($tickets_id))->isTrue();

        // Add user approval for other  user
        $validation = new \TicketValidation();
        $validations_id_2 = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'User',
            'items_id_target'       => $_SESSION['glpiID'] + 1, // Other user. Doesn't need to exist
            'comment_submission'    => 'testCanValidateUser',
        ]);
        $this->integer($validations_id_2)->isGreaterThan(0);

        // Test the current user can still approve since they still have an approval
        $this->boolean($validation::canValidate($tickets_id))->isTrue();
        // Remove user approval for current user
        $this->boolean($validation->delete(['id' => $validations_id_1]))->isTrue();
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->boolean($validation::canValidate($tickets_id))->isFalse();
    }

    public function testCanValidateGroup()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'      => 'testCanValidateGroup',
            'content'   => 'testCanValidateGroup',
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        $validation = new \TicketValidation();

        // Test the current user cannot approve since there are no approvals
        $this->boolean($validation::canValidate($tickets_id))->isFalse();

        // Create a test group
        $group = new \Group();
        $groups_id = $group->add([
            'name' => 'testCanValidateGroup',
        ]);
        $this->integer($groups_id)->isGreaterThan(0);

        // Add current user to the group
        $group_user = new \Group_User();
        $this->integer($group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $_SESSION['glpiID'],
        ]))->isGreaterThan(0);

        // Add approval for user's group
        $validations_id_1 = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'Group',
            'items_id_target'       => $groups_id,
            'comment_submission'    => 'testCanValidateGroup',
        ]);
        $this->integer($validations_id_1)->isGreaterThan(0);
        $this->boolean($validation::canValidate($tickets_id))->isTrue();

        // Add approval for other group
        $validation = new \TicketValidation();
        $validations_id_2 = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'Group',
            'items_id_target'       => $groups_id + 1, // Other group. Doesn't need to exist
            'comment_submission'    => 'testCanValidateGroup',
        ]);
        $this->integer($validations_id_2)->isGreaterThan(0);

        // Test the current user can still approve since they still have an approval
        $this->boolean($validation::canValidate($tickets_id))->isTrue();
        // Remove approval for current user's group
        $this->boolean($validation->delete(['id' => $validations_id_1]))->isTrue();
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->boolean($validation::canValidate($tickets_id))->isFalse();
    }

    public function testIsCurrentUserValidationTarget()
    {
        $this->login();

        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name'      => 'testIsCurrentUserValidationTarget',
            'content'   => 'testIsCurrentUserValidationTarget',
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Add validation for current user
        $validation = new \TicketValidation();
        $validations_id = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'User',
            'items_id_target'       => $_SESSION['glpiID'],
            'comment_submission'    => 'testIsCurrentUserValidationTarget',
        ]);
        $this->integer($validations_id)->isGreaterThan(0);

        // Test the current user is the validation target
        $this->boolean($validation->isCurrentUserValidationTarget())->isTrue();

        // Delete validation
        $this->boolean($validation->delete(['id' => $validations_id]))->isTrue();

        // Create a test group
        $group = new \Group();
        $groups_id = $group->add([
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name' => 'testIsCurrentUserValidationTarget',
        ]);
        $this->integer($groups_id)->isGreaterThan(0);
        // Add current user to the group
        $group_user = new \Group_User();
        $this->integer($group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $_SESSION['glpiID'],
        ]))->isGreaterThan(0);

        // Force reload of group memberships is current session
        \Session::loadGroups();

        // Add validation for group
        $validations_id = $validation->add([
            'tickets_id'            => $tickets_id,
            'itemtype_target'       => 'Group',
            'items_id_target'       => $groups_id,
            'comment_submission'    => 'testIsCurrentUserValidationTarget',
        ]);
        $this->integer($validations_id)->isGreaterThan(0);

        // Test the current user is the validation target
        $this->boolean($validation->isCurrentUserValidationTarget(true))->isTrue();
        // Test the current user is not the validation target when groups are not considered
        $this->boolean($validation->isCurrentUserValidationTarget(false))->isFalse();
    }
}
