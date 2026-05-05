<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

//!  ProjectTaskTeam Class
/**
 * This class is used to manage the project task team
 * @see ProjectTask
 * @author Julien Dombre
 * @since 0.85
 **/
class ProjectTaskTeam extends CommonDBRelation
{
    // From CommonDBTM
    public bool $dohistory                  = true;
    public bool $no_form_page               = true;

    // From CommonDBRelation
    public static ?string $itemtype_1 = ProjectTask::class;
    public static ?string $items_id_1          = 'projecttasks_id';

    public static ?string $itemtype_2          = 'itemtype';
    public static ?string $items_id_2          = 'items_id';
    public static int $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    /** @var class-string<CommonDBTM>[] */
    public static array $available_types     = ['User', 'Group', 'Supplier', 'Contact'];


    /**
     * @see CommonDBTM::getNameField()
     **/
    public static function getNameField()
    {
        return 'id';
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Task team', 'Task teams', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-users';
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case 'affect_to_team':
            case 'unaffect_to_team':
                $rand = Dropdown::showItemTypes('itemtype', static::$available_types);
                echo '<br>';
                $params = [
                    'idtable'             => '__VALUE__',
                    'display_emptychoice' => true,
                    'name'                => 'items_id',
                    'entity_restrict'     => Session::getActiveEntity(),
                    'rand'                => $rand,
                ];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_itemtype$rand",
                    "results_itemtype$rand",
                    $CFG_GLPI['root_doc'] . '/ajax/dropdownAllItems.php',
                    $params
                );
                echo "<span id='results_itemtype$rand'></span>";
                echo '<br>';

                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param int[] $ids
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ): void {
        $action = $ma->getAction();
        $input  = $ma->getInput();

        if (!in_array($action, ['affect_to_team', 'unaffect_to_team'], true)) {
            parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
            return;
        }

        if (
            empty($input['itemtype'])
            || !isset($input['items_id'])
            || (int) $input['items_id'] <= 0
        ) {
            foreach ($ids as $id) {
                $ma->itemDone($item::class, $id, MassiveAction::NO_ACTION);
            }
            return;
        }

        $team = new self();

        foreach ($ids as $id) {
            if (!$item->can($id, UPDATE)) {
                $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                continue;
            }

            $criteria = [
                'projecttasks_id' => $id,
                'itemtype'        => $input['itemtype'],
                'items_id'        => (int) $input['items_id'],
            ];

            if ($action === 'affect_to_team') {
                if (countElementsInTable(self::getTable(), $criteria) > 0) {
                    $ma->itemDone($item::class, $id, MassiveAction::NO_ACTION);
                    continue;
                }
                $result = $team->add($criteria);
            } else {
                if (countElementsInTable(self::getTable(), $criteria) === 0) {
                    $ma->itemDone($item::class, $id, MassiveAction::NO_ACTION);
                    continue;
                }
                $result = $team->deleteByCriteria($criteria);
            }

            if ($result) {
                $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
            } else {
                $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
            }
        }
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && static::canView()) {
            $nb = 0;
            switch (get_class($item)) {
                case ProjectTask::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = $item->getTeamCount();
                    }
                    return self::createTabEntry(self::getTypeName(1), $nb, $item::class);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case ProjectTask::class:
                $item->showTeam($item);
                break;
        }

        return true;
    }

    public function post_addItem()
    {
        if (!isset($this->input['_disablenotif'])) {
            // Read again to be sure that the data is up to date
            $this->getFromDB($this->fields['id']);
            // Get linked task
            $task = new ProjectTask();
            $task->getFromDB($this->fields['projecttasks_id']);
            // Raise update event on task
            NotificationEvent::raiseEvent("update", $task);
        }
    }


    /**
     * Get team for a project task
     *
     * @param int $tasks_id
     * @param bool $expand If true, the team member data is expanded to include specific properties like firstname, realname, ...
     * @return array<class-string<CommonDBTM>, array<array{id: int, projecttasks_id: int, itemtype: class-string<CommonDBTM>, items_id: int, display_name?: string}>>
     **/
    public static function getTeamFor($tasks_id, bool $expand = false)
    {
        global $DB;

        $team = [];
        // Define empty types
        foreach (static::$available_types as $type) {
            if (!isset($team[$type])) {
                $team[$type] = [];
            }
        }

        $criteria = [
            'FROM'   => self::getTable(),
            'WHERE'  => ['projecttasks_id' => $tasks_id],
        ];
        $iterator = $DB->request($criteria);

        foreach ($iterator as $data) {
            $data['role'] = Team::ROLE_MEMBER;
            $team[$data['itemtype']][] = $data;
        }

        if ($expand) {
            // This call is purposefully going to ProjectTeam because the code would be the same for both it and this (ProjectTaskTeam)
            $team = ProjectTeam::expandTeamData($team);
        }

        return $team;
    }

    public function prepareInputForAdd($input)
    {
        global $DB;

        if (!isset($input['itemtype'])) {
            Session::addMessageAfterRedirect(
                __s('An item type is mandatory'),
                false,
                ERROR
            );
            return false;
        }

        if (!isset($input['items_id'])) {
            Session::addMessageAfterRedirect(
                __s('An item ID is mandatory'),
                false,
                ERROR
            );
            return false;
        }

        if (!isset($input['projecttasks_id'])) {
            Session::addMessageAfterRedirect(
                __s('A project task is mandatory'),
                false,
                ERROR
            );
            return false;
        }

        $task = new ProjectTask();
        $task->getFromDB($input['projecttasks_id']);
        switch ($input['itemtype']) {
            case User::class:
                Planning::checkAlreadyPlanned(
                    $input['items_id'],
                    $task->fields['plan_start_date'],
                    $task->fields['plan_end_date']
                );
                break;
            case Group::class:
                $group_iterator = $DB->request([
                    'SELECT' => 'users_id',
                    'FROM'   => Group_User::getTable(),
                    'WHERE'  => ['groups_id' => $input['items_id']],
                ]);
                foreach ($group_iterator as $row) {
                    Planning::checkAlreadyPlanned(
                        $row['users_id'],
                        $task->fields['plan_start_date'],
                        $task->fields['plan_end_date']
                    );
                }
                break;
            case Supplier::class:
            case Contact::class:
                //only Users can be checked for planning conflicts
                break;
            default:
                throw new RuntimeException($input['itemtype'] . " is not (yet?) handled.");
        }

        return $input;
    }
}
