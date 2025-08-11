<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use InvalidArgumentException;
use RuntimeException;
use Session;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

final class CustomFieldDefinition extends CommonDBChild
{
    public static $itemtype = AssetDefinition::class;
    public static $items_id = 'assets_assetdefinitions_id';

    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Custom field', 'Custom fields', $nb);
    }

    public static function getNameField()
    {
        return 'label';
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
        global $DB;

        $it = $DB->request([
            'SELECT' => ['fields_display'],
            'FROM' => AssetDefinition::getTable(),
            'WHERE' => [
                'id' => $this->fields[self::$items_id],
            ],
        ]);
        $fields_display = json_decode($it->current()['fields_display'] ?? '[]', true) ?? [];
        $order = 0;
        foreach ($fields_display as $k => $field) {
            if ($field['key'] === 'custom_' . $this->fields['system_name']) {
                $order = $field['order'];
                unset($fields_display[$k]);
                break;
            }
        }
        if ($order > 0) {
            foreach ($fields_display as $k => $field) {
                if ($field['order'] > $order) {
                    $fields_display[$k]['order']--;
                }
            }
        }
        $DB->update(AssetDefinition::getTable(), [
            'fields_display' => json_encode(array_values($fields_display)),
        ], [
            'id' => $this->fields[self::$items_id],
        ]);

        $DB->update('glpi_assets_assets', [
            'custom_fields' => QueryFunction::jsonRemove([
                'custom_fields',
                new QueryExpression($DB::quoteValue('$."' . $this->fields['id'] . '"')),
            ]),
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
        $field_types = array_combine($field_types, array_map(static fn($t) => $t::getName(), $field_types));
        TemplateRenderer::getInstance()->display('pages/assets/customfield.html.twig', [
            'no_header' => true,
            'item' => $this,
            'assetdefinitions_id' => $options[self::$items_id],
            'allowed_dropdown_itemtypes' => $adm->getAllowedDropdownItemtypes(),
            'field_types' => $field_types,
            'params' => [
                'formfooter' => false,
            ],
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
        global $DB;

        if (!is_string($input['system_name']) || preg_match('/^[a-z0-9_]+$/', $input['system_name']) !== 1) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('The following field has an incorrect value: "%s".'),
                    __('System name')
                )),
                false,
                ERROR
            );
            return false;
        }

        // The name must be unique for the asset definition
        $it = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => self::getTable(),
            'WHERE' => [
                'system_name' => $input['system_name'],
                AssetDefinition::getForeignKeyField() => $input[self::$items_id],
            ],
        ]);
        if ($it->current()['cpt'] > 0) {
            Session::addMessageAfterRedirect(__s('The system name must be unique among fields for this asset definition'), false, ERROR);
            return false;
        }
        return true;
    }

    private function prepareInputForAddAndUpdate(array $input): array|false
    {
        // Ensure we have a field instance with the updated type and field options
        $field_for_validation = new self();
        $field_for_validation->fields = array_merge($this->fields, $input);
        if (isset($input['default_value'])) {
            try {
                $input['default_value'] = json_encode($field_for_validation->getFieldType()->formatValueForDB($input['default_value']));
            } catch (InvalidArgumentException) {
                $input['default_value'] = null;
            }
        }

        if (!($field_for_validation->getFieldType() instanceof DropdownType)) {
            $input['itemtype'] = null;
        }

        $input['field_options'] = json_encode($input['field_options'] ?? []);

        if (array_key_exists('translations', $input)) {
            if (!$this->validateTranslationsArray($input['translations'])) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        _n('Translation', 'Translations', Session::getPluralNumber())
                    )),
                    false,
                    ERROR
                );
                return false;
            } else {
                $input['translations'] = json_encode($input['translations']);
            }
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->validateSystemName($input)) {
            return false;
        }

        if (empty($input['label'])) {
            $input['label'] = $input['system_name'];
        }

        $input = $this->prepareInputForAddAndUpdate($input);
        if ($input === false) {
            return false;
        }
        if (!array_key_exists('translations', $input)) {
            $input['translations'] = '[]';
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        // Cannot change type or system_name of existing field
        if (
            array_key_exists('system_name', $input)
            && $input['system_name'] !== $this->fields['system_name']
        ) {
            Session::addMessageAfterRedirect(
                __s('The system name cannot be changed.'),
                false,
                ERROR
            );
            return false;
        }

        if (
            array_key_exists('type', $input)
            && $input['type'] !== $this->fields['type']
        ) {
            Session::addMessageAfterRedirect(
                __s('The field type cannot be changed.'),
                false,
                ERROR
            );
            return false;
        }

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
        if ($this->fields['default_value'] !== null) {
            $this->fields['default_value'] = $this->getFieldType()->formatValueFromDB(json_decode($this->fields['default_value']));
        }
        parent::post_getFromDB();
    }

    public function post_addItem()
    {
        parent::post_addItem();

        $this->refreshAssetDefinition();
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        $this->refreshAssetDefinition();
    }

    public function post_purgeItem()
    {
        parent::post_purgeItem();

        $this->refreshAssetDefinition();
    }

    /**
     * Refresh the asset definition to get force its custom fields definitions to be updated.
     */
    private function refreshAssetDefinition(): void
    {
        $definition = AssetDefinition::getById($this->fields['assets_assetdefinitions_id']);
        AssetDefinitionManager::getInstance()->registerDefinition($definition);
    }

    public function computeFriendlyName(): string
    {
        return $this->getDecodedTranslationsField()[Session::getLanguage()] ?? $this->fields['label'];
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
        throw new RuntimeException('Invalid field type: ' . $this->fields['type']);
    }

    /**
     * Return the decoded value of the `translations` field.
     *
     * @return array{language: string, translation: string}[]
     */
    public function getDecodedTranslationsField(): array
    {
        $translations = json_decode($this->fields['translations'] ?? '[]', associative: true) ?? [];
        if (!$this->validateTranslationsArray($translations)) {
            trigger_error(
                sprintf('Invalid `translations` value (`%s`).', $this->fields['translations']),
                E_USER_WARNING
            );
            $this->fields['translations'] = '[]'; // prevent warning to be triggered on each method call
            $translations = [];
        }
        return $translations;
    }

    /**
     * Validate that the given translations array contains valid values.
     *
     * @param mixed $translations
     * @return bool
     */
    protected function validateTranslationsArray(mixed $translations): bool
    {
        global $CFG_GLPI;

        if (!is_array($translations)) {
            return false;
        }

        $is_valid = true;

        // Array keys must be valid language codes
        foreach (array_keys($translations) as $language) {
            if (!array_key_exists($language, $CFG_GLPI['languages'])) {
                $is_valid = false;
                break;
            }
        }

        // Array values must be strings
        foreach ($translations as $translation) {
            if (!is_string($translation)) {
                $is_valid = false;
                break;
            }
        }

        return $is_valid;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'translations') {
            $translations = json_decode($values[$field], associative: true);
            $twig_params = ['translations' => $translations];
            // language=Twig
            return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% if translations is not empty %}
                    <ul>
                        {% for language, translation in translations %}
                            <li>
                                {{ config('languages')[language][0] }}:
                                {% include "pages/admin/customobjects/plurals.html.twig" with {
                                    'plurals': {
                                        'one': translation
                                    },
                                } only %}
                            </li>
                        {% endfor %}
                    </ul>
                {% endif %}
TWIG, $twig_params);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
