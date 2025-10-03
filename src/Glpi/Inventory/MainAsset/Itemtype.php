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

use Blacklist;
use Glpi\Inventory\Asset\NetworkCard;
use RuleDefineItemtypeCollection;
use stdClass;

class Itemtype extends MainAsset
{
    protected $extra_data = [
        'hardware' => null,
        'bios' => null,
        'users' => null,
        NetworkCard::class => null,
        'network_device' => null,
        'network_components' => null,
    ];

    /**
     * @param stdClass $data
     */
    public function __construct($data)
    {
        $namespaced = explode('\\', static::class);
        $this->itemtype = array_pop($namespaced);
        //store raw data for reference
        $this->raw_data = $data;
    }

    protected function getModelsFieldName(): string
    {
        return '';
    }

    protected function getTypesFieldName(): string
    {
        return '';
    }

    public function defineItemtype($original_itemtype): array
    {
        $blacklist = new Blacklist();

        $data = $this->data[0] ?? null; //there is only one data entry for MainAsset
        if (!$data) {
            return [];
        }

        //netrwok equipments information are store in extra node network_device
        if (isset($this->extra_data['network_device'])) {
            $data = (object) array_merge((array) $data, (array) $this->extra_data['network_device']);
        }

        $blacklist->processBlackList($data);
        $input = $this->prepareAllRulesInput($data);

        //Force correct itemtype for rules
        $input['itemtype'] = $original_itemtype;
        $itemtype_rule = new RuleDefineItemtypeCollection();
        $itemtype_rule->getCollectionPart();
        $data_itemtype = $itemtype_rule->processAllRules($input);
        return $data_itemtype;
    }
}
