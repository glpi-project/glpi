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
use Glpi\Features\StateInterface;

/**
 * SoftwareVersion Class
 **/
class SoftwareVersion extends CommonDBChild implements StateInterface
{
    use Glpi\Features\State;

    // From CommonDBTM
    public $dohistory = true;

    // From CommonDBChild
    public static $itemtype  = 'Software';
    public static $items_id  = 'softwares_id';

    protected $displaylist = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Version', 'Versions', $nb);
    }

    public static function getIcon()
    {
        return Software::getIcon();
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_SoftwareVersion::class,
            ]
        );
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Item_SoftwareVersion::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function getPreAdditionalInfosForName()
    {
        $soft = new Software();
        if ($soft->getFromDB($this->fields['softwares_id'])) {
            return $soft->getName();
        }
        return '';
    }

    /**
     * Print the Software / version form
     *
     * @param integer $ID Id of the version or the template to print
     * @param array $options of possible options:
     *     - target form target
     *     - softwares_id ID of the software for add process
     *
     * @return bool true if displayed  false if item not found or not right to display
     *
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
            $softwares_id = $this->fields['softwares_id'];
        } else {
            $softwares_id = $options['softwares_id'];
            $this->check(-1, CREATE, $options);
        }

        // Only count softwareversions_id_buy (don't care of softwareversions_id_use if no installation)
        if (
            (SoftwareLicense::countForVersion($ID) > 0)
            || (Item_SoftwareVersion::countForVersion($ID) > 0)
        ) {
            $options['candel'] = false;
        }

        $twig_params = [
            'item' => $this,
            'softwares_id' => $softwares_id,
            'params' => $options,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% extends 'generic_show_form.html.twig' %}
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {% block form_fields %}
                {% if item.isNewItem() %}
                    <input type="hidden" name="softwares_id" value="{{ softwares_id }}">
                {% endif %}
                {{ fields.htmlField('', get_item_link('Software', softwares_id), 'Software'|itemtype_name()) }}
                {{ parent() }}
                {{ fields.dropdownField('OperatingSystem', 'operatingsystems_id', item.fields['operatingsystems_id'], 'OperatingSystem'|itemtype_name()) }}
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
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => OperatingSystem::getTable(),
            'field'              => 'name',
            'name'               => OperatingSystem::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    /**
     * Make a select box for  software to install
     *
     * @param array $options Array of possible options:
     *    - name          : string / name of the select (default is softwareversions_id)
     *    - softwares_id  : integer / ID of the software (mandatory)
     *    - value         : integer / value of the selected version
     *    - used          : array / already used items
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function dropdownForOneSoftware($options = [])
    {
        global $DB;

        $p['softwares_id']          = 0;
        $p['value']                 = 0;
        $p['name']                  = 'softwareversions_id';
        $p['used']                  = [];
        $p['display_emptychoice']   = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        // Make a select box
        $criteria = [
            'SELECT'    => [
                'glpi_softwareversions.*',
                'glpi_states.name AS sname',
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_softwareversions',
            'LEFT JOIN' => [
                State::getTable()  => [
                    'ON' => [
                        'glpi_softwareversions' => 'states_id',
                        State::getTable()           => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_softwareversions.softwares_id'   => $p['softwares_id'],
            ],
            'ORDERBY'   => 'name',
        ];

        if (count($p['used'])) {
            $criteria['WHERE']['NOT'] = ['glpi_softwareversions.id' => $p['used']];
        }

        $iterator = $DB->request($criteria);

        $values = [];
        foreach ($iterator as $data) {
            $ID     = $data['id'];
            $output = $data['name'];

            if (empty($output) || $_SESSION['glpiis_ids_visible']) {
                $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
            }
            if (!empty($data['sname'])) {
                $output = sprintf(__('%1$s - %2$s'), $output, $data['sname']);
            }
            $values[$ID] = $output;
        }
        return Dropdown::showFromArray($p['name'], $values, $p);
    }

    /**
     * Show Versions of a software
     *
     * @param Software $soft Software object
     *
     * @return void
     **/
    public static function showForSoftware(Software $soft)
    {
        global $DB;

        $softwares_id = $soft->getID();

        if (!$soft->can($softwares_id, READ)) {
            return;
        }
        $canedit = $soft->canEdit($softwares_id);

        if ($canedit) {
            $twig_params = [
                'btn_msg' => _x('button', 'Add a version'),
                'softwares_id' => $softwares_id,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center mb-3">
                    <a class="btn btn-primary" href="{{ 'SoftwareVersion'|itemtype_form_path }}?softwares_id={{ softwares_id }}">{{ btn_msg }}</a>
                </div>
TWIG, $twig_params);
        }

        $sv_table = self::getTable();
        $state_table = State::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                "$sv_table.*",
                "$state_table.name AS sname",
            ],
            'FROM' => $sv_table,
            'LEFT JOIN' => [
                $state_table  => [
                    'ON' => [
                        $sv_table => 'states_id',
                        $state_table => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'softwares_id' => $softwares_id,
            ],
            'ORDERBY'   => 'name',
        ]);

        $tot = 0;
        $entries = [];
        $sv = new self();
        foreach ($iterator as $data) {
            $sv->getFromResultSet($data);
            $nb = Item_SoftwareVersion::countForVersion($data['id']);

            $tot += $nb;
            $entries[] = [
                'itemtype' => self::class,
                'id' => $sv->getID(),
                'version' => $sv->getLink(),
                'status' => $data['sname'],
                'os' => Dropdown::getDropdownName('glpi_operatingsystems', $data['operatingsystems_id']),
                'arch' => $data['arch'],
                'installations' => $nb,
                'comments' => nl2br(htmlescape($data['comment'])),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'version' => self::getTypeName(Session::getPluralNumber()),
                'status' => __('Status'),
                'os' => OperatingSystem::getTypeName(1),
                'arch' => _n('Architecture', 'Architectures', 1),
                'installations' => _n('Installation', 'Installations', Session::getPluralNumber()),
                'comments' => _n('Comment', 'Comments', Session::getPluralNumber()),
            ],
            'formatters' => [
                'version' => 'raw_html',
                'comments' => 'raw_html',
            ],
            'footers' => [
                ['', '', '', __('Total'), $tot, ''],
            ],
            'footer_class' => 'fw-bold',
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case Software::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(static::getTable(), ['softwares_id' => $item->getID()]);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === Software::class) {
            self::showForSoftware($item);
        }
        return true;
    }
}
