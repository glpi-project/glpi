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

namespace Glpi\Inventory\Asset;

use Agent;
use Blacklist;
use CommonDBTM;
use CommonDropdown;
use Dropdown;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;
use Manufacturer;
use OperatingSystemKernelVersion;

abstract class InventoryAsset
{
    /** @var array */
    protected $data = [];
    /** @var CommonDBTM */
    protected $item;
    /** @var string */
    protected $itemtype;
    /** @var array */
    protected $extra_data = [];
    /** @var \Agent */
    protected $agent;
    /** @var integer */
    protected $entities_id = 0;
    /** @var array */
    protected $ruleentity_data = [];
    /** @var array */
    protected $rulelocation_data = [];
    /** @var boolean */
    protected $links_handled = false;
    /** @var boolean */
    protected $with_history = true;
    /** @var InventoryAsset */
    protected $main_asset;
    /** @var string */
    protected $request_query;
    /** @var bool */
    private bool $is_new = false;

    /**
     * Constructor
     *
     * @param CommonDBTM $item Item instance
     * @param array|null $data Data part, optional
     */
    public function __construct(CommonDBTM $item, array $data = null)
    {
        $this->item = $item;
        if ($data !== null) {
            $this->data = $data;
        }
    }

    /**
     * Set data from raw data part
     *
     * @param array $data Data part
     *
     * @return InventoryAsset
     */
    public function setData(array $data): InventoryAsset
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get current data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Prepare data from raw data part
     *
     * @return array
     */
    abstract public function prepare(): array;

    /**
     * Handle in database
     *
     * @return void
     */
    abstract public function handle();

    /**
     * Set extra sub parts of interest
     * Only declared types in subclass extra_data are handled
     *
     * @param array $data Processed data
     *
     * @return InventoryAsset
     */
    public function setExtraData($data): InventoryAsset
    {
        foreach (array_keys($this->extra_data) as $extra) {
            if (isset($data[$extra])) {
                $this->extra_data[$extra] = $data[$extra];
            }
        }
        return $this;
    }

    /**
     * Get ignore list declared from asset
     *
     * @param string $type Ignore type ("controllers" only for now)
     *
     * @return array
     */
    public function getIgnored($type): array
    {
        return $this->ignored[$type] ?? [];
    }

    /**
     * Check if configuration allows that part
     *
     * @param Conf $conf Conf instance
     *
     * @return boolean
     */
    abstract public function checkConf(Conf $conf): bool;

    /**
     * Handle links (manufacturers, models, users, ...), create items if needed
     *
     * @return array
     */
    public function handleLinks()
    {
        $knowns = [];
        $foreignkey_itemtype = [];

        $blacklist = new Blacklist();

        $data = $this->data;
        foreach ($data as &$value) {
            $blacklist->processBlackList($value);
            // save raw manufacture name before its replacement by id for importing model
            // (we need manufacturers name in when importing model in dictionary)
            $manufacturer_name = "";
            if (property_exists($value, 'manufacturers_id')) {
                $manufacturer_name = $value->manufacturers_id;
            }

            foreach ($value as $key => &$val) {
                if ($val instanceof \stdClass || is_array($val)) {
                    continue;
                }

                if ($key == "manufacturers_id" || $key == 'bios_manufacturers_id') {
                    $manufacturer = new Manufacturer();
                    $value->$key  = $manufacturer->processName($value->$key);
                    if ($key == 'bios_manufacturers_id') {
                        $foreignkey_itemtype[$key] = getItemtypeForForeignKeyField('manufacturers_id');
                    }
                }
                if (!is_numeric($val)) {
                    $known_key = md5($key . $val);
                    if (isset($knowns[$known_key])) {
                        $value->$key = $knowns[$known_key];
                        continue;
                    }

                    $entities_id = $this->entities_id;
                    if ($key == "locations_id") {
                        $value->$key = Dropdown::importExternal('Location', addslashes($value->$key), $entities_id);
                    } else if (preg_match('/^.+models_id/', $key)) {
                       // models that need manufacturer relation for dictionary import
                       // see CommonDCModelDropdown::$additional_fields_for_dictionnary
                        $value->$key = Dropdown::importExternal(
                            getItemtypeForForeignKeyField($key),
                            addslashes($value->$key),
                            $entities_id,
                            ['manufacturer' => $manufacturer_name]
                        );
                    } else if (isset($foreignkey_itemtype[$key])) {
                        $value->$key = Dropdown::importExternal($foreignkey_itemtype[$key], addslashes($value->$key), $entities_id);
                    } else if (isForeignKeyField($key) && is_a($itemtype = getItemtypeForForeignKeyField($key), CommonDropdown::class, true)) {
                        $foreignkey_itemtype[$key] = $itemtype;
                        $value->$key = Dropdown::importExternal($foreignkey_itemtype[$key], addslashes($value->$key), $entities_id);

                        if (
                            $key == 'operatingsystemkernelversions_id'
                            && property_exists($value, 'operatingsystemkernels_id')
                            && (int)$value->$key > 0
                        ) {
                            $kversion = new OperatingSystemKernelVersion();
                            $kversion->getFromDB($value->$key);
                            if ($kversion->fields['operatingsystemkernels_id'] != $value->operatingsystemkernels_id) {
                                $kversion->update([
                                    'id'                          => $kversion->getID(),
                                    'operatingsystemkernels_id'   => $value->operatingsystemkernels_id
                                ]);
                            }
                        }
                    }
                    $knowns[$known_key] = $value->$key;
                }
            }
        }
        $this->links_handled = true;
        return $this->data;
    }

    /**
     * Set agent
     *
     * @param Agent $agent Agent instance
     *
     * @return $this
     */
    public function setAgent(Agent $agent): InventoryAsset
    {
        $this->agent = $agent;
        return $this;
    }

    /**
     * Get agent
     *
     * @return Agent
     */
    public function getAgent(): Agent
    {
        return $this->agent;
    }

    /**
     * Set entity id from main asset
     *
     * @param integer $id Entity ID
     *
     * @return $this
     */
    public function setEntityID($id): InventoryAsset
    {
        $this->entities_id = $id;
        return $this;
    }

    /**
     * Set request query
     *
     * @param string $query Requested query
     *
     * @return $this
     */
    public function setRequestQuery($query = Request::INVENT_QUERY): InventoryAsset
    {
        $this->request_query = $query;
        return $this;
    }

    /**
     * Are link handled already (call to handleLinks should happen only once
     *
     * @return boolean
     */
    public function areLinksHandled(): bool
    {
        return $this->links_handled;
    }

    /**
     * Set item and itemtype
     *
     * @param CommonDBTM $item Item instance
     *
     * @return InventoryAsset
     */
    protected function setItem(CommonDBTM $item): self
    {
        $this->item = $item;
        $this->itemtype = $item->getType();
        return $this;
    }

    /**
     * Set inventory item
     *
     * @param InventoryAsset $mainasset Main inventory asset instance
     *
     * @return InventoryAsset
     */
    public function setMainAsset(InventoryAsset $mainasset): self
    {
        $this->main_asset = $mainasset;
        return $this;
    }

    /**
     * Get main inventory asset
     *
     * @return InventoryAsset
     */
    public function getMainAsset(): InventoryAsset
    {
        return $this->main_asset;
    }

    /**
     * Add or move a computer_item.
     * If the computer is item is already linked to another computer, existing link will be replaced by new link.
     *
     * @param array $input
     *
     * @return void
     */
    protected function addOrMoveItem(array $input): void
    {
        $citem = new \Computer_Item();
        $citem->getFromDBByCrit([
            'itemtype' => $input['itemtype'],
            'items_id' => $input['items_id']
        ]);

        $itemtype = $input['itemtype'];
        $item = new $itemtype();
        $item->getFromDb($input['items_id']);

        if (!($item->fields['is_global'] ?? false)) {
            if (isset($citem->fields['id'])) {
                $citem->delete(['id' => $citem->fields['id']], true);
            }
            $citem->add($input);
        }
    }

    protected function setNew(): self
    {
        $this->is_new = true;
        $this->with_history = false;//do not handle history on main item first import
        return $this;
    }

    public function isNew(): bool
    {
        return $this->is_new;
    }
}
