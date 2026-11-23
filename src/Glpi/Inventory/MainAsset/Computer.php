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

use ComputerModel;
use ComputerType;
use NetworkPort as GlobalNetworkPort;
use stdClass;

class Computer extends MainAsset
{
    protected function getModelsFieldName(): string
    {
        return ComputerModel::getForeignKeyField();
    }

    protected function getTypesFieldName(): string
    {
        return ComputerType::getForeignKeyField();
    }


    protected function portUpdated(stdClass $port, int $netports_id): void
    {
        $this->handlePortMetrics($port, $netports_id);
    }

    private function handlePortMetrics(stdClass $port, int $netports_id): void
    {
        $input = (array) $port;
        if (isset($input['ifinbytes'], $input['ifoutbytes'], $input['ifinerrors'], $input['ifouterrors'])) {
            $netport = new GlobalNetworkPort();
            $netport->update([
                'id'          => $netports_id,
                'ifinbytes'   => $input['ifinbytes'],
                'ifoutbytes'  => $input['ifoutbytes'],
                'ifinerrors'  => $input['ifinerrors'],
                'ifouterrors' => $input['ifouterrors'],
                'is_dynamic'  => true,
            ]);
        }
    }
}
