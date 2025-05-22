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

/**
 * SLM Class
 * @since 9.2
 **/
class SLM extends CommonDBTM
{
    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['SLA', 'OLA'];

    public static $rightname                   = 'slm';

    public const TTR = 0; // Time to resolve
    public const TTO = 1; // Time to own

    public const RIGHT_ASSIGN = 256;

    public static function getTypeName($nb = 0)
    {
        return _n('Service level', 'Service levels', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(SLA::class, $ong, $options);
        $this->addStandardTab(OLA::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

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
            if ((int) $input['calendars_id'] === -1) {
                $input['calendars_id'] = 0;
                $input['use_ticket_calendar'] = 1;
            } else {
                $input['use_ticket_calendar'] = 0;
            }
        }

        return $input;
    }

    public function post_updateItem($history = true)
    {
        global $DB;

        if (in_array('use_ticket_calendar', $this->updates, true) || in_array('calendars_id', $this->updates, true)) {
            // Propagate calendar settings to children
            foreach ([OLA::class, SLA::class] as $child_class) {
                $child_iterator = $DB->request(
                    [
                        'SELECT' => 'id',
                        'FROM'   => $child_class::getTable(),
                        'WHERE'  => [
                            static::getForeignKeyField() => $this->getID(),
                        ],
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
     * Print the SLM form
     * {@inheritdoc}
     **/
    public function showForm($ID, array $options = [])
    {
        $twig_params = [
            'item'        => $this,
            'params'      => $options,
            'empty_label' => __('24/7'),
            'toadd_label' => __('Calendar of the ticket'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% extends 'generic_show_form.html.twig' %}
            {% import 'components/form/fields_macros.html.twig' as fields %}
            
            {% block more_fields %}
                {{ fields.dropdownField('Calendar', 'calendars_id', item.fields['use_ticket_calendar'] ? -1 : item.fields['calendars_id'], 'Calendar'|itemtype_name(1), {
                    emptylabel: empty_label,
                    toadd: {
                        (-1): toadd_label
                    }
                }) }}
            {% endblock %}
TWIG, $twig_params);
        return true;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_calendars',
            'field'              => 'name',
            'name'               => _n('Calendar', 'Calendars', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
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

            $menu['options'][SLA::class]['title']           = SLA::getTypeName(1);
            $menu['options'][SLA::class]['page']            = SLA::getSearchURL(false);
            $menu['options'][SLA::class]['links']['search'] = SLA::getSearchURL(false);

            $menu['options'][OLA::class]['title']           = OLA::getTypeName(1);
            $menu['options'][OLA::class]['page']            = OLA::getSearchURL(false);
            $menu['options'][OLA::class]['links']['search'] = OLA::getSearchURL(false);

            $menu['options'][SlaLevel::class]['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
            $menu['options'][SlaLevel::class]['page']            = SlaLevel::getSearchURL(false);
            $menu['options'][SlaLevel::class]['links']['search'] = SlaLevel::getSearchURL(false);

            $menu['options'][OlaLevel::class]['title']           = OlaLevel::getTypeName(Session::getPluralNumber());
            $menu['options'][OlaLevel::class]['page']            = OlaLevel::getSearchURL(false);
            $menu['options'][OlaLevel::class]['links']['search'] = OlaLevel::getSearchURL(false);
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

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();
        $values[self::RIGHT_ASSIGN]  = [
            'short' => __('Assign'),
            'long'  => __('Search result user display'),
        ];

        return $values;
    }
}
