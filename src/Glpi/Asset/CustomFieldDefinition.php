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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\TypeInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Session;

final class CustomFieldDefinition extends CommonDBChild
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

    public function showForm($ID, array $options = [])
    {
        $options[self::$items_id] = $options['parent']->fields["id"];
        if (!self::isNewID($ID)) {
            $this->check($ID, UPDATE);
        } else {
            $this->check(-1, CREATE, $options);
        }

        $adm = AssetDefinitionManager::getInstance();
        $field_types = $adm->getCustomFieldTypes();
        $field_types = array_combine($field_types, array_map(static fn ($t) => $t::getName(), $field_types));
        TemplateRenderer::getInstance()->display('pages/assets/customfield.html.twig', [
            'no_header' => true,
            'item' => $this,
            'assetdefinitions_id' => $options[self::$items_id],
            'allowed_dropdown_itemtypes' => $adm->getAllowedDropdownItemtypes(),
            'field_types' => $field_types,
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

    private function validateSystemName(array &$input): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Spaces are replaced with underscores and the name is made lowercase. Only lowercase letters and underscores are kept.
        $input['name'] = preg_replace('/[^a-z_]/', '', strtolower(str_replace(' ', '_', $input['name'])));
        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__s('The system name must not be empty'), false, ERROR);
            return false;
        }

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
            Session::addMessageAfterRedirect(__s('The system name must be unique among fields for this asset definition'), false, ERROR);
            return false;
        }
        return true;
    }

    private function prepareInputForAddAndUpdate(array $input): array
    {
        // Ensure we have a field instance with the updated type and field options
        $field_for_validation = new self();
        $field_for_validation->fields = array_merge($this->fields, $input);
        if (isset($input['default_value'])) {
            try {
                $input['default_value'] = json_encode($field_for_validation->getFieldType()->formatValueForDB($input['default_value']));
            } catch (\InvalidArgumentException) {
                $input['default_value'] = null;
            }
        }

        if (!($field_for_validation->getFieldType() instanceof DropdownType)) {
            $input['itemtype'] = null;
        }

        $input['field_options'] = json_encode($input['field_options'] ?? []);
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->validateSystemName($input)) {
            return false;
        }
        $input = $this->prepareInputForAddAndUpdate($input);
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        // Cannot change type or name of existing field
        unset($input['type'], $input['name']);
        $input = $this->prepareInputForAddAndUpdate($input);
        return parent::prepareInputForUpdate($input);
    }

    public function post_getFromDB()
    {
        $this->fields['field_options'] = json_decode($this->fields['field_options'] ?? '[]', true) ?? [];
        if (isset($this->fields['field_options']['multiple'])) {
            $this->fields['field_options']['multiple'] = (bool) $this->fields['field_options']['multiple'];
        }
        if ($this->fields['default_value'] !== null) {
            $this->fields['default_value'] = $this->getFieldType()->formatValueFromDB(json_decode($this->fields['default_value']));
        }
        parent::post_getFromDB();
    }

    public function computeFriendlyName(): string
    {
        return $this->fields['label'];
    }

    public function getSearchOptionID(): int
    {
        return 45000 + $this->getID();
    }

    public function getFieldType(): TypeInterface
    {
        $field_types = AssetDefinitionManager::getInstance()->getCustomFieldTypes();
        if (in_array($this->fields['type'], $field_types, true)) {
            return new $this->fields['type']($this);
        }
        throw new \RuntimeException('Invalid field type: ' . $this->fields['type']);
    }
}
