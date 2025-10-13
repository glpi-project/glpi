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

class NetworkPort_Vlan extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1          = 'NetworkPort';
    public static $items_id_1          = 'networkports_id';

    public static $itemtype_2          = 'Vlan';
    public static $items_id_2          = 'vlans_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * @param integer $portID
     * @param integer $vlanID
     **/
    public function unassignVlan($portID, $vlanID)
    {
        $this->getFromDBByCrit([
            'networkports_id' => $portID,
            'vlans_id'        => $vlanID,
        ]);

        return $this->delete($this->fields);
    }

    /**
     * @param integer $port
     * @param integer $vlan
     * @param int $tagged
     * @return bool|int
     **/
    public function assignVlan($port, $vlan, $tagged)
    {
        $input = [
            'networkports_id' => $port,
            'vlans_id'        => $vlan,
            'tagged'          => $tagged,
        ];

        return $this->add($input);
    }

    /**
     * @param NetworkPort $port
     * @return false|void
     */
    public static function showForNetworkPort(NetworkPort $port)
    {
        global $DB;

        $ID = $port->getID();
        if (!$port->can($ID, READ)) {
            return false;
        }

        $canedit = $port->canEdit($ID);

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_networkports_vlans.id as assocID',
                'glpi_networkports_vlans.tagged',
                'glpi_vlans.*',
            ],
            'FROM'      => 'glpi_networkports_vlans',
            'LEFT JOIN' => [
                'glpi_vlans'   => [
                    'ON' => [
                        'glpi_networkports_vlans'  => 'vlans_id',
                        'glpi_vlans'               => 'id',
                    ],
                ],
            ],
            'WHERE'     => ['networkports_id' => $ID],
        ]);

        $vlans  = [];
        $used   = [];
        foreach ($iterator as $line) {
            $used[$line["id"]]       = $line["id"];
            $vlans[$line["assocID"]] = $line;
        }

        if ($canedit) {
            $twig_params = [
                'id' => $ID,
                'used' => $used,
                'tagged_label' => __('Tagged'),
                'btn_label' => _x('button', 'Associate'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'NetworkPort_Vlan'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="networkports_id" value="{{ id }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownField('Vlan', 'vlans_id', 0, null, {
                                no_label: true,
                                used: used
                            }) }}
                            {{ fields.checkboxField('tagged', 0, tagged_label) }}
                        </div>
                        <div class="d-flex flex-row-reverse">
                            <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $vlan = new Vlan();
        foreach ($vlans as $data) {
            $vlan->getFromResultSet($data);

            if (!isset($entity_cache[$data['entities_id']])) {
                $entity_cache[$data['entities_id']] = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
            }
            $entries[] = [
                'itemtype'      => self::class,
                'id'            => $data['assocID'],
                'name'          => $vlan->getLink(),
                'entities_id'   => $entity_cache[$data['entities_id']],
                'tagged'        => Dropdown::getYesNo($data["tagged"]),
                'tag'           => $data['tag'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entities_id' => Entity::getTypeName(1),
                'tagged' => __('Tagged'),
                'tag' => __('ID TAG'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
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

    public static function showForVlan(Vlan $vlan)
    {
        global $DB;

        $ID = $vlan->getID();
        if (!$vlan->can($ID, READ)) {
            return false;
        }

        $canedit = $vlan->canEdit($ID);

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_networkports_vlans.id as assocID',
                'glpi_networkports_vlans.tagged',
                'glpi_networkports.*',
            ],
            'FROM'      => 'glpi_networkports_vlans',
            'LEFT JOIN' => [
                'glpi_networkports'   => [
                    'ON' => [
                        'glpi_networkports_vlans'  => 'networkports_id',
                        'glpi_networkports'        => 'id',
                    ],
                ],
            ],
            'WHERE'     => ['vlans_id' => $ID],
        ]);

        $entries = [];
        $entity_cache = [];
        $netport = new NetworkPort();
        foreach ($iterator as $data) {
            $netport->getFromResultSet($data);
            if (!isset($entity_cache[$data['entities_id']])) {
                $entity_cache[$data['entities_id']] = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
            }
            $entries[] = [
                'itemtype'      => self::class,
                'id'            => $data['assocID'],
                'name'          => $netport->getLink(),
                'entities_id'   => $entity_cache[$data['entities_id']],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entities_id' => Entity::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
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

    /**
     * @param integer $portID
     * @return array
     */
    public static function getVlansForNetworkPort($portID)
    {
        global $DB;

        $vlans = [];
        $iterator = $DB->request([
            'SELECT' => 'vlans_id',
            'FROM'   => 'glpi_networkports_vlans',
            'WHERE'  => ['networkports_id' => $portID],
        ]);

        foreach ($iterator as $data) {
            $vlans[$data['vlans_id']] = $data['vlans_id'];
        }

        return $vlans;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case NetworkPort::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ["networkports_id" => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Vlan::getTypeName(), $nb, $item::class);
                case Vlan::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ["vlans_id" => $item->getID()]
                        );
                    }
                    return self::createTabEntry(NetworkPort::getTypeName(), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        return match ($item::class) {
            NetworkPort::class => self::showForNetworkPort($item),
            Vlan::class => self::showForVlan($item),
            default => true,
        };
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        $specificities = parent::getRelationMassiveActionsSpecificities();

        // Set the labels for add_item and remove_item
        $specificities['button_labels']['add']    = _sx('button', 'Associate');
        $specificities['button_labels']['remove'] = _sx('button', 'Dissociate');

        return $specificities;
    }

    public static function showRelationMassiveActionsSubForm(MassiveAction $ma, $peer_number)
    {
        if ($ma->getAction() === 'add') {
            echo "<br><br>" . __s('Tagged') . Html::getCheckbox(['name' => 'tagged']);
        }
    }

    public static function getRelationInputForProcessingOfMassiveActions(
        $action,
        CommonDBTM $item,
        array $ids,
        array $input
    ) {
        if ($action === 'add') {
            return ['tagged' => $input['tagged']];
        }
        return [];
    }
}
