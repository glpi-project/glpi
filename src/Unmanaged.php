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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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
 * Not managed devices from inventory
 */
class Unmanaged extends CommonDBTM
{
   // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Unmanaged device', 'Unmanaged devices', $nb);
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
        return $ong;
    }


    /**
     * Print the unmanagemed form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/unmanaged.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'        => '2',
            'table'     => $this->getTable(),
            'field'     => 'id',
            'name'      => __('ID'),
        ];

        $tab[] = [
            'id'        => '3',
            'table'     => 'glpi_locations',
            'field'     => 'name',
            'linkfield' => 'locations_id',
            'name'      => Location::getTypeName(1),
            'datatype'  => 'dropdown',
        ];

        $tab[] = [
            'id'           => '4',
            'table'        => $this->getTable(),
            'field'        => 'serial',
            'name'         => __('Serial Number'),
        ];

        $tab[] = [
            'id'           => '5',
            'table'        => $this->getTable(),
            'field'        => 'otherserial',
            'name'         => __('Inventory number'),
        ];

        $tab[] = [
            'id'           => '6',
            'table'        => $this->getTable(),
            'field'        => 'contact',
            'name'         => Contact::getTypeName(1),
        ];

        $tab[] = [
            'id'        => '7',
            'table'     => $this->getTable(),
            'field'     => 'hub',
            'name'      => __('Network hub'),
            'datatype'  => 'bool',
        ];

        $tab[] = [
            'id'        => '8',
            'table'     => 'glpi_entities',
            'field'     => 'completename',
            'linkfield' => 'entities_id',
            'name'      => Entity::getTypeName(1),
            'datatype'  => 'dropdown',
        ];

        $tab[] = [
            'id'        => '9',
            'table'     => 'glpi_domains',
            'field'     => 'name',
            'linkfield' => 'domains_id',
            'name'      => Domain::getTypeName(1),
            'datatype'  => 'dropdown',
        ];

        $tab[] = [
            'id'        => '10',
            'table'     => $this->getTable(),
            'field'     => 'comment',
            'name'      => __('Comments'),
            'datatype'  => 'text',
        ];

        $tab[] = [
            'id'        => '13',
            'table'     => $this->getTable(),
            'field'     => 'itemtype',
            'name'      => _n('Type', 'Types', 1),
            'datatype'  => 'dropdown',
        ];

        $tab[] = [
            'id'        => '14',
            'table'     => $this->getTable(),
            'field'     => 'date_mod',
            'name'      => __('Last update'),
            'datatype'  => 'datetime',
        ];

        $tab[] = [
            'id'        => '15',
            'table'     => $this->getTable(),
            'field'     => 'sysdescr',
            'name'      => __('Sysdescr'),
            'datatype'  => 'text',
        ];

        $tab[] = [
            'id'           => '18',
            'table'        => $this->getTable(),
            'field'        => 'ip',
            'name'         => __('IP'),
        ];

        return $tab;
    }

    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                NetworkPort::class
            ]
        );
    }

    public static function getIcon()
    {
        return "ti ti-question-mark";
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = [];
        if (self::canUpdate()) {
            $actions['Unmanaged' . MassiveAction::CLASS_ACTION_SEPARATOR . 'convert']    = __('Convert');
        }
        return $actions;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = 0,
        CommonDBTM $checkitem = null
    ) {
        if (self::canUpdate()) {
            $actions['Unmanaged' . MassiveAction::CLASS_ACTION_SEPARATOR . 'convert']    = __('Convert');
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;
        switch ($ma->getAction()) {
            case 'convert':
                echo __('Select an itemtype: ') . ' ';
                Dropdown::showItemType($CFG_GLPI['inventory_types'], [
                    'display_emptychoice' => false,
                ]);
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        global $CFG_GLPI;
        switch ($ma->getAction()) {
            case 'convert':
                $unmanaged = new self();
                foreach ($ids as $id) {
                    $itemtype = $_POST['itemtype'];
                    $unmanaged->convert($id, $itemtype);
                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                }
                break;
        }
    }

    /**
     * Convert to a managed asset
     *
     * @param int         $items_id ID of Unmanaged equipment
     * @param string|null $itemtype Item type to convert to. Will take Unmanaged value if null
     */
    public function convert(int $items_id, string $itemtype = null)
    {
        global $DB;

        $this->getFromDB($items_id);
        $netport = new NetworkPort();

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => NetworkPort::getTable(),
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $items_id
            ]
        ]);

        if (!empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
        }

        $asset = new $itemtype();
        $asset_data = [
            'name'          => $this->fields['name'],
            'entities_id'   => $this->fields['entities_id'],
            'serial'        => $this->fields['serial'],
            'uuid'          => $this->fields['uuid'] ?? null,
            'is_dynamic'    => 1
        ] + $this->fields;
        $assets_id = $asset->add(Toolbox::addslashes_deep($asset_data));

        foreach ($iterator as $row) {
            $row += [
                'items_id' => $assets_id,
                'itemtype' => $itemtype
            ];
            $netport->update(Toolbox::addslashes_deep($row));
        }
        $this->deleteFromDB(1);
    }

    public static function canDelete()
    {
        return static::canUpdate();
    }

    public static function canPurge()
    {
        return static::canUpdate();
    }
}
