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

// Generic test classe, to be extended for CommonDBTM Object

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity;
use Glpi\Asset\CapacityConfig;
use Glpi\Dropdown\DropdownDefinition;

class DbTestCase extends GLPITestCase
{
    /**
     * Indicates whether the custom assets autoloader is registered.
     * @var boolean
     */
    private $is_asset_autoloader_registered = false;

    public function setUp(): void
    {
        global $DB;
        $DB->beginTransaction();
        parent::setUp();
    }

    public function tearDown(): void
    {
        global $DB;
        $DB->rollback();

        // All transactions should be closed when a test is complete.
        // If a transaction remains active, it mean there is a code somewhere
        // that started it but forgot to commit/rollback.
        $in_transaction = $this->callPrivateMethod($DB, 'isInTransaction');
        $this->assertFalse($in_transaction);

        parent::tearDown();
    }


    /**
     * Connect (using the test user per default)
     *
     * @param string $user_name User name (defaults to TU_USER)
     * @param string $user_pass user password (defaults to TU_PASS)
     * @param bool $noauto disable autologin (from CAS by example)
     * @param bool $expected bool result expected from login return
     *
     * @return Auth
     */
    protected function realLogin(
        string $user_name = TU_USER,
        string $user_pass = TU_PASS,
        bool $noauto = true,
        bool $expected = true
    ): Auth {
        Session::destroy();
        Session::start();

        $auth = new Auth();
        $this->assertEquals($expected, $auth->login($user_name, $user_pass, $noauto));

        return $auth;
    }

    /**
     * "Fake" login process (for performances reason), if you need a real login
     * use the realLogin() method instead.
     *
     * The rule engine will not be executed.
     *
     * A session will be loaded for the supplied user without checking if his
     * account is valid.
     *
     * The "user_pass" parameter is no longer used but was not removed to
     * avoid needing to update all the occurences in existings tests.
     */
    protected function login(
        string $user_name = TU_USER,
        string $user_pass = "",
    ): Auth {
        Session::destroy();
        Session::start();

        $auth = new Auth();
        $auth->user = getItemByTypeName(User::class, $user_name);
        $auth->auth_succeded = true;
        Session::init($auth);

        return $auth;
    }

    /**
     * Log out current user
     *
     * @return void
     */
    protected function logOut()
    {
        Session::destroy();
        Session::start();
    }

    /**
     * change current entity
     *
     * @param int|string $entityname Name of the entity (or its id)
     * @param boolean $subtree   Recursive load
     *
     * @return void
     */
    protected function setEntity($entityname, $subtree)
    {
        $entity_id = is_int($entityname) ? $entityname : getItemByTypeName('Entity', $entityname, true);
        $res = Session::changeActiveEntities($entity_id, $subtree);
        $this->assertTrue($res);
    }

    /**
     * Assert added object is inserted as expected (reload the object from DB)
     *
     * @param  CommonDBTM $object The object to test
     * @param  int        $id     The id of added object
     * @param  array      $input  the input used for add object (optionnal)
     *
     * @return void
     */
    protected function checkInput(CommonDBTM $object, $id = 0, $input = [])
    {
        $this->assertGreaterThan($object instanceof Entity ? -1 : 0, (int) $id);
        $this->assertTrue($object->getFromDB($id));
        $this->assertEquals($id, $object->getID());

        if (count($input)) {
            foreach ($input as $k => $v) {
                $this->assertArrayHasKey(
                    $k,
                    $object->fields,
                    "Object not created as expected, field '$k' not found in object " . get_class($object),
                );

                if (is_array($v)) {
                    $this->assertArraysEqualRecursive($v, $object->fields[$k]);
                } else {
                    $this->assertEquals(
                        $v,
                        $object->fields[$k],
                        "
                    Object not created as expected
                    field '$k' value is '{$object->fields[$k]}' (" . gettype($object->fields[$k]) . ")
                    but was expected to be '$v' (" . gettype($v) . ")"
                    );
                }
            }
        }
    }

    /**
     * Get classes in src/
     *
     * Get classes implementing the specified $function, or all classes if $function is false,
     * excluding classnames matching a pattern in $excludes
     *
     * @param string|false $function Whether to look for a function
     * @param array $excludes List of classes to exclude
     *
     * @return array<int, class-string>
     */
    protected static function getClasses($function = false, array $excludes = [])
    {
        $files_iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(GLPI_ROOT . '/src'),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $classes = [];
        foreach ($files_iterator as $fileInfo) {
            if ($fileInfo->getExtension() !== 'php') {
                continue;
            }

            $classname = $fileInfo->getBasename('.php');

            $is_excluded = false;
            foreach ($excludes as $exclude) {
                if ($classname === $exclude || @preg_match($exclude, $classname) === 1) {
                    $is_excluded = true;
                    break;
                }
            }
            if ($is_excluded) {
                continue;
            }

            if (!class_exists($classname)) {
                continue;
            }
            $reflectionClass = new ReflectionClass($classname);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            if ($function) {
                if (method_exists($classname, $function)) {
                    $classes[] = $classname;
                }
            } else {
                $classes[] = $classname;
            }
        }
        return array_unique($classes);
    }

    /**
     * Create an item of the given class and check data of created object are same as given in input.
     *
     * Fields starting with _ and fields provided in $skip_fields are ignored in the checking.
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $input
     * @param array $skip_fields Fields that won't be checked after creation
     *
     * @return T
     */
    protected function createItem($itemtype, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $item->add($input);
        $id = $item->getID();
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id, 'Failed to create item ' . $itemtype . ' with input ' . json_encode($input));

        // Remove special fields
        $skip_fields[] = 'id';
        $input = array_filter($input, function ($key) use ($skip_fields) {
            return !in_array($key, $skip_fields) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->checkInput($item, $id, $input);

        return $item;
    }

    /**
     * Update an item and checks it's data are same as given in input.
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $input
     * @param array $skip_fields Fields that wont be checked after creation
     * @return T The updated item
     */
    protected function updateItem($itemtype, $id, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->update($input);
        $this->assertTrue($success, 'Failed to update item ' . $itemtype . ' with id ' . $id . ' and input ' . json_encode($input));

        // Remove special fields
        $input = array_filter($input, function ($key) use ($skip_fields) {
            return !in_array($key, $skip_fields) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->checkInput($item, $id, $input);

        return $item;
    }

    /**
     * Create multiples items of the given class
     *
     * @param string $itemtype
     * @param array $inputs
     *
     * @return array created items
     */
    protected function createItems($itemtype, $inputs): array
    {
        $items = [];
        foreach ($inputs as $input) {
            $items[] = $this->createItem($itemtype, $input);
        }

        return $items;
    }

    /**
     * Delete an item of the given class
     *
     * @param string $itemtype
     * @param int $id
     * @param bool $purge
     *
     * @return void
     */
    protected function deleteItem($itemtype, $id, bool $purge = false): void
    {
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->delete($input, $purge);
        $this->assertTrue($success);
    }

    /**
     * Delete multiple items of the given class
     *
     * @param int[] $ids
     */
    protected function deleteItems(string $itemtype, array $ids, bool $purge = false): void
    {
        foreach ($ids as $id) {
            $this->deleteItem($itemtype, $id, $purge);
        }
    }

    /**
     * Helper methods to quickly create many items of the same type.
     *
     * @param array[] $names
     * @return CommonDBTM[]
     */
    protected function createItemsWithNames(string $itemtype, array $names): array
    {
        return array_map(
            fn($name) => $this->createItem($itemtype, ['name' => $name]),
            $names,
        );
    }

    /**
     * Helper methods to quickly get the names of multiple items using their ids.
     *
     * @param int[] $ids
     * @return string[]
     */
    protected function getItemsNames(string $itemtype, array $ids): array
    {
        return array_map(
            fn($id) => $itemtype::getById($id)->fields['name'],
            $ids,
        );
    }

    /**
     * Adds a new rule
     *
     * @param string $name     New rule name
     * @param array  $criteria Rule criteria
     * @param array  $action   Rule action
     *
     * @return int
     */
    protected function addRule(string $type, string $name, array $criteria, array $action, ?int $ranking = null): int
    {
        $builder = new RuleBuilder($name, $type);
        if ($ranking !== null) {
            $builder->setRanking($ranking);
        }

        // Add criteria
        foreach ($criteria as $crit) {
            $builder->addCriteria($crit['criteria'], $crit['condition'], $crit['pattern']);
        }

        // Add action
        $builder->addAction($action['action_type'], $action['field'], $action['value']);

        $rule = $this->createRule($builder);
        return $rule->getID();
    }

    /**
     * Helper method to avoid writing the same boilerplate code for rule creation
     *
     * @param RuleBuilder $builder RuleConfiguration
     *
     * @return Rule Created rule
     */
    protected function createRule(RuleBuilder $builder): Rule
    {
        $input = [
            'is_active'    => 1,
            'sub_type'     => $builder->getRuleType(),
            'name'         => $builder->getName(),
            'match'        => $builder->getOperator(),
            'condition'    => $builder->getCondition(),
            'is_recursive' => $builder->isRecursive(),
            'entities_id'  => $builder->getEntity(),
        ];
        if ($ranking = $builder->getRanking()) {
            $input['ranking'] = $ranking;
        }
        $rule = $this->createItem(Rule::class, $input);

        foreach ($builder->getCriteria() as $criterion) {
            $this->createItem(RuleCriteria::class, [
                'rules_id'  => $rule->getID(),
                'criteria'  => $criterion['criteria'],
                'condition' => $criterion['condition'],
                'pattern'   => $criterion['pattern'],
            ]);
        }

        foreach ($builder->getActions() as $criterion) {
            $this->createItem(RuleAction::class, [
                'rules_id'    => $rule->getID(),
                'action_type' => $criterion['action_type'],
                'field'       => $criterion['field'],
                'value'       => $criterion['value'],
            ]);
        }

        return $rule;
    }

    /**
     * Register the custom asset autoloader. This autoloader is not available by default in the testing context.
     */
    protected function registerAssetsAutoloader(): void
    {
        if (!$this->is_asset_autoloader_registered) {
            AssetDefinitionManager::getInstance()->registerAutoload();
            $this->is_asset_autoloader_registered = true;
        }
    }

    /**
     * Initialize a definition.
     *
     * @param ?string $system_name
     * @param Capacity[] $capacities
     * @param ?array $profiles
     *
     * @return AssetDefinition
     */
    protected function initAssetDefinition(
        ?string $system_name = null,
        array $capacities = [],
        ?array $profiles = null,
    ): AssetDefinition {
        $this->registerAssetsAutoloader();

        if ($profiles === null) {
            // Initialize with all standard rights for super admin profile
            $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
            $profiles = [
                $superadmin_p_id => ALLSTANDARDRIGHT,
            ];
        }

        $capacity_input = array_map(
            fn(Capacity $capacity) => $capacity->jsonSerialize(),
            $capacities
        );

        $definition = $this->createItem(
            AssetDefinition::class,
            [
                'system_name' => $system_name ?? $this->getUniqueString(),
                'is_active'   => true,
                'capacities'  => $capacity_input,
                'profiles'    => $profiles,
                'fields_display' => [],
            ],
            skip_fields: ['capacities', 'profiles', 'fields_display'] // JSON encoded fields cannot be automatically checked
        );

        $this->assertEqualsCanonicalizing(
            $capacities,
            array_values($this->callPrivateMethod($definition, 'getDecodedCapacitiesField'))
        );
        $this->assertEqualsCanonicalizing(
            $profiles,
            $this->callPrivateMethod($definition, 'getDecodedProfilesField')
        );

        return $definition;
    }

    /**
     * Initialize a definition.
     *
     * @param ?string $system_name
     * @param ?array $profiles
     *
     * @return DropdownDefinition
     */
    protected function initDropdownDefinition(
        ?string $system_name = null,
        ?array $profiles = null,
    ): DropdownDefinition {
        if ($profiles === null) {
            // Initialize with all standard rights for super admin profile
            $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
            $profiles = [
                $superadmin_p_id => ALLSTANDARDRIGHT,
            ];
        }

        $definition = $this->createItem(
            DropdownDefinition::class,
            [
                'system_name' => $system_name ?? $this->getUniqueString(),
                'is_active'   => true,
                'profiles'    => $profiles,
            ],
            skip_fields: ['profiles'] // JSON encoded fields cannot be automatically checked
        );
        $this->assertEquals($profiles, $this->callPrivateMethod($definition, 'getDecodedProfilesField'));

        return $definition;
    }

    /**
     * Create a random text document.
     * @return Document
     */
    protected function createTxtDocument(): Document
    {
        $entity   = getItemByTypeName('Entity', '_test_root_entity', true);
        $filename = uniqid('glpitest_', true) . '.txt';
        $contents = random_bytes(1024);

        $written_bytes = file_put_contents(GLPI_TMP_DIR . '/' . $filename, $contents);
        $this->assertEquals(strlen($contents), $written_bytes);

        return $this->createItem(
            Document::class,
            [
                'filename'    => $filename,
                'entities_id' => $entity,
                '_filename'   => [
                    $filename,
                ],
            ]
        );
    }

    /**
     * Helper method to enable a capacity on the given asset definition
     *
     * @param AssetDefinition $definition           Asset definition
     * @param string          $capacity_classname   Capacity to enable
     *
     * @return AssetDefinition Updated asset definition
     */
    protected function enableCapacity(
        AssetDefinition $definition,
        string $capacity_classname,
        ?CapacityConfig $config = new CapacityConfig()
    ): AssetDefinition {
        // Add new capacity
        $existing_capacities = $this->callPrivateMethod($definition, 'getDecodedCapacitiesField');
        $existing_capacities[] = new Capacity(name: $capacity_classname, config: $config);

        $capacity_input = array_map(fn(Capacity $capacity) => $capacity->jsonSerialize(), $existing_capacities);

        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            ['capacities' => $capacity_input],
            // JSON encoded fields cannot be automatically checked
            skip_fields: ['capacities']
        );

        // Reload definition after update
        $definition->getFromDB($definition->getID());

        // Ensure capacity was added
        $this->assertArrayHasKey(
            $capacity_classname,
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        );

        return $definition;
    }

    /**
     * Helper method to disable a capacity on the given asset definition
     *
     * @param AssetDefinition $definition           Asset definition
     * @param string          $capacity_classname   Capacity to disable
     *
     * @return AssetDefinition Updated asset definition
     */
    protected function disableCapacity(
        AssetDefinition $definition,
        string $capacity_classname
    ): AssetDefinition {
        $existing_capacities = $this->callPrivateMethod($definition, 'getDecodedCapacitiesField');

        $capacity_input = [];
        foreach ($existing_capacities as $capacity) {
            if ($capacity->getName() === $capacity_classname) {
                continue;
            }

            $capacity_input[] = $capacity->jsonSerialize();
        }

        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            ['capacities' => $capacity_input],
            // JSON encoded fields cannot be automatically checked
            skip_fields: ['capacities']
        );

        // Reload definition after update
        $definition->getFromDB($definition->getID());

        // Ensure capacity was deleted
        $this->assertArrayNotHasKey(
            $capacity_classname,
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        );

        return $definition;
    }

    /**
     * Recursively compare two arrays without considering order at any level
     *
     * This method checks that both arrays have the same number of elements
     * and that each element in the expected array is found in the actual array,
     * regardless of the order of elements.
     *
     * @param array $expected The expected array
     * @param array $actual The actual array to compare against
     */
    protected function assertArraysEqualRecursive($expected, $actual): void
    {
        $this->assertEquals(count($expected), count($actual), 'Arrays must have the same number of elements');

        foreach ($expected as $expectedItem) {
            $found = false;
            foreach ($actual as $actualItem) {
                if ($this->arraysEqualRecursive($expectedItem, $actualItem)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Expected item not found in actual array: ' . json_encode($expectedItem));
        }
    }

    /**
     * Recursively compare two values without considering array order
     */
    private function arraysEqualRecursive($a, $b): bool
    {
        if (is_array($a) && is_array($b)) {
            if (count($a) !== count($b)) {
                return false;
            }
            foreach ($a as $key => $value) {
                if (!array_key_exists($key, $b)) {
                    return false;
                }
                if (!$this->arraysEqualRecursive($value, $b[$key])) {
                    return false;
                }
            }
            return true;
        }
        return $a === $b;
    }

    protected function addRightToProfile(
        string $profile,
        string $right,
        int $value
    ): void {
        $profile_right = new ProfileRight();
        $profile_right->getFromDBByCrit([
            'profiles_id' => getItemByTypeName(Profile::class, $profile, true),
            'name' => $right,
        ]);
        $this->updateItem(ProfileRight::class, $profile_right->fields['id'], [
            'rights' => $profile_right->fields['rights'] | $value,
        ]);
    }

    protected function removeRightFromProfile(
        string $profile,
        string $right,
        int $value
    ): void {
        $profile_right = new ProfileRight();
        $profile_right->getFromDBByCrit([
            'profiles_id' => getItemByTypeName(Profile::class, $profile, true),
            'name' => $right,
        ]);
        $this->updateItem(ProfileRight::class, $profile_right->fields['id'], [
            'rights' => $profile_right->fields['rights'] & ~$value,
        ]);
    }
}
