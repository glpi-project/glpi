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

namespace Glpi\Inventory\MainAsset;

use CommonDBTM;
use Glpi\Inventory\Asset\Plug as AssetPlug;
use Glpi\Inventory\Conf;
use PDUModel;
use PDUType;

class PDU extends NetworkEquipment
{
    protected Conf $conf;
    private AssetPlug $plugs;

    public function __construct(CommonDBTM $item, $data)
    {
        parent::__construct($item, $data);
    }

    public function prepare(): array
    {
        parent::prepare();

        $inventory_plugs = [];
        foreach ($this->data as &$val) {

            // asset type is defined under 'pdu->type'
            if (property_exists($val, 'pdu') && property_exists($val->pdu, 'type')) {
                $val->pdutypes_id = $val->pdu->type;
                unset($val->pdu->type);
            }

            if (property_exists($val, 'pdu') && property_exists($val->pdu, 'plugs')) {
                $inventory_plugs[] = $val->pdu->plugs;
            }
        }

        if (count($inventory_plugs)) {
            $this->plugs = new AssetPlug($this->item);
            $this->plugs->setData($inventory_plugs);
            $this->plugs->prepare();
        }
        return $this->data;
    }

    public function handle()
    {
        parent::handle();
        if (isset($this->plugs)) {
            $this->plugs->handleLinks();
            $this->plugs->handle();
        }
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        $this->conf = $conf;
        return $conf->import_pdu == 1 && in_array($this->item::class, $CFG_GLPI['plug_types']);
    }

    protected function getModelsFieldName(): string
    {
        return PDUModel::getForeignKeyField();
    }

    protected function getTypesFieldName(): string
    {
        return PDUType::getForeignKeyField();
    }

}
