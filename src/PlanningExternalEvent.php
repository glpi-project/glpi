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
use Glpi\Application\View\TemplateRenderer;
use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Traits\VobjectConverterTrait;
use Glpi\Features\PlanningEvent;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTodo;

class PlanningExternalEvent extends CommonDBTM implements CalDAVCompatibleItemInterface
{
    use PlanningEvent {
        rawSearchOptions as protected trait_rawSearchOptions;
    }
    use VobjectConverterTrait;

    public $dohistory = true;
    public static $rightname = 'externalevent';

    public const MANAGE_BG_EVENTS =   1024;

    public static function getTypeName($nb = 0)
    {
        return _n('External event', 'External events', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', Planning::class, self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public static function canUpdate(): bool
    {
        // we permits globally to update this object,
        // as users can update their onw items
        return Session::haveRightsOr(self::$rightname, [
            CREATE,
            UPDATE,
            self::MANAGE_BG_EVENTS,
        ]);
    }

    public function canUpdateItem(): bool
    {
        if (!$this->canUpdateBGEvents()) {
            return false;
        }

        // the current user can update only this own events without UPDATE right
        // but not bg one, see above
        if (
            (int) $this->fields['users_id'] !== Session::getLoginUserID()
            && !Session::haveRight(self::$rightname, UPDATE)
        ) {
            return false;
        }

        return parent::canUpdateItem();
    }

    public function canPurgeItem(): bool
    {
        if (!$this->canUpdateBGEvents()) {
            return false;
        }

        // the current user can update only this own events without PURGE right
        // but not bg one, see above
        if (
            (int) $this->fields['users_id'] !== Session::getLoginUserID()
            && !Session::haveRight(self::$rightname, PURGE)
        ) {
            return false;
        }

        return parent::canPurgeItem();
    }

    /**
     * do we have the right to manage background events
     *
     * @return bool
     */
    public function canUpdateBGEvents()
    {
        return !($this->fields["background"]
            && !Session::haveRight(self::$rightname, self::MANAGE_BG_EVENTS));
    }

    public function post_getFromDB()
    {
        $this->fields['users_id_guests'] = importArrayFromDB($this->fields['users_id_guests']);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $options['canedit'] = $this->can($ID, UPDATE);
        $rand       = mt_rand();
        $rand_plan  = mt_rand();
        $rand_rrule = mt_rand();

        if (
            ($options['from_planning_ajax'] ?? false)
            || ($options['from_planning_edit_ajax'] ?? false)
        ) {
            $options['no_header'] = true;
            $options['in_modal']  = true;
        } else {
            $options['in_modal']  = false;
        }

        $is_ajax  = isset($options['from_planning_edit_ajax']) && $options['from_planning_edit_ajax'];
        $is_rrule = ($this->fields['rrule'] ?? '') !== '';

        // set event for another user
        if (isset($options['res_itemtype'], $options['res_items_id']) && strtolower($options['res_itemtype']) === "user") {
            $this->fields['users_id'] =  $options['res_items_id'];
        }

        if ($is_ajax && $is_rrule) {
            $options['candel'] = false;
            $options['addbuttons'] = [];
            if ($this->can(-1, CREATE)) {
                $options['addbuttons']['save_instance'] = [
                    'text'  => __("Detach instance"),
                    'icon'  => 'ti ti-unlink',
                    'title' => __("Detach this instance from the series to create an independent event"),
                ];
            }
            if ($this->can($ID, PURGE)) {
                $options['addbuttons']['purge'] = [
                    'text' => __("Delete serie"),
                    'icon' => 'ti ti-trash',
                ];
                $options['addbuttons']['purge_instance'] = [
                    'text' => __("Delete instance"),
                    'icon' => 'ti ti-trash',
                ];
            }
        }

        TemplateRenderer::getInstance()->display('pages/assistance/planning/external_event.html.twig', [
            'item' => $this,
            'rand' => $rand,
            'rand_plan' => $rand_plan,
            'rand_rrule' => $rand_rrule,
            'params' => $options,
        ]);
        return true;
    }

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();

        $values[self::MANAGE_BG_EVENTS] = __('manage background events');

        return $values;
    }

    public static function getGroupItemsAsVCalendars($groups_id)
    {
        return self::getItemsAsVCalendars([self::getTableField('groups_id') => $groups_id]);
    }

    public static function getUserItemsAsVCalendars($users_id)
    {
        return self::getItemsAsVCalendars([
            'OR' => [
                self::getTableField('users_id')        => $users_id,
                self::getTableField('users_id_guests') => ['LIKE', '%"' . $users_id . '"%'],
            ],
        ]);
    }

    /**
     * Returns items as VCalendar objects.
     *
     * @param array $criteria
     *
     * @return VCalendar[]
     */
    private static function getItemsAsVCalendars(array $criteria)
    {
        global $DB;

        $query = [
            'FROM'  => self::getTable(),
            'WHERE' => $criteria,
        ];

        $event_iterator = $DB->request($query);

        $vcalendars = [];
        foreach ($event_iterator as $event) {
            $item = new self();
            $item->getFromResultSet($event);
            $vcalendar = $item->getAsVCalendar();
            if (null !== $vcalendar) {
                $vcalendars[] = $vcalendar;
            }
        }

        return $vcalendars;
    }

    public function getAsVCalendar()
    {
        if (!$this->canViewItem()) {
            return null;
        }

        $is_task = in_array($this->fields['state'], [Planning::DONE, Planning::TODO]);
        $is_planned = !empty($this->fields['begin']) && !empty($this->fields['end']);
        $target_component = $this->getTargetCaldavComponent($is_planned, $is_task);
        if (null === $target_component) {
            return null;
        }

        $vcalendar = $this->getVCalendarForItem($this, $target_component);

        return $vcalendar;
    }

    public function getInputFromVCalendar(VCalendar $vcalendar)
    {
        $vcomp = $vcalendar->getBaseComponent();

        $input = $this->getCommonInputFromVcomponent($vcomp, $this->isNewItem());

        $input['text'] = $input['content'];
        unset($input['content']);

        if ($vcomp instanceof VTodo && !array_key_exists('state', $input)) {
            // Force default state to TO DO or event will be considered as VEVENT
            $input['state'] = Planning::TODO;
        }

        return $input;
    }

    public function rawSearchOptions()
    {
        return $this->trait_rawSearchOptions();
    }

    public static function getVisibilityCriteria(): array
    {
        if (Session::haveRight(Planning::$rightname, Planning::READALL)) {
            return [];
        }

        $condition = [
            'OR' => [
                self::getTableField('users_id') => $_SESSION['glpiID'],
                self::getTableField('users_id_guests') => ['LIKE', '%"' . $_SESSION['glpiID'] . '"%'],
            ],
        ];

        if (Session::haveRight(Planning::$rightname, Planning::READGROUP)) {
            $groups = $_SESSION['glpigroups'];
            if (count($groups)) {
                $users = Group_User::getGroupUsers($groups);
                $users_id = [];
                foreach ($users as $data) {
                    $users_id[] = $data['id'];
                }
                $condition['OR'][self::getTableField('users_id')] = $users_id;
            }
        }

        return $condition;
    }
}
