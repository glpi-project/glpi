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

use CommonITILObject;
use DbTestCase;
use OlaLevel;
use OlaLevel_Ticket;
use Rule;
use RuleBuilder;
use RuleTicket;
use SlaLevel_Ticket;
use Ticket;

class SLM extends DbTestCase
{
    private $method;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
       //to handle GLPI barbarian replacements.
        $this->method = str_replace(
            ['\\', 'beforeTestMethod'],
            ['', $method],
            __METHOD__
        );
    }


    /**
     * Create a full SLM with all level filled (slm/sla/ola/levels/action/criterias)
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
                'end'          => '20:00:00'
            ]);
            $this->checkInput($calseg, $calseg_id);
        }

        $slm    = new \SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => $this->method,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => $cal_id,
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

       // prepare sla/ola inputs
        $sla1_in = $sla2_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => \SLM::TTO,
            'number_time'     => 4,
            'definition_time' => 'day',
        ];
        $sla2_in['type'] = \SLM::TTR;
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
            'name'           => $this->method,
            'execution_time' => -DAY_TIMESTAMP,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id
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
            'pattern'      => 1
        ];
        unset($ocrit_in['slalevels_id']);
        $ocrit_in['olalevels_id'] = $olal1_id;
        $saction_in = $oaction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'status',
            'value'        => 4
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruleinput = [
            'name'         => $this->method,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => 2,
            'pattern'   => $this->method
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_tto',
            'value'       => $sla1_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_ttr',
            'value'       => $sla2_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_tto',
            'value'       => $ola1_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_ttr',
            'value'       => $ola2_id
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

       // test create ticket
        $ticket = new \Ticket();
        $start_date = date("Y-m-d H:i:s", time() - 4 * DAY_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            'date'    => $start_date,
            'name'    => $this->method,
            'content' => $this->method
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('slas_id_tto'))->isEqualTo($sla1_id);
        $this->integer((int)$ticket->getField('slas_id_ttr'))->isEqualTo($sla2_id);
        $this->integer((int)$ticket->getField('olas_id_tto'))->isEqualTo($ola1_id);
        $this->integer((int)$ticket->getField('olas_id_ttr'))->isEqualTo($ola2_id);
        $this->string($ticket->getField('time_to_resolve'))->length->isEqualTo(19);

       // test update ticket
        $ticket = new \Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'    => "to be updated",
            'content' => $this->method
        ]);
        $ticket->update([
            'id'   => $tickets_id_2,
            'name' => $this->method
        ]);
        $ticket_input_2['name'] = $this->method;
        $this->checkInput($ticket, $tickets_id_2, $ticket_input_2);
        $this->integer((int)$ticket->getField('slas_id_tto'))->isEqualTo($sla1_id);
        $this->integer((int)$ticket->getField('slas_id_ttr'))->isEqualTo($sla2_id);
        $this->integer((int)$ticket->getField('olas_id_tto'))->isEqualTo($ola1_id);
        $this->integer((int)$ticket->getField('olas_id_ttr'))->isEqualTo($ola2_id);
        $this->string($ticket->getField('time_to_resolve'))->length->isEqualTo(19);

       // ## 3 - test purge of slm and check if we don't find any sub objects
        $this->boolean($slm->delete(['id' => $slm_id], true))->isTrue();
       //sla
        $this->boolean($sla->getFromDB($sla1_id))->isFalse();
        $this->boolean($sla->getFromDB($sla2_id))->isFalse();
       //ola
        $this->boolean($ola->getFromDB($ola1_id))->isFalse();
        $this->boolean($ola->getFromDB($ola2_id))->isFalse();
       //slalevel
        $this->boolean($slal->getFromDB($slal1_id))->isFalse();
        $this->boolean($slal->getFromDB($slal2_id))->isFalse();
       //olalevel
        $this->boolean($olal->getFromDB($olal1_id))->isFalse();
        $this->boolean($olal->getFromDB($olal2_id))->isFalse();
       //crit
        $this->boolean($scrit->getFromDB($scrit_id))->isFalse();
        $this->boolean($ocrit->getFromDB($ocrit_id))->isFalse();
       //action
        $this->boolean($saction->getFromDB($saction_id))->isFalse();
        $this->boolean($oaction->getFromDB($oaction_id))->isFalse();
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
        $cal    = new \Calendar();
        $calseg = new \CalendarSegment();
        $cal_id = getItemByTypeName('Calendar', 'Default', true);

        $slm    = new \SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => $this->method,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => $cal_id,
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

       // prepare sla/ola inputs
        $sla1_in = $sla2_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => \SLM::TTO,
            'number_time'     => 4,
            'definition_time' => 'month',
        ];
        $sla2_in['type'] = \SLM::TTR;
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
            'name'           => $this->method,
            'execution_time' => -MONTH_TIMESTAMP,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id
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
            'pattern'      => 1
        ];
        unset($ocrit_in['slalevels_id']);
        $ocrit_in['olalevels_id'] = $olal1_id;
        $saction_in = $oaction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'status',
            'value'        => 4
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruleinput = [
            'name'         => $this->method,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruleinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => 2,
            'pattern'   => $this->method
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_tto',
            'value'       => $sla1_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'slas_id_ttr',
            'value'       => $sla2_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_tto',
            'value'       => $ola1_id
        ]);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'olas_id_ttr',
            'value'       => $ola2_id
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

       // test create ticket
        $ticket = new \Ticket();
        $start_date = date("Y-m-d H:i:s", time() - 4 * MONTH_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            'date'    => $start_date,
            'name'    => $this->method,
            'content' => $this->method
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('slas_id_tto'))->isEqualTo($sla1_id);
        $this->integer((int)$ticket->getField('slas_id_ttr'))->isEqualTo($sla2_id);
        $this->integer((int)$ticket->getField('olas_id_tto'))->isEqualTo($ola1_id);
        $this->integer((int)$ticket->getField('olas_id_ttr'))->isEqualTo($ola2_id);
        $this->string($ticket->getField('time_to_resolve'))->length->isEqualTo(19);

       // test update ticket
        $ticket = new \Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'    => "to be updated",
            'content' => $this->method
        ]);
       //SLA/OLA  TTR/TTO not already set
        $this->integer((int)$ticket->getField('slas_id_tto'))->isEqualTo(0);
        $this->integer((int)$ticket->getField('slas_id_ttr'))->isEqualTo(0);
        $this->integer((int)$ticket->getField('olas_id_tto'))->isEqualTo(0);
        $this->integer((int)$ticket->getField('olas_id_ttr'))->isEqualTo(0);

        $ticket->update([
            'id'   => $tickets_id_2,
            'name' => $this->method
        ]);
        $ticket_input_2['name'] = $this->method;
        $this->checkInput($ticket, $tickets_id_2, $ticket_input_2);
        $this->integer((int)$ticket->getField('slas_id_tto'))->isEqualTo($sla1_id);
        $this->integer((int)$ticket->getField('slas_id_ttr'))->isEqualTo($sla2_id);
        $this->integer((int)$ticket->getField('olas_id_tto'))->isEqualTo($ola1_id);
        $this->integer((int)$ticket->getField('olas_id_ttr'))->isEqualTo($ola2_id);
        $this->string($ticket->getField('time_to_resolve'))->length->isEqualTo(19);

       // ## 3 - test purge of slm and check if we don't find any sub objects
        $this->boolean($slm->delete(['id' => $slm_id], true))->isTrue();
       //sla
        $this->boolean($sla->getFromDB($sla1_id))->isFalse();
        $this->boolean($sla->getFromDB($sla2_id))->isFalse();
       //ola
        $this->boolean($ola->getFromDB($ola1_id))->isFalse();
        $this->boolean($ola->getFromDB($ola2_id))->isFalse();
       //slalevel
        $this->boolean($slal->getFromDB($slal1_id))->isFalse();
        $this->boolean($slal->getFromDB($slal2_id))->isFalse();
       //olalevel
        $this->boolean($olal->getFromDB($olal1_id))->isFalse();
        $this->boolean($olal->getFromDB($olal2_id))->isFalse();
       //crit
        $this->boolean($scrit->getFromDB($scrit_id))->isFalse();
        $this->boolean($ocrit->getFromDB($ocrit_id))->isFalse();
       //action
        $this->boolean($saction->getFromDB($saction_id))->isFalse();
        $this->boolean($oaction->getFromDB($oaction_id))->isFalse();
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

       // Create a calendar having tommorow as working day
        $calendar = new \Calendar();
        $segment  = new \CalendarSegment();
        $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar']);
        $this->integer($calendar_id)->isGreaterThan(0);

        $segment_id = $segment->add(
            [
                'calendars_id' => $calendar_id,
                'day'          => (int)date('w') === 6 ? 0 : (int)date('w') + 1,
                'begin'        => '09:00:00',
                'end'          => '19:00:00'
            ]
        );
        $this->integer($segment_id)->isGreaterThan(0);

       // Create SLM with TTR OLA
        $slm = new \SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => $calendar_id,
            ]
        );
        $this->integer($slm_id)->isGreaterThan(0);

        $ola = new \OLA();
        $ola_id = $ola->add(
            [
                'slms_id'         => $slm_id,
                'name'            => 'Test TTR OLA',
                'type'            => \SLM::TTR,
                'number_time'     => 4,
                'definition_time' => 'hour',
            ]
        );
        $this->integer($ola_id)->isGreaterThan(0);

       // Create ticket to test computation based on OLA
        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test',
            ]
        );
        $this->integer($ticket_id)->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo(0);
        $this->variable($ticket->fields['ola_ttr_begin_date'])->isEqualTo(null);
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo(null);

       // Assign TTR OLA
        $update_time = strtotime('+10s');
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', $update_time);
        $updated = $ticket->update(['id' => $ticket_id, 'olas_id_ttr' => $ola_id]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->boolean($updated)->isTrue();
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo($ola_id);
        $this->integer(strtotime($ticket->fields['ola_ttr_begin_date']))->isEqualTo($update_time);
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo($tomorrow_1pm);

       // Simulate waiting to first working hour +1
        $this->boolean(
            $ticket->update(
                [
                    'id' => $ticket_id,
                    'status' => \CommonITILObject::WAITING,
                ]
            )
        )->isTrue();
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('tomorrow 10am'));
        $updated = $ticket->update(['id' => $ticket_id, 'status' => \CommonITILObject::ASSIGNED]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->boolean($updated)->isTrue();
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo($tomorrow_2pm);

       // Create ticket to test computation based on manual date
        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR manual test',
            ]
        );
        $this->integer($ticket_id)->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo(0);
        $this->variable($ticket->fields['ola_ttr_begin_date'])->isEqualTo(null);
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo(null);

       // Assign manual TTR
        $this->boolean($ticket->update(['id' => $ticket_id, 'internal_time_to_resolve' => $tomorrow_1pm]))->isTrue();
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo(0);
        $this->variable($ticket->fields['ola_ttr_begin_date'])->isEqualTo(null);
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo($tomorrow_1pm);

       // Simulate 1 hour of waiting time
        $this->boolean(
            $ticket->update(
                [
                    'id' => $ticket_id,
                    'status' => \CommonITILObject::WAITING,
                ]
            )
        )->isTrue();
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($currenttime_bak)));
        $updated = $ticket->update(['id' => $ticket_id, 'status' => \CommonITILObject::ASSIGNED]);
        $_SESSION['glpi_currenttime'] = $currenttime_bak;
        $this->boolean($updated)->isTrue();
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo($tomorrow_2pm);
    }

    /**
     * Check 'internal_time_to_resolve' computed dates.
     */
    public function testComputationByMonth()
    {
        $this->login();

        $currenttime_bak = $_SESSION['glpi_currenttime'];

       // Create SLM with TTR/TTO OLA/SLA
        $slm = new \SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => 0, //24/24 7/7
            ]
        );
        $this->integer($slm_id)->isGreaterThan(0);

        $ola_ttr = new \OLA();
        $ola_ttr_id = $ola_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR OLA',
                'type'               => \SLM::TTR,
                'number_time'        => 4,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->integer($ola_ttr_id)->isGreaterThan(0);

        $ola_tto = new \OLA();
        $ola_tto_id = $ola_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO OLA',
                'type'               => \SLM::TTO,
                'number_time'        => 3,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->integer($ola_tto_id)->isGreaterThan(0);

        $sla_ttr = new \SLA();
        $sla_ttr_id = $sla_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR SLA',
                'type'               => \SLM::TTR,
                'number_time'        => 2,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->integer($sla_ttr_id)->isGreaterThan(0);

        $sla_tto = new \SLA();
        $sla_tto_id = $sla_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO SLA',
                'type'               => \SLM::TTO,
                'number_time'        => 1,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
            ]
        );
        $this->integer($sla_tto_id)->isGreaterThan(0);

       // Create ticket with SLA/OLA TTO/TTR to test computation based on SLA OLA
        $ticket = new \Ticket();
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
        $this->integer($ticket_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
       //check computed data from SLA / OLA
        $this->integer((int)$ticket->fields['slas_id_tto'])->isEqualTo($sla_tto_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_own'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (1 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['slas_id_ttr'])->isEqualTo($sla_ttr_id);
       // date + delay
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_resolve'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (2 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['olas_id_tto'])->isEqualTo($ola_tto_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_own'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (3 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo($ola_ttr_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_resolve'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['ola_ttr_begin_date']) + (4 * MONTH_TIMESTAMP)));

        $this->integer(strtotime($ticket->fields['ola_ttr_begin_date']))
         ->isEqualTo(strtotime($ticket->fields['date']));

       // Create ticket to test computation based on OLA / SLA on update
        $ticket = new \Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test on update',
            ]
        );
        $this->integer($ticket_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo(0);
        $this->variable($ticket->fields['ola_ttr_begin_date'])->isEqualTo(null);
        $this->variable($ticket->fields['internal_time_to_resolve'])->isEqualTo(null);

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

        $this->boolean($updated)->isTrue();

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

       //check computed data from SLA / OLA
        $this->integer((int)$ticket->fields['olas_id_ttr'])->isEqualTo($ola_ttr_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_resolve'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['ola_ttr_begin_date']) + (4 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['olas_id_tto'])->isEqualTo($ola_tto_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['internal_time_to_own'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date_mod']) + (3 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['slas_id_ttr'])->isEqualTo($sla_ttr_id);
       // date + delay
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_resolve'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (2 * MONTH_TIMESTAMP)));

        $this->integer((int)$ticket->fields['slas_id_tto'])->isEqualTo($sla_tto_id);
        $this->variable(date('Y-m-d H:i:s', strtotime($ticket->fields['time_to_own'])))
         ->isEqualTo(date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (1 * MONTH_TIMESTAMP)));

        $this->integer(strtotime($ticket->fields['ola_ttr_begin_date']))
         ->isEqualTo(strtotime($ticket->fields['date_mod']));
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
            "type"            => \SLM::TTO,
            "number_time"     => 4,
            "definition_time" => "hour",
        ]);
        $sla_ttr = $this->createItem("SLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test SLA ttr",
            "entities_id"     => $entity,
            "type"            => \SLM::TTR,
            "number_time"     => 12,
            "definition_time" => "hour",
        ]);

        // Create OLAs
        $ola_tto = $this->createItem("OLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test OLA tto",
            "entities_id"     => $entity,
            "type"            => \SLM::TTO,
            "number_time"     => 2,
            "definition_time" => "hour",
        ]);
        $ola_ttr = $this->createItem("OLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test OLA ttr",
            "entities_id"     => $entity,
            "type"            => \SLM::TTR,
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
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

        // Check SLA, must be calculated from the ticket start date
        $tto_expected_date = date('Y-m-d H:i:s', strtotime($date_1_hour_ago) + 3600 * 4); // 4 hours TTO
        $ttr_expected_date = date('Y-m-d H:i:s', strtotime($date_1_hour_ago) + 3600 * 12); // 12 hours TTR
        $this->string($ticket->fields['time_to_own'])->isEqualTo($tto_expected_date);
        $this->string($ticket->fields['time_to_resolve'])->isEqualTo($ttr_expected_date);

        // Check escalation levels
        $sla_levels = (new SlaLevel_Ticket())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->array($sla_levels)->hasSize(2);
        $tto_level = array_shift($sla_levels);
        $ttr_level = array_shift($sla_levels);
        $tto_level_expected_date = date('Y-m-d H:i:s', strtotime($tto_expected_date) - 900); // 15 minutes escalation level
        $ttr_level_expected_date = date('Y-m-d H:i:s', strtotime($ttr_expected_date) - 1800); // 30 minutes escalation level
        $this->string($tto_level['date'])->isEqualTo($tto_level_expected_date);
        $this->string($ttr_level['date'])->isEqualTo($ttr_level_expected_date);

        // Check OLA, must be calculated from the date at which it was added to the ticket
        $tto_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 2); // 2 hours TTO
        $ttr_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 8); // 8 hours TTR
        $this->string($ticket->fields['internal_time_to_own'])->isEqualTo($tto_expected_date);
        $this->string($ticket->fields['internal_time_to_resolve'])->isEqualTo($ttr_expected_date);

        // Check escalation levels
        $ola_levels = (new OlaLevel_Ticket())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->array($ola_levels)->hasSize(2);
        $tto_level = array_shift($ola_levels);
        $ttr_level = array_shift($ola_levels);
        $tto_level_expected_date = date('Y-m-d H:i:s', strtotime($tto_expected_date) - 2700); // 45 minutes escalation level
        $ttr_level_expected_date = date('Y-m-d H:i:s', strtotime($ttr_expected_date) - 3600); // 60 minutes escalation level
        $this->string($tto_level['date'])->isEqualTo($tto_level_expected_date);
        $this->string($ttr_level['date'])->isEqualTo($ttr_level_expected_date);
    }

    protected function laProvider(): iterable
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
            foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
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
                    'target_date'       => $la_type == \SLM::TTR
                        // 2034-06-09 08:46:12 + 30 m (LA time) + 2 h 29 m 13 s (waiting time)
                        ? '2034-06-09 11:45:25'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 09:16:12'
                    ,
                    'waiting_duration'  => $la_type == \SLM::TTR
                        ? 8953 // 4692 + 1525 + 2736
                        : 0,
                    // Negative 5 minutes escalation level
                    'escalation_time'        => - 5 * MINUTE_TIMESTAMP,
                    'target_escalation_date' => $la_type == \SLM::TTR
                        // 5 minutes before each target date
                        ? '2034-06-09 11:40:25'
                        : '2034-06-09 09:11:12'
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
                    'target_date'       => $la_type == \SLM::TTR
                        // 2034-06-09 08:46:12 + 4 h (LA time) + 2h 8 m 22 s (waiting time)
                        ? '2034-06-09 14:54:34'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 12:46:12'
                    ,
                    'waiting_duration'  => $la_type == \SLM::TTR ? 7702 : 0,
                    // Positive 10 hour escalation level
                    'escalation_time'   => 10 * HOUR_TIMESTAMP,
                    'target_escalation_date' => $la_type == \SLM::TTR
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
                        : '2034-06-12 14:16:12'
                ];

                // 4 hours LA with pauses accross multiple days
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
                    'target_date'       => $la_type == \SLM::TTR
                        // 2034-06-05 10:30:00 + 4 h (LA time) + 29 h (waiting time) + non-working hours
                        ? '2034-06-08 12:00:00'
                        // TTO is not impacted by waiting times
                        : '2034-06-05 14:30:00'
                    ,
                    'waiting_duration'  => $la_type == \SLM::TTR ? 104400 : 0,
                    // Positive 3 days escalation level
                    'escalation_time'   => 3 * DAY_TIMESTAMP,
                    'target_escalation_date' => $la_type == \SLM::TTR
                         // 3 days after TTR
                         // Skip saturday and sunday (2034-06-10 and 2034-06-11)
                         // The fact that monday start later (+ 2 hours) SHOULD NOT
                         // be taken into account as we work in days not in hours
                         ? '2034-06-13 12:00:00'
                         // 3 day after TTO
                         : '2034-06-08 14:30:00'
                ];

                // 5 days LA over a week-end without pauses
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
                    'target_escalation_date' =>  '2034-06-15 11:16:12'
                ];

                // 5 days LA over a week-end without pauses
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
                    'target_escalation_date' =>  '2034-06-14 19:00:00'
                ];

                // 5 days LA with multiple pauses, including a pause of multiple days over a week-end
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
                    'target_date'       => $la_type == \SLM::TTR
                        // 2034-06-07 10:00:00 + 5 days (LA time)
                        // -> 2034-06-14 10:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 09:00:00'
                        : '2034-06-14 10:00:00' // TTO does is not impacted by waiting times
                    ,
                    'waiting_duration'  => $la_type == \SLM::TTR ? 178200 : 0,
                    // Positive 3 week escalation level
                    'escalation_time'   => 15 * DAY_TIMESTAMP,
                    'target_escalation_date' => $la_type == \SLM::TTR
                        ? '2034-07-12 09:00:00'
                        : '2034-07-05 10:00:00'
                ];

                // 5 days LA with multiple pauses, including a pause of multiple days over a week-end
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
                    'target_date'       => $la_type == \SLM::TTR
                        // 2034-06-07 10:00:00 + 5 days/end of working day(LA time)
                        // -> 2034-06-14 19:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 18:00:00'
                        // TTO does is not impacted by waiting times
                        : '2034-06-14 19:00:00'
                    ,
                    'waiting_duration'  => $la_type == \SLM::TTR ? 178200 : 0,
                    // Positive 2 hours escalation level
                    'escalation_time'   => 2 * HOUR_TIMESTAMP,
                    'target_escalation_date' => $la_type == \SLM::TTR
                        // Must be two hours after their respetive target date
                        ? '2034-06-22 09:30:00'
                        : '2034-06-15 10:30:00'
                ];
            }
        }
    }

    /**
     * @dataProvider laProvider
     */
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
            \SLM::class,
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

        list($la_date_field, $la_fk_field) = $la->getFieldNames($la->fields['type']);
        $ticket = $this->createItem(
            \Ticket::class,
            [
                'name'       => __FUNCTION__,
                'content'    => __FUNCTION__,
                $la_fk_field => $la->getID(),
            ]
        );

        // Apply pauses
        foreach ($pauses as $pause) {
            $_SESSION['glpi_currenttime'] = $pause['from'];
            $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => \Ticket::WAITING]);

            $_SESSION['glpi_currenttime'] = $pause['to'];
            $this->updateItem(\Ticket::class, $ticket->getID(), ['status' => \Ticket::ASSIGNED]);
        }

        // Reload ticket
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

        $this->integer($ticket->fields[$la_class::getWaitingFieldName()])->isEqualTo($waiting_duration);
        $this->string($ticket->fields[$la_date_field])->isEqualTo($target_date);

        // Check escalation date
        $la_level_class = $la->getLevelTicketClass();
        $la_level_ticket = (new $la_level_class())->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->array($la_level_ticket)->hasSize(1);
        $escalation_data = array_pop($la_level_ticket)["date"];
        $this->string($escalation_data)->isEqualTo($target_escalation_date);
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
        $slm = $this->createItem(\SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar,
        ]);

        // Create rules to set full SLA and OLA on ticket creation and to change them on ticket update
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
                $la = new $la_class();
                list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

                // Create two LA with one escalation level
                list($la1, $la2) = $this->createItems($la_class, [
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
                $builder = new RuleBuilder('Add first LA on creation');
                $builder->setEntity($entity)
                    ->setCondtion(RuleTicket::ONADD)
                    ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                    ->addCriteria('entities_id', Rule::PATTERN_IS, $entity)
                    ->addAction('assign', $la_fk_field, $la1->getID());
                $this->createRule($builder);

                // First OLA is added on update
                $builder = new RuleBuilder('Add second LA on update');
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

            foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
                list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 1", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 1 level", true);
                $this->integer($ticket->fields[$la_fk_field])->isEqualTo($expected_la);

                // Check that the target date is correct (+ 4 hours)
                $this->string($ticket->fields[$la_date_field])->isEqualTo('2034-08-16 17:00:00');
            }

            // Check that all escalations levels are sets
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()]);
            $this->array($sa_levels_ticket)->hasSize(2); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->array(
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            )->isEqualTo($expected_la_levels);

            // Check that they match the expected date (- 1 hour)
            $this->array(
                array_unique(array_column($sa_levels_ticket, 'date'))
            )->isEqualTo(['2034-08-16 16:00:00']);
        }

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but we need to be in the input for the rule
        ]);
        $ticket->getFromDB($ticket->getID());

        // Check that each LA TTO and TTR have been modified as expected
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            $la = new $la_class();
            $level_class = $la->getLevelClass();
            $expected_la_levels = [];

            foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
                list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 2", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 2 level", true);
                $this->integer($ticket->fields[$la_fk_field])->isEqualTo($expected_la);

                // Check that the target date is correct (+ 2 hours)
                $this->string($ticket->fields[$la_date_field])->isEqualTo('2034-08-16 15:00:00');
            }

            // Check that all escalations levels have been modifieds
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
            $this->array($sa_levels_ticket)->hasSize(2); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->array(
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            )->isEqualTo($expected_la_levels);

            // Check that they match the expected date (- 1 hour)
            $this->array(
                array_unique(array_column($sa_levels_ticket, 'date'))
            )->isEqualTo(['2034-08-16 14:00:00']);
        }

        // Check that the control ticket LA and escalation levels are valid
        // These checks are needed to ensure the clearInvalidLevels() method only
        // impacted the correct ticket
        foreach ([\OLA::class, \SLA::class] as $la_class) {
            $la = new $la_class();
            $level_class = $la->getLevelClass();
            $expected_la_levels = [];

            foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
                list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

                // Check that the correct LA is assigned to the ticket
                $expected_la = getItemByTypeName($la_class, "$la_class $la_type 1", true);
                $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 1 level", true);
                $this->integer($control_ticket->fields[$la_fk_field])->isEqualTo($expected_la);

                // Check that the target date is correct (+ 4 hours)
                $this->string($control_ticket->fields[$la_date_field])->isEqualTo('2034-08-16 17:00:00');
            }

            // Check that all escalations levels are sets
            $level_ticket_class = $la->getLevelTicketClass();
            $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $control_ticket->getID()], [$level_class::getForeignKeyField()]);
            $this->array($sa_levels_ticket)->hasSize(2); // One TTO and one TTR

            // Check that they match the expected la levels
            $this->array(
                array_column($sa_levels_ticket, $level_class::getForeignKeyField())
            )->isEqualTo($expected_la_levels);

            // Check that they match the expected date (- 1 hour)
            $this->array(
                array_unique(array_column($sa_levels_ticket, 'date'))
            )->isEqualTo(['2034-08-16 16:00:00']);
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
        $slm = $this->createItem(\SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar->getID(),
        ]);

        // Create rules to set SLA on ticket creation
        $la_class = \SLA::class;
        $la = new $la_class();
        foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
            list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

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
            $builder = new RuleBuilder('Add first LA on creation');
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

        foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
            list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName($la_class, "$la_class $la_type", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type level", true);
            $this->integer($ticket->fields[$la_fk_field])->isEqualTo($expected_la);

            // Check that the target date is correct (+ 4 hours)
            $this->string($ticket->fields[$la_date_field])->isEqualTo('2034-08-16 17:00:00');
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()]);
        $this->array($sa_levels_ticket)->hasSize(2); // One TTO and one TTR

        // Check that they match the expected la levels
        $this->array(
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        )->isEqualTo($expected_la_levels);

        // Check that they match the expected date (- 1 hour)
        $this->array(
            array_unique(array_column($sa_levels_ticket, 'date'))
        )->isEqualTo(['2034-08-16 16:00:00']);

        // Put ticket on waiting for 10 minutes
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => \Ticket::WAITING,
        ]);
        $_SESSION['glpi_currenttime'] = '2034-08-16 13:10:00';
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => \Ticket::INCOMING,
        ]);
        $ticket->getFromDB($ticket->getID());

        // Check that TTO and TTR have been modified as expected
        $la = new $la_class();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        $la_type = \SLM::TTR;
        list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

        // Check that the correct LA is assigned to the ticket
        $expected_la = getItemByTypeName($la_class, "$la_class $la_type", true);
        $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type level", true);
        $this->integer($ticket->fields[$la_fk_field])->isEqualTo($expected_la);

        // Check that the target date is correct (+ 4 hours)
        $this->string($ticket->fields[$la_date_field])->isEqualTo('2034-08-16 17:10:00');

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->array($sa_levels_ticket)->hasSize(1);

        // Check that they match the expected la levels
        $this->array(
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        )->isEqualTo($expected_la_levels);

        // Check that they match the expected date (- 1 hour)
        $this->array(
            array_unique(array_column($sa_levels_ticket, 'date'))
        )->isEqualTo(['2034-08-16 16:10:00']);

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
        $slm2 = $this->createItem(\SLM::class, [
            'name'                => 'SLM 2',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar2->getID(),
        ]);

        // Create rules to set full SLA on ticket update
        foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
            $la = new $la_class();
            list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

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
            $builder = new RuleBuilder('Add second LA on update');
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
        $ticket->getFromDB($ticket->getID());

        // Check that TTO and TTR have been modified as expected
        $la = new $la_class();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        foreach ([\SLM::TTO, \SLM::TTR] as $la_type) {
            list($la_date_field, $la_fk_field) = $la->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName($la_class, "$la_class $la_type 2", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "$la_class $la_type 2 level", true);
            $this->integer($ticket->fields[$la_fk_field])->isEqualTo($expected_la);

            // Check that the target date is correct (+ 6 hours)
            $this->string($ticket->fields[$la_date_field])->isEqualTo('2034-08-17 08:10:00');
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->array($sa_levels_ticket)->hasSize(2);

        // Check that they match the expected la levels
        $this->array(
            array_column($sa_levels_ticket, $level_class::getForeignKeyField())
        )->isEqualTo($expected_la_levels);

        // Check that they match the expected date (- 1 hour)1
        $this->array(
            array_unique(array_column($sa_levels_ticket, 'date'))
        )->isEqualTo(['2034-08-16 18:10:00']);
    }
}
