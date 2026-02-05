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

use Glpi\Inventory\Conf;
use PDUModel;
use PDUType;
use Toolbox;

class PDU extends NetworkEquipment
{
    protected $extra_data = [
        'hardware'        => null,
        'network_device'  => null,
        'pdu'             => null,
    ];

    public function prepare(): array
    {
        parent::prepare();

        Toolbox::logDebug($this->data);
        Toolbox::logDebug($this->raw_data);
        Toolbox::logDebug($this->extra_data['pdu']);
        return $this->data;
    }


    public function handle() {}


    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        $this->conf = $conf;
        return $conf->import_pdu == 1 && in_array($this->item::class, $CFG_GLPI['process_types']);
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
