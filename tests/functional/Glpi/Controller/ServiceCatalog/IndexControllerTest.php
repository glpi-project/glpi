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

namespace tests\units\Glpi\Controller\ServiceCatalog;

use Entity;
use Glpi\Controller\ServiceCatalog\IndexController;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\SortStrategy\SortStrategyEnum;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Symfony\Component\HttpFoundation\Request;

final class IndexControllerTest extends DbTestCase
{
    use FormTesterTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->disableExistingForms();
    }

    public function testEntitySortStrategyIsAppliedOnInitialLoad(): void
    {
        // Arrange: configure entity with alphabetical sort strategy (not the default)
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $this->updateItem(Entity::class, $entity_id, [
            'enable_helpdesk_service_catalog'       => 1,
            'service_catalog_default_sort_strategy' => SortStrategyEnum::ALPHABETICAL->value,
        ]);

        // Create forms and assign usage_count so that popularity order (ZZZ first)
        // is the inverse of alphabetical order (AAA first). This ensures the two
        // sort strategies produce distinguishable results.
        $forms = [
            'ZZZ Form' => 100,
            'MMM Form' => 50,
            'AAA Form' => 1,
        ];
        foreach ($forms as $name => $usage_count) {
            $form = $this->createForm((new FormBuilder($name))->setIsActive(true));
            $this->updateItem(Form::class, $form->getID(), ['usage_count' => $usage_count]);
        }

        // Act: invoke the controller directly (simulates the initial page load)
        $this->login();
        $request = Request::create('/ServiceCatalog', 'GET');
        $controller = new IndexController();
        $response = $controller->__invoke($request);

        // Assert: forms must appear in alphabetical order (not popularity order)
        $content = $response->getContent();
        $pos_aaa = strpos($content, 'AAA Form');
        $pos_mmm = strpos($content, 'MMM Form');
        $pos_zzz = strpos($content, 'ZZZ Form');

        $this->assertNotFalse($pos_aaa, 'AAA Form should be present in the response');
        $this->assertNotFalse($pos_mmm, 'MMM Form should be present in the response');
        $this->assertNotFalse($pos_zzz, 'ZZZ Form should be present in the response');

        $this->assertLessThan($pos_mmm, $pos_aaa, 'AAA Form must appear before MMM Form');
        $this->assertLessThan($pos_zzz, $pos_mmm, 'MMM Form must appear before ZZZ Form');
    }
}
