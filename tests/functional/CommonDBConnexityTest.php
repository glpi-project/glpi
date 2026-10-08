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
use CommonDBConnexityItemNotFound;
use CommonDBTM;
use Entity_Reminder;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Reminder;
use Reminder_User;
use Session;

class CommonDBConnexityTest extends DbTestCase
{
    /**
     * A bare CommonDBConnexity instance. `canConnexity()`, `canConnexityItem()` and
     * `getItemFromArray()` all take the itemtype/items_id field names as arguments rather than
     * reading the statics, so no configuration is needed and nothing can leak between tests.
     *
     * @param array<string, mixed> $fields
     */
    private function makeConnexity(array $fields = []): CommonDBConnexity
    {
        $instance = new class extends CommonDBConnexity {
            public static function getItemField($itemtype): string
            {
                return '';
            }
        };
        $instance->fields = $fields;

        return $instance;
    }

    /**
     * A document the session can reach, so that `CommonDBTM::canViewItem()` / `canUpdateItem()`
     * (which only check the entity) return true and the assertions below isolate the global
     * right check.
     */
    private function createReachableDocument(): CommonDBTM
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        return $this->createItem(\Document::class, [
            'name'        => 'Connexity test document',
            'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
        ]);
    }

    public function testGetItemFromArrayResolvesAFixedItemtype(): void
    {
        $document = $this->createReachableDocument();

        $item = CommonDBConnexity::getItemFromArray(
            \Document::class,
            'documents_id',
            ['documents_id' => $document->getID()]
        );

        $this->assertInstanceOf(\Document::class, $item);
        $this->assertSame($document->getID(), $item->getID());
    }

    public function testGetItemFromArrayResolvesAPolymorphicItemtype(): void
    {
        $document = $this->createReachableDocument();

        $item = CommonDBConnexity::getItemFromArray(
            'itemtype',
            'items_id',
            ['itemtype' => \Document::class, 'items_id' => $document->getID()]
        );

        $this->assertInstanceOf(\Document::class, $item);
        $this->assertSame($document->getID(), $item->getID());
    }

    /**
     * Everything that cannot be resolved collapses to the same `false`. In particular the helper
     * cannot distinguish "no itemtype chosen yet" from "itemtype points at a row that does not
     * exist" -- a distinction some callers would like to make.
     *
     * @return iterable<string, array{string, string, array<string, mixed>}>
     */
    public static function unresolvableItemProvider(): iterable
    {
        yield 'fixed itemtype, missing id field' => [\Document::class, 'documents_id', []];
        yield 'fixed itemtype, id 0'             => [\Document::class, 'documents_id', ['documents_id' => 0]];
        yield 'fixed itemtype, unknown id'       => [\Document::class, 'documents_id', ['documents_id' => 999999999]];
        yield 'polymorphic, missing itemtype'    => ['itemtype', 'items_id', ['items_id' => 1]];
        yield 'polymorphic, empty itemtype'      => ['itemtype', 'items_id', ['itemtype' => '', 'items_id' => 1]];
        yield 'polymorphic, unknown class'       => ['itemtype', 'items_id', ['itemtype' => 'NotAnItemtype', 'items_id' => 1]];
        yield 'polymorphic, unknown id'          => ['itemtype', 'items_id', ['itemtype' => \Document::class, 'items_id' => 999999999]];
    }

    /**
     * @param array<string, mixed> $fields
     */
    #[DataProvider('unresolvableItemProvider')]
    public function testGetItemFromArrayReturnsFalseWhenItCannotResolve(
        string $itemtype,
        string $items_id,
        array $fields
    ): void {
        $this->login();

        $this->assertFalse(CommonDBConnexity::getItemFromArray($itemtype, $items_id, $fields));
    }

    /**
     * With `$getFromDBOrEmpty`, an id that does not resolve yields an *empty* object rather than
     * false. Callers that then read rights or entity off that object are reading defaults, not
     * the record they asked for. Used by CommonDBRelation::affectRelation() and
     * Item_OperatingSystem::getConnexityItem().
     */
    public function testGetItemFromArrayFallsBackToAnEmptyItem(): void
    {
        $this->login();

        $item = CommonDBConnexity::getItemFromArray(
            \Document::class,
            'documents_id',
            ['documents_id' => 999999999],
            true,
            true,
            true
        );

        $this->assertInstanceOf(\Document::class, $item);
        $this->assertSame(0, (int) $item->fields['id']);
    }

    /**
     * @return iterable<string, array{int, string, int, bool}>
     */
    public static function canConnexityProvider(): iterable
    {
        $dont_check = CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS;
        $view       = CommonDBConnexity::HAVE_VIEW_RIGHT_ON_ITEM;
        $same       = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

        // A fixed itemtype can be resolved statically, so the global right is checked here.
        yield 'fixed, dont check, no right'  => [$dont_check, \Document::class, 0, true];
        yield 'fixed, view, no right'        => [$view, \Document::class, 0, false];
        yield 'fixed, view, read'            => [$view, \Document::class, READ, true];
        yield 'fixed, same, read'            => [$same, \Document::class, READ, false];
        yield 'fixed, same, read+update'     => [$same, \Document::class, READ | UPDATE, true];

        // A polymorphic end has no class to call the static on, so canConnexity() always returns
        // true. That is "cannot tell", not "allowed": the decision is deferred to
        // canConnexityItem(), which is why that method has to be exact.
        yield 'polymorphic, dont check'      => [$dont_check, 'itemtype', 0, true];
        yield 'polymorphic, view, no right'  => [$view, 'itemtype', 0, true];
        yield 'polymorphic, same, no right'  => [$same, 'itemtype', 0, true];
    }

    #[DataProvider('canConnexityProvider')]
    public function testCanConnexity(int $item_right, string $itemtype, int $rights, bool $expected): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['document'] = $rights;

        $instance = $this->makeConnexity();

        $this->assertSame(
            $expected,
            $instance::canConnexity('canUpdate', $item_right, $itemtype, 'items_id')
        );
    }

    /**
     * A rights-checked end reports a missing item by throwing, which is how callers reach their
     * `$mustBeAttached` / `isAttachNValid()` handling.
     *
     * @return iterable<string, array{int}>
     */
    public static function rightsCheckedEndProvider(): iterable
    {
        yield 'view' => [CommonDBConnexity::HAVE_VIEW_RIGHT_ON_ITEM];
        yield 'same' => [CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM];
    }

    #[DataProvider('rightsCheckedEndProvider')]
    public function testCanConnexityItemThrowsWhenARightsCheckedItemIsMissing(int $item_right): void
    {
        $this->login();
        $instance = $this->makeConnexity(['documents_id' => 999999999]);

        $this->expectException(CommonDBConnexityItemNotFound::class);
        $instance->canConnexityItem(
            'canUpdateItem',
            'canUpdate',
            $item_right,
            \Document::class,
            'documents_id'
        );
    }

    /**
     * ...but an unchecked end does not. It returns true and leaves `&$item` null, so the caller
     * has no way to know the item is missing unless it inspects `$item` itself. This asymmetry
     * is the root of GitHub issues #25580 and #25591: CommonDBRelation used to notice by
     * accident, through a second probe that no longer runs.
     *
     * This is characterization of today's behaviour, not a contract assertion. If the missing
     * item detection is ever moved into this method, this test is expected to flip to
     * expectException() -- that is the fix landing, not a regression.
     */
    public function testCanConnexityItemStaysSilentWhenAnUncheckedItemIsMissing(): void
    {
        $this->login();
        $instance = $this->makeConnexity(['documents_id' => 999999999]);

        $item = null;
        $this->assertTrue(
            $instance->canConnexityItem(
                'canUpdateItem',
                'canUpdate',
                CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS,
                \Document::class,
                'documents_id',
                $item
            )
        );
        $this->assertNull($item);
    }

    /**
     * The item is loaded and handed back through `&$item` even when no right is checked, so
     * callers can still use it for entity coherency.
     */
    public function testCanConnexityItemExposesTheResolvedItem(): void
    {
        $document = $this->createReachableDocument();

        $item = null;
        $instance = $this->makeConnexity(['documents_id' => $document->getID()]);
        $instance->canConnexityItem(
            'canUpdateItem',
            'canUpdate',
            CommonDBConnexity::DONT_CHECK_ITEM_RIGHTS,
            \Document::class,
            'documents_id',
            $item
        );

        $this->assertInstanceOf(\Document::class, $item);
        $this->assertSame($document->getID(), $item->getID());
    }

    /**
     * A caller-supplied `&$item` is trusted as-is and short-circuits the lookup entirely -- the
     * fields are never consulted. CommonDBRelation relies on this to avoid re-reading an end
     * between its `$canN` and `$viewN` probes.
     */
    public function testCanConnexityItemReusesACallerSuppliedItem(): void
    {
        $document = $this->createReachableDocument();
        $_SESSION['glpiactiveprofile']['document'] = READ | UPDATE;

        // Fields point at a row that does not exist: without the cache this would throw.
        $instance = $this->makeConnexity(['documents_id' => 999999999]);

        $item = $document;
        $this->assertTrue(
            $instance->canConnexityItem(
                'canUpdateItem',
                'canUpdate',
                CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM,
                \Document::class,
                'documents_id',
                $item
            )
        );
        $this->assertSame($document->getID(), $item->getID());
    }

    private function createReminder(int $user_id): Reminder
    {
        $reminder = $this->createItem(
            Reminder::class,
            [
                'name'     => 'Foreign reminder',
                'text'     => 'Foreign reminder',
                'users_id' => $user_id,
            ]
        );
        $this->createItem(
            Entity_Reminder::class,
            [
                'reminders_id' => $reminder->getID(),
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]
        );

        return $reminder;
    }

    private function createReminderUser(int $reminder_id, int $user_id): Reminder_User
    {
        $link = $this->createItem(
            Reminder_User::class,
            [
                'reminders_id' => $reminder_id,
                'users_id'     => $user_id,
            ]
        );

        return $link;
    }

    private const ATTACHED_FIELDS = ['Reminder', 'reminders_id', 'User', 'users_id'];

    public function testCheckAttachedItemChangesAllowedWithoutAnyChange(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id = getItemByTypeName(\User::class, 'tech', true);

        $reminder = $this->createReminder(Session::getLoginUserID());
        $link = $this->createReminderUser($reminder->getID(), $tech_id);

        $this->assertTrue($link->checkAttachedItemChangesAllowed(
            ['reminders_id' => $reminder->getID(), 'users_id' => $tech_id],
            self::ATTACHED_FIELDS
        ));
    }

    /**
     * Only the attached fields are watched; an unrelated field changing is not a re-parenting.
     */
    public function testCheckAttachedItemChangesAllowedIgnoresUnwatchedFields(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id = getItemByTypeName(\User::class, 'tech', true);

        $reminder = $this->createReminder(Session::getLoginUserID());
        $link = $this->createReminderUser($reminder->getID(), $tech_id);

        $this->assertTrue($link->checkAttachedItemChangesAllowed(
            ['id' => $link->getID(), 'reminders_id' => $reminder->getID(), 'users_id' => $tech_id],
            self::ATTACHED_FIELDS
        ));
    }

    /**
     * Re-parenting onto a reminder the user can write is allowed.
     */
    public function testCheckAttachedItemChangesAllowedTowardsAWritableParent(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id   = getItemByTypeName(\User::class, 'tech', true);
        $normal_id = getItemByTypeName(\User::class, 'normal', true);

        $reminder = $this->createReminder(Session::getLoginUserID());
        $link = $this->createReminderUser($reminder->getID(), $tech_id);

        $this->assertTrue($link->checkAttachedItemChangesAllowed(
            ['reminders_id' => $reminder->getID(), 'users_id' => $normal_id],
            self::ATTACHED_FIELDS
        ));
    }

    /**
     * Re-parenting onto a reminder the user can only read is refused. The method reports through
     * an INFO message rather than ERROR, which is why this failure is easy to miss in the UI.
     */
    public function testCheckAttachedItemChangesAllowedTowardsAReadOnlyParent(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id   = getItemByTypeName(\User::class, 'tech', true);
        $normal_id = getItemByTypeName(\User::class, 'normal', true);

        $own_reminder = $this->createReminder(Session::getLoginUserID());
        $foreign_reminder = $this->createReminder($normal_id);
        $link = $this->createReminderUser($own_reminder->getID(), $tech_id);

        $this->assertFalse($link->checkAttachedItemChangesAllowed(
            ['reminders_id' => $foreign_reminder->getID(), 'users_id' => $tech_id],
            self::ATTACHED_FIELDS
        ));
    }

    /**
     * The change detection uses a loose `!=`, so a value that differs only by type is not
     * treated as a change and no rights re-check happens. That is benign here -- the id is the
     * same -- but it is worth pinning, because tightening the comparison would start routing
     * string-typed form input through the full create/delete/purge re-check.
     */
    public function testCheckAttachedItemChangesAllowedUsesLooseComparison(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id   = getItemByTypeName(\User::class, 'tech', true);

        $reminder = $this->createReminder(Session::getLoginUserID());
        $link = $this->createReminderUser($reminder->getID(), $tech_id);

        $this->assertTrue($link->checkAttachedItemChangesAllowed(
            ['reminders_id' => (string) $reminder->getID(), 'users_id' => (string) $tech_id],
            self::ATTACHED_FIELDS
        ));

    }

    /**
     * A watched field that is not a column of the connexity -- which is what a fixed itemtype
     * such as `Reminder` is, since CommonDBRelation passes class names and field names through
     * the same list -- is skipped by the `isset()` guard rather than compared.
     */
    public function testCheckAttachedItemChangesAllowedSkipsNonColumnFields(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | Reminder::PERSONAL;

        $tech_id   = getItemByTypeName(\User::class, 'tech', true);
        $normal_id = getItemByTypeName(\User::class, 'normal', true);

        $own_reminder = $this->createReminder(Session::getLoginUserID());
        $foreign_reminder = $this->createReminder($normal_id);
        $link = $this->createReminderUser($own_reminder->getID(), $tech_id);

        // 'Reminder' and 'User' are class names, not columns: they never trigger a re-check.
        $this->assertTrue($link->checkAttachedItemChangesAllowed(
            ['Reminder' => $foreign_reminder->getID(), 'User' => $tech_id],
            ['Reminder', 'User']
        ));
    }
}
