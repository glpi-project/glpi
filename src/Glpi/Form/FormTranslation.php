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

namespace Glpi\Form;

use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\ItemTranslation\ItemTranslation;
use Override;

final class FormTranslation extends ItemTranslation
{
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
                countDistinctElementsInTable(
                    static::getTable(),
                    'language',
                    ['items_id' => $item->getID(), 'itemtype' => $item->getType()]
                ),
                0
            );
        } elseif ($item instanceof FormTranslation) {
            return self::createTabEntry($item->getName());
        }

        return '';
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if ($item instanceof Form) {
            $translations = array_reduce(
                self::getTranslationsForItem($item),
                fn($carry, $translation) => $carry + [$translation->fields['language'] => $translation],
                []
            );
            $available_languages = self::getLanguagesCanBeAddedToTranslation($item->getID());
            TemplateRenderer::getInstance()->display('pages/admin/form/form_translations.html.twig', [
                'item'                => $item,
                'translations'        => $translations,
                'available_languages' => $available_languages,
            ]);

            return true;
        } elseif ($item instanceof FormTranslation) {
            // Retrieve good FormTranslation object
            $form_translation = new self();
            if (!$form_translation->getFromDB($tabnum)) {
                return false;
            }

            return $form_translation->showForm($form_translation->getID());
        }

        return false;
    }

    #[Override]
    public function defineTabs($options = [])
    {
        $tabs = [];
        $form_translations = array_reduce(
            self::getTranslationsForItem(Form::getById($this->fields[static::$items_id])),
            fn($carry, $translation) => $carry + [$translation->fields['language'] => $translation],
            []
        );
        foreach ($form_translations as $form_translation) {
            $index = $form_translation->getID();
            $tabName = $this->getTabNameForItem($form_translation);
            if ($this->fields['language'] === $form_translation->fields['language']) {
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
        if (!($form instanceof Form)) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/admin/form/form_translation.html.twig', [
            'form'             => $form,
            'can_update'       => self::canUpdate(),
            'form_translation' => $this,
        ]);

        return true;
    }

    /**
     * Get remaining languages that can be added to a form translation
     *
     * @param int $form_id
     * @return array<string, string> List of languages (code => name)
     */
    public static function getLanguagesCanBeAddedToTranslation(int $form_id): array
    {
        $form_translations = array_map(
            fn(ItemTranslation $translation) => $translation->fields['language'],
            self::getTranslationsForItem(Form::getById($form_id))
        );

        return array_combine(
            array_diff(array_keys(Dropdown::getLanguages()), $form_translations),
            array_map(
                fn($language) => Dropdown::getLanguageName($language),
                array_diff(array_keys(Dropdown::getLanguages()), $form_translations)
            )
        );
    }
}
