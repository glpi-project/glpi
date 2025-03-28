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
use CommonITILValidation;
use Glpi\PHPUnit\Tests\CommonITILValidationTest;
use Glpi\PHPUnit\Tests\Glpi\ValidationStepTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for src/TicketValidation.php */
class TicketValidationTest extends CommonITILValidationTest
{
    use ValidationStepTrait;

    /**
     * @todo Move to CommonITILValidation test file when the Change Rule Engine is implemented
     * @todo Split in multilple tests (hard to understand and maintain) (multiple tickets, test dependent on previous tests actions)
     *
     * - create a user group, add 2 users in this group
     * - create a rule on ticket creation, this rules is triggered if ticket is assigned to the created group, it creates a validation request
     * - create a ticket, not assign to the group -> no validation created
     * - create a ticket, assign it to the group -> validation request is created, it's status is WAITING
     * - ...
     */
    public function testGroupUserApproval(): void
    {
        $this->login();

        /** Create a group with two users */
        $group = new \Group();
        $gid = (int)$group->add([
            'name'   => 'Test group'
        ]);
        $this->assertGreaterThan(0, $gid);

        $uid1 = getItemByTypeName('User', 'glpi', true);
        $user = new \User();
        $uid2 = (int)$user->add([
            'name'      => 'approval',
            'password'  => 'approval',
            'password2' => 'approval'
        ]);
        $this->assertGreaterThan(0, $uid2);
        $profile = new \Profile_User();
        $this->assertGreaterThan(
            0,
            (int)$profile->add([
                'users_id'     => $uid2,
                'profiles_id'  => getItemByTypeName('Profile', 'admin', true),
                'entities_id'  => 0
            ])
        );

        $guser = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid1
            ])
        );

        $guser = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid2
            ])
        );

        /** Create a rule on ticket creation and update that will
         * request an approval from previously created group */
        $ruleticket = new \RuleTicket();
        $rulecrit = new \RuleCriteria();
        $condition = \RuleTicket::ONUPDATE + \RuleTicket::ONADD;
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name' => "test rule add",
            'match' => 'AND',
            'is_active' => 1,
            'sub_type' => 'RuleTicket',
            'condition' => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id' => $ruletid,
            'criteria' => '_groups_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => $gid
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id' => $ruletid,
            'action_type' => 'add_validation',
            'field' => 'groups_id_validate',
            'value' => $gid
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        /** Create a ticket, no approval requested */
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name' => "test ticket, will not trigger on rule",
            'content' => "test",
        ]);
        $tid = $tickets_id; //keep trace of this one
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals(\CommonITILValidation::NONE, (int)$ticket->fields['global_validation']);

        $this->assertEquals(
            0,
            countElementsInTable(
                \TicketValidation::getTable(),
                ['tickets_id' => $tickets_id]
            )
        );

        /** Create a ticket, approval requested */
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name' => "test ticket, approval will be added",
            'content' => "test",
            '_groups_id_assign' => $gid
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        $this->assertEquals(
            2,
            countElementsInTable(
                \TicketValidation::getTable(),
                ['tickets_id' => $tickets_id]
            )
        );

        $this->assertEquals(\CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        $ticket->getFromDB($tid);
        $this->assertEquals(\CommonITILValidation::NONE, (int)$ticket->fields['global_validation']);

        // update ticket title and trigger rule on title updating
        $this->assertTrue(
            $ticket->update([
                'id' => $tid,
                'name' => 'test ticket, approval will be also added',
                '_itil_assign' => ['_type' => 'group', 'groups_id' => $gid],
                'global_validation' => \CommonITILValidation::NONE
            ])
        );

        $this->assertEquals(
            2,
            countElementsInTable(
                \TicketValidation::getTable(),
                ['tickets_id' => $tid]
            )
        );

        $this->assertTrue($ticket->getFromDB($tid));
        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        $this->assertTrue($ticket->getFromDB($tid));

        // accept first validation - implies that validation required is at 0%
        $this->login('glpi', 'glpi');

        $validation = new \TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tid,
                'itemtype_target' => 'User',
                'items_id_target' => $uid1,
            ])
        );

        // update itil_validation step to require 0%, so the first validation ACCEPTED will cause the ticket global_validation to be ACCEPTED
        $this->updateItem(
            \TicketValidationStep::class,
            $validation->fields['itils_validationsteps_id'],
            ['minimal_required_validation_percent' => 0]
        );

        // update created validation status to ACCEPTED
        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'status' => \CommonITILValidation::ACCEPTED
            ])
        );

        $this->assertTrue($ticket->getFromDB($tid));
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);

        // refuse other one
        $this->login('approval', 'approval');
        $validation = new \TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id,
                'itemtype_target' => 'User',
                'items_id_target' => $uid2,
            ])
        );
        $res = $validation->update([
            'id' => $validation->fields['id'],
            'status' => \CommonITILValidation::REFUSED
        ]);
        $this->hasSessionMessages(ERROR, ['If approval is denied, specify a reason.']);
        $this->assertFalse($res);

        //retry with comment / img paste and doc upload
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));
        $filename_img = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $filename_txt = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename_img);
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename_txt);
        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'tickets_id' => $tickets_id,
                'status' => \CommonITILValidation::REFUSED,
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
        );

        // check document upload
        $this->assertEquals(
            2,
            countElementsInTable(
                \Document_Item::getTable(),
                ['itemtype' =>  \TicketValidation::getType()]
            )
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, (int)$ticket->fields['global_validation']);

        // require 100% for global status to be changed
        assert(100 === $this->getInitialDefaultValidationStep()->fields['minimal_required_validation_percent']);
        /** Create a ticket, approval requested */
        $ticket = new \Ticket();
        $tickets_id_2 = $ticket->add($ticket_input = [
            'name' => "test ticket, approval will be added",
            'content' => "test",
            '_groups_id_assign' => $gid,
//            'validation_percent' => 100 // now ignored, defined in itil validation step
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id_2, $ticket_input);

        $this->assertEquals(
            2,
            countElementsInTable(
                \TicketValidation::getTable(),
                ['tickets_id' => $tickets_id_2]
            )
        );

        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        // accept first validation, second one is still WAITING - test on $tickets_id_2
        // one validation is accepted, the other is waiting -> global_validation status should be WAITING
        $this->login('glpi', 'glpi');
        $validation = new \TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id_2,
                'itemtype_target' => 'User',
                'items_id_target' => $uid1,
            ])
        );

        // update itil validation step to require 50%, so next assertion returns WAITING, and the seconde return ACCEPTED
        // find ticket itil_validationstep -> update it
        $validation = new \TicketValidation();
        $validation->getFromDBByCrit([
            'tickets_id' => $tickets_id_2,
            'itemtype_target' => 'User',
            'items_id_target' => $uid1,
        ]);
        $this->updateItem(
            \TicketValidationStep::class,
            $validation->fields['itils_validationsteps_id'],
            ['minimal_required_validation_percent' => 50]
        );

        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'status' => \CommonITILValidation::ACCEPTED
            ])
        );

        // reload ticket because global_validation is updated at Validation update
        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);

        // accept second one, both are accepted -> global_validation status should be ACCEPTED
        $this->login('approval', 'approval');
        $validation = new \TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id_2,
                'itemtype_target' => 'User',
                'items_id_target' => $uid2,
            ])
        );

        $res = $validation->update([
            'id' => $validation->fields['id'],
            'status' => \CommonITILValidation::ACCEPTED
        ]);

        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);
    }

    public function testValidationStatusAfterRefusedAndNewRequest()
    {
        $this->login();

        // Create a ticket
        $ticket = $this->createItem(\Ticket::class, [
            'name'               => "Test validation status transition",
            'content'            => "Test content",
        ]);

        // Add first validation request
        $validation = $this->createItem(\TicketValidation::class, [
            'tickets_id'        => $ticket->getID(),
            'itemtype_target' => \User::class,
            'items_id_target' => getItemByTypeName('User', 'tech', true),
            'comment_submission' => 'Please validate this ticket'
        ]);

        // Check that global validation status is WAITING
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals(
            \CommonITILValidation::WAITING,
            (int)$ticket->getField('global_validation')
        );

        // Login as tech to refuse the validation
        $this->login('tech', 'tech');

        // Refuse the validation
        $this->updateItem($validation::class, $validation->getID(), [
            'status'             => \CommonITILValidation::REFUSED,
            'comment_validation' => 'I refuse this validation'
        ]);

        // Check that global validation status is now REFUSED
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals(
            \CommonITILValidation::REFUSED,
            (int)$ticket->getField('global_validation')
        );

        // Login back as normal admin
        $this->login();

        // Add another validation request
        $this->createItem($validation::class, [
            'tickets_id'        => $ticket->getID(),
            'itemtype_target' => \User::class,
            'items_id_target' => getItemByTypeName('User', 'tech', true),
            'comment_submission' => 'Please validate this ticket (second attempt)'
        ]);

        // Check that global validation status is now back to WAITING
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals(
            \CommonITILValidation::WAITING,
            (int)$ticket->getField('global_validation')
        );
    }

    /**
     * Status computation is done on testComputeXXXTests()
     * Here, test that ticket global_validation is updated when a validation status is updated
     */
    public function testTicketValidationStatusUpdated()
    {
        // add a validation in same step
        $vs = $this->createValidationStep(50);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [\CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $this->addITILValidationStepWithValidations($vs, [CommonITILValidation::ACCEPTED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $itil->fields['global_validation']);

        // add a validation in a new step (same code as above but with a new validation step)
        $vs = $this->createValidationStep(0);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [\CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $vs2 = $this->createValidationStep(0);
        $this->addITILValidationStepWithValidations($vs2, [CommonITILValidation::REFUSED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $itil->fields['global_validation']);

        // remove a validation (same as above but with a validation removed)
        $vs = $this->createValidationStep(0);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [\CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $vs2 = $this->createValidationStep(0);
        $ivs = $this->addITILValidationStepWithValidations($vs2, [CommonITILValidation::REFUSED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $itil->fields['global_validation']);
        $validation = $itil::getValidationClassInstance();
        assert(true === $validation->getFromDBByCrit([$itil::getForeignKeyField() => $itil->getID(), 'itils_validationsteps_id' => $ivs->getID()])); // find validation
        assert(true === $validation->delete(['id' => $validation->getID()])); // delete validation
        assert(true === $itil->getFromDB($itil->getID())); // reload itil
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $itil->fields['global_validation']);

        // update a validation
        $vs = $this->createValidationStep(100);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [\CommonITILValidation::WAITING]);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $validation = $itil::getValidationClassInstance();
        assert(true === $validation->getFromDBByCrit([$itil::getForeignKeyField() => $itil->getID()]));
        assert(true === $validation->update(['id' => $validation->getID(), 'status' => CommonITILValidation::ACCEPTED]));
        assert(true === $itil->getFromDB($itil->getID()));
        assert(CommonITILValidation::ACCEPTED === $itil->fields['global_validation']);

        // update a validation step required percent
        $vs = $this->createValidationStep(100);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [\CommonITILValidation::WAITING, CommonITILValidation::ACCEPTED]);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        assert(CommonITILValidation::WAITING === $itil->fields['global_validation']);
        // update itils_validationstep to require 100%
        $ivs->update(['id' => $ivs->getID(), 'minimal_required_validation_percent' => 50]);
        $itil->getFromDB($itil->getID());
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $itil->fields['global_validation']);
    }

    public static function testgetNumberToValidateProvider(): array
    {
        return [
            [
                'input'     => [
                    'name'      => 'Ticket_Closed_With_Validation_Request',
                    'content'   => 'Ticket_Closed_With_Validation_Request',
                ],
                'expected'  => true,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
            [
                'input'     => [
                    'name' => 'Ticket_With_Validation_Request',
                    'content' => 'Ticket_With_Validation_Request',
                    'status' =>  CommonITILObject::SOLVED
                ],
                'expected'  => false,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
            [
                'input'     => [
                    'name' => 'Ticket_With_Validation_Request',
                    'content' => 'Ticket_With_Validation_Request',
                    'status' =>  CommonITILObject::CLOSED
                ],
                'expected'  => false,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
        ];
    }

    #[DataProvider('testgetNumberToValidateProvider')]
    public function testgetNumberToValidate(
        array $input,
        bool $expected,
        int $user_id
    ): void {
        $this->login();

        $initial_count = \TicketValidation::getNumberToValidate($user_id);

        /** Create a ticket, approval requested */
        $ticket = $this->createItem('Ticket', $input);

//        $itils_validationsteps_id = ;
        $this->createItem('TicketValidation', [
            'tickets_id'      => $ticket->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $user_id,
            '_validationsteps_id' => $this->getInitialDefaultValidationStep()->getID()
        ]);

        $this->assertEquals($expected ? ($initial_count + 1) : $initial_count, \TicketValidation::getNumberToValidate($user_id));
    }

    public function testcomputeValidationStatusReturnNone(): void
    {
        $ticket = $this->createItem(\Ticket::class, ['name' => 'Ticket1', 'content' => 'Ticket1']);
        $this->assertEquals(\CommonITILValidation::NONE, \TicketValidation::computeValidationStatus($ticket));
    }

    /**
     * One validation is REFUSED : the ticket global_validation is REFUSED
     */
    public function testComputeValidationStatusReturnRefused(): void
    {
        // ticket with one refused itil validation step
        $vs50 = $this->createValidationStep(50);
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::REFUSED]);
        // check created itil_validation step status is REFUSED before testing
        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, \TicketValidation::computeValidationStatus($ticket));

        // + an accepted itil validation step (use previous ticket)
        $vs2 = $this->createValidationStep(50);
        $itil_vs = $this->addITILValidationStepWithValidations($vs2, [\CommonITILValidation::ACCEPTED], $ticket);
        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, \TicketValidation::computeValidationStatus($ticket));

        // ticket with a waiting + an accepted + refused validation step
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::WAITING]);

        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [\CommonITILValidation::REFUSED], $ticket);

        $vs100_2 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_2, [\CommonITILValidation::ACCEPTED], $ticket);

        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, \TicketValidation::computeValidationStatus($ticket));
    }

    /**
     * One validation is WAITING : the ticket global_validation is WAITING
     */
    public function testComputeValidationStatusReturnWaiting(): void
    {
        // ticket with one waiting itil validation step
        $vs50 = $this->createValidationStep(50);
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::WAITING]);
        assert(\CommonITILValidation::WAITING === \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to create validation step with WAITING status');
        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, \TicketValidation::computeValidationStatus($ticket));

        // + an accepted itil validation step (use previous ticket)
        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [\CommonITILValidation::ACCEPTED], $ticket);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to add validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, \TicketValidation::computeValidationStatus($ticket));

        // second test
        // ticket with an accepted + waiting itil validation step
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::ACCEPTED]);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to create validation step with ACCEPTED status');

        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [\CommonITILValidation::WAITING], $ticket);
        assert(\CommonITILValidation::WAITING ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to add validation step with WAITING status');

        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, \TicketValidation::computeValidationStatus($ticket));
    }

    /**
     * All validations are ACCEPTED : the ticket global_validation is ACCEPTED
     */
    public function testComputeValidationStatusReturnAccepted(): void
    {
        // ticket with one ACCEPTED itil validation step
        $vs50 = $this->createValidationStep(50);
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::ACCEPTED]);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to create validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \TicketValidation::computeValidationStatus($ticket));

        // many validation step  (use previous ticket)
        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [\CommonITILValidation::ACCEPTED], $ticket);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to add validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \TicketValidation::computeValidationStatus($ticket));

        // + another one
        $vs100_2 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_2, [\CommonITILValidation::ACCEPTED], $ticket);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to add validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \TicketValidation::computeValidationStatus($ticket));

        // + another one
        $vs100_3 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_3, [\CommonITILValidation::ACCEPTED], $ticket);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to add validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \TicketValidation::computeValidationStatus($ticket));

        // ticket with a refused + an accepted validation step, then remove the refused validation
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::REFUSED]);
        $tvs = $this->addITILValidationStepWithValidations($vs100, [\CommonITILValidation::ACCEPTED], $ticket);
        assert(\CommonITILValidation::REFUSED === \TicketValidation::computeValidationStatus($ticket));
        // find and delete the refused validation
        $tv = new \TicketValidation();
        $tv->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'itils_validationsteps_id' => $itil_vs->getID()]);
        assert($tv->delete(['id' => $tv->getID()]));
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \TicketValidation::computeValidationStatus($ticket));
    }

    /**
     * - create a ticket with a validated state.
     * - update a validation step
     * - check ticket validation status has changed
     */
    public function testTicketValidationChangesWhenValidationStepPercentageIsChanged(): void
    {
        // arrange
        $vs50 = $this->createValidationStep(50);
        [$ticket, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [\CommonITILValidation::ACCEPTED, \CommonITILValidation::REFUSED]);
        assert(\CommonITILValidation::ACCEPTED ===  \TicketValidationStep::getITILValidationStepStatus($itil_vs->getID()), 'failed to create validation step with ACCEPTED status');
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, $ticket->fields['global_validation']);

        // act - update itil validation step
        $this->updateItem($itil_vs::class, $itil_vs->getID(), ['minimal_required_validation_percent' => 100]);

        // assert
        $ticket->getFromDB($ticket->getID());
        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, $ticket->fields['global_validation']);
    }
}
