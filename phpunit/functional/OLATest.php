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

use CommonITILActor;
use CommonITILObject;
use DateInterval;
use DateTime;
use DbTestCase;
use Glpi\PHPUnit\Tests\Glpi\ITILTrait;
use Glpi\PHPUnit\Tests\Glpi\SLMTrait;
use Group;
use Group_User;
use Item_Ola;
use OLA;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Log\LogLevel;
use Rule;
use RuleBuilder;
use RuleCommonITILObject;
use RuleTicket;
use RuntimeException;
use SLM;
use Ticket;
use User;

/**
 * Test Plan / Spec
 *
 * - ola data are retrieved : @see self::testGetOLAData()
 * - ola can be associated and deassociate to a ticket :
 *      - single OLA
 *          - at creation time :@see self::testAssociateSingleOlaOnCreation()
 *          - at update time :@see self::testAssociateSingleOlaOnUpdate()
 *      - muliple OLA
 *          - at creation time :@see self::testAssociateMultipleOlasOnCreation()
 *          - at update time :@see self::testAssociateMultipleOlaOnUpdate()
 *      - ola are unchanged when no ola form input is specified : @see self::testUpdateTicketWithoutOlaInputs()
 *      - existing ola associations can be changed : @see self::testUpdateTicketOlas()
 *      - multiple times the same ola results in a single association : @see self::testUpdateTicketWithDuplicatedOlasInputs()
 *      - Create and update a ticket with old form params (olas_id_tto, olas_id_ttr) still works :
 *          - on creation : @see self::testCreateTicketWithOldFormParams()
 *          - on update : @see self::testUpdateTicketWithOldFormParams()
 *          - when both old and new form params are passed, the new ones are used (preserve plugins behavior): @see self::testCreateTicketWithBothOldAndNewInputFields()
 * - passing removed parameters throws execption ('ola_tto_begin_date', 'ola_ttr_begin_date', ...)
 *      - on create ticket : @see self::testCreateTicketWithOlaRemovedFieldsThrowsAnExecption()
 *      - on update ticket : @see self::testUpdateTicketWithOlaRemovedFieldsThrowsAnExecption()
 * - ola association with a group :
 *      - succeed when group has ability to be assigned to a ticket and fails if it hasn't the ability,
 *          - when ola is created @see self::testOlaCanBeAssociatedWithAnAllowedGroupOnAdd()
 *          - when ola is updated @see self::testOlaCanBeAssociatedWithAnAllowedGroupOnUpdate()
 *      - Association can be done when no group is associated to the ola (migrated olas) : @see self::testOlaAssociationCanBeDoneWhenNoGroupIsAssociatedToOla()
 *
 * - time computing & completion
 *      - TTO (Time To Own)
 *          - ola tto is associated with a ticket then 'start_time' is set to ticket date & 'due_time' is calculated (group is not taken into account)
 *              - on ticket creation : @see self::testInitialOlaTtoValuesOnCreation()
 *              - on ticket update : @see self::testInitialOlaTtoValuesOnUpdate()
 *
 *          - completion :
 *              - is done when the dedicated group is assigned to the ticket : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToDedicatedGroup()
 *              - is     done when a user of the dedicated group is assigned to the ticket by a user of the ola group       : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfOlaGroup()
 *              - is not done when a user of the dedicated group is assigned to the ticket by foreign user of the ola group : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfAnotherGroup()
 *              - is not done when a non dedicated group is assigned to the ticket : @see self::testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup()
 *              - is not done when a user not in the dedicated group is assigned to the ticket : @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserNotInDedicatedGroup()
 *              - same with group & ola assigned by rule : @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRule()
 *              - is not done when the group is added and the ola added by rule : @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule()
 *              - is done when a group associated to ola is removed from ticket assigned group :
 *                  - @see self::testOlaTtoIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup()
 *                  - @see self::testOlaTtoIstCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRuleThenGroupRemoved()
 *              - is done when ticket is "taken into account" (add task, add followup) by a user of the ola group :
 *                  - @see self::testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithAFollowup()
 *                  - @see self::testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithATask()
 *              - is not done when ticket is updated(add task, add followup) by a user not in the ola group : @see self::testOlaTtoIsNotCompleteWhenUserNotOfGroupAssociatedToOlaTakesTicketIntoAccount()
 *
 *          - delay :
 *              - due time is not delayed if the ticket status is WAITING and ticket is not assigned to group: @see self::testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup()
 *              - due time is delayed if the ticket status is WAITING and ticket is assigned to group: @see self::testOlaTTODueTimeIsDelayedWhileTicketStatusIsWaitingAndAssignedToOlaGroup()
 *              - waiting time is not incremented while the ticket is WAITING and assigned to ola group @see self::testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaitingAndAssignedToOlaGroup()
 *              - waiting time is incremented while the ticket is WAITING and not assigned to ola group @see self::testOlaTTOWaitingTimeIsIncrementedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup()
 *
 *          - ola can be associated by rule and form at the same time @see self::testOlaCanBeAssociatedByRulesAndByForm()
 *          - due_time is not updated on ticket date update @see self::testOlaTtoDueTimeIsNotUpdatedOnTicketDateUpdate()
 *
 *      - TTR (Time To Resolve)
 *          - ola ttr is associated with a ticket then 'start_time' is set to ticket 'date' & 'due_time' is calculated, however the group (or a user in the group) is not assigned to the ticket yet
 *              - on creation : @see self::testInitialOlaTtrValuesOnCreation()
 *              - on update : @see self::testInitialOlaTtrValuesOnUpdate()
 *
 *          - completion :
 *              - is done when the ticket is closed (end_time is set): @see self::testOlaTtrIsCompleteWhenTicketStatusChanges()
 *              - is done when the ticket is solved : @see self::testOlaTtrIsCompleteWhenTicketStatusChanges()
 *              - is done when a group associated to ola is removed from ticket assigned group : @see self::testOlaTtrIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup()
 *
 *          - delay :
 *              - ttr due time is delayed if the ticket status is WAITING : @see self::testOlaTtrDueTimeIsDelayedWhileTicketStatusIsWaiting
 *              - ttr waiting time is incremented while the ticket status is WAITING : @see self::testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
 *
 *          - ticket is late if : (business logic extracted from CommonITILObject::generateSLAOLAComputation())*
 *              - tto : (note that takeintoaccountdate is replaced by end_time)
 *                - end_time is defined and > due_time - @see self::testOlaTtoIsLateWhenEndTimeIsAfterDueTime()
 *                - end_time is not defined & due_time is passed - @see self::testOlaTtoIsLateWhenDueTimeIsPassed()
 *                - ticket status is not WAITING (1): @see self::testOlaTtoIsNotLateWhenTicketStatusIsNotWaiting()
 *
 *              - ttr : exactly the same tests as above, duplication to be prepared for future changes
 *                 - end_time is defined and > due_time - @see self::testOlaTtrIsLateWhenEndTimeIsAfterDueTime()
 *                 - end_time is not defined & due_time is passed - @see self::testOlaTtrIsLateWhenDueTimeIsPassed()
 *                 - ticket status is not WAITING (1): @see self::testOlaTtrIsNotLateWhenTicketStatusIsNotWating()
 *
 *          - due_time is not updated on ticket date update @see self::testOlaTtrDueTimeIsNotUpdatedOnTicketDateUpdate()
 *          - when completion is done, the associated group is removed from ticket assignees : not implemented, seems not relevant atm
 */

class OLATest extends DbTestCase
{
    use SLMTrait;
    use ITILTrait;

    public function testGetOLAData()
    {
        $this->login();
        // arrange
        $ticket = $this->createTicket();
        ['ola' => $ola_tto1, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_tto2] = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);

        $association_data = [
            'items_id' => $ticket->getID(),
            'itemtype' => $ticket::class,
            'start_time' => date('Y-m-d H:i:s'),
        ];

        // act - create associations
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto2->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_ttr->getID(), 'ola_type' => SLM::TTR] + $association_data);

        // assert - check if the ticket has the 3 OLA associated
        $ticket = $this->reloadItem($ticket);
        $this->assertCount(3, $ticket->getOlasData(), 'Expected 3 OLA associated with ticket, but ' . count($ticket->getOlasData()) . ' found');
        $this->assertCount(2, $ticket->getOlasTTOData(), 'Expected 2 OLA TTO associated with ticket, but found different results');
        $this->assertCount(1, $ticket->getOlasTTRData(), 'Expected 1 OLA TTR associated with ticket, but found different results');
    }

    public function testAssociateSingleOlaOnCreation(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA()['ola'];

        // act - create ticket with OLA
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()],]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Failed to associate with ticket on creation');
    }

    public function testAssociateSingleOlaOnUpdate(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        $ola = $this->createOLA()['ola'];

        // act - update ticket with OLA
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->reloadItem($ticket);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Failed to associate with ticket on update');
    }

    public function testAssociateMultipleOlasOnCreation(): void
    {
        // arrange - create 3 OLAs
        $this->login();
        ['ola' => $ola1, 'slm' => $slm, 'group' => $group] = $this->createOLA();
        $ola2 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $ola3 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $olas_ids = [$ola1->getID(), $ola2->getID(), $ola3->getID()];

        // act - create ticket with OLA
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => $olas_ids,]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing($olas_ids, $fetched_olas, 'Failed to associate multiple olas with ticket on creation');
    }

    public function testAssociateMultipleOlaOnUpdate(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        ['ola' => $ola1, 'slm' => $slm, 'group' => $group] = $this->createOLA();
        $ola2 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $ola3 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $olas_ids = [$ola1->getID(), $ola2->getID(), $ola3->getID()];

        // act - update ticket with OLA
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $olas_ids]);
        $ticket = $this->reloadItem($ticket);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing($olas_ids, $fetched_olas, 'Failed to associate multiple olas with ticket on creation');
    }

    public function testUpdateTicketWithoutOlaInputs(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        $ola = $this->createOLA()['ola'];
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // act - update ticket without OLA fields, note '_la_update' is not set
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['name' => $ticket->fields['name']]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Failed to keep OLA associated with ticket when no OLA inputs are specified on update');
    }

    public function testUpdateTicketOlas(): void
    {
        // arrange - add 3 olas to a ticket
        $this->login();
        $ticket = $this->createTicket();
        ['ola' => $ola1, 'slm' => $slm, 'group' => $group] = $this->createOLA();
        $ola2 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $ola3 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $olas_ids = [$ola1->getID(), $ola2->getID(), $ola3->getID()];
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $olas_ids]); // no check needed, tested before

        // act - remove just an ola from the ticket
        $updated_olas_ids = [$ola1->getID(), $ola3->getID()]; // $ola2 removed
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $updated_olas_ids]);
        $ticket = $this->reloadItem($ticket);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing($updated_olas_ids, $fetched_olas);
    }

    /**
     * When passing multiple OLA IDs to the ticket, the same OLA ID should not be passed associated multiple times
     * Just test for update, no need to test for create (process is in the same function)
     */
    public function testUpdateTicketWithDuplicatedOlasInputs(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        $ola = $this->createOLA()['ola'];

        // act - update ticket
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID(), $ola->getID()]]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Unexpected OLA associated with ticket, duplicated OLA IDs should be ignored');
    }

    /**
     * Backward compatibility test
     */
    public function testCreateTicketWithOldFormParams(): void
    {
        // arrange
        $this->login();
        ['ola' => $ola_tto, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        $ola_ttr = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm)['ola'];

        // act - update ticket
        $ticket = $this->createTicket(
            ['olas_id_tto' => $ola_tto->getID(), 'olas_id_ttr' => $ola_ttr->getID()],
            ['olas_id_tto', 'olas_id_ttr'] // do not exist anymore but here we check backward compatibility.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket using old form params on creation');

        $this->hasPhpLogRecordThatContains('Passing `olas_id_ttr` input to ticket is deprecated.', LogLevel::INFO);
        $this->hasPhpLogRecordThatContains('Passing `olas_id_tto` input to ticket is deprecated.', LogLevel::INFO);
    }

    public function testCreateTicketWithBothOldAndNewInputFields(): void
    {
        // arrange
        $this->login();
        ['ola' => $ola_new, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        $ola_new2 = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm)['ola'];
        $ola_ttr_old = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm)['ola'];
        $ola_tto_old = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm)['ola'];

        // act - update ticket
        $ticket = $this->createTicket(
            [
                '_olas_ids' => [$ola_new->getID(), $ola_new2->getID()],
                '_olas_update' => true,
                'olas_id_ttr' => $ola_ttr_old->getID(),
                'olas_id_tto' => $ola_tto_old->getID(),

            ],
            ['olas_id_tto', 'olas_id_ttr'] // do not exist anymore but here we check backward compatibility.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto_old->getID(), $ola_ttr_old->getID()], $fetched_olas, 'Ola passed with old form params should be used, not the new ones');

        $this->hasPhpLogRecordThatContains('Passing `olas_id_ttr` input to ticket is deprecated.', LogLevel::INFO);
        $this->hasPhpLogRecordThatContains('Passing `olas_id_tto` input to ticket is deprecated.', LogLevel::INFO);
    }

    public function testUpdateTicketWithOldFormParams(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        ['ola' => $ola_tto, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        $ola_ttr = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm)['ola'];

        // act - update ticket
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            ['olas_id_tto' => $ola_tto->getID(), 'olas_id_ttr' => $ola_ttr->getID()],
            ['olas_id_tto', 'olas_id_ttr'] // do not exist anymore but here we check backward compatibility, so updateItem must no fail.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket using old form params on update');

        $this->hasPhpLogRecordThatContains('Passing `olas_id_ttr` input to ticket is deprecated.', LogLevel::INFO);
        $this->hasPhpLogRecordThatContains('Passing `olas_id_tto` input to ticket is deprecated.', LogLevel::INFO);
    }

    /**
     * Create a ticket with removed fields trigger
     */
    public function testCreateTicketWithOlaRemovedFieldsThrowsAnExecption(): void
    {
        $this->login();
        $removed_fields = ['ola_tto_begin_date', 'ola_ttr_begin_date', 'internal_time_to_resolve', 'internal_time_to_own', 'olalevels_id_ttr'];

        foreach ($removed_fields as $removed_field) {
            $this->expectException(RuntimeException::class);
            $value = $removed_field === 'olalevels_id_ttr' ? 1 : date('Y-m-d 00:00:00');

            $this->createTicket(
                [$removed_field => $value],
                [$removed_field] // do not exist anymore but here we check backward compatibility, so createItem must no fail.
            );
        }
    }

    public function testUpdateTicketWithOlaRemovedFieldsThrowsAnExecption(): void
    {
        $this->login();
        $removed_fields = ['ola_tto_begin_date', 'ola_ttr_begin_date', 'internal_time_to_resolve', 'internal_time_to_own', 'olalevels_id_ttr'];
        $ticket = $this->createTicket();

        foreach ($removed_fields as $removed_field) {
            $this->expectException(RuntimeException::class);
            $value = $removed_field === 'olalevels_id_ttr' ? 1 : date('Y-m-d 00:00:00');

            $this->updateItem(
                Ticket::class,
                $ticket->getID(),
                [$removed_field => $value],
                [$removed_field] // do not exist anymore but here we check backward compatibility, so updateItem must no fail.
            );
        }
    }

    /**
     * An allowed group is a group that can be set as assigned to a ticket (Group > "visible in ticket" > "Assigned to")
     */
    #[TestWith([1, true])]
    #[TestWith([0, false])]
    public function testOlaCanBeAssociatedWithAnAllowedGroupOnAdd(int $is_assigned, bool $expected_add_return): void
    {
        // arrange
        $test_group = $this->createItem(Group::class, ['is_assign' => $is_assigned, 'name' => 'test group']);
        $slm = $this->createSLM();

        // act
        $inserted = (bool) (new OLA())->add([
            'groups_id' => $test_group->getID(),
            'slms_id' => $slm->getID(),
            'name' => 'OLA',
            'number_time' => 1,
            'definition_time' => 'hour',
        ]);

        // assert
        $expected_add_return
            ? $this->assertTrue($inserted, 'Failed to associate OLA with allowed group on add')
            : $this->hasSessionMessages(ERROR, ['The group #' . $test_group->getID() . ' is not allowed to be associated with an OLA. group.is_assign must be set to 1']);
    }

    // --- business tests

    #[TestWith([1, true])]
    #[TestWith([0, false])]
    public function testOlaCanBeAssociatedWithAnAllowedGroupOnUpdate(int $is_assigned, bool $expected_add_return): void
    {
        // arrange
        $allowed_group = $this->createItem(Group::class, ['is_assign' => 1, 'name' => 'allowed group']);
        $test_group = $this->createItem(Group::class, ['is_assign' => $is_assigned, 'name' => 'test group']);
        $slm = $this->createSLM();

        $_ola = $this->createItem(
            OLA::class,
            [
                'groups_id' => $allowed_group->getID(),
                'slms_id' => $slm->getID(),
                'name' => 'OLA',
                'number_time' => 1,
                'definition_time' => 'hour',
            ]
        );

        // act - update OLA with a group
        $update = $_ola->update([
            'id' => $_ola->getID(),
            'groups_id' => $test_group->getID(),
        ]);

        // assert
        $expected_add_return
            ? $this->assertTrue($update, 'Failed to associate OLA with allowed group on update')
            : $this->hasSessionMessages(ERROR, ['The group #' . $test_group->getID() . ' is not allowed to be associated with an OLA. group.is_assign must be set to 1']);
    }

    /**
     * Despite the association with a group is mandatory, migrated data have ola without group associated.
     */
    public function testOlaAssociationCanBeDoneWhenNoGroupIsAssociatedToOla(): void
    {
        global $DB;
        $this->login();

        // arrange : create an OLA without group association - direct db insertion to bypass validation
        $slm = $this->createSLM();
        //
        $ola_name = 'OLA ' . uniqid();
        $result = $DB->insert('glpi_olas', [
            'name' => $ola_name,
            'type' => SLM::TTR,
            'comment' => 'OLA comment ' . time(),
            'number_time' => 90,
            'definition_time' => 'minute',
            'slms_id' => $slm->getID(),
            'groups_id' => 0,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        assert(false !== $result, 'failed to insert OLA without group association');
        $ola = getItemByTypeName(OLA::class, $ola_name);

        // act - create ticket with OLA
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - check if the ticket has the OLA associated & no group associated with the ticket
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas);
        $this->assertEmpty($ticket->getGroups(CommonITILActor::class));
    }

    /**
     * - start_time is set using ticket 'date' field (which is the current time when not specified)
     * - due_time is set at the moment the Ola is assigned to the ticket
     * - endtime is set not set
     * - waiting_time is not set
     */
    public function testInitialOlaTtoValuesOnCreation(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        // ticket date is set to 30 minutes before current time
        $now = $this->setCurrentTime('2025-06-02 09:00:00');
        $ticket_date = $now->modify('- 30 minutes');

        // act associate ticket with ola
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()], 'date' => $ticket_date->format('Y-m-d H:i:s')]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time
        $this->assertEquals($ola_data['start_time'], $ticket_date->format('Y-m-d H:i:s'), 'Start time should be set to the \'date\' field of associated ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = $ticket_date->add($this->getDefaultOlaTtoDelayInterval());
        $this->assertEquals($ola_data['due_time'], $due_time_datetime->format('Y-m-d H:i:s'), 'Due time should be start_time + OLA_TTO_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');

        //        throw new \Exception('test de cohérence de type avec l ola origine');
    }

    /**
     * Same as above but ola is assigned at update time
     */
    public function testInitialOlaTtoValuesOnUpdate(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $this->setCurrentTime('2025-06-02 09:00:00');
        $ticket = $this->createTicket();
        assert(empty($ticket->getOlasData()), 'no OLA should be associated with ticket at this point');

        // act associate ticket with ola after 30 minutes
        $ola_association_datetime = $this->setCurrentTime('2025-06-02 09:30:00');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $ola_association_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = $ola_association_datetime->add($this->getDefaultOlaTtoDelayInterval());
        $this->assertEquals($ola_data['due_time'], $due_time_datetime->format('Y-m-d H:i:s'), 'Due time should be start_time + OLA_TTO_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    /**
     *  - endtime (completion) is set when ticket is assigned to a dedicated group
     */
    public function testOlaTtoIsCompleteWhenTicketIsAssignedToDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - wait 10 minutes, assign ticket to a dedicated group
        $group_assignation_datetime = $this->setCurrentTime('2025-06-10 09:10:00');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola_tto->fields['groups_id']]);
        assert($ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_tto->fields['groups_id']]), 'Ticket should be assigned to the dedicated group of the OLA.');
        $ola_data = $ticket->getOlasData()[0];

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $this->assertEquals($group_assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a dedicated group.');
    }

    public function testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfOlaGroup(): void
    {
        $this->login();

        // --- arrange - create a group, an ola, a user and set the user in the group, assign the ola to the ticket
        $ola_group = $this->createItem(Group::class, [
            'name' => 'Ola_group', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $ola_tto = $this->createOLA(ola_type: SLM::TTO, group: $ola_group)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket([
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ]);

        $new_user_name = $this->getUniqueString();
        $user = $this->createItem(
            User::class,
            [
                'name' => $new_user_name,
                'entities_id' => $this->getTestRootEntity(true)]
        );
        $user_group = new Group_User();
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $ola_tto->fields['groups_id']]);
        assert(Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User should be in the group of the OLA.');

        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found');
        // assign user to profile Technician, to allow ticket update
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        \Session::checkCentralAccess(); // ensure user can update ticket

        // --- act - wait 10 minutes, assign ticket to a dedicated group
        $this->login($new_user_name);
        $assignation_datetime = $this->setCurrentTime('2025-06-10 09:10:00');
        /** @var Ticket $ticket */
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.');

        // --- assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }
    public function testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfAnotherGroup(): void
    {
        $this->login();

        // --- arrange - create a group, an ola, a user and set the user in the group, assign the ola to the ticket
        $ola_group = $this->createItem(Group::class, [
            'name' => 'Ola_group', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $ola_tto = $this->createOLA(ola_type: SLM::TTO, group: $ola_group)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket([
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ]);

        $new_user_name = $this->getUniqueString();
        $user = $this->createItem(
            User::class,
            [
                'name' => $new_user_name,
                'entities_id' => $this->getTestRootEntity(true)]
        );
        $user_group = new Group_User();
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $ola_tto->fields['groups_id']]);
        assert(Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User should be in the group of the OLA.');

        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found');
        // assign user to profile Technician, to allow ticket update
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        \Session::checkCentralAccess(); // ensure user can update ticket

        // --- act - wait 10 minutes, assign ticket to a dedicated group
        $this->login($new_user_name);
        $assignation_datetime = $this->setCurrentTime('2025-06-10 09:10:00');
        /** @var Ticket $ticket */
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.');

        // --- assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    public function testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);
        $non_dedicated_group = getItemByTypeName(Group::class, '_test_group_2');
        assert($non_dedicated_group->getID() !== $ola_tto->fields['groups_id'], 'Non dedicated group should not be the same as the OLA group');

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - wait 10 minutes, assign ticket to a dedicated group
        $this->setCurrentTime('2025-06-10 09:10:00');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $non_dedicated_group->getID()]);
        assert($ticket->haveAGroup(CommonITILActor::ASSIGN, [$non_dedicated_group->getID()]), 'Ticket should be assigned to the dedicated group of the OLA.');
        $ola_data = $ticket->getOlasData()[0];

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $this->assertEquals(null, $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a dedicated group.');
    }

    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserNotInDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $user = $this->createItem(User::class, ['name' => 'my user seb' ]);
        $user_group = new Group_User();
        $group = getItemByTypeName(Group::class, '_test_group_2');
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $group->getID()]);
        assert($group->fields['is_assign'] == 1, 'Group should be assignable to tickets.');
        assert(!Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User not should be in the group of the OLA.'); // ok

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.'); // ok

        // act - wait 10 minutes, assign ticket to a dedicated group
        $assignation_datetime = $this->setCurrentTime('2025-06-10 09:10:00');
        /** @var Ticket $ticket */
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.'); // ok

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    /**
     * - a ticket is added
     * - this triggers the default OLA assignment rule: adding the G_servicedesk group + adding 2 OLAs
     * - if needed, technician performs an escalation: assigning the ticket to the G_N1 group
     * - this triggers the N1 OLA rule: adding 2 OLAs: OLA_N1_TTO & OLA_N1_TTR
     * Added olas should not be complete as ticket is not assigned to G_N1 group
     */
    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule(): void
    {
        $this->login();
        $g_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $g_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $g_n1)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $g_n1->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $this->createRule($rule_builder);


        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $g_desk->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $g_n1->getID(),
            ]
        );
        // check rule has processed : OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // assert OLA TTO is not complete
        $this->assertNull($fetched_ola_data['end_time']);
    }

    /**
     * Same as @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule()
     * + ensure ola is complete when group is removed from ticket assigned groups
     */
    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRule(): void
    {
        // --- arrange
        $this->login();
        $root_entity_id = $this->getTestRootEntity(true);
        // create groups + ola
        $group_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $group_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $group_n1)['ola'];

        // rule to assign ticket to group N1

        // rule to assign ticket to group N1 + add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('entities_id', Rule::PATTERN_IS, $root_entity_id);
        $rule_builder->addAction('assign', '_groups_id_assign', $group_n1->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $rule_builder->setCondtion(RuleTicket::ONADD);

        $this->createRule($rule_builder);

        // -- act : create ticket in entity
        $ticket = $this->createTicket([
            'entities_id' => $root_entity_id,
        ]);
        // check rule has processed : ticket is assigned to group N1 + OLA TTO is added to ticket
        assert($ticket->isGroup(CommonITILActor::ASSIGN, $group_n1->getID()), 'Ticket should be assigned to group N1 by rule');
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // --- assert OLA TTO is not complete
        $this->assertNull($fetched_ola_data['end_time']);
    }

    /**
     * Same code as testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRule()
     * + remove group associated to OLA from ticket assigned groups
     * then assert OLA is complete
     */
    public function testOlaTtoIstCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRuleThenGroupRemoved(): void
    {
        // --- arrange
        $this->login();
        $root_entity_id = $this->getTestRootEntity(true);
        // create groups + ola
        $group_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $group_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $group_n1)['ola'];

        // onadd rule to assign ticket to group N1 + add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('entities_id', Rule::PATTERN_IS, $root_entity_id);
        $rule_builder->addAction('assign', '_groups_id_assign', $group_n1->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $rule_builder->setCondtion(RuleTicket::ONADD);
        $this->createRule($rule_builder);

        // -- act : create ticket in entity then remove group associated to OLA from ticket assigned groups
        $ticket = $this->createTicket([
            'entities_id' => $root_entity_id,
            '_groups_id_assign' => $group_desk->getID(), // to be sure the rule assign the ticket to group N1
        ]);
        // check rule has processed : ticket is assigned to group N1 + OLA TTO is added to ticket
        assert($ticket->isGroup(CommonITILActor::ASSIGN, $group_n1->getID()), 'Ticket should be assigned to group N1 by rule');
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        assert(null === $fetched_ola_data['end_time'], 'OLA TTO should not be complete yet - fix testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupByRuleAndOlaIsAddedByRule() first');

        // --- assert OLA TTO complete after removing group associated to OLA from ticket assigned groups
        $gt = new \Group_Ticket();
        assert(true === $gt->deleteByCriteria(['tickets_id' => $ticket->getID(), 'groups_id' => $group_n1->getID()]));
        $ticket = $this->reloadItem($ticket);
        assert(false === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$group_n1->getID()]));
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should still be associated to ticket.');

        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    public function testOlaTtoIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup(): void
    {
        // create ticket with OLA TTO not completed - copy from testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule
        $this->login();
        $g_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $g_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $g_n1)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $g_n1->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $this->createRule($rule_builder);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $g_desk->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $g_n1->getID(),
            ]
        );
        // check rule has processed : OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // check OLA TTO is not complete
        assert(null == $fetched_ola_data['end_time']);

        // act : remove group associated to OLA TTO from ticket assigned groups, maybe there is a cleaner way to do this
        $gt = new \Group_Ticket();
        assert(true === $gt->deleteByCriteria(['tickets_id' => $ticket->getID(), 'groups_id' => $g_n1->getID()]));

        $ticket = $this->reloadItem($ticket);
        assert(false === $ticket->isGroup(CommonITILActor::ASSIGN, $g_n1->getID()), 'Group N1 should not be assigned to ticket anymore');

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    public function testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithAFollowup(): void
    {
        // create ticket with OLA TTO not completed - copy from testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule
        $this->login();
        $desk_group = $this->createItem(Group::class, [
            'name' => 'G_desk', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        // create group N1 with user + profile Technician
        $n1_group = $this->createItem(Group::class, [
            'name' => 'N1', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $n1_user_name = 'N1 user';
        $n1_user = $this->createItem(User::class, [
            'name' => $n1_user_name,
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->createItem(Group_User::class, [
            'groups_id' => $n1_group->getID(),
            'users_id' => $n1_user->getID(),
        ]);
        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $n1_user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found for user N1');
        // assign user to profile Technician, to allow ticket update
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $n1_group)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $n1_group->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $this->createRule($rule_builder);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check rule has processed : OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a followup as a user of the group associated to OLA TTO
        $this->login($n1_user_name);
        \Session::checkCentralAccess(); // ensure user can update ticket to create a followup

        $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'needed to triger update', '_followup' => ['content' => 'the followup content']]);
        $ticket = $this->reloadItem($ticket);

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    /**
     * Same as above but with a task instead of a followup
     */
    public function testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithATask(): void
    {
        // create ticket with OLA TTO not completed - copy from testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule
        $this->login();
        $desk_group = $this->createItem(Group::class, [
            'name' => 'G_desk', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        // create group N1 with user + profile Technician
        $n1_group = $this->createItem(Group::class, [
            'name' => 'N1', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $n1_user_name = 'N1 user';
        $n1_user = $this->createItem(User::class, [
            'name' => $n1_user_name,
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->createItem(Group_User::class, [
            'groups_id' => $n1_group->getID(),
            'users_id' => $n1_user->getID(),
        ]);
        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $n1_user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found for user N1');
        // assign user to profile Technician, to allow ticket update
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $n1_group)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $n1_group->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $this->createRule($rule_builder);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check rule has processed : OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a task as a user of the group associated to OLA TTO
        $this->login($n1_user_name);
        \Session::checkCentralAccess(); // ensure user can update ticket to create a followup

        $tt = $this->createItem(\TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content' => 'the task content',
            'state' => 1,
            'actiontime' => '0',
            'users_id_tech' => $n1_user->getID(),
        ]);
        $ticket = $this->reloadItem($ticket);

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    public function testOlaTtoIsNotCompleteWhenUserNotOfGroupAssociatedToOlaTakesTicketIntoAccount()
    {
        // create ticket with OLA TTO not completed - copy from testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule
        $this->login();
        $desk_group = $this->createItem(Group::class, [
            'name' => 'G_desk',
            'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1]);
        // create group N1 with user + profile Technician
        $n1_group = $this->createItem(Group::class, [
            'name' => 'N1',
            'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1]);
        $desk_user_name = 'Desk user';
        $desk_user = $this->createItem(User::class, ['name' => $desk_user_name, 'is_active' => 1, 'entities_id' => $this->getTestRootEntity(true)]);

        // assign user to profile Technician, to allow ticket update
        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $desk_user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found for user desk');
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $n1_group)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTO to ticket
        $rule_builder = new RuleBuilder('add ola n1 tto');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $n1_group->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_tto_n1->getID());
        $this->createRule($rule_builder);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check rule has processed : OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket by rule');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a followup as a user of the group associated to OLA TTO
        $this->login($desk_user_name);
        \Session::checkCentralAccess(); // ensure user can update ticket to create a followup
        $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'needed to triger update', '_followup' => ['content' => 'the followup content']]);
        $ticket = $this->reloadItem($ticket);

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNull($fetched_ola_data['end_time']);
    }

    public function testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup(): void
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $now = $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        assert(false === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should not be assigned to the OLA group.');

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('2025-06-26 10:00:00');
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];
        $expected_due_time = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->modify('+1 hour') // 1 hour waiting time;
            ->format('Y-m-d H:i:s');

        $this->assertEquals(
            $expected_due_time,
            $new_due_time,
            'Waiting time should not be incremented while ticket status is WAITING for an OLA TTO.'
        );
    }

    public function testOlaTTODueTimeIsDelayedWhileTicketStatusIsWaitingAndAssignedToOlaGroup(): void
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(
            [   '_la_update' => true,
                '_olas_id' => [$ola->getID()],
                '_groups_id_assign' => getItemByTypeName(Group::class, '_test_group_1', true)]
        );
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        assert(true === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should be assigned to the OLA group.');
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('2025-06-26 10:00:00');
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];

        $this->assertEquals(
            (new DateTime($initial_due_time))->format('Y-m-d H:i:s'),
            $new_due_time,
            'Waiting time should not be incremented while ticket status is WAITING for an OLA TTO.'
        );
    }


    public function testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaitingAndAssignedToOlaGroup(): void
    {
        // arrange
        $this->login();
        $this->setCurrentTime('2025-06-26 10:04:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING, '_groups_id_assign' => $ola_group->getID()]);
        assert(true === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should be assigned to the OLA group.');

        // act - wait 20 minutes and switch ticket to assigned
        $this->setCurrentTime('2025-06-26 10:24:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should be 0 minute after 20 min in WAITING status for an OLA TTO');
    }

    public function testOlaTTOWaitingTimeIsIncrementedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup(): void
    {
        // arrange
        $this->login();
        $this->setCurrentTime('2025-06-26 10:04:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING, '_groups_id_assign' => getItemByTypeName(Group::class, '_test_group_2', true)]);
        assert(false === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should not be assigned to the OLA group.');

        // act - wait 20 minutes and switch ticket to assigned
        $this->setCurrentTime('2025-06-26 10:24:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(20 * MINUTE_TIMESTAMP, $ola_data['waiting_time'], 'Waiting time should be 0 minute after 20 min in WAITING status for an OLA TTO');
    }

    public function testOlaCanBeAssociatedByRulesAndByForm(): void
    {
        // arrange - create a rule to assign OLA when priority is 4
        $this->login();
        [   'ola' => $ola_by_rule,
            'slm' => $slm,
            'group' => $group,
        ] = $this->createOLA(ola_type: SLM::TTO);
        $ola_by_form = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm)['ola'];

        $builder = new RuleBuilder('Assign OLA rule', RuleTicket::class);
        $builder->setCondtion(RuleCommonITILObject::ONADD);
        $builder->addCriteria('priority', Rule::PATTERN_IS, 4);
        $builder->addAction('append', 'olas_id', $ola_by_rule->getID());
        $builder->setEntity(0);
        $this->createRule($builder);

        // act - create ticket with priority 4 and associate OLA
        $ticket = $this->createTicket(['priority' => 4, '_la_update' => true, '_olas_id' => [$ola_by_form->getID()]]);

        // assert - check if the ticket has the 2 OLA associated
        $fetched_ola_ids = array_map(fn($ola_data) => $ola_data['olas_id'], $ticket->getOlasData());
        $this->assertEqualsCanonicalizing([$ola_by_form->getID(), $ola_by_rule->getID()], $fetched_ola_ids);

    }

    public function testOlaTtoDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-06-25 13:00:01');
        // arrange
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $ticket = $this->createTicket(['_olas_id' => [$ola->getID()], '_la_update' => true]);
        // assert due time is set correctly
        $initial_expected_due_time_str = $now->add($this->getDefaultOlaTtoDelayInterval())->format('Y-m-d H:i:s');
        $fetched_due_time = $ticket->getOlasTTOData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        assert($initial_expected_due_time_str === $fetched_due_time, 'OLA TTO Due time should be set to the current date + TTO delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:00';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $fetched_due_time = $ticket->getOlasTTOData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        $this->assertEquals($initial_expected_due_time_str, $fetched_due_time, 'OLA TTO due time is not updated when ticket date is changed.');
    }

    public function testOlaTtrDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-07-21 13:02:01');
        // arrange
        $ola = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $ticket = $this->createTicket(['_olas_id' => [$ola->getID()], '_la_update' => true]);

        // assert due time is set correctly
        $initial_expected_due_time_str = $now->add($this->getDefaultOlaTtrDelayInterval())->format('Y-m-d H:i:s');
        $fetched_due_time = $ticket->getOlasTTRData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        assert($initial_expected_due_time_str === $fetched_due_time, 'OLA TTR Due time should be set to the current date + TTR delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:02';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $fetched_due_time = $ticket->getOlasTTRData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        $this->assertEquals($initial_expected_due_time_str, $fetched_due_time, 'OLA TTR due time is updated when ticket date is changed.');
    }

    public function testInitialOlaTtrValuesOnCreation(): void
    {
        $this->login();
        // arrange
        $ola_ttr = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $start_time_datetime = $this->setCurrentTime('2025-06-02 09:00:00');

        // act associate ticket with ola
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $start_time_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime_str = $start_time_datetime->add($this->getDefaultOlaTtrDelayInterval())->format('Y-m-d H:i:s');
        $this->assertEquals($ola_data['due_time'], $due_time_datetime_str, 'Due time should be start_time + OLA_TTR_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    public function testInitialOlaTtrValuesOnUpdate()
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $this->setCurrentTime('2025-06-02 09:00:00');
        $ticket = $this->createTicket();
        assert(empty($ticket->getOlasData()), 'no OLA should be associated with ticket at this point');

        // act associate ticket with ola after 30 minutes
        $ola_association_datetime = $this->setCurrentTime('2025-06-02 09:30:00');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $ola_association_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime_str = $ola_association_datetime->add($this->getDefaultOlaTtrDelayInterval())->format('Y-m-d H:i:s');
        $this->assertEquals($ola_data['due_time'], $due_time_datetime_str, 'Due time should be start_time + OLA_TTR_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    #[TestWith([CommonITILObject::SOLVED])]
    #[TestWith([CommonITILObject::CLOSED])]
    public function testOlaTtrIsCompleteWhenTicketStatusChanges(int $ticket_status): void
    {
        $this->login();
        // arrange
        $ola_ttr = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $this->setCurrentTime('2025-06-13 14:12:00');
        // create (assigned) ticket - OLA + assign ola group to ticket
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()], '_groups_id_assign' => $ola_ttr->fields['groups_id'], 'status' => CommonITILObject::ASSIGNED]);

        // act : solve the ticket 30 minutes later
        $ticket_resolution_datetime = $this->setCurrentTime('2025-06-13 14:42:00');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::SOLVED]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($ticket_resolution_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'Ola ttr end time should not set when ticket is solved/closed.');
    }

    public function testOlaTtrIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup()
    {
        // create ticket with OLA TTR not completed - copy from testOlaTtrIsNotCompleteWhenTicketIsAssignedToDedicatedGroupAndOlaAddedByRule
        $this->login();
        $g_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $g_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_ttr_n1 = $this->createOLA(ola_type: SLM::TTR, group: $g_n1)['ola'];

        // rule : when ticket is assigned to group N1 -> add OLA N1 TTR to ticket
        $rule_builder = new RuleBuilder('add ola n1 ttr');
        $rule_builder->addCriteria('_groups_id_assign', Rule::PATTERN_IS, $g_n1->getID());
        $rule_builder->addAction('append', 'olas_id', $ola_ttr_n1->getID());
        $this->createRule($rule_builder);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $g_desk->getID(),
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $g_n1->getID(),
            ]
        );
        // check rule has processed : OLA TTR is added to ticket
        $fetched_ola_data = $ticket->getOlasTTRData()[0] ?? throw new \Exception('OLA TTR should be added to ticket by rule');
        assert($fetched_ola_data['olas_id'] === $ola_ttr_n1->getID(), 'OLA TTR should be added to ticket by rule');

        // check OLA TTR is not complete
        assert(null == $fetched_ola_data['end_time']);

        // act : remove group associated to OLA TTR from ticket assigned groups, maybe there is a cleaner way to do this
        $gt = new \Group_Ticket();
        assert(true === $gt->deleteByCriteria(['tickets_id' => $ticket->getID(), 'groups_id' => $g_n1->getID()]));

        $ticket = $this->reloadItem($ticket);
        assert(false === $ticket->isGroup(CommonITILActor::ASSIGN, $g_n1->getID()), 'Group N1 should not be assigned to ticket anymore');

        // assert OLA TTR is now complete
        $fetched_ola_data = $ticket->getOlasTTRData()[0] ?? throw new \Exception('Tested OLA TTR not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    public function testOlaTTRDueTimeIsDelayedWhileTicketStatusIsWaiting()
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => CommonITILObject::WAITING]);
        assert(CommonITILObject::WAITING === (int) $ticket->fields['status']);
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('2025-06-26 10:00:00');
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];

        $this->assertEquals(
            (new DateTime($initial_due_time))->modify('+1 hour')->format('Y-m-d H:i:s'),
            $new_due_time,
            'Due time should be delayed by one hour because of ticket waiting times'
        );
    }

    public function testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
    {
        $this->login();
        $this->setCurrentTime('2025-06-26 10:04:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => CommonITILObject::WAITING]);
        assert($ticket->fields['status'] === CommonITILObject::WAITING);
        $this->setCurrentTime('2025-06-26 10:24:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(20 * 60, $ola_data['waiting_time'], 'Waiting time should be incremented by 20 minutes after 20 min in WAITING status for an OLA TTR');
    }

    public function testOlaTtoIsLateWhenEndTimeIsAfterDueTime(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $now = $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - check ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : complete ola after due time - wait for ola to be late + assign the ola group to the ticket
        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola->fields['groups_id']]);

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when end time is set');
    }

    /**
     * same as above, due time reached, without assigning the group to the ticket
     */
    public function testOlaTtoIsLateWhenDueTimeIsPassed(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $now = $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : wait for ola to be late
        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when due time is passed and end time is not set');
    }

    public function testOlaTtoIsNotLateWhenTicketStatusIsNotWaiting(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $now = $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : wait for ola to be late, set ticket status to WAITING
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);

        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is not late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['is_late'], 'OLA should not be late when ticket is WAITING (even if due time is passed and end time is not set)');
    }

    public function testOlaTtrIsLateWhenEndTimeIsAfterDueTime(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $now = $this->setCurrentTime('2025-02-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - check ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : complete ola after due time - wait for ola to be late + assign the ola group to the ticket
        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola->fields['groups_id']]);

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when end time is set');
    }

    public function testOlaTtrIsLateWhenDueTimeIsPassed(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $now = $this->setCurrentTime('2025-02-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : wait for ola to be late
        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when due time is passed and end time is not set');
    }

    public function testOlaTtrIsNotLateWhenTicketStatusIsNotWating(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $now = $this->setCurrentTime('2025-02-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when end time is not set');

        // act : wait for ola to be late, set ticket status to WAITING
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);

        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->add(new DateInterval('PT1H')) // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is not late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['is_late'], 'OLA should not be late when ticket is WAITING (even if due time is passed and end time is not set)');

    }

    public function testSplitIdsByType(): void
    {
        // arrange - create 3 TTO and 5 TTR OLA
        $ola_tto_to_create = 3;
        $ola_ttr_to_create = 5;
        $created_olas_tto_ids = $created_olas_ttr_ids = [];
        for ($i = 0; $i < $ola_tto_to_create; $i++) {
            $created_olas_tto_ids[] = $this->createOLA(ola_type: SLM::TTO)['ola']->getID();
        }
        for ($i = 0; $i < $ola_ttr_to_create; $i++) {
            $created_olas_ttr_ids[] = $this->createOLA(ola_type: SLM::TTR)['ola']->getID();
        }

        // assert
        [SLM::TTO => $fetched_olas_tto_ids, SLM::TTR => $fetched_olas_ttr_ids] = OLA::splitIdsByType(array_merge($created_olas_tto_ids, $created_olas_ttr_ids));
        $this->assertEqualsCanonicalizing($created_olas_ttr_ids, $fetched_olas_ttr_ids);
        $this->assertEqualsCanonicalizing($created_olas_tto_ids, $fetched_olas_tto_ids);
    }
}
