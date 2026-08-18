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

namespace tests\units;

use Computer;
use Dropdown;
use Entity;
use Glpi\Form\Category;
use Glpi\SocketModel;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Tester\Computer as TesterComputer;
use PHPUnit\Framework\Attributes\DataProvider;
use Printer;
use ProfileRight;
use Rule;
use Session;
use Tag;
use Tag_Itemtype;

class TagTest extends DbTestCase
{
    /**
     * Test that taggable items are correctly identified
     */
    public function testTaggableItem(): void
    {
        global $CFG_GLPI;

        // Classes that are not taggable
        $not_taggable_classes = [
            \CommonImplicitTreeDropdown::class,
            \Log::class,
            \RuleMatchedLog::class,
            \NotificationMailingSetting::class,
            \KnowbaseItem_Revision::class,
            \DisplayPreference::class,
            \OlaLevel_Ticket::class,
            \ImpactCompound::class,
            \ImpactContext::class,
            \NotificationEvent::class,
            Rule::class,
            \PendingReasonCron::class,
            \CleanSoftwareCron::class,
            \SavedSearch::class,
            \AuthLdapReplicate::class,
            \KnowbaseItem_Comment::class,
            \ValidatorSubstitute::class,
            \CommonITILRecurrentCron::class,
            \CommonITILValidationCron::class,
            \FieldUnicity::class,
            \AgentType::class,
            \ObjectLock::class,
            \OAuthClient::class,
            \NotImportedEmail::class,
            \RuleCollection::class,
            \ImpactItem::class,
            \PurgeLogs::class,
            \SlaLevel_Ticket::class,
            \Alert::class,
            \CronTask::class,
            \NotificationSettingConfig::class,
            \RefusedEquipment::class,
            \Plugin::class,
            \Transfer::class,
            \SNMPCredential::class,
            \Config::class,
            \QueuedNotification::class,
            \Lockedfield::class,
            \APIClient::class,
            Tag::class,
            \DefaultFilter::class,
        ];

        // Classes that are taggable and are subclasses of CommonDBConnexity
        $taggable_connexity = [
            \Database::class,
            \NetworkName::class,
            \DomainRecord::class,
            \Item_DeviceSimcard::class,
        ];

        // Classes that are taggable but not discoverable by getClasses()
        $undiscoverable_taggable = [
            Category::class,
            SocketModel::class,
        ];

        // Get all classes that are subclasses of CommonDBTM
        $classes = $this->getClasses();
        $classes = array_merge($classes, $undiscoverable_taggable);
        $taggable_classes = [];

        // Check each class to see if it is taggable
        foreach ($classes as $class) {
            if (
                is_subclass_of($class, \CommonDBTM::class)
                && (!is_subclass_of($class, \CommonDBConnexity::class) || in_array($class, $taggable_connexity))
                && !is_subclass_of($class, \CommonITILSatisfaction::class)
                && !is_subclass_of($class, Rule::class)
                && !is_subclass_of($class, \RuleCollection::class)
                && !is_subclass_of($class, \NotificationSetting::class)
                && !is_subclass_of($class, \CommonITILTask::class)
                && !in_array($class, $not_taggable_classes)
                && !(new \ReflectionClass($class))->isAbstract()
            ) {
                $this->assertContains($class, $CFG_GLPI['taggable_types'], "Class $class should be taggable");
                $taggable_classes[] = $class;
            }
        }

        // Filter out plugin and custom asset classes from the taggable_types for comparison
        $CFG_GLPI['taggable_types'] = array_filter($CFG_GLPI['taggable_types'], function ($class) {
            return !str_starts_with($class, 'GlpiPlugin') && !str_starts_with($class, 'Glpi\\CustomAsset');
        });

        // Check that all classes in taggable_types are covered by the test loop
        $this->assertCount(
            count($CFG_GLPI['taggable_types']),
            $taggable_classes,
            "Some classes in taggable_types are not covered by the test loop.\n"
            . "Add them to \$not_taggable_classes or \$undiscoverable_taggable:\n- "
            . implode("\n- ", array_diff($CFG_GLPI['taggable_types'], $taggable_classes))
        );
    }

    public static function providerTestRights(): iterable
    {
        yield 'no rights' => [
            'rights'     => 0,
            'can_view'   => false,
            'can_create' => false,
            'can_update' => false,
            'can_purge'  => false,
        ];

        yield 'All rights' => [
            'rights'     => READ | CREATE | UPDATE | PURGE,
            'can_view'   => true,
            'can_create' => true,
            'can_update' => true,
            'can_purge'  => true,
        ];
    }

    /**
     * Test Tag rights
     */
    #[DataProvider('providerTestRights')]
    public function testRights(
        int $rights,
        bool $can_view,
        bool $can_create,
        bool $can_update,
        bool $can_purge,
    ): void {
        $this->login();

        $profile_id = $_SESSION['glpiactiveprofile']['id'];

        // Update profile right for tag
        $profile_right = new ProfileRight();
        $profile_right->getFromDBByCrit(['profiles_id' => $profile_id, 'name' => Tag::$rightname]);
        $this->updateItem(ProfileRight::class, $profile_right->fields['id'], ['rights' => $rights]);
        Session::changeProfile($profile_id);

        // Assert rights
        $this->assertSame($can_view, Tag::canView());
        $this->assertSame($can_create, Tag::canCreate());
        $this->assertSame($can_update, Tag::canUpdate());
        $this->assertSame($can_purge, Tag::canPurge());
    }

    /**
     * Verify itemtypes for a given tag
     *
     * @param Tag $tag Tag to verify
     * @param array|string $itemtypes Expected itemtypes
     */
    private function verifyItemtypes(Tag $tag, array|string $itemtypes): void
    {
        global $CFG_GLPI;

        $tag_itemtype = new Tag_Itemtype();
        if (!is_array($itemtypes)) {
            $this->assertEmpty($tag_itemtype->find(['tags_id' => $tag->getID()]));
            return;
        }

        foreach ($itemtypes as $itemtype) {
            $is_add_for_tag = $tag_itemtype->getFromDBByCrit(['tags_id' => $tag->getID(), 'itemtype' => $itemtype]);
            if (!in_array($itemtype, $CFG_GLPI['taggable_types'])) {
                $this->assertFalse($is_add_for_tag);
            } else {
                $this->assertTrue($is_add_for_tag);
            }
        }
    }

    /**
     * Create a tag with given input and verify itemtypes
     *
     * @param array $input Input data for tag creation
     * @param array $skip_fields Fields to skip verification
     *
     * @return Tag Created tag
     */
    private function createTag(array $input, array $skip_fields = []): Tag
    {
        $tag = $this->createItem(Tag::class, $input, $skip_fields);
        $this->verifyItemtypes($tag, $input['_itemtypes'] ?? []);

        return $tag;
    }

    /**
     * Update a tag with given input and verify itemtypes
      *
      * @param Tag $tag Tag to update
      * @param array $input Input data
      * @param array $skip_fields Fields to skip verification
      *
      * @return Tag Updated tag
      */
    private function updateTag(Tag $tag, array $input, array $skip_fields = []): Tag
    {
        $tag = $this->updateItem(Tag::class, $tag->getID(), $input, $skip_fields);
        $this->verifyItemtypes($tag, $input['_itemtypes'] ?? []);
        return $tag;
    }

    public static function providerTestAddAndUpdateTagItemtypes(): iterable
    {
        yield 'itemtypes as array' => [
            'input_itemtypes' => [Computer::class],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'itemtypes is empty' => [
            'input_itemtypes' => "",
            'expected_itemtypes' => [],
        ];

        yield 'multiple itemtypes' => [
            'input_itemtypes' => [Printer::class, Computer::class],
            'expected_itemtypes' => [Printer::class, Computer::class],
        ];

        yield 'unauthorized itemtype' => [
            'input_itemtypes' => [Rule::class, Computer::class],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'duplicate itemtypes' => [
            'input_itemtypes' => [Computer::class, Computer::class],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'plugin itemtype' => [
            'input_itemtypes' => [TesterComputer::class],
            'expected_itemtypes' => [TesterComputer::class],
        ];

        yield 'custom asset itemtype' => [
            'input_itemtypes' => ['Glpi\\CustomAsset\\Test01Asset'],
            'expected_itemtypes' => ['Glpi\\CustomAsset\\Test01Asset'],
        ];
    }

    /**
     * Test adding a tag with itemtypes and verify that itemtypes are correctly set
     */
    #[DataProvider('providerTestAddAndUpdateTagItemtypes')]
    public function testAddAndUpdateTagWithItemtypes(array|string $input_itemtypes, array $expected_itemtypes): void
    {
        // Test add
        $tag = $this->createTag([
            'name' => 'Test Tag',
            '_itemtypes' => $input_itemtypes,
        ]);
        $this->assertSame($expected_itemtypes, $tag->getItemtypes());
        $this->deleteItem(Tag::class, $tag->getID());

        // Test update
        $tag = $this->createTag([
            'name' => 'Test Tag',
            '_itemtypes' => [Printer::class],
        ]);

        $this->updateTag($tag, [
            'name' => 'Test Tag',
            '_itemtypes' => $input_itemtypes,
        ]);
        $this->assertSame($expected_itemtypes, $tag->getItemtypes());
        $this->deleteItem(Tag::class, $tag->getID());
    }

    public static function providerTestColorGeneration(): iterable
    {
        yield 'default color generation' => [
            'input' => [
                'name' => 'Test Tag',
                'color' => '',
                'bg_color' => '',
            ],
            'expected_bg_color' => '#6cb7e0',
            'expected_color' => '#000000',
        ];

        yield 'invalid bg_color' => [
            'input' => [
                'name' => 'Test Tag',
                'color' => '#000000',
                'bg_color' => 'bg_color',
            ],
            'expected_bg_color' => '#6cb7e0',
            'expected_color' => '#000000',
        ];

        yield 'invalid color' => [
            'input' => [
                'name' => 'Test Tag',
                'color' => 'color',
                'bg_color' => '#000000',
            ],
            'expected_bg_color' => '#000000',
            'expected_color' => '#FFFFFF',
        ];

        yield 'valid colors' => [
            'input' => [
                'name' => 'Test Tag',
                'color' => '#ffff00',
                'bg_color' => '#00ffff',
            ],
            'expected_bg_color' => '#00ffff',
            'expected_color' => '#ffff00',
        ];
    }

    /**
     * Test that background and text colors are correctly generated based on input
     */
    #[DataProvider('providerTestColorGeneration')]
    public function testColorGeneration(array $input, string $expected_bg_color, string $expected_color): void
    {
        // Test color generation on add
        $tag = $this->createTag($input, ['color', 'bg_color']);

        $this->assertSame($expected_bg_color, $tag->getBackgroundColor());
        $this->assertSame($expected_color, $tag->getTextColor());
        $this->deleteItem(Tag::class, $tag->getID());

        // Test color generation on update
        $tag = $this->createTag([
            'name' => 'Test Tag',
            'color' => '#ff0000',
            'bg_color' => '#00ff00',
        ]);

        $tag = $this->updateTag($tag, $input, ['color', 'bg_color']);
        $this->assertSame($expected_bg_color, $tag->getBackgroundColor());
        $this->assertSame($expected_color, $tag->getTextColor());
        $this->deleteItem(Tag::class, $tag->getID());
    }

    /**
     * Test that tags must have unique names
     */
    public function testUniqueName(): void
    {
        $this->login();

        $root_entity_id = $this->getTestRootEntity(true);
        $child_entity = $this->createItem(Entity::class, [
            'name'        => 'Child Entity',
            'entities_id' => $root_entity_id,
        ]);

        //Create a tag in the root entity
        $tag = $this->createTag([
            'name'        => 'Unique Tag Name',
            'entities_id' => $root_entity_id,
        ]);

        // Check if creating a tag with the same name in the same entity fails
        $tag_duplicate = new Tag();
        $result = $tag_duplicate->add([
            'name'        => 'Unique Tag Name',
            'entities_id' => $root_entity_id,
        ]);
        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, [htmlescape(sprintf(
            'A tag with this name already exists in entity "%s"! Transfter the tag to another entity or change its name.',
            Dropdown::getDropdownName(Entity::getTable(), $root_entity_id)
        ))]);

        // Updating the tag with its own name must still be allowed
        $this->updateTag($tag, [
            'name'        => 'Unique Tag Name',
            'entities_id' => $root_entity_id,
        ]);
        $this->deleteItem(Tag::class, $tag->getID());

        // Check if creating a tag with the same name in a child entity fails
        $duplicate_tag = $this->createTag([
            'name'         => 'Duplicate Tag Name',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 0,
        ]);
        $result = $tag_duplicate->add([
            'name'        => 'Duplicate Tag Name',
            'entities_id' => $child_entity->getID(),
        ]);
        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, [htmlescape(sprintf(
            'A tag with this name already exists in entity "%s"! Transfter the tag to another entity or change its name.',
            Dropdown::getDropdownName(Entity::getTable(), $root_entity_id)
        ))]);
        $this->deleteItem(Tag::class, $duplicate_tag->getID());

        // Check if creating a tag with the same name in a child entity fails
        // when the parent tag is recursive
        $recursive_tag = $this->createTag([
            'name'         => 'Recursive Tag Name',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 1,
        ]);

        $conflicting = new Tag();
        $this->assertFalse($conflicting->add([
            'name'        => 'Recursive Tag Name',
            'entities_id' => $child_entity->getID(),
        ]));
        $this->hasSessionMessages(ERROR, [htmlescape(sprintf(
            'A tag with this name already exists in entity "%s"! Transfter the tag to another entity or change its name.',
            Dropdown::getDropdownName(Entity::getTable(), $root_entity_id)
        ))]);
        $this->deleteItem(Tag::class, $recursive_tag->getID());
    }

    /**
     * Test that deleting all associations for an itemtype removes both standard and legacy plugin associations
     */
    public function testPluginUninstallRemovesTagAssociations(): void
    {
        $tag = $this->createTag([
            'name'      => 'Test Plugin Uninstall Tag Associations',
            '_itemtypes' => [Computer::class, TesterComputer::class],
        ]);

        // Insert legacy-style plugin classes directly in DB, since they are not
        // part of taggable_types and would be rejected by prepareInputForAdd()
        global $DB;
        $DB->insert(Tag_Itemtype::getTable(), [
            'tags_id'  => $tag->getID(),
            'itemtype' => 'PluginTesterComputer',
        ]);
        $DB->insert(Tag_Itemtype::getTable(), [
            'tags_id'  => $tag->getID(),
            'itemtype' => 'PluginTesterrComputer',
        ]);

        $tag_itemtype = new Tag_Itemtype();
        $this->assertCount(4, $tag_itemtype->find(['tags_id' => $tag->getID()]));

        // Simulate plugin uninstall
        Tag_Itemtype::deleteForItemtype('tester');

        // Verify that both 'GlpiPlugin\\TesterComputer' and 'PluginTesterComputer' associations are removed, but not 'Computer'
        $remaining           = $tag_itemtype->find(['tags_id' => $tag->getID()]);
        $remaining_itemtypes = array_column($remaining, 'itemtype');

        $this->assertCount(2, $remaining);
        $this->assertContains(Computer::class, $remaining_itemtypes);
        $this->assertNotContains(TesterComputer::class, $remaining_itemtypes);
        $this->assertNotContains('PluginTesterComputer', $remaining_itemtypes);
        $this->assertContains('PluginTesterrComputer', $remaining_itemtypes);
    }

    /**
     * Test that purging a tag removes its itemtypes associations
     */
    public function testPurgeTagRemovesItemtypes(): void
    {
        $tag = $this->createTag([
            'name' => 'Test Tag',
            '_itemtypes' => [
                Printer::class,
                Computer::class,
                TesterComputer::class,
                'Glpi\\CustomAsset\\Test01Asset',
            ],
        ]);

        $tag_itemtype = new Tag_Itemtype();
        $tag_itemtypes = $tag_itemtype->find(['tags_id' => $tag->getID()]);
        $this->assertCount(4, $tag_itemtypes);
        $this->deleteItem(Tag::class, $tag->getID());
        $this->assertEmpty($tag_itemtype->find(['tags_id' => $tag->getID()]));
    }
}
