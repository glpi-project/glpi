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

use CommonDBConnexity;
use CommonDBRelation;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\ItemLinkException;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CommonDBRelationTest extends DbTestCase
{
    public function testCreateCheck(): void
    {
        /** both specific, both attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = true;
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
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = false;
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
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = false;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = true;
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
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = false;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = false;
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
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';
            public static $mustBeAttached_2 = true;
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
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = 'itemtype_peripheral';
            public static $items_id_2 = 'items_id';
            public static $mustBeAttached_2 = true;
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
            public static $itemtype_1 = \KnowbaseItem::class;
            public static $items_id_1 = 'knowbaseitems_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';
            public static $mustBeAttached_2 = true;

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

    public function testCannotCreateRelationWithoutUpdateRightOnReminder(): void
    {
        // Arrange: a reminder of another user, visible in all the entities.
        $this->login('glpi', 'glpi');
        $reminder = new \Reminder();
        $reminder_id = $reminder->add([
            'name'     => 'Public reminder',
            'text'     => 'Public reminder',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertGreaterThan(0, (new \Entity_Reminder())->add([
            'reminders_id' => $reminder_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        // Arrange: a user that can read this reminder, but that cannot update it.
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | \Reminder::PERSONAL;
        $this->assertTrue($reminder->getFromDB($reminder_id));
        $this->assertTrue($reminder->canViewItem());
        $this->assertFalse($reminder->canUpdateItem());

        $input = [
            'reminders_id' => $reminder_id,
            'users_id'     => getItemByTypeName(\User::class, 'tech', true),
        ];

        // Act: compute creation rights
        $can_create = (new \Reminder_User())->can(-1, CREATE, $input);

        // Assert: should be refused
        $this->assertFalse($can_create);
    }

    public function testCanCreateRelationWithUpdateRightOnReminder(): void
    {
        // Arrange: a reminder of another user, visible in all the entities.
        $this->login('glpi', 'glpi');
        $reminder = new \Reminder();
        $reminder_id = $reminder->add([
            'name'     => 'Public reminder',
            'text'     => 'Public reminder',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertGreaterThan(0, (new \Entity_Reminder())->add([
            'reminders_id' => $reminder_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        // Arrange: a user that can read and update this reminder
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE | \Reminder::PERSONAL;
        $this->assertTrue($reminder->getFromDB($reminder_id));
        $this->assertTrue($reminder->canViewItem());
        $this->assertTrue($reminder->canUpdateItem());

        $input = [
            'reminders_id' => $reminder_id,
            'users_id'     => getItemByTypeName(\User::class, 'tech', true),
        ];

        // Act: compute creation rights
        $can_create = (new \Reminder_User())->can(-1, CREATE, $input);

        // Assert: should be allowed
        $this->assertTrue($can_create);
    }

    public static function visibilityRelationProvider(): iterable
    {
        foreach ([\Reminder::class, \RSSFeed::class] as $itemtype) {
            $fkey = getForeignKeyFieldForItemType($itemtype);
            $prefix = $itemtype === \Reminder::class ? 'Reminder' : 'RSSFeed';
            yield "Entity_$prefix" => [$itemtype, "Entity_$prefix", ['entities_id' => 0, 'is_recursive' => 1]];
            yield "Group_$prefix" => [$itemtype, "Group_$prefix", ['groups_id' => '_test_group_1', 'entities_id' => 0]];
            yield "Profile_$prefix" => [$itemtype, "Profile_$prefix", ['profiles_id' => 'Super-Admin', 'entities_id' => 0]];
            yield "{$prefix}_User" => [$itemtype, "{$prefix}_User", ['users_id' => 'tech']];
        }
    }

    #[DataProvider('visibilityRelationProvider')]
    public function testVisibilityRelationRequiresUpdateRight(string $itemtype, string $relation_class, array $target): void
    {
        // Arrange: an item of another user, visible in all the entities.
        $this->login('glpi', 'glpi');
        $rightname = $itemtype::$rightname;
        $item = $this->createItem($itemtype, [
            'name'     => 'Public item',
            'text'     => 'Public item',
            'url'      => 'https://example.com/feed',
            'users_id' => \Session::getLoginUserID(),
        ], ['text', 'url']);
        $fkey = $item::getForeignKeyField();
        $this->createItem('Entity_' . ($itemtype === \Reminder::class ? 'Reminder' : 'RSSFeed'), [
            $fkey          => $item->getID(),
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $input = [$fkey => $item->getID()];
        foreach ($target as $field => $value) {
            $input[$field] = match ($field) {
                'groups_id'   => getItemByTypeName(\Group::class, $value, true),
                'profiles_id' => getItemByTypeName(\Profile::class, $value, true),
                'users_id'    => getItemByTypeName(\User::class, $value, true),
                default       => $value,
            };
        }

        // Act/Assert: a user that can only read the item cannot add a visibility target
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile'][$rightname] = READ | $itemtype::PERSONAL;
        $this->assertTrue($item->getFromDB($item->getID()));
        $this->assertTrue($item->canViewItem());
        $this->assertFalse($item->canUpdateItem());
        $this->assertFalse((new $relation_class())->can(-1, CREATE, $input));

        // Act/Assert: a user that can also update the item can add it
        $_SESSION['glpiactiveprofile'][$rightname] = READ | UPDATE | $itemtype::PERSONAL;
        $this->assertTrue($item->canUpdateItem());
        $this->assertTrue((new $relation_class())->can(-1, CREATE, $input));
    }

    public function testRSSFeedOwnerCanShareWithUserWithoutUserReadRight(): void
    {
        // Arrange: a feed of the current user, who has no global right on users
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile']['rssfeed_public'] = READ | UPDATE | \RSSFeed::PERSONAL;
        $_SESSION['glpiactiveprofile']['user'] = 0;
        $feed = $this->createItem(\RSSFeed::class, [
            'name' => 'My feed',
            'url' => 'https://example.com/feed',
            'users_id' => \Session::getLoginUserID(),
        ], ['url']);

        $input = [
            'rssfeeds_id' => $feed->getID(),
            'users_id' => getItemByTypeName(\User::class, 'tech', true),
        ];

        // Act/Assert: the owner can still share the feed with a user
        $this->assertTrue(\RSSFeed_User::canCreate());
        $this->assertTrue((new \RSSFeed_User())->can(-1, CREATE, $input));
    }

    /**
     * Session right levels used by the rights matrix, and the item rights they produce on a
     * `CommonDBVisible` item that the current user can see but does not own.
     *
     * - `none`        : canViewItem() false, canUpdateItem() false
     * - `read`        : canViewItem() true,  canUpdateItem() false
     * - `read+update` : canViewItem() true,  canUpdateItem() true
     *
     * @return array<string, int>
     */
    private static function rightLevels(): array
    {
        return [
            'none'        => 0,
            'read'        => READ,
            'read+update' => READ | UPDATE,
        ];
    }

    /**
     * Create a reminder and an RSS feed owned by `glpi` and made visible to the whole entity
     * tree, then log in as `normal` so that both items are visible but not owned.
     *
     * @return array{int, int} reminder id, RSS feed id
     */
    private function createSharedReminderAndRssFeed(): array
    {
        $this->login('glpi', 'glpi');

        $reminders_id = (new \Reminder())->add([
            'name'     => 'Shared reminder',
            'text'     => 'Shared reminder',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->assertGreaterThan(0, $reminders_id);
        $this->assertGreaterThan(0, (new \Entity_Reminder())->add([
            'reminders_id' => $reminders_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        $rssfeeds_id = (new \RSSFeed())->add([
            'name'      => 'Shared RSS feed',
            'url'       => 'https://glpi-project.org/feed',
            'users_id'  => \Session::getLoginUserID(),
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $rssfeeds_id);
        $this->assertGreaterThan(0, (new \Entity_RSSFeed())->add([
            'rssfeeds_id'  => $rssfeeds_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        $this->login('normal', 'normal');

        return [$reminders_id, $rssfeeds_id];
    }

    /**
     * Guard test: if this one fails, every rights matrix assertion below is meaningless because
     * the fixtures themselves do not behave as the matrix assumes.
     */
    public function testSharedFixturesProduceTheExpectedItemRights(): void
    {
        [$reminders_id, $rssfeeds_id] = $this->createSharedReminderAndRssFeed();

        $reminder = new \Reminder();
        $rssfeed  = new \RSSFeed();

        foreach (self::rightLevels() as $level => $rights) {
            $_SESSION['glpiactiveprofile']['reminder_public'] = $rights;
            $_SESSION['glpiactiveprofile']['rssfeed_public']  = $rights;

            $this->assertTrue($reminder->getFromDB($reminders_id));
            $this->assertTrue($rssfeed->getFromDB($rssfeeds_id));

            $this->assertSame($rights !== 0, $reminder->canViewItem(), "reminder view @ $level");
            $this->assertSame($rights !== 0, $rssfeed->canViewItem(), "rssfeed view @ $level");

            $writable = ($rights & UPDATE) === UPDATE;
            $this->assertSame($writable, $reminder->canUpdateItem(), "reminder update @ $level");
            $this->assertSame($writable, $rssfeed->canUpdateItem(), "rssfeed update @ $level");
        }
    }

    /**
     * Expected result of `canRelationItem('canUpdateItem', 'canUpdate', true, false)` for every
     * combination of `$checkItem_1_Rights` / `$checkItem_2_Rights`, against every combination of
     * item rights on the two ends. Both ends resolve, so no `CommonDBConnexityItemNotFound` is
     * involved.
     *
     * The expected values are derived from the documented contract, not from the implementation:
     *
     * - `DONT_CHECK_ITEM_RIGHTS`  : the end grants unconditionally.
     * - `HAVE_VIEW_RIGHT_ON_ITEM` : the end grants when `canViewItem()` is true.
     * - `HAVE_SAME_RIGHT_ON_ITEM` : the end grants when `canUpdateItem()` is true.
     * - When *both* ends are `HAVE_SAME_RIGHT_ON_ITEM` and `$forceCheckBoth` is false, the
     *   relaxed "one write is enough" rule applies: write on one end plus view on the other.
     *   A `DONT_CHECK_ITEM_RIGHTS` end is an absence of a check, not a right, so it can never
     *   stand in for the write half of that rule.
     *
     * @return iterable<string, array{int, int, array<string, array<string, bool>>}>
     */
    public static function canRelationItemRightsProvider(): iterable
    {
        $dont_check = CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS;
        $view       = CommonDBConnexity::HAVE_VIEW_RIGHT_ON_ITEM;
        $same       = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

        // "one write is enough": write on one end + view on the other
        yield 'same / same' => [$same, $same, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => false, 'read' => false, 'read+update' => true],
            'read+update' => ['none' => false, 'read' => true,  'read+update' => true],
        ]];

        // write on end 1 + view on end 2
        yield 'same / view' => [$same, $view, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read+update' => ['none' => false, 'read' => true,  'read+update' => true],
        ]];

        // write on end 1, end 2 is not checked
        yield 'same / dont check' => [$same, $dont_check, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read+update' => ['none' => true,  'read' => true,  'read+update' => true],
        ]];

        // view on end 1 + write on end 2
        yield 'view / same' => [$view, $same, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => false, 'read' => false, 'read+update' => true],
            'read+update' => ['none' => false, 'read' => false, 'read+update' => true],
        ]];

        // view on both ends
        yield 'view / view' => [$view, $view, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => false, 'read' => true,  'read+update' => true],
            'read+update' => ['none' => false, 'read' => true,  'read+update' => true],
        ]];

        // view on end 1, end 2 is not checked
        yield 'view / dont check' => [$view, $dont_check, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => false],
            'read'        => ['none' => true,  'read' => true,  'read+update' => true],
            'read+update' => ['none' => true,  'read' => true,  'read+update' => true],
        ]];

        // end 1 is not checked, write on end 2
        yield 'dont check / same' => [$dont_check, $same, [
            'none'        => ['none' => false, 'read' => false, 'read+update' => true],
            'read'        => ['none' => false, 'read' => false, 'read+update' => true],
            'read+update' => ['none' => false, 'read' => false, 'read+update' => true],
        ]];

        // end 1 is not checked, view on end 2
        yield 'dont check / view' => [$dont_check, $view, [
            'none'        => ['none' => false, 'read' => true, 'read+update' => true],
            'read'        => ['none' => false, 'read' => true, 'read+update' => true],
            'read+update' => ['none' => false, 'read' => true, 'read+update' => true],
        ]];

        // neither end is checked
        yield 'dont check / dont check' => [$dont_check, $dont_check, [
            'none'        => ['none' => true, 'read' => true, 'read+update' => true],
            'read'        => ['none' => true, 'read' => true, 'read+update' => true],
            'read+update' => ['none' => true, 'read' => true, 'read+update' => true],
        ]];
    }

    /**
     * @param array<string, array<string, bool>> $expected
     */
    #[DataProvider('canRelationItemRightsProvider')]
    public function testCanRelationItemRightsMatrix(int $rights_1, int $rights_2, array $expected): void
    {
        [$reminders_id, $rssfeeds_id] = $this->createSharedReminderAndRssFeed();

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = \RSSFeed::class;
            public static $items_id_2 = 'rssfeeds_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $mustBeAttached_1       = true;
            public static $mustBeAttached_2       = true;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = false;
        };
        // Anonymous classes are declared once per code location, so the statics are also reset
        // explicitly on every data provider row.
        $instance::$checkItem_1_Rights     = $rights_1;
        $instance::$checkItem_2_Rights     = $rights_2;
        $instance::$mustBeAttached_1       = true;
        $instance::$mustBeAttached_2       = true;
        $instance::$checkAlwaysBothItems   = false;
        $instance::$check_entity_coherency = false;
        $instance->fields = [
            'reminders_id' => $reminders_id,
            'rssfeeds_id'  => $rssfeeds_id,
        ];

        $levels = self::rightLevels();
        foreach ($expected as $level_1 => $per_level_2) {
            foreach ($per_level_2 as $level_2 => $can) {
                $_SESSION['glpiactiveprofile']['reminder_public'] = $levels[$level_1];
                $_SESSION['glpiactiveprofile']['rssfeed_public']  = $levels[$level_2];

                $this->assertSame(
                    $can,
                    $instance->canRelationItem('canUpdateItem', 'canUpdate', true, false),
                    sprintf('reminder = %s, rssfeed = %s', $level_1, $level_2)
                );
            }
        }
    }

    /**
     * `canViewItem()` is the only caller that passes `$forceCheckBoth = true`, so the relaxed
     * "one write is enough" rule never applies to it and both ends must be viewable.
     */
    public function testCanViewItemAlwaysChecksBothEnds(): void
    {
        [$reminders_id, $rssfeeds_id] = $this->createSharedReminderAndRssFeed();

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = \RSSFeed::class;
            public static $items_id_2 = 'rssfeeds_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = false;
        };
        $instance->fields = [
            'reminders_id' => $reminders_id,
            'rssfeeds_id'  => $rssfeeds_id,
        ];

        // Write on the reminder is not enough to view the relation if the RSS feed is invisible.
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = 0;
        $this->assertFalse($instance->canViewItem());

        // Read on both ends is enough.
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = READ;
        $this->assertTrue($instance->canViewItem());
    }

    /**
     * `$checkAlwaysBothItems` is currently false in every class of the tree, so this knob is
     * effectively untested dead configuration. Pin its meaning before anyone relies on it.
     */
    public function testCheckAlwaysBothItemsDisablesTheRelaxedRule(): void
    {
        [$reminders_id, $rssfeeds_id] = $this->createSharedReminderAndRssFeed();

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = \RSSFeed::class;
            public static $items_id_2 = 'rssfeeds_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = false;
        };
        $instance->fields = [
            'reminders_id' => $reminders_id,
            'rssfeeds_id'  => $rssfeeds_id,
        ];

        // Write on the RSS feed, read only on the reminder.
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = READ | UPDATE;

        $instance::$checkAlwaysBothItems = false;
        $this->assertTrue($instance->canUpdateItem());

        $instance::$checkAlwaysBothItems = true;
        $this->assertFalse($instance->canUpdateItem());

        $instance::$checkAlwaysBothItems = false;
    }

    /**
     * @return iterable<string, array{int, bool, array<string, mixed>, bool}>
     */
    public static function canRelationItemUnresolvedEndProvider(): iterable
    {
        $dont_check = CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS;
        $view       = CommonDBConnexity::HAVE_VIEW_RIGHT_ON_ITEM;
        $same       = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

        // An end with no itemtype chosen yet: this is the shape of `Item_Rack` while the
        // creation form is being rendered (see GitHub issue #25580).
        $unset       = ['itemtype' => '', 'items_id' => 0];
        // An end pointing at a row that does not exist (any more).
        $nonexistent = ['itemtype' => \RSSFeed::class, 'items_id' => 999999999];

        foreach (['not set' => $unset, 'nonexistent' => $nonexistent] as $state => $fields) {
            foreach (['same' => $same, 'view' => $view, 'dont check' => $dont_check] as $label => $rights) {
                // An end that must be attached cannot be left dangling...
                yield "$label / $state / mandatory" => [$rights, true, $fields, false];
                // ...but an optional end may be, and then the entity check has nothing to
                // compare so it is skipped rather than failed.
                yield "$label / $state / optional" => [$rights, false, $fields, true];
            }
        }
    }

    /**
     * @param array<string, mixed> $end_2_fields
     */
    #[DataProvider('canRelationItemUnresolvedEndProvider')]
    public function testCanRelationItemWithUnresolvedEnd(
        int $rights_2,
        bool $must_be_attached_2,
        array $end_2_fields,
        bool $expected
    ): void {
        [$reminders_id, ] = $this->createSharedReminderAndRssFeed();

        // Full rights on both ends: only the resolution of end 2 decides the outcome.
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = READ | UPDATE;

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $mustBeAttached_1       = true;
            public static $mustBeAttached_2       = true;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = true;
        };
        $instance::$checkItem_1_Rights     = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;
        $instance::$checkItem_2_Rights     = $rights_2;
        $instance::$mustBeAttached_1       = true;
        $instance::$mustBeAttached_2       = $must_be_attached_2;
        $instance::$checkAlwaysBothItems   = false;
        // Left enabled on purpose: this is the default, and the regression is precisely that an
        // unresolved end reaches the entity coherency block instead of disabling it.
        $instance::$check_entity_coherency = true;
        $instance->fields = ['reminders_id' => $reminders_id] + $end_2_fields;

        $this->assertSame(
            $expected,
            $instance->canRelationItem('canUpdateItem', 'canUpdate', true, false)
        );
    }

    /**
     * Sanity counterpart of the above: when end 2 does resolve, it is evaluated normally and the
     * entity coherency block is reached with both items loaded.
     */
    public function testCanRelationItemWithResolvedPolymorphicEnd(): void
    {
        [$reminders_id, $rssfeeds_id] = $this->createSharedReminderAndRssFeed();

        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = READ | UPDATE;

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $mustBeAttached_1       = true;
            public static $mustBeAttached_2       = true;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = true;
        };
        $instance->fields = [
            'reminders_id' => $reminders_id,
            'itemtype'     => \RSSFeed::class,
            'items_id'     => $rssfeeds_id,
        ];

        $this->assertTrue($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));

        // Both ends are HAVE_SAME_RIGHT_ON_ITEM, so "one write is enough": write on the reminder
        // plus view on the RSS feed still passes.
        $_SESSION['glpiactiveprofile']['rssfeed_public'] = READ;
        $this->assertTrue($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));

        // Read-only on both ends does not.
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ;
        $this->assertFalse($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));
    }

    /**
     * `isAttach1Valid()` / `isAttach2Valid()` let a class accept a relation whose end is
     * deliberately not attached. `CommonITILActor` is the only implementation in the tree: it
     * accepts `users_id = 0` when an `alternative_email` is provided (anonymous ITIL actor).
     */
    public function testCanRelationItemConsultsIsAttachValidHook(): void
    {
        [$reminders_id, ] = $this->createSharedReminderAndRssFeed();

        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['rssfeed_public']  = READ | UPDATE;

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Reminder::class;
            public static $items_id_1 = 'reminders_id';
            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $mustBeAttached_1       = true;
            public static $mustBeAttached_2       = true;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = true;

            public function isAttach2Valid(array &$input)
            {
                return !empty($input['alternative_email']);
            }
        };

        foreach (
            [
                'rights checked end'   => CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM,
                'unchecked end'        => CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS,
            ] as $label => $rights_2
        ) {
            $instance::$checkItem_2_Rights = $rights_2;

            $instance->fields = [
                'reminders_id'      => $reminders_id,
                'itemtype'          => '',
                'items_id'          => 0,
                'alternative_email' => '',
            ];
            $this->assertFalse(
                $instance->canRelationItem('canUpdateItem', 'canUpdate', true, false),
                "$label, no alternative email"
            );

            $instance->fields['alternative_email'] = 'external.user@example.com';
            $this->assertTrue(
                $instance->canRelationItem('canUpdateItem', 'canUpdate', true, false),
                "$label, alternative email provided"
            );
        }
    }

    // -----------------------------------------------------------------------------------------
    // canRelationItem(): entity coherency
    // -----------------------------------------------------------------------------------------

    public function testCanRelationItemChecksEntityCoherency(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $root    = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $child_1 = getItemByTypeName(\Entity::class, '_test_child_1', true);
        $child_2 = getItemByTypeName(\Entity::class, '_test_child_2', true);

        $computer_child_1 = $this->createItem(\Computer::class, [
            'name'         => 'Computer in child 1',
            'entities_id'  => $child_1,
            'is_recursive' => 0,
        ]);
        $computer_root_recursive = $this->createItem(\Computer::class, [
            'name'         => 'Recursive computer in root',
            'entities_id'  => $root,
            'is_recursive' => 1,
        ]);
        $monitor_child_1 = $this->createItem(\Monitor::class, [
            'name'         => 'Monitor in child 1',
            'entities_id'  => $child_1,
            'is_recursive' => 0,
        ]);
        $monitor_child_2 = $this->createItem(\Monitor::class, [
            'name'         => 'Monitor in child 2',
            'entities_id'  => $child_2,
            'is_recursive' => 0,
        ]);

        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Computer::class;
            public static $items_id_1 = 'computers_id';
            public static $itemtype_2 = \Monitor::class;
            public static $items_id_2 = 'monitors_id';

            public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
            public static $mustBeAttached_1       = true;
            public static $mustBeAttached_2       = true;
            public static $checkAlwaysBothItems   = false;
            public static $check_entity_coherency = true;
        };
        $instance::$check_entity_coherency = true;

        // Same entity: allowed.
        $instance->fields = [
            'computers_id' => $computer_child_1->getID(),
            'monitors_id'  => $monitor_child_1->getID(),
        ];
        $this->assertTrue($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));

        // Unrelated sibling entities: refused, even though rights are held on both items.
        $instance->fields = [
            'computers_id' => $computer_child_1->getID(),
            'monitors_id'  => $monitor_child_2->getID(),
        ];
        $this->assertFalse($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));

        // A recursive item in an ancestor entity is visible from the descendant: allowed.
        $instance->fields = [
            'computers_id' => $computer_root_recursive->getID(),
            'monitors_id'  => $monitor_child_2->getID(),
        ];
        $this->assertTrue($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));

        // Classes opting out of the check (Contract_User, Ticket_Contract, Ticket_Ticket) are
        // not subject to it.
        $instance::$check_entity_coherency = false;
        $instance->fields = [
            'computers_id' => $computer_child_1->getID(),
            'monitors_id'  => $monitor_child_2->getID(),
        ];
        $this->assertTrue($instance->canRelationItem('canUpdateItem', 'canUpdate', true, false));
        $instance::$check_entity_coherency = true;

        // Entity coherency is only enforced on create/update: canDeleteItem() and canPurgeItem()
        // pass $check_entity = false so an already stored cross-entity relation stays removable.
        $this->assertFalse($instance->canCreateItem());
        $this->assertFalse($instance->canUpdateItem());
        $this->assertTrue($instance->canDeleteItem());
        $this->assertTrue($instance->canPurgeItem());
    }

    /**
     * Regression test.
     * GitHub issue #25580: clicking "+" on a rack unit renders the creation form, which calls
     * `Item_Rack::can(-1, CREATE, ...)` before any itemtype has been chosen. The unchecked end is
     * legitimately unset at that point and `$mustBeAttached_2` is false, so the check must pass.
     */
    public function testItemRackCreationIsAllowedBeforeAnItemIsChosen(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $rack = new \Rack();
        $this->assertGreaterThan(0, $rack->add([
            'name'         => 'Test rack',
            'number_units' => 10,
            'dcrooms_id'   => 0,
            'position'     => 0,
            'entities_id'  => getItemByTypeName(\Entity::class, '_test_root_entity', true),
        ]));

        $input = [
            'racks_id'    => $rack->getID(),
            'position'    => 5,
            'orientation' => 0,
        ];

        $this->assertTrue((new \Item_Rack())->can(-1, CREATE, $input));
    }

    /**
     * Regression test.
     * GitHub issue #25591: `POST /apirest.php/Ticket_User` with `users_id = 0` and an
     * `alternative_email` (anonymous requester) must be accepted, because
     * `CommonITILActor::isAttach2Valid()` declares that shape valid despite `$mustBeAttached_2`.
     */
    public function testTicketUserAcceptsAnonymousActorWithAlternativeEmail(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket with an external requester',
            'content'     => 'Ticket with an external requester',
            'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
        ]);

        $input = [
            'tickets_id'        => $ticket->getID(),
            'users_id'          => 0,
            'type'              => \CommonITILActor::REQUESTER,
            'use_notification'  => 1,
            'alternative_email' => 'external.user@example.com',
        ];
        $this->assertTrue((new \Ticket_User())->can(-1, CREATE, $input));

        // Without an alternative email there is nothing to attach the actor to.
        $input['alternative_email'] = '';
        $this->assertFalse((new \Ticket_User())->can(-1, CREATE, $input));
    }

    /**
     * Regression test.
     * Counterpart of testCannotCreateRelationWithoutUpdateRightOnReminder(): the visibility
     * target itself is mandatory, so a `Reminder_User` without a user must be refused even
     * though the `User` end is not rights-checked.
     */
    public function testReminderUserRequiresTheUserToBeAttached(): void
    {
        [$reminders_id, ] = $this->createSharedReminderAndRssFeed();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE | \Reminder::PERSONAL;

        $input = ['reminders_id' => $reminders_id];
        $this->assertFalse((new \Reminder_User())->can(-1, CREATE, $input));
    }
}
