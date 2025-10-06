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

use DbTestCase;
use DropdownTranslation;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

class CategoryTest extends DbTestCase
{
    use FormTesterTrait;

    public static function formsTabNameProvider(): iterable
    {
        yield 'Category with no forms' => [
            'builders'  =>  [],
            'glpishow_count_on_tabs' => true,
            'expected' => 'Forms',
        ];

        yield 'Category with two forms' => [
            'builders'  =>  [
                new FormBuilder("My form 1"),
                new FormBuilder("My form 2"),
            ],
            'glpishow_count_on_tabs' => true,
            'expected' => 'Forms 2',
        ];

        yield 'Category with two forms (no count)' => [
            'builders'  =>  [
                new FormBuilder("My form 1"),
                new FormBuilder("My form 2"),
            ],
            'glpishow_count_on_tabs' => false,
            'expected' => 'Forms',
        ];
    }


    /** @param FormBuilder[] $builders */
    #[DataProvider('formsTabNameProvider')]
    public function testFormsTabName(
        array $builders,
        bool $glpishow_count_on_tabs,
        string $expected,
    ): void {
        // Arrange: create a category and associate it to the forms.
        $category = $this->createItem(Category::class, ['name' => 'My category']);
        foreach ($builders as $builder) {
            $builder->setCategory($category->getID());
            $this->createForm($builder);
        }

        $_SESSION['glpishow_count_on_tabs'] = $glpishow_count_on_tabs;

        // Act: get tab name
        $tab_name = (new Form())->getTabNameForItem($category);

        // Assert: compare tab name without html tags
        $this->assertEquals($expected, strip_tags($tab_name));
    }

    public function testFormsTabContent(): void
    {
        // Arrange: create multiples categories and forms.
        $category_1 = $this->createItem(Category::class, ['name' => 'My category 1']);
        $category_2 = $this->createItem(Category::class, ['name' => 'My category 2']);
        $this->createItems(Form::class, [
            ['name' => 'My form 1', 'forms_categories_id' => $category_1->getID()],
            ['name' => 'My form 2', 'forms_categories_id' => $category_1->getID()],
            ['name' => 'My form 3', 'forms_categories_id' => $category_2->getID()],
            ['name' => 'My form 4'],
        ]);

        // Act: get tab content for "My category 1"
        $this->login('glpi', 'glpi');
        ob_start();
        Form::displayTabContentForItem($category_1);
        $html = ob_get_clean();

        // Assert: only the two forms associated to "My category 1" must exist
        // in the returned content.
        $this->assertStringContainsString("My form 1", $html);
        $this->assertStringContainsString("My form 2", $html);
        $this->assertStringNotContainsString("My form 3", $html);
        $this->assertStringNotContainsString("My form 4", $html);
    }

    public function testServiceCatalogContentIsTranslated(): void
    {
        // Arrange: create a category with a translation
        $category = $this->createItem(Category::class, [
            'name' => 'My category',
            'description' => 'My description',
        ]);
        $this->translateCategory($category, 'fr_FR', 'Ma catégorie', 'Ma description');
        $this->translateCategory($category, 'es_ES', 'Mi categoría', 'Mi descripción');
        $this->createUserWithLang('en_GB');
        $this->createUserWithLang('fr_FR');
        $this->createUserWithLang('es_ES');
        $this->createUserWithLang('it_IT');

        // Act: fetch the category name in diffent languages
        $this->login('en_GB');
        $default = [
            'name' => $category->getServiceCatalogItemTitle(),
            'description' => $category->getServiceCatalogItemDescription(),
        ];

        $this->login('fr_FR');
        $fr_FR = [
            'name' => $category->getServiceCatalogItemTitle(),
            'description' => $category->getServiceCatalogItemDescription(),
        ];

        $this->login('es_ES');
        $es_ES = [
            'name' => $category->getServiceCatalogItemTitle(),
            'description' => $category->getServiceCatalogItemDescription(),
        ];

        $this->login('it_IT');
        $it_IT = [
            'name' => $category->getServiceCatalogItemTitle(),
            'description' => $category->getServiceCatalogItemDescription(),
        ];

        // Assert: validate the translations
        $this->assertEquals([
            'name' => 'My category',
            'description' => 'My description',
        ], $default);

        $this->assertEquals([
            'name' => 'Ma catégorie',
            'description' => 'Ma description',
        ], $fr_FR);

        $this->assertEquals([
            'name' => 'Mi categoría',
            'description' => 'Mi descripción',
        ], $es_ES);

        // No translation for italian
        $this->assertEquals([
            'name' => 'My category',
            'description' => 'My description',
        ], $it_IT);
    }

    private function translateCategory(
        Category $category,
        string $lang,
        string $title,
        string $description,
    ): void {
        $this->createItem(DropdownTranslation::class, [
            'items_id' => $category->getID(),
            'itemtype' => Category::class,
            'language' => $lang,
            'field'    => 'name',
            'value'    => $title,
        ]);
        $this->createItem(DropdownTranslation::class, [
            'items_id' => $category->getID(),
            'itemtype' => Category::class,
            'language' => $lang,
            'field'    => 'description',
            'value'    => $description,
        ]);
    }

    private function createUserWithLang(string $lang): void
    {
        $this->createItem(User::class, [
            'name'     => $lang,
            'language' => $lang,
            '_entities_id' => $this->getTestRootEntity(only_id: true),
            '_profiles_id' => 1,
        ]);
    }
}
