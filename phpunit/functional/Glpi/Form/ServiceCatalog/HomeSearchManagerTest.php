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

namespace tests\units\Glpi\Form\ServiceCatalog;

use Computer;
use ComputerType;
use DbTestCase;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\HomeSearchManager;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\ComputerForServiceCatalog;
use KnowbaseItem;
use Session;

final class HomeSearchManagerTest extends DbTestCase
{
    use FormTesterTrait;
    public function testFaqArticlesAreFound(): void
    {
        // Arrange: create some FAQ articles
        $not_faq = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'Not a FAQ',
            'answer'                  => 'My answer',
            'users_id'                => 2, // Important: not our current user
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            KnowbaseItem::getForeignKeyField() => $not_faq->getID(),
            Entity::getForeignKeyField()       => 0,
            'is_recursive'                     => true,
        ]);
        $faq = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'FAQ',
            'answer'                  => 'My answer',
            'is_faq'                  => 1,
            'users_id'                => 2, // Important: not our current user
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            KnowbaseItem::getForeignKeyField() => $faq->getID(),
            Entity::getForeignKeyField()       => 0,
            'is_recursive'                     => true,
        ]);

        // Act: get home search results
        $this->login('post-only');
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $results = HomeSearchManager::getInstance()->getItems($item_request);

        // Assert: only valid faq items should be found
        $kb_names = array_map(
            fn(KnowbaseItem $kb) => $kb->fields['name'],
            $results["FAQ articles"],
        );
        $this->assertEquals(["FAQ"], $kb_names);
    }

    public function testFormsAreFound(): void
    {
        // Arrange: create a mix of active/inactive forms
        $builders = [
            (new FormBuilder("Active form 1"))->setIsActive(true),
            (new FormBuilder("Active form 2"))->setIsActive(true),
            (new FormBuilder("Inactive form 1"))->setIsActive(false),
            (new FormBuilder("Inactive form 2"))->setIsActive(false),
            (new FormBuilder("Inactive form 3"))->setIsActive(false),
        ];
        foreach ($builders as $builder) {
            $this->createForm($builder);
        }

        // Act: get the forms
        $this->login('post-only');
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $results = HomeSearchManager::getInstance()->getItems($item_request);

        // Assert: only active forms must be found.
        $forms_names = array_map(
            fn(Form $f) => $f->fields['name'],
            $results["Forms"],
        );
        $this->assertEquals([
            "Active form 1",
            "Active form 2",
            "Report an issue",
            "Request a service",
        ], $forms_names);
    }

    public function testResultsAreSizeLimited(): void
    {
        // Arrange: create more than 20 forms
        for ($i = 0; $i < 30; $i++) {
            $builder = (new FormBuilder("Active form 1"))->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms
        $this->login('post-only');
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $results = HomeSearchManager::getInstance()->getItems($item_request);

        // Assert: only 20 results should be found
        $this->assertCount(20, $results["Forms"]);
    }

    public function testPluginCanRegisterProviders(): void
    {
        // Arrange: create some computers in the 'test' type
        $type = $this->createItem(ComputerType::class, [
            'name' => 'test',
        ]);
        $to_create = ['Computer 1', 'Computer 2', 'Computer 3'];
        foreach ($to_create as $name) {
            $this->createItem(Computer::class, [
                'name' => $name,
                'entities_id' => $this->getTestRootEntity(only_id: true),
                ComputerType::getForeignKeyField() => $type->getID(),
            ]);
        }

        // Act: get items
        $this->login('post-only');
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $results = HomeSearchManager::getInstance()->getItems($item_request);

        // Assert: computers should be found thanks to the ComputerProvider from
        // the tester plugin
        $names = array_map(
            fn(ComputerForServiceCatalog $c) => $c->getServiceCatalogItemTitle(),
            $results["Computers with the 'test' type"],
        );
        $this->assertEquals(['Computer 1', 'Computer 2', 'Computer 3'], $names);
    }
}
