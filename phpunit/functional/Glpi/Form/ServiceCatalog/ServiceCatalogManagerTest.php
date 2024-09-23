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

use AbstractRightsDropdown;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

final class ServiceCatalogManagerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static ServiceCatalogManager $manager;

    public static function setUpBeforeClass(): void
    {
        self::$manager = new ServiceCatalogManager();
        parent::setUpBeforeClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        // Disable default forms to not pollute the tests of this file
        $this->disableExistingForms();
    }

    public function testOnlyActiveFormsAreDisplayed(): void
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
            $builder->allowAllUsers();
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: only active forms must be found
        $this->assertEquals([
            "Active form 1",
            "Active form 2",
        ], $forms_names);
    }

    public function testFormsAreOrderedByNames(): void
    {
        // Arrange: create forms with unordered names
        $builders = [
            new FormBuilder("ZZZ"),
            new FormBuilder("AAA"),
            new FormBuilder("CCC"),
        ];
        foreach ($builders as $builder) {
            $builder->allowAllUsers();
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: forms must be ordered by name
        $this->assertEquals([
            "AAA",
            "CCC",
            "ZZZ",
        ], $forms_names);
    }

    public function testFormsNamesAreUniques(): void
    {
        // Arrange: create forms with duplicated names
        $builders = [
            new FormBuilder("My form"),
            new FormBuilder("My form"),
            new FormBuilder("My other form"),
            new FormBuilder("My form"),
        ];
        foreach ($builders as $builder) {
            $builder->allowAllUsers();
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: names must be uniques
        $this->assertEquals([
            "My form",
            "My form (1)",
            "My form (2)",
            "My other form",
        ], $forms_names);
    }

    public function testFormsWithActiveAccessPoliciesAreFound(): void
    {
        // Arrange: create a form with an active policy
        $builder = new FormBuilder("Form with active policy");
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            is_active: true,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: our form must be found
        $this->assertEquals(["Form with active policy"], $forms_names);
    }

    public function testFormWithoutAccessPoliciesAreNotFound(): void
    {
        // Arrange: create a form without any policies
        $builder = new FormBuilder("Form without policies");
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: our form must not be found
        $this->assertEquals([], $forms_names);
    }

    public function testFormWithInactiveAccessPoliciesAreNotFound(): void
    {
        // Arrange: create a form with an inactive policy
        $builder = new FormBuilder("Form with inactive policy");
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            is_active: false,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters);
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);

        // Assert: our form must not be found
        $this->assertEquals([], $forms_names);
    }

    public static function onlyFormThatMatchAllowListCriteriaAreFoundProvider(): iterable
    {
        $allow_list = new AllowListConfig(
            user_ids: [
                getItemByTypeName(User::class, "tech", true),
                getItemByTypeName(User::class, "normal", true),
            ],
        );
        yield 'glpi' => [
            'config'   => $allow_list,
            'user'     => 'glpi',
            'expected' => false,
        ];
        yield 'tech' => [
            'config'   => $allow_list,
            'user'     => 'tech',
            'expected' => true,
        ];
        yield 'normal' => [
            'config'   => $allow_list,
            'user'     => 'normal',
            'expected' => true,
        ];
        yield 'post-only' => [
            'config'   => $allow_list,
            'user'     => 'post-only',
            'expected' => false,
        ];
    }

    #[DataProvider('onlyFormThatMatchAllowListCriteriaAreFoundProvider')]
    public function testOnlyFormThatMatchAllowListCriteriaAreFound(
        AllowListConfig $config,
        string $user,
        bool $expected,
    ): void {
        // Arrange: create a form with an allow list
        $builder = new FormBuilder();
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: $config,
            is_active: true,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: as the specified user, get the number of forms from the catalog manager
        $session_info = new SessionInfo(
            user_id: getItemByTypeName(User::class, $user, true),
        );
        $access_parameters = new FormAccessParameters($session_info, []);
        $forms = self::$manager->getForms($access_parameters);
        $nb_forms = count($forms);

        // Assert: list should be empty if we don't expect the user to see the form
        $this->assertEquals($expected, $nb_forms === 1);
    }

    public static function formsCanBeFilteredProvider(): iterable
    {
        // Simple tests cases using string comparison
        $forms_data = [
            ['name' => "Red form", 'description'  => 'My first description'],
            ['name' => "Blue form", 'description' => 'My second description'],
            ['name' => "Pink form", 'description' => 'My third description'],
        ];
        yield 'Simple comparison using name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Blue",
            'expected_forms_names' => ["Blue form"],
        ];
        yield 'Simple comparison using name (case insensitive)' => [
            'forms_data'           => $forms_data,
            'filter'               => "pInK",
            'expected_forms_names' => ["Pink form"],
        ];
        yield 'Simple contains filter on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "third",
            'expected_forms_names' => ["Pink form"],
        ];
        yield 'Simple contains filter on description (case insensitive)' => [
            'forms_data'           => $forms_data,
            'filter'               => "fIrSt",
            'expected_forms_names' => ["Red form"],
        ];

        // Test using fuzzy search
        $forms_data = [
            ['name' => "Fruits form", 'description'  => 'Banana, apple, orange'],
            ['name' => "Vegetable form", 'description' => 'Carrot, tomato, cucumber'],
        ];
        yield 'Partial match on name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Fruit form",
            'expected_forms_names' => ["Fruits form"],
        ];
        yield 'Invalid partial match on name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Frui and veg form",
            'expected_forms_names' => [],
        ];
        yield 'Partial match on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "banana orange",
            'expected_forms_names' => ["Fruits form"],
        ];
        yield 'Invalid partial match on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "banana lemon orange",
            'expected_forms_names' => [],
        ];
        yield 'Partial match on name with spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "Vegetoble form",
            'expected_forms_names' => ["Vegetable form"],
        ];
        yield 'Partial match on description with spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "cuccummber",
            'expected_forms_names' => ["Vegetable form"],
        ];
        yield 'Too many spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "Bonanonano",
            'expected_forms_names' => [],
        ];
    }

    #[DataProvider('formsCanBeFilteredProvider')]
    public function testFormsCanBeFiltered(
        array $forms_data,
        string $filter,
        array $expected_forms_names,
    ): void {
        // Arrange: create a form with the specified names
        foreach ($forms_data as $form_data) {
            $builder = new FormBuilder($form_data['name']);
            $builder->setDescription($form_data['description']);
            $builder->allowAllUsers();
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: filter the forms
        $access_parameters = $this->getDefaultParametersForTestUser();
        $forms = self::$manager->getForms($access_parameters, $filter);

        // Assert: only the expected forms must be found
        $forms_names = array_map(fn (Form $form) => $form->fields['name'], $forms);
        $this->assertEquals($expected_forms_names, $forms_names);
    }
}
