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

namespace Glpi\ItemTranslation;

use CommonDBChild;
use CommonDBTM;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Override;
use Gettext\Languages\Language;

abstract class ItemTranslation extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';


    #[Override]
    public static function getTable($classname = null)
    {
        return 'glpi_itemtranslations_itemtranslations';
    }

    #[Override]
    public function prepareInputForAdd($input): array
    {
        return $this->prepapreInput($input);
    }

    #[Override]
    public function prepareInputForUpdate($input): array
    {
        return $this->prepapreInput($input);
    }

    public function prepapreInput($input): array
    {
        if (isset($input['translations'])) {
            $input['translations'] = json_encode($input['translations']);
        }

        if (
            isset($input['key'])
            && isset($input['itemtype'])
            && isset($input['items_id'])
        ) {
            $item = getItemForItemtype($input['itemtype'])?->getById($input['items_id']);
            if ($item instanceof ProvideTranslationsInterface) {
                foreach ($item->listTranslationsHandlers() as $handlers) {
                    foreach ($handlers as $handler) {
                        if ($handler->getKey() === $input['key']) {
                            $input['hash'] = md5($handler->getValue());
                            break;
                        }
                    }
                }
            }
        }

        return $input;
    }

    public function getTranslation(int $count = 1): ?string
    {
        if ($this->isNewItem()) {
            return '';
        }

        $translations =  json_decode($this->fields['translations'], true);

        // retrieve the formulas associated to the language
        $gettext_language = Language::getById($this->fields['language']);

        // compute the formula with the paramater count
        $formula_to_compute = str_replace('n', $count, $gettext_language->formula);
        $category_index_number = eval("return $formula_to_compute;");

        // retrieve the category index string (one, few, many, other) based on the index
        $found_category = $gettext_language->categories[$category_index_number] ?? $gettext_language->categories[0];
        $category_index_string = $found_category->id;

        return $translations[$category_index_string] ?? null;
    }

    /**
     * Check if the translation is possibly obsolete
     *
     * @return bool
     */
    public function isPossiblyObsolete(): bool
    {
        $item = getItemForItemtype($this->fields[static::$itemtype])?->getById($this->fields[static::$items_id]);
        if ($item instanceof ProvideTranslationsInterface) {
            foreach ($item->listTranslationsHandlers() as $handlers) {
                foreach ($handlers as $handler) {
                    if ($handler->getKey() === $this->fields['key']) {
                        return md5($handler->getValue()) !== $this->fields['hash'];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get translations for an item
     *
     * @param CommonDBTM $item
     *
     * @return array<ItemTranslation>
     */
    public static function getTranslationsForItem(CommonDBTM $item): array
    {
        $translations = (new static())->find([
            static::$items_id => $item->getID(),
            static::$itemtype => $item->getType()
        ]);

        return array_map(fn($id) => static::getById($id), array_keys($translations));
    }

    /**
     * Get instance for the given item, key and language.
     *
     * @param CommonDBTM $item
     * @param string $key
     * @param string $language
     *
     * @return ItemTranslation|null
     */
    public static function getForItemKeyAndLanguage(CommonDBTM $item, string $key, string $language): ?ItemTranslation
    {
        $translation = (new static())->find([
            static::$items_id => $item->getID(),
            static::$itemtype => $item->getType(),
            'key'             => $key,
            'language'        => $language
        ]);

        if (!empty($translation)) {
            return static::getById(key($translation));
        }

        return null;
    }
}
