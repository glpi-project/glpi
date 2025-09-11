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
use Gettext\Languages\Language;
use Glpi\Form\FormTranslation;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use LogicException;
use Override;
use Session;

use function Safe\json_decode;
use function Safe\json_encode;

abstract class ItemTranslation extends CommonDBChild
{
    public static $rightname = 'config';

    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

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
            if (!is_string($input['translations'])) {
                $input['translations'] = json_encode($input['translations']);
            } elseif ($input['translations'] == "") {
                $input['translations'] = "{}";
            }
        }

        if (
            isset($input['key'])
            && isset($input['itemtype'])
            && isset($input['items_id'])
        ) {
            $item = getItemForItemtype($input['itemtype']);
            if (
                $item instanceof ProvideTranslationsInterface
                && $item->getFromDB($input['items_id'])
            ) {
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
        $formula_to_compute = str_replace('n', (string) $count, $gettext_language->formula);
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
        $item = getItemForItemtype($this->fields[static::$itemtype]);
        if (
            $item instanceof ProvideTranslationsInterface
            && $item->getFromDB($this->fields[static::$items_id])
        ) {
            foreach ($item->listTranslationsHandlers() as $handlers) {
                foreach ($handlers as $handler) {
                    if ($handler->getKey() === $this->fields['key'] && $this->getTranslation() != null) {
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
     * @return array<ItemTranslation|false>
     */
    public static function getTranslationsForItem(CommonDBTM $item): array
    {
        $translations = (new static())->find([
            static::$items_id => $item->getID(),
            static::$itemtype => $item->getType(),
        ]);

        return array_map(fn($id) => static::getById($id), array_keys($translations));
    }

    /**
     * Get instance for the given item, key and language.
     */
    public static function getForItemKeyAndLanguage(CommonDBTM $item, string $key, string $language): ?ItemTranslation
    {
        $translation = (new static())->find([
            static::$items_id => $item->getID(),
            static::$itemtype => $item->getType(),
            'key'             => $key,
            'language'        => $language,
        ]);

        if (!empty($translation)) {
            $itemtranslation = static::getById(key($translation));
            if ($itemtranslation instanceof self) {
                return $itemtranslation;
            }
        }

        return null;
    }

    /**
     * Get the translated string for the given object and key in the current session language.
     */
    public static function translate(CommonDBTM&ProvideTranslationsInterface $item, string $key, int $count = 1): ?string
    {
        $translation = static::getForItemKeyAndLanguage($item, $key, Session::getLanguage())?->getTranslation($count);

        if (!empty($translation)) {
            return $translation;
        }

        foreach ($item->listTranslationsHandlers() as $handlers) {
            foreach ($handlers as $handler) {
                if (
                    $handler->getItem()::class === $item::class
                    && $handler->getItem()->getID() === $item->getID()
                    && $handler->getKey() === $key
                ) {
                    return $handler->getValue();
                }
            }
        }

        return null;
    }

    protected function getTranslationsHandlersForStats(): array
    {
        $item = $this->getItem();
        if (!($item instanceof ProvideTranslationsInterface)) {
            throw new LogicException('Item does not provide translations');
        }

        return $item->listTranslationsHandlers();
    }

    public function getTranslatedPercentage(): int
    {
        $translations_handlers = $this->getTranslationsHandlersForStats();
        $translated_handlers = 0;
        $total_handlers = 0;

        array_walk_recursive(
            $translations_handlers,
            function ($handler) use (&$translated_handlers, &$total_handlers) {
                if (
                    !empty(static::getForItemKeyAndLanguage($handler->getItem(), $handler->getKey(), $this->fields['language'])?->getTranslation())
                ) {
                    $translated_handlers++;
                }

                $total_handlers++;
            }
        );

        return $total_handlers > 0 ? (int) (($translated_handlers / $total_handlers) * 100) : 0;
    }

    public function getTranslationsToDo(): int
    {
        $translations_handlers = $this->getTranslationsHandlersForStats();
        $translated_handlers = 0;
        $total_handlers = 0;

        array_walk_recursive(
            $translations_handlers,
            function ($handler) use (&$translated_handlers, &$total_handlers) {
                if (
                    !empty(static::getForItemKeyAndLanguage($handler->getItem(), $handler->getKey(), $this->fields['language'])?->getTranslation())
                ) {
                    $translated_handlers++;
                }

                $total_handlers++;
            }
        );

        return $total_handlers - $translated_handlers;
    }

    public function getTranslationsToReview(): int
    {

        $translations_handlers = $this->getTranslationsHandlersForStats();
        $translations_to_review = 0;

        array_walk_recursive(
            $translations_handlers,
            function ($handler) use (&$translations_to_review) {
                $translation = new FormTranslation();
                if (
                    $translation->getFromDBByCrit([
                        static::$items_id => $handler->getItem()->getID(),
                        static::$itemtype => $handler->getItem()->getType(),
                        'language'        => $this->fields['language'],
                        'key'             => $handler->getKey(),
                        'hash'            => ['!=', md5($handler->getValue())],
                    ])
                ) {
                    if ($translation->isPossiblyObsolete()) {
                        $translations_to_review++;
                    }
                }
            }
        );

        return $translations_to_review;
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        throw new LogicException('getSystemSQLCriteria must be implemented in subclasses of ItemTranslation');
    }
}
