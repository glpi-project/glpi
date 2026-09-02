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

use CommonDBRelation;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\ItemLinkException;
use Glpi\Exception\RedirectException;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use Group;
use Group_User;
use User;

class CommonDBRelationTest extends DbTestCase
{
    use ReAuthTrait;

    public function tearDown(): void
    {
        $this->restoreWebContext();
        parent::tearDown();
    }

    public function testCreateCheck(): void
    {
        /** both specific, both attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = true;

            public static ?string $itemtype_2 = \Holiday::class;
            public static ?string $items_id_2 = 'holidays_id';
            public static bool $mustBeAttached_2 = true;
        };

        //nothing in input
        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), $instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //zeroes in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_1 => 0, $instance::$items_id_2 => 0];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), $instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //only first in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_1 => 42];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //only second in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_2 => 42];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1)])
                ),
            ]
        );

        //null input (default) is OK
        try {
            $instance->check(-1, CREATE);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK - try with a real object
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        $instance = new \Calendar_Holiday();
        try {
            $instance->check(-1, CREATE, $input);
        } catch (AccessDeniedHttpException $e) {
            //this exception sounds not logical here; but this is not the point of current tests.
        }
        /** /both specific, both attached */

        /** both specific, first attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = true;

            public static ?string $itemtype_2 = \Holiday::class;
            public static ?string $items_id_2 = 'holidays_id';
            public static bool $mustBeAttached_2 = false;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1)])
                ),
            ]
        );
        /** /both specific, first attached */

        /** both specific, second attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = false;

            public static ?string $itemtype_2 = \Holiday::class;
            public static ?string $items_id_2 = 'holidays_id';
            public static bool $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );
        /** both specific, second attached */

        /** both specific, none attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = false;

            public static ?string $itemtype_2 = \Holiday::class;
            public static ?string $items_id_2 = 'holidays_id';
            public static bool $mustBeAttached_2 = false;
        };

        //nothing in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }
        /** /both specific, none attached */

        /** first only specific, all attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = true;

            public static ?string $itemtype_2 = 'itemtype';
            public static ?string $items_id_2 = 'items_id';
            public static bool $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), 'itemtype'])
                ),
            ]
        );
        /** /first only specific, all attached */

        /** first only specific, second suffixed, all attached */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \Calendar::class;
            public static ?string $items_id_1 = 'calendars_id';
            public static bool $mustBeAttached_1 = true;

            public static ?string $itemtype_2 = 'itemtype_peripheral';
            public static ?string $items_id_2 = 'items_id';
            public static bool $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), 'itemtype_peripheral'])
                ),
            ]
        );
        /** /first only specific, second suffixed, all attached */

        /** Entity with items_id = 0 is valid (root entity) */
        $instance = new class extends CommonDBRelation {
            public static ?string $itemtype_1 = \KnowbaseItem::class;
            public static ?string $items_id_1 = 'knowbaseitems_id';
            public static bool $mustBeAttached_1 = true;

            public static ?string $itemtype_2 = 'itemtype';
            public static ?string $items_id_2 = 'items_id';
            public static bool $mustBeAttached_2 = true;

            public static function getTable($classname = null)
            {
                return 'glpi_knowbaseitems_items'; // ensure using a table with expected fields, some backend code rely on table columns
            }
        };

        // items_id = 0 with Entity itemtype should pass validation
        $input = ['knowbaseitems_id' => 42, 'itemtype' => \Entity::class, 'items_id' => 0];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (AccessDeniedHttpException $e) {
            // no session is set up, so rights check fails after validation; this is expected
        }

        // items_id = 0 with non-Entity itemtype should fail validation
        $exception_thrown = false;
        try {
            $input = ['knowbaseitems_id' => 42, 'itemtype' => \Computer::class, 'items_id' => 0];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain a valid value for: %1$s',
                    'items_id'
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    'itemtype'
                ),
            ]
        );
        /** /Entity with items_id = 0 is valid (root entity) */
    }

    // --- Re-authentication ("sudo mode") propagation through the CommonDBRelation hierarchy ---

    /**
     * Group_User is a CommonDBRelation subclass that requires re-authentication.
     * changeActiveEntities('all') is required so that the group item created in entity 0
     * is visible regardless of the active entity restriction.
     */
    #[\PHPUnit\Framework\Attributes\Group('reauth')]
    public function testCanPropagatesReauthThroughRelation(): void
    {
        // --- arrange ---
        $this->login();
        $relation_id = $this->createGroupUser()->getID();
        $this->fakeWebContext();

        // --- act + assert : not re-authenticated → can() returns false and sets reauth_needed ---
        $this->setReauthenticated(false);
        $reauth_needed = null;
        $input = null;
        $this->assertFalse((new Group_User())->can($relation_id, READ, $input, $reauth_needed));
        $this->assertTrue($reauth_needed);

        // --- act + assert : re-authenticated → can() returns true and clears reauth_needed ---
        $this->setReauthenticated(true);
        $reauth_needed = null;
        $input = null;
        $this->assertTrue((new Group_User())->can($relation_id, READ, $input, $reauth_needed));
        $this->assertFalse($reauth_needed);
    }

    /**
     * CommonDBRelation overrides check() but delegates to parent::check(),
     * so the re-authentication redirect must still be triggered.
     */
    #[\PHPUnit\Framework\Attributes\Group('reauth')]
    public function testCheckOverrideStillRedirectsToReauthPrompt(): void
    {
        // --- arrange ---
        $this->login();
        $relation_id = $this->createGroupUser()->getID();
        $this->fakeWebContext();
        $this->setReauthenticated(false);

        // --- act + assert ---
        $this->expectException(RedirectException::class);
        (new Group_User())->check($relation_id, READ);
    }

    private function createGroupUser(): Group_User
    {
        $group = $this->createItem(
            Group::class,
            $this->getMinimalCreationInput(Group::class),
        );

        return $this->createItem(Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => getItemByTypeName(User::class, TU_USER, true),
        ]);
    }

}
