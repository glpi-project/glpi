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
use Glpi\Search\SearchOption;
use Glpi\SocketModel;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\TagTrait;
use GlpiPlugin\Tester\Computer as TesterComputer;
use PHPUnit\Framework\Attributes\DataProvider;
use Printer;
use ProfileRight;
use Rule;
use Session;
use Tag;
use Tag_Item;
use Tag_Itemtype;

class TagTest extends DbTestCase
{
    use TagTrait;

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

    public static function providerTestAddAndUpdateTagItemtypes(): iterable
    {
        yield 'itemtypes as array' => [
            'input_itemtypes' => [Computer::class],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'itemtypes is empty' => [
            'input_itemtypes' => "",
            'expected_itemtypes' => Tag_Itemtype::getTaggableItems(),
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
            'A tag with this name already exists in entity "%s"! Transfer the tag to another entity or change its name.',
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
            'A tag with this name already exists in entity "%s"! Transfer the tag to another entity or change its name.',
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
            'A tag with this name already exists in entity "%s"! Transfer the tag to another entity or change its name.',
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
     * Test that purging a tag removes its itemtypes associations, as well as its
     * associations to the items it was attached to
     */
    public function testPurgeTagRemovesItemtypes(): void
    {
        $this->login();

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

        // Attach the tag to an item so we can verify that association is cleaned up too
        $computer = $this->createItem(Computer::class, [
            'name'        => 'Test Computer for Tag Purge',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->assertTrue(Tag_Item::attachTag($computer, $tag->getID()));

        $this->deleteItem(Tag::class, $tag->getID());

        // Verify that the tag's itemtype associations are removed
        $this->assertEmpty($tag_itemtype->find(['tags_id' => $tag->getID()]));

        // Verify that the tag's associations to items are removed
        $tag_item = new Tag_Item();
        $this->assertEmpty($tag_item->find(['tags_id' => $tag->getID()]));
    }

    /**
     * Test that tags can be attached to all taggable itemtypes and that the attachment state is correctly updated when items are updated or deleted
     */
    public function testAttachTagToAllTaggableItemtypes(): void
    {
        $this->login();

        // Create three tags
        $tag1 = $this->createTag([
            'name' => 'Tag1',
        ]);
        $tag2 = $this->createTag([
            'name' => 'Tag2',
        ]);
        $tag3 = $this->createTag([
            'name' => 'Tag3',
        ]);

        foreach (Tag_Itemtype::getTaggableItems() as $itemtype) {
            // Create an item of the current itemtype with tag1 and tag2 attached
            $item = $this->createTaggableTestItemtype($itemtype, ['_tags' => [$tag1->getID(), $tag2->getID()]]);

            $this->assertTrue(
                Tag_Item::hasTag($item, $tag1->getID()),
                'Tag1 should be attached to ' . $itemtype
            );
            $this->assertTrue(
                Tag_Item::hasTag($item, $tag2->getID()),
                'Tag2 should be attached to ' . $itemtype
            );
            $this->assertFalse(
                Tag_Item::hasTag($item, $tag3->getID()),
                'Tag3 should not be attached to ' . $itemtype
            );

            // Update the item to remove tag1 and add tag3
            $this->updateItem($itemtype, $item->getID(), ['_tags' => [$tag2->getID(), $tag3->getID()]]);

            $this->assertFalse(
                Tag_Item::hasTag($item, $tag1->getID()),
                'Tag1 should no longer be attached to ' . $itemtype
            );
            $this->assertTrue(
                Tag_Item::hasTag($item, $tag2->getID()),
                'Tag2 should still be attached to ' . $itemtype
            );
            $this->assertTrue(
                Tag_Item::hasTag($item, $tag3->getID()),
                'Tag3 should now be attached to ' . $itemtype
            );

            // Determine if the item can be deleted based on its properties
            $can_deleted = !$item->isTemplate()
                && $item->maybeDeleted()
                && !($item->useDeletedToLockIfDynamic() && !$item->isDynamic());

            // Delete the item and verify that all tags are no longer attached
            if ($can_deleted) {
                $this->deleteItem($itemtype, $item->getID(), false);

                $this->assertFalse(
                    Tag_Item::hasTag($item, $tag1->getID()),
                    'Tag1 should no longer be attached to ' . $itemtype
                );
                $this->assertTrue(
                    Tag_Item::hasTag($item, $tag2->getID()),
                    'Tag2 attachment state is incorrect for ' . $itemtype
                );
                $this->assertTrue(
                    Tag_Item::hasTag($item, $tag3->getID()),
                    'Tag3 attachment state is incorrect for ' . $itemtype
                );
            }

            // Force delete the item to ensure all tags are removed
            $this->deleteItem($itemtype, $item->getID(), true);

            $this->assertFalse(
                Tag_Item::hasTag($item, $tag1->getID()),
                'Tag1 should no longer be attached to ' . $itemtype
            );
            $this->assertFalse(
                Tag_Item::hasTag($item, $tag2->getID()),
                'Tag2 should no longer be attached to ' . $itemtype
            );
            $this->assertFalse(
                Tag_Item::hasTag($item, $tag3->getID()),
                'Tag3 should no longer be attached to ' . $itemtype
            );

        }
    }

    /**
     * Test Tag_Itemtype::getItemtypesByTag() directly, including its $all_if_empty behavior
     */
    public function testGetItemtypesByTag(): void
    {
        // A tag with no restriction: $all_if_empty (default true) returns every taggable itemtype
        $all_assets_tag = $this->createTag(['name' => 'All Assets Tag for getItemtypesByTag']);
        $this->assertSame(
            Tag_Itemtype::getTaggableItems(),
            Tag_Itemtype::getItemtypesByTag($all_assets_tag)
        );

        $this->assertSame(Tag_Itemtype::getTaggableItems(), Tag_Itemtype::getItemtypesByTag($all_assets_tag, true));
        $this->assertSame([], Tag_Itemtype::getItemtypesByTag($all_assets_tag, false));

        // A tag restricted to specific itemtypes returns only those, regardless of $all_if_empty
        $restricted_tag = $this->createTag([
            'name' => 'Restricted Tag for getItemtypesByTag',
            '_itemtypes' => [Printer::class, Computer::class],
        ]);
        $this->assertSame(
            [Printer::class, Computer::class],
            Tag_Itemtype::getItemtypesByTag($restricted_tag)
        );
        $this->assertSame(
            [Printer::class, Computer::class],
            Tag_Itemtype::getItemtypesByTag($restricted_tag, false)
        );

        // A tag that has not been saved yet always returns an empty list, regardless of $all_if_empty
        $new_tag = new Tag();
        $this->assertSame([], Tag_Itemtype::getItemtypesByTag($new_tag));
        $this->assertSame([], Tag_Itemtype::getItemtypesByTag($new_tag, false));
    }

    public function testGetTagsDropdownData(): void
    {
        $this->login();

        $computer_tag = $this->createTag([
            'name'        => 'Computer Tag',
            '_itemtypes'  => [Computer::class],
            'is_active'   => 1,
            'color'       => '#111111',
            'bg_color'    => '#222222',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $printer_tag = $this->createTag([
            'name'        => 'Printer Tag',
            '_itemtypes'  => [Printer::class],
            'is_active'   => 1,
            'color'       => '#333333',
            'bg_color'    => '#444444',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $all_assets_tag = $this->createTag([
            'name'        => 'All Assets Tag',
            'is_active'   => 1,
            'color'       => '#555555',
            'bg_color'    => '#666666',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $computer_printer_tag = $this->createTag([
            'name'        => 'Computer Printer Tag',
            '_itemtypes'  => [Computer::class, Printer::class],
            'is_active'   => 1,
            'color'       => '#777777',
            'bg_color'    => '#888888',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->createTag([
            'name'        => 'Inactive Tag',
            '_itemtypes'  => [Computer::class],
            'is_active'   => 0,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // A single itemtype: tags restricted to Computer plus the unrestricted tag
        $this->assertEquals(
            [
                'tag_names'   => [
                    $computer_tag->getID()   => $computer_tag->fields['name'],
                    $all_assets_tag->getID() => $all_assets_tag->fields['name'],
                    $computer_printer_tag->getID() => $computer_printer_tag->fields['name'],
                ],
                'bg_colors'   => [
                    $computer_tag->getID()   => '#222222',
                    $all_assets_tag->getID() => '#666666',
                    $computer_printer_tag->getID() => '#888888'
                ],
                'text_colors' => [
                    $computer_tag->getID()   => '#111111',
                    $all_assets_tag->getID() => '#555555',
                    $computer_printer_tag->getID() => '#777777'
                ],
            ],
            Tag::getTagsDropdownData(Computer::class)
        );

        // Several itemtypes (intersection): only the unrestricted tag is allowed for both
        $this->assertEquals(
            [
                'tag_names'   => [
                    $all_assets_tag->getID() => $all_assets_tag->fields['name'],
                    $computer_printer_tag->getID() => $computer_printer_tag->fields['name'],
                ],
                'bg_colors'   => [
                    $all_assets_tag->getID() => '#666666',
                    $computer_printer_tag->getID() => '#888888',
                ],
                'text_colors' => [
                    $all_assets_tag->getID() => '#555555',
                    $computer_printer_tag->getID() => '#777777',
                ],
            ],
            Tag::getTagsDropdownData([Computer::class, Printer::class])
        );

        // No itemtype filter
        $this->assertEquals(
            [
                'tag_names'   => [],
                'bg_colors'   => [],
                'text_colors' => [],
            ],
            Tag::getTagsDropdownData(null)
        );
    }

    /**
     * Test that the Tag search option column is present for all taggable itemtypes
     */
    public function testTagSearchOptionForAllTaggableItemtypes(): void
    {
        foreach (Tag_Itemtype::getTaggableItems() as $itemtype) {
            $options = SearchOption::getOptionsForItemtype($itemtype);

            $this->assertArrayHasKey(
                490,
                $options,
                "Tag search option (id 490) should be present for $itemtype"
            );
            $this->assertSame(
                Tag::getTable(),
                $options[490]['table'] ?? null,
                "Tag search option table is incorrect for $itemtype"
            );
            $this->assertSame(
                'name',
                $options[490]['field'] ?? null,
                "Tag search option field is incorrect for $itemtype"
            );
        }
    }
}
