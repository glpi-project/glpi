<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * SLM Class
 **/
class SLM extends CommonDBTM
{
   // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['SLA', 'OLA'];

    public static $rightname                   = 'slm';

    const TTR = 0; // Time to resolve
    const TTO = 1; // Time to own

    public static function getTypeName($nb = 0)
    {
        return _n('Service level', 'Service levels', $nb);
    }

    /**
     * Force calendar of the SLM if value -1: calendar of the entity
     *
     * @param integer $calendars_id calendars_id of the ticket
     **/
    public function setTicketCalendar($calendars_id)
    {
        Toolbox::deprecated();

        if ($this->fields['use_ticket_calendar']) {
            $this->fields['calendars_id'] = $calendars_id;
        }
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab('SLA', $ong, $options);
        $this->addStandardTab('OLA', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function prepareInputForAdd($input)
    {
        $input = $this->handleCalendarStrategy($input);

        return parent::prepareInputForAdd($input);
    }


    public function prepareInputForUpdate($input)
    {
        $input = $this->handleCalendarStrategy($input);

        return parent::prepareInputForAdd($input);
    }

    /**
     * Handle negative input in `calendars_id`.
     * This method is usefull to be able to propose a `-1` special value in Calendar dropdown.
     *
     * @param array $input
     *
     * @return array
     */
    private function handleCalendarStrategy(array $input): array
    {
        if (array_key_exists('calendars_id', $input)) {
            if ($input['calendars_id'] == -1) {
                $input['calendars_id'] = 0;
                $input['use_ticket_calendar'] = 1;
            } else {
                $input['use_ticket_calendar'] = 0;
            }
        }

        return $input;
    }

    public function post_updateItem($history = 1)
    {
        global $DB;

        if (in_array('use_ticket_calendar', $this->updates) || in_array('calendars_id', $this->updates)) {
            // Propagate calendar settings to children
            foreach ([OLA::class, SLA::class] as $child_class) {
                $child_iterator = $DB->request(
                    [
                        'SELECT' => 'id',
                        'FROM'   => $child_class::getTable(),
                        'WHERE'  => [
                            $this->getForeignKeyField() => $this->getID()
                        ]
                    ]
                );
                foreach ($child_iterator as $child_data) {
                    $child = new $child_class();
                    $child->update(
                        [
                            'id'                  => $child_data['id'],
                            'use_ticket_calendar' => $this->fields['use_ticket_calendar'],
                            'calendars_id'        => $this->fields['calendars_id'],
                        ]
                    );
                }
            }
        }

        parent::post_updateItem($history);
    }

    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                SLA::class,
                OLA::class,
            ]
        );
    }

    /**
     * Print the slm form
     *
     * @param integer $ID ID of the item
     * @param array   $options of possible options:
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {

        $rowspan = 2;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "<td rowspan='" . $rowspan . "'>" . __('Comments') . "</td>";
        echo "<td rowspan='" . $rowspan . "'>
            <textarea class='form-control' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . _n('Calendar', 'Calendars', 1) . "</td>";
        echo "<td>";

        Calendar::dropdown([
            'value'      => $this->fields['use_ticket_calendar'] ? -1 : $this->fields['calendars_id'],
            'emptylabel' => __('24/7'),
            'toadd'      => ['-1' => __('Calendar of the ticket')]
        ]);
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_calendars',
            'field'              => 'name',
            'name'               => _n('Calendar', 'Calendars', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        return $tab;
    }


    public static function getMenuContent()
    {

        $menu = [];
        if (static::canView()) {
            $menu['title']           = self::getTypeName(2);
            $menu['page']            = static::getSearchURL(false);
            $menu['icon']            = static::getIcon();
            $menu['links']['search'] = static::getSearchURL(false);
            if (static::canCreate()) {
                $menu['links']['add'] = SLM::getFormURL(false);
            }

            $menu['options']['sla']['title']           = SLA::getTypeName(1);
            $menu['options']['sla']['page']            = SLA::getSearchURL(false);
            $menu['options']['sla']['links']['search'] = SLA::getSearchURL(false);

            $menu['options']['ola']['title']           = OLA::getTypeName(1);
            $menu['options']['ola']['page']            = OLA::getSearchURL(false);
            $menu['options']['ola']['links']['search'] = OLA::getSearchURL(false);

            $menu['options']['slalevel']['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
            $menu['options']['slalevel']['page']            = SlaLevel::getSearchURL(false);
            $menu['options']['slalevel']['links']['search'] = SlaLevel::getSearchURL(false);

            $menu['options']['olalevel']['title']           = OlaLevel::getTypeName(Session::getPluralNumber());
            $menu['options']['olalevel']['page']            = OlaLevel::getSearchURL(false);
            $menu['options']['olalevel']['links']['search'] = OlaLevel::getSearchURL(false);
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }


    public static function getIcon()
    {
        return "ti ti-checkup-list";
    }
}
