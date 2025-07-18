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

namespace Glpi\Api\Deprecated;

use Item_Plug;
use PDU;

class Pdu_Plug implements DeprecatedInterface
{
    use CommonDeprecatedTrait;

    public function getType(): string
    {
        return Item_Plug::class;
    }

    public function mapCurrentToDeprecatedHateoas(array $hateoas): array
    {
        return $this->replaceCurrentHateoasRefByDeprecated($hateoas);
    }

    public function mapDeprecatedToCurrentFields(object $fields): object
    {
        $this->renameField($fields, 'pdus_id', 'items_id');
        $this->addField($fields, 'itemtype', PDU::class);

        return $fields;
    }

    public function mapCurrentToDeprecatedFields(array $fields): array
    {
        $this->renameField($fields, 'items_id', 'pdus_id');
        $this->deleteField($fields, 'itemtype');

        return $fields;
    }

    public function mapDeprecatedToCurrentCriteria(array $criteria): array
    {
        return $criteria;
    }

    public function mapCurrentToDeprecatedSearchOptions(array $soptions): array
    {
        $this->updateSearchOptionsUids($soptions);

        return array_map(
            static function ($soption) {
                if (isset($soption['table']) && $soption['table'] === 'glpi_pdu_plugs') {
                    $soption['table'] = 'glpi_item_plugs';
                }
                return $soption;
            },
            $soptions
        );
    }
}
