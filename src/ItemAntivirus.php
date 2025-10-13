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
 * @since 11.0
 */
class ItemAntivirus extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = true;



    public static function getTypeName($nb = 0)
    {
        return _n('Antivirus', 'Antiviruses', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            throw new RuntimeException("Only CommonDBTM items are supported");
        }

        // can exists for template
        if ($item::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    ['itemtype' => $item->getType(), 'items_id' => $item->getID(), 'is_deleted' => 0 ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item, $withtemplate);
        return true;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
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
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'antivirus_version',
            'name'               => _n('Version', 'Versions', 1),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'signature_version',
            'name'               => __('Signature database version'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'itemantivirus_types',
            'massiveaction'      => false,
        ];

        return $tab;
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];
        $name = _n('Antivirus', 'Antiviruses', Session::getPluralNumber());

        $tab[] = [
            'id'                 => 'antivirus',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '167',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'searchtype'         => ['contains'],
        ];

        $tab[] = [
            'id'                 => '168',
            'table'              => static::getTable(),
            'field'              => 'antivirus_version',
            'name'               => _n('Version', 'Versions', 1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '169',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'linkfield'          => '',
            'name'               => __('Active'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'usehaving'          => true,
            'searchtype'         => ['equals'],
        ];

        $tab[] = [
            'id'                 => '170',
            'table'              => static::getTable(),
            'field'              => 'is_uptodate',
            'linkfield'          => '',
            'name'               => __('Is up to date'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'usehaving'          => true,
            'searchtype'         => ['equals'],
        ];

        $tab[] = [
            'id'                 => '171',
            'table'              => static::getTable(),
            'field'              => 'signature_version',
            'name'               => __('Signature database version'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '172',
            'table'              => static::getTable(),
            'field'              => 'date_expiration',
            'name'               => __('Expiration date'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'date',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        return $tab;
    }

    /**
     * Display form for antivirus
     *
     * @param integer $ID      id of the antivirus
     * @param array   $options
     *
     * @return boolean TRUE if form is ok
     **/
    public function showForm($ID, array $options = [])
    {
        if (isset($options['parent'])) {
            $options['itemtype'] = $options['parent']::class;
            $options['items_id'] = $options['parent']->getID();
        }

        if ($ID > 0) {
            $asset = getItemForItemtype($this->fields['itemtype']);
            $this->check($ID, READ);
            $asset->getFromDB($this->fields['items_id']);
        } else {
            $asset = getItemForItemtype($options['itemtype']);
            $this->check(-1, CREATE, $options);
            $asset->getFromDB($options['items_id']);
        }

        $options['canedit'] = $asset->can($asset->getID(), UPDATE);
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('components/form/item_antivirus.html.twig', [
            'item'   => $this,
            'asset'  => $asset,
            'params' => $options,
        ]);

        return true;
    }


    /**
     * Print the items antiviruses
     *
     * @param CommonDBTM $asset         Asset
     * @param integer    $withtemplate Template or basic item (default 0)
     *
     * @return void
     **/
    private static function showForItem(CommonDBTM $asset, $withtemplate = 0)
    {
        global $DB;

        $ID = $asset->fields['id'];
        $itemtype = $asset->getType();

        if (
            !$asset->getFromDB($ID)
            || !$asset->can($ID, READ)
        ) {
            return;
        }
        $canedit = $asset->canEdit($ID);

        $result = $DB->request(
            [
                'FROM'  => ItemAntivirus::getTable(),
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $ID,
                    'is_deleted'   => 0,
                ],
            ]
        );

        TemplateRenderer::getInstance()->display('components/form/viewsubitem.html.twig', [
            'type' => self::class,
            'parenttype' => $itemtype,
            'items_id' => $asset::getForeignKeyField(),
            'id' => $ID,
            'cancreate' => ($canedit && !(!empty($withtemplate) && ($withtemplate == 2))),
            'add_new_label' => __('Add an antivirus'),
            'ajax_form_submit' => true,
            'reload_tab' => true,
        ]);

        $antivirus = new self();
        $entries = [];
        foreach ($result as $data) {
            $antivirus->getFromDB($data['id']);
            $manufacturer = new Manufacturer();
            $manufacturer->getFromDB($data['manufacturers_id']);
            $entries[] = [
                'name'          => $antivirus->getLink(),
                'is_dynamic'    => Dropdown::getYesNo($data['is_active']),
                'manufacturers_id' => $manufacturer->getLink(),
                'antivirus_version' => $data['antivirus_version'],
                'signature_version' => $data['signature_version'],
                'is_active'     => Dropdown::getYesNo($data['is_active']),
                'is_uptodate'   => Dropdown::getYesNo($data['is_uptodate']),
                'date_expiration' => $data['date_expiration'],
            ];
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'is_dynamic' => __('Automatic inventory'),
                'manufacturers_id' => Manufacturer::getTypeName(1),
                'antivirus_version' => __('Antivirus version'),
                'signature_version' => __('Signature database version'),
                'is_active' => __('Active'),
                'is_uptodate' => __('Up to date'),
                'date_expiration' => __('Expiration date'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'manufacturers_id' => 'raw_html',
                'date_expiration' => 'date',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
            $input['date_expiration'] = 'NULL';
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);

        if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
            $input['date_expiration'] = 'NULL';
        }

        return $input;
    }


    public static function getIcon()
    {
        return "ti ti-virus-search";
    }
}
