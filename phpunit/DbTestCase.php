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

// Generic test classe, to be extended for CommonDBTM Object

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetDefinitionManager;

class DbTestCase extends \GLPITestCase
{
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
     * @return \Auth
     */
    protected function login(
        string $user_name = TU_USER,
        string $user_pass = TU_PASS,
        bool $noauto = true,
        bool $expected = true
    ): \Auth {
        \Session::destroy();
        \Session::start();

        $auth = new Auth();
        $this->assertEquals($expected, $auth->login($user_name, $user_pass, $noauto));

        return $auth;
    }

    /**
     * Log out current user
     *
     * @return void
     */
    protected function logOut()
    {
        \Session::destroy();
        \Session::start();
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
     * Generic method to test if an added object is corretly inserted
     *
     * @param  CommonDBTM $object The object to test
     * @param  int    $id     The id of added object
     * @param  array  $input  the input used for add object (optionnal)
     *
     * @return void
     */
    protected function checkInput(CommonDBTM $object, $id = 0, $input = [])
    {
        $this->assertGreaterThan($object instanceof Entity ? -1 : 0, (int)$id);
        $this->assertTrue($object->getFromDB($id));
        $this->assertEquals($id, $object->getField('id'));

        if (count($input)) {
            foreach ($input as $k => $v) {
                $this->assertEquals(
                    $v,
                    $object->fields[$k],
                    "
                    '$k' key current value '{$object->fields[$k]}' (" . gettype($object->fields[$k]) . ")
                    is not equal to '$v' (" . gettype($v) . ")"
                );
            }
        }
    }

    /**
     * Get all classes in folder inc/
     *
     * @param boolean $function Whether to look for a function
     * @param array   $excludes List of classes to exclude
     *
     * @return array
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
     * Create an item of the given class
     *
     * @param string $itemtype
     * @param array $input
     * @param array $skip_fields Fields that wont be checked after creation
     *
     * @return CommonDBTM
     */
    protected function createItem($itemtype, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $id = $item->add($input);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Remove special fields
        $skip_fields[] = 'id';
        $input = array_filter($input, function ($key) use ($skip_fields) {
            return !in_array($key, $skip_fields) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->checkInput($item, $id, $input);

        return $item;
    }

    /**
     * Create an item of the given class
     *
     * @param string $itemtype
     * @param array $input
     * @param array $skip_fields Fields that wont be checked after creation
     *
     * @return CommonDBTM The updated item
     */
    protected function updateItem($itemtype, $id, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->update($input);
        $this->assertTrue($success);

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
     * Helper method to avoid writting the same boilerplate code for rule creation
     *
     * @param RuleBuilder $builder RuleConfiguration
     *
     * @return Rule Created rule
     */
    protected function createRule(RuleBuilder $builder): Rule
    {
        $rule = $this->createItem(Rule::class, [
            'is_active'    => 1,
            'sub_type'     => $builder->getRuleType(),
            'name'         => $builder->getName(),
            'match'        => $builder->getOperator(),
            'condition'    => $builder->getCondition(),
            'is_recursive' => $builder->isRecursive(),
            'entities_id'  => $builder->getEntity(),
        ]);

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
     * Initialize a definition.
     *
     * @param string $system_name
     * @param array $capacities
     *
     * @return AssetDefinition
     */
    protected function initAssetDefinition(
        ?string $system_name = null,
        array $capacities = [],
        ?array $profiles = null,
    ): AssetDefinition {
        if ($profiles === null) {
            // Initialize with all standard rights for super admin profile
            $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
            $profiles = [
                $superadmin_p_id => ALLSTANDARDRIGHT,
            ];
        }

        $definition = $this->createItem(
            AssetDefinition::class,
            [
                'system_name' => $system_name ?? $this->getUniqueString(),
                'is_active'   => true,
                'capacities'  => $capacities,
                'profiles'    => $profiles,
            ],
            skip_fields: ['capacities', 'profiles'] // JSON encoded fields cannot be automatically checked
        );
        $this->assertEquals(
            $capacities,
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        );
        $this->assertEquals(
            $profiles,
            $this->callPrivateMethod($definition, 'getDecodedProfilesField')
        );

        $manager = AssetDefinitionManager::getInstance();
        $this->callPrivateMethod($manager, 'loadConcreteClass', $definition);
        $this->callPrivateMethod($manager, 'loadConcreteModelClass', $definition);
        $this->callPrivateMethod($manager, 'loadConcreteTypeClass', $definition);
        $this->callPrivateMethod($manager, 'boostrapConcreteClass', $definition);

        // Clear definition cache
        $rc = new ReflectionClass(AssetDefinitionManager::class);
        $rc->getProperty('definitions_data')->setValue(AssetDefinitionManager::getInstance(), null);

        return $definition;
    }

    /**
     * Create a random text document.
     * @return \Document
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
     * @param AssetDefinition $definition Asset definition
     * @param string          $capacity   Capacity to enable
     *
     * @return AssetDefinition Updated asset definition
     */
    protected function enableCapacity(
        AssetDefinition $definition,
        string $capacity
    ): AssetDefinition {
        // Add new capacity
        $capacities = json_decode($definition->fields['capacities']);
        $capacities[] = $capacity;

        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            ['capacities' => $capacities],
            // JSON encoded fields cannot be automatically checked
            skip_fields: ['capacities']
        );

        // Reload definition after update
        $definition->getFromDB($definition->getID());

        // Ensure capacity was added
        $this->assertContains(
            $capacity,
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        );

        // Force boostrap to trigger methods such as "onClassBootstrap"
        $manager = AssetDefinitionManager::getInstance();
        $this->callPrivateMethod(
            $manager,
            'boostrapConcreteClass',
            $definition
        );

        return $definition;
    }

    /**
     * Helper method to disable a capacity on the given asset definition
     *
     * @param AssetDefinition $definition Asset definition
     * @param string          $capacity   Capacity to disable
     *
     * @return AssetDefinition Updated asset definition
     */
    protected function disableCapacity(
        AssetDefinition $definition,
        string $capacity
    ): AssetDefinition {
        // Remove capacity
        $capacities = json_decode($definition->fields['capacities']);
        $capacities = array_diff($capacities, [$capacity]);

        // Reorder keys to ensure json_decode will return an array instead of an
        // object
        $capacities = array_values($capacities);

        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            ['capacities' => $capacities],
            // JSON encoded fields cannot be automatically checked
            skip_fields: ['capacities']
        );

        // Reload definition after update
        $definition->getFromDB($definition->getID());

        // Ensure capacity was deleted
        $this->assertNotContains(
            $capacity,
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        );

        // Force boostrap to trigger methods such as "onClassBootstrap"
        $manager = AssetDefinitionManager::getInstance();
        $this->callPrivateMethod(
            $manager,
            'boostrapConcreteClass',
            $definition
        );

        return $definition;
    }
}
