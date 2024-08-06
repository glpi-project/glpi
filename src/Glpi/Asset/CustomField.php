<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Asset;

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Session;

final class CustomField extends CommonDBChild
{
    public static $itemtype = AssetDefinition::class;
    public static $items_id = 'assets_assetdefinitions_id';

    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Custom field', 'Custom fields', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-forms';
    }

    public static function canCreate(): bool
    {
        return parent::canUpdate();
    }

    public static function canDelete(): bool
    {
        return parent::canUpdate();
    }

    public static function canPurge(): bool
    {
        return parent::canUpdate();
    }

    public function cleanDBonPurge()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->update('glpi_assets_assets', [
            'custom_fields' => QueryFunction::jsonRemove([
                'custom_fields',
                new QueryExpression($DB::quoteValue('$."' . $this->fields['id'] . '"'))
            ])
        ], [
            'assets_assetdefinitions_id' => $this->fields['assets_assetdefinitions_id'],
        ]);
    }

    public static function getAllowedDropdownItemtypes()
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        static $allowed_dropdown_itemtypes = null;

        if ($allowed_dropdown_itemtypes === null) {
            $allowed_dropdown_itemtypes = [
                _n('Asset', "Assets", Session::getPluralNumber()) => array_combine(
                    $CFG_GLPI['asset_types'],
                    array_map(static fn ($t) => $t::getTypeName(1), $CFG_GLPI['asset_types'])
                ),
            ];
            $allowed_dropdown_itemtypes = array_merge_recursive($allowed_dropdown_itemtypes, Dropdown::getStandardDropdownItemTypes());
        }

        return $allowed_dropdown_itemtypes;
    }

    private static function getFieldTypes(): array
    {
        return [
            'string' => __('String'),
            'text' => __('Text'),
            'number' => __('Number'),
            'date' => _n('Date', 'Dates', 1),
            'datetime' => __('Date and time'),
            'dropdown' => _n('Dropdown', 'Dropdowns', 1),
            'url' => __('URL'),
            'bool' => __('Yes/No'),
            'placeholder' => __('Placeholder'),
        ];
    }

    /**
     * Checks if the value is valid for the field and modifies the value to normalize it.
     * @param mixed $value
     * @return bool
     */
    public function validateValue(mixed &$value): bool
    {
        if ($value === null) {
            return true;
        }

        $valid = match ($this->fields['type']) {
            'bool' => (string) $value === '0' || (string) $value === '1',
            'url', 'string', 'text' => is_string($value),
            'number' => is_numeric($value),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value),
            'datetime' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value),
            'dropdown', 'placeholder' => true,
            default => false,
        };

        if ($valid) {
            $value = match ($this->fields['type']) {
                'bool' => $value ? '1' : '0',
                'string' => substr((string) $value, 0, 255),
                'text', 'url' => (string) $value,
                // Convert dates from current tz to utc/gmt
                'date' => gmdate('Y-m-d', strtotime($value)),
                'datetime' => gmdate('Y-m-d H:i:s', strtotime($value)),
                'placeholder' => '',
                default => $value,
            };
            if ($value === false && in_array($this->fields['type'], ['date', 'datetime'])) {
                return false;
            }

            if ($this->fields['type'] === 'number') {
                $min = $this->fields['field_options']['min'] ?? 0;
                $max = $this->fields['field_options']['max'] ?? PHP_INT_MAX;
                $step = $this->fields['field_options']['step'] ?? 1;
                $is_int = is_int($min + $step);
                $value = $is_int ? (int) $value : (float) $value;
                $value = max($min, min($max, $value));
            }
        }

        return $valid;
    }

    public static function formatFromDB(CustomField $field, mixed $value): mixed
    {
        // Convert date and datetime from utc/gmt to current tz
        if (is_string($value)) {
            $value = match ($field->fields['type']) {
                'date' => date('Y-m-d', strtotime($value . ' UTC')),
                'datetime' => date('Y-m-d H:i:s', strtotime($value . ' UTC')),
                default => $value,
            };
        }
        return $value;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = countElementsInTable(self::getTable(), [
                AssetDefinition::getForeignKeyField() => $item->fields['id'],
            ]);
        }
        return self::createTabEntry(self::getTypeName(), $count);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!$item->canViewItem()) {
            return false;
        }

        $canedit = $item->canUpdateItem();
        $rand = mt_rand();
        if ($canedit) {
            TemplateRenderer::getInstance()->display('components/form/viewsubitem.html.twig', [
                'cancreate' => self::canCreate(),
                'id'        => $item->fields['id'],
                'rand'      => $rand,
                'type'      => self::class,
                'parenttype' => self::$itemtype,
                'items_id'  => self::$items_id,
                'add_new_label' => __('Add a new field'),
                'datatable_id' => 'datatable_customfields' . $rand,
            ]);
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'label', 'type', 'field_options', 'itemtype'],
            'FROM' => self::getTable(),
            'WHERE' => [
                AssetDefinition::getForeignKeyField() => $item->fields['id'],
            ],
        ]);

        $entries = [];
        foreach ($iterator as $data) {
            $entry = [
                'id' => $data['id'],
                'itemtype' => self::class,
                'name' => $data['name'],
                'label' => $data['label'],
                'type' => self::getFieldTypes()[$data['type']] ?? NOT_AVAILABLE,
                'dropdown_itemtype' => $data['itemtype'] !== '' ? (self::getAllowedDropdownItemtypes()[$data['itemtype']] ?? NOT_AVAILABLE) : NOT_AVAILABLE,
                'row_class' => 'cursor-pointer'
            ];

            $field_options = json_decode($data['field_options'] ?? '[]', true) ?? [];
            $flags = '';
            if ($field_options['readonly'] ?? false) {
                $flags .= '<span class="badge badge-outline text-secondary">' . __s('Read-only') . '</span>';
            }
            if ($field_options['required'] ?? false) {
                $flags .= '<span class="badge badge-outline text-secondary">' . __s('Mandatory') . '</span>';
            }
            if ($field_options['multiple'] ?? false) {
                $flags .= '<span class="badge badge-outline text-secondary">' . __s('Multiple values') . '</span>';
            }
            $entry['flags'] = $flags;
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'datatable_customfields' . $rand,
            'is_tab' => true,
            'nopager' => true,
            'nosort' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'label' => __('Label'),
                'type' => _n('Type', 'Types', 1),
                'flags' => __('Flags'),
                'dropdown_itemtype' => __('Item type'),
            ],
            'formatters' => [
                'flags' => 'raw_html'
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . str_replace('\\', '_', self::class) . $rand,
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')]
            ],
        ]);

        return true;
    }

    public function showForm($ID, array $options = [])
    {
        $options[self::$items_id] = $options['parent']->fields["id"];
        if (!self::isNewID($ID)) {
            $this->check($ID, UPDATE);
        } else {
            $this->check(-1, CREATE, $options);
        }

        TemplateRenderer::getInstance()->display('pages/assets/customfield.html.twig', [
            'no_header' => true,
            'item' => $this,
            'assetdefinitions_id' => $options[self::$items_id],
            'allowed_dropdown_itemtypes' => self::getAllowedDropdownItemtypes(),
            'field_types' => self::getFieldTypes(),
        ]);
        return true;
    }

    public function getEmpty()
    {
        if (parent::getEmpty()) {
            $this->fields['field_options'] = [];
            return true;
        }
        return false;
    }

    private function prepareInputForAddAndUpdate(array $input): array|false
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Spaces are replaced with underscores and the name is made lowercase. Only lowercase letters and underscores are kept.
        $input['name'] = preg_replace('/[^a-z_]/', '', strtolower(str_replace(' ', '_', $input['name'])));

        // The name must be unique for the asset definition
        $it = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => self::getTable(),
            'WHERE' => [
                'name' => $input['name'],
                AssetDefinition::getForeignKeyField() => $input[self::$items_id],
            ],
        ]);
        if ($it->current()['cpt'] > 0) {
            Session::addMessageAfterRedirect(__('The system name must be unique among fields for this asset definition'), false, ERROR);
            return false;
        }

        // Ensure we have a field instance with the updated type and field options
        $field_for_validation = new self();
        $field_for_validation->fields = array_merge($this->fields, $input);
        if (isset($input['default_value']) && !$field_for_validation->validateValue($input['default_value'])) {
            $input['default_value'] = null;
        }

        if (isset($input['field_options']['multiple'])) {
            $input['field_options']['multiple'] = (bool) $input['field_options']['multiple'];
        }
        $input['field_options'] = json_encode($input['field_options'] ?? []);
        if ($input['type'] === 'placeholder') {
            $input['field_options'] = '[]';
            $input['name'] = 'placeholder' . mt_rand();
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAndUpdate($input);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForAddAndUpdate($input);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    public function post_getFromDB()
    {
        $this->fields['field_options'] = json_decode($this->fields['field_options'] ?? '[]', true) ?? [];
        if (isset($this->fields['field_options']['multiple'])) {
            $this->fields['field_options']['multiple'] = (bool) $this->fields['field_options']['multiple'];
        }
        $this->fields['default_value'] = self::formatFromDB($this, $this->fields['default_value']);
        parent::post_getFromDB();
    }

    public function getSearchOption(): ?array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $opt = [
            'id' => 45000 + $this->getID(),
            'name' => $this->fields['label'],
            'table' => 'glpi_assets_assets',
            'field' => 'value',
            'computation' => QueryFunction::coalesce([
                QueryFunction::jsonUnquote(
                    expression: QueryFunction::jsonExtract([
                        'glpi_assets_assets.custom_fields',
                        new QueryExpression($DB::quoteValue('$."' . $this->fields['id'] . '"'))
                    ])
                ),
                new QueryExpression($DB::quoteValue($this->fields['default_value']))
            ]),
            'nometa' => true,
            'field_definition' => $this,
        ];

        if ($this->fields['type'] === 'dropdown') {
            $opt['searchtype'] = ['equals'];
            if ($this->fields['field_options']['multiple'] ?? false) {
                $opt['usehaving'] = true;
            }
        }

        $opt['datatype'] = match ($this->fields['type']) {
            'url' => 'string',
            'dropdown' => 'specific',
            'placeholder' => null,
            default => $this->fields['type'],
        };

        if ($opt['datatype'] === null) {
            return null;
        }

        if ($opt['datatype'] === 'number') {
            $opt['min'] = $this->fields['field_options']['min'] ?? 0;
            $opt['max'] = $this->fields['field_options']['max'] ?? PHP_INT_MAX;
            $opt['step'] = $this->fields['field_options']['step'] ?? 1;
        }

        return $opt;
    }
}
