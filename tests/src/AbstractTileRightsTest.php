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

namespace Glpi\Tests;

use CommonDBTM;
use Glpi\Helpdesk\Tile\Item_Tile;
use Glpi\Helpdesk\Tile\TileInterface;
use Glpi\Helpdesk\Tile\TilesManager;
use Profile;
use ProfileRight;
use User;

abstract class AbstractTileRightsTest extends DbTestCase
{
    /**
     * @return class-string<CommonDBTM&TileInterface>
     */
    abstract protected function getTileClass(): string;

    /**
     * A valid creation input for the tested tile type.
     *
     * @return array<string, mixed>
     */
    abstract protected function getTileInput(): array;

    /**
     * A user that can view and update the item a tile is attached to must be
     * able to view / update / delete / purge / create that tile.
     */
    public function testTileIsManageableByUserAbleToUpdateLinkedItem(): void
    {
        // Arrange: a helpdesk profile that will hold the tile, and a tile linked
        // to it.
        $this->login();
        $linked_profile = $this->createHelpdeskProfile('Tile holder profile');
        [$tile_class, $tile_id] = $this->createTile($linked_profile);

        // Arrange: a user that can read and update profiles (thus the linked
        // profile) but does NOT have the "config" right.
        $this->createCentralUser('user_that_can_edit_profiles', [
            'profile' => READ | UPDATE,
        ]);

        // Act: log in as this user
        $this->login('user_that_can_edit_profiles');

        // Assert: the user is indeed able to update the linked profile...
        $this->assertTrue(
            $linked_profile->can($linked_profile->getID(), UPDATE),
            'Precondition failed: the user should be able to update the linked profile',
        );

        // ...and therefore must be able to manage the tile.
        $this->assertTrue((new $tile_class())->can($tile_id, READ));
        $this->assertTrue((new $tile_class())->can($tile_id, UPDATE));
        $this->assertTrue((new $tile_class())->can($tile_id, DELETE));
        $this->assertTrue((new $tile_class())->can($tile_id, PURGE));

        // ...and be able to create tiles for this item
        $create_input = [
            '_itemtype_item' => Profile::class,
            '_items_id_item' => $linked_profile->getID(),
        ];
        $this->assertTrue((new $tile_class())->can(-1, CREATE, $create_input));
    }

    /**
     * A user that can NOT update the item a tile is attached to must not be able
     * to manage that tile.
     */
    public function testTileIsNotManageableByUserUnableToUpdateLinkedItem(): void
    {
        // Arrange: a helpdesk profile that will hold the tile, and a tile linked
        // to it.
        $this->login();
        $linked_profile = $this->createHelpdeskProfile('Tile holder profile');
        [$tile_class, $tile_id] = $this->createTile($linked_profile);

        // Arrange: a user that holds the "config" right but can NOT update
        // profiles (thus can not update the linked profile).
        $this->createCentralUser('user_that_cant_edit_profiles', [
            'profile' => 0,
        ]);

        // Act: log in as this user
        $this->login('user_that_cant_edit_profiles');

        // Assert: the user can NOT update the linked profile...
        $this->assertFalse(
            $linked_profile->can($linked_profile->getID(), UPDATE),
            'Precondition failed: the user should not be able to update the linked profile',
        );

        // ...and therefore must NOT be able to manage the tile.
        $this->assertFalse((new $tile_class())->can($tile_id, READ));
        $this->assertFalse((new $tile_class())->can($tile_id, UPDATE));
        $this->assertFalse((new $tile_class())->can($tile_id, DELETE));
        $this->assertFalse((new $tile_class())->can($tile_id, PURGE));

        // ...and not be able to create tiles for this item
        $create_input = [
            '_itemtype_item' => Profile::class,
            '_items_id_item' => $linked_profile->getID(),
        ];
        $this->assertFalse((new $tile_class())->can(-1, CREATE, $create_input));
    }

    /**
     * The item validated by the create right check is the one that is
     * actually persisted: it is read from the same input, so it can not be
     * spoofed to pass the check while linking to another item.
     */
    public function testTileCreationLinksToTheHolderFromInput(): void
    {
        // Arrange
        $this->login();
        $linked_profile = $this->createHelpdeskProfile('Tile holder profile');

        // Act: create a tile, providing the holder through the input.
        $tile_class = $this->getTileClass();
        $input = $this->getTileInput() + [
            '_itemtype_item' => Profile::class,
            '_items_id_item' => $linked_profile->getID(),
        ];
        $tile = new $tile_class();
        $tile_id = $tile->add($input);
        $this->assertIsInt($tile_id);
        $this->assertGreaterThan(0, $tile_id);

        // Assert: the tile has been linked to the holder from the input.
        $item_tile = new Item_Tile();
        $this->assertTrue($item_tile->getFromDBByCrit([
            'itemtype_tile' => $tile_class,
            'items_id_tile' => $tile_id,
        ]));
        $this->assertEquals(Profile::class, $item_tile->fields['itemtype_item']);
        $this->assertEquals(
            $linked_profile->getID(),
            $item_tile->fields['items_id_item']
        );
    }

    private function createHelpdeskProfile(string $name): Profile
    {
        return $this->createItem(Profile::class, [
            'name'      => $name,
            'interface' => 'helpdesk',
        ]);
    }

    /**
     * Create the tested tile, linked to the given profile.
     *
     * @return array{0: class-string<CommonDBTM&TileInterface>, 1: int}
     */
    private function createTile(Profile $linked_profile): array
    {
        $tile_class = $this->getTileClass();

        $manager = TilesManager::getInstance();
        $item_tile_id = $manager->addTile(
            $linked_profile,
            $tile_class,
            $this->getTileInput()
        );

        $item_tile = Item_Tile::getById($item_tile_id);
        $tile_id = $item_tile->fields['items_id_tile'];

        return [$tile_class, $tile_id];
    }

    /**
     * Create a central user tied to a brand new profile with the given rights.
     *
     * @param array<string, int> $rights Map of right name => right value.
     */
    private function createCentralUser(string $login, array $rights): void
    {
        $profile = $this->createItem(Profile::class, [
            'name'      => $login . '_profile',
            'interface' => 'central',
        ]);

        foreach ($rights as $right_name => $right_value) {
            $profile_right = new ProfileRight();
            $this->assertTrue($profile_right->getFromDBByCrit([
                'profiles_id' => $profile->getID(),
                'name'        => $right_name,
            ]));
            $this->updateItem(ProfileRight::class, $profile_right->getID(), [
                'rights' => $right_value,
            ]);
        }

        $this->createItem(User::class, [
            'name'          => $login,
            'password'      => 'password',
            'password2'     => 'password',
            'profiles_id'   => $profile->getID(),
            '_profiles_id'  => $profile->getID(),
            '_entities_id'  => 0,
            '_is_recursive' => true,
        ], ['password', 'password2']);
    }
}
