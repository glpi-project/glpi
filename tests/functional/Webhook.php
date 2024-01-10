<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

/* Test for inc/user.class.php */

use Glpi\Search\SearchOption;

class Webhook extends \DbTestCase
{
    /**
     * Make sure all webhook item types have an ID search option so that the criteria filters can be applied properly
     * @return void
     */
    public function testWebhookTypesHaveIDOpt()
    {
        $supported = \Webhook::getItemtypesDropdownValues();
        $itemtypes = [];
        foreach ($supported as $types) {
            $itemtypes = array_merge($itemtypes, array_keys($types));
        }

        /** @var \CommonDBTM $itemtype */
        foreach ($itemtypes as $itemtype) {
            $opts = SearchOption::getOptionsForItemtype($itemtype);
            $id_field = $itemtype::getIndexName();
            $item_table = $itemtype::getTable();
            $id_opt_num = null;
            foreach ($opts as $opt_num => $opt) {
                if (isset($opt['field']) && $opt['field'] === $id_field && $opt['table'] === $item_table) {
                    $id_opt_num = $opt_num;
                    break;
                }
            }
            if ($id_opt_num === null) {
                echo 'No ID option found for itemtype ' . $itemtype;
            }
            $this->variable($id_opt_num)->isNotNull();
        }
    }
}
