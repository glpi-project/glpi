<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/**
 * SlaLevel class
 **/
class SlaLevel extends LevelAgreementLevel
{
    protected $rules_id_field     = 'slalevels_id';
    protected $ruleactionclass    = 'SlaLevelAction';
    protected static $parentclass = 'SLA';
    protected static $fkparent    = 'slas_id';
    // No criteria
    protected $rulecriteriaclass = 'SlaLevelCriteria';

    public static function getTable($classname = null)
    {
        return CommonDBTM::getTable(self::class);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', SLA::class, self::class];
    }

    public function cleanDBonPurge()
    {
        parent::cleanDBonPurge();

        // SlaLevel_Ticket does not extends CommonDBConnexity
        $slt = new SlaLevel_Ticket();
        $slt->deleteByCriteria([$this->rules_id_field => $this->fields['id']]);
    }

    #[Override]
    public function showForParent(LevelAgreement $la)
    {
        $this->showForLA($la);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'clone';
        return $forbidden;
    }

    public function getActions()
    {
        $actions = parent::getActions();

        unset($actions['slas_id']);
        $actions['recall']['name']          = __('Automatic reminders of SLA');
        $actions['recall']['type']          = 'yesonly';
        $actions['recall']['force_actions'] = ['send'];

        return $actions;
    }

    /**
     * Get first level for a SLA
     *
     * @param integer $slas_id id of the SLA
     *
     * @since 9.1 (before getFirst SlaLevel)
     *
     * @return integer id of the sla level : 0 if not exists
     **/
    public static function getFirstSlaLevel($slas_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'slas_id'   => $slas_id,
                'is_active' => 1,
            ],
            'ORDER'  => 'execution_time ASC',
            'LIMIT'  => 1,
        ]);

        if ($result = $iterator->current()) {
            return $result['id'];
        }
        return 0;
    }

    /**
     * Get next level for a SLA
     *
     * @param integer $slas_id      id of the SLA
     * @param integer $slalevels_id id of the current SLA level
     *
     * @return integer id of the sla level : 0 if not exists
     **/
    public static function getNextSlaLevel($slas_id, $slalevels_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'execution_time',
            'FROM'   => self::getTable(),
            'WHERE'  => ['id' => $slalevels_id],
        ]);

        if ($result = $iterator->current()) {
            $execution_time = $result['execution_time'];

            $lvl_iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'slas_id'         => $slas_id,
                    'is_active'       => 1,
                    'id'              => ['<>', $slalevels_id],
                    'execution_time'  => ['>', $execution_time],
                ],
                'ORDER'  => 'execution_time ASC',
                'LIMIT'  => 1,
            ]);

            if ($result = $lvl_iterator->current()) {
                return $result['id'];
            }
        }
        return 0;
    }
}
