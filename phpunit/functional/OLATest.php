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
use DbTestCase;
use Glpi\PHPUnit\Tests\Glpi\ITILTrait;
use Glpi\PHPUnit\Tests\Glpi\SLMTrait;
use PHPUnit\Framework\Attributes\TestWith;
use SLM;
use Ticket;

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
 *              - is done when a user of the dedicated group is assigned to the ticket : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroup()
 *              - is not done when a non dedicated group is assigned to the ticket : @see self::testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup()
 *              - is not done when a user not in the dedicated group is assigned to the ticket : @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserNotInDedicatedGroup()
 *
 *          - delay :
 *              - ola due time is not delayed if the ticket status is WAITING : @see self::testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaiting() @todoseb maintenant plus subtile, doit être compté avant que le groupe assigné au ticket l'ai pris en charge.
 *              - ola waiting time is not incremented while the ticket is WAITING @see self::testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaiting()
 *
 *          - ola can be associated by rule and form at the same time @see self::testOlaCanBeAssociatedByRulesAndByForm()
 *
 *      - TTR (Time To Resolve)
 *          - ola ttr is associated with a ticket then 'start_time' is set to ticket 'date' & 'due_time' is calculated, however the group (or a user in the group) is not assigned to the ticket yet
 *              - on creation : @see self::testInitialOlaTtrValuesOnCreation()
 *              - on update : @see self::testInitialOlaTtrValuesOnUpdate()
 *
 *          - completion :
 *              - is done when the ticket is closed (end_time is set): @see self::testOlaTtrIsCompleteWhenTicketIsClosed() // @todoseb nom de test pas cohérent avec le test tto
 *              - is done when the ticket is solved : @see self::testOlaTtrIsCompleteWhenTicketIsSolved()
 *
 *          - delay :
 *              - ttr due time is delayed if the ticket status is WAITING : @see self::testOlaTtrDueTimeIsDelayedWhileTicketStatusIsWaiting
 *              - ttr waiting time is incremented while the ticket status is WAITING : @see self::testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
 *              + @todo tests avec autres groupes
 *
 *          - when completion is done, the associated group is removed from ticket assignees : not implemented, seems not relevant atm
 */

// @todoseb test à la suppression d'un OLA ? comportement à adopter ? est déjà protégé ? -> a tester
// @todoseb test à la modification d'un OLA ? : recalcul sur les dates d'échéance ?
// @todoseb revoir les messages des assertions
// @todoseb tests sur getSlasData() - ailleurs

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
        $this->createItem(\Item_Ola::class, ['olas_id' => $ola_tto1->getID()] + $association_data);
        $this->createItem(\Item_Ola::class, ['olas_id' => $ola_tto2->getID()] + $association_data);
        $this->createItem(\Item_Ola::class, ['olas_id' => $ola_ttr->getID()] + $association_data);

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
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Expected exactly 1 OLA associated with ticket, but found different results');
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
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Expected exactly 1 OLA associated with ticket, but found different results');
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
        $this->assertEqualsCanonicalizing($olas_ids, $fetched_olas, 'Expected OLAs associated with ticket don\'t match the expected IDs');
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
        $this->assertEqualsCanonicalizing($olas_ids, $fetched_olas, 'Expected exactly 1 OLA associated with ticket, but found different results');
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
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Expected exactly 1 OLA associated with ticket, but found different results');
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
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Expected exactly 1 OLA associated with ticket, but found different results');
    }

    /**
     * Backward compatibility test
     */
    public function testCreateTicketWithOldFormParams(): void
    {
        // arrange
        $this->login();
        ['ola' => $ola_tto, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: \SLM::TTO);
        $ola_ttr = $this->createOLA(ola_type: \SLM::TTR, group: $group, slm: $slm)['ola'];

        // act - update ticket
        $ticket = $this->createTicket(
            ['olas_id_tto' => $ola_tto->getID(), 'olas_id_ttr' => $ola_ttr->getID()],
            ['olas_id_tto', 'olas_id_ttr'] // do not exist anymore but here we check backward compatibility.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket');
    }

    public function testUpdateTicketWithOldFormParams(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        ['ola' => $ola_tto, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: \SLM::TTO);
        $ola_ttr = $this->createOLA(ola_type: \SLM::TTR, group: $group, slm: $slm)['ola'];

        // act - update ticket
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            ['olas_id_tto' => $ola_tto->getID(), 'olas_id_ttr' => $ola_ttr->getID()],
            ['olas_id_tto', 'olas_id_ttr'] // do not exist anymore but here we check backward compatibility, so updateItem must no fail.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket');
    }

    /**
     * Create a ticket with removed fields trigger
     */
    public function testCreateTicketWithOlaRemovedFieldsThrowsAnExecption(): void
    {
        $this->login();
        $removed_fields = ['ola_tto_begin_date', 'ola_ttr_begin_date', 'internal_time_to_resolve', 'internal_time_to_own', 'olalevels_id_ttr'];

        foreach ($removed_fields as $removed_field) {
            $this->expectException(\RuntimeException::class);
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
            $this->expectException(\RuntimeException::class);
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
        $test_group = $this->createItem(\Group::class, ['is_assign' => $is_assigned, 'name' => 'test group']);
        $slm = $this->createSLM();

        // act
        $inserted = (bool) (new \OLA())->add([
            'groups_id' => $test_group->getID(),
            'slms_id' => $slm->getID(),
            'name' => 'OLA',
            'number_time' => 1,
            'definition_time' => 'hour',
        ]);

        // assert
        $expected_add_return
            ? $this->assertTrue($inserted)
            : $this->hasSessionMessages(ERROR, ['The group #' . $test_group->getID() . ' is not allowed to be associated with an OLA. group.is_assign must be set to 1']);
    }

    // --- business tests

    #[TestWith([1, true])]
    #[TestWith([0, false])]
    public function testOlaCanBeAssociatedWithAnAllowedGroupOnUpdate(int $is_assigned, bool $expected_add_return): void
    {
        // arrange
        $allowed_group = $this->createItem(\Group::class, ['is_assign' => 1, 'name' => 'allowed group']);
        $test_group = $this->createItem(\Group::class, ['is_assign' => $is_assigned, 'name' => 'test group']);
        $slm = $this->createSLM();

        $_ola = $this->createItem(
            \OLA::class,
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
            ? $this->assertTrue((bool) $update)
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
            'is_recursive' => 1,
            'type' => SLM::TTR,
            'comment' => 'OLA comment ' . time(),
            'number_time' => 90,
            'definition_time' => 'minute',
            'slms_id' => $slm->getID(),
            'groups_id' => 0,
        ]);
        assert(false !== $result, 'failed to insert OLA without group association');
        $ola = getItemByTypeName(\OLA::class, $ola_name);

        // act - create ticket with OLA
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - check if the ticket has the OLA associated & no group associated with the ticket
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas);
        $this->assertEmpty($ticket->getGroups(\CommonITILActor::class));

        // @todoseb faire la même avec une rule
    }

    /**
     * - start_time is set using ticket 'date' field (which is the current time when not specified))
     * - due_time is set at the moment the Ola is assigned to the ticket
     * - endtime is set not set
     * - waiting_time is not set
     */
    public function testInitialOlaTtoValuesOnCreation(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $start_time_datetime = $this->setCurrentTime('09:00:00', '2025-06-02');
        $ticket_date = new \DateTime('- 30 minutes');

        // act associate ticket with ola
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()], 'date' => $ticket_date->format('Y-m-d H:i:s')]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time
        $this->assertEquals($ola_data['start_time'], $ticket_date->format('Y-m-d H:i:s'), 'Start time should be set to the \'date\' field of associated ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = clone $ticket_date;
        $due_time_datetime->add($this->getDefaultTtoDelayInterval());
        $this->assertEquals($ola_data['due_time'], $due_time_datetime->format('Y-m-d H:i:s'), 'Due time should be start_time + OLA_TTO_DELAY.');

        // end_time is not set
        // @todoseb est actuellement null, zero c'est mieux ?
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    /**
     * Same as above but ola is assigned at update time
     */
    public function testInitialOlaTtoValuesOnUpdate(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-02');
        $ticket = $this->createTicket();
        assert(empty($ticket->getOlasData()), 'no OLA should be associated with ticket at this point');

        // act associate ticket with ola after 30 minutes
        $ola_association_datetime = $this->setCurrentTime('09:30:00', '2025-06-02');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $ola_association_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = clone $ola_association_datetime;
        $due_time_datetime->add($this->getDefaultTtoDelayInterval());
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
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-10');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - wait 10 minutes, assign ticket to a dedicated group
        $group_assignation_datetime = $this->setCurrentTime('09:10:00', '2025-06-10');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola_tto->fields['groups_id']]);
        assert($ticket->haveAGroup(\CommonITILActor::ASSIGN, [$ola_tto->fields['groups_id']]), 'Ticket should be assigned to the dedicated group of the OLA.');
        $ola_data = $ticket->getOlasData()[0];

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $this->assertEquals($group_assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a dedicated group.');
    }

    public function testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-10');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $user = $this->createItem(\User::class, ['name' => 'my user seb' ]);
        $user_group = new \Group_User();
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $ola_tto->fields['groups_id']]);
        assert(\Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User should be in the group of the OLA.'); // ok

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.'); // ok

        // act - wait 10 minutes, assign ticket to a dedicated group
        $assignation_datetime = $this->setCurrentTime('09:10:00', '2025-06-10');
        /** @var Ticket $ticket */
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(\CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.'); // ok

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    public function testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-10');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);
        $non_dedicated_group = getItemByTypeName(\GROUP::class, '_test_group_2');
        assert($non_dedicated_group->getID() !== $ola_tto->fields['groups_id'], 'Non dedicated group should not be the same as the OLA group');

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - wait 10 minutes, assign ticket to a dedicated group
        $group_assignation_datetime = $this->setCurrentTime('09:10:00', '2025-06-10');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_groups_id_assign' => $non_dedicated_group->getID()]);
        assert($ticket->haveAGroup(\CommonITILActor::ASSIGN, [$non_dedicated_group->getID()]), 'Ticket should be assigned to the dedicated group of the OLA.');
        $ola_data = $ticket->getOlasData()[0];

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $this->assertEquals(null, $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a dedicated group.');
    }

    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserNotInDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-10');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $user = $this->createItem(\User::class, ['name' => 'my user seb' ]);
        $user_group = new \Group_User();
        $group = getItemByTypeName(\GROUP::class, '_test_group_2');
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $group->getID()]);
        assert($group->fields['is_assign'] == 1, 'Group should be assignable to tickets.');
        assert(!\Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User not should be in the group of the OLA.'); // ok

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.'); // ok

        // act - wait 10 minutes, assign ticket to a dedicated group
        $assignation_datetime = $this->setCurrentTime('09:10:00', '2025-06-10');
        /** @var Ticket $ticket */
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(\CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.'); // ok

        // assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    public function testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaiting()
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert(\CommonITILObject::WAITING === (int) $ticket->fields['status']);
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('10:00:00');
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => \CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];

        $this->assertEquals(
            (new \DateTime($initial_due_time))->format('Y-m-d H:i:s'),
            $new_due_time,
            'Le temps d\'échéance (due time) devrait être retardé d\'une heure après passage du ticket de WAITING à un autre statut'
        );
    }

    public function testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaiting()
    {
        // arrange
        $this->login();
        $this->setCurrentTime('10:04:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTO);

        // act - create ticket, set status to waiting, wait 20 minutes, switch ticket to assigned
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert($ticket->fields['status'] === CommonITILObject::WAITING);
        $this->setCurrentTime('10:24:00');
        $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should be 0 minute after 20 min in WAITING status for an OLA TTO');
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

        $builder = new \RuleBuilder('Assign OLA rule', \RuleTicket::class);
        $builder->setCondtion(\RuleCommonITILObject::ONADD);
        $builder->addCriteria('priority', \Rule::PATTERN_IS, 4);
        $builder->addAction('append', 'olas_id', $ola_by_rule->getID());
        $builder->setEntity(0);
        $this->createRule($builder);

        // act - create ticket with priority 4 and associate OLA
        // @todoseb faire test supplémentaire car le param _la_update pourrait faire fonctionner la rule qui ne fonctionnerai pas autrement : en 2 étapes
        $ticket = $this->createTicket(['priority' => 4, '_la_update' => true, '_olas_id' => [$ola_by_form->getID()]]);

        // assert - check if the ticket has the 2 OLA associated
        $fetched_ola_ids = array_map(fn($ola_data) => $ola_data['olas_id'], $ticket->getOlasData());
        $this->assertEqualsCanonicalizing([$ola_by_form->getID(), $ola_by_rule->getID()], $fetched_ola_ids);

    }

    public function testInitialOlaTtrValuesOnCreation(): void
    {
        // @todoseb peut-être qu'on peut factoriser avec ola tto, de façon a pouvoir facilement les séparer plus tard, si ça ne nuit pas à la lisibilité, ou faire un appel à la même fonction
        $this->login();
        // arrange
        $ola_ttr = $this->createOLA(ola_type: \SLM::TTR)['ola'];
        $start_time_datetime = $this->setCurrentTime('09:00:00', '2025-06-02');

        // act associate ticket with ola
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $start_time_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = clone $start_time_datetime;
        $due_time_datetime->add($this->getDefaultTtrDelayInterval());
        $this->assertEquals($ola_data['due_time'], $due_time_datetime->format('Y-m-d H:i:s'), 'Due time should be start_time + OLA_TTR_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    public function testInitialOlaTtrValuesOnUpdate()
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTR)['ola'];
        $this->setCurrentTime('09:00:00', '2025-06-02');
        $ticket = $this->createTicket();
        assert(empty($ticket->getOlasData()), 'no OLA should be associated with ticket at this point');

        // act associate ticket with ola after 30 minutes
        $ola_association_datetime = $this->setCurrentTime('09:30:00', '2025-06-02');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $ola_association_datetime->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = clone $ola_association_datetime;
        $due_time_datetime->add($this->getDefaultTtrDelayInterval());
        $this->assertEquals($ola_data['due_time'], $due_time_datetime->format('Y-m-d H:i:s'), 'Due time should be start_time + OLA_TTR_DELAY.');

        // end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // waiting_time is not set
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
    }

    public function testOlaTtrIsCompleteWhenTicketIsClosed(): void
    {
        $this->login();
        // arrange
        $ola_ttr = $this->createOLA(ola_type: \SLM::TTR)['ola'];
        $this->setCurrentTime('14:12:00', '2025-06-13');
        // create (assigned) ticket with OLA + assign ola group to ticket
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()], '_groups_id_assign' => $ola_ttr->fields['groups_id'], 'status' => \CommonITILObject::ASSIGNED]);

        // act associate close the ticket 30 minutes later
        $ticket_resolution_datetime = $this->setCurrentTime('14:42:00', '2025-06-13');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['status' => \CommonITILObject::CLOSED]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // end_time is not set to
        $this->assertEquals($ticket_resolution_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'Ola ttr end time should not set when ticket is closed.');
    }

    /**
     * Same test as above but for solved status
     * @todoseb refacto ?
     */
    public function testOlaTtrIsCompleteWhenTicketIsSolved(): void
    {
        $this->login();
        // arrange
        $ola_ttr = $this->createOLA(ola_type: \SLM::TTR)['ola'];
        $this->setCurrentTime('14:12:00', '2025-06-13');
        // create (assigned) ticket with OLA + assign ola group to ticket
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()], '_groups_id_assign' => $ola_ttr->fields['groups_id'], 'status' => \CommonITILObject::ASSIGNED]);

        // act associate solve the ticket 30 minutes later
        $ticket_resolution_datetime = $this->setCurrentTime('14:42:00', '2025-06-13');
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['status' => \CommonITILObject::SOLVED]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // end_time is not set to
        $this->assertEquals($ticket_resolution_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'Ola ttr end time should not set when ticket is solved.');
    }

    public function testOlaTTRDueTimeIsDelayedWhileTicketStatusIsWaiting()
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert(\CommonITILObject::WAITING === (int) $ticket->fields['status']);
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('10:00:00');
        $this->updateItem($ticket::class, $ticket->getID(), ['status' => \CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];

        $this->assertEquals(
            (new \DateTime($initial_due_time))->modify('+1 hour')->format('Y-m-d H:i:s'),
            $new_due_time,
            'Le temps d\'échéance (due time) devrait être retardé d\'une heure après passage du ticket de WAITING à un autre statut'
        );
    }

    public function testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
    {
        $this->login();
        $this->setCurrentTime('10:04:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert($ticket->fields['status'] === CommonITILObject::WAITING);
        $this->setCurrentTime('10:24:00');
        $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(20 * 60, $ola_data['waiting_time'], 'Waiting time should be incremented by 20 minutes after 20 min in WAITING status for an OLA TTR');
    }

    private function getDefaultTtoDelayInterval(): \DateInterval
    {
        [$amount, $unit] = self::OLA_TTO_DELAY;

        return new \DateInterval(sprintf('PT%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }

    private function getDefaultTtrDelayInterval(): \DateInterval
    {
        [$amount, $unit] = self::OLA_TTR_DELAY;

        return new \DateInterval(sprintf('P%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }
}
