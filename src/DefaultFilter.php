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
use Glpi\Search\CriteriaFilter;
use Glpi\Search\FilterableInterface;
use Glpi\Search\FilterableTrait;

use function Safe\json_decode;

class DefaultFilter extends CommonDBTM implements FilterableInterface
{
    use FilterableTrait;

    public static $rightname = 'defaultfilter';

    public function getItemtypeToFilter(): string
    {
        return $this->fields['itemtype'];
    }

    public function getItemtypeField(): string
    {
        return 'itemtype';
    }

    public function getInfoTitle(): string
    {
        return __("Additional default filter");
    }

    public function getInfoDescription(): string
    {
        return __("Default search filter, applied in addition to the user's search criteria.");
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Default filter', 'Default filters', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', CommonDropdown::class, self::class];
    }

    public static function getIcon()
    {
        return "ti ti-filter";
    }
    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '4',
            'table'         => self::getTable(),
            'field'         =>  'comment',
            'name'          =>  _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'      =>  'text',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Itemtype'),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'globalsearch_types',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'itemtype',
                'label' => __('Itemtype'),
                'type'           => 'itemtypename',
                'itemtype_list'      => 'globalsearch_types',
            ],
        ];
    }

    public static function getSearchCriteria(string $itemtype): ?array
    {
        global $DB;

        $default_table = self::getTable();
        $filter_table = CriteriaFilter::getTable();

        $criteria = [
            'SELECT' => [
                "$default_table.*",
                "$filter_table.search_criteria",
            ],
            'FROM' => $default_table,
            'JOIN' => [
                $filter_table => [
                    'FKEY'  => [
                        $default_table => 'id',
                        $filter_table => 'items_id',
                    ],
                    'AND'   => [
                        "$filter_table.itemtype" => self::class,
                    ],
                ],
            ],
            'WHERE' => [
                "$default_table.itemtype" => $itemtype,
                "NOT" => [
                    "$filter_table.search_criteria" => null,
                ],
            ],
        ];

        $iterator = $DB->request($criteria);

        if ($iterator->count() == 1) {
            $item = $iterator->current();

            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'comment' => $item['comment'],
                'search_criteria' => [
                    'link' => 'AND',
                    'criteria' => json_decode($item['search_criteria'], true),
                ],
            ];
        }
        return null;
    }

    private function prepareInput($input)
    {
        // Checks that the itemtype is not already in use
        $criteria = [
            'itemtype' => $input['itemtype'],
        ];

        if (isset($input['id'])) {
            $criteria['id'] = ['!=', $input['id']];
        }

        if ($this->getFromDBByCrit($criteria)) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('Itemtype %s is already in use'),
                    $input['itemtype']
                )),
                true,
                ERROR
            );
            return false;
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }
}
