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

use Glpi\Team\Team;

//!  ProjectTeam Class
/**
 * This class is used to manage the project team
 * @see Project
 * @author Julien Dombre
 * @since 0.85
 **/
class ProjectTeam extends CommonDBRelation
{
   // From CommonDBTM
    public $dohistory                  = true;
    public $no_form_page               = true;

   // From CommonDBRelation
    public static $itemtype_1          = 'Project';
    public static $items_id_1          = 'projects_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static $available_types     = ['User', 'Group', 'Supplier', 'Contact'];


    /**
     * @see CommonDBTM::getNameField()
     **/
    public static function getNameField()
    {
        return 'id';
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Project team', 'Project teams', $nb);
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (self::canView()) {
            $nb = 0;
            switch (get_class($item)) {
                case Project::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = $item->getTeamCount();
                    }
                    return self::createTabEntry(self::getTypeName(1), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case Project::class:
                $item->showTeam($item);
        }
        return true;
    }

    /**
     * Add additional data about the individual members to an array of team members for a Project or ProjectTask.
     *
     * The additional information includes data in the specific itemtype's table rather than the ProjectTeam or ProjectTaskTeam tables.
     * @param array $team Team members. The keys should correspond to the Itemtype and each sub-array should have at least the 'id' property.
     * @return array The array of team members with additional information
     * @since 10.0.0
     */
    public static function expandTeamData(array $team)
    {
        /** @var \DBmysql $DB */
        global $DB;
        $subqueries = [];

        if (count($team['User'])) {
            $user_ids = array_column($team['User'], 'items_id');
            $subqueries[] = new QuerySubQuery([
                'SELECT' => ['id', 'name', 'realname', 'firstname',
                    new QueryExpression('"User" AS itemtype')
                ],
                'FROM' => 'glpi_users',
                'WHERE' => [
                    'id'           => $user_ids
                ]
            ]);
        }
        if (count($team['Group'])) {
            $group_ids = array_column($team['Group'], 'items_id');
            $subqueries[] = new QuerySubQuery([
                'SELECT' => [
                    'id',
                    'name',
                    new QueryExpression('NULL AS realname'),
                    new QueryExpression('NULL AS firstname'),
                    new QueryExpression('"Group" AS itemtype')
                ],
                'FROM' => 'glpi_groups',
                'WHERE' => [
                    'id'           => $group_ids
                ]
            ]);
        }
        if (count($team['Supplier'])) {
            $supplier_ids = array_column($team['Supplier'], 'items_id');
            $subqueries[] = new QuerySubQuery([
                'SELECT' => [
                    'id',
                    'name',
                    new QueryExpression('NULL AS realname'),
                    new QueryExpression('NULL AS firstname'),
                    new QueryExpression('"Supplier" AS itemtype')
                ],
                'FROM' => 'glpi_suppliers',
                'WHERE' => [
                    'id' => $supplier_ids
                ]
            ]);
        }
        if (count($team['Contact'])) {
            $contact_ids = array_column($team['Contact'], 'items_id');
            $subqueries[] = new QuerySubQuery([
                'SELECT' => [
                    'id',
                    'name',
                    new QueryExpression('NULL AS realname'),
                    new QueryExpression('NULL AS firstname'),
                    new QueryExpression('"Contact" AS itemtype')
                ],
                'FROM' => 'glpi_contacts',
                'WHERE' => [
                    'id' => $contact_ids
                ]
            ]);
        }

        if (count($subqueries)) {
            $union = new QueryUnion($subqueries);
            $criteria = [
                'SELECT' => ['id', 'name', 'realname', 'firstname', 'itemtype'],
                'FROM' => $union,
            ];
            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                foreach ($team[$data['itemtype']] as &$member) {
                    if ($member['items_id'] === $data['id']) {
                        $member['display_name'] = formatUserName($data['id'], $data['name'], $data['realname'], $data['firstname']);
                        unset($data['id']);
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $member = array_merge($member, $data);
                        break;
                    }
                }
            }
        }

        return $team;
    }

    /**
     * Get team for a project
     *
     * @param $projects_id
     * @param bool $expand If true, the team member data is expanded to include specific properties like firstname, realname, ...
     * @return array
     */
    public static function getTeamFor($projects_id, bool $expand = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $team = [];

       // Define empty types
        foreach (static::$available_types as $type) {
            if (!isset($team[$type])) {
                $team[$type] = [];
            }
        }

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['projects_id' => $projects_id]
        ]);

        foreach ($iterator as $data) {
            $data['role'] = Team::ROLE_MEMBER;
            if (!isset($team[$data['itemtype']])) {
                $team[$data['itemtype']] = [];
            }
            $team[$data['itemtype']][] = $data;
        }

        if ($expand) {
            $team = self::expandTeamData($team);
        }

        return $team;
    }
}
