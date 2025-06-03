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
use SLM;
use Ticket;

/**
 * Test Plan / Spec
 *
 * - ola data are retrieved as array : @see self::testGetOLAData()
 * - ola can be associated and deassociate to a ticket :
 *      - single OLA
 *          - at creation time :@see self::testAssociateSingleOlaWithCreatedTicket()
 *          - at update time :@see self::testAssociateSingleOlaWithUpdatedTicket()
 *      - muliple OLA
 *          - at creation time :@see self::testAssociateMultipleOlasWithCreatedTicket()
 *          - at update time :@see self::testAssociateMultipleOlaWithUpdatedTicket()
 *      - ola are unchanged when no ola input is specified : @see self::testUpdateTicketWithoutOlaInputs()
 *      - existing ola associations can be changed : @see self::testDeassociateOlaToTicket()
 *      - multiple times the same ola results in a single association : @see self::testUpdateTicketWithSameOlasInputs()
 *      - Create and update a ticket with old form params (olas_id_tto, olas_id_ttr) still works :
 *          - @see self::testUpdateTicketWithOldFormParams()
 *          - @see self::testUpdateTicketWithSameOlasInputs()
 * - passing removed parameters throws execption ('ola_tto_begin_date', 'ola_ttr_begin_date', ...)
 *      - on create ticket : @see self::testCreateTicketWithOlaRemovedFieldsThrowsAnExecption()
 *      - on update ticket : @see self::testUpdateTicketWithOlaRemovedFieldsThrowsAnExecption()
 *
 * - time computing
 *      - ola tto 'starts' when ola is associated with a ticket : 'start_time' is set to now & 'due_time' is calculated
 *          - on ticket creation : @see self::testOlaTtoStartsWhenOlaIsAssignedAtCreation()
 *          - on ticket update : @see self::testOlaTtoStartsWhenOlaIsAssignedAtUpdate()
 *      - due time is delayed until the ticket is on WAITING status : @see self::testOlaDueTimeIsDelayWhileTicketStatusIsWaiting()
 *
 *      - ola ttr starts when a ticket is assigned to :
 *          - a dedicated group : @see self::testOlaTtrStartsWhenTicketIsAssignedToDedicatedGroup()
 *          - a user in the dedicated group : @see self::testOlaTtrStartsWhenTicketIsAssignedToAUserInDedicatedGroup()
 *      - ola ttr does not start when a ticket is assigned to :
 *          - a group out of dedicated group : @see self::testOlaTtrDoesNotStartWhenTicketIsAssignedToANonDedicatedGroup()
 *          - a user not in dedicated group :  @see self::testOlaTtrDoesNotStartWhenTicketIsAssignedToAUserNotInDedicatedGroup()
 * // @todoseb plan à completer
 */

// @todoseb test à la suppression d'un OLA ? comportement à adopter ? est déjà protégé ?
// @todoseb test à la modification d'un OLA ? : recalcul sur les dates d'échéance ?
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

    public function testAssociateSingleOlaWithCreatedTicket(): void
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

    public function testAssociateSingleOlaWithUpdatedTicket(): void
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

    public function testAssociateMultipleOlasWithCreatedTicket(): void
    {
        // arrange - create 3 OLAs
        $this->login();
        ['ola' => $ola1, 'slm' => $slm, 'group' => $group] = $this->createOLA();
        $ola2 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $ola3 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $olas_ids = [$ola1->getID(), $ola2->getID(), $ola3->getID()];

        // act - create ticket with OLA
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => $olas_ids,]);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing($olas_ids, $fetched_olas, 'Expected OLAs associated with ticket don\'t match the expected IDs');
    }

    public function testAssociateMultipleOlaWithUpdatedTicket(): void
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

    public function testDeassociateOlaToTicket(): void
    {
        // arrange - add 3 olas to a ticket
        $this->login();
        $ticket = $this->createTicket();
        ['ola' => $ola1, 'slm' => $slm, 'group' => $group] = $this->createOLA();
        $ola2 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $ola3 = $this->createOLA(group: $group, slm: $slm)['ola'];
        $olas_ids = [$ola1->getID(), $ola2->getID(), $ola3->getID()];
        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $olas_ids]); // no check needed, tested before

        // act - remove an ola from the ticket
        $updated_olas_ids = [$ola1->getID(), $ola3->getID()]; // $ola2 removed
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $updated_olas_ids]);
        $ticket = $this->reloadItem($ticket);

        // assert
        $fetched_olas = array_column($ticket->getOlasData(), 'olas_id');
        $this->assertEqualsCanonicalizing($updated_olas_ids, $fetched_olas);
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
        $ticket = $this->createTicket(['priority' => 4, '_la_update' => true, '_olas_id' => [$ola_by_form->getID()]]);

        // assert - check if the ticket has the 2 OLA associated
        $fetched_ola_ids = array_map(fn($ola_data) => $ola_data['olas_id'], $ticket->getOlasData());
        $this->assertEqualsCanonicalizing([$ola_by_form->getID(), $ola_by_rule->getID()], $fetched_ola_ids);

    }

    /**
     * When passing multiple OLA IDs to the ticket, the same OLA ID should not be passed associated multiple times
     * Just test for update, no need to test for create (process is in the same function)
     */
    public function testUpdateTicketWithSameOlasInputs(): void
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
     * - start_time is set at the moment the Ola is assigned to the ticket
     * - due_time is start_time + ola_tto duration
     */
    public function testOlaTtoStartsWhenOlaIsAssignedAtCreation(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $start_time_datetime = $this->setCurrentTime('09:00:00');
        $due_time_datetime = clone $start_time_datetime;
        $due_time_datetime->add($this->getDefaultTtoDelayInterval());

        // act associate ticket with ola
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        // test using database object
        $item_ola = new \Item_Ola();
        assert(true === $item_ola->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => $ticket::getType(), 'olas_id' => $ola_tto->getID()]), 'failed to find created Item_Ola');
        // start_time is now
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['start_time']);
        // due_time is set to now() + OLA_TTO_DELAY
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['due_time']);

        // test using getAssociatedOlas()
        $ola = $ticket->getOlasData()[0];
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $ola['start_time']);
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $ola['due_time']);
    }

    public function testOlaTtoStartsWhenOlaIsAssignedAtUpdate(): void
    {
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $start_time_datetime = $this->setCurrentTime('09:00:00');
        $due_time_datetime = clone $start_time_datetime;
        $due_time_datetime->add($this->getDefaultTtoDelayInterval());
        $ticket = $this->createTicket();

        // act associate ticket with ola
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);

        // assert
        // test using database object
        $item_ola = new \Item_Ola();
        assert(true === $item_ola->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => $ticket::getType(), 'olas_id' => $ola_tto->getID()]), 'failed to find created Item_Ola');
        // start_time is now
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['start_time']);
        // due_time is set to now() + OLA_TTO_DELAY
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['due_time']);

        // test using getAssociatedOlas()
        $ola = $ticket->getOlasData()[0];
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $ola['start_time']);
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $ola['due_time']);
    }

    public function testOlaTTRDueTimeIsDelayedWhileTicketStatusIsWaiting()
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
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

    public function testOlaTTODueTimeIsNotDelayedWhileTicketStatusIsWaiting()
    {
        $this->login();

        // arrange create ticket with OLA at 09:00:00, status WAITING
        $this->setCurrentTime('09:00:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
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

    public function testOlaTTRWaitingTimeIsIncrementedWhileTicketStatusIsWaiting()
    {
        $this->login();
        $this->setCurrentTime('10:04:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTR);
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert($ticket->fields['status'] === CommonITILObject::WAITING);
        $this->setCurrentTime('10:24:00');
        $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(20 * 60, $ola_data['waiting_time'], 'Waiting time should be incremented by 20 minutes after 20 min in WAITING status for an OLA TTR');
    }

    public function testOlaTTOWaitingTimeIsNotIncrementedWhileTicketStatusIsWaiting()
    {
        $this->login();
        $this->setCurrentTime('10:04:00');
        ['ola' => $ola ] = $this->createOLA(ola_type: SLM::TTO);
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola->getID()], 'status' => \CommonITILObject::WAITING]);
        assert($ticket->fields['status'] === CommonITILObject::WAITING);
        $this->setCurrentTime('10:24:00');
        $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => CommonITILObject::ASSIGNED]);

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals(0, $ola_data['waiting_time'], 'Waiting time should be 0 minute after 20 min in WAITING status for an OLA TTO');
    }

    /**
     * - start_time is set at the moment the Ola is assigned to the dedicated group
     * - then due_time is start_time + ola_ttr duration
     */
    public function testOlaTtrStartsWhenTicketIsAssignedToDedicatedGroup(): void
    {
        $this->markTestIncomplete('implement me');
        $this->login();
        // arrange
        $ola_tto = $this->createOLA(ola_type: \SLM::TTO)['ola'];
        $start_time_datetime = $this->setCurrentTime('09:00:00');
        $due_time_datetime = clone $start_time_datetime;
        $due_time_datetime->add($this->getDefaultTtoDelayInterval());

        // act - associate ticket with ola
        $ticket = $this->createTicket( ['_la_update' => true, '_olas_id' => [$ola_tto->getID()]]);
        // - one hour later, assign the ticket to a non dedicated group
        throw new \Exception('implement me');
        // @todoseb test assignation à un user qui n'est pas du group
        // - assign

        // assert
        // test using database object
        $item_ola = new \Item_Ola();
        assert(true === $item_ola->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => $ticket::getType(), 'olas_id' => $ola_tto->getID()]), 'failed to find created Item_Ola');
        // start_time is now
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['start_time']);
        // due_time is set to now() + OLA_TTO_DELAY
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $item_ola->fields['due_time']);

        // test using getAssociatedOlas()
        $ola = $ticket->getOlasData()[0];
        $this->assertEquals($start_time_datetime->format('Y-m-d H:i:s'), $ola['start_time']);
        $this->assertEquals($due_time_datetime->format('Y-m-d H:i:s'), $ola['due_time']);
    }

    public function testOlaTtrStartsWhenTicketIsAssignedToAUserInDedicatedGroup(): void
    {
        $this->markTestIncomplete('implement me');
    }

    public function testOlaTtrDoesNotStartWhenTicketIsAssignedToANonDedicatedGroup(): void
    {
        $this->markTestIncomplete('implement me');
    }

    public function testOlaTtrDoesNotStartWhenTicketIsAssignedToAUserNotInDedicatedGroup(): void
    {
        $this->markTestIncomplete('implement me');
    }

    private function getDefaultTtoDelayInterval(): \DateInterval
    {
        [$amount, $unit] = self::OLA_TTO_DELAY;

        return new \DateInterval(sprintf('PT%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }
}
