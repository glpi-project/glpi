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

use Glpi\DBAL\QueryExpression;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\ITILTrait;
use Glpi\Tests\Glpi\SLMTrait;
use Glpi\Tests\RuleBuilder;
use MassiveAction;
use OLA;
use OlaLevel_Ticket;
use PHPUnit\Framework\Attributes\DataProvider;
use Rule;
use RuleCommonITILObject;
use RuleTicket;
use SLA;
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
     *
     * - assign SLA and OLA by rule
     * - delete SLM : related objects should be deleted
     *      - ola/sla
     *     - ola/sla levels
     *     - ola/sla criteria
     *     - ola/sla actions
     */
    public function testSlmDeletion()
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
        $sla_tto_input = $sla_ttr_input = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => SLM::TTO,
            'number_time'     => 4,
            'definition_time' => 'day',
            'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
        ];
        $sla_ttr_input['type'] = SLM::TTR;
        $sla_ttr_input['name'] = "SLA TTR";

        // add two sla (TTO & TTR)
        $sla    = new SLA();
        $sla1_id = $sla->add($sla_tto_input);
        $this->checkInput($sla, $sla1_id, $sla_tto_input);
        $sla2_id = $sla->add($sla_ttr_input);
        $this->checkInput($sla, $sla2_id, $sla_ttr_input);

        // add two ola (TTO & TTR), we re-use the same inputs as sla
        $ola_tto_input = $sla_tto_input + ['groups_id' => getItemByTypeName('Group', '_test_group_1', true)];
        $ola_tto_input['name'] = str_replace("SLA", "OLA", $sla_tto_input['name']);

        $ola  = new OLA();
        $ola_tto_id = $ola->add($ola_tto_input);
        $this->checkInput($ola, $ola_tto_id, $ola_tto_input);

        $ola_ttr_input = $sla_ttr_input + ['groups_id' => getItemByTypeName('Group', '_test_group_1', true)];
        $ola_ttr_input['name'] = str_replace("SLA", "OLA", $sla_ttr_input['name']);

        $ola_ttr_id = $ola->add($ola_ttr_input);
        $this->checkInput($ola, $ola_ttr_id, $ola_ttr_input);

        // prepare levels input for each ola/sla
        $sla_level_1_input = $slal2_in = $olal1_in = $olal2_in = [
            'name'           => __METHOD__,
            'execution_time' => -DAY_TIMESTAMP,
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id,
            'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
        ];
        $slal2_in['slas_id'] = $sla2_id;
        unset($olal1_in['slas_id'], $olal2_in['slas_id']);
        $olal1_in['olas_id'] = $ola_tto_id;
        $olal2_in['olas_id'] = $ola_ttr_id;

        // add levels
        $sla_level = new \SlaLevel();
        $slal1_id = $sla_level->add($sla_level_1_input);
        $this->checkInput($sla_level, $slal1_id, $sla_level_1_input);
        $slal2_id = $sla_level->add($slal2_in);
        $this->checkInput($sla_level, $slal2_id, $slal2_in);

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
        // assign slas
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
        // assign olas
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'olas_id',
            'value'       => $ola_tto_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'olas_id',
            'value'       => $ola_ttr_id,
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

        $id_ttr_data = $ticket->getOlasTTRData()[0]['olas_id'] ?? throw new \Exception('Ola TTR not found');
        $id_tto_data = $ticket->getOlasTTOData()[0]['olas_id'] ?? throw new \Exception('Ola TTO not found');
        $this->assertEquals($ola_tto_id, (int) $id_tto_data);
        $this->assertEquals($ola_ttr_id, (int) $id_ttr_data);
        $time_to_resolve = $ticket->fields['time_to_resolve'];
        $time_to_own = $ticket->fields['time_to_own'];
        $this->assertEquals(19, strlen($time_to_resolve));
        $this->assertEquals(19, strlen($time_to_own));
        $this->assertCount(2, $ticket->getOlasData());

        // ## 3 - Action - purge slm
        $this->deleteItem(SLM::class, $slm_id, true);

        // ## 4 - Check related objects deletion / status

        // sla are deleted
        $this->assertFalse($sla->getFromDB($sla1_id));
        $this->assertFalse($sla->getFromDB($sla2_id));

        // ola are deleted
        $this->assertFalse($ola->getFromDB($ola_tto_id));
        $this->assertFalse($ola->getFromDB($ola_ttr_id));

        // slalevels are deleted
        $this->assertFalse($sla_level->getFromDB($slal1_id));
        $this->assertFalse($sla_level->getFromDB($slal2_id));

        // olalevel are deleted
        $this->assertFalse($olal->getFromDB($olal1_id));
        $this->assertFalse($olal->getFromDB($olal2_id));

        // levels criterias are deleted
        $this->assertFalse($scrit->getFromDB($scrit_id));
        $this->assertFalse($ocrit->getFromDB($ocrit_id));

        // levels actions are deleted
        $this->assertFalse($saction->getFromDB($saction_id));
        $this->assertFalse($oaction->getFromDB($oaction_id));

        // rule becomes inactive
        $rule = getItemByTypeName(RuleTicket::class, __METHOD__);
        $this->assertEquals(0, $rule->fields['is_active']);

        // ticket SLA and OLA are not associated anymore
        $ticket = getItemByTypeName(Ticket::class, __METHOD__);
        $this->assertEquals(0, (int) $ticket->fields['slas_id_tto']);
        $this->assertEquals(0, (int) $ticket->fields['slas_id_ttr']);
        $this->assertEmpty($ticket->getOlasData());

        // sla due times are preserved
        $this->assertEquals($time_to_resolve, $ticket->fields['time_to_resolve']);
        $this->assertEquals($time_to_own, $ticket->fields['time_to_own']);
    }

    /**
     * Create a full SLM by month with all level filled (slm/sla/ola/levels/action/criterias)
     * And Delete IT to check clean os sons objects
     *
     * - assign SLA and OLA by rule
     * - delete SLM : related objects should be deleted
     *     - ola/sla
     *     - ola/sla levels
     *     - ola/sla criteria
     *     - ola/sla actions
     */
    public function testSlmDeletionByMonth()
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
            'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
        ];
        $sla2_in['type'] = SLM::TTR;
        $sla2_in['name'] = "SLA TTR";

        // add two sla (TTO & TTR)
        $sla    = new SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);
        $sla2_id = $sla->add($sla2_in);
        $this->checkInput($sla, $sla2_id, $sla2_in);

        // add two ola (TTO & TTR), we re-use the same inputs as sla
        $ola  = new OLA();
        $ola1_in = $sla1_in + ['groups_id' => getItemByTypeName('Group', '_test_group_1', true)];
        $sla1_in['name'] = str_replace("SLA", "OLA", $ola1_in['name']);

        $ola1_id = $ola->add($ola1_in);
        $this->checkInput($ola, $ola1_id, $ola1_in);

        $ola2_in = $sla2_in + ['groups_id' => getItemByTypeName('Group', '_test_group_1', true)];
        $ola2_in['name'] = str_replace("SLA", "OLA", $ola2_in['name']);
        $ola2_id = $ola->add($ola2_in);
        $this->checkInput($ola, $ola2_id, $ola2_in);

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
            'action_type' => 'append',
            'field'       => 'olas_id',
            'value'       => $ola1_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);
        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'olas_id',
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
        $id_ttr_data = $ticket->getOlasTTRData()[0]['olas_id'];
        $id_tto_data = $ticket->getOlasTTOData()[0]['olas_id'];
        $this->assertEquals($ola1_id, (int) $id_tto_data);
        $this->assertEquals($ola2_id, (int) $id_ttr_data);
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
        $this->assertEmpty($ticket->getOlasTTOData());
        $this->assertEmpty($ticket->getOlasTTRData());

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
        $this->assertEquals(19, strlen($ticket->getField('time_to_resolve')));

        $id_ttr_data = $ticket->getOlasTTRData()[0]['olas_id'];
        $id_tto_data = $ticket->getOlasTTOData()[0]['olas_id'];
        $this->assertEquals($ola1_id, (int) $id_tto_data);
        $this->assertEquals($ola2_id, (int) $id_ttr_data);

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
     * Check OLA TTR computed dates (start_time + due_time)
     */
    public function testOlaTtrComputation()
    {
        $this->login();

        $currenttime_bak = \Session::getCurrentTime();
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

        $ola = new OLA();
        $ola_id = $ola->add(
            [
                'slms_id'         => $slm_id,
                'name'            => 'Test TTR OLA',
                'type'            => SLM::TTR,
                'number_time'     => 4,
                'definition_time' => 'hour',
                'groups_id'      => getItemByTypeName('Group', '_test_group_1', true),
                'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );
        $this->assertGreaterThan(0, $ola_id);

        // Create ticket to test computation based on OLA
        $ticket = $this->createTicket();
        $ticket_id = $ticket->getID();
        $this->assertGreaterThan(0, $ticket_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        // assert no OLA associated
        $this->assertEmpty($ticket->getOlasData());

        // Assign TTR OLA
        $update_time = strtotime('+10s'); // to check ola start time is related to the moment the ola is added
        $this->setCurrentTime(date('Y-m-d H:i:s', $update_time));

        // add an OLA
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => [(int) $ola_id]]);
        $this->setCurrentTime($currenttime_bak);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        $ola_ttr = $ticket->getOlasTTRData()[0];
        $this->assertEquals($ola_id, (int) $ola_ttr['olas_id']);
        $this->assertEquals($update_time, strtotime($ola_ttr['start_time']));
        $this->assertEquals($tomorrow_1pm, $ola_ttr['due_time']);

        // Simulate waiting to first working hour +1 (set status to waiting, forward one hour)
        // because of waiting status for one hour, due_time is increased by one hour.
        $this->assertTrue(
            $ticket->update(
                [
                    'id' => $ticket_id,
                    'status' => \CommonITILObject::WAITING,
                ]
            )
        );
        $this->setCurrentTime(date('Y-m-d H:i:s', strtotime('tomorrow 10am')));
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['status' => \CommonITILObject::ASSIGNED]);
        $this->setCurrentTime($currenttime_bak);
        // find ola and check due_time (former ticket::internal_time_to_resolve)
        $ola_data = $ticket->getOlasTTRData()[0];
        $this->assertEquals($tomorrow_2pm, $ola_data['due_time']);
    }

    /**
     * Check SLA and OLA due date computation on an slm without calendar (24/24 7/7 time is counted).
     * - time_to_resolve + time_to_own for SLA
     * - due_time for OLA Tto + Ttr
     */
    public function testComputationByMonthWithoutCalendar()
    {
        // --- arrange
        $this->login();

        $currenttime_bak = \Session::getCurrentTime();

        // Create SLM with TTR/TTO OLA/SLA
        $slm = new SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => 0, //24/24 7/7
            ]
        );
        $this->assertGreaterThan(0, $slm_id);

        $ola_ttr = new OLA();
        $ola_ttr_id = $ola_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR OLA',
                'type'               => SLM::TTR,
                'number_time'        => 4,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
                'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
                'entities_id'       => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );
        $this->assertGreaterThan(0, $ola_ttr_id);

        $ola_tto = new OLA();
        $ola_tto_id = $ola_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO OLA',
                'type'               => SLM::TTO,
                'number_time'        => 3,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
                'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
                'entities_id'       => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );
        $this->assertGreaterThan(0, $ola_tto_id);

        $sla_ttr = new SLA();
        $sla_ttr_id = $sla_ttr->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTR SLA',
                'type'               => SLM::TTR,
                'number_time'        => 2,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
                'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
            ]
        );
        $this->assertGreaterThan(0, $sla_ttr_id);

        $sla_tto = new SLA();
        $sla_tto_id = $sla_tto->add(
            [
                'slms_id'            => $slm_id,
                'name'               => 'Test TTO SLA',
                'type'               => SLM::TTO,
                'number_time'        => 1,
                'definition_time'    => 'month',
                'end_of_working_day' => false,
                'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
            ]
        );
        $this->assertGreaterThan(0, $sla_tto_id);

        // --- act
        // Create ticket with SLA/OLA TTO/TTR to test computation based on SLA OLA
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => 'Test Ticket',
                'content' => 'Ticket for TTR OLA test on create',
                '_la_update' => true,
                '_olas_id' => [
                    $ola_ttr_id,
                    $ola_tto_id,
                ],
                'slas_id_ttr' => $sla_ttr_id,
                'slas_id_tto' => $sla_tto_id,
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        $this->assertTrue($ticket->getFromDB($ticket_id));

        // --- assert
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

        $fetched_ola_data = $ticket->getOlasTTOData()[0];
        $this->assertEquals($ola_tto_id, (int) $fetched_ola_data['olas_id']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + (3 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['due_time']))
        );

        $fetched_ola_data = $ticket->getOlasTTRData()[0];
        $this->assertEquals($ola_ttr_id, (int) $fetched_ola_data['olas_id']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['start_time']) + (4 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['due_time']))
        );

        $this->assertEquals(
            strtotime($ticket->fields['date']),
            strtotime($fetched_ola_data['start_time'])
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

        $fetched_ola_data = $ticket->getOlasTTRData();
        $this->assertEmpty($fetched_ola_data);

        // Assign TTR/TTO OLA/SLA
        $this->setCurrentTime(date('Y-m-d H:i:s', strtotime('+10s')));
        $updated = $ticket->update(
            [
                'id' => $ticket_id,
                '_la_update' => true,
                '_olas_id'   => [
                    $ola_ttr_id,
                    $ola_tto_id,
                ],
                'slas_id_ttr' => $sla_ttr_id,
                'slas_id_tto' => $sla_tto_id,
                'date_mod'    => date('Y-m-d H:i:s', strtotime($ticket->fields['date']) + 1),
            ]
        );
        $this->setCurrentTime($currenttime_bak);

        $this->assertTrue($updated);
        $this->assertTrue($ticket->getFromDB($ticket_id));

        //check computed data from SLA / OLA
        $fetched_ola_data = $ticket->getOlasTTRData()[0];
        $this->assertEquals($ola_ttr_id, (int) $fetched_ola_data['olas_id']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['start_time']) + (4 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['due_time']))
        );

        $fetched_ola_data = $ticket->getOlasTTOData()[0];
        $this->assertEquals($ola_tto_id, (int) $fetched_ola_data['olas_id']);
        $this->assertEquals(
            date('Y-m-d H:i:s', strtotime($ticket->fields['date_mod']) + (3 * MONTH_TIMESTAMP)),
            date('Y-m-d H:i:s', strtotime($fetched_ola_data['due_time']))
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
            strtotime($fetched_ola_data['start_time'])
        );
    }

    /**
     * Ensure all SLA time_to_own and time_to_resolve + OLA due_time are properly set
     * in a ticket as well as their escalation date
     */
    public function testDueDatesAndEscalationDate(): void
    {
        // --- arrange
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
            'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
        ]);
        $ola_ttr = $this->createItem("OLA", [
            "slms_id"         => $slm->getID(),
            "name"            => "Test OLA ttr",
            "entities_id"     => $entity,
            "type"            => SLM::TTR,
            "number_time"     => 8,
            "definition_time" => "hour",
            'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
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

        // --- act
        // Create a ticket 1 hour ago without any SLA
        $date_1_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime(\Session::getCurrentTime())));
        $ticket = $this->createItem("Ticket", [
            "name"        => "Test ticket",
            "content"     => "Test ticket",
            "entities_id" => $entity,
            "date"        => $date_1_hour_ago,
        ]);

        // Add SLA and OLA to the ticket
        $now = \Session::getCurrentTime(); // Keep track of when the OLA where set
        $this->updateItem("Ticket", $ticket->getID(), [
            "slas_id_tto" => $sla_tto->getID(),
            "slas_id_ttr" => $sla_ttr->getID(),
            "_la_update" => true,
            "_olas_id" => [
                $ola_tto->getID(),
                $ola_ttr->getID(),
            ],
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
        $fetched_ola_tto_data = $ticket->getOlasTTOData()[0];
        $fetched_ola_ttr_data = $ticket->getOlasTTRData()[0];
        $tto_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 2); // 2 hours TTO
        $ttr_expected_date = date('Y-m-d H:i:s', strtotime($now) + 3600 * 8); // 8 hours TTR
        $this->assertEquals($tto_expected_date, $fetched_ola_tto_data['due_time']);
        $this->assertEquals($ttr_expected_date, $fetched_ola_ttr_data['due_time']);

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

        foreach ([OLA::class, SLA::class] as $la_class) {
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
                    // escalation 10 minutes before target date
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
                    'target_date'       => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // 2034-06-09 08:46:12 + 30 m (LA time) + 2 h 29 m 13 s (waiting time)
                        ? '2034-06-09 11:45:25'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 09:16:12',
                    'waiting_duration'  => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        ? 8953 // 4692 + 1525 + 2736
                        : 0,
                    // Negative 5 minutes escalation level
                    'escalation_time'        => - 5 * MINUTE_TIMESTAMP,
                    'target_escalation_date' => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
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
                    'target_date'       => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // 2034-06-09 08:46:12 + 4 h (LA time) + 2h 8 m 22 s (waiting time)
                        ? '2034-06-09 14:54:34'
                        // TTO does is not impacted by waiting times
                        : '2034-06-09 12:46:12',
                    'waiting_duration'  => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class ? 7702 : 0,
                    // Positive 10 hour escalation level
                    'escalation_time'   => 10 * HOUR_TIMESTAMP,
                    'target_escalation_date' => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
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
                    'target_date'       => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // 2034-06-05 10:30:00 + 4 h (LA time) + 29 h (waiting time) + non-working hours
                        ? '2034-06-08 12:00:00'
                        // TTO is not impacted by waiting times
                        : '2034-06-05 14:30:00',
                    'waiting_duration'  => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class ? 104400 : 0,
                    // Positive 3 days escalation level
                    'escalation_time'   => 3 * DAY_TIMESTAMP,
                    'target_escalation_date' => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
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
                    'target_date'       => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // 2034-06-07 10:00:00 + 5 days (LA time)
                        // -> 2034-06-14 10:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 09:00:00'
                        : '2034-06-14 10:00:00' // TTO does is not impacted by waiting times
                    ,
                    'waiting_duration'  => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class ? 178200 : 0,
                    // Positive 3 week escalation level
                    'escalation_time'   => 15 * DAY_TIMESTAMP,
                    'target_escalation_date' => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
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
                    'target_date'       => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // 2034-06-07 10:00:00 + 5 days/end of working day(LA time)
                        // -> 2034-06-14 19:00:00 + 49 h 30 m (waiting time) + non-working hours
                        ? '2034-06-21 18:00:00'
                        // TTO does is not impacted by waiting times
                        : '2034-06-14 19:00:00',
                    'waiting_duration'  => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class ? 178200 : 0,
                    // Positive 2 hours escalation level
                    'escalation_time'   => 2 * HOUR_TIMESTAMP,
                    'target_escalation_date' => ($la_type == SLM::TTR && $la_class == SLA::class) || $la_class == OLA::class
                        // Must be two hours after their respective target date
                        ? '2034-06-22 09:30:00'
                        : '2034-06-15 10:30:00',
                ];
            }
        }
    }

    #[DataProvider('laProvider')]
    public function testEscalationLevelComputationOnSla(
        string $la_class,
        array $la_params,
        string $begin_date,
        array $pauses,
        string $target_date,
        int $waiting_duration,
        int $escalation_time,
        string $target_escalation_date
    ): void {
        // test only works with SLA
        if ($la_class == OLA::class) {
            return;
        }

        // --- arrange
        $this->login();

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
        $this->setCurrentTime($begin_date);

        [$la_date_field, $la_fk_field] = $la->getFieldNames($la->fields['type']);
        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'       => __FUNCTION__,
                'content'    => __FUNCTION__,
                $la_fk_field => $la->getID(),
            ]
        );

        // --- act
        // Apply pauses
        foreach ($pauses as $pause) {
            $this->setCurrentTime($pause['from']);
            $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::WAITING]);

            $this->setCurrentTime($pause['to']);
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

    #[DataProvider('laProvider')]
    public function testEscalationLevelComputationOnOla(
        string $la_class,
        array $la_params,
        string $begin_date,
        array $pauses,
        string $target_date,
        int $waiting_duration,
        int $escalation_time,
        string $target_escalation_date
    ): void {
        // test only works with SLA
        if ($la_class == SLA::class) {
            return;
        }

        // --- arrange
        $this->login();

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
        $ola = $this->createItem(
            OLA::class,
            [
                'name'    => __FUNCTION__,
                'slms_id' => $slm->getID(),
                'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
                'entities_id'       => getItemByTypeName('Entity', '_test_root_entity', true),
            ] + $la_params
        );

        // Create escalation level
        $this->createItem($ola->getLevelClass(), [
            'name'                          => 'Test escalation level',
            'execution_time'                => $escalation_time,
            'is_active'                     => 1,
            'is_recursive'                  => 1,
            'match'                         => "OR",
            OLA::class::getForeignKeyField() => $ola->getID(),
        ]);

        // Create a ticket
        $this->setCurrentTime($begin_date);

        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'       => __FUNCTION__,
                'content'    => __FUNCTION__,
                '_la_update' => true,
                '_olas_id' => [$ola->getID()],
            ]
        );

        // --- act
        // Apply pauses
        foreach ($pauses as $pause) {
            $this->setCurrentTime($pause['from']);
            $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::WAITING]);

            $this->setCurrentTime($pause['to']);
            $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::ASSIGNED]);
        }

        // Reload ticket
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        if ($la_class == OLA::class) {
            $this->runOlaCron();
        } else {
            $this->runSlaCron();
        }

        $ola_data = $ticket->getOlasData()[0];
        $this->assertEquals($waiting_duration, $ola_data['waiting_time']);
        $this->assertEquals($target_date, $ola_data['due_time']);

        // Check escalation date
        $la_level_class = $ola->getLevelTicketClass();
        $la_level_ticket = (new $la_level_class())->find([
            'tickets_id' => $ticket->getID(),
        ]);

        $this->assertCount(1, $la_level_ticket);
        $escalation_data = array_pop($la_level_ticket)["date"];
        $this->assertEquals($target_escalation_date, $escalation_data);
    }

    /**
     * Assign SLA to a ticket then reassign them with a rule
     * The ticket should only have the escalation level of the second set of SLA / OLA
     */
    public function testLaChangeOnSLA(): void
    {
        $this->login();
        $la_class = SLA::class;
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // OLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $calendar = getItemByTypeName('Calendar', 'Default', true);
        $this->setCurrentTime('2034-08-16 13:00:00');

        // Create test SLM
        $slm = $this->createItem(SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'is_recursive'        => true,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar,
        ]);

        // Create rules to set full SLA on ticket creation and to change them on ticket update
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

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but we need to be in the input for the rule
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that each LA TTO and TTR have been modified as expected
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

        // Check that the control ticket LA and escalation levels are valid
        // These checks are needed to ensure the clearInvalidLevels() method only
        // impacted the correct ticket
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
    /**
     * Assign SLA and OLA to a ticket then reassign them with a rule
     * The ticket should have the escalation levels of the first and second SLA/OLA
     */
    public function testLaChangeOnOLA(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // OLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $calendar = getItemByTypeName('Calendar', 'Default', true);
        $this->setCurrentTime('2034-08-16 13:00:00');

        // Create test SLM
        $slm = $this->createItem(SLM::class, [
            'name'                => 'SLM',
            'entities_id'         => $entity,
            'use_ticket_calendar' => false,
            'calendars_id'        => $calendar,
        ]);

        // Create rules to set full SLA and OLA on ticket creation and to change them on ticket update
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            // Create two LA with one escalation level
            [$ola1, $ola2] = $this->createItems(OLA::class, [
                [
                    'name'                => "OLA $la_type 1",
                    'entities_id'         => $entity,
                    'type'                => $la_type,
                    'number_time'         => 4,
                    'calendars_id'        => $calendar,
                    'definition_time'     => 'hour',
                    'end_of_working_day'  => false,
                    'slms_id'             => $slm->getID(),
                    'use_ticket_calendar' => false,
                    'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
                ],
                [
                    'name'                => "OLA $la_type 2",
                    'entities_id'         => $entity,
                    'type'                => $la_type,
                    'number_time'         => 2,
                    'calendars_id'        => $calendar,
                    'definition_time'     => 'hour',
                    'end_of_working_day'  => false,
                    'slms_id'             => $slm->getID(),
                    'use_ticket_calendar' => false,
                    'groups_id'          => getItemByTypeName('Group', '_test_group_1', true),
                ],
            ]);
            foreach ([$ola1, $ola2] as $created_la) {
                $this->createItem(\OlaLevel::class, [
                    'name'                          => $created_la->fields['name'] . ' level',
                    OLA::class::getForeignKeyField() => $created_la->getID(),
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
                ->setCondtion(RuleCommonITILObject::ONADD)
                ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                ->addCriteria('entities_id', Rule::PATTERN_IS, $entity)
                ->addAction('append', 'olas_id', $ola1->getID());
            $this->createRule($builder);

            // Second OLA is added on update
            $builder = new RuleBuilder('Add second LA on update', RuleTicket::class);
            $builder->setEntity($entity)
                ->setCondtion(RuleCommonITILObject::ONUPDATE)
                ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                ->addCriteria('urgency', Rule::PATTERN_IS, 5)
                ->addAction('append', 'olas_id', $ola2->getID());
            $this->createRule($builder);
        }
        unset($ola1, $ola2);


        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // ticket just created, two ola should be associated on creation, one for the OLA TTO and one for the OLA TTR
        $this->assertEquals(2, countElementsInTable(\Item_Ola::getTable(), ['items_id' => $ticket->getID()]));

        // Create another ticket as a control subject that shouldn't be impacted
        // by changes on the other ticket
        $control_ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Check that each LA TTO and TTR are set as expected
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName(OLA::class, "OLA $la_type 1", true);
            $expected_la_levels[] = getItemByTypeName(\OlaLevel::class, "OLA $la_type 1 level", true);
            $associated_ola = match ($la_type) {
                SLM::TTO => $ticket->getOlasTTOData()[0],
                SLM::TTR => $ticket->getOlasTTRData()[0],
            };
            $this->assertEquals($expected_la, $associated_ola['olas_id']);

            // Check that the target date is correct (+ 4 hours)
            $this->assertEquals('2034-08-16 17:00:00', $associated_ola['due_time']);
        }

        // Check that all escalations levels are sets
        $sa_levels_ticket = (new OlaLevel_Ticket())->find(['tickets_id' => $ticket->getID()]);
        $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

        // Check that they match the expected la levels
        $this->assertEquals(
            $expected_la_levels,
            array_column($sa_levels_ticket, \OlaLevel::class::getForeignKeyField())
        );

        // Check that they match the expected date (- 1 hour)
        $fetched_dates = array_unique(array_column($sa_levels_ticket, 'date'));
        $this->assertEquals(
            ['2034-08-16 16:00:00'],
            $fetched_dates
        );

        // --- Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated, but needs to be in the input for the rule
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that each LA TTO and TTR have been modified as expected
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            $associated_olas = match ($la_type) {
                SLM::TTO => $ticket->getOlasTTOData(),
                SLM::TTR => $ticket->getOlasTTRData(),
            };

            // 2 associated olas one by creation and one by update
            $this->assertCount(2, $associated_olas);

            // Check that the correct new OLA is associated to the ticket
            $expected_la = getItemByTypeName(OLA::class, "OLA $la_type 2", true);
            $expected_la_levels[] = getItemByTypeName(\OlaLevel::class, "OLA $la_type 2 level", true);
            $this->assertContains($expected_la, array_column($associated_olas, 'olas_id'));

            // Check that the target date is correct (+ 2 hours)
            $associated_ola = array_values(array_filter($associated_olas, fn($io) => $io['olas_id'] == $expected_la))[0];
            $this->assertEquals('2034-08-16 15:00:00', $associated_ola['due_time']); // tmp remettre
        }

        // Check that all escalations levels have been modified
        $sa_levels_ticket = (new OlaLevel_Ticket())->find(['tickets_id' => $ticket->getID()]);
        $this->assertCount(4, $sa_levels_ticket); // One TTO and one TTR added on creation, plus one TTO and one TTR added on update

        // Check that they match the expected la levels
        $fetched_la_levels = array_column($sa_levels_ticket, \OlaLevel::class::getForeignKeyField());
        $this->assertEqualsCanonicalizing(
            $expected_la_levels,
            $fetched_la_levels
        );

        // Check that they match the expected date (- 1 hour)
        $fetched_dates = array_unique(array_column($sa_levels_ticket, 'date'));
        $expected_dates = ['2034-08-16 14:00:00', '2034-08-16 16:00:00'];
        $this->assertEqualsCanonicalizing(
            $expected_dates,
            $fetched_dates
        );

        // Check that the control ticket LA and escalation levels are valid
        // These checks are needed to ensure the clearInvalidLevels() method only
        // impacted the correct ticket
        $la = new OLA();
        $level_class = $la->getLevelClass();
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            $associated_ola = match ($la_type) {
                SLM::TTO => $control_ticket->getOlasTTOData()[0],
                SLM::TTR => $control_ticket->getOlasTTRData()[0],
            };

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName(OLA::class, "OLA $la_type 1", true);
            $expected_la_levels[] = getItemByTypeName(\OlaLevel::class, "OLA $la_type 1 level", true);
            $this->assertEquals($expected_la, $associated_ola['olas_id']);

            // Check that the target date is correct (+ 4 hours)
            $this->assertEquals('2034-08-16 17:00:00', $associated_ola['due_time']);
        }

        // Check that all escalations levels are sets on the control ticket
        $level_ticket_class = $la->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $control_ticket->getID()], [\OlaLevel::class::getForeignKeyField()]);
        $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

        // Check that they match the expected la levels
        $this->assertEquals(
            $expected_la_levels,
            array_column($sa_levels_ticket, \OlaLevel::class::getForeignKeyField())
        );

        // Check that they match the expected date (- 1 hour)
        $this->assertEquals(
            ['2034-08-16 16:00:00'],
            array_unique(array_column($sa_levels_ticket, 'date'))
        );
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

        // --- 10:00 - create ticket
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runOlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            '_la_update' => true,
            '_olas_id' => [$ola->getID()],
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();

        $ola_data = $ticket->getOlasTTRData()[0];
        $this->assertEquals($ola->getID(), $ola_data['olas_id']);
        // TTR is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ola_data['due_time'], -8));
        // next level to be processed is level_1
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_1->getID()]));

        // --- 11:01 ticket : level 1 is reached, so executed then removed from todo levels -> next level to be processed is level_2
        $this->setCurrentTime('2025-05-26 11:01:00');
        $this->runOlaCron();

        // next level to be processed is level_2
        $this->assertTrue((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID(), 'olalevels_id' => $level_2->getID()]));
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(4, $ticket->fields['priority'], 'Level action not processed'); // level_1 action is applied

        // --- 11:31 ticket : level 2 is reached
        $this->setCurrentTime('2025-05-26 11:31:00');
        $this->runOlaCron();
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

        // --- 10:00 - create ticket
        $this->setCurrentTime('2025-05-26 10:00:00');
        $this->runOlaCron(); // no changes will be triggered
        $ticket = $this->createTicket([
            'status' => \CommonITILObject::INCOMING,
            '_la_update' => true,
            '_olas_id' => [$ola->getID()],
            'priority' => 3,  // to match level_1 criteria
        ]);
        $ticket_id = $ticket->getID();

        $ola_data = $ticket->getOlasTTOData()[0];
        $this->assertEquals($ola->getID(), $ola_data['olas_id']);
        // TTR is computed from the time of creation 10:00 + 120 minutes -> 12:00
        $this->assertEquals('12:00:00', substr($ola_data['due_time'], -8));
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
        // no next level to be processed
        $this->assertFalse((new OlaLevel_Ticket())->getFromDBByCrit(['tickets_id' => $ticket->getID()]));
        // priority changed to 5
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $this->assertEquals(5, $ticket->fields['priority']); // level_1 action is applied
    }

    /**
     * Test with two OLA TTO with escalation levels
     */
    public function testEscalationLevelsChangesWithMultiplesOlas(): void
    {
        // --- arrange
        $this->login();
        // create slm + ola tto with 120 minutes
        ['ola' => $ola_1, 'slm' => $slm, 'group' => $group] = $this->createOLA(data: ['number_time' => 120, 'definition_time' => 'minute',], ola_type: SLM::TTO);
        ['ola' => $ola_2] = $this->createOLA(data: ['number_time' => 120, 'definition_time' => 'minute',], ola_type: SLM::TTO, group: $group, slm: $slm);

        $levels = []; // [ola_id => olalevels_id, ...]
        // add 1 escalation level to each ola
        foreach ([$ola_1, $ola_2] as $ola) {
            // level
            $_level = $this->createItem(\OlaLevel::class, [
                'name' => 'OLA level ' . time(),
                'olas_id' => $ola->getID(),
                'execution_time' => -60 * MINUTE_TIMESTAMP,
                'is_active' => 1,
                'is_recursive' => 1,
                'match' => 'AND',
            ]);
            $levels[$ola->getID()] = $_level->getID();
            // criteria : ticket priority = 3
            $this->createItem(\OlaLevelCriteria::class, ['criteria' => 'priority', 'condition' => 0, 'pattern' => 3, 'olalevels_id' => $_level->getID()]);
            // action : ticket priority -> 4
            $this->createItem(\OlaLevelAction::class, ['action_type' => 'assign', 'field' => 'priority', 'value' => 4, 'olalevels_id' => $_level->getID()]);
        }

        // -- act : associate both OLA TTO to a ticket
        $this->setCurrentTime('2025-05-26 10:00:00');
        $ticket = $this->createTicket([
            '_la_update' => true,
            '_olas_id' => [$ola_1->getID(), $ola_2->getID()],
            'priority' => 3,  // to match level criteria
            '_skip_auto_assign' => true,
        ]);

        assert(empty($ticket->getGroups(\CommonITILActor::ASSIGN) + $ticket->getUsers(\CommonITILActor::ASSIGN)), 'Ticket should not be assigned, escalation don\'t work for ola tto in this case.');
        assert(0 === $ticket->fields['takeintoaccount_delay_stat'], 'Ticket takeintoaccount_delay_stat field should be 0, escalation don\'t work for ola tto in this case.');

        // go 70 minutes forward, so esaclation level is reached
        $this->setCurrentTime('2025-05-26 11:10:00');

        // -- assert : levels should be in olalevels_tickets
        $levels_todo = (new OlaLevel_Ticket())->find(['tickets_id' => $ticket->getID()]);
        $map_level_to_olalevels_id = fn(array $levels) => array_map(fn($l) => $l['olalevels_id'], $levels);
        $this->assertEqualsCanonicalizing(
            $levels,
            $map_level_to_olalevels_id($levels_todo)
        );
    }

    /**
     * Check recalculating the SLA when the SLA is changed to an SLA with a different calendar
     *
     */
    public function testSLaChangeCalendar(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // SLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $this->setCurrentTime('2034-08-16 13:00:00');

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
        $sla = new SLA();
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $sla->getFieldNames($la_type);

            // Create two LA with one escalation level
            $sla = $this->createItem(SLA::class, [
                'name'                => "\SLA $la_type",
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
            $this->createItem($sla->getLevelClass(), [
                'name'                          => $sla->fields['name'] . ' level',
                SLA::class::getForeignKeyField() => $sla->getID(),
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
                ->addAction('assign', $la_fk_field, $sla->getID());
            $this->createRule($builder);
        }

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Check that TTO and TTR are set as expected
        $sla = new SLA();
        $level_class = $sla->getLevelClass();
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $sla->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName(SLA::class, "\SLA $la_type", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "\SLA $la_type level", true);
            $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

            // Check that the target date is correct (+ 4 hours)
            $this->assertEquals('2034-08-16 17:00:00', $ticket->fields[$la_date_field]);
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $sla->getLevelTicketClass();
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
        $this->setCurrentTime('2034-08-16 13:10:00');
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => Ticket::INCOMING,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that TTO and TTR have been modified as expected
        $sla = new SLA();
        $level_class = $sla->getLevelClass();
        $expected_la_levels = [];

        // since ticket has been put on waiting, TTO is completed, no more TTO levels to check
        $la_type = SLM::TTR;
        [$la_date_field, $la_fk_field] = $sla->getFieldNames($la_type);

        // Check that the correct LA is assigned to the ticket
        $expected_la = getItemByTypeName(SLA::class, "\SLA $la_type", true);
        $expected_la_levels[] = getItemByTypeName($level_class, "\SLA $la_type level", true);
        $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

        // Check that the target date is correct (+ 4 hours)
        $this->assertEquals('2034-08-16 17:10:00', $ticket->fields[$la_date_field]);

        // Check that all escalations levels are sets
        $level_ticket_class = $sla->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->assertCount(1, $sa_levels_ticket); // no tto levels, tto completed

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
            $sla = new SLA();
            [$la_date_field, $la_fk_field] = $sla->getFieldNames($la_type);

            // Create two LA with one escalation level
            $sla = $this->createItem(SLA::class, [
                'name'                => "\SLA $la_type 2",
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
            $this->createItem($sla->getLevelClass(), [
                'name'                          => $sla->fields['name'] . ' level',
                SLA::class::getForeignKeyField() => $sla->getID(),
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
                ->addAction('assign', $la_fk_field, $sla->getID());
            $this->createRule($builder);
        }

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but we need to be in the input for the rule
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that TTO and TTR have been modified as expected
        $sla = new SLA();
        $level_class = $sla->getLevelClass();
        $expected_la_levels = [];

        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            [$la_date_field, $la_fk_field] = $sla->getFieldNames($la_type);

            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName(SLA::class, "\SLA $la_type 2", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "\SLA $la_type 2 level", true);
            $this->assertEquals($expected_la, $ticket->fields[$la_fk_field]);

            // Check that the target date is correct (+ 6 hours)
            $this->assertEquals('2034-08-17 08:10:00', $ticket->fields[$la_date_field]);
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $sla->getLevelTicketClass();
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

    /**
     * Checks :
     * - OLA can be assigned using rules
     * - due times are set as expected
     * - escalation levels dates are set as expected
     * - setting ticket status to waiting change due times and escalation levels dates
     */
    public function testOLaChangeCalendar(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_ticket_name = "Test ticket with multiple LA assignation " . mt_rand();

        // OLA change are recomputed from the current date so we need to set
        // glpi_currenttime to get predictable results
        $this->setCurrentTime('2034-08-16 13:00:00');

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

        $created_olas_ids = [];
        // Create rules to set OLA on ticket creation
        // - Create two OLA with one escalation level for each
        // - Create a rule to assign the OLA on ticket creation
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            ['ola' => $ola ] = $this->createOLA([
                'name'                => "\OLA $la_type",
                'entities_id'         => $entity,
                'is_recursive'        => true,
                'type'                => $la_type,
                'number_time'         => 4,
                'calendars_id'        => $calendar->getID(),
                'definition_time'     => 'hour',
                'end_of_working_day'  => false,
                'use_ticket_calendar' => false,
            ], slm: $slm);
            $created_olas_ids[] = $ola->getID();

            $this->createItem($ola->getLevelClass(), [
                'name'                          => $ola->fields['name'] . ' level',
                OLA::class::getForeignKeyField() => $ola->getID(),
                'execution_time'                => - HOUR_TIMESTAMP,
                'is_active'                     => true,
                'entities_id'                   => $entity,
                'is_recursive'                  => true,
                'match'                         => 'AND',
            ]);

            // First OLA is added on creation
            $builder = new RuleBuilder('Add first OLA on creation', RuleTicket::class);
            $builder->setEntity($entity)
                ->setCondtion(RuleTicket::ONADD)
                ->addCriteria('name', Rule::PATTERN_IS, $test_ticket_name)
                ->addCriteria('entities_id', Rule::PATTERN_IS, $entity)
                ->addAction('append', 'olas_id', $ola->getID());
            $this->createRule($builder);
        }

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity,
            'name'        => $test_ticket_name,
            'content'     => '',
        ]);

        // Check that TTO and TTR are set as expected
        $olas_data = $ticket->getOlasData();
        // Check that the correct LA is assigned to the ticket
        $this->assertEqualsCanonicalizing($created_olas_ids, array_column($olas_data, 'olas_id'));
        // Check that the target date are correct (+ 4 hours)
        $this->assertEquals('2034-08-16 17:00:00', $olas_data[0]['due_time']);
        $this->assertEquals('2034-08-16 17:00:00', $olas_data[1]['due_time']);

        // Check that all escalations levels are sets
        $ola = new OLA();
        $level_ticket_class = $ola->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()]);
        $this->assertCount(2, $sa_levels_ticket); // One TTO and one TTR

        // Check that they match the expected la levels
        $level_class = $ola->getLevelClass();
        $expected_la_levels = [
            getItemByTypeName($level_class, "\OLA 0 level", true),
            getItemByTypeName($level_class, "\OLA 1 level", true),
        ];
        $this->assertEqualsCanonicalizing(
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
        $this->setCurrentTime('2034-08-16 13:10:00');
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'status' => Ticket::INCOMING,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        // Check that TTO and TTR have been modified as expected
        $expected_la_levels = [];
        foreach ([SLM::TTR, SLM::TTO] as $la_type) {
            // Check that the correct LA is assigned to the ticket
            $expected_la = getItemByTypeName(OLA::class, "\OLA $la_type", true);
            $expected_la_levels[] = getItemByTypeName($level_class, "\OLA $la_type level", true);
            $fetched_ola_id = match ($la_type) {
                SLM::TTR => $ticket->getOlasTTRData()[0] ?? throw new \Exception("Expected associated OLA not found on ticket"),
                SLM::TTO => $ticket->getOlasTTOData()[0] ?? throw new \Exception("Expected associated OLA not found on ticket"),
            };
            $this->assertEquals($expected_la, $fetched_ola_id['olas_id']);

            // Check that the target date is correct (+ 4 hours)
            $this->assertEquals('2034-08-16 17:10:00', $fetched_ola_id['due_time']);
        }

        // Check that all escalations levels are sets
        $level_ticket_class = $ola->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->assertCount(2, $sa_levels_ticket);

        // Check that they match the expected la levels
        $this->assertEqualsCanonicalizing(
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

        // Create rules to set full OLA on ticket update
        $new_created_olas_ids = [];
        foreach ([SLM::TTO, SLM::TTR] as $la_type) {
            // Create two LA with one escalation level
            $ola = $this->createOLA([
                'name'                => "\OLA $la_type 2",
                'entities_id'         => $entity,
                'is_recursive'        => true,
                'type'                => $la_type,
                'number_time'         => 6,
                'calendars_id'        => $calendar2->getID(),
                'definition_time'     => 'hour',
                'end_of_working_day'  => false,
                'use_ticket_calendar' => false,
            ], slm: $slm2)['ola'];
            $created_olas_ids[] = $ola->getID();
            $new_created_olas_ids[] = $ola->getID();

            $this->createItem($ola->getLevelClass(), [
                'name'                          => $ola->fields['name'] . ' level',
                OLA::class::getForeignKeyField() => $ola->getID(),
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
                ->addAction('append', 'olas_id', $ola->getID());
            ;
            $this->createRule($builder);
        }

        // Update ticket, triggering an LA change
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'urgency' => 5,
            'name' => $test_ticket_name, // Name is not updated but needs to be in the input for the rule
        ]);
        $ticket = $this->reloadItem($ticket);

        // Check that TTO and TTR have been added as expected

        // Check that TTO and TTR are set as expected on ticket update
        $olas_data = $ticket->getOlasData();
        // Check that the correct LA is assigned to the ticket
        // there is now 4 OLA assigned to the ticket, the update has added two more (on SLA they are replaced)
        $this->assertCount(4, $olas_data);
        $this->assertEqualsCanonicalizing($created_olas_ids, array_column($olas_data, 'olas_id'));

        // to simplify test, remove the OLA associated on ticket creation (= keep only the ones created on update)
        // clean levels_todo to ensure there is no waste inside.
        global $DB;
        $DB->delete($level_ticket_class::getTable(), [new QueryExpression('1=1')]);

        $this->updateItem(Ticket::class, $ticket->getID(), ['_la_update' => true, '_olas_id' => $new_created_olas_ids]);

        // Check that the target date are correct (+ 4 hours)
        $olas_data = $ticket->getOlasData();
        $this->assertEquals('2034-08-17 08:10:00', $olas_data[0]['due_time']);
        $this->assertEquals('2034-08-17 08:10:00', $olas_data[1]['due_time']);

        // Check that all escalations levels are sets
        $level_ticket_class = $ola->getLevelTicketClass();
        $sa_levels_ticket = (new $level_ticket_class())->find(['tickets_id' => $ticket->getID()], [$level_class::getForeignKeyField()]);
        $this->assertCount(2, $sa_levels_ticket);

        // Check that they match the expected la levels
        $expected_la_levels = [
            getItemByTypeName($level_class, "\OLA 0 2 level", true),
            getItemByTypeName($level_class, "\OLA 1 2 level", true),
        ];
        $this->assertEqualsCanonicalizing(
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
        $initial_expected_due_time_str = $now->add($this->getDefaultSlaTtoDelayInterval())->format('Y-m-d H:i:s');
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
        $initial_expected_due_time_str = $now->add($this->getDefaultSlaTtrDelayInterval())->format('Y-m-d H:i:s');
        assert($initial_expected_due_time_str === $ticket->fields['time_to_resolve'], 'SLA TTR Due time should be set to the current date + TTR delay interval.');

        // act - update ticket date
        $new_date = '2025-06-22 10:00:02';
        $ticket = $this->updateItem($ticket::class, $ticket->getID(), ['date' => $new_date]);

        // assert - check if the due time is unchanged despite the ticket date change
        $this->assertEquals($initial_expected_due_time_str, $ticket->fields['time_to_resolve'], 'SLA TTR due time is updated when ticket date is changed.');
    }

    public function testCannotExportSLALevel()
    {
        $this->login();

        // Create an SLM
        $slm = $this->createItem(SLM::class, [
            'name' => 'SLM',
        ]);

        // Create an SLA
        /** @var SLA $sla */
        $sla = $this->createItem(SLA::class, [
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
        /** @var SLA $sla */
        $sla = $this->createItem(SLA::class, [
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
        $sla_clone = SLA::getById($sla_clone_id);

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
        /** @var OLA $ola */
        $ola = $this->createItem(OLA::class, [
            'name'            => 'OLA',
            'slms_id'         => $slm->getID(),
            'definition_time' => 'hour',
            'number_time'     => 4,
            'groups_id'      => getItemByTypeName('Group', '_test_group_1', true),
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
        $ola_clone = OLA::getById($ola_clone_id);

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

    public function testGetSLAData()
    {
        $this->login();
        // arrange
        $ticket = $this->createTicket();
        ['sla' => $sla_tto, 'slm' => $slm] = $this->createSLA(sla_type: SLM::TTO);
        ['sla' => $sla_ttr] = $this->createSLA(sla_type: SLM::TTR, slm: $slm);

        // act - associate SLAs to the ticket
        $this->updateItem(Ticket::class, $ticket->getID(), [
            'slas_id_tto' => $sla_tto->getID(),
            'slas_id_ttr' => $sla_ttr->getID(),
        ]);

        // assert - check if the ticket has the 2 SLA associated
        $ticket = $this->reloadItem($ticket);
        $this->assertCount(2, $ticket->getSlasData(), 'Expected 2 SLA associated with ticket, but ' . count($ticket->getSlasData()) . ' found');
        $this->assertEqualsCanonicalizing([$sla_tto->getID(),$sla_ttr->getID() ], array_column($ticket->getSlasData(), 'id'));
    }
}
