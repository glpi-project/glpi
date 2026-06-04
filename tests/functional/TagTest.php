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
    private function verifItemtypes(Tag $tag, array|string $itemtypes): void
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
     */
    private function createTag(array $input): Tag
    {
        $tag = $this->createItem(Tag::class, $input, ['itemtypes']);
        $this->verifItemtypes($tag, $input['itemtypes']);

        return $tag;
    }

    /**
     * Update a tag with given input and verify itemtypes
      *
      * @param Tag $tag Tag to update
      * @param array $input Input data
      */
    private function updateTag(Tag $tag, array $input): void
    {
        $this->updateItem(Tag::class, $tag->getID(), $input, ['itemtypes']);
        $this->verifItemtypes($tag, $input['itemtypes']);
    }

    /**
     * Data provider for testAddTagWithItemtypes and testUpdateTagItemtypes
      *
      * Each case provides input for tag creation/update and expected itemtypes after operation
       *
       * @return iterable
       */
    public static function providerTestAddAndUpdateTagItemtypes(): iterable
    {
        yield 'itemtypes as array' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => [Computer::class],
            ],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'itemtypes is empty' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => "",
            ],
            'expected_itemtypes' => [],
        ];

        yield 'multiple itemtypes' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => [Printer::class, Computer::class],
            ],
            'expected_itemtypes' => [Printer::class, Computer::class],
        ];

        yield 'unauthorized itemtype' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => [Rule::class, Computer::class],
            ],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'duplicate itemtypes' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => [Computer::class, Computer::class],
            ],
            'expected_itemtypes' => [Computer::class],
        ];

        yield 'plugin itemtype' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => [TesterComputer::class],
            ],
            'expected_itemtypes' => [TesterComputer::class],
        ];

        yield 'custom asset itemtype' => [
            'input' => [
                'name' => 'Test Tag',
                'itemtypes' => ['Glpi\\CustomAsset\\Test01Asset'],
            ],
            'expected_itemtypes' => ['Glpi\\CustomAsset\\Test01Asset'],
        ];
    }

    /**
     * Test adding a tag with itemtypes and verify that itemtypes are correctly set
     */
    #[DataProvider('providerTestAddAndUpdateTagItemtypes')]
    public function testAddTagWithItemtypes(array $input, array $expected_itemtypes): void
    {
        $tag = $this->createTag($input);
        $this->assertSame($expected_itemtypes, $tag->getItemtypes());
    }

    /**
     * Test updating a tag with itemtypes and verify that itemtypes are correctly updated
     *
     * @param array $input Input data for update
     * @param array|string $expected_itemtypes Expected itemtypes after update
     */
    #[DataProvider('providerTestAddAndUpdateTagItemtypes')]
    public function testUpdateTagItemtypes(array $input, array $expected_itemtypes): void
    {
        $tag = $this->createTag([
            'name' => 'Test Tag',
            'itemtypes' => [Printer::class],
        ]);

        $this->updateTag($tag, $input);
        $this->assertSame($expected_itemtypes, $tag->getItemtypes());
    }

    /**
     * Test that purging a tag removes its itemtypes associations
      *
      * @param array $input Input data for tag creation
     */
    public function testPurgeTagRemovesItemtypes(): void
    {
        $tag = $this->createTag([
            'name' => 'Test Tag',
            'itemtypes' => [
                Printer::class,
                Computer::class,
                TesterComputer::class,
            ],
        ]);

        $tag_itemtype = new Tag_Itemtype();
        $tag_itemtypes = $tag_itemtype->find(['tags_id' => $tag->getID()]);
        $this->assertCount(3, $tag_itemtypes);
        $this->deleteItem(Tag::class, $tag->getID());
        $this->assertEmpty($tag_itemtype->find(['tags_id' => $tag->getID()]));
    }
}
