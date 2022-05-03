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

namespace Glpi\Csv;

use CommonDBTM;
use Impact;
use ImpactContext;
use ImpactItem;

class ImpactCsvExport implements ExportToCsvInterface
{
    /** @var CommonDBTM */
    protected $item;

    public function __construct(CommonDBTM $item)
    {
        $this->item = $item;
    }

    public function getFileName(): string
    {
        return "{$this->item->fields['name']}.csv";
    }

    public function getFileHeader(): array
    {
        return [
            __("Relation"),
            __("Itemtype"),
            __("Id"),
            __("Name"),
        ];
    }

    public function getFileContent(): array
    {
        $content = [];

       // Load graph and impactitem
        $graph = Impact::buildGraph($this->item);
        $impact_item = ImpactItem::findForItem($this->item);
        $impact_context = ImpactContext::findForImpactItem($impact_item);

       // Get depth param
        if (!$impact_context) {
            $max_depth = Impact::DEFAULT_DEPTH;
        } else {
            $max_depth = $impact_context->fields["max_depth"];
        }

       // Load list data
        $data = [];
        $directions = [Impact::DIRECTION_FORWARD, Impact::DIRECTION_BACKWARD];
        foreach ($directions as $direction) {
            $data[$direction] = Impact::buildListData(
                $graph,
                $direction,
                $this->item,
                $max_depth
            );
        }

       // Flatten the hiarchical $data and insert it line by line
        foreach ($data as $direction => $impact_data) {
            if ($direction == Impact::DIRECTION_FORWARD) {
                $direction_label = __("Impact");
            } else {
                $direction_label = __("Impacted by");
            }

            foreach ($impact_data as $data_type => $data_elements) {
                foreach ($data_elements as $data_element) {
                    $content[] = [
                        $direction_label,
                        $data_type,
                        $data_element['stored']->fields['id'],
                        $data_element['stored']->fields['name'],
                    ];
                }
            }
        }

        return $content;
    }
}
