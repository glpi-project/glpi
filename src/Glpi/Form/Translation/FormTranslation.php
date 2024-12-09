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

namespace Glpi\Form\Translation;

use CommonDBChild;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use Glpi\Form\Translation\Serializer\FormTranslationSerializer;
use LogicException;
use Override;
use Session;

final class FormTranslation extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Form translation', 'Form translations', $nb);
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-language";
    }

    #[Override]
    public static function canView(): bool
    {
        return Form::canView();
    }

    #[Override]
    public static function canCreate(): bool
    {
        return Form::canCreate();
    }

    #[Override]
    public static function canUpdate(): bool
    {
        return Form::canUpdate();
    }

    #[Override]
    public function getName($options = []): string
    {
        return Dropdown::getLanguageName($this->fields['language']);
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Form) {
            return self::createTabEntry(
                self::getTypeName(),
                countElementsInTable(self::getTable(), [Form::getForeignKeyField() => $item->getID()])
            );
        } elseif ($item instanceof FormTranslation) {
            return self::createTabEntry($item->getName());
        }

        return '';
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
        $serializer = new FormTranslationSerializer();
        $translations_spec = $serializer->getTranslationsFromJson(
            !empty($this->fields['translations']) ?
                $this->fields['translations']
                : '[]'
        );

        if (isset($input['translations']) && is_array($input['translations'])) {
            foreach ($input['translations'] ?? [] as $key => $translation) {
                if (empty($translation)) {
                    $serializer->removeTranslation($translations_spec, $key);
                } else {
                    $serializer->setTranslation($translations_spec, $key, $translation);
                }
            }

            $input['translations'] = $serializer->getJsonFromTranslations($translations_spec);
        }

        return $input;
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if ($item instanceof Form) {
            $translations = self::getFormTranslationsForForm($item->getID());
            $availableLanguages = self::getLanguagesCanBeAddedToTranslation($item->getID());
            TemplateRenderer::getInstance()->display('pages/admin/form/form_translations.html.twig', [
                'item'                => $item,
                'translations'        => $translations,
                'available_languages' => $availableLanguages,
            ]);

            return true;
        } elseif ($item instanceof FormTranslation) {
            // Retrieve good FormTranslation object
            $formTranslation = new self();
            if (!$formTranslation->getFromDB($tabnum)) {
                return false;
            }

            return $formTranslation->showForm($formTranslation->getID());
        }

        return false;
    }

    #[Override]
    public function defineTabs($options = [])
    {
        $tabs = [];
        $formTranslations = self::getFormTranslationsForForm($this->fields[Form::getForeignKeyField()]);
        foreach ($formTranslations as $formTranslation) {
            $index = $formTranslation->getID();
            $tabName = $this->getTabNameForItem($formTranslation);
            if ($this->getID() === $formTranslation->getID()) {
                $index = 'main';
                $tabs = array_merge([self::getType() . '$' . $index => $tabName], $tabs);
            } else {
                $tabs[self::getType() . '$' . $index] = $tabName;
            }
        }

        return $tabs;
    }

    #[Override]
    public function showForm($ID, array $options = [])
    {
        $form = $this->getItem();
        TemplateRenderer::getInstance()->display('pages/admin/form/form_translation.html.twig', [
            'form'             => $form,
            'can_update'       => self::canUpdate(),
            'form_translation' => $this,
        ]);

        return true;
    }

    /**
     * Get translations for a form
     *
     * @param int $form_id
     * @return array<FormTranslation>
     */
    public static function getFormTranslationsForForm(int $form_id): array
    {
        $formTranslation = new self();
        $formTranslations = $formTranslation->find([Form::getForeignKeyField() => $form_id]);

        return array_map(fn($id) => FormTranslation::getById($id), array_keys($formTranslations));
    }

    /**
     * Get remaining languages that can be added to a form translation
     *
     * @param int $form_id
     * @return array<string, string> List of languages (code => name)
     */
    public static function getLanguagesCanBeAddedToTranslation(int $form_id): array
    {
        $formTranslations = array_map(
            fn(FormTranslation $formTranslation) => $formTranslation->fields['language'],
            self::getFormTranslationsForForm($form_id)
        );

        return array_combine(
            array_diff(array_keys(Dropdown::getLanguages()), $formTranslations),
            array_map(
                fn($language) => Dropdown::getLanguageName($language),
                array_diff(array_keys(Dropdown::getLanguages()), $formTranslations)
            )
        );
    }

    public function getTranslatedPercentage(): int
    {
        $serializer = new FormTranslationSerializer();
        $translations_spec = $serializer->getTranslationsFromJson($this->fields['translations'] ?? '[]');

        /** @var Form $form */
        $form = $this->getItem();
        $totalHandlers = 0;
        $formTranslationsHandlers = $form->listFormTranslationsHandlers();
        array_walk_recursive(
            $formTranslationsHandlers,
            function () use (&$totalHandlers) {
                $totalHandlers++;
            }
        );

        $translated = count($translations_spec->translations);

        return $totalHandlers > 0 ? (int)(($translated / $totalHandlers) * 100) : 0;
    }

    public function getTranslationsToDo(): int
    {
        $serializer = new FormTranslationSerializer();
        $translations_spec = $serializer->getTranslationsFromJson($this->fields['translations'] ?? '[]');

        /** @var Form $form */
        $form = $this->getItem();
        $totalHandlers = 0;
        $formTranslationsHandlers = $form->listFormTranslationsHandlers();
        array_walk_recursive(
            $formTranslationsHandlers,
            function () use (&$totalHandlers) {
                $totalHandlers++;
            }
        );

        $translationsToDo = count($translations_spec->translations);

        return $totalHandlers - $translationsToDo;
    }

    public function getTranslationsToReview(): int
    {
        return 0;
    }

    public function getTranslationForKey(string $key): ?string
    {
        $serializer = new FormTranslationSerializer();
        $translations_spec = $serializer->getTranslationsFromJson($this->fields['translations'] ?? '[]');

        return $serializer->getTranslationForKey($translations_spec, $key);
    }

    public function getDefaultTranslation(int $form_id, string $key): ?string
    {
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new LogicException('Form not found');
        }

        foreach ($form->listFormTranslationsHandlers() as $handlers) {
            foreach ($handlers as $handler) {
                if ($handler->getKey() === $key) {
                    return $handler->getValue();
                }
            }
        }

        return null;
    }

    public static function getLocalizedTranslationForKey(int $form_id, string $key): ?string
    {
        $formTranslation = new self();
        if (
            !$formTranslation->getFromDBByCrit([
                Form::getForeignKeyField() => $form_id,
                'language'                 => Session::getLanguage()
            ])
        ) {
            return $formTranslation->getDefaultTranslation($form_id, $key);
        }

        return $formTranslation->getTranslationForKey($key) ?? $formTranslation->getDefaultTranslation($form_id, $key);
    }
}
