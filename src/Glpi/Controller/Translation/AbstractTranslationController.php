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

namespace Glpi\Controller\Translation;

use Dropdown;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\ItemTranslation\ItemTranslation;
use Session;

abstract class AbstractTranslationController extends AbstractController
{
    /**
     * Get the translation class instance
     */
    abstract protected function getTranslationClass(): ItemTranslation;

    /**
     * Get the redirect URL after successful operations
     */
    abstract protected function getRedirectUrl(?string $language = null): string;

    /**
     * Get the translation handlers for the specific context
     */
    abstract protected function getTranslationHandlers(): array;

    /**
     * Get translations for the specific context
     */
    abstract protected function getContextTranslations(?string $language = null): array;

    /**
     * Validate language code
     */
    protected function validateLanguage(string $language): void
    {
        if (!\array_key_exists($language, Dropdown::getLanguages())) {
            throw new BadRequestHttpException('Invalid language code');
        }
    }

    /**
     * Create a new translation
     */
    protected function createTranslation(array $translation_input): false|int
    {
        $translation_instance = $this->getTranslationClass();

        if (!$translation_instance->can(-1, CREATE, $translation_input)) {
            throw new AccessDeniedHttpException();
        }

        return $translation_instance->add($translation_input);
    }

    /**
     * Update an existing translation
     */
    protected function updateTranslation(ItemTranslation $translation_instance, array $translation_input): bool
    {
        $translation_input['id'] = $translation_instance->getID();

        if (!$translation_instance->can($translation_instance->getID(), UPDATE, $translation_input)) {
            throw new AccessDeniedHttpException();
        }

        return $translation_instance->update($translation_input);
    }

    /**
     * Process multiple translations (create or update)
     */
    protected function processTranslations(array $translations, string $language): bool
    {
        $success = false;
        $translation_class = get_class($this->getTranslationClass());

        foreach ($translations as $translation) {
            $itemtype = $translation['itemtype'];
            $items_id = $translation['items_id'];
            $item = $itemtype::getById($items_id);

            $translation_input = ['language' => $language] + $translation;

            $existing_translation = $translation_class::getForItemKeyAndLanguage($item, $translation['key'], $language);
            if ($existing_translation !== null) {
                $success = $this->updateTranslation($existing_translation, $translation_input);
            } else {
                $success = $this->createTranslation($translation_input) !== false;
            }
        }

        return $success;
    }

    /**
     * Process translation deletions
     */
    protected function processDeletions(string $language): void
    {
        $translation_instance = $this->getTranslationClass();
        $handlers_with_sections = $this->getTranslationHandlers();

        foreach ($handlers_with_sections as $handlers) {
            foreach ($handlers as $handler) {
                $input = [
                    'itemtype' => $handler->getItem()->getType(),
                    'items_id' => $handler->getItem()->getID(),
                    'key'      => $handler->getKey(),
                    'language' => $language,
                ];

                if ($translation_instance->getFromDBByCrit($input)) {
                    $input['id'] = $translation_instance->getID();

                    // Right check
                    if (!$translation_instance->can($translation_instance->getID(), PURGE, $input)) {
                        throw new AccessDeniedHttpException();
                    }

                    // Delete the translation
                    $translation_instance->delete($input + ['purge' => true]);
                }
            }
        }
    }

    /**
     * Create initial translation with first handler
     */
    protected function createInitialTranslation(string $language): void
    {
        $translation_instance = $this->getTranslationClass();
        $translation_handlers = $this->getTranslationHandlers();
        $first_handler = current(current($translation_handlers));

        $input = [
            $translation_instance::$itemtype => $first_handler->getItem()->getType(),
            $translation_instance::$items_id => $first_handler->getItem()->getID(),
            'language'                       => $language,
            'key'                           => $first_handler->getKey(),
            'translations'                  => '{}',
        ];

        // Right check
        if (!$translation_instance->can(-1, CREATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        $translation_instance->add($input);
    }

    /**
     * Add success message after update
     */
    protected function addSuccessMessage(string $language): void
    {
        $context_translations = $this->getContextTranslations($language);
        if ($context_translations !== []) {
            $translation = current(array_filter(
                $context_translations,
                fn($translation) => $translation->fields['language'] === $language
            ));

            if ($translation) {
                Session::addMessageAfterRedirect(
                    $translation->formatSessionMessageAfterAction(__('Item successfully updated'))
                );
            }
        }
    }
}
