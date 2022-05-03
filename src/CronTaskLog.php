<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * CronTaskLog class
 **/
class CronTaskLog extends CommonDBTM
{
   // Class constant
    const STATE_START = 0;
    const STATE_RUN   = 1;
    const STATE_STOP  = 2;
    const STATE_ERROR = 3;


    /**
     * Clean old event for a task
     *
     * @param $id     integer  ID of the CronTask
     * @param $days   integer  number of day to keep
     *
     * @return integer number of events deleted
     **/
    public static function cleanOld($id, $days)
    {
        global $DB;

        $secs      = $days * DAY_TIMESTAMP;

        $result = $DB->delete(
            'glpi_crontasklogs',
            [
                'crontasks_id' => $id,
                new \QueryExpression("UNIX_TIMESTAMP(" . $DB->quoteName("date") . ") < UNIX_TIMESTAMP()-$secs")
            ]
        );

        return $result ? $DB->affectedRows() : 0;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'CronTask':
                    $ong    = [];
                    $ong[1] = __('Statistics');
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  countElementsInTable(
                            $this->getTable(),
                            ['crontasks_id' => $item->getID(),
                                'state'        => self::STATE_STOP
                            ]
                        );
                    }
                    $ong[2] = self::createTabEntry(_n('Log', 'Logs', Session::getPluralNumber()), $nb);
                    return $ong;
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'CronTask') {
            switch ($tabnum) {
                case 1:
                    $item->showStatistics();
                    break;

                case 2:
                    $item->showHistory();
                    break;
            }
        }
        return true;
    }
}
