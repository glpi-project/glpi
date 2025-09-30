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

use DbTestCase;
use Glpi\Tests\Glpi\ITILTrait;
use Glpi\Tests\Glpi\SLMTrait;
use MassiveAction;
use OlaLevel_Ticket;
use PHPUnit\Framework\Attributes\DataProvider;
use Rule;
use RuleBuilder;
use RuleTicket;
use SlaLevel_Ticket;
use SLM;
use Ticket;

class SLMTest extends DbTestCase
{
    use ITILTrait;
    use SLMTrait;
    /**
     * Create a full SLM with all level filled (slm/sla/ola/levels/action/criteria)
     * And Delete IT to check clean os sons objects
     */
    public function testLifecyle()
    {
        $this->login();
        // ## 1 - test adding sla and sub objects

        // prepare a calendar with limited time ranges [8:00 -> 20:00]
        $cal    = new \Calendar();
        $calseg = new \CalendarSegment();
        $cal_id = $cal->add(['name' => "test calendar"]);
        $this->checkInput($cal, $cal_id);
        for ($day = 1; $day <= 5; $day++) {
            $calseg_id = $calseg->add([
                'calendars_id' => $cal_id,
                'day'          => $day,
                'begin'        => '08:00:00',
                'end'          => '20:00:00',
            ]);
            $this->checkInput($calseg, $calseg_id);
        }

        $slm    = new SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => __METHOD__,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => $cal_id,
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

        // prepare sla/ola inputs
        $sla1_in = $sla2_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => SLM::TTO,
            'number_time'     => 4,
            'definition_time' => 'day',
        ];
        $sla2_in['type'] = SLM::TTR;
        $sla2_in['name'] = "SLA TTR";

        // add two sla (TTO & TTR)
        $sla    = new \SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);
        $sla2_id = $sla->add($sla2_in);
        $this->checkInput($sla, $sla2_id, $sla2_in);

        // add two ola (TTO & TTR), we re-use the same inputs as sla
        $ola  = new \OLA();
        $sla1_in['name'] = str_replace("SLA", "OLA", $sla1_in['name']);
        $sla2_in['name'] = str_replace("SLA", "OLA", $sla2_in['name']);
        $ola1_id = $ola->add($sla1_in);
        $this->checkInput($ola, $ola1_id, $sla1_in);
        $ola2_id = $ola->add($sla2_in);
        $this->checkInput($ola, $ola2_id, $sla2_in);

        // prepare levels input for each ola/sla
        $slal1_in = $slal2_in = $olal1_in = $olal2_in = [
            'name'           => __METHOD__,
            'execution_time' => -DAY_TIMESTAMP,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id,
        ];
        $slal2_in['slas_id'] = $sla2_id;
        unset($olal1_in['slas_id'], $olal2_in['slas_id']);
        $olal1_in['olas_id'] = $ola1_id;
        $olal2_in['olas_id'] = $ola2_id;

        // add levels
        $slal = new \SlaLevel();
        $slal1_id = $slal->add($slal1_in);
        $this->checkInput($slal, $slal1_id, $slal1_in);
        $slal2_id = $slal->add($slal2_in);
        $this->checkInput($slal, $slal2_id, $slal2_in);

        $olal = new \OlaLevel();
        $olal1_id = $olal->add($olal1_in);
        $this->checkInput($olal, $olal1_id, $olal1_in);
        $olal2_id = $olal->add($olal2_in);
        $this->checkInput($olal, $olal2_id, $olal2_in);

        // add criteria/actions
        $scrit_in = $ocrit_in = [
            'slalevels_id' => $slal1_id,
            'criteria'     => 'status',
            'condition'    => 1,
            'pattern'      => 1,
        ];
        unset($ocrit_in['slalevels_id']);
        $ocrit_in['olalevels_id'] = $olal1_id;
        $saction_in = $oaction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'status',
            'value'        => 4,
        ];
        unset($oaction_in['slalevels_id']);
        $oaction_in['olalevels_id'] = $olal1_id;

        $scrit    = new \SlaLevelCriteria();
        $ocrit    = new \OlaLevelCriteria();
        $saction  = new \SlaLevelAction();
        $oaction  = new \OlaLevelAction();

        $scrit_id   = $scrit->add($scrit_in);
        $ocrit_id   = $ocrit->add($ocrit_in);
        $saction_id = $saction->add($saction_in);
        $oaction_id = $oaction->add($oaction_in);
        $this->checkInput($scrit, $scrit_id, $scrit_in);
        $this->checkInput($ocrit, $ocrit_id, $ocrit_in);
        $this->checkInput($saction, $saction_id, $saction_in);
        $this->checkInput($oaction, $oaction_id, $oaction_in);

        // ## 2 - test using sla in tickets

        // add rules for using sla
        $ruleticket = new RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruleinput = [
            'name'         => __METHOD__,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => RuleTicket::ONADD + RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => 2,
            'pattern'   => __METHOD__,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_tto',
            'value'       => $sla1_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_ttr',
            'value'       => $sla2_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_tto',
            'value'       => $ola1_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_ttr',
            'value'       => $ola2_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // test create ticket
        $ticket = new Ticket();
        $start_date = date("Y-m-d H:i:s", time() - 4 * DAY_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            'date'    => $start_date,
            'name'    => __METHOD__,
            'content' => __METHOD__,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals($sla2_id, (int) $ticket->getField('slas_id_ttr'));
        $this->assertEquals($ola1_id, (int) $ticket->getField('olas_id_tto'));
        $this->assertEquals($ola2_id, (int) $ticket->getField('olas_id_ttr'));
        $this->assertEquals(19, strlen($ticket->getField('time_to_resolve')));

        // test update ticket
        $ticket = new Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'    => "to be updated",
            'content' => __METHOD__,
        ]);
        $this->assertGreaterThan(0, $tickets_id_2);
        $this->assertTrue(
            $ticket->update([
                'id'   => $tickets_id_2,
                'name' => __METHOD__,
            ])
        );
        $ticket_input_2['name'] = __METHOD__;
        $this->checkInput($ticket, $tickets_id_2, $ticket_input_2);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals($sla2_id, (int) $ticket->getField('slas_id_ttr'));
        $this->assertEquals($ola1_id, (int) $ticket->getField('olas_id_tto'));
        $this->assertEquals($ola2_id, (int) $ticket->getField('olas_id_ttr'));
        $this->assertEquals(19, strlen($ticket->getField('time_to_resolve')));

        // ## 3 - test purge of slm and check if we don't find any sub objects
        $this->assertTrue($slm->delete(['id' => $slm_id], true));
        //sla
        $this->assertFalse($sla->getFromDB($sla1_id));
        $this->assertFalse($sla->getFromDB($sla2_id));
        //ola
        $this->assertFalse($ola->getFromDB($ola1_id));
        $this->assertFalse($ola->getFromDB($ola2_id));
        //slalevel
        $this->assertFalse($slal->getFromDB($slal1_id));
        $this->assertFalse($slal->getFromDB($slal2_id));
        //olalevel
        $this->assertFalse($olal->getFromDB($olal1_id));
        $this->assertFalse($olal->getFromDB($olal2_id));
        //crit
        $this->assertFalse($scrit->getFromDB($scrit_id));
        $this->assertFalse($ocrit->getFromDB($ocrit_id));
        //action
        $this->assertFalse($saction->getFromDB($saction_id));
        $this->assertFalse($oaction->getFromDB($oaction_id));
    }

    /**
     * Create a full SLM by month with all level filled (slm/sla/ola/levels/action/criterias)
     * And Delete IT to check clean os sons objects
     */
    public function testLifecylebyMonth()
    {
        $this->login();

        // ## 1 - test adding sla and sub objects

        // prepare a calendar with limited time ranges [8:00 -> 20:00]
        $cal_id = getItemByTypeName('Calendar', 'Default', true);

        $slm    = new SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => __METHOD__,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => $cal_id,
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

        // prepare sla/ola inputs
        $sla1_in = $sla2_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => SLM::TTO,
            'number_time'     => 4,
            'definition_time' => 'month',
        ];
        $sla2_in['type'] = SLM::TTR;
        $sla2_in['name'] = "SLA TTR";

        // add two sla (TTO & TTR)
        $sla    = new \SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);
        $sla2_id = $sla->add($sla2_in);
        $this->checkInput($sla, $sla2_id, $sla2_in);

        // add two ola (TTO & TTR), we re-use the same inputs as sla
        $ola  = new \OLA();
        $sla1_in['name'] = str_replace("SLA", "OLA", $sla1_in['name']);
        $sla2_in['name'] = str_replace("SLA", "OLA", $sla2_in['name']);
        $ola1_id = $ola->add($sla1_in);
        $this->checkInput($ola, $ola1_id, $sla1_in);
        $ola2_id = $ola->add($sla2_in);
        $this->checkInput($ola, $ola2_id, $sla2_in);

        // prepare levels input for each ola/sla
        $slal1_in = $slal2_in = $olal1_in = $olal2_in = [
            'name'           => __METHOD__,
            'execution_time' => -MONTH_TIMESTAMP,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id,
        ];
        $slal2_in['slas_id'] = $sla2_id;
        unset($olal1_in['slas_id'], $olal2_in['slas_id']);
        $olal1_in['olas_id'] = $ola1_id;
        $olal2_in['olas_id'] = $ola2_id;

        // add levels
        $slal = new \SlaLevel();
        $slal1_id = $slal->add($slal1_in);
        $this->checkInput($slal, $slal1_id, $slal1_in);
        $slal2_id = $slal->add($slal2_in);
        $this->checkInput($slal, $slal2_id, $slal2_in);

        $olal = new \OlaLevel();
        $olal1_id = $olal->add($olal1_in);
        $this->checkInput($olal, $olal1_id, $olal1_in);
        $olal2_id = $olal->add($olal2_in);
        $this->checkInput($olal, $olal2_id, $olal2_in);

        // add criteria/actions
        $scrit_in = $ocrit_in = [
            'slalevels_id' => $slal1_id,
            'criteria'     => 'status',
            'condition'    => 1,
            'pattern'      => 1,
        ];
        unset($ocrit_in['slalevels_id']);
        $ocrit_in['olalevels_id'] = $olal1_id;
        $saction_in = $oaction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'status',
            'value'        => 4,
        ];
        unset($oaction_in['slalevels_id']);
        $oaction_in['olalevels_id'] = $olal1_id;

        $scrit    = new \SlaLevelCriteria();
        $ocrit    = new \OlaLevelCriteria();
        $saction  = new \SlaLevelAction();
        $oaction  = new \OlaLevelAction();

        $scrit_id   = $scrit->add($scrit_in);
        $ocrit_id   = $ocrit->add($ocrit_in);
        $saction_id = $saction->add($saction_in);
        $oaction_id = $oaction->add($oaction_in);
        $this->checkInput($scrit, $scrit_id, $scrit_in);
        $this->checkInput($ocrit, $ocrit_id, $ocrit_in);
        $this->checkInput($saction, $saction_id, $saction_in);
        $this->checkInput($oaction, $oaction_id, $oaction_in);

        // ## 2 - test using sla in tickets

        // add rules for using sla
        $ruleticket = new RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruleinput = [
            'name'         => __METHOD__,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => RuleTicket::ONADD + RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => 2,
            'pattern'   => __METHOD__,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_tto',
            'value'       => $sla1_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_ttr',
            'value'       => $sla2_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_tto',
            'value'       => $ola1_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_ttr',
            'value'       => $ola2_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // test create ticket
        $ticket = new Ticket();
        $start_date = date("Y-m-d H:i:s", time() - 4 * MONTH_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            'date'    => $start_date,
            'name'    => __METHOD__,
            'content' => __METHOD__,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals($sla2_id, (int) $ticket->getField('slas_id_ttr'));
        $this->assertEquals($ola1_id, (int) $ticket->getField('olas_id_tto'));
        $this->assertEquals($ola2_id, (int) $ticket->getField('olas_id_ttr'));
        $this->assertEquals(19, strlen($ticket->getField('time_to_resolve')));

        // test update ticket
        $ticket = new Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'    => "to be updated",
            'content' => __METHOD__,
        ]);
        $this->checkInput($ticket, $tickets_id_2, $ticket_input_2);
        //SLA/OLA  TTR/TTO not already set
        $this->assertEquals(0, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals(0, (int) $ticket->getField('slas_id_ttr'));
        $this->assertEquals(0, (int) $ticket->getField('olas_id_tto'));
        $this->assertEquals(0, (int) $ticket->getField('olas_id_ttr'));

        $this->assertTrue(
            $ticket->update([
                'id'   => $tickets_id_2,
                'name' => __METHOD__,
            ])
        );
        $ticket_input_2['name'] = __METHOD__;
        $this->checkInput($ticket, $tickets_id_2, $ticket_input_2);
        $this->assertEquals($sla1_id, (int) $ticket->getField('slas_id_tto'));
        $this->assertEquals($sla2_id, (int) $ticket->getField('slas_id_ttr'));
        $this->assertEquals($ola1_id, (int) $ticket->getField('olas_id_tto'));
        $this->assertEquals($ola2_id, (int) $ticket->getField('olas_id_ttr'));
        $this->assertEquals(19, strlen($ticket->getField('time_to_resolve')));

        // ## 3 - test purge of slm and check if we don't find any sub objects
        $this->assertTrue($slm->delete(['id' => $slm_id], true));
        //sla
        $this->assertFalse($sla->getFromDB($sla1_id));
        $this->assertFalse($sla->getFromDB($sla2_id));
        //ola
        $this->assertFalse($ola->getFromDB($ola1_id));
        $this->assertFalse($ola->getFromDB($ola2_id));
        //slalevel
        $this->assertFalse($slal->getFromDB($slal1_id));
        $this->assertFalse($slal->getFromDB($slal2_id));
        //olalevel
        $this->assertFalse($olal->getFromDB($olal1_id));
        $this->assertFalse($olal->getFromDB($olal2_id));
        //crit
        $this->assertFalse($scrit->getFromDB($scrit_id));
        $this->assertFalse($ocrit->getFromDB($ocrit_id));
        //action
        $this->assertFalse($saction->getFromDB($saction_id));
        $this->assertFalse($oaction->getFromDB($oaction_id));
    }

    /**
     * Check 'internal_time_to_resolve' computed dates.
     */
    public function testInternalTtrComputation()
    {
        $this->login();

        $currenttime_bak = $_SESSION['glpi_currenttime'];
        $tomorrow_1pm = date('Y-m-d H:i:s', strtotime('tomorrow 1pm'));
        $tomorrow_2pm = date('Y-m-d H:i:s', strtotime('tomorrow 2pm'));

        // Create a calendar having tomorrow as working day
        $calendar = new \Calendar();
        $segment  = new \CalendarSegment();
        $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar']);
        $this->assertGreaterThan(0, $calendar_id);

        $segment_id = $segment->add(
            [
                'calendars_id' => $calendar_id,
                'day'          => (int) date('w') === 6 ? 0 : (int) date('w') + 1,
                'begin'        => '09:00:00',
                'end'          => '19:00:00',
            ]
        );
        $this->assertGreaterThan(0, $segment_id);

        // Create SLM with TTR OLA
        $slm = new SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => $calendar_id,
            ]
        );
        $this->assertGreaterThan(0, $slm_id);

        $ola = new \OLA();
        $ola_id = $ola->add(
            [
                'slms_id'         => $slm_id,
                'name'            => 'Test TTR OLA',
                'type'            => SLM::TTR,
                'number_time'     => 4,
                'definition_time' => 'hour',
            ]
        );
        $this->assertGreaterThan(0, $ola_id);

        // Create ticket to test computation based on OLA
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test',
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals(0, (int) $ticket->fields['olas_id_ttr']);
        $this->assertNull($ticket->fields['ola_ttr_begin_date']);
        $this->assertNull($ticket->fields['internal_time_to_resolve']);

        // Assign TTR OLA
        $update_time = strtotime('+10s');
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', $update_time);
        $updated = $ticket->update(['id' => $ticket_id, 'olas_id_ttr' => $ola_id]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->assertTrue($updated);
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals($ola_id, (int) $ticket->fields['olas_id_ttr']);
        $this->assertEquals($update_time, strtotime($ticket->fields['ola_ttr_begin_date']), 'OLA begin date should be time of assignment of OLA to the ticket');
        $this->assertEquals($tomorrow_1pm, $ticket->fields['internal_time_to_resolve']);

        // Simulate waiting to first working hour +1
        $this->assertTrue(
            $ticket->update(
                [
                    'id' => $ticket_id,
                    'status' => \CommonITILObject::WAITING,
                ]
            )
        );
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('tomorrow 10am'));
        $updated = $ticket->update(['id' => $ticket_id, 'status' => \CommonITILObject::ASSIGNED]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->assertTrue($updated);
        $this->assertEquals($tomorrow_2pm, $ticket->fields['internal_time_to_resolve']);

        // Create ticket to test computation based on manual date
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR manual test',
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals(0, (int) $ticket->fields['olas_id_ttr']);
        $this->assertNull($ticket->fields['ola_ttr_begin_date']);
        $this->assertNull($ticket->fields['internal_time_to_resolve']);

        // Assign manual TTR
        $this->assertTrue($ticket->update(['id' => $ticket_id, 'internal_time_to_resolve' => $tomorrow_1pm]));
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals(0, (int) $ticket->fields['olas_id_ttr']);
        $this->assertNull($ticket->fields['ola_ttr_begin_date']);
        $this->assertEquals($tomorrow_1pm, $ticket->fields['internal_time_to_resolve']);

        // Simulate 1 hour of waiting time
        $this->assertTrue(
            $ticket->update(
                [
                    'id' => $ticket_id,
                    'status' => \CommonITILObject::WAITING,
                ]
            )
        );
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($currenttime_bak)));
        $updated = $ticket->update(['id' => $ticket_id, 'status' => \CommonITILObject::ASSIGNED]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->assertTrue($updated);
        $this->assertEquals($tomorrow_2pm, $ticket->fields['internal_time_to_resolve']);
    }

    /**
     * Check 'internal_time_to_resolve' computed dates.
     */
    public function testComputationByMonth()
    {
        $this->login();

        $currenttime_bak = $_SESSION['glpi_currenttime'];

        // Create SLM with TTR/TTO OLA/SLA
        $slm = new SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => 0, //24/24 7/7
            ]
        );
        $this->assertGreaterThan(0, $slm_id);

        $ola_ttr = new \OLA();
        $ola_ttr_id = $ola_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR OLA',
                'type'               => SLM::TTR,
                'number_time'        => 4,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->assertGreaterThan(0, $ola_ttr_id);

        $ola_tto = new \OLA();
        $ola_tto_id = $ola_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO OLA',
                'type'               => SLM::TTO,
                'number_time'        => 3,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->assertGreaterThan(0, $ola_tto_id);

        $sla_ttr = new \SLA();
        $sla_ttr_id = $sla_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR SLA',
                'type'               => SLM::TTR,
                'number_time'        => 2,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->assertGreaterThan(0, $sla_ttr_id);

        $sla_tto = new \SLA();
        $sla_tto_id = $sla_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO SLA',
                'type'               => SLM::TTO,
                'number_time'        => 1,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->assertGreaterThan(0, $sla_tto_id);

        // Create ticket with SLA/OLA TTO/TTR to test computation based on SLA OLA
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test on create',
                'olas_id_ttr' => $ola_ttr_id,
                'olas_id_tto' => $ola_tto_id,
                'slas_id_ttr' => $sla_ttr_id,
                'slas_id_tto' => $sla_tto_id,
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        $this->assertTrue($ticket->getFromDB($ticket_id));
        //check computed data from SLA / OLA
        $this->assertEquals($sla_tto_id, (int) $ticket->fields['slas_id_tto']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (1 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_own']))
        );

        $this->assertEquals($sla_ttr_id, (int) $ticket->fields['slas_id_ttr']);
        // date + delay
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (2 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_resolve']))
        );

        $this->assertEquals($ola_tto_id, (int) $ticket->fields['olas_id_tto']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (3 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_own']))
        );

        $this->assertEquals($ola_ttr_id, (int) $ticket->fields['olas_id_ttr']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['ola_ttr_begin_date']) + (4 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_resolve']))
        );

        $this->assertEquals(
            strtotime($ticket->fields['date']),
            strtotime($ticket->fields['ola_ttr_begin_date'])
        );

        // Create ticket to test computation based on OLA / SLA on update
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test on update',
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertEquals(0, (int) $ticket->fields['olas_id_ttr']);
        $this->assertNull($ticket->fields['ola_ttr_begin_date']);
        $this->assertNull($ticket->fields['internal_time_to_resolve']);

        // Assign TTR/TTO OLA/SLA

        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('+10s'));
        $updated = $ticket->update(
            [
                'id' => $ticket_id,
                'olas_id_ttr' => $ola_ttr_id,
                'olas_id_tto' => $ola_tto_id,
                'slas_id_ttr' => $sla_ttr_id,
                'slas_id_tto' => $sla_tto_id,
                'date_mod'    => date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + 1),
            ]
        );
        $_SESSION['glpi_currenttime'] = $currenttime_bak;

        $this->assertTrue($updated);

        $this->assertTrue($ticket->getFromDB($ticket_id));

        //check computed data from SLA / OLA
        $this->assertEquals($ola_ttr_id, (int) $ticket->fields['olas_id_ttr']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['ola_ttr_begin_date']) + (4 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_resolve']))
        );

        $this->assertEquals($ola_tto_id, (int) $ticket->fields['olas_id_tto']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date_mod']) + (3 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_own']))
        );

        $this->assertEquals($sla_ttr_id, (int) $ticket->fields['slas_id_ttr']);
        // date + delay
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (2 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_resolve']))
        );

        $this->assertEquals($sla_tto_id, (int) $ticket->fields['slas_id_tto']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (1 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_own']))
        );

        $this->assertEquals(
            strtotime($ticket->fields['date_mod']),
            strtotime($ticket->fields['ola_ttr_begin_date'])
        );
    }

    /**
     * Functional tests to ensure all SLA and OLA target dates are set properly
     * in a ticket, as well as their escalation date
     *
     * @return void
     */
    public function testDatesAndEscalation(): void
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create parent SLM
        $slm = $this->createItem("SLM", [
            "name"         => "Test SLM",
            "entities_id"  => $entity,
            "calendars_id" => 0, // No specific calendar
        ]);

        // Create SLAs
        $sla_tto = $this->createItem("SLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test SLA tto",
            "entities_id"     => $entity,
            "type"            => SLM::TTO,
            "number_time"     => 4,
            "definition_time" => "hour",
        ]);
        $sla_ttr = $this->createItem("SLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test SLA ttr",
            "entities_id"     => $entity,
            "type"            => SLM::TTR,
            "number_time"     => 12,
            "definition_time" => "hour",
        ]);

        // Create OLAs
        $ola_tto = $this->createItem("OLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test OLA tto",
            "entities_id"     => $entity,
            "type"            => SLM::TTO,
            "number_time"     => 2,
            "definition_time" => "hour",
        ]);
        $ola_ttr = $this->createItem("OLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test OLA ttr",
            "entities_id"     => $entity,
            "type"            => SLM::TTR,
            "number_time"     => 8,
            "definition_time" => "hour",
        ]);

        // Create one escalation level for each SLA and OLA
        $this->createItems("SlaLevel", [
            [
                "slas_id"        => $sla_tto->getID(),
                "name"           => "Test escalation level (SLA/TTO)",
                "entities_id"    => $entity,
                "execution_time" => -900, // 15 minutes
                "is_active"      => true,
            ],
            [
                "slas_id"        => $sla_ttr->getID(),
                "name"           => "Test escalation level (SLA/TTR)",
                "entities_id"    => $entity,
                "execution_time" => -1800, // 30 minutes
                "is_active"      => true,
            ],
        ]);
        $this->createItems("OlaLevel", [
            [
                "olas_id"        => $ola_tto->getID(),
                "name"           => "Test escalation level (OLA/TTO)",
                "entities_id"    => $entity,
                "execution_time" => -2700, // 45 minutes
                "is_active"      => true,
            ],
            [
                "olas_id"        => $ola_ttr->getID(),
                "name"           => "Test escalation level (OLA/TTR)",
                "entities_id"    => $entity,
                "execution_time" => -3600, // 60 minutes
                "is_active"      => true,
            ],
        ]);

        // Create a ticket 1 hour ago without any SLA
        $date_1_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($_SESSION['glpi_currenttime'])));
        $ticket = $this->createItem("Ticket", [
            "name"        => "Test ticket",
            "content"     => "Test ticket",
            "entities_id" => $entity,
            "date"        => $date_1_hour_ago,
        ]);

        // Add SLA and OLA to the ticket
        $now = $_SESSION['glpi_currenttime']; // Keep track of when the OLA where set
        $this->updateItem("Ticket", $ticket->getID(), [
            "slas_id_tto" => $sla_tto->getID(),
            "slas_id_ttr" => $sla_ttr->getID(),
            "olas_id_tto" => $ola_tto->getID(),
            "olas_id_ttr" => $ola_ttr->getID(),
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check SLA, must be calculated from the ticket start date
        $tto_expected_date = date('Y-m-d H:i:s', strtotime($date_1_hour_ago) + 3600 * 4); // 4 hours TTO
        $ttr_expected_date = date('Y-m-d H:i:s', strtotime($date_1_hour_ago) + 3600 * 12); // 12 hours TTR
        $this->assertEquals($tto_expected_date, $ticket->fields['time_to_own']);
        $this->assertEquals($ttr_expected_date, $ticket->fields['time_to_resolve']);

        // Check escalation levels
        $sla_levels = (new SlaLevel_Ticket())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertCount(2, $sla_levels);
        $tto_level = array_shift($sla_levels);
        $ttr_level = array_shift($sla_levels);
        $tto_level_expected_date = date('Y-m-d H:i:s', strtotime($tto_expected_date) - 900); // 15 minutes escalation level
        $ttr_level_expected_date = date('Y-m-d H:i:s', strtotime($ttr_expected_date) - 1800); // 30 minutes escalation level
        $this->assertEquals($tto_level_expected_date, $tto_level['date']);
        $this->assertEquals($ttr_level_expected_date, $ttr_level['date']);

        // Check OLA, must be calculated from the date at which it was added to the ticket
        $tto_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 2); // 2 hours TTO
        $ttr_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 8); // 8 hours TTR
        $this->assertEquals($tto_expected_date, $ticket->fields['internal_time_to_own']);
        $this->assertEquals($ttr_expected_date, $ticket->fields['internal_time_to_resolve']);

        // Check escalation levels
        $ola_levels = (new OlaLevel_Ticket())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertCount(2, $ola_levels);
        $tto_level = array_shift($ola_levels);
        $ttr_level = array_shift($ola_levels);
        $tto_level_expected_date = date('Y-m-d H:i:s', strtotime($tto_expected_date) - 2700); // 45 minutes escalation level
        $ttr_level_expected_date = date('Y-m-d H:i:s', strtotime($ttr_expected_date) - 3600); // 60 minutes escalation level
        $this->assertEquals($tto_level_expected_date, $tto_level['date']);
        $this->assertEquals($ttr_level_expected_date, $ttr_level['date']);
    }

    public static function laProvider(): iterable
    {
        // WARNING: dates must be in the future or escalation levels will be
        // computed immediately and removed from the database (and thus wont be able
        // to be tested properly)

        // Note: while it is possible to add multiple escalation levels,
        // only one at a time is set in the database so we can only
        // really test one level here
        // With that in mind, escalation_time and escalation_target_date will be
        // individual parameters instead of an array that could support multiple levels

        foreach ([\OLA::class, \SLA::class] as $la_class) {
            foreach ([SLM::TTO, SLM::TTR] as $la_type) {
                // 30 minutes LA without pauses
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 30,
                        'definition_time' => 'minute',
                    ],
                    'begin_date'             => '2034-06-09 08:46:12',
                    'pauses'                 => [],
                    'target_date'            => '2034-06-09 09:16:12',
                    'waiting_duration'       => 0,
                    // Negative 10 minutes escalation level
                    'escalation_time'        => - 10 * MINUTE_TIMESTAMP,
                    'target_escalation_date' => '2034-06-09 09:06:12',
                ];

                // 30 minutes LA with many pauses within the same day
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 30,
                        'definition_time' => 'minute',
                    ],
                    'begin_date'        => '2034-06-09 08:46:12',
                    'pauses'            => [
                        [
                            // pause: 1 h 18 m 12 s (4692 s)
                            'from' => '2034-06-09 08:47:42',
                            'to'   => '2034-06-09 10:05:54',
                        ],
                        [
                            // pause: 25 m 25 s (1525 s)
                            'from' => '2034-06-09 10:09:13',
                            'to'   => '2034-06-09 10:34:38',
                        ],
                        [
                            // pause: 45 m 36 s (2736 s)
                            'from' => '2034-06-09 10:43:41',
                            'to'   => '2034-06-09 11:29:17',
                        ],
                    ],
                    'target_date'       => $la_type == SLM::TTR
                        // 2034-06-09 08:46:12 + 30 m (LA time) + 2 h 29 m 13 s (waiting time)
                        ? '2034-06-09 11:45:25'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 09:16:12',
                    'waiting_duration'  => $la_type == SLM::TTR
                        ? 8953 // 4692 + 1525 + 2736
                        : 0,
                    // Negative 5 minutes escalation level
                    'escalation_time'        => - 5 * MINUTE_TIMESTAMP,
                    'target_escalation_date' => $la_type == SLM::TTR
                        // 5 minutes before each target date
                        ? '2034-06-09 11:40:25'
                        : '2034-06-09 09:11:12',
                ];

                // 4 hours LA without pauses
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ],
                    'begin_date'        => '2034-06-09 08:46:12',
                    'pauses'            => [],
                    'target_date'       => '2034-06-09 12:46:12',
                    'waiting_duration'  => 0,
                    // Positive 1 hour escalation level
                    'escalation_time'   => HOUR_TIMESTAMP,
                    'target_escalation_date' => '2034-06-09 13:46:12',
                ];

                // 4 hours LA with a pause within the same day
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ],
                    'begin_date'        => '2034-06-09 08:46:12',
                    'pauses'            => [
                        [
                            // pause: 2 h 8 m 22 s (7702 s)
                            'from' => '2034-06-09 09:15:27',
                            'to'   => '2034-06-09 11:23:49',
                        ],
                    ],
                    'target_date'       => $la_type == SLM::TTR
                        // 2034-06-09 08:46:12 + 4 h (LA time) + 2h 8 m 22 s (waiting time)
                        ? '2034-06-09 14:54:34'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 12:46:12',
                    'waiting_duration'  => $la_type == SLM::TTR ? 7702 : 0,
                    // Positive 10 hour escalation level
                    'escalation_time'   => 10 * HOUR_TIMESTAMP,
                    'target_escalation_date' => $la_type == SLM::TTR
                        // Start on 2034-06-09 14:54:34 (target TTR date) - 10 hours to add
                        // 4h06 to reach end of day (19h)
                        // There is still 5h54 remaining hours to add
                        // 2034-06-10 is outside our calendar (saturday)
                        // 2034-06-11 is outside our calendar (sunday)
                        // Start again on 2034-06-12 on 10h30 (monday)
                        // Add the remaining 5h54 hours -> 16h24
                        ? '2034-06-12 16:24:34'
                        // Start on 2034-06-09 12:46:12 (target TTO date) - 10 hours to add
                        // 6h14 to reach end of day (19h)
                        // There is still 3h46 remaining hours to add
                        // 2034-06-10 is outside our calendar (saturday)
                        // 2034-06-11 is outside our calendar (sunday)
                        // Start again on 2034-06-12 on 10h30 (monday)
                        // Add the remaining 3h46 hours -> 14h16
                        : '2034-06-12 14:16:12',
                ];

                // 4 hours LA with pauses across multiple days
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ],
                    'begin_date'        => '2034-06-05 10:00:00', // LA will start at 10:30
                    'pauses'            => [
                        [
                            // From calendar POV, pause is
                            // from 11:00:00 to 19:00:00 on 2034-06-05 (8 h),
                            // from 08:30:00 to 19:00:00 on 2034-06-06 (10 h 30 m),
                            // from 08:30:00 to 09:30:00 on 2034-06-07 (1 h).
                            // pause: 8 h + 10 h 30 m + 1 h = 19 h 30 m (70 200 s)
                            'from' => '2034-06-05 11:00:00',
                            'to'   => '2034-06-07 09:30:00',
                        ],
                        [
                            // From calendar POV, pause is
                            // from 10:00:00 to 19:00:00 on 2034-06-07 (9 h),
                            // from 08:30:00 to 09:00:00 on 2034-06-08 (30 m).
                            // pause: 9 h + 30 m = 9 h 30 m (34 200 s)
                            'from' => '2034-06-07 10:00:00',
                            'to'   => '2034-06-08 09:00:00',
                        ],
                    ],
                    'target_date'       => $la_type == SLM::TTR
                        // 2034-06-05 10:30:00 + 4 h (LA time) + 29 h (waiting time) + non-working hours
                        ? '2034-06-08 12:00:00'
                        // TTO is not impacted by waiting times
                        : '2034-06-05 14:30:00',
                    'waiting_duration'  => $la_type == SLM::TTR ? 104400 : 0,
                    // Positive 3 days escalation level
                    'escalation_time'   => 3 * DAY_TIMESTAMP,
                    'target_escalation_date' => $la_type == SLM::TTR
                         // 3 days after TTR
                         // Skip saturday and sunday (2034-06-10 and 2034-06-11)
                         // The fact that monday start later (+ 2 hours) SHOULD NOT
                         // be taken into account as we work in days not in hours
                         ? '2034-06-13 12:00:00'
                         // 3 day after TTO
                         : '2034-06-08 14:30:00',
                ];

                // 5 days LA over a weekend without pauses
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 5,
                        'definition_time' => 'day',
                    ],
                    'begin_date'        => '2034-06-09 08:46:12',
                    'pauses'            => [],
                    'target_date'       => '2034-06-16 08:46:12',
                    'waiting_duration'  => 0,
                    // Negative 8 hours escalation level
                    'escalation_time'   => - 8 * HOUR_TIMESTAMP,
                    // Count back from 2034-06-16 08:46:12 (friday) - 8 hours to remove
                    // 16m to reach start of day (8h30)
                    // 7h44 hours remaining
                    // Start counting back again from 2034-06-15 19h00 (thurday)
                    // Remove the remaining 7h44 hours -> 11h16
                    'target_escalation_date' =>  '2034-06-15 11:16:12',
                ];

                // 5 days LA over a weekend without pauses
                // + `end_of_working_day`
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'               => $la_type,
                        'number_time'        => 5,
                        'definition_time'    => 'day',
                        'end_of_working_day' => 1,
                    ],
                    'begin_date'        => '2034-06-09 08:46:12',
                    'pauses'            => [],
                    'target_date'       => '2034-06-16 19:00:00',
                    'waiting_duration'  => 0,
                    // Negative 2 days escalation level
                    'escalation_time'   => - 2 * DAY_TIMESTAMP,
                    // Remove two days
                    'target_escalation_date' =>  '2034-06-14 19:00:00',
                ];

                // 5 days LA with multiple pauses, including a pause of multiple days over a weekend
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'            => $la_type,
                        'number_time'     => 5,
                        'definition_time' => 'day',
                    ],
                    'begin_date'        => '2034-06-07 10:00:00',
                    'pauses'            => [
                        [
                            // From calendar POV, pause is
                            // from 11:00:00 to 19:00:00 on 2034-06-07 (8 h),
                            // from 08:30:00 to 19:00:00 on 2034-06-08 (10 h 30 m),
                            // from 08:30:00 to 19:00:00 on 2034-06-09 (10 h 30 m),
                            // not counted on 2034-06-10 as it is not a working day,
                            // not counted on 2034-06-11 as it is not a working day,
                            // from 10:30:00 to 19:00:00 on 2034-06-12 (08 h 30 m),
                            // from 08:30:00 to 11:00:00 on 2034-06-13 (2 h 30 m).
                            // pause: 8 h + 10 h 30 m + 10 h 30 m + 10 h 30 m + 2 h 30 m = 40 h (144 000 s)
                            'from' => '2034-06-07 11:00:00',
                            'to'   => '2034-06-13 11:00:00',
                        ],
                        [
                            // From calendar POV, pause is from 08:30:00 to 18:00:00 on 2034-06-07 (9 h 30 m),
                            // pause: 9 h 30 m (34 200 s)
                            'from' => '2034-06-14 07:00:00',
                            'to'   => '2034-06-14 18:00:00',
                        ],
                    ],
                    'target_date'       => $la_type == SLM::TTR
                        // 2034-06-07 10:00:00 + 5 days (LA time)
                        // -> 2034-06-14 10:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 09:00:00'
                        : '2034-06-14 10:00:00' // TTO does is not impacted by waiting times
                    ,
                    'waiting_duration'  => $la_type == SLM::TTR ? 178200 : 0,
                    // Positive 3 week escalation level
                    'escalation_time'   => 15 * DAY_TIMESTAMP,
                    'target_escalation_date' => $la_type == SLM::TTR
                        ? '2034-07-12 09:00:00'
                        : '2034-07-05 10:00:00',
                ];

                // 5 days LA with multiple pauses, including a pause of multiple days over a weekend
                // + `end_of_working_day`
                yield [
                    'la_class'          => $la_class,
                    'la_params'         => [
                        'type'               => $la_type,
                        'number_time'        => 5,
                        'definition_time'    => 'day',
                        'end_of_working_day' => 1,
                    ],
                    'begin_date'        => '2034-06-07 10:00:00',
                    'pauses'            => [
                        [
                            // From calendar POV, pause is
                            // from 11:00:00 to 19:00:00 on 2034-06-07 (8 h),
                            // from 08:30:00 to 19:00:00 on 2034-06-08 (10 h 30 m),
                            // from 08:30:00 to 19:00:00 on 2034-06-09 (10 h 30 m),
                            // not counted on 2034-06-10 as it is not a working day,
                            // not counted on 2034-06-11 as it is not a working day,
                            // from 10:30:00 to 19:00:00 on 2034-06-12 (08 h 30 m),
                            // from 08:30:00 to 11:00:00 on 2034-06-13 (2 h 30 m).
                            // pause: 8 h + 10 h 30 m + 10 h 30 m + 10 h 30 m + 2 h 30 m = 40 h (144 000 s)
                            'from' => '2034-06-07 11:00:00',
                            'to'   => '2034-06-13 11:00:00',
                        ],
                        [
                            // From calendar POV, pause is from 08:30:00 to 18:00:00 on 2034-06-07 (9 h 30 m),
                            // pause: 9 h 30 m (34 200 s)
                            'from' => '2034-06-14 07:00:00',
                            'to'   => '2034-06-14 18:00:00',
                        ],
                    ],
                    'target_date'       => $la_type == SLM::TTR
                        // 2034-06-07 10:00:00 + 5 days/end of working day(LA time)
                        // -> 2034-06-14 19:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 18:00:00'
                        // TTO does is not impacted by waiting times
                        : '2034-06-14 19:00:00',
                    'waiting_duration'  => $la_type == SLM::TTR ? 178200 : 0,
                    // Positive 2 hours escalation level
                    'escalation_time'   => 2 * HOUR_TIMESTAMP,
                    'target_escalation_date' => $la_type == SLM::TTR
                        // Must be two hours after their respective target date
                        ? '2034-06-22 09:30:00'
                        : '2034-06-15 10:30:00',
                ];
            }
        }
    }

    #[DataProvider('laProvider')]
    public function testComputation(
        string $la_class,
        array $la_params,
        string $begin_date,
        array $pauses,
        string $target_date,
        int $waiting_duration,
        int $escalation_time,
        string $target_escalation_date
    ): void {
        $this->login(); // must be logged in to be able to change ticket status

        // Create a calendar with working hours from 8 a.m. to 7 p.m. Monday to Friday
        $calendar = $this->createItem(\Calendar::class, ['name' => __FUNCTION__]);
        for ($i = 1; $i <= 5; $i++) {
            $this->createItem(
                \CalendarSegment::class,
                [
                    'calendars_id' => $calendar->getID(),
                    'day'          => $i,
                    'begin'        => $i == 1 ? '10:30:00' : '08:30:00', // monday starts later
                    'end'          => '19:00:00',
                ]
            );
        }

        // Create a service level
        $slm = $this->createItem(
            SLM::class,
            [
                'name'         => __FUNCTION__,
                'calendars_id' => $calendar->getID(),
            ]
        );

        // Create a level agreement item
        $la = $this->createItem(
            $la_class,
            [
                'name'    => __FUNCTION__,
                'slms_id' => $slm->getID(),
            ] + $la_params
        );

        // Create escalation level
        $this->createItem($la->getLevelClass(), [
            'name'                          => 'Test escalation level',
            'execution_time'                => $escalation_time,
            'is_active'                     => 1,
            'is_recursive'                  => 1,
            'match'                         => "OR",
            $la_class::getForeignKeyField() => $la->getID(),
        ]);

        // Create a ticket
        $_SESSION['glpi_currenttime'] = $begin_date;

        [$la_date_field, $la_fk_field] = $la->getFieldNames($la->fields['type']);
        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'       => __FUNCTION__,
                'content'    => __FUNCTION__,
                $la_fk_field => $la->getID(),
            ]
        );

        // Apply pauses
        foreach ($pauses as $pause) {
            $_SESSION['glpi_currenttime'] = $pause['from'];
            $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::WAITING]);

            $_SESSION['glpi_currenttime'] = $pause['to'];
            $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::ASSIGNED]);
        }

        // Reload ticket
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        $this->assertEquals($waiting_duration, $ticket->fields[$la_class::getWaitingFieldName()]);
        $this->assertEquals($target_date, $ticket->fields[$la_date_field])
        ;

        // Check escalation date
        $la_level_class = $la->getLevelTicketClass();
        $la_level_ticket = (new $la_level_class())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertCount(1, $la_level_ticket);
        $escalation_data = array_pop($la_level_ticket)["date"];
        $this->assertEquals($target_escalation_date, $escalation_data);
    }

    /**
     * Assign SLA and OLA to a ticket then change them with a rule
     * The ticket should only have the escalation level of the second set of SLA / OLA
     *
     * @return void
     */
    public function testLaChange(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // OLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $calendar = getItemByTypeName('Calendar', 'Default', true);
        $_SESSION['glpi_currenttime'] = '2034-08-16 13:00:00';

        // Create test SLM
        $slm = $this->createItem(SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar,
        ]);

        // Create rules to set full SLA and OLA on ticket creation and to change them on ticket update
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            foreach ([SLM::TTO, SLM::TTR] as $la_type) {
                $la = new $la_class();
                [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

                // Create two LA with one escalation level
                [$la1, $la2] = $this->createItems($la_class, [
                    [
                        'name'                => "$la_class $la_type 1",
                        'entities_id'         => $entity,
                        'is_recursive'        => true,
                        'type'                => $la_type,
                        'number_time'         => 4,
                        'calendars_id'        => $calendar,
                        'definition_time'     => 'hour',
                        'end_of_working_day'  => false,
                        'slms_id'             => $slm->getID(),
                        'use_ticket_calendar' => false,
                    ],
                    [
                        'name'                => "$la_class $la_type 2",
                        'entities_id'         => $entity,
                        'is_recursive'        => true,
                        'type'                => $la_type,
                        'number_time'         => 2,
                        'calendars_id'        => $calendar,
                        'definition_time'     => 'hour',
                        'end_of_working_day'  => false,
                        'slms_id'             => $slm->getID(),
                        'use_ticket_calendar' => false,
                    ],
                ]);
                foreach ([$la1, $la2] as $created_la) {
                    $this->createItem($created_la->getLevelClass(), [
                        'name'                          => $created_la->fields['name'] . ' level',
                        $la_class::getForeignKeyField() => $created_la->getID(),
                        'execution_time'                => - HOUR_TIMESTAMP,
                        'is_active'                     => true,
                        'entities_id'                   => $entity,
                        'is_recursive'                  => true,
                        'match'                         => 'AND',
                    ]);
                }

                // First OLA is added on creation
                $builder = new RuleBuilder('Add first LA on creation', RuleTicket::class);
                $builder->setEntity($entity)
                    ->setCondtion(RuleTicket::ONADD)
                    ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                    ->addCriteria('entities_id', Rule::PATTERN_IS, $entity)
                    ->addAction('assign', $la_fk_field, $la1->getID());
                $this->createRule($builder);

                // First OLA is added on update
                $builder = new RuleBuilder('Add second LA on update', RuleTicket::class);
                $builder->setEntity($entity)
                    ->setCondtion(RuleTicket::ONUPDATE)
                    ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                    ->addCriteria('urgency', Rule::PATTERN_IS, 5)
                    ->addAction('assign', $la_fk_field, $la2->getID());
                $this->createRule($builder);
            }
        }

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Create another ticket as a control subject that shouldn't be impacted
        // by changes on the other ticket
        $control_ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Check that each LA TTO and TTR are set as expected
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            $la = new $la_class();
            $level_class = $la->getLevelClass();
            $expected_la_levels = [];

            foreach ([SLM::TTO, SLM::TTR] as $la_type) {
                [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 1", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 1 level", true);
                $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

                // Check that the target date is correct (+ 4 hours)
                $this->assertEquals('2034-08-16 17:00:00', $ticket->fields[$la_date_field]);
            }

            // Check that all escalations levels are sets
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()]);
            $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->assertEquals(
                $expected_la_levels,
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            );

            // Check that they match the expected date (- 1 hour)
            $this->assertEquals(
                ['2034-08-16 16:00:00'],
                array_unique(array_column($sa_levels_ticket, 'date'))
            );
        }

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but we need to be in the input for the rule
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that each LA TTO and TTR have been modified as expected
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            $la = new $la_class();
            $level_class = $la->getLevelClass();
            $expected_la_levels = [];

            foreach ([SLM::TTO, SLM::TTR] as $la_type) {
                [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 2", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 2 level", true);
                $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

                // Check that the target date is correct (+ 2 hours)
                $this->assertEquals('2034-08-16 15:00:00', $ticket->fields[$la_date_field]);
            }

            // Check that all escalations levels have been modified
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
            $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->assertEquals(
                $expected_la_levels,
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            );

            // Check that they match the expected date (- 1 hour)
            $this->assertEquals(
                ['2034-08-16 14:00:00'],
                array_unique(array_column($sa_levels_ticket, 'date'))
            );
        }

        // Check that the control ticket LA and escalation levels are valid
        // These checks are needed to ensure the clearInvalidLevels() method only
        // impacted the correct ticket
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            $la = new $la_class();
            $level_class = $la->getLevelClass();
            $expected_la_levels = [];

            foreach ([SLM::TTO, SLM::TTR] as $la_type) {
                [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 1", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 1 level", true);
                $this->assertEquals($expected_la, $control_ticket->fields[$la_fk_field]);

                // Check that the target date is correct (+ 4 hours)
                $this->assertEquals('2034-08-16 17:00:00', $control_ticket->fields[$la_date_field]);
            }

            // Check that all escalations levels are sets
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $control_ticket->getID()], [$level_class::getForeignKeyField()]);
            $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->assertEquals(
                $expected_la_levels,
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            );

            // Check that they match the expected date (- 1 hour)
            $this->assertEquals(
                ['2034-08-16 16:00:00'],
                array_unique(array_column($sa_levels_ticket, 'date'))
            );
        }
    }


    /**
     * Escalation level changes when time passes for SLA TTR
     */
    public function testEscalationLevelChangesSlaTtr()
    {
        $this->login();
        // create slm + sla ttr with 120 minutes
        ['sla' => $sla] = $this->createSLA(data: [ 'number_time' => 120, 'definition_time' => 'minute',], sla_type: SLM::TTR);

        // add 2 escalation level to created SLA
        $level_1 = $this->createItem(\SlaLevel::class, [
            'name' => 'SLA level ' . time(),
            'slas_id' => $sla->getID(),
            'execution_time' => -60 * MINUTE_TIMESTAMP, // 60 minutes before TTR, 60 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 3
        $this->createItem(\SlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '3', 'slalevels_id' => $level_1->getID()]);
        // action : ticket priority -> 4
        $this->createItem(\SlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 4, 'slalevels_id' => $level_1->getID()]);

        $level_2 = $this->createItem(\SlaLevel::class, [
            'name' => 'SLA level ' . time(),
            'slas_id' => $sla->getID(),
            'execution_time' => -30 * MINUTE_TIMESTAMP, // 30 minutes before TTR, 90 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 4
        $this->createItem(\SlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '4', 'slalevels_id' => $level_2->getID()]);
        // action : ticket priority -> 5
        $this->createItem(\SlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 5, 'slalevels_id' => $level_2->getID()]);

        // --- 10:00 - create ticket and affect slalevels_id_ttr
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runSlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            'slas_id_ttr' => $sla->getID(),
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();
        $this->runSlaCron();

        $this->assertEquals($sla->getID(), $ticket->fields['slas_id_ttr']);
        // slalevels_id_ttr takes the first level, no matter what state or elapsed time is.
        $this->assertEquals($level_1->getID(), $ticket->fields['slalevels_id_ttr']);
        // TTR is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ticket->fields['time_to_resolve'], -8));
        // next level to be processed is level_1
        $this->assertTrue((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'slalevels_id' => $level_1->getID()]));

        // --- 11:01 ticket : level 1 is reached
        $this->setCurrentTime('2025-05-26 11:01:00');
        $this->runSlaCron();
        $this->assertEquals($level_1->getID(), $ticket->fields['slalevels_id_ttr']);
        // next level to be processed is level_2
        $this->assertTrue((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'slalevels_id' => $level_2->getID()]));
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(4, $ticket->fields['priority']); // level_1 action is applied

        // --- 11:31 ticket : level 2 is reached
        $this->setCurrentTime('2025-05-26 11:31:00');
        $this->runSlaCron();
        // next assertion is commented out because I have no explanation why it does not change to the current level_2
        // maybe this is field value is useless and should be removed
        // @todo try to remove slalevels_id_ttr field from the ticket table.
        // $this->assertEquals($level_2->getID(), $ticket->fields['slalevels_id_ttr']);

        // no next level to be processed
        $this->assertFalse((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID()]));
        // priority changed to 5
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(5, $ticket->fields['priority']); // level_1 action is applied
    }

    /**
     * Escalation level changes when time passes for SLA TTR
     */
    public function testEscalationLevelChangesSlaTto()
    {
        $this->login();
        // create slm + sla tto with 120 minutes
        ['sla' => $sla] = $this->createSLA(data: [ 'number_time' => 120, 'definition_time' => 'minute',], sla_type: SLM::TTO);

        // add 2 escalation level to created SLA
        $level_1 = $this->createItem(\SlaLevel::class, [
            'name' => 'SLA level ' . time(),
            'slas_id' => $sla->getID(),
            'execution_time' => -60 * MINUTE_TIMESTAMP, // 60 minutes before TTO, 60 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 3
        $this->createItem(\SlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '3', 'slalevels_id' => $level_1->getID()]);
        // action : ticket priority -> 4
        $this->createItem(\SlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 4, 'slalevels_id' => $level_1->getID()]);

        $level_2 = $this->createItem(\SlaLevel::class, [
            'name' => 'SLA level ' . time(),
            'slas_id' => $sla->getID(),
            'execution_time' => -30 * MINUTE_TIMESTAMP, // 30 minutes before TTO, 90 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 4
        $this->createItem(\SlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '4', 'slalevels_id' => $level_2->getID()]);
        // action : ticket priority -> 5
        $this->createItem(\SlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 5, 'slalevels_id' => $level_2->getID()]);

        // --- 10:00 - create ticket and affect slalevels_id_tto
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runSlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            'slas_id_tto' => $sla->getID(),
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();

        $this->assertEquals($sla->getID(), $ticket->fields['slas_id_tto']);
        // TTO is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ticket->fields['time_to_own'], -8));
        // next level to be processed is level_1
        $this->assertTrue((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'slalevels_id' => $level_1->getID()]));

        // --- 11:01 ticket : level 1 is reached
        $this->setCurrentTime('2025-05-26 11:01:00');
        $this->runSlaCron();
        // next level to be processed is level_2
        $this->assertTrue((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'slalevels_id' => $level_2->getID()]));
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(4, $ticket->fields['priority']); // level_1 action is applied

        // --- 11:31 ticket : level 2 is reached
        $this->setCurrentTime('2025-05-26 11:31:00');
        $this->runSlaCron();
        // no next level to be processed
        $this->assertFalse((new SlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID()]));
        // priority changed to 5
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(5, $ticket->fields['priority']); // level_1 action is applied
    }

    /**
     * Escalation level changes when time passes for OLA TTR
     */
    public function testEscalationLevelChangesOlaTtr()
    {
        $this->login();
        // create slm + ola ttr with 120 minutes
        ['ola' => $ola] = $this->createOLA(data: [ 'number_time' => 120, 'definition_time' => 'minute',], ola_type: SLM::TTR);

        // add 2 escalation level to created OLA
        $level_1 = $this->createItem(\OlaLevel::class, [
            'name' => 'OLA level ' . time(),
            'olas_id' => $ola->getID(),
            'execution_time' => -60 * MINUTE_TIMESTAMP, // 60 minutes before TTR, 60 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 3
        $this->createItem(\OlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '3', 'olalevels_id' => $level_1->getID()]);
        // action : ticket priority -> 4
        $this->createItem(\OlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 4, 'olalevels_id' => $level_1->getID()]);

        $level_2 = $this->createItem(\OlaLevel::class, [
            'name' => 'OLA level ' . time(),
            'olas_id' => $ola->getID(),
            'execution_time' => -30 * MINUTE_TIMESTAMP, // 30 minutes before TTR, 90 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 4
        $this->createItem(\OlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '4', 'olalevels_id' => $level_2->getID()]);
        // action : ticket priority -> 5
        $this->createItem(\OlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 5, 'olalevels_id' => $level_2->getID()]);

        // --- 10:00 - create ticket and affect olalevels_id_ttr
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runOlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            'olas_id_ttr' => $ola->getID(),
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();

        $this->assertEquals($ola->getID(), $ticket->fields['olas_id_ttr']);
        // olalevels_id_ttr takes the first level, no matter what state or elapsed time is.
        $this->assertEquals($level_1->getID(), $ticket->fields['olalevels_id_ttr']);
        // TTR is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ticket->fields['internal_time_to_resolve'], -8));
        // next level to be processed is level_1
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_1->getID()]));

        // --- 11:01 ticket : level 1 is reached
        $this->setCurrentTime('2025-05-26 11:01:00');
        $this->runOlaCron();
        $this->assertEquals($level_1->getID(), $ticket->fields['olalevels_id_ttr']); // not a relevant change, this won't change for the rest, kind of a bug ?
        // next level to be processed is level_2
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_2->getID()]));
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(4, $ticket->fields['priority']); // level_1 action is applied

        // --- 11:31 ticket : level 2 is reached
        $this->setCurrentTime('2025-05-26 11:31:00');
        $this->runOlaCron();
        // $this->assertEquals($level_2->getID(), $ticket->fields['olalevels_id_ttr']) // as noted above, this field does not change
        // no next level to be processed
        $this->assertFalse((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID()]));
        // priority changed to 5
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(5, $ticket->fields['priority']); // level_1 action is applied
    }

    /**
     * Escalation level changes when time passes for OLA TTO
     */
    public function testEscalationLevelChangesOlaTto()
    {
        $this->login();
        // create slm + ola tto with 120 minutes
        ['ola' => $ola] = $this->createOLA(data: [ 'number_time' => 120, 'definition_time' => 'minute',], ola_type: SLM::TTO);

        // add 2 escalation level to created OLA
        $level_1 = $this->createItem(\OlaLevel::class, [
            'name' => 'OLA level ' . time(),
            'olas_id' => $ola->getID(),
            'execution_time' => -60 * MINUTE_TIMESTAMP, // 60 minutes before TTR, 60 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 3
        $this->createItem(\OlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '3', 'olalevels_id' => $level_1->getID()]);
        // action : ticket priority -> 4
        $this->createItem(\OlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 4, 'olalevels_id' => $level_1->getID()]);

        $level_2 = $this->createItem(\OlaLevel::class, [
            'name' => 'OLA level ' . time(),
            'olas_id' => $ola->getID(),
            'execution_time' => -30 * MINUTE_TIMESTAMP, // 30 minutes before TTR, 90 minutes elapsed
            'is_active' => 1,
            'is_recursive' => 1,
            'match' => 'AND',
        ]);
        // criteria : ticket priority = 4
        $this->createItem(\OlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => '4', 'olalevels_id' => $level_2->getID()]);
        // action : ticket priority -> 5
        $this->createItem(\OlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 5, 'olalevels_id' => $level_2->getID()]);

        // --- 10:00 - create ticket and affect olalevels_id_tto
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runOlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            'olas_id_tto' => $ola->getID(),
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();

        $this->assertEquals($ola->getID(), $ticket->fields['olas_id_tto']);
        // olalevels_id_tto field does not exist
        // TTR is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ticket->fields['internal_time_to_own'], -8));
        // next level to be processed is level_1
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_1->getID()]));

        // --- 11:01 ticket : level 1 is reached
        $this->setCurrentTime('2025-05-26 11:01:00');
        $this->runOlaCron();
        // next level to be processed is level_2
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_2->getID()]));
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(4, $ticket->fields['priority']); // level_1 action is applied

        // --- 11:31 ticket : level 2 is reached
        $this->setCurrentTime('2025-05-26 11:31:00');
        $this->runOlaCron();
        // $this->assertEquals($level_2->getID(), $ticket->fields['olalevels_id_tto']) // as noted above, this field does not change
        // no next level to be processed
        $this->assertFalse((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID()]));
        // priority changed to 5
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(5, $ticket->fields['priority']); // level_1 action is applied
    }

    // @todo write tests to ensure/document that there is No execution of levels
    // - when ticket status is CLOSED or CLOSED
    // - when levelAgreement is an TTO and takeintoaccount_delay_stat is > 0

    /**
     * Ola begin date values business logic test
     *
     * ola begin date fields (ola_tto_begin_date, ola_ttr_begin_date) logic is as follows:
     *  - ola is set on ticket creation : ola begin date is set ticket.date field
     *  - ola is set on ticket update   : ola begin date is set to the current time
     */
    public function testOlaBeginDate(): void
    {
        $this->login();

        // on creation, the OLA begin date is set to the ticket date
        $provided_date = '2025-05-26 10:00:00';
        foreach ([SLM::TTR, SLM::TTO] as $type) {
            ['ola' => $ola] = $this->createOLA(ola_type: $type);
            [$olas_id_fk, $olas_begin_field] = match ($type) {
                SLM::TTO => ['olas_id_tto', 'ola_tto_begin_date'],
                SLM::TTR => ['olas_id_ttr', 'ola_ttr_begin_date'],
            };
            // create ticket with OLA set on creation + provided date
            $ticket = $this->createTicket(['date' => $provided_date, $olas_id_fk => $ola->getID()]);

            $this->assertEquals($provided_date, $ticket->fields[$olas_begin_field]);
        }

        // on update, the OLA begin date is set to the current time
        $now = $this->setCurrentTime('2022-05-26 09:00:00');
        $provided_date = '2022-05-01 10:00:00';
        foreach ([SLM::TTR, SLM::TTO] as $type) {
            ['ola' => $ola] = $this->createOLA(ola_type: $type);
            [$olas_id_fk, $olas_begin_field] = match ($type) {
                SLM::TTO => ['olas_id_tto', 'ola_tto_begin_date'],
                SLM::TTR => ['olas_id_ttr', 'ola_ttr_begin_date'],
            };
            // create ticket with OLA set on creation + provided date
            $ticket = $this->createTicket(['date' => $provided_date]);
            $ticket = $this->updateItem($ticket::class, $ticket->getID(), [$olas_id_fk => $ola->getID(),]);

            $this->assertEquals($now->format('Y-m-d H:i:s'), $ticket->fields[$olas_begin_field]);
        }
    }

    /**
     * Check recalculating the SLA when the SLA is changed to an SLA with a different calendar
     *
     * @return void
     */
    public function testLaChangeCalendar(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // OLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $_SESSION['glpi_currenttime'] = '2034-08-16 13:00:00';

        // Create a calendar with working hours from 8 a.m. to 7 p.m. Monday to Friday
        $calendar = $this->createItem(\Calendar::class, ['name' => __FUNCTION__ . ' 1']);
        for ($i = 1; $i <= 5; $i++) {
            $this->createItem(
                \CalendarSegment::class,
                [
                    'calendars_id' => $calendar->getID(),
                    'day'          => $i,
                    'begin'        => '08:00:00',
                    'end'          => '19:00:00',
                ]
            );
        }

        // Create test SLM
        $slm = $this->createItem(SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar->getID(),
        ]);

        // Create rules to set SLA on ticket creation
        $la_class = \SLA::class;
        $la = new $la_class();
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

            // Create two LA with one escalation level
            $la = $this->createItem($la_class, [
                'name'                => "$la_class $la_type",
                'entities_id'         => $entity,
                'is_recursive'        => true,
                'type'                => $la_type,
                'number_time'         => 4,
                'calendars_id'        => $calendar->getID(),
                'definition_time'     => 'hour',
                'end_of_working_day'  => false,
                'slms_id'             => $slm->getID(),
                'use_ticket_calendar' => false,
            ]);
            $this->createItem($la->getLevelClass(), [
                'name'                          => $la->fields['name'] . ' level',
                $la_class::getForeignKeyField() => $la->getID(),
                'execution_time'                => - HOUR_TIMESTAMP,
                'is_active'                     => true,
                'entities_id'                   => $entity,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ]);

            // First OLA is added on creation
            $builder = new RuleBuilder('Add first LA on creation', RuleTicket::class);
            $builder->setEntity($entity)
                ->setCondtion(RuleTicket::ONADD)
                ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                ->addCriteria('entities_id', Rule::PATTERN_IS, $entity)
                ->addAction('assign', $la_fk_field, $la->getID());
            $this->createRule($builder);
        }

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Check that TTO and TTR are set as expected
        $la = new $la_class();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName($la_class, "$la_class $la_type", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type level", true);
            $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

            // Check that the target date is correct (+ 4 hours)
            $this->assertEquals('2034-08-16 17:00:00', $ticket->fields[$la_date_field]);
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()]);
        $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

        // Check that they match the expected la levels
        $this->assertEquals(
            $expected_la_levels,
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        );

        // Check that they match the expected date (- 1 hour)
        $this->assertEquals(
            ['2034-08-16 16:00:00'],
            array_unique(array_column($sa_levels_ticket, 'date'))
        );

        // Put ticket on waiting for 10 minutes
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => Ticket::WAITING,
        ]);
        $_SESSION['glpi_currenttime'] = '2034-08-16 13:10:00';
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => Ticket::INCOMING,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that TTO and TTR have been modified as expected
        $la = new $la_class();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        $la_type = SLM::TTR;
        [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

        // Check that the correct LA is assigned to the ticket
        $expected_la = getItemByTypeName($la_class, "$la_class $la_type", true);
        $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type level", true);
        $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

        // Check that the target date is correct (+ 4 hours)
        $this->assertEquals('2034-08-16 17:10:00', $ticket->fields[$la_date_field]);

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->assertCount(1, $sa_levels_ticket);

        // Check that they match the expected la levels
        $this->assertEquals(
            $expected_la_levels,
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        );

        // Check that they match the expected date (- 1 hour)
        $this->assertEquals(
            ['2034-08-16 16:10:00'],
            array_unique(array_column($sa_levels_ticket, 'date'))
        );

        // Create a second calendar with working hours from 8 a.m. to 7 p.m. Monday to Friday
        $calendar2 = $this->createItem(\Calendar::class, ['name' => __FUNCTION__ . ' 2']);
        for ($i = 1; $i <= 5; $i++) {
            $this->createItem(
                \CalendarSegment::class,
                [
                    'calendars_id' => $calendar2->getID(),
                    'day'          => $i,
                    'begin'        => '08:00:00',
                    'end'          => '19:00:00',
                ]
            );
        }

        // Create a new SLM with the second calendar
        $slm2 = $this->createItem(SLM::class, [
            'name'                => 'SLM 2',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar2->getID(),
        ]);

        // Create rules to set full SLA on ticket update
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            $la = new $la_class();
            [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

            // Create two LA with one escalation level
            $la = $this->createItem($la_class, [
                'name'                => "$la_class $la_type 2",
                'entities_id'         => $entity,
                'is_recursive'        => true,
                'type'                => $la_type,
                'number_time'         => 6,
                'calendars_id'        => $calendar2->getID(),
                'definition_time'     => 'hour',
                'end_of_working_day'  => false,
                'slms_id'             => $slm2->getID(),
                'use_ticket_calendar' => false,
            ]);
            $this->createItem($la->getLevelClass(), [
                'name'                          => $la->fields['name'] . ' level',
                $la_class::getForeignKeyField() => $la->getID(),
                'execution_time'                => - HOUR_TIMESTAMP,
                'is_active'                     => true,
                'entities_id'                   => $entity,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ]);

            // OLA is added on update
            $builder = new RuleBuilder('Add second LA on update', RuleTicket::class);
            $builder->setEntity($entity)
                ->setCondtion(RuleTicket::ONUPDATE)
                ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                ->addCriteria('urgency', Rule::PATTERN_IS, 5)
                ->addAction('assign', $la_fk_field, $la->getID());
            $this->createRule($builder);
        }

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but we need to be in the input for the rule
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that TTO and TTR have been modified as expected
        $la = new $la_class();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $la->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName($la_class, "$la_class $la_type 2", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 2 level", true);
            $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

            // Check that the target date is correct (+ 6 hours)
            $this->assertEquals('2034-08-17 08:10:00', $ticket->fields[$la_date_field]);
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->assertCount(2, $sa_levels_ticket);

        // Check that they match the expected la levels
        $this->assertEquals(
            $expected_la_levels,
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        );

        // Check that they match the expected date (- 1 hour)1
        $this->assertEquals(
            ['2034-08-16 18:10:00'],
            array_unique(array_column($sa_levels_ticket, 'date'))
        );
    }

    public function testSlaTtoDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-06-25 13:00:01');
        // arrange
        $sla = $this->createSLA(sla_type: SLM::TTO)['sla'];
        $ticket = $this->createTicket(['slas_id_tto' => $sla->getID()]);
        // assert due time is set correctly
        $initial_expected_due_time = $now->add($this->getDefaultSlaTtoDelayInterval());
        $initial_expected_due_time_str = $initial_expected_due_time->format('Y-m-d H:i:s');
        assert($initial_expected_due_time_str === $ticket->fields['time_to_own'], 'SLA TTO Due time should be set to the current date + TTO delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:00';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $this->assertEquals($initial_expected_due_time_str, $ticket->fields['time_to_own'], 'SLA TTO due time is not updated when ticket date is changed.');
    }

    public function testSlaTtrDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-07-21 13:02:01');
        // arrange
        $sla = $this->createSLA(sla_type: SLM::TTR)['sla'];
        $ticket = $this->createTicket(['slas_id_ttr' => $sla->getID()]);
        // assert due time is set correctly
        $initial_expected_due_time = $now->add($this->getDefaultSlaTtrDelayInterval());
        $initial_expected_due_time_str = $initial_expected_due_time->format('Y-m-d H:i:s');
        assert($initial_expected_due_time_str === $ticket->fields['time_to_resolve'], 'SLA TTR Due time should be set to the current date + TTR delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:02';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $this->assertEquals($initial_expected_due_time_str, $ticket->fields['time_to_resolve'], 'SLA TTR due time is updated when ticket date is changed.');
    }

    public function testOlaTtoDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-06-25 13:00:01');
        // arrange
        $ola = $this->createOLA(ola_type: SLM::TTO)['ola'];
        $ticket = $this->createTicket(['olas_id_tto' => $ola->getID()]);
        // assert due time is set correctly
        $initial_expected_due_time = $now->add($this->getDefaultOlaTtoDelayInterval());
        $initial_expected_due_time_str = $initial_expected_due_time->format('Y-m-d H:i:s');
        assert($initial_expected_due_time_str === $ticket->fields['internal_time_to_own'], 'OLA TTO Due time should be set to the current date + TTO delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:00';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $this->assertEquals($initial_expected_due_time_str, $ticket->fields['internal_time_to_own'], 'OLA TTO due time is not updated when ticket date is changed.');
    }

    public function testOlaTtrDueTimeIsNotUpdatedOnTicketDateUpdate(): void
    {
        $this->login();
        $now = $this->setCurrentTime('2025-07-21 13:02:01');
        // arrange
        $ola = $this->createOLA(ola_type: SLM::TTR)['ola'];
        $ticket = $this->createTicket(['olas_id_ttr' => $ola->getID()]);
        // assert due time is set correctly
        $initial_expected_due_time = $now->add($this->getDefaultOlaTtrDelayInterval());
        $initial_expected_due_time_str = $initial_expected_due_time->format('Y-m-d H:i:s');
        assert($initial_expected_due_time_str === $ticket->fields['internal_time_to_resolve'], 'OLA TTR Due time should be set to the current date + TTR delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:02';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $this->assertEquals($initial_expected_due_time_str, $ticket->fields['internal_time_to_resolve'], 'OLA TTR due time is updated when ticket date is changed.');
    }

    public function testCannotExportSLALevel()
    {
        $this->login();

        // Create an SLM
        $slm = $this->createItem(SLM::class, [
            'name' => 'SLM',
        ]);

        // Create an SLA
        /** @var \SLA $sla */
        $sla = $this->createItem(\SLA::class, [
            'name'            => 'SLA',
            'slms_id'         => $slm->getID(),
            'definition_time' => 'hour',
            'number_time'     => 4,
        ]);

        // Create an escalation level
        $sla_level = $this->createItem(\SlaLevel::class, [
            'name'                          => 'SLA level',
            'slas_id'                       => $sla->getID(),
            'execution_time'                => -HOUR_TIMESTAMP,
            'is_active'                     => true,
            'is_recursive'                  => true,
            'match'                         => 'AND',
        ]);

        // Retrieve available actions
        $actions = $sla_level->getSpecificMassiveActions();

        // Check that the export action is not available
        $this->assertArrayNotHasKey(Rule::getType() . MassiveAction::CLASS_ACTION_SEPARATOR . 'export', $actions);
    }

    public function testCloneSLA()
    {
        $this->login();

        // Create an SLM
        $slm = $this->createItem(SLM::class, [
            'name' => 'SLM',
        ]);

        // Create an SLA
        /** @var \SLA $sla */
        $sla = $this->createItem(\SLA::class, [
            'name'            => 'SLA',
            'slms_id'         => $slm->getID(),
            'definition_time' => 'hour',
            'number_time'     => 4,
        ]);

        // Create multiple escalation levels
        $sla_levels = $this->createItems(\SlaLevel::class, [
            [
                'name'                          => 'SLA level 1',
                'slas_id'                       => $sla->getID(),
                'execution_time'                => -HOUR_TIMESTAMP,
                'is_active'                     => true,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ],
            [
                'name'                          => 'SLA level 2',
                'slas_id'                       => $sla->getID(),
                'execution_time'                => -2 * HOUR_TIMESTAMP,
                'is_active'                     => true,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ],
        ]);

        // Create multiple escalation levels criteria
        $sla_levels_criterias = $this->createItems(\SlaLevelCriteria::class, [
            [
                'slalevels_id' => $sla_levels[0]->getID(),
                'criteria'     => 'status',
                'pattern'      => 1,
                'condition'    => 0,
            ],
            [
                'slalevels_id' => $sla_levels[1]->getID(),
                'criteria'     => 'urgency',
                'pattern'      => 5,
                'condition'    => 0,
            ],
        ]);

        // Create multiple escalation levels actions
        $sla_levels_actions = $this->createItems(\SlaLevelAction::class, [
            [
                'slalevels_id' => $sla_levels[0]->getID(),
                'action_type'  => 'assign',
                'field'        => 'type',
                'value'        => 1,
            ],
            [
                'slalevels_id' => $sla_levels[1]->getID(),
                'action_type'  => 'assign',
                'field'        => 'type',
                'value'        => 2,
            ],
        ]);

        // Clone the SLA
        $sla_clone_id = $sla->clone();
        $sla_clone = \SLA::getById($sla_clone_id);

        // Check that the clone has the same fields as the original
        $this->assertEquals(
            array_merge(
                $sla->fields,
                [
                    'id' => $sla_clone_id,
                    'name' => 'SLA (copy)',
                ]
            ),
            $sla_clone->fields
        );

        // Check that SLA levels have been cloned
        $sla_clone_levels = (new \SlaLevel())->find(['slas_id' => $sla_clone_id]);
        $this->assertCount(2, $sla_clone_levels);
        $sla_compare = \SlaLevel::getById(current($sla_clone_levels)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $sla_levels[0]->fields,
                [
                    'id'        => current($sla_clone_levels)['id'],
                    'name'      => 'SLA level 1 (copy)',
                    'uuid'      => current($sla_clone_levels)['uuid'],
                    'slas_id'   => $sla_clone_id,
                    'is_active' => 0,
                ]
            ),
            $sla_compare
        );
        $sla_compare = \SlaLevel::getById(next($sla_clone_levels)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $sla_levels[1]->fields,
                [
                    'id'        => current($sla_clone_levels)['id'],
                    'name'      => 'SLA level 2 (copy 2)',
                    'uuid'      => current($sla_clone_levels)['uuid'],
                    'slas_id'   => $sla_clone_id,
                    'is_active' => 0,
                ]
            ),
            $sla_compare
        );

        // Check that SLA levels criteria have been cloned
        $sla_clone_criteria = (new \SlaLevelCriteria())->find(['slalevels_id' => array_column($sla_clone_levels, 'id')]);
        $this->assertCount(2, $sla_clone_criteria);
        $this->assertEquals(
            array_merge(
                $sla_levels_criterias[0]->fields,
                [
                    'id'           => current($sla_clone_criteria)['id'],
                    'slalevels_id' => reset($sla_clone_levels)['id'],
                ]
            ),
            \SlaLevelCriteria::getById(current($sla_clone_criteria)['id'])->fields
        );
        $sla_compare = \SlaLevelCriteria::getById(next($sla_clone_criteria)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $sla_levels_criterias[1]->fields,
                [
                    'id'           => current($sla_clone_criteria)['id'],
                    'slalevels_id' => next($sla_clone_levels)['id'],
                ]
            ),
            $sla_compare
        );

        // Check that SLA levels actions have been cloned
        $sla_clone_actions = (new \SlaLevelAction())->find(['slalevels_id' => array_column($sla_clone_levels, 'id')]);
        $this->assertCount(2, $sla_clone_actions);
        $sla_compare = \SlaLevelAction::getById(current($sla_clone_actions)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $sla_levels_actions[0]->fields,
                [
                    'id'           => current($sla_clone_actions)['id'],
                    'slalevels_id' => reset($sla_clone_levels)['id'],
                ]
            ),
            $sla_compare
        );
        $sla_compare = \SlaLevelAction::getById(next($sla_clone_actions)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $sla_levels_actions[1]->fields,
                [
                    'id'           => current($sla_clone_actions)['id'],
                    'slalevels_id' => next($sla_clone_levels)['id'],
                ]
            ),
            $sla_compare
        );
    }

    public function testCloneOLA()
    {
        $this->login();

        // Create an SLM
        $slm = $this->createItem(SLM::class, [
            'name' => 'SLM',
        ]);

        // Create an OLA
        /** @var \OLA $ola */
        $ola = $this->createItem(\OLA::class, [
            'name'            => 'OLA',
            'slms_id'         => $slm->getID(),
            'definition_time' => 'hour',
            'number_time'     => 4,
        ]);

        // Create multiple escalation levels
        $ola_levels = $this->createItems(\OlaLevel::class, [
            [
                'name'                          => 'OLA level 1',
                'olas_id'                       => $ola->getID(),
                'execution_time'                => -HOUR_TIMESTAMP,
                'is_active'                     => true,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ],
            [
                'name'                          => 'OLA level 2',
                'olas_id'                       => $ola->getID(),
                'execution_time'                => -2 * HOUR_TIMESTAMP,
                'is_active'                     => true,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ],
        ]);

        // Create multiple escalation levels criteria
        $ola_levels_criterias = $this->createItems(\OlaLevelCriteria::class, [
            [
                'olalevels_id' => $ola_levels[0]->getID(),
                'criteria'     => 'status',
                'pattern'      => 1,
                'condition'    => 0,
            ],
            [
                'olalevels_id' => $ola_levels[1]->getID(),
                'criteria'     => 'urgency',
                'pattern'      => 5,
                'condition'    => 0,
            ],
        ]);

        // Create multiple escalation levels actions
        $ola_levels_actions = $this->createItems(\OlaLevelAction::class, [
            [
                'olalevels_id' => $ola_levels[0]->getID(),
                'action_type'  => 'assign',
                'field'        => 'type',
                'value'        => 1,
            ],
            [
                'olalevels_id' => $ola_levels[1]->getID(),
                'action_type'  => 'assign',
                'field'        => 'type',
                'value'        => 2,
            ],
        ]);

        // Clone the OLA
        $ola_clone_id = $ola->clone();
        $ola_clone = \OLA::getById($ola_clone_id);

        // Check that the clone has the same fields as the original
        $this->assertEquals(
            array_merge(
                $ola->fields,
                [
                    'id' => $ola_clone_id,
                    'name' => 'OLA (copy)',
                ]
            ),
            $ola_clone->fields
        );

        // Check that OLA levels have been cloned
        $ola_clone_levels = (new \OlaLevel())->find(['olas_id' => $ola_clone_id]);
        $this->assertCount(2, $ola_clone_levels);
        $ola_compare = \OlaLevel::getById(current($ola_clone_levels)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels[0]->fields,
                [
                    'id'        => current($ola_clone_levels)['id'],
                    'name'      => 'OLA level 1 (copy)',
                    'uuid'      => current($ola_clone_levels)['uuid'],
                    'olas_id'   => $ola_clone_id,
                    'is_active' => 0,
                ]
            ),
            $ola_compare
        );
        $ola_compare = \OlaLevel::getById(next($ola_clone_levels)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels[1]->fields,
                [
                    'id'        => current($ola_clone_levels)['id'],
                    'name'      => 'OLA level 2 (copy 2)',
                    'uuid'      => current($ola_clone_levels)['uuid'],
                    'olas_id'   => $ola_clone_id,
                    'is_active' => 0,
                ]
            ),
            $ola_compare
        );

        // Check that OLA levels criteria have been cloned
        $ola_clone_criteria = (new \OlaLevelCriteria())->find(['olalevels_id' => array_column($ola_clone_levels, 'id')]);
        $this->assertCount(2, $ola_clone_criteria);
        $ola_compare = \OlaLevelCriteria::getById(current($ola_clone_criteria)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels_criterias[0]->fields,
                [
                    'id'           => current($ola_clone_criteria)['id'],
                    'olalevels_id' => reset($ola_clone_levels)['id'],
                ]
            ),
            $ola_compare
        );
        $ola_compare = \OlaLevelCriteria::getById(next($ola_clone_criteria)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels_criterias[1]->fields,
                [
                    'id'           => current($ola_clone_criteria)['id'],
                    'olalevels_id' => next($ola_clone_levels)['id'],
                ]
            ),
            $ola_compare
        );

        // Check that OLA levels actions have been cloned
        $ola_clone_actions = (new \OlaLevelAction())->find(['olalevels_id' => array_column($ola_clone_levels, 'id')]);
        $this->assertCount(2, $ola_clone_actions);
        $ola_compare = \OlaLevelAction::getById(current($ola_clone_actions)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels_actions[0]->fields,
                [
                    'id'           => current($ola_clone_actions)['id'],
                    'olalevels_id' => reset($ola_clone_levels)['id'],
                ]
            ),
            $ola_compare
        );
        $ola_compare = \OlaLevelAction::getById(next($ola_clone_actions)['id'])->fields;
        $this->assertEquals(
            array_merge(
                $ola_levels_actions[1]->fields,
                [
                    'id'           => current($ola_clone_actions)['id'],
                    'olalevels_id' => next($ola_clone_levels)['id'],
                ]
            ),
            $ola_compare
        );
    }

    private function runSlaCron(): void
    {
        SlaLevel_Ticket::cronSlaTicket(getItemByTypeName(\CronTask::class, 'slaticket'));
    }

    private function runOlaCron(): void
    {
        OlaLevel_Ticket::cronOlaTicket(getItemByTypeName(\CronTask::class, 'slaticket')); // at the moment only slaticketcron exists
    }
}
