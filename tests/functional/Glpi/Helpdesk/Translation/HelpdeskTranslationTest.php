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

namespace tests\units\Glpi\Form;

use Dropdown;
use Entity;
use Glpi\Helpdesk\HelpdeskTranslation;
use Glpi\Helpdesk\Tile\GlpiPageTile;
use Glpi\Helpdesk\Tile\Item_Tile;
use Glpi\Helpdesk\Tile\TilesManager;
use Session;

class HelpdeskTranslationTest extends \DbTestCase
{
    public function testGetLanguagesCanBeAddedToTranslation()
    {
        $this->initHelpdeskWithTranslations();

        $this->assertEquals(
            HelpdeskTranslation::getLanguagesCanBeAddedToTranslation(),
            array_diff_key(
                Dropdown::getLanguages(),
                ['fr_FR' => '', 'es_ES' => '']
            )
        );
    }

    public function testGetTranslationForKey()
    {
        $this->initHelpdeskWithTranslations();

        foreach (['fr_FR', 'es_ES'] as $language) {
            foreach ((new HelpdeskTranslation())->listTranslationsHandlers() as $handlers) {
                foreach ($handlers as $handler) {
                    $this->assertEquals(
                        $handler->getKey() . ' in ' . $language,
                        HelpdeskTranslation::getForItemKeyAndLanguage($handler->getItem(), $handler->getKey(), $language)
                            ->getTranslation()
                    );
                }
            }
        }
    }

    public function testTranslate()
    {
        global $CFG_GLPI;

        $this->initHelpdeskWithTranslations();

        $handlers = array_merge(...array_values((new HelpdeskTranslation())->listTranslationsHandlers()));

        // Set the default language
        $_SESSION['glpilanguage'] = $CFG_GLPI['language'];
        foreach ($handlers as $handler) {
            $this->assertEquals(
                HelpdeskTranslation::translate($handler->getItem(), $handler->getKey()),
                $handler->getValue()
            );
        }

        // Set the language to French
        $_SESSION['glpilanguage'] = 'fr_FR';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                $handler->getKey() . ' in ' . Session::getLanguage(),
                HelpdeskTranslation::translate($handler->getItem(), $handler->getKey())
            );
        }

        // Set the language to Spanish
        $_SESSION['glpilanguage'] = 'es_ES';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                $handler->getKey() . ' in ' . Session::getLanguage(),
                HelpdeskTranslation::translate($handler->getItem(), $handler->getKey())
            );
        }

        // Set the language to Portuguese to test the fallback to the default language
        $_SESSION['glpilanguage'] = 'pt_PT';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                $handler->getValue(),
                HelpdeskTranslation::translate($handler->getItem(), $handler->getKey())
            );
        }
    }

    public function testTranslationsCascadeDeleteWhenDeletingTranslatableElements()
    {
        $this->initHelpdeskWithTranslations();

        // Get all translatable handlers before deletion
        $handlers = (new HelpdeskTranslation())->listTranslationsHandlers();
        $translation_items = [];

        // Collect all translated items
        array_walk_recursive(
            $handlers,
            function ($handler) use (&$translation_items) {
                $translations = HelpdeskTranslation::getTranslationsForItem($handler->getItem());
                if (!empty($translations)) {
                    $item = $handler->getItem();
                    $translation_items[$item->getType() . $item->getID()] = $item;
                }
            }
        );

        // Verify that we have translations before deletion
        $this->assertNotEmpty($translation_items, 'No translations found to test cascade deletion');

        foreach ($translation_items as $item) {
            // Delete the item
            $success = $item->delete(['id' => $item->getID()], true); // Force purge
            $this->assertTrue($success, "Failed to delete item with ID " . $item->getID());

            // Verify that all translations have been cascade deleted
            $remaining_translations = HelpdeskTranslation::getTranslationsForItem($item);
            $this->assertEmpty(
                $remaining_translations,
                "Translations were not cascade deleted for {$item->getType()} {$item->getID()}. Found " . count($remaining_translations) . " remaining translations."
            );

            // Also verify by direct database query
            $translation_count = countElementsInTable(
                HelpdeskTranslation::getTable(),
                [
                    HelpdeskTranslation::$itemtype => $item->getType(),
                    HelpdeskTranslation::$items_id => $item->getID(),
                ]
            );
            $this->assertEquals(
                0,
                $translation_count,
                "Database still contains {$translation_count} translations for deleted {$item->getType()} {$item->getID()}"
            );
        }
    }

    public function initHelpdeskWithTranslations(): void
    {
        $this->login();

        // Remove existing tiles
        $tilesManager = TilesManager::getInstance();
        array_map(fn($tile) => $tilesManager->deleteTile($tile), $tilesManager->getAllTiles());

        $entity = $this->createItem(Entity::class, [
            'name'         => 'Test Root Entity',
            'entities_id'  => $this->getTestRootEntity(true),
        ]);

        // Use custom helpdesk title
        $this->updateItem(
            Entity::class,
            $entity->getID(),
            [
                'custom_helpdesk_home_title' => Entity::HELPDESK_TITLE_CUSTOM,
                '_custom_helpdesk_home_title' => 'Custom Helpdesk Title',
            ],
            ['custom_helpdesk_home_title']
        );

        // Add tiles
        $glpi_tile = $this->createItem(
            GlpiPageTile::class,
            [
                'title' => 'Test Tile',
                'description' => 'This is a test tile',
            ]
        );

        $this->createItem(
            Item_Tile::class,
            [
                'itemtype_item' => Entity::class,
                'items_id_item' => $entity->getID(),
                'itemtype_tile' => GlpiPageTile::class,
                'items_id_tile' => $glpi_tile->getID(),
            ]
        );

        // Add translations for the custom helpdesk title
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => Entity::class,
                'items_id'     => $entity->getID(),
                'language'     => 'fr_FR',
                'key'          => Entity::TRANSLATION_KEY_CUSTOM_HELPDESK_HOME_TITLE,
                'translations' => ['one' => 'custom_helpdesk_home_title in fr_FR'],
            ],
            ['translations']
        );
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => Entity::class,
                'items_id'     => $entity->getID(),
                'language'     => 'es_ES',
                'key'          => Entity::TRANSLATION_KEY_CUSTOM_HELPDESK_HOME_TITLE,
                'translations' => ['one' => 'custom_helpdesk_home_title in es_ES'],
            ],
            ['translations']
        );

        // Add translations for the tile
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => GlpiPageTile::class,
                'items_id'     => $glpi_tile->getID(),
                'language'     => 'fr_FR',
                'key'          => 'title',
                'translations' => ['one' => 'title in fr_FR'],
            ],
            ['translations']
        );
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => GlpiPageTile::class,
                'items_id'     => $glpi_tile->getID(),
                'language'     => 'fr_FR',
                'key'          => 'description',
                'translations' => ['one' => 'description in fr_FR'],
            ],
            ['translations']
        );
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => GlpiPageTile::class,
                'items_id'     => $glpi_tile->getID(),
                'language'     => 'es_ES',
                'key'          => 'title',
                'translations' => ['one' => 'title in es_ES'],
            ],
            ['translations']
        );
        $this->createItem(
            HelpdeskTranslation::class,
            [
                'itemtype'     => GlpiPageTile::class,
                'items_id'     => $glpi_tile->getID(),
                'language'     => 'es_ES',
                'key'          => 'description',
                'translations' => ['one' => 'description in es_ES'],
            ],
            ['translations']
        );
    }
}
