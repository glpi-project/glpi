<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use DateTime;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\ITILTrait;
use Glpi\Tests\Glpi\SLMTrait;
use Glpi\Tests\RuleBuilder;
use Group;
use Group_User;
use Item_Ola;
use Notification;
use Notification_NotificationTemplate;
use NotificationTarget;
use NotificationTemplate;
use NotificationTemplateTranslation;
use OLA;
use OlaLevel;
use OlaLevel_Ticket;
use OlaLevelAction;
use OlaLevelCriteria;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Log\LogLevel;
use QueuedNotification;
use Rule;
use RuleCommonITILObject;
use RuleTicket;
use RuntimeException;
use SLM;
use Ticket;
use User;

/**
 * OLA functional specifications (extracted from test plan below)
 *
 * - multiple OLA can be associated to a ticket (no more just a single tto and a single ttr)
 * - ola due dates cannot be set manually anymore in a ticket
 * - the same ola can be associated multiple times to a ticket, if one is completed.
 * - old form params are still supported (olas_id_tto, olas_id_ttr)
 * - ola must be associated with a group (if the group has the ability to be assigned to a ticket)
 * - ola previously created (without group) can be associated to tickets
 * - ola can be associated to ticket by rule and form at the same time
 * - completion :
 *      - tto :
 *          - when ticket is "taken into account" (add task, add followup) by a user of the ola group
 *          - when a user of the dedicated group assign itself (or it's group) to the ticket.
 *          - when a group associated to ola is removed from ticket assigned group
 *      - ttr :
 *          - when the ticket is closed or solved
 *          - when a group associated to ola is removed from ticket assigned group
 * - due time delaying :
 *      - ttr : when the ticket status is WAITING
 *      - tto : when the ticket status is WAITING and ticket is not assigned to group
 * - due_time is not updated on ticket's date update
 * - ticket is late if :
 *      - end_time is defined and > due_time
 *      - end_time is not defined & due_time is passed
 *      - ticket status is not WAITING
 */

/**
 * Test Plan
 *
 * - ola waiting_start is set to current time on ticket creation with waiting status : @see self::testOlaWaitingStartIsSetOnTicketCreationWithWaitingStatus()
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
 *      - multiple times the same ola results in a single association : @see self::testUpdateTicketWithDuplicatedOlasNotCompleted()
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
 *              - is done when a user of the dedicated group is assigned to the ticket by a user of the ola group : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfOlaGroup()
 *              - is not done when a user of the dedicated group is assigned to the ticket by foreign user of the ola group : @see self::testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfAnotherGroup()
 *              - is not done when a non-dedicated group is assigned to the ticket : @see self::testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup()
 *              - is not done when a user not in the dedicated group is assigned to the ticket : @see self::testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserNotInDedicatedGroup()
 *              - is done when a group associated to ola is removed from ticket assigned group :
 *                  - @see self::testOlaTtoIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup()
 *              - is done when ticket is "taken into account" (add task, add followup) by a user of the ola group :
 *                  - @see self::testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithAFollowup()
 *                  - @see self::testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithATask()
 *              - is not done when ticket is updated(add task, add followup) by a user not in the ola group : @see self::testOlaTtoIsNotCompleteWhenUserNotOfGroupAssociatedToOlaTakesTicketIntoAccount()
 *
 *          - delay (ticket has been paused) :
 *              - due time is delayed if the ticket status is WAITING and ticket is not assigned to group: @see self::testOlaTTODueTimeIsDelayedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup()
 *              - waiting time is incremented while the ticket is WAITING and not assigned to ola group @see self::testOlaTTOWaitingTimeIsIncrementedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup()
 *              - due time is not delayed if the ticket status is WAITING and ticket is assigned to group: @see self::testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaitingAndAssignedToOlaGroup()
 *              - waiting time is not incremented while the ticket is WAITING and assigned to ola group @see self::testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaitingAndAssignedToOlaGroup()
 *
 *          - ticket is late if : (business logic extracted from CommonITILObject::generateSLAOLAComputation())*
 *                 - end_time is defined and > due_time - @see self::testOlaTtoIsLateWhenEndTimeIsAfterDueTime()
 *                 - end_time is not defined & due_time is passed - @see self::testOlaTtoIsLateWhenDueTimeIsPassed()
 *                 - ticket status is not WAITING (1): @see self::testOlaTtoIsNotLateWhenTicketStatusIsNotWaiting()
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
 *          - delay : (doesn't depend on group assignment)
 *              - ttr due time is delayed if the ticket status is WAITING : @see self::testOlaTtrDueTimeIsDelayedWhileTicketStatusIsWaiting
 *              - ttr waiting time is incremented while the ticket status is WAITING : @see self::testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
 *
 *          - ticket is late if : exactly the same tests as for tto (duplication to be prepared for future changes)
 *                 - end_time is defined and > due_time - @see self::testOlaTtrIsLateWhenEndTimeIsAfterDueTime()
 *                 - end_time is not defined & due_time is passed - @see self::testOlaTtrIsLateWhenDueTimeIsPassed()
 *                 - ticket status is not WAITING (1): @see self::testOlaTtrIsNotLateWhenTicketStatusIsNotWating()
 *
 *          - due_time is not updated on ticket date update @see self::testOlaTtrDueTimeIsNotUpdatedOnTicketDateUpdate()
 *
 * - escalation levels
 *      - a passed level is executed only once, whatever the number of cron runs : @see self::testOlaLevelsAreExecutedOnlyOnceByCron()
 *      - a passed level is not executed again on ticket update : @see self::testOlaLevelsAreNotExecutedAgainOnTicketUpdate()
 *      - a future level is executed once when its date is reached : @see self::testFutureOlaLevelsAreExecutedOnceWhenReached()
 *      - TTO and TTR levels on the same ticket are each executed once : @see self::testOlaLevelsOfTtoAndTtrOnSameTicketAreExecutedOnce()
 *      - levels are suspended while the ticket is waiting, then executed once at their delayed date : @see self::testOlaLevelReachedWhileWaitingIsExecutedOnceAfterWaiting()
 *      - levels of an OLA associated to a waiting ticket are executed once after the waiting period : @see self::testOlaLevelsOfOlaAssociatedToWaitingTicketAreExecutedAfterWaiting()
 *      - levels to do of an OLA removed from the ticket are cleaned : @see self::testOlaLevelsToDoAreCleanedWhenOlaIsRemovedFromTicket()
 *      - passed levels of 2 OLA of the same type are each executed once : @see self::testPassedOlaLevelsOfTwoOlasOfSameTypeAreExecutedOnce()
 *      - levels are suspended while the ticket is in the trash, then executed once when it is restored : @see self::testOlaLevelsAreExecutedOnceAfterTicketIsRestored()
 *      - a level reached while waiting is delayed when the ticket is resumed from the ticket form : @see self::testOlaLevelReachedWhileWaitingIsDelayedWhenResumedFromTicketForm()
 *      - an OLA added to an existing ticket starts from its first level, a removed one sends nothing, a kept one is not replayed : @see self::testOlaLevelsWhenOlasAreAddedAndRemovedOnExistingTicket()
 *      - several passed levels to do of a waiting ticket are kept, then executed once after waiting : @see self::testSeveralPassedOlaLevelsOfWaitingTicketAreKeptThenExecutedOnce()
 *      - several passed levels to do of a closed ticket are cleaned : @see self::testSeveralPassedOlaLevelsOfClosedTicketAreCleaned()
 *      - levels to do of a closed ticket are cleaned when its OLA is completed : @see self::testOlaLevelsToDoOfClosedTicketAreCleanedWhenOlaIsCompleted()
 *      - levels of an OLA associated to a solved ticket are executed once after reopening : @see self::testOlaLevelsOfOlaAssociatedToSolvedTicketAreExecutedAfterReopening()
 *      - a level setting the ticket WAITING suspends the next levels until the ticket is resumed : @see self::testOlaLevelSettingTicketWaitingSuspendsNextLevels()
 *      - a level reached while the ticket is closed is dropped, passed levels are not replayed after reopening : @see self::testOlaLevelReachedWhileClosedIsNotExecutedAfterReopening()
 *      - a level not passed yet is rescheduled after a waiting period and executed once : @see self::testOlaLevelsAreRescheduledAfterWaiting()
 *      - passed levels of an OLA associated to a backdated ticket are executed once : @see self::testPassedOlaLevelsOfBackdatedTicketAreExecutedOnce()
 *      - a level not passed yet is executed once after the ticket is solved then reopened : @see self::testOlaLevelsAreExecutedAfterReopening()
 */

class OLATest extends DbTestCase
{
    use SLMTrait;
    use ITILTrait;

    public function testOlaWaitingStartIsSetOnTicketCreationWithWaitingStatus(): void
    {
        $this->login();
        // arrange : create ola
        ['ola' => $ola_tto] = $this->createOLA(ola_type: SLM::TTO);

        // act - create ticket with OLA TTO and WAITING status
        $ticket_data = [
            'name' => 'Ticket with OLA TTO and WAITING status',
            'status' => CommonITILObject::WAITING,
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ];
        $ticket_creation_date = '2025-10-31 14:08:11';
        $this->setCurrentTime($ticket_creation_date);
        $ticket = $this->createTicket($ticket_data);

        // assert - check if the ticket has the OLA associated and waiting_start is set
        $olas_data = $ticket->getOlasTTOData();
        $this->assertCount(1, $olas_data, 'Expected 1 OLA TTO associated with ticket, but found different results');
        $this->assertEquals($ticket_creation_date, $olas_data[0]['waiting_start'], 'Expected waiting_start to be set at ticket creation time');
    }

    public function testOlaWaitingStartIsNullOnTicketCreationWithIncomingStatus(): void
    {
        $this->login();
        // arrange : create ola
        ['ola' => $ola_tto] = $this->createOLA(ola_type: SLM::TTO);

        // act - create ticket with OLA TTO and WAITING status
        $ticket_data = [
            'name' => 'Ticket with OLA TTO and WAITING status',
            'status' => CommonITILObject::INCOMING,
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ];
        $ticket_creation_date = '2025-10-31 14:08:11';
        $this->setCurrentTime($ticket_creation_date);
        $ticket = $this->createTicket($ticket_data);

        // assert - check if the ticket has the OLA associated and waiting_start is set
        $olas_data = $ticket->getOlasTTOData();
        $this->assertCount(1, $olas_data, 'Expected 1 OLA TTO associated with ticket, but found different results');
        $this->assertNull($olas_data[0]['waiting_start'], 'Expected OLA waiting_start to be null');
    }

    public function testGetOLAData()
    {
        $this->login();
        // arrange : create ticket and 3 olas
        $ticket = $this->createTicket();
        ['ola' => $ola_tto1, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_tto2] = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);

        $association_data = [
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'start_time' => date('Y-m-d H:i:s'),
        ];

        // act - create associations
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto2->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_ttr->getID(), 'ola_type' => SLM::TTR] + $association_data);

        // assert - check if the ticket has the 3 OLA associated, 2 tto, 1 ttr
        $ticket = $this->reloadItem($ticket);
        $this->assertCount(3, $ticket->getOlasData(), 'Expected 3 OLA associated with ticket, but ' . count($ticket->getOlasData()) . ' found');
        $this->assertCount(2, $ticket->getOlasTTOData(), 'Expected 2 OLA TTO associated with ticket, but found different results');
        $this->assertCount(1, $ticket->getOlasTTRData(), 'Expected 1 OLA TTR associated with ticket, but found different results');

        $io = new Item_Ola();
        $this->assertCount(3, $io->getDataFromOlasIdsForTicket($ticket, [$ola_tto1->getID(), $ola_tto2->getID(), $ola_ttr->getID()]));
    }

    public function testGetOLADataWithDuplicatedOla()
    {
        $this->login();
        // arrange : create ticket and 3 olas
        $ticket = $this->createTicket();
        ['ola' => $ola_tto1, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_tto2] = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);

        $association_data = [
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'start_time' => date('Y-m-d H:i:s'),
        ];

        // act - create associations 3 + 1 duplicated
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto2->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_ttr->getID(), 'ola_type' => SLM::TTR] + $association_data);
        // duplicated association - associate with ola_tto1 again, but completed this time
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO, 'end_time' => \Session::getCurrentTime()] + $association_data);

        // assert - check if the ticket has the 4 OLA associated
        $ticket = $this->reloadItem($ticket);
        $this->assertCount(4, $ticket->getOlasData(), 'Expected 4 OLA associated with ticket, but ' . count($ticket->getOlasData()) . ' found');
        $this->assertCount(3, $ticket->getOlasTTOData(), 'Expected 3 OLA TTO associated with ticket, but found different results');
        $this->assertCount(1, $ticket->getOlasTTRData(), 'Expected 1 OLA TTR associated with ticket, but found different results');
    }

    public function testGetDataFromOlasIdsForTicketWithDuplicatedOla()
    {
        $this->login();
        // arrange : create ticket and 3 olas
        $ticket = $this->createTicket();
        ['ola' => $ola_tto1, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_tto2] = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);

        $association_data = [
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'start_time' => date('Y-m-d H:i:s'),
        ];

        // act - create associations 3 + 1 duplicated
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto2->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_ttr->getID(), 'ola_type' => SLM::TTR] + $association_data);
        // duplicated association - associate with ola_tto1 again, but completed this time
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO, 'end_time' => \Session::getCurrentTime()] + $association_data);

        // assert - check if the ticket has the 4 OLA associated
        $io = new Item_Ola();
        $this->assertCount(4, $io->getDataFromOlasIdsForTicket($ticket, [$ola_tto1->getID(), $ola_tto2->getID(), $ola_ttr->getID(), $ola_tto1->getID()]));
    }

    public function testGetDataFromOlasIdsForTicket()
    {
        $this->login();
        // arrange : create ticket and 3 olas
        $ticket = $this->createTicket();
        ['ola' => $ola_tto1, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_tto2] = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);

        $association_data = [
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'start_time' => date('Y-m-d H:i:s'),
        ];

        // act - create associations
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto1->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_tto2->getID(), 'ola_type' => SLM::TTO] + $association_data);
        $this->createItem(Item_Ola::class, ['olas_id' => $ola_ttr->getID(), 'ola_type' => SLM::TTR] + $association_data);

        // assert - check if the ticket has the 3 OLA associated, 2 tto, 1 ttr
        $io = new Item_Ola();
        $this->assertCount(3, $io->getDataFromOlasIdsForTicket($ticket, [$ola_tto1->getID(), $ola_tto2->getID(), $ola_ttr->getID()]));
    }

    public function testGetDataFromOlasIdsForNewTicket(): void
    {
        $this->login();
        // arrange : create 2 olas, and a ticket that is not yet saved
        ['ola' => $ola_tto, 'slm' => $slm, 'group' => $group] = $this->createOLA(ola_type: SLM::TTO);
        ['ola' => $ola_ttr] = $this->createOLA(ola_type: SLM::TTR, group: $group, slm: $slm);
        $ticket = new Ticket();
        $ticket->getEmpty();

        // assert - requested olas data is returned, without searching associations of the unsaved ticket
        $io = new Item_Ola();
        $olas_data = $io->getDataFromOlasIdsForTicket($ticket, [$ola_tto->getID(), $ola_ttr->getID()]);
        $this->assertCount(2, $olas_data);
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

        // act - create ticket with OLAs
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => $olas_ids,]);

        // assert - olas are associated with ticket
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

        // act - update ticket with OLAs
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $olas_ids]);
        $ticket = $this->reloadItem($ticket);

        // assert - olas are associated with ticket
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
     * When passing multiple OLA IDs to the ticket, the same OLA ID should not be associated multiple times
     * Just test for update, no need to test for create (process is in the same function)
     */
    public function testUpdateTicketWithDuplicatedOlasNotCompleted(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        $ola = $this->createOLA()['ola'];

        // act - update ticket
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID(), $ola->getID()]]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID()], $fetched_olas, 'Unexpected OLA associated with ticket, duplicated OLA IDs should be ignored');
    }

    public function testUpdateTicketWithDuplicatedOlasButNotAllCompleted(): void
    {
        // arrange
        $this->login();
        $ticket = $this->createTicket();
        $ola = $this->createOLA()['ola'];

        // act - update ticket - associated with a ola
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        // set associated ola as completed
        $io = new Item_Ola();
        $io->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => Ticket::class, 'olas_id' => $ola->getID()]);
        $this->updateItem($io::class, $io->getID(), ['end_time' => \Session::getCurrentTime()] + $io->fields);
        // update ticket with twice the same ola id (one is completed, the other is not)
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID(), $ola->getID()]]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola->getID(),$ola->getID() ], $fetched_olas, 'Unexpected OLA associated with ticket, duplicated OLA IDs should not be ignored if one is completed');
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

        // act - create ticket
        $ticket = $this->createTicket(
            ['olas_id_tto' => $ola_tto->getID(), 'olas_id_ttr' => $ola_ttr->getID()],
            ['olas_id_tto', 'olas_id_ttr'] // these fields don't exist anymore, but here we check backward compatibility.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket using old form params on creation');

        $this->hasPhpLogRecordThatContains('Passing `olas_id_ttr` input to ticket is deprecated.', LogLevel::INFO);
        $this->hasPhpLogRecordThatContains('Passing `olas_id_tto` input to ticket is deprecated.', LogLevel::INFO);
    }

    /**
     * Checks old form params are still working
     * + a warning is logged
     */
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
            ['olas_id_tto', 'olas_id_ttr'] // fields don't exist anymore, so must be ignored in item creation check.
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
            ['olas_id_tto', 'olas_id_ttr'] // fields don't exist anymore, so must be ignored in item creation check.
        );

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing([$ola_tto->getID(), $ola_ttr->getID()], $fetched_olas, 'Unexpected OLA associated with ticket using old form params on update');

        $this->hasPhpLogRecordThatContains('Passing `olas_id_ttr` input to ticket is deprecated.', LogLevel::INFO);
        $this->hasPhpLogRecordThatContains('Passing `olas_id_tto` input to ticket is deprecated.', LogLevel::INFO);
    }

    /**
     * Create a ticket with removed fields trigger runtime exceptions
     */
    public function testCreateTicketWithOlaRemovedFieldsThrowsAnExecption(): void
    {
        $this->login();
        $removed_fields = ['ola_tto_begin_date', 'ola_ttr_begin_date', 'internal_time_to_resolve', 'internal_time_to_own', 'olalevels_id_ttr'];

        foreach ($removed_fields as $removed_field) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageMatches('/Input fields ".*" are not used anymore/');
            $value = $removed_field === 'olalevels_id_ttr' ? 1 : date('Y-m-d 00:00:00');

            $this->createTicket(
                [$removed_field => $value],
                [$removed_field]
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
            $this->expectExceptionMessageMatches('/Input field(s)? .* is|are not used anymore.*/');
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
    #[TestWith([true, true])]
    #[TestWith([false, false])]
    public function testOlaCanBeAssociatedWithAnAllowedGroupOnAdd(bool $group_can_be_assigned_to_ticket, bool $expected_add_return): void
    {
        // arrange
        $test_group = $this->createItem(Group::class, ['is_assign' => (int) $group_can_be_assigned_to_ticket, 'name' => 'test group']);
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

    #[TestWith([true, true])]
    #[TestWith([false, false])]
    public function testOlaCanBeAssociatedWithAnAllowedGroupOnUpdate(bool $group_can_be_assigned_to_ticket, bool $expected_add_return): void
    {
        // arrange
        $allowed_group = $this->createItem(Group::class, ['is_assign' => 1, 'name' => 'allowed group']);
        $test_group = $this->createItem(Group::class, ['is_assign' => (int) $group_can_be_assigned_to_ticket, 'name' => 'test group']);
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

        // end_time, waiting_time, waiting_start are not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
        $this->assertEquals(0, $ola_data['waiting_start'], 'Waiting start time should not be set.');
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
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_association_datetime->format('Y-m-d H:i:s'), $ola_data['start_time'], 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime = $ola_association_datetime->add($this->getDefaultOlaTtoDelayInterval());
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $ola_data['due_time'], 'Due time should be start_time + OLA_TTO_DELAY.');

        // end_time, waiting_time, waiting_start are not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should not be set for TTO OLA.');
        $this->assertEquals(0, $ola_data['waiting_start'], 'Waiting start time should not be set.');
    }

    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - wait 10 minutes, assign ticket to a dedicated group
        $this->setCurrentTime('2025-06-10 09:10:00');
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola_tto->fields['groups_id']]);
        assert($ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_tto->fields['groups_id']]), 'Ticket should be assigned to the dedicated group of the OLA.');
        $ola_data = $ticket->getOlasData()[0];

        // assert : end_time is not set
        $this->assertNull($ola_data['end_time'], 'End time should not be set to the moment ticket is assigned to a dedicated group.');
    }

    public function testOlaTtoIsCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfOlaGroup(): void
    {
        $this->login();
        // --- arrange - create a group, an ola, a user and set the user in the group, assign the ola to the ticket
        ['user' => $user, 'group' => $group] = $this->createAssignableUserInGroup();
        ['ola' => $ola_tto] = $this->createOLA(ola_type: SLM::TTO, group: $group);
        $now = $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket([
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ]);

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        \Session::checkCentralAccess(); // ensure user can update ticket

        // --- act - login as user of dedicated group, wait 10 minutes, assign ticket to a dedicated group
        $this->login($user->fields['name']);
        $assignation_datetime = $now->modify('+10 min');
        $this->setCurrentTime($assignation_datetime->format('Y-m-d H:i:s'));
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.');

        // --- assert : end_time is set to the moment ticket is assigned to a dedicated group
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($assignation_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'End time should be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    public function testOlaTtoIsNotCompleteWhenTicketIsAssignedToUserInDedicatedGroupByUserOfAnotherGroup(): void
    {
        $this->login();

        // --- arrange
        // - create a group + a user in the group
        // - create another user (not in the group)
        // - create an ola tto associated to the group
        // - assign the ola to the ticket
        ['user' => $user_in_ola_group, 'group' => $group] = $this->createAssignableUserInGroup();
        $ola_tto = $this->createOLA(ola_type: SLM::TTO, group: $group)['ola'];
        $this->setCurrentTime('2025-06-10 09:00:00');
        $ticket = $this->createTicket([
            '_la_update' => true,
            '_olas_id' => [$ola_tto->getID()],
        ]);

        ['user' => $other_user] = $this->createAssignableUserInGroup();

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');
        \Session::checkCentralAccess(); // ensure user can update ticket

        // --- act - login as other user, wait 10 minutes, assign ticket to a user in dedicated group
        $this->login($other_user->fields['name']);
        $this->setCurrentTime('2025-06-10 09:10:00');
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_users_id_assign' => $user_in_ola_group->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user_in_ola_group->getID()), 'Ticket should be assigned to a user of ola dedicated group.');

        // --- assert : end_time is not set
        $ola_data = $ticket->getOlasData()[0];
        $this->assertNull($ola_data['end_time'], 'End time should not be set to the moment ticket is assigned to a user of the dedicated group.');
    }

    public function testOlaIsNotCompleteWhenTicketIsAssignedToNonDedicatedGroup(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);
        $non_dedicated_group = getItemByTypeName(Group::class, '_test_group_2');
        assert($non_dedicated_group->getID() !== $ola_tto->fields['groups_id'], 'Non dedicated group should not be the same as the OLA group');

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - assign ticket to a dedicated group
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_groups_id_assign' => $non_dedicated_group->getID()]);
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

        $group = getItemByTypeName(Group::class, '_test_group_2');
        $user = $this->createItem(User::class, ['name' => $this->getUniqueString() ]);
        $user_group = new Group_User();
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $group->getID()]);
        assert($group->fields['is_assign'] == 1, 'Group should be assignable to tickets.');
        assert(!Group_User::isUserInGroup($user->getID(), $ola_tto->fields['groups_id']), 'User should not be in the group of the OLA.');

        $ola_data = $ticket->getOlasData()[0];
        assert(null === $ola_data['end_time'], 'End time should not be set when OLA is assigned to ticket.');

        // act - assign ticket to a user not in the dedicated group
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_users_id_assign' => $user->getID()]);
        assert($ticket->isUser(CommonITILActor::ASSIGN, $user->getID()), 'Ticket should be assigned to a user of ola dedicated group.');

        // assert : end_time is not set
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(null, $ola_data['end_time'], 'End time should not be set when ticket is assigned to a user of a non dedicated group.');
    }

    public function testOlaTtoIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup(): void
    {
        // arrange
        // create ticket with OLA TTO not completed
        $this->login();
        $group_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $group_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $group_n1)['ola'];

        // Ticket assigned to group_desk and ola
        $ticket = $this->createTicket([
            '_groups_id_assign' => $group_desk->getID(),
            '_la_update' => true,
            '_olas_id' => [$ola_tto_n1->getID()],
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $group_n1->getID(),
            ]
        );
        // check OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket');

        // check OLA TTO is not complete
        assert(null == $fetched_ola_data['end_time']);

        // act : remove group associated to OLA TTO from ticket assigned groups, maybe there is a cleaner way to do this
        $gt = new \Group_Ticket();
        assert(true === $gt->deleteByCriteria(['tickets_id' => $ticket->getID(), 'groups_id' => $group_n1->getID()]));

        $ticket = $this->reloadItem($ticket);
        assert(false === $ticket->isGroup(CommonITILActor::ASSIGN, $group_n1->getID()), 'Group N1 should not be assigned to ticket anymore');

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    public function testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithAFollowup(): void
    {
        // create ticket with OLA TTO not completed
        $this->login();
        ['group' => $desk_group] = $this->createAssignableUserInGroup();
        ['user' => $n1_user, 'group' => $n1_group] = $this->createAssignableUserInGroup();
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $n1_group)['ola'];

        // Ticket assigned to desk_group
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
            '_la_update' => true,
            '_olas_id' => [$ola_tto_n1->getID()],
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a followup as a user of the group associated to OLA TTO
        $this->login($n1_user->fields['name']);
        \Session::checkCentralAccess(); // ensure user can update ticket to create a followup

        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'needed to triger update', '_followup' => ['content' => 'the followup content']]);

        // assert OLA TTO is now completed
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNotNull($fetched_ola_data['end_time']);
    }

    /**
     * Same as above but with a task instead of a followup
     */
    public function testOlaTtoIsCompleteWhenUserOfGroupAssociatedToOlaTakesTicketIntoAccountWithATask(): void
    {
        // create ticket with OLA TTO not completed
        $this->login();
        ['group' => $desk_group] = $this->createAssignableUserInGroup();
        ['user' => $n1_user, 'group' => $n1_group] = $this->createAssignableUserInGroup();
        $ola_tto_n1 = $this->createOLA(ola_type: SLM::TTO, group: $n1_group)['ola'];

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
            '_la_update' => true,
            '_olas_id' => [$ola_tto_n1->getID()],
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a task as a user of the group associated to OLA TTO
        $this->login($n1_user->fields['name']);
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
        // create ticket with OLA TTO not completed
        $this->login();
        ['user' => $desk_user, 'group' => $desk_group] = $this->createAssignableUserInGroup();
        ['group' => $n1_group] = $this->createAssignableUserInGroup();
        ['ola' => $ola_tto_n1]  = $this->createOLA(ola_type: SLM::TTO, group: $n1_group);

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $desk_group->getID(),
            '_la_update' => true,
            '_olas_id' => [$ola_tto_n1->getID()],
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $n1_group->getID(),
            ]
        );
        // check OLA TTO is added to ticket
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('OLA TTO should be added to ticket');
        assert($fetched_ola_data['olas_id'] === $ola_tto_n1->getID(), 'OLA TTO should be added to ticket');

        // check OLA TTO is not complete before acting
        assert(null == $fetched_ola_data['end_time']);

        // act : create a followup as a user of the group associated to OLA TTO
        $this->login($desk_user->fields['name']);
        \Session::checkCentralAccess(); // ensure user can update ticket to create a followup
        $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'needed to triger update', '_followup' => ['content' => 'the followup content']]);
        $ticket = $this->reloadItem($ticket);

        // assert OLA TTO is now complete
        $fetched_ola_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Tested OLA TTO not fetched');
        $this->assertNull($fetched_ola_data['end_time']);
    }

    public function testOlaTTODueTimeIsDelayedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup(): void
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $ola_assign_date = $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        assert(false === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should not be assigned to the OLA group.');

        // act : wait one hour and change status to ASSIGNED to trigger due_time recomputing
        $this->setCurrentTime('2025-06-26 10:00:00');
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $fetched_ola_data = $ticket->getOlasData()[0];
        $fetched_due_time = $fetched_ola_data['due_time'];

        $expected_due_time = $ola_assign_date
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->modify('+1 hour') // 1 hour waiting time;
            ->format('Y-m-d H:i:s');

        $this->assertEquals(
            $expected_due_time,
            $fetched_due_time,
            'Due time should be incremented while ticket status is WAITING for an OLA TTO when the ola group is not assigned.'
        );
    }

    public function testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaitingAndAssignedToOlaGroup(): void
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(
            [   '_la_update' => true,
                '_olas_id' => [$ola->getID()],
                '_groups_id_assign' => $ola_group->getID(),
            ]
        );
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        assert(true === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should be assigned to the OLA group.');
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime('2025-06-26 10:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $fetched_ola_data = $ticket->getOlasData()[0];
        $current_due_time = $fetched_ola_data['due_time'];
        $current_waiting_time = $fetched_ola_data['waiting_time'];

        $this->assertEquals(
            (new DateTime($initial_due_time))->format('Y-m-d H:i:s'),
            $current_due_time,
            'Waiting time should not be incremented while ticket status is WAITING for an OLA TTO.'
        );
        $this->assertEquals(null, $current_waiting_time, 'waiting time should null, is not set'); // out of scope here
    }

    public function testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaitingAndAssignedToOlaGroup(): void
    {
        // arrange
        $this->login();
        $creation_time = $this->setCurrentTime('2025-06-26 10:04:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING, '_groups_id_assign' => $ola_group->getID()]);
        assert(true === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should be assigned to the OLA group.');

        // act - wait 20 minutes and switch ticket to assigned
        $this->setCurrentTime($creation_time->modify('+20 min')->format('Y-m-d H:i:s'));
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should be 0 minute after 20 min in WAITING status for an OLA TTO');
    }

    public function testOlaTTOWaitingTimeIsIncrementedWhileTicketStatusIsWaitingAndNotAssignedToOlaGroup(): void
    {
        // arrange
        $this->login();
        $creation_time = $this->setCurrentTime('2025-06-26 10:04:00');
        ['ola' => $ola, 'group' => $ola_group ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING, '_groups_id_assign' => getItemByTypeName(Group::class, '_test_group_2', true)]);
        assert(false === $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola_group->getID()]), 'Ticket should not be assigned to the OLA group.');

        // act - wait 20 minutes and switch ticket to assigned
        $this->setCurrentTime($creation_time->modify('+20 min')->format('Y-m-d H:i:s'));
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(20 * MINUTE_TIMESTAMP, $ola_data['waiting_time'], 'Waiting time should be 20 minutes after 20 min in WAITING status for an OLA TTO');
    }

    public function testOlaCanBeAssociatedByRulesAndByForm(): void
    {
        // arrange - create a rule to assign OLA when priority is 4
        $this->login();
        ['ola' => $ola_by_rule, 'slm' => $slm, 'group' => $group,] = $this->createOLA(ola_type: SLM::TTO);
        $ola_by_form = $this->createOLA(ola_type: SLM::TTO, group: $group, slm: $slm)['ola'];

        $builder = new RuleBuilder('Assign OLA rule', RuleTicket::class);
        $builder->setCondtion(RuleCommonITILObject::ONADD);
        $builder->addCriteria('priority', Rule::PATTERN_IS, 4);
        $builder->addAction('append', 'olas_id', $ola_by_rule->getID());
        $builder->setEntity(0);
        $this->createRule($builder);

        // act - create ticket matching rule criteria and associate OLA
        $ticket = $this->createTicket(['priority' => 4, '_la_update' => true, '_olas_id' => [$ola_by_form->getID()]]);

        // assert - check if the ticket has the 2 OLA associated
        $fetched_ola_ids = array_map(fn($ola_data) => $ola_data['olas_id'], $ticket->getOlasData());
        $this->assertEqualsCanonicalizing([$ola_by_form->getID(), $ola_by_rule->getID()], $fetched_ola_ids);
    }

    public function testOlaTtoDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        // arrange
        $this->login();
        $now = $this->setCurrentTime('2025-06-25 13:00:01');
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $ticket = $this->createTicket(['_olas_id' => [$ola->getID()], '_la_update' => true]);

        // check due time is set correctly
        $initial_expected_due_time_str = $now->add($this->getDefaultOlaTtoDelayInterval())->format('Y-m-d H:i:s');
        $fetched_due_time = $ticket->getOlasTTOData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        assert($initial_expected_due_time_str === $fetched_due_time, 'OLA TTO Due time should be set to the current date + TTO delay interval.');

        // act - update ticket date (in the past)
        $new_date = '2025-06-22 10:00:00';
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['date' => $new_date]);

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

        // check due time is set correctly
        $initial_expected_due_time_str = $now->add($this->getDefaultOlaTtrDelayInterval())->format('Y-m-d H:i:s');
        $fetched_due_time = $ticket->getOlasTTRData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        assert($initial_expected_due_time_str === $fetched_due_time, 'OLA TTR Due time should be set to the current date + TTR delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:02';
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $fetched_due_time = $ticket->getOlasTTRData()[0]['due_time'] ?? throw new RuntimeException('Ola not found for test');
        $this->assertEquals($initial_expected_due_time_str, $fetched_due_time, 'OLA TTR due time is updated when ticket date is changed.');
    }

    public function testInitialOlaTtrValuesOnCreation(): void
    {
        // arrange
        $this->login();
        $ola_ttr = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $start_time = $this->setCurrentTime('2025-06-02 09:00:00');

        // act associate ticket with ola
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_ttr->getID()]]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        // start_time = ola association with ticket
        $this->assertEquals($ola_data['start_time'], $start_time->format('Y-m-d H:i:s'), 'Start time should be set to the moment OLA is assigned to ticket.');

        // due_time is set and equal to start_time + OLA_TTO_DELAY
        $due_time_datetime_str = $start_time->add($this->getDefaultOlaTtrDelayInterval())->format('Y-m-d H:i:s');
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
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

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
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::SOLVED]);

        // assert
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($ticket_resolution_datetime->format('Y-m-d H:i:s'), $ola_data['end_time'], 'Ola ttr end time should be set when ticket is solved/closed.');
    }

    public function testOlaTtrIsCompleteWhenGroupAssociatedToOlaIsRemovedFromTicketAssignedGroup()
    {
        // arrange
        // create ticket with OLA TTR not completed
        $this->login();
        $g_desk = $this->createItem(Group::class, ['name' => 'G_desk', 'is_assign' => 1]);
        $g_n1 = $this->createItem(Group::class, ['name' => 'N1', 'is_assign' => 1]);
        $ola_ttr_n1 = $this->createOLA(ola_type: SLM::TTR, group: $g_n1)['ola'];

        // Ticket assigned to group g_desk
        $ticket = $this->createTicket([
            '_groups_id_assign' => $g_desk->getID(),
            '_la_update' => true,
            '_olas_id' => [$ola_ttr_n1->getID()],
        ]);

        // update ticket : assign to group N1
        $ticket = $this->updateItem(
            Ticket::class,
            $ticket->getID(),
            [
                '_groups_id_assign' => $g_n1->getID(),
            ]
        );
        // check OLA TTR is added to ticket
        $fetched_ola_data = $ticket->getOlasTTRData()[0] ?? throw new \Exception('OLA TTR should be added to ticket');
        assert($fetched_ola_data['olas_id'] === $ola_ttr_n1->getID(), 'OLA TTR should be added to ticket');

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
        $ola_creation_time = $this->setCurrentTime('2025-06-26 09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => CommonITILObject::WAITING]);
        assert(CommonITILObject::WAITING === (int) $ticket->fields['status']);
        $initial_due_time = $ticket->getOlasData()[0]['due_time'];

        // act : wait one hour and change status to trigger due_time recomputing
        $this->setCurrentTime($ola_creation_time->modify('+1 hour')->format('Y-m-d H:i:s'));
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);
        $new_due_time = $ticket->getOlasData()[0]['due_time'];

        $this->assertEquals(
            (new DateTime($initial_due_time))->modify('+1 hour')->format('Y-m-d H:i:s'),
            $new_due_time,
            'Due time should be delayed by one hour because of ticket waiting time'
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
        $this->assertEquals(20 * MINUTE_TIMESTAMP, $ola_data['waiting_time'], 'Waiting time should be incremented by 20 minutes after 20 min in WAITING status for an OLA TTR');
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
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when is just created.');

        // act : complete ola after due time - wait for ola to be late + assign the ola group to the ticket
        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola->fields['groups_id']]);

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when end time is after due time');
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
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when ticket is just created.');

        // act : wait for ola to be late
        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when due time is passed (and end time is not set)');
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
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when ticket is just created.');

        // act : wait for ola to be late, set ticket status to WAITING
        $later = $now
            ->add($this->getDefaultOlaTtoDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert - check ola is late if ticket is not waiting and not late if ticket is in WAITING status
        $ticket = $this->reloadItem($ticket);
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late']);

        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
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
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when ticket is just created.');

        // act : complete ola after due time - wait for ola to be late + assign the ola group to the ticket
        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->updateItem(Ticket::class, $ticket->getID(), ['_groups_id_assign' => $ola->fields['groups_id']]);

        // assert - check ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when end time is after due time');
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
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when ticket is just created.');

        // act : wait for ola to be late
        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
            ->format('Y-m-d H:i:s');
        $this->setCurrentTime($later);
        $this->runOlaCron();

        // assert ola is late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(1, $ola_data['is_late'], 'OLA should be late when due time is passed (and end time is not set)');
    }

    public function testOlaTtrIsNotLateWhenTicketStatusIsNotWating(): void
    {
        // arrange
        $this->login();
        $ola = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $now = $this->setCurrentTime('2025-02-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert ola is not yet late
        $ola_data = $ticket->getOlasData()[0];
        $this->assertFalse((bool) $ola_data['is_late'], 'OLA should not be late when ticket is just created.');

        // act : wait for ola to be late, set ticket status to WAITING
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);

        $later = $now
            ->add($this->getDefaultOlaTtrDelayInterval())
            ->modify('+1 hour') // add 1 hour to ensure end time is after due time
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
        $this->assertEqualsCanonicalizing($created_olas_ttr_ids, array_values($fetched_olas_ttr_ids));
        $this->assertEqualsCanonicalizing($created_olas_tto_ids, array_values($fetched_olas_tto_ids));
    }

    public function testOlaLevelsAreExecutedOnlyOnceByCron(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola, ticket not taken into account
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $now = $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed before its date.');

        // act - both levels are passed, cron runs several times
        $this->setCurrentTime($now->modify('+3 hours')->format('Y-m-d H:i:s'));
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Each passed level should be executed once.');

        $this->runOlaCron();
        $this->runOlaCron();

        // assert - levels are not executed again
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Passed levels must not be executed again on each cron run.');
    }

    public function testOlaLevelsAreNotExecutedAgainOnTicketUpdate(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola, ticket not taken into account
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $now = $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        $this->setCurrentTime($now->modify('+3 hours')->format('Y-m-d H:i:s'));
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Each passed level should be executed once.');

        // act - simple ticket update
        $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'updated name']);

        // assert - levels are not executed again
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Passed levels must not be executed again on ticket update.');
    }

    public function testOlaLevelsAreRescheduledAfterWaiting(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), ticket not taken into account
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        $this->setCurrentTime('2025-06-26 10:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed.');

        // act - ticket is waiting for 1 hour, second level is delayed to 12:44
        $this->setCurrentTime('2025-06-26 10:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        $this->setCurrentTime('2025-06-26 11:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);

        // assert - second level is not executed at its initial date, but once at its new date
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level should be delayed by the waiting time.');

        $this->setCurrentTime('2025-06-26 12:50:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once at its new date.');
    }

    public function testPassedOlaLevelsOfBackdatedTicketAreExecutedOnce(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44)
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 13:04:00');

        // act - ticket created with a date 3 hours ago, both levels are passed
        $ticket = $this->createTicket(['date' => '2025-06-26 10:04:00', '_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - passed levels are executed once, on association only
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Passed levels should be executed when the OLA is associated.');
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Passed levels must not be executed again by the cron.');
    }

    public function testOlaLevelsAreExecutedAfterReopening(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), first level executed
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 10:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed.');

        // act - ticket solved then reopened before the second level
        $this->setCurrentTime('2025-06-26 10:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::SOLVED]);
        $this->setCurrentTime('2025-06-26 11:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);

        // assert - second level is executed once
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once after reopening.');
    }

    public function testFutureOlaLevelsAreExecutedOnceWhenReached(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44)
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // act & assert - each level is executed once, when the cron runs after its date
        $this->setCurrentTime('2025-06-26 10:30:00');
        $this->runOlaCron();
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed before its date.');

        $this->setCurrentTime('2025-06-26 10:35:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed once.');

        $this->setCurrentTime('2025-06-26 11:45:00');
        $this->runOlaCron();
        $this->setCurrentTime('2025-06-26 11:50:00');
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once.');
    }

    public function testOlaLevelsOfTtoAndTtrOnSameTicketAreExecutedOnce(): void
    {
        // arrange - TTO (90 min) and TTR (2 days) OLA, each with 2 levels sending recall_ola
        $this->login();
        $ola_tto = $this->createOlaWithRecallLevels(SLM::TTO);
        $ola_ttr = $this->createOlaWithRecallLevels(SLM::TTR);
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_tto->getID(), $ola_ttr->getID()]]);

        // act & assert - TTO levels passed only
        $this->setCurrentTime('2025-06-26 13:04:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Only TTO levels should be executed, once.');

        // act & assert - TTR levels passed too
        $this->setCurrentTime('2025-07-02 10:04:00'); // TTR due time is 2025-06-30 10:04 (working days calendar)
        $this->runOlaCron();
        $this->runOlaCron();
        $this->updateItem(Ticket::class, $ticket->getID(), ['name' => 'updated name']);
        $this->assertSame(4, $this->countOlaRecalls($ticket), 'TTO and TTR levels should each be executed once.');
    }

    /**
     * Levels are suspended while the ticket is waiting:
     * a level reaching its (non delayed) date while waiting is not executed, then executed once at its date delayed by the waiting time.
     */
    #[TestWith([[]])]
    #[TestWith([[['criteria' => 'status', 'condition' => Rule::PATTERN_IS_NOT, 'pattern' => CommonITILObject::WAITING]]])]
    public function testOlaLevelReachedWhileWaitingIsExecutedOnceAfterWaiting(array $criteria): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), first level executed
        $this->login();
        $ola = $this->createOlaWithRecallLevels(SLM::TTO, $criteria);
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 10:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed.');

        // act - ticket is waiting while the second level date (11:44) is reached
        $this->setCurrentTime('2025-06-26 10:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);
        $this->setCurrentTime('2025-06-26 11:50:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level must not be executed while waiting.');

        // assert - after 75 min of waiting, second level is delayed to 12:59 and executed once
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);
        $this->setCurrentTime('2025-06-26 12:50:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level should be delayed by the waiting time.');
        $this->setCurrentTime('2025-06-26 13:05:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once at its delayed date.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'No level should remain to do.');
    }

    /**
     * Same as testOlaLevelReachedWhileWaitingIsExecutedOnceAfterWaiting(), but the ticket is resumed from the ticket form,
     * which also sends the OLA fields (_la_update + _olas_id).
     */
    public function testOlaLevelReachedWhileWaitingIsDelayedWhenResumedFromTicketForm(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), first level executed
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 10:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed.');

        // act - ticket is waiting while the second level date (11:44) is reached, then resumed from the form
        $this->setCurrentTime('2025-06-26 10:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING, '_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 11:50:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level must not be executed while waiting.');
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING, '_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // assert - after 75 min of waiting, second level is delayed to 12:59, not executed on resume
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level should be delayed by the waiting time, not executed on resume.');
        $this->setCurrentTime('2025-06-26 13:05:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once at its delayed date.');
    }

    public function testOlaLevelsOfOlaAssociatedToWaitingTicketAreExecutedAfterWaiting(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44 without waiting)
        $this->login();
        $ola = $this->createOlaWithRecallLevels();

        // act - OLA associated to a waiting ticket, waiting for 1 hour: levels are delayed to 11:34 and 12:44
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['status' => CommonITILObject::WAITING, '_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 11:00:00');
        $this->runOlaCron();
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed while waiting.');
        $this->setCurrentTime('2025-06-26 11:04:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);

        // assert - each level is executed once at its delayed date
        $this->setCurrentTime('2025-06-26 11:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed at its delayed date.');
        $this->setCurrentTime('2025-06-26 12:50:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once at its delayed date.');
    }

    public function testPassedOlaLevelsOfTwoOlasOfSameTypeAreExecutedOnce(): void
    {
        // arrange - ticket with 2 TTO OLA, each with 2 levels sending recall_ola (10:34 and 11:44)
        $this->login();
        $ola_1 = $this->createOlaWithRecallLevels();
        $ola_2 = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_1->getID(), $ola_2->getID()]]);

        // act - all levels are passed at the same time
        $this->setCurrentTime('2025-06-26 13:04:00');
        $this->runOlaCron();
        $this->assertSame(4, $this->countOlaRecalls($ticket), 'Passed levels of both OLA should be executed.');
        $this->runOlaCron();

        // assert - each level is executed once
        $this->assertSame(4, $this->countOlaRecalls($ticket), 'Passed levels must not be executed again.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'No level should remain to do.');
    }

    public function testOlaLevelsAreExecutedOnceAfterTicketIsRestored(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), ticket put in the trash
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 10:20:00');
        $this->assertTrue($ticket->delete(['id' => $ticket->getID()]));

        // act - levels are passed while the ticket is in the trash
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed while the ticket is in the trash.');

        // assert - levels are executed once after the ticket is restored
        $this->setCurrentTime('2025-06-26 12:05:00');
        $this->assertTrue($ticket->restore(['id' => $ticket->getID()]));
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Passed levels should be executed once after restoration.');
    }

    public function testOlaLevelsToDoAreCleanedWhenOlaIsRemovedFromTicket(): void
    {
        // arrange - ticket with 2 TTO OLA having levels to do
        $this->login();
        $ola_kept = $this->createOlaWithRecallLevels();
        $ola_removed = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_kept->getID(), $ola_removed->getID()]]);
        $this->assertSame(2, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]));

        // act - remove one OLA from the ticket
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_kept->getID()]]);

        // assert - only the level to do of the kept OLA remains, removed OLA levels are never executed
        $todo = (new OlaLevel_Ticket())->find(['tickets_id' => $ticket->getID()]);
        $this->assertCount(1, $todo);
        $this->assertSame(OlaLevel::getFirstOlaLevel($ola_kept->getID()), current($todo)['olalevels_id']);

        $this->setCurrentTime('2025-06-26 13:04:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Only levels of the kept OLA should be executed, once.');
    }

    public function testOlaLevelSettingTicketWaitingSuspendsNextLevels(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), the first one also sets the ticket WAITING
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->createItem(OlaLevelAction::class, [
            'olalevels_id' => OlaLevel::getFirstOlaLevel($ola->getID()),
            'action_type'  => 'assign',
            'field'        => 'status',
            'value'        => CommonITILObject::WAITING,
        ]);
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);

        // act - both levels are passed, the replay of the first level sets the ticket WAITING
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - first level executed once, second level suspended while waiting
        $this->assertSame(CommonITILObject::WAITING, $this->reloadItem($ticket)->fields['status']);
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Second level must not be executed once the first level has set the ticket WAITING.');

        // assert - second level is executed once when the ticket is resumed
        $this->setCurrentTime('2025-06-26 13:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);
        $this->setCurrentTime('2025-06-26 15:00:00');
        $this->runOlaCron();
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Second level should be executed once after waiting.');
    }

    /**
     * Unlike other suspended statuses, the pending level of a closed ticket is dropped when its date is reached
     * (@see OlaLevel_Ticket::doLevelForTicket()): it is not executed after reopening, and passed levels are not replayed.
     */
    public function testOlaLevelReachedWhileClosedIsNotExecutedAfterReopening(): void
    {
        // arrange - TTO OLA (90 min) with 2 levels sending recall_ola (10:34 and 11:44), first level executed
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->setCurrentTime('2025-06-26 10:40:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'First level should be executed.');

        // act - ticket closed while the second level date (11:44) is reached, then reopened
        $this->setCurrentTime('2025-06-26 10:45:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::CLOSED]);
        $this->setCurrentTime('2025-06-26 11:50:00');
        $this->runOlaCron();
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'No level should be executed while the ticket is closed.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'Pending level of a closed ticket is dropped.');

        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);
        $this->setCurrentTime('2025-06-26 14:00:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - passed levels are not replayed after reopening
        $this->assertSame(1, $this->countOlaRecalls($ticket), 'Passed levels must not be replayed after reopening.');
    }

    public function testOlaLevelsWhenOlasAreAddedAndRemovedOnExistingTicket(): void
    {
        // arrange - ticket with a TTO OLA (90 min) whose 2 levels (10:34 and 11:44) are executed
        $this->login();
        $ola_kept = $this->createOlaWithRecallLevels(SLM::TTO);
        $ola_added = $this->createOlaWithRecallLevels(SLM::TTR);
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_kept->getID()]]);
        $this->setCurrentTime('2025-06-26 12:00:00');
        $this->runOlaCron();
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Kept OLA levels should be executed.');

        // act - TTR OLA added (levels not passed yet) from the ticket form
        $this->setCurrentTime('2025-06-26 12:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_kept->getID(), $ola_added->getID()]]);
        $this->runOlaCron();

        // assert - added OLA starts from its first level, kept OLA is not replayed
        $todo = (new OlaLevel_Ticket())->find(['tickets_id' => $ticket->getID()]);
        $this->assertSame([OlaLevel::getFirstOlaLevel($ola_added->getID())], array_column(array_values($todo), 'olalevels_id'));
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Kept OLA levels must not be replayed when an OLA is added.');

        // act - added OLA removed from the ticket form, then its level dates are passed
        $this->setCurrentTime('2025-06-26 12:10:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_kept->getID()]]);
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'Removed OLA levels should be cleaned.');
        $this->setCurrentTime('2025-07-02 10:04:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - removed OLA sends nothing, kept OLA is not replayed
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Removed OLA must not send anything, kept OLA must not be replayed.');
    }

    public function testSeveralPassedOlaLevelsOfWaitingTicketAreKeptThenExecutedOnce(): void
    {
        // arrange - ticket with 2 TTO OLA (90 min, levels at 10:34 and 11:44 without waiting), waiting before any level date
        $this->login();
        $ola_1 = $this->createOlaWithRecallLevels();
        $ola_2 = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_1->getID(), $ola_2->getID()]]);
        $this->setCurrentTime('2025-06-26 10:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::WAITING]);

        // act - several levels to do are passed while waiting
        $this->setCurrentTime('2025-06-26 13:00:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - nothing executed, levels to do kept (no infinite loop)
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed while waiting.');
        $this->assertSame(2, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'Levels to do should be kept while waiting.');

        // act - ticket resumed after ~3 hours of waiting, levels delayed accordingly, then passed
        $this->setCurrentTime('2025-06-26 13:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'Levels should be delayed by the waiting time.');
        $this->setCurrentTime('2025-06-26 17:00:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - each level of each OLA executed once
        $this->assertSame(4, $this->countOlaRecalls($ticket), 'Each level of each OLA should be executed once.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]));
    }

    public function testSeveralPassedOlaLevelsOfClosedTicketAreCleaned(): void
    {
        // arrange - ticket with 2 TTO OLA (90 min, levels at 10:34 and 11:44), closed before any level date
        $this->login();
        $ola_1 = $this->createOlaWithRecallLevels();
        $ola_2 = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola_1->getID(), $ola_2->getID()]]);
        $this->setCurrentTime('2025-06-26 10:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::CLOSED]);
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'Levels to do of a closed ticket should be cleaned when it is closed.');

        // act - several levels to do are passed
        $this->setCurrentTime('2025-06-26 13:00:00');
        $this->runOlaCron();

        // assert - nothing executed, all levels to do cleaned
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed for a closed ticket.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'All passed levels to do of a closed ticket should be cleaned.');
    }

    public function testOlaLevelsOfOlaAssociatedToSolvedTicketAreExecutedAfterReopening(): void
    {
        // arrange - solved ticket without OLA
        $this->login();
        $ola = $this->createOlaWithRecallLevels();
        $this->setCurrentTime('2025-06-26 10:00:00');
        $ticket = $this->createTicket();
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::SOLVED]);

        // act - TTO OLA (90 min, 2 levels) associated while the ticket is solved, then ticket reopened
        $this->setCurrentTime('2025-06-26 10:04:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed while the ticket is solved.');
        $this->setCurrentTime('2025-06-26 10:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::INCOMING]);
        $this->setCurrentTime('2025-06-26 14:00:00');
        $this->runOlaCron();
        $this->runOlaCron();

        // assert - each level executed once after reopening
        $this->assertSame(2, $this->countOlaRecalls($ticket), 'Each level should be executed once after reopening.');
    }

    public function testOlaLevelsToDoOfClosedTicketAreCleanedWhenOlaIsCompleted(): void
    {
        // arrange - ticket with a TTR OLA (90 min, levels at 10:34 and 11:44)
        $this->login();
        $ola = $this->createOlaWithRecallLevels(SLM::TTR);
        $this->setCurrentTime('2025-06-26 10:04:00');
        $ticket = $this->createTicket(['_la_update' => true, '_olas_id' => [$ola->getID()]]);
        $this->assertSame(1, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]));

        // act - ticket closed before any level date (TTR OLA completed: no more recomputed by the cron), then level dates are passed
        $this->setCurrentTime('2025-06-26 10:05:00');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => CommonITILObject::CLOSED]);
        $this->setCurrentTime('2025-06-26 13:00:00');
        $this->runOlaCron();

        // assert - nothing executed, no level to do left
        $this->assertSame(0, $this->countOlaRecalls($ticket), 'No level should be executed for a closed ticket.');
        $this->assertSame(0, countElementsInTable(OlaLevel_Ticket::getTable(), ['tickets_id' => $ticket->getID()]), 'Levels to do of a closed ticket should be cleaned.');
    }

    /**
     * OLA with 2 active levels (1 hour before due time, 10 minutes after due time), each sending the recall_ola notification.
     * The notification is sent to the global administrator (created once).
     *
     * @param array<array{criteria: string, condition: int, pattern: mixed}> $criteria criteria added to each level
     */
    private function createOlaWithRecallLevels(int $ola_type = SLM::TTO, array $criteria = []): OLA
    {
        global $CFG_GLPI;

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;
        $CFG_GLPI['admin_email']           = 'admin@localhost';

        $ola = $this->createOLA(ola_type: $ola_type)['ola'];
        foreach ([-HOUR_TIMESTAMP, 10 * MINUTE_TIMESTAMP] as $execution_time) {
            $level = $this->createItem(OlaLevel::class, [
                'name'           => 'OLA level ' . $execution_time,
                'olas_id'        => $ola->getID(),
                'execution_time' => $execution_time,
                'is_active'      => 1,
                'match'          => 'AND',
            ]);
            $this->createItem(OlaLevelAction::class, [
                'olalevels_id' => $level->getID(),
                'action_type'  => 'send',
                'field'        => 'recall_ola',
                'value'        => 1,
            ]);
            foreach ($criteria as $criterion) {
                $this->createItem(OlaLevelCriteria::class, ['olalevels_id' => $level->getID()] + $criterion);
            }
        }

        if (countElementsInTable(Notification::getTable(), ['event' => 'recall_ola', 'name' => 'OLA recall']) > 0) {
            return $ola;
        }

        $notification = $this->createItem(Notification::class, [
            'name'         => 'OLA recall',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'is_active'    => 1,
            'itemtype'     => Ticket::class,
            'event'        => 'recall_ola',
        ]);
        $template = $this->createItem(NotificationTemplate::class, [
            'name'     => 'OLA recall',
            'itemtype' => Ticket::class,
        ]);
        $this->createItem(NotificationTemplateTranslation::class, [
            'notificationtemplates_id' => $template->getID(),
            'language'                 => '',
            'subject'                  => 'OLA recall ##ticket.id##',
            'content_text'             => 'OLA recall ##ticket.id##',
            'content_html'             => 'OLA recall ##ticket.id##',
        ]);
        $this->createItem(Notification_NotificationTemplate::class, [
            'notifications_id'         => $notification->getID(),
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $template->getID(),
        ]);
        $this->createItem(NotificationTarget::class, [
            'notifications_id' => $notification->getID(),
            'type'             => Notification::USER_TYPE,
            'items_id'         => Notification::GLOBAL_ADMINISTRATOR,
        ]);

        return $ola;
    }

    private function countOlaRecalls(Ticket $ticket): int
    {
        return count((new QueuedNotification())->find([
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'event'    => 'recall_ola',
        ]));
    }

    /**
     * @return array{'user': User, 'group': Group}
     * @throws \Exception
     */
    private function createAssignableUserInGroup(): array
    {
        $group = $this->createItem(Group::class, [
            'name' => 'Ola_group', 'is_assign' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);

        $new_user_name = $this->getUniqueString();
        $user = $this->createItem(
            User::class,
            [
                'name' => $new_user_name,
                'entities_id' => $this->getTestRootEntity(true)]
        );
        $user_group = new Group_User();
        $user_group->add(['users_id' => $user->getID(), 'groups_id' => $group->getID()]);
        assert(Group_User::isUserInGroup($user->getID(), $group->getID()), 'User should be in the group of the OLA.');

        $pu = new \Profile_User();
        $profile_user_ids = $pu->find(['users_id' => $user->getID()]);
        $profile_user_id = array_pop($profile_user_ids)['id'] ?? throw new \Exception('Profile_User not found');
        // assign user to profile Technician, to allow ticket update
        assert(true === $pu->update(['id' => $profile_user_id, 'profiles_id' => getItemByTypeName(\Profile::class, 'Technician', true)]));

        return ['user' => $user, 'group' => $group];
    }
}
