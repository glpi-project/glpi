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

namespace tests\units\Glpi\Form;

use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class ServiceCatalogManagerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static ServiceCatalogManager $manager;

    public static function setUpBeforeClass(): void
    {
        self::$manager = new ServiceCatalogManager();
        parent::setUpBeforeClass();
    }

    public function testOnlyActiveFormsAreDisplayed(): void
    {
        // Arrange
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

        // Act
        $forms = self::$manager->getForms();
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert
        $this->assertEquals([
            "Active form 1",
            "Active form 2",
        ], $forms_names);
    }

    public function testFormsAreOrderedByNames(): void
    {
        // Arrange
        $builders = [
            (new FormBuilder("ZZZ"))->setIsActive(true),
            (new FormBuilder("AAA"))->setIsActive(true),
            (new FormBuilder("CCC"))->setIsActive(true),
        ];
        foreach ($builders as $builder) {
            $this->createForm($builder);
        }

        // Act
        $forms = self::$manager->getForms();
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert
        $this->assertEquals([
            "AAA",
            "CCC",
            "ZZZ",
        ], $forms_names);
    }

    public function testFormsNamesAreUniques(): void
    {
        // Arrange
        $builders = [
            (new FormBuilder("My form"))->setIsActive(true),
            (new FormBuilder("My form"))->setIsActive(true),
            (new FormBuilder("My other form"))->setIsActive(true),
            (new FormBuilder("My form"))->setIsActive(true),
        ];
        foreach ($builders as $builder) {
            $this->createForm($builder);
        }

        // Act
        $forms = self::$manager->getForms();
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert
        $this->assertEquals([
            "My form",
            "My form (1)",
            "My form (2)",
            "My other form",
        ], $forms_names);
    }
}
