<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

class Slm extends DbTestCase {
   private $method;

   public function beforeTestMethod($method) {
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
   public function testLifecyle() {
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

      $slm    = new \Slm();
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
      $sla    = new \Sla();
      $sla1_id = $sla->add($sla1_in);
      $this->checkInput($sla, $sla1_id, $sla1_in);
      $sla2_id = $sla->add($sla2_in);
      $this->checkInput($sla, $sla2_id, $sla2_in);

      // add two ola (TTO & TTR), we re-use the same inputs as sla
      $ola  = new \Ola();
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

      $scrit    = new \SlaLevelCriteria;
      $ocrit    = new \OlaLevelCriteria;
      $saction  = new \SlaLevelAction;
      $oaction  = new \OlaLevelAction;

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
      $ruleticket = new \RuleTicket;
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

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
      $ticket = new \Ticket;
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
      $ticket = new \Ticket;
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
}
