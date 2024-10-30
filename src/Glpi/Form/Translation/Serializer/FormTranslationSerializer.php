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

namespace Glpi\Form\Translation\Serializer;

use Glpi\Form\Translation\Specification\FormTranslationSpecification;
use Glpi\Form\Translation\Specification\FormTranslationsSpecification;

final class FormTranslationSerializer extends AbstractFormTranslationSerializer
{
    public function getTranslationsFromJson(string $json): FormTranslationsSpecification
    {
        return $this->deserialize($json);
    }

    public function getJsonFromTranslations(FormTranslationsSpecification $translations): string
    {
        // Reindex the array to avoid useless keys in the JSON
        $translations->translations = array_values($translations->translations);

        return $this->serialize($translations);
    }

    public function setTranslation(FormTranslationsSpecification &$translations, string $key, string $translation): void
    {
        // Check if the key exists in the translations and if the translation is different
        foreach ($translations->translations as $formTranslation) {
            if ($formTranslation->key === $key) {
                if ($formTranslation->translation !== $translation) {
                    $formTranslation->translation = $translation;
                    $formTranslation->last_update = time();
                }

                return;
            }
        }

        // If the key does not exist, create a new translation
        $formTranslation = new FormTranslationSpecification();
        $formTranslation->key = $key;
        $formTranslation->translation = $translation;
        $formTranslation->last_update = time();

        $translations->translations[] = $formTranslation;
    }

    public function removeTranslation(FormTranslationsSpecification &$translations, string $key): void
    {
        foreach ($translations->translations as $index => $formTranslation) {
            if ($formTranslation->key === $key) {
                unset($translations->translations[$index]);
                return;
            }
        }
    }

    public function getTranslationForKey(FormTranslationsSpecification $translations, string $key): ?string
    {
        foreach ($translations->translations as $formTranslation) {
            if ($formTranslation->key === $key) {
                return $formTranslation->translation;
            }
        }

        return null;
    }
}
