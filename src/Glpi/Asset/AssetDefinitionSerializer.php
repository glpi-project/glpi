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

namespace Glpi\Asset;

use Closure;
use CommonDBTM;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\CustomObject\DefinitionSerializer;
use Profile;
use RuntimeException;
use Throwable;
use Toolbox;

use function Safe\base64_decode;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\preg_replace;

/**
 * Exports and imports asset definitions, with their capacities, fields
 * display, picture and custom fields.
 *
 * On top of the common definition format, each item has a `custom_fields`
 * list, and its `picture` is embedded.
 */
final class AssetDefinitionSerializer extends DefinitionSerializer
{
    /** Columns of the custom fields copied as they are. */
    private const CUSTOM_FIELD_COLUMNS = ['system_name', 'label', 'type', 'itemtype'];

    /** Columns of the custom fields holding JSON. */
    private const CUSTOM_FIELD_JSON_COLUMNS = ['field_options', 'default_value', 'translations'];

    /** Value of the `readonly` and `hidden` field options that is not a profile. */
    private const SPECIAL_PROFILE_VALUES = ['-1'];

    public function __construct()
    {
        parent::__construct(AssetDefinition::class);
    }

    /** @param AbstractDefinition<*> $definition */
    protected function exportDefinition(AbstractDefinition $definition): array
    {
        $item = parent::exportDefinition($definition);
        foreach (['capacities', 'fields_display'] as $column) {
            $item['fields'][$column] = json_decode($definition->fields[$column] ?? '[]', true);
        }
        $item['fields']['picture'] = $this->exportPicture($definition->fields['picture'] ?? null);

        $item['custom_fields'] = [];
        $rows = (new CustomFieldDefinition())->find([AssetDefinition::getForeignKeyField() => $definition->getID()], ['id']);
        foreach ($rows as $row) {
            $fields = array_intersect_key($row, array_flip(self::CUSTOM_FIELD_COLUMNS));
            foreach (self::CUSTOM_FIELD_JSON_COLUMNS as $column) {
                $fields[$column] = is_string($row[$column]) ? json_decode($row[$column], true) : null;
            }
            $item['custom_fields'][] = $fields;
        }
        return $item;
    }

    protected function prepareInput(array $fields): array
    {
        $input = parent::prepareInput($fields);
        $input['capacities'] = is_array($fields['capacities'] ?? null) ? $fields['capacities'] : [];

        // Stored as a list of {key, order, field_options}, but given as two arrays.
        $fields_display = array_filter(is_array($fields['fields_display'] ?? null) ? $fields['fields_display'] : [], 'is_array');
        $input['fields_display'] = array_column($fields_display, 'key', 'order');
        $input['field_options'] = array_column($fields_display, 'field_options', 'key');

        $picture = $fields['picture'] ?? null;
        if (is_array($picture) && is_string($picture['data'] ?? null)) {
            // Handed over to `managePictures()`, which checks it is an image.
            $extension = preg_replace('/[^a-z0-9]/i', '', pathinfo((string) ($picture['name'] ?? ''), PATHINFO_EXTENSION));
            $filename = uniqid('import_') . '.' . $extension;
            file_put_contents(GLPI_TMP_DIR . '/' . $filename, base64_decode($picture['data'], true));
            $input['_picture'] = [$filename];
        }
        return $input;
    }

    /** @return AbstractDefinition<*> */
    protected function importDefinition(array $item): AbstractDefinition
    {
        $definition = parent::importDefinition($item);
        try {
            foreach (is_array($item['custom_fields'] ?? null) ? $item['custom_fields'] : [] as $fields) {
                if (!is_array($fields)) {
                    continue;
                }
                $input = array_intersect_key($fields, array_flip([...self::CUSTOM_FIELD_COLUMNS, ...self::CUSTOM_FIELD_JSON_COLUMNS]));
                $input[AssetDefinition::getForeignKeyField()] = $definition->getID();
                if ((new CustomFieldDefinition())->add($input) === false) {
                    throw new RuntimeException(sprintf('Unable to import the custom field "%s".', $fields['system_name'] ?? ''));
                }
            }
        } catch (Throwable $e) {
            // The definition is rolled back, but not its picture file.
            if (!empty($definition->fields['picture'])) {
                Toolbox::deletePicture($definition->fields['picture']);
            }
            throw $e;
        }
        return $definition;
    }

    protected function convertReferences(array $item, Closure $convert): array
    {
        $item = parent::convertReferences($item, $convert);

        $fields_display = $item['fields']['fields_display'] ?? [];
        foreach (is_array($fields_display) ? $fields_display : [] as $key => $field) {
            if (is_array($field) && is_array($field['field_options'] ?? null)) {
                $fields_display[$key]['field_options'] = self::convertFieldOptions($field['field_options'], $convert);
            }
        }
        $item['fields']['fields_display'] = $fields_display;

        $custom_fields = $item['custom_fields'] ?? [];
        foreach (is_array($custom_fields) ? $custom_fields : [] as $key => $field) {
            if (!is_array($field)) {
                continue;
            }
            if (is_array($field['field_options'] ?? null)) {
                $custom_fields[$key]['field_options'] = self::convertFieldOptions($field['field_options'], $convert);
            }
            // Default value of a dropdown field
            $itemtype = $field['itemtype'] ?? null;
            if (is_string($itemtype) && is_a($itemtype, CommonDBTM::class, true)) {
                $custom_fields[$key]['default_value'] = self::convertValue($field['default_value'] ?? null, $itemtype, $convert);
            }
        }
        $item['custom_fields'] = $custom_fields;

        return $item;
    }

    protected function getFatalErrors(array $item): array
    {
        $classes = [];
        $capacities = $item['fields']['capacities'] ?? [];
        foreach (is_array($capacities) ? $capacities : [] as $capacity) {
            $classes[] = is_array($capacity) ? ($capacity['name'] ?? null) : null;
        }
        $custom_fields = $item['custom_fields'] ?? [];
        foreach (is_array($custom_fields) ? $custom_fields : [] as $field) {
            $classes[] = is_array($field) ? ($field['type'] ?? null) : null;
            $classes[] = is_array($field) ? ($field['itemtype'] ?? null) : null;
        }
        return array_values(array_unique([...parent::getFatalErrors($item), ...self::getMissingClassesErrors($classes)]));
    }

    /**
     * @param array<mixed> $options
     * @param Closure(class-string<CommonDBTM>, mixed): mixed $convert
     * @return array<mixed>
     */
    private static function convertFieldOptions(array $options, Closure $convert): array
    {
        foreach (['readonly', 'hidden'] as $option) {
            if (isset($options[$option])) {
                $options[$option] = self::convertValue($options[$option], Profile::class, $convert, self::SPECIAL_PROFILE_VALUES);
            }
        }
        return $options;
    }

    /** @return array{name: string, data: string}|null */
    private function exportPicture(mixed $path): ?array
    {
        if (!is_string($path) || $path === '' || str_contains($path, '..') || !is_file(GLPI_PICTURE_DIR . '/' . $path)) {
            return null;
        }
        return ['name' => basename($path), 'data' => base64_encode(file_get_contents(GLPI_PICTURE_DIR . '/' . $path))];
    }
}
