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

namespace tests\units;

use DbTestCase;
use ITILCategory;
use QueryExpression;

class DropdownTranslationTest extends DbTestCase
{
    private function completenameGenerationFakeProvider(): iterable
    {
        $this->login();

        $category = $this->createItem(
            ITILCategory::class,
            [
                'name' => 'Root category'
            ]
        );
        $sub_category = $this->createItem(
            ITILCategory::class,
            [
                'name' => 'Sub category',
                'itilcategories_id' => $category->getID(),
            ]
        );
        $sub_sub_category = $this->createItem(
            ITILCategory::class,
            [
                'name' => 'Sub sub category',
                'itilcategories_id' => $sub_category->getID(),
            ]
        );

        // Default value is always returned when there is no translation
        foreach ([$category->getID(), $sub_category->getID(), $sub_sub_category->getID()] as $category_id) {
            yield [
                'translations'  => [
                ],
                'category_id'   => $category_id,
                'language'      => 'fr_FR',
                'default_value' => 'Valeur par défaut',
                'result'        => 'Valeur par défaut',
            ];
        }

        yield [
            'translations'  => [
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Catégorie racine',
                ]
            ],
            'category_id'   => $category->getID(),
            'language'      => 'fr_FR',
            'default_value' => 'Valeur par défaut',
            'result'        => 'Catégorie racine',
        ];

        yield [
            'translations'  => [
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Catégorie racine',
                ]
            ],
            'category_id'   => $sub_category->getID(),
            'language'      => 'fr_FR',
            'default_value' => 'Valeur par défaut',
            'result'        => 'Catégorie racine > Sub category',
        ];

        yield [
            'translations'  => [
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $sub_category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Sous catégorie',
                ]
            ],
            'category_id'   => $sub_category->getID(),
            'language'      => 'fr_FR',
            'default_value' => 'Valeur par défaut',
            'result'        => 'Root category > Sous catégorie',
        ];

        yield [
            'translations'  => [
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Catégorie racine',
                ],
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $sub_category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Sous catégorie',
                ]
            ],
            'category_id'   => $sub_category->getID(),
            'language'      => 'fr_FR',
            'default_value' => 'Valeur par défaut',
            'result'        => 'Catégorie racine > Sous catégorie',
        ];

        yield [
            'translations'  => [
                [
                    'itemtype' => ITILCategory::class,
                    'items_id' => $sub_category->getID(),
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'Sous catégorie',
                ]
            ],
            'category_id'   => $sub_sub_category->getID(),
            'language'      => 'fr_FR',
            'default_value' => 'Valeur par défaut',
            'result'        => 'Root category > Sous catégorie > Sub sub category',
        ];
    }

    public function testgetTranslatedCompletename(): void
    {
        global $CFG_GLPI, $DB;
        $CFG_GLPI['translate_dropdowns'] = 1;

        $values = $this->completenameGenerationFakeProvider();
        foreach ($values as $value) {
            // Delete existing translations to prevent conflicts with tested data
            $DB->delete(\DropdownTranslation::getTable(), [new QueryExpression("true")]);

            $translations = $value['translations'];
            $category_id = $value['category_id'];
            $language = $value['language'];
            $default_value = $value['default_value'];
            $result = $value['result'];

            $this->createItems(\DropdownTranslation::class, $translations);

            foreach (['en_GB', 'fr_FR', 'es_ES'] as $session_language) {
                // Current session language should not affect result
                $_SESSION['glpilanguage'] = $session_language;
                $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($session_language);

                $this->assertEquals(
                    $result,
                    \DropdownTranslation::getTranslatedValue(
                        $category_id,
                        ITILCategory::class,
                        'completename',
                        $language,
                        $default_value
                    )
                );
            }
        }
    }
}
