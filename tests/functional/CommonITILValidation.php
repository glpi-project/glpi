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

/* Test for inc/commonitilvalidation.class.php */

class CommonITILValidation extends DbTestCase
{
    public function testGroupApproval()
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
}
