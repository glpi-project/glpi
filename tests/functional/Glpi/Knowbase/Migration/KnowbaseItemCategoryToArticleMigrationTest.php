<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Knowbase\Migration;

use Glpi\Tests\GLPITestCase;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Migration;
use PHPUnit\Framework\Attributes\DataProvider;

final class KnowbaseItemCategoryToArticleMigrationTest extends GLPITestCase
{
    private const CATEGORY_NAME = 'Migrated category';

    public function tearDown(): void
    {
        global $DB;

        // The migration runs DDL, so no test transaction is used.
        // Thus, remove the migrated data manually.
        $kb_ids = array_column(iterator_to_array($DB->request([
            'SELECT' => 'id',
            'FROM'   => KnowbaseItem::getTable(),
            'WHERE'  => ['name' => self::CATEGORY_NAME],
        ])), 'id');
        if ($kb_ids !== []) {
            $DB->delete(KnowbaseItemTranslation::getTable(), ['knowbaseitems_id' => $kb_ids]);
            $DB->delete(KnowbaseItem::getTable(), ['id' => $kb_ids]);
        }
        $DB->delete('glpi_dropdowntranslations', ['itemtype' => 'KnowbaseItemCategory']);
        $DB->dropTable('glpi_knowbaseitemcategories', true);
        $DB->clearSchemaCache();

        parent::tearDown();
    }

    public static function categoryDatesProvider(): iterable
    {
        yield 'category with dates' => [
            'date_creation' => '2020-01-01 00:00:00',
            'date_mod'      => '2021-06-01 00:00:00',
        ];
        // Null dates are valid, they must not be replaced
        yield 'category without dates' => [
            'date_creation' => null,
            'date_mod'      => null,
        ];
    }

    #[DataProvider('categoryDatesProvider')]
    public function testCategoryTranslationKeepsNullDates(?string $date_creation, ?string $date_mod): void
    {
        global $DB;

        // Arrange: a 11.0 category with a name translation (11.0 schema)
        $DB->doQuery(
            "CREATE TABLE `glpi_knowbaseitemcategories` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `entities_id` int unsigned NOT NULL DEFAULT '0',
                `is_recursive` tinyint NOT NULL DEFAULT '0',
                `knowbaseitemcategories_id` int unsigned NOT NULL DEFAULT '0',
                `name` varchar(255) DEFAULT NULL,
                `completename` text,
                `comment` text,
                `level` int NOT NULL DEFAULT '0',
                `sons_cache` longtext,
                `ancestors_cache` longtext,
                `date_mod` timestamp NULL DEFAULT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`entities_id`,`knowbaseitemcategories_id`,`name`),
                KEY `name` (`name`),
                KEY `is_recursive` (`is_recursive`),
                KEY `date_mod` (`date_mod`),
                KEY `date_creation` (`date_creation`),
                KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
                KEY `level` (`level`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
        $DB->insert('glpi_knowbaseitemcategories', [
            'entities_id'   => $this->getTestRootEntity(only_id: true),
            'is_recursive'  => 1,
            'name'          => self::CATEGORY_NAME,
            'completename'  => self::CATEGORY_NAME,
            'level'         => 1,
            'date_creation' => $date_creation,
            'date_mod'      => $date_mod,
        ]);
        $DB->insert('glpi_dropdowntranslations', [
            'items_id' => $DB->insertId(),
            'itemtype' => 'KnowbaseItemCategory',
            'language' => 'fr_FR',
            'field'    => 'name',
            'value'    => 'Catégorie migrée',
        ]);

        // Act
        $migration = new Migration('12.0.0');
        require GLPI_ROOT . '/install/migrations/update_11.0.x_to_12.0.0/knowbaseitemcategory_to_article.php';

        // Assert: the article keeps the category dates, null included
        $kb = getItemByTypeName(KnowbaseItem::class, self::CATEGORY_NAME);
        $this->assertSame($date_creation, $kb->fields['date_creation']);
        $this->assertSame($date_mod, $kb->fields['date_mod']);

        // Assert: the source has no dates, so the translation dates stay null
        $translation = new KnowbaseItemTranslation();
        $this->assertTrue($translation->getFromDBByCrit([
            'knowbaseitems_id' => $kb->getID(),
            'language'         => 'fr_FR',
        ]));
        $this->assertSame('Catégorie migrée', $translation->fields['name']);
        $this->assertNull($translation->fields['date_creation']);
        $this->assertNull($translation->fields['date_mod']);
    }
}
