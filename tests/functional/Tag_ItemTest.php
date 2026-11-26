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

use CommonDBTM;
use Computer;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\TagTrait;
use GlpiPlugin\Tester\Computer as TesterComputer;
use MassiveAction;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Printer;
use Symfony\Component\DomCrawler\Crawler;
use Tag;
use Tag_Item;
use Tag_Itemtype;

class Tag_ItemTest extends DbTestCase
{
    use TagTrait;

    public function testAttachAndDetachTag(): void
    {
        $this->login();

        // Create test items and tags
        $rule = $this->createItem(\RuleTicket::class, ['name' => 'Test Rule', 'entities_id' => $this->getTestRootEntity(true)]);
        $computer = $this->createItem(Computer::class, ['name' => 'Test Computer', 'entities_id' => $this->getTestRootEntity(true)]);
        $printer = $this->createItem(Printer::class, ['name' => 'Test Printer', 'entities_id' => $this->getTestRootEntity(true)]);
        $custom_asset = $this->createItem('Glpi\\CustomAsset\\Test01Asset', ['name' => 'Test Custom Asset', 'entities_id' => $this->getTestRootEntity(true)]);
        $tester_computer = $this->createItem(TesterComputer::class, ['name' => 'Test Tester Computer', 'entities_id' => $this->getTestRootEntity(true)]);
        $all_assets_tag = $this->createTag(['name' => 'All Assets Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $computer_tag = $this->createTag(['name' => 'Computer Tag', '_itemtypes' => [Computer::class], 'entities_id' => $this->getTestRootEntity(true)]);

        // Try to attach a tag to an item that is not taggable
        $this->assertFalse(Tag_Item::addTag($rule, $all_assets_tag->getID()));

        // Attach tags to taggable items
        $this->assertTrue(Tag_Item::addTag($computer, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($printer, $all_assets_tag->getID()));

        // Try to attach the same tag again to the same item
        $this->assertFalse(Tag_Item::addTag($computer, $all_assets_tag->getID()));

        // Attach a tag that is restricted to a specific itemtype
        $this->assertTrue(Tag_Item::addTag($computer, $computer_tag->getID()));
        $this->assertFalse(Tag_Item::addTag($computer, $computer_tag->getID()));

        // Try to attach a tag to plugin itemtype and a custom itemtype
        $this->assertTrue(Tag_Item::addTag($custom_asset, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($tester_computer, $all_assets_tag->getID()));

        // Verify that the tag is attached to the asset
        $this->assertFalse(Tag_Item::hasTag($rule, $all_assets_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($rule, $computer_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $computer_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($printer, $all_assets_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($printer, $computer_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($custom_asset, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($tester_computer, $all_assets_tag->getID()));

        // Try to detach a tag from an item that is not taggable
        $this->assertFalse(Tag_Item::removeTag($rule, $all_assets_tag->getID()));

        // Detach tags from taggable items
        $this->assertTrue(Tag_Item::removeTag($computer, $all_assets_tag->getID()));

        // Try to detach a tag that is not attached to the item
        $this->assertFalse(Tag_Item::removeTag($computer, $all_assets_tag->getID()));

        // Try to detach a unrelated tag from a taggable item
        $this->assertFalse(Tag_Item::removeTag($printer, $computer_tag->getID()));

        // Try to detach a tag from a plugin itemtype
        $this->assertTrue(Tag_Item::removeTag($tester_computer, $all_assets_tag->getID()));

        // Try to detach a tag from a custom itemtype
        $this->assertTrue(Tag_Item::removeTag($custom_asset, $all_assets_tag->getID()));

        // Verify that the tag is detached from the asset
        $this->assertFalse(Tag_Item::hasTag($computer, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $computer_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($printer, $all_assets_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($custom_asset, $all_assets_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($tester_computer, $all_assets_tag->getID()));

        // A tag belonging to an entity outside of the current active entities must not be
        // attachable, even though it has no itemtype restriction
        $foreign_tag = $this->createTag([
            'name' => 'Foreign Entity Tag',
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
        ]);
        $this->setEntity('_test_root_entity', false);
        $this->assertFalse(Tag_Item::addTag($computer, $foreign_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer, $foreign_tag->getID()));
    }

    public function testCleanTag(): void
    {
        $this->login();

        // Create test items and tags
        $computer = $this->createItem(Computer::class, ['name' => 'Test Computer', 'entities_id' => $this->getTestRootEntity(true)]);
        $all_assets_tag = $this->createTag(['name' => 'All Assets Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $computer_tag = $this->createTag(['name' => 'Computer Tag', '_itemtypes' => [Computer::class], 'entities_id' => $this->getTestRootEntity(true)]);

        // Attach tags to taggable items
        $this->assertTrue(Tag_Item::addTag($computer, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($computer, $computer_tag->getID()));

        // Clean tags from taggable items
        $this->assertTrue(Tag_Item::cleanTag($computer));

        // Verify that the tags are detached from the assets
        $this->assertFalse(Tag_Item::hasTag($computer, $all_assets_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer, $computer_tag->getID()));
    }

    public function testReplaceTag(): void
    {
        $this->login();

        // Create test items and tags
        $computer = $this->createItem(Computer::class, ['name' => 'Test Computer', 'entities_id' => $this->getTestRootEntity(true)]);
        $old_tag = $this->createTag(['name' => 'Old Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $new_tag = $this->createTag(['name' => 'New Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $tag = $this->createTag(['name' => 'Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $printer_tag = $this->createTag(['name' => 'Printer Tag', '_itemtypes' => [Printer::class], 'entities_id' => $this->getTestRootEntity(true)]);

        $this->assertTrue(Tag_Item::addTag($computer, $tag->getID()));

        // Try to replace a tag that is not attached to the computer
        $this->assertFalse(Tag_Item::replaceTag($computer, $old_tag->getID(), $new_tag->getID()));

        // Attach the old tag to the computer
        $this->assertTrue(Tag_Item::addTag($computer, $old_tag->getID()));

        // Try to replace a tag that is not allowed for the computer's itemtype: the old tag must be kept
        $this->assertFalse(Tag_Item::replaceTag($computer, $old_tag->getID(), $printer_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $old_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer, $printer_tag->getID()));

        // Try to replace a tag with a tag that does not exist: the old tag must be kept
        $this->assertFalse(Tag_Item::replaceTag($computer, $old_tag->getID(), 999999));
        $this->assertTrue(Tag_Item::hasTag($computer, $old_tag->getID()));

        // Replace a tag by a tag that is already attached: only the old tag is detached
        $this->assertTrue(Tag_Item::replaceTag($computer, $old_tag->getID(), $tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer, $old_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $tag->getID()));
        $this->assertTrue(Tag_Item::addTag($computer, $old_tag->getID()));

        // Replacing a tag by itself is a no-op
        $this->assertTrue(Tag_Item::replaceTag($computer, $old_tag->getID(), $old_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $old_tag->getID()));

        // Replace the old tag with the new tag
        $this->assertTrue(Tag_Item::replaceTag($computer, $old_tag->getID(), $new_tag->getID()));

        // Verify that the old tag is detached and the new tag is attached to the computer
        $this->assertFalse(Tag_Item::hasTag($computer, $old_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $new_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer, $tag->getID()));
    }

    public function testGetTagsForItem(): void
    {
        $this->login();

        [$computer, $computer2] = $this->createItems(Computer::class, [
            ['name' => 'Test Computer', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Test Computer 2', 'entities_id' => $this->getTestRootEntity(true)],
        ]);
        $all_assets_tag = $this->createTag(['name' => 'All Assets Tag', 'entities_id' => $this->getTestRootEntity(true)]);
        $computer_tag = $this->createTag(['name' => 'Computer Tag', '_itemtypes' => [Computer::class], 'entities_id' => $this->getTestRootEntity(true)]);

        // Attach tags to taggable items
        $this->assertTrue(Tag_Item::addTag($computer, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($computer, $computer_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($computer2, $all_assets_tag->getID()));

        $this->assertSame(
            [$all_assets_tag->getID(), $computer_tag->getID()],
            Tag_Item::getTagsForItem($computer)
        );
        $this->assertSame(
            [$all_assets_tag->getID()],
            Tag_Item::getTagsForItem($computer2)
        );
    }

    public function testGetTagsByItemtypeAndItemtypes(): void
    {
        $this->login();

        $computer_tag = $this->createTag([
            'name' => 'Computer Tag',
            '_itemtypes' => [Computer::class],
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $printer_tag = $this->createTag([
            'name' => 'Printer Tag',
            '_itemtypes' => [Printer::class],
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $all_assets_tag = $this->createTag([
            'name' => 'All Assets Tag',
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Restricted to Computer but inactive
        $this->createTag([
            'name' => 'Inactive Computer Tag',
            '_itemtypes' => [Computer::class],
            'is_active' => 0,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // A tag with no restriction is allowed for every itemtype
        $this->assertSameTagIds(
            [$computer_tag->getID(), $all_assets_tag->getID()],
            Tag_Itemtype::getTagsByItemtype(Computer::class)
        );
        $this->assertSameTagIds(
            [$printer_tag->getID(), $all_assets_tag->getID()],
            Tag_Itemtype::getTagsByItemtype(Printer::class)
        );

        $expected_ids = [$computer_tag->getID(), $all_assets_tag->getID()];
        $actual_ids = Tag_Itemtype::getTagIdsByItemtype(Computer::class);
        sort($expected_ids);
        sort($actual_ids);
        $this->assertSame($expected_ids, $actual_ids);
        $this->assertSame([], Tag_Itemtype::getTagIdsByItemtype(Tag::class));

        // Tags allowed for both Computer and Printer
        $this->assertSameTagIds(
            [$all_assets_tag->getID()],
            Tag_Itemtype::getTagsByItemtypes([Computer::class, Printer::class])
        );

        // Duplicated itemtypes are counted once
        $this->assertSameTagIds(
            [$computer_tag->getID(), $all_assets_tag->getID()],
            Tag_Itemtype::getTagsByItemtypes([Computer::class, Computer::class])
        );

        // No itemtype given: no tags returned
        $this->assertSame([], Tag_Itemtype::getTagsByItemtypes([]));
    }

    /**
     * Assert that the given tags match the expected tag IDs, regardless of order.
     *
     * @param list<int> $expected_tags_ids
     * @param iterable<Tag> $tags
     */
    private function assertSameTagIds(array $expected_tags_ids, iterable $tags): void
    {
        $actual_tags_ids = [];
        foreach ($tags as $tag) {
            $actual_tags_ids[] = $tag->getID();
        }
        sort($expected_tags_ids);
        sort($actual_tags_ids);
        $this->assertSame($expected_tags_ids, $actual_tags_ids);
    }

    /**
     * Simulate the execution of a Tag_Item massive action ('add' or 'remove')
     *
     * @param string     $action_code action to run ('add' or 'remove')
     * @param CommonDBTM $item        item on which the action is run
     * @param array      $ids         ids of the items to process
     * @param array      $input       input submitted by the massive action form
     * @param int        $ok          expected number of successes
     * @param int        $ko          expected number of failures
     */
    private function processTagMassiveAction(
        string $action_code,
        CommonDBTM $item,
        array $ids,
        array $input,
        int $ok,
        int $ko
    ): void {
        $ma_ok = 0;
        $ma_ko = 0;

        $ma = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAction', 'addMessage', 'getInput', 'itemDone'])
            ->getMock();

        $ma->POST = $input;
        $ma->method('getAction')->willReturn($action_code);
        $ma->method('addMessage')->willReturn(null);
        $ma->method('getInput')->willReturn($input);
        $ma->method('itemDone')->willReturnCallback(
            function ($item, $ids, $res) use (&$ma_ok, &$ma_ko) {
                if ($res === MassiveAction::ACTION_OK) {
                    $ma_ok += 1;
                } elseif (in_array($res, [MassiveAction::ACTION_KO, MassiveAction::ACTION_NORIGHT], true)) {
                    $ma_ko += 1;
                } else {
                    throw new \RuntimeException("Unexpected result '$res' in itemDone callback");
                }
            }
        );

        // Clone the item, as processMassiveActionsForOneItemtype() mutates `fields['id']`
        // in place while looping over $ids, which would otherwise corrupt the caller's object.
        Tag_Item::processMassiveActionsForOneItemtype($ma, clone $item, $ids);

        $this->assertSame($ok, $ma_ok, "$ok success(es) expected but $ma_ok found");
        $this->assertSame($ko, $ma_ko, "$ko failure(s) expected but $ma_ko found");
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMassiveActionAddTag(): void
    {
        $this->login();

        [$computer1, $computer2] = $this->createItems(Computer::class, [
            ['name' => 'Test Computer 1', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Test Computer 2', 'entities_id' => $this->getTestRootEntity(true)],
        ]);
        $all_assets_tag = $this->createTag(['name' => 'All Assets Tag for MA add', 'entities_id' => $this->getTestRootEntity(true)]);
        $printer_tag = $this->createTag(['name' => 'Printer Tag for MA add', '_itemtypes' => [Printer::class], 'entities_id' => $this->getTestRootEntity(true)]);

        // Add a tag compatible with both computers: both succeed
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $all_assets_tag->getID()],
            2,
            0
        );
        $this->assertTrue(Tag_Item::hasTag($computer1, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer2, $all_assets_tag->getID()));

        // Add the same tag again: both fail, as it is already attached
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $all_assets_tag->getID()],
            0,
            2
        );

        // Add a tag restricted to another itemtype: both fail
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $printer_tag->getID()],
            0,
            2
        );
        $this->assertFalse(Tag_Item::hasTag($computer1, $printer_tag->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer2, $printer_tag->getID()));

        $this->assertTrue(Tag_Item::removeTag($computer1, $all_assets_tag->getID()));

        // Add a tag to one computer that has it and one that doesn't
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $all_assets_tag->getID()],
            1,
            1
        );
        $this->assertTrue(Tag_Item::hasTag($computer1, $all_assets_tag->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer2, $all_assets_tag->getID()));

        // Submitting the massive action without picking a tag (the dropdown's empty choice,
        // submitted as an empty string) must not crash and must report both items as failed
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => ''],
            0,
            2
        );

        // Same when the input is missing entirely
        $this->processTagMassiveAction(
            'add',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            [],
            0,
            2
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMassiveActionRemoveTag(): void
    {
        $this->login();

        [$computer1, $computer2] = $this->createItems(Computer::class, [
            ['name' => 'Test Computer 1', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Test Computer 2', 'entities_id' => $this->getTestRootEntity(true)],
        ]);
        $tag1 = $this->createTag(['name' => 'Tag 1 for MA remove', 'entities_id' => $this->getTestRootEntity(true)]);
        $tag2 = $this->createTag(['name' => 'Tag 2 for MA remove', 'entities_id' => $this->getTestRootEntity(true)]);

        $this->assertTrue(Tag_Item::addTag($computer1, $tag1->getID()));
        $this->assertTrue(Tag_Item::addTag($computer1, $tag2->getID()));
        $this->assertTrue(Tag_Item::addTag($computer2, $tag1->getID()));
        $this->assertTrue(Tag_Item::addTag($computer2, $tag2->getID()));

        // Remove the tag from both computers: both succeed
        $this->processTagMassiveAction(
            'remove',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $tag1->getID()],
            2,
            0
        );
        $this->assertFalse(Tag_Item::hasTag($computer1, $tag1->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer1, $tag2->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer2, $tag1->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer2, $tag2->getID()));

        // Remove the same tag again: both fail, as it is not attached anymore
        $this->processTagMassiveAction(
            'remove',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $tag1->getID()],
            0,
            2
        );

        $this->assertTrue(Tag_Item::addTag($computer1, $tag1->getID()));

        // Remove a tag from one computer that has it and one that doesn't
        $this->processTagMassiveAction(
            'remove',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => $tag1->getID()],
            1,
            1
        );
        $this->assertFalse(Tag_Item::hasTag($computer1, $tag1->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer1, $tag2->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer2, $tag1->getID()));
        $this->assertTrue(Tag_Item::hasTag($computer2, $tag2->getID()));

        $this->assertTrue(Tag_Item::addTag($computer1, $tag1->getID()));
        $this->assertTrue(Tag_Item::addTag($computer2, $tag1->getID()));

        // "Remove all at once" is submitted as an empty/0 peer_tags_id: both succeed
        $this->processTagMassiveAction(
            'remove',
            $computer1,
            [$computer1->getID(), $computer2->getID()],
            ['peer_tags_id' => 0],
            2,
            0
        );

        $this->assertFalse(Tag_Item::hasTag($computer1, $tag1->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer1, $tag2->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer2, $tag1->getID()));
        $this->assertFalse(Tag_Item::hasTag($computer2, $tag2->getID()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMassiveActionOnItemOutsideActiveEntities(): void
    {
        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'Computer outside active entities',
            'entities_id' => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $tag1 = $this->createTag(['name' => 'Tag 1 for MA entities', 'entities_id' => $this->getTestRootEntity(true), 'is_recursive' => 1]);
        $tag2 = $this->createTag(['name' => 'Tag 2 for MA entities', 'entities_id' => $this->getTestRootEntity(true), 'is_recursive' => 1]);
        $this->assertTrue(Tag_Item::addTag($computer, $tag1->getID()));

        // Restrict the active entities so that the computer is not reachable anymore
        $this->setEntity('_test_child_1', true);

        $this->processTagMassiveAction('add', $computer, [$computer->getID()], ['peer_tags_id' => $tag2->getID()], 0, 1);
        $this->assertFalse(Tag_Item::hasTag($computer, $tag2->getID()));

        $this->processTagMassiveAction('remove', $computer, [$computer->getID()], ['peer_tags_id' => $tag1->getID()], 0, 1);
        $this->assertTrue(Tag_Item::hasTag($computer, $tag1->getID()));

        $this->processTagMassiveAction('remove', $computer, [$computer->getID()], ['peer_tags_id' => 0], 0, 1);
        $this->assertTrue(Tag_Item::hasTag($computer, $tag1->getID()));
    }

    public function testDirectLinkCreationIsValidated(): void
    {
        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'Computer for direct link',
            'entities_id' => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $rule = $this->createItem(\RuleTicket::class, ['name' => 'Rule for direct link', 'entities_id' => $this->getTestRootEntity(true)]);
        $recursive_tag = $this->createTag(['name' => 'Recursive tag for direct link', 'entities_id' => $this->getTestRootEntity(true), 'is_recursive' => 1]);
        $printer_tag = $this->createTag(['name' => 'Printer tag for direct link', '_itemtypes' => [Printer::class], 'entities_id' => $this->getTestRootEntity(true), 'is_recursive' => 1]);
        $sibling_tag = $this->createTag(['name' => 'Sibling entity tag for direct link', 'entities_id' => getItemByTypeName('Entity', '_test_child_1', true)]);

        $tag_item = new Tag_Item();
        $input = ['tags_id' => $recursive_tag->getID(), 'itemtype' => Computer::class, 'items_id' => $computer->getID()];

        // Item that is not taggable
        $this->assertFalse($tag_item->add(['itemtype' => \RuleTicket::class, 'items_id' => $rule->getID()] + $input));

        // Item that does not exist
        $this->assertFalse($tag_item->add(['items_id' => 999999] + $input));

        // Tag restricted to another itemtype
        $this->assertFalse($tag_item->add(['tags_id' => $printer_tag->getID()] + $input));

        // Tag of a sibling entity, not visible from the item's entity, even if visible from the session
        $this->assertFalse($tag_item->add(['tags_id' => $sibling_tag->getID()] + $input));
        $this->assertFalse(Tag_Item::addTag($computer, $sibling_tag->getID()));

        // Valid link
        $this->assertGreaterThan(0, $tag_item->add($input));
        $this->assertTrue(Tag_Item::hasTag($computer, $recursive_tag->getID()));

        // The link cannot be moved to an invalid tag
        $this->assertFalse($tag_item->update(['id' => $tag_item->getID(), 'tags_id' => $printer_tag->getID()]));
        $this->assertTrue(Tag_Item::hasTag($computer, $recursive_tag->getID()));
    }

    public function testUpdateKeepsTagsNotProposedInForm(): void
    {
        $this->login();

        $computer = $this->createItem(Computer::class, ['name' => 'Computer for hidden tags', 'entities_id' => $this->getTestRootEntity(true)]);
        $tag1 = $this->createTag(['name' => 'Active tag 1 for hidden tags', 'is_active' => 1, 'entities_id' => $this->getTestRootEntity(true)]);
        $tag2 = $this->createTag(['name' => 'Active tag 2 for hidden tags', 'is_active' => 1, 'entities_id' => $this->getTestRootEntity(true)]);
        $inactive_tag = $this->createTag(['name' => 'Inactive tag for hidden tags', 'entities_id' => $this->getTestRootEntity(true), 'is_active' => 0]);

        foreach ([$tag1, $tag2, $inactive_tag] as $tag) {
            $this->assertTrue(Tag_Item::addTag($computer, $tag->getID()));
        }

        // The inactive tag is not proposed in the form, so it is not submitted and must be kept
        $this->assertTrue($computer->update(['id' => $computer->getID(), '_tags' => [$tag1->getID()]]));
        $this->assertEqualsCanonicalizing(
            [$tag1->getID(), $inactive_tag->getID()],
            Tag_Item::getTagsForItem($computer)
        );

        // Same when every proposed tag is unselected
        $this->assertTrue($computer->update(['id' => $computer->getID(), '_tags' => '']));
        $this->assertSame([$inactive_tag->getID()], Tag_Item::getTagsForItem($computer));
    }

    public function testTagsNotVisibleFromActiveEntitiesAreNotProposed(): void
    {
        $this->login();

        $root_entity_id = $this->getTestRootEntity(true);

        // A recursive computer of the root entity is visible from its child entities
        $computer = $this->createItem(Computer::class, [
            'name'         => 'Recursive computer for entity visibility',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 1,
        ]);
        $recursive_tag = $this->createTag([
            'name'         => 'Recursive tag for entity visibility',
            'is_active'    => 1,
            'entities_id'  => $root_entity_id,
            'is_recursive' => 1,
        ]);
        $local_tag = $this->createTag([
            'name'         => 'Not recursive tag for entity visibility',
            'is_active'    => 1,
            'entities_id'  => $root_entity_id,
            'is_recursive' => 0,
        ]);
        $this->assertTrue(Tag_Item::addTag($computer, $recursive_tag->getID()));
        $this->assertTrue(Tag_Item::addTag($computer, $local_tag->getID()));

        // From the root entity, both tags are proposed
        $proposed_tags_ids = Tag_Itemtype::getTagIdsByItemtype(Computer::class, Tag::getTagsEntityID($computer));
        $this->assertContains($recursive_tag->getID(), $proposed_tags_ids);
        $this->assertContains($local_tag->getID(), $proposed_tags_ids);

        // From a child entity, the not recursive tag of the root entity is not visible, so it is not proposed
        $this->setEntity('_test_child_1', true);
        $proposed_tags_ids = Tag_Itemtype::getTagIdsByItemtype(Computer::class, Tag::getTagsEntityID($computer));
        $this->assertContains($recursive_tag->getID(), $proposed_tags_ids);
        $this->assertNotContains($local_tag->getID(), $proposed_tags_ids);

        // As it is not proposed in the form, the not recursive tag is kept when every proposed tag is unselected
        $this->assertTrue($computer->update(['id' => $computer->getID(), '_tags' => '']));
        $this->assertSame([$local_tag->getID()], Tag_Item::getTagsForItem($computer));
    }

    public function testTagFieldIsPresentOnTaggableItemsForm(): void
    {
        $this->login();

        foreach (Tag_Itemtype::getTaggableItems() as $itemtype) {
            if ($itemtype === TesterComputer::class) {
                continue;
            }

            $item = $this->createTaggableTestItemtype($itemtype);

            ob_start();
            if ($item instanceof \KnowbaseItem) {
                $item->showFull(['mode' => 'edit']);
            } else {
                $item->showForm($item->getID(), ['withtemplate' => 0]);
            }
            $html = ob_get_clean();

            $crawler = new Crawler($html);
            $this->assertGreaterThan(
                0,
                $crawler->filter('select[name="_tags[]"]')->count(),
                sprintf('Tags field is missing from the "%s" form.', $itemtype)
            );
        }
    }
}
