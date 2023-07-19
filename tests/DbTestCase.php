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
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Tests\FormBuilder;

class DbTestCase extends \GLPITestCase
{
    public function beforeTestMethod($method)
    {
        global $DB;
        $DB->beginTransaction();
        parent::beforeTestMethod($method);
    }

    public function afterTestMethod($method)
    {
        global $DB;
        $DB->rollback();
        parent::afterTestMethod($method);
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
        $this->boolean($auth->login($user_name, $user_pass, $noauto))->isEqualTo($expected);

        return $auth;
    }

    /**
     * Log out current user
     *
     * @return void
     */
    protected function logOut()
    {
        $ctime = $_SESSION['glpi_currenttime'];
        \Session::destroy();
        $_SESSION['glpi_currenttime'] = $ctime;
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
        $this->boolean($res)->isTrue();
    }

    /**
     * Generic method to test if an added object is corretly inserted
     *
     * @param  Object $object The object to test
     * @param  int    $id     The id of added object
     * @param  array  $input  the input used for add object (optionnal)
     *
     * @return void
     */
    protected function checkInput(CommonDBTM $object, $id = 0, $input = [])
    {
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($object->getFromDB($id))->isTrue();
        $this->variable($object->getField('id'))->isEqualTo($id);

        if (count($input)) {
            foreach ($input as $k => $v) {
                $this->variable($object->fields[$k])->isEqualTo(
                    $v,
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
    protected function getClasses($function = false, array $excludes = [])
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
        $this->integer($id)->isGreaterThan(0);

        // Remove special fields
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
        $this->boolean($success)->isTrue();

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
     */
    protected function deleteItem($itemtype, $id): void
    {
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->delete($input);
        $this->boolean($success)->isTrue();
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
        $this->array($this->callPrivateMethod($definition, 'getDecodedCapacitiesField'))->isEqualTo($capacities);
        $this->array($this->callPrivateMethod($definition, 'getDecodedProfilesField'))->isEqualTo($profiles);

        $manager = \Glpi\Asset\AssetDefinitionManager::getInstance();
        $this->callPrivateMethod($manager, 'loadConcreteClass', $definition);
        $this->callPrivateMethod($manager, 'loadConcreteModelClass', $definition);
        $this->callPrivateMethod($manager, 'loadConcreteTypeClass', $definition);
        $this->callPrivateMethod($manager, 'boostrapConcreteClass', $definition);

        // Clear definition cache
        $rc = new ReflectionClass(\Glpi\Asset\AssetDefinitionManager::class);
        $rc->getProperty('definitions_data')->setValue(\Glpi\Asset\AssetDefinitionManager::getInstance(), null);

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
        $this->integer($written_bytes)->isEqualTo(strlen($contents));

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
        $this->array(
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        )->contains($capacity);

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
        $this->array(
            $this->callPrivateMethod($definition, 'getDecodedCapacitiesField')
        )->notContains($capacity);

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
     * Helper method to help creating complex forms using the FormBuilder class.
     *
     * @param FormBuilder $builder RuleConfiguration
     *
     * @return Form Created form
     */
    protected function createForm(FormBuilder $builder): Form
    {
        // Create form
        $form = $this->createItem(Form::class, [
            'name'                  => $builder->getName(),
            'entities_id'           => $builder->getEntitiesId(),
            'is_recursive'          => $builder->getIsRecursive(),
            'is_active'             => $builder->getIsActive(),
            'header'                => $builder->getHeader(),
            'is_draft'              => $builder->getIsDraft(),
            '_do_not_init_sections' => true, // We will handle sections ourselves
        ]);

        foreach ($builder->getSections() as $section_data) {
            // Create section
            $section = $this->createItem(Section::class, [
                'forms_forms_id' => $form->getID(),
                'name'           => $section_data['name'],
                'description'    => $section_data['description'],
            ]);

            // Create questions
            foreach ($section_data['questions'] as $question_data) {
                $this->createItem(Question::class, [
                    'forms_sections_id' => $section->getID(),
                    'name'              => $question_data['name'],
                    'type'              => $question_data['type'],
                    'is_mandatory'      => $question_data['is_mandatory'],
                    'default_value'     => $question_data['default_value'],
                    'extra_data'        => $question_data['extra_data'],
                ]);
            }
        }

        // Reload form
        $form->getFromDB($form->getID());

        return $form;
    }

    /**
     * Helper method to access the ID of a question for a given form.
     *
     * @param Form        $form          Given form
     * @param string      $question_name Question name to look for
     * @param string|null $section_name  Optional section name, might be needed if
     *                                   multiple sections have questions with the
     *                                   same names.
     *
     * @return int The ID of the question
     */
    public function getQuestionId(
        Form $form,
        string $question_name,
        string $section_name = null,
    ): int {
        // Make sure form is up to date
        $form->getFromDB($form->getID());

        // Get questions
        $questions = $form->getQuestions();

        if ($section_name === null) {
            // Search by name
            $filtered_questions = array_filter(
                $questions,
                fn($question) => $question->fields['name'] === $question_name
            );

            $this->array($filtered_questions)->hasSize(1);
            $question = array_pop($filtered_questions);
            return $question->getID();
        } else {
            // Find section
            $sections = $form->getSections();
            $filtered_sections = array_filter(
                $sections,
                fn($section) => $section->fields['name'] === $section_name
            );
            $this->array($filtered_sections)->hasSize(1);
            $section = array_pop($filtered_sections);

            // Search by name AND section
            $filtered_questions = array_filter(
                $questions,
                fn($question) => $question->fields['name'] === $question_name
                    && $question->fields['forms_sections_id'] === $section->getID()
            );
            $this->array($filtered_questions)->hasSize(1);
            $question = array_pop($filtered_questions);
            return $question->getID();
        }
    }

    /**
     * Helper method to access the ID of a section for a given form.
     *
     * @param Form        $form         Given form
     * @param string      $section_name Section name to look for
     *
     * @return int The ID of the section
     */
    public function getSectionId(
        Form $form,
        string $section_name,
    ): int {
        // Make sure form is up to date
        $form->getFromDB($form->getID());

        // Get sections
        $sections = $form->getSections();

        // Search by name
        $filtered_sections = array_filter(
            $sections,
            fn($section) => $section->fields['name'] === $section_name
        );

        $this->array($filtered_sections)->hasSize(1);
        $section = array_pop($filtered_sections);
        return $section->getID();
    }
}
