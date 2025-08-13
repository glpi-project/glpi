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
 * @since 9.2
 */


/**
 * OlaLevel class
 **/
class OlaLevel extends LevelAgreementLevel
{
    protected $rules_id_field     = 'olalevels_id';
    protected $ruleactionclass    = 'OlaLevelAction';
    protected static $parentclass = 'OLA';
    protected static $fkparent    = 'olas_id';
    // No criteria
    protected $rulecriteriaclass = 'OlaLevelCriteria';


    public static function getTable($classname = null)
    {
        return CommonDBTM::getTable(self::class);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', OLA::class, self::class];
    }

    public function cleanDBonPurge()
    {
        parent::cleanDBonPurge();

        // OlaLevel_Ticket does not extends CommonDBConnexity
        $olt = new OlaLevel_Ticket();
        $olt->deleteByCriteria([$this->rules_id_field => $this->fields['id']]);
    }

    #[Override]
    public function showForParent(LevelAgreement $la)
    {
        $this->showForLA($la);
    }

    public function getActions()
    {
        $actions = parent::getActions();

        unset($actions['olas_id']);
        $actions['recall_ola']['name']          = __('Automatic reminders of OLA');
        $actions['recall_ola']['type']          = 'yesonly';
        $actions['recall_ola']['force_actions'] = ['send'];

        return $actions;
    }

    /**
     * Get first level for a OLA
     *
     * @param integer $olas_id id of the OLA
     *
     * @since 9.1 (before getFirst OlaLevel)
     *
     * @return integer id of the ola level : 0 if not exists
     **/
    public static function getFirstOlaLevel($olas_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_olalevels',
            'WHERE'  => [
                'olas_id'   => $olas_id,
                'is_active' => 1,
            ],
            'ORDER'  => 'execution_time ASC',
            'LIMIT'  => 1,
        ]);

        if (count($iterator)) {
            $result = $iterator->current();
            return $result['id'];
        }
        return 0;
    }

    /**
     * Get next level for a OLA
     *
     * @param integer $olas_id      id of the OLA
     * @param integer $olalevels_id id of the current OLA level
     *
     * @return integer id of the ola level : 0 if not exists
     **/
    public static function getNextOlaLevel($olas_id, $olalevels_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'execution_time',
            'FROM'   => 'glpi_olalevels',
            'WHERE'  => ['id' => $olalevels_id],
        ]);

        if (count($iterator)) {
            $result = $iterator->current();
            $execution_time = $result['execution_time'];

            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => 'glpi_olalevels',
                'WHERE'  => [
                    'olas_id'         => $olas_id,
                    'id'              => ['<>', $olalevels_id],
                    'execution_time'  => ['>', $execution_time],
                    'is_active'       => 1,
                ],
                'ORDER'  => 'execution_time ASC',
                'LIMIT'  => 1,
            ]);

            if (count($iterator)) {
                $result = $iterator->current();
                return $result['id'];
            }
        }
        return 0;
    }
}
