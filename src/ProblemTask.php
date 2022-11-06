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

class ProblemTask extends CommonITILTask
{
    public static $rightname = 'task';


    public static function getTypeName($nb = 0)
    {
        return _n('Problem task', 'Problem tasks', $nb);
    }


    public static function canCreate()
    {
        return Session::haveRight('problem', UPDATE)
          || Session::haveRight(self::$rightname, parent::ADDALLITEM);
    }


    public static function canView()
    {
        return Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);
    }


    public static function canUpdate()
    {
        return Session::haveRight('problem', UPDATE)
          || Session::haveRight(self::$rightname, parent::UPDATEALL);
    }


    public static function canPurge()
    {
        return Session::haveRight('problem', UPDATE);
    }


    public function canViewPrivates()
    {
        return true;
    }


    public function canEditAll()
    {
        return Session::haveRightsOr('problem', [CREATE, UPDATE, DELETE, PURGE]);
    }


    /**
     * Does current user have right to show the current task?
     *
     * @return boolean
     **/
    public function canViewItem()
    {
        return $this->canReadITILItem();
    }


    /**
     * Does current user have right to create the current task?
     *
     * @return boolean
     **/
    public function canCreateItem()
    {
        if (!$this->canReadITILItem()) {
            return false;
        }

        $problem = new Problem();
        if ($problem->getFromDB($this->fields['problems_id'])) {
            return (Session::haveRight(self::$rightname, parent::ADDALLITEM)
                 || Session::haveRight('problem', UPDATE)
                 || (Session::haveRight('problem', Problem::READMY)
                     && ($problem->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                         || (isset($_SESSION["glpigroups"])
                             && $problem->haveAGroup(
                                 CommonITILActor::ASSIGN,
                                 $_SESSION['glpigroups']
                             )))));
        }
        return false;
    }


    /**
     * Does current user have right to update the current task?
     *
     * @return boolean
     **/
    public function canUpdateItem()
    {

        if (!$this->canReadITILItem()) {
            return false;
        }

        if (
            ($this->fields["users_id"] != Session::getLoginUserID())
            && !Session::haveRight('problem', UPDATE)
            && !Session::haveRight(self::$rightname, parent::UPDATEALL)
        ) {
            return false;
        }

        return true;
    }


    /**
     * Does current user have right to purge the current task?
     *
     * @return boolean
     **/
    public function canPurgeItem()
    {
        return $this->canUpdateItem();
    }


    /**
     * Populate the planning with planned problem tasks
     *
     * @param $options  array of possible options:
     *    - who         ID of the user (0 = undefined)
     *    - whogroup    ID of the group of users (0 = undefined)
     *    - begin Date
     *    - end Date
     *
     * @return array of planning item
     **/
    public static function populatePlanning($options = []): array
    {
        return parent::genericPopulatePlanning(__CLASS__, $options);
    }


    /**
     * Display a Planning Item
     *
     * @param array           $val       array of the item to display
     * @param integer         $who       ID of the user (0 if all)
     * @param string          $type      position of the item in the time block (in, through, begin or end)
     * @param integer|boolean $complete  complete display (more details)
     *
     * @return string
     */
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        return parent::genericDisplayPlanningItem(__CLASS__, $val, $who, $type, $complete);
    }

    /**
     * Populate the planning with not planned problem tasks
     *
     * @param $options  array of possible options:
     *    - who         ID of the user (0 = undefined)
     *    - whogroup    ID of the group of users (0 = undefined)
     *    - begin Date
     *    - end Date
     *
     * @return array of planning item
     **/
    public static function populateNotPlanned($options = []): array
    {
        return parent::genericPopulateNotPlanned(__CLASS__, $options);
    }
}
