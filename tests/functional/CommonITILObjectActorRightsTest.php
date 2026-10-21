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

use Change;
use CommonITILActor;
use CommonITILObject;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\ITILActorAssertionsTrait;
use Glpi\Tests\RuleBuilder;
use Group;
use Group_User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Problem;
use Rule;
use RuleTicket;
use Session;
use Supplier;
use Ticket;
use User;

/**
 * Actors submitted without the matching right are ignored: the ITIL object is saved without them.
 *
 * The result of `update()` is not always checked: an input fully filtered by the closed/no-update
 * whitelist makes it fail. Only the actors stored in database matter.
 */
class CommonITILObjectActorRightsTest extends DbTestCase
{
    use ITILActorAssertionsTrait;

    #[TestWith(['legacy'], 'legacy')]
    #[TestWith(['additional_array'], 'additional_array')]
    public function testSelfServiceCreatesTicketForHimself(string $format): void
    {
        // --- arrange ---
        $this->loginAsSelfService();
        $self_id = Session::getLoginUserID();

        // --- act ---
        $ticket = $this->createItem(Ticket::class, $this->buildActorInput($format, 'requester', User::class, $self_id) + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::REQUESTER, 'Self-Service user should be requester of his ticket');
    }

    #[TestWith(['legacy'], 'legacy')]
    #[TestWith(['additional_array'], 'additional_array')]
    public function testSelfServiceCreatesTicketForDelegatingUser(string $format): void
    {
        // --- arrange post-only is delegatee of normal ---
        $this->login();
        $new_requester = getItemByTypeName(User::class, 'normal');
        $this->addDelegation('post-only', 'normal');
        $this->loginAsSelfService();
        assert(Ticket::canDelegateeCreateTicket($new_requester->getID()), 'Delegation is required: the Self-Service user may create tickets for this user');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, $this->buildActorInput($format, 'requester', User::class, $new_requester->getID()) + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $new_requester->getID(), CommonITILActor::REQUESTER, 'Delegating user should be requester');
    }

    /**
     * A Self-Service user may only set as requester himself or a user he is delegate of
     * (`Ticket::canDelegateeCreateTicket()`). Checked for every input format, including `_actors`
     * with a lowercase itemtype, which is converted to the historical keys before the check.
     */
    #[TestWith(['legacy'], 'legacy')]
    #[TestWith(['additional'], 'additional')]
    #[TestWith(['additional_array'], 'additional_array')]
    #[TestWith(['actors'], 'actors')]
    #[TestWith(['actors_lowercase'], 'actors_lowercase')]
    public function testSelfServiceCannotCreateTicketForUserWithoutDelegation(string $format): void
    {
        // --- arrange ---
        $this->loginAsSelfService();
        $new_requester = getItemByTypeName(User::class, 'normal');
        assert(!Ticket::canDelegateeCreateTicket($new_requester->getID()), 'Delegation must be missing: the Self-Service user may not create tickets for this user');

        // --- act ---
        $ticket = $this->createItem(
            Ticket::class,
            $this->buildActorInput($format, 'requester', User::class, $new_requester->getID()) + $this->getMinimalCreationInput(Ticket::class)
        );

        // --- assert ---
        $this->assertActorNotLinked($ticket, User::class, $new_requester->getID(), CommonITILActor::REQUESTER, 'Requester without delegation should be ignored');
    }

    /**
     * Partial removal in a requester list sent directly as an historical key.
     *
     * The Self-Service user sets himself and another user as requesters, without delegation on
     * the other one: only the other requester is removed, the Self-Service user stays requester.
     * Ensures that the actor by actor check removes the refused actor without dropping the whole key.
     *
     * Requester counterpart of testCreateTicketKeepsSelfAssignmentAndRemovesOtherAssigneeWithoutAssignRight().
     */
    public function testSelfServiceCreatesTicketForHimselfWithoutRequesterLackingDelegation(): void
    {
        // --- arrange ---
        $this->loginAsSelfService();
        $self_id = Session::getLoginUserID();
        $other_requester_id = getItemByTypeName(User::class, 'normal')->getID();
        assert(!Ticket::canDelegateeCreateTicket($other_requester_id), 'Delegation must be missing: only the other requester must be removed');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, ['_users_id_requester' => [$self_id, $other_requester_id]] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::REQUESTER, 'Self-Service user should be requester of his ticket');
        $this->assertActorNotLinked($ticket, User::class, $other_requester_id, CommonITILActor::REQUESTER, 'Requester without delegation should be removed');
    }

    #[DataProvider('itilClassProvider')]
    public function testCreateItilForAnotherRequesterFromStandardInterface(string $itil_class): void
    {
        // --- arrange ---
        $this->login('tech');
        $new_requester = getItemByTypeName(User::class, 'normal');
        assert(Session::getCurrentInterface() === 'central', 'Standard interface is required: any requester may be set');

        // --- act ---
        $itil = $this->createItil($itil_class, ['_users_id_requester' => $new_requester->getID()]);

        // --- assert ---
        $this->assertActorLinked($itil, User::class, $new_requester->getID(), CommonITILActor::REQUESTER, 'Requester should be set');
    }

    public function testSelfServiceCreatesTicketWithObservers(): void
    {
        // --- arrange ---
        $this->loginAsSelfService();
        $new_observer_user = getItemByTypeName(User::class, 'normal');
        $new_observer_group = getItemByTypeName(Group::class, '_test_group_1');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, [
            '_users_id_observer' => $new_observer_user->getID(),
            '_groups_id_observer' => $new_observer_group->getID(),
        ] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $new_observer_user->getID(), CommonITILActor::OBSERVER, 'Observer user should be set');
        $this->assertActorLinked($ticket, Group::class, $new_observer_group->getID(), CommonITILActor::OBSERVER, 'Observer group should be set');
    }

    #[DataProvider('itilClassProvider')]
    public function testCreateItilWithAssigneesWithAssignRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $new_assignees = [
            User::class => getItemByTypeName(User::class, 'tech')->getID(),
            Group::class => getItemByTypeName(Group::class, '_test_group_1')->getID(),
            Supplier::class => getItemByTypeName(Supplier::class, '_suplier01_name')->getID(),
        ];
        assert((new $itil_class())->canAssign(), 'Assign right is required: any assignee may be set');

        // --- act ---
        $itil = $this->createItil($itil_class, [
            '_users_id_assign' => $new_assignees[User::class],
            '_groups_id_assign' => $new_assignees[Group::class],
            '_suppliers_id_assign' => $new_assignees[Supplier::class],
        ]);

        // --- assert ---
        foreach ($new_assignees as $actor_itemtype => $actor_id) {
            $this->assertActorLinked($itil, $actor_itemtype, $actor_id, CommonITILActor::ASSIGN, "$actor_itemtype assignee should be set");
        }
    }

    /**
     * Without the assign right (Ticket: assign right; Change/Problem: update right), assignees are
     * removed on creation, whatever the input format and the actor type (user, group, supplier).
     */
    #[DataProvider('assigneeInputProvider')]
    public function testCannotCreateItilWithAssigneeWithoutAssignRight(string $itil_class, string $format, string $actor_itemtype, string $actor_name): void
    {
        // --- arrange ---
        $this->login();
        $this->removeAssignRight($itil_class);
        $new_assignee_id = getItemByTypeName($actor_itemtype, $actor_name)->getID();
        assert(!(new $itil_class())->canAssign(), 'Assign right must be missing: the assignee must be ignored');

        // --- act ---
        $itil = $this->createItil($itil_class, $this->buildActorInput($format, 'assign', $actor_itemtype, $new_assignee_id));

        // --- assert ---
        $this->assertActorNotLinked($itil, $actor_itemtype, $new_assignee_id, CommonITILActor::ASSIGN, 'Assignee without assign right should be ignored');
    }

    #[TestWith(['legacy'], 'legacy')]
    #[TestWith(['additional_array'], 'additional_array')]
    public function testCreateTicketAssignedToHimselfWithOwnRight(string $format): void
    {
        // --- arrange ---
        $this->login('tech');
        $self_id = Session::getLoginUserID();
        assert(!Session::haveRight(Ticket::$rightname, Ticket::ASSIGN), 'Assign right must be missing: only self-assignment is tested');
        assert(!Session::haveRight(Ticket::$rightname, Ticket::STEAL), 'Steal right must be missing: only the own right is tested');
        assert(Session::haveRight(Ticket::$rightname, Ticket::OWN), 'Own right is required: a new ticket has no assignee yet');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, $this->buildActorInput($format, 'assign', User::class, $self_id) + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Technician should be assigned');
    }

    /**
     * Without the assign right, a user may only assign himself, if he can take the ticket
     * (`Ticket::canAssignToMe()`: steal right, or own right on a ticket without assignee).
     * Here the technician has none of the assign, steal and own rights: his self-assignment is removed.
     * Rights of the current user only; whether the assignee may be assigned is not checked here.
     *
     * Counterpart of testCreateTicketAssignedToHimselfWithOwnRight().
     */
    public function testCannotCreateTicketAssignedToHimselfWithoutOwnRight(): void
    {
        // --- arrange ---
        $this->login('tech');
        $self_id = Session::getLoginUserID();
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] &= ~Ticket::OWN;
        assert(!Session::haveRightsOr(Ticket::$rightname, [Ticket::ASSIGN, Ticket::STEAL, Ticket::OWN]), 'Assign, steal and own rights must be missing: self-assignment must be ignored');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, ['_users_id_assign' => $self_id] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorNotLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Self-assignment without own right should be ignored');
    }

    /**
     * Partial removal in an assignee list sent directly as an historical key.
     *
     * The technician (own right, no assign right) assigns himself and another user: his
     * self-assignment is kept, the other assignee is removed, in the same key.
     */
    public function testCreateTicketKeepsSelfAssignmentAndRemovesOtherAssigneeWithoutAssignRight(): void
    {
        // --- arrange ---
        $this->login('tech');
        $self_id = Session::getLoginUserID();
        $other_assignee_id = getItemByTypeName(User::class, 'normal')->getID();
        assert(!Session::haveRight(Ticket::$rightname, Ticket::ASSIGN), 'Assign right must be missing: the other assignee must be removed');
        assert(!Session::haveRight(Ticket::$rightname, Ticket::STEAL), 'Steal right must be missing: only the own right is tested');
        assert(Session::haveRight(Ticket::$rightname, Ticket::OWN), 'Own right is required: self-assignment must be kept');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, ['_users_id_assign' => [$self_id, $other_assignee_id]] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Technician should be assigned');
        $this->assertActorNotLinked($ticket, User::class, $other_assignee_id, CommonITILActor::ASSIGN, 'Other assignee without assign right should be removed');
    }

    /**
     * The actor list must not be reindexed when an actor is removed.
     *
     * `CommonITILObject::updateActors()` reads `_<fk>_<type>_notif` with the key of each actor of
     * `_<fk>_<type>`. The removed assignee is the first one (key 0): if the list was reindexed, the
     * technician would move to key 0 and get the email address of the removed assignee.
     */
    public function testCreateTicketKeepsNotificationSettingsOfSelfAssignmentWhenOtherAssigneeIsRemoved(): void
    {
        // --- arrange ---
        $this->login('tech');
        $self_id = Session::getLoginUserID();
        $other_assignee_id = getItemByTypeName(User::class, 'normal')->getID();
        assert(!Session::haveRight(Ticket::$rightname, Ticket::ASSIGN), 'Assign right must be missing: the other assignee must be removed');
        assert(!Session::haveRight(Ticket::$rightname, Ticket::STEAL), 'Steal right must be missing: only the own right is tested');
        assert(Session::haveRight(Ticket::$rightname, Ticket::OWN), 'Own right is required: self-assignment must be kept');

        // --- act ---
        // removed assignee first: notification settings are matched by list key
        $ticket = $this->createItem(Ticket::class, [
            '_users_id_assign' => [$other_assignee_id, $self_id],
            '_users_id_assign_notif' => [
                'use_notification' => [1, 1],
                'alternative_email' => ['normal@example.com', 'tech@example.com'],
            ],
        ] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorNotLinked($ticket, User::class, $other_assignee_id, CommonITILActor::ASSIGN, 'Other assignee without assign right should be removed');
        $link = $ticket->getActorObjectForItem(User::class);
        $this->assertTrue($link->getFromDBByCrit([
            $link::getItilObjectForeignKey() => $ticket->getID(),
            $link->getActorForeignKey() => $self_id,
            'type' => CommonITILActor::ASSIGN,
        ]), 'Technician should be assigned');
        $this->assertSame('tech@example.com', $link->fields['alternative_email'], 'Technician should keep his own notification email');
    }

    #[DataProvider('itilClassProvider')]
    public function testAssignItilWithAssignRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil($itil_class);
        $new_assignee_id = getItemByTypeName(User::class, 'tech')->getID();
        assert($itil->canAssign(), 'Assign right is required: any assignee may be set');

        // --- act ---
        $this->updateItem($itil_class, $itil->getID(), ['_users_id_assign' => $new_assignee_id]);

        // --- assert ---
        $this->assertActorLinked($itil, User::class, $new_assignee_id, CommonITILActor::ASSIGN, 'Assignee should be set');
    }

    /**
     * Same as testCannotCreateItilWithAssigneeWithoutAssignRight(), on update.
     */
    #[DataProvider('assigneeInputProvider')]
    public function testCannotAssignItilWithoutAssignRight(string $itil_class, string $format, string $actor_itemtype, string $actor_name): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil($itil_class);
        $this->removeAssignRight($itil_class);
        $new_assignee_id = getItemByTypeName($actor_itemtype, $actor_name)->getID();
        assert(!$this->reloadItem($itil)->canAssign(), 'Assign right must be missing: the assignee must be ignored');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, 'assign', $actor_itemtype, $new_assignee_id));

        // --- assert ---
        $this->assertActorNotLinked($itil, $actor_itemtype, $new_assignee_id, CommonITILActor::ASSIGN, 'Assignee without assign right should be ignored');
    }

    /**
     * The steal right allows to take a ticket already assigned to someone else (`Ticket::canAssignToMe()`),
     * without the assign right.
     *
     * Counterpart of testCannotAssignAlreadyAssignedTicketToHimselfWithoutStealRight().
     */
    public function testAssignAlreadyAssignedTicketToHimselfWithStealRight(): void
    {
        // --- arrange ---
        $this->login();
        $initial_assignee_id = getItemByTypeName(User::class, 'glpi')->getID();
        $ticket = $this->createItil(Ticket::class, ['_users_id_assign' => $initial_assignee_id]);
        assert($this->isActorLinked($ticket, User::class, $initial_assignee_id, CommonITILActor::ASSIGN), 'Initial assignee must be set: the ticket must already be assigned');

        $this->login('tech');
        $self_id = Session::getLoginUserID();
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] |= Ticket::STEAL;
        assert(!Session::haveRight(Ticket::$rightname, Ticket::ASSIGN), 'Assign right must be missing: only self-assignment is tested');

        // --- act ---
        $this->updateItem(Ticket::class, $ticket->getID(), ['_users_id_assign' => $self_id]);

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Technician should be assigned');
    }

    /**
     * The own right allows self-assignment only on a ticket without assignee: on a ticket already
     * assigned, the steal right is required (`Ticket::canAssignToMe()`).
     */
    public function testCannotAssignAlreadyAssignedTicketToHimselfWithoutStealRight(): void
    {
        // --- arrange ---
        $this->login();
        $initial_assignee_id = getItemByTypeName(User::class, 'glpi')->getID();
        $ticket = $this->createItil(Ticket::class, ['_users_id_assign' => $initial_assignee_id]);
        assert($this->isActorLinked($ticket, User::class, $initial_assignee_id, CommonITILActor::ASSIGN), 'Initial assignee must be set: the ticket must already be assigned');

        $this->login('tech');
        $self_id = Session::getLoginUserID();
        assert(!Session::haveRightsOr(Ticket::$rightname, [Ticket::ASSIGN, Ticket::STEAL]), 'Assign and steal rights must be missing: own right alone does not allow to take an assigned ticket');
        assert(Session::haveRight(Ticket::$rightname, UPDATE), 'Update right is required: only the assignment must be blocked');

        // --- act ---
        $this->updateItem(Ticket::class, $ticket->getID(), ['_users_id_assign' => $self_id]);

        // --- assert ---
        $this->assertActorNotLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Self-assignment on an assigned ticket without steal right should be ignored');
    }

    /**
     * The "associate myself" button sends the single actor key `_itil_assign`, which is not handled by
     * the actor filter but by its own checks (`Ticket::prepareInputForUpdate()`). Ensures that
     * self-assignment with the own right still works through this path.
     */
    public function testAssignTicketToHimselfWithAssociateMeButton(): void
    {
        // --- arrange ---
        $this->login();
        $ticket = $this->createItil(Ticket::class);

        $this->login('tech');
        $self_id = Session::getLoginUserID();
        assert(!Session::haveRight(Ticket::$rightname, Ticket::ASSIGN), 'Assign right must be missing: only the "associate myself" button is tested');
        assert(Session::haveRight(Ticket::$rightname, Ticket::OWN), 'Own right is required: the ticket has no assignee');

        // --- act ---
        $this->updateItem(Ticket::class, $ticket->getID(), [
            '_itil_assign' => ['_type' => 'user', 'users_id' => $self_id, 'use_notification' => 1],
        ]);

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $self_id, CommonITILActor::ASSIGN, 'Technician should be assigned');
    }

    #[DataProvider('itilClassProvider')]
    public function testUnassignItilWithAssignRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $initial_assignee_id = getItemByTypeName(User::class, 'tech')->getID();
        $itil = $this->createItil($itil_class, ['_users_id_assign' => $initial_assignee_id]);
        $link_id = $this->getActorLinkId($itil, User::class, $initial_assignee_id, CommonITILActor::ASSIGN);
        assert($this->reloadItem($itil)->canAssign(), 'Assign right is required: the assignee may be removed');

        // --- act ---
        $this->updateItem($itil_class, $itil->getID(), [
            '_users_id_assign_deleted' => [['itemtype' => User::class, 'id' => $link_id]],
        ]);

        // --- assert ---
        $this->assertActorNotLinked($itil, User::class, $initial_assignee_id, CommonITILActor::ASSIGN, 'Assignee should be removed');
    }

    /**
     * Removal of an actor through `_<fk>_<type>_deleted` requires the same right as adding it:
     * without the assign right, the assignee is kept.
     */
    #[DataProvider('itilClassProvider')]
    public function testCannotUnassignItilWithoutAssignRight(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $initial_assignee_id = getItemByTypeName(User::class, 'tech')->getID();
        $itil = $this->createItil($itil_class, ['_users_id_assign' => $initial_assignee_id]);
        $link_id = $this->getActorLinkId($itil, User::class, $initial_assignee_id, CommonITILActor::ASSIGN);
        $this->removeAssignRight($itil_class);
        assert(!$this->reloadItem($itil)->canAssign(), 'Assign right must be missing: the assignee must be kept');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update([
            'id' => $itil->getID(),
            '_users_id_assign_deleted' => [['itemtype' => User::class, 'id' => $link_id]],
        ]);

        // --- assert ---
        $this->assertActorLinked($itil, User::class, $initial_assignee_id, CommonITILActor::ASSIGN, 'Assignee should not be removed without assign right');
    }

    #[DataProvider('itilClassProvider')]
    public function testReplaceRequesterOfItilFromStandardInterface(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $initial_requester_id = getItemByTypeName(User::class, 'post-only')->getID();
        $new_requester_id = getItemByTypeName(User::class, 'normal')->getID();
        $itil = $this->createItil($itil_class, ['_users_id_requester' => $initial_requester_id]);
        assert($this->isActorLinked($itil, User::class, $initial_requester_id, CommonITILActor::REQUESTER), 'Initial requester must be set: it is the one to be replaced');
        assert($itil->can($itil->getID(), UPDATE), 'Update right on the item is required: the requester may be replaced');

        // --- act ---
        $this->updateItem($itil_class, $itil->getID(), [
            '_actors' => ['requester' => [['itemtype' => User::class, 'items_id' => $new_requester_id]]],
        ]);

        // --- assert ---
        $this->assertActorLinked($itil, User::class, $new_requester_id, CommonITILActor::REQUESTER, 'New requester should be set');
        $this->assertActorNotLinked($itil, User::class, $initial_requester_id, CommonITILActor::REQUESTER, 'Initial requester should be removed');
    }

    /**
     * A Self-Service requester may update his recent ticket (`Ticket::canRequesterUpdateItem()`), but not
     * its actors: the simplified interface shows them read-only, the server must refuse them too.
     */
    public function testCannotReplaceRequesterOfTicketFromSimplifiedInterface(): void
    {
        // --- arrange ---
        $this->login();
        $initial_requester_id = getItemByTypeName(User::class, 'post-only')->getID();
        $new_requester_id = getItemByTypeName(User::class, 'normal')->getID();
        $ticket = $this->createItil(Ticket::class, ['_users_id_requester' => $initial_requester_id]);
        assert($this->isActorLinked($ticket, User::class, $initial_requester_id, CommonITILActor::REQUESTER), 'Initial requester must be set: it is the one to be kept');
        $this->loginAsSelfService();
        $ticket = $this->reloadItem($ticket);
        assert($ticket->canRequesterUpdateItem(), 'Requester update must be allowed: recent ticket without followup nor task, only the interface must block');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $ticket->update([
            'id' => $ticket->getID(),
            '_actors' => ['requester' => [['itemtype' => User::class, 'items_id' => $new_requester_id]]],
        ]);

        // --- assert ---
        $this->assertActorNotLinked($ticket, User::class, $new_requester_id, CommonITILActor::REQUESTER, 'New requester should be ignored');
        $this->assertActorLinked($ticket, User::class, $initial_requester_id, CommonITILActor::REQUESTER, 'Initial requester should be kept');
    }

    #[DataProvider('itilClassProvider')]
    public function testAddObserverToItilFromStandardInterface(string $itil_class): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil($itil_class);
        $new_observer_id = getItemByTypeName(User::class, 'normal')->getID();
        assert($itil->can($itil->getID(), UPDATE), 'Update right on the item is required: an observer may be added');

        // --- act ---
        $this->updateItem($itil_class, $itil->getID(), ['_users_id_observer' => $new_observer_id]);

        // --- assert ---
        $this->assertActorLinked($itil, User::class, $new_observer_id, CommonITILActor::OBSERVER, 'Observer should be set');
    }

    /**
     * Same as testCannotReplaceRequesterOfTicketFromSimplifiedInterface(), for observers.
     */
    #[TestWith(['legacy', User::class, 'normal'], 'legacy User')]
    #[TestWith(['legacy', Group::class, '_test_group_1'], 'legacy Group')]
    #[TestWith(['additional', User::class, 'normal'], 'additional User')]
    #[TestWith(['actors', User::class, 'normal'], 'actors User')]
    public function testCannotAddObserverToTicketFromSimplifiedInterface(string $format, string $actor_itemtype, string $actor_name): void
    {
        // --- arrange ---
        $this->login();
        $ticket = $this->createItil(Ticket::class, ['_users_id_requester' => getItemByTypeName(User::class, 'post-only')->getID()]);
        $new_observer_id = getItemByTypeName($actor_itemtype, $actor_name)->getID();
        $this->loginAsSelfService();
        $ticket = $this->reloadItem($ticket);
        assert($ticket->canRequesterUpdateItem(), 'Requester update must be allowed: recent ticket without followup nor task, only the interface must block');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $ticket->update(['id' => $ticket->getID()] + $this->buildActorInput($format, 'observer', $actor_itemtype, $new_observer_id));

        // --- assert ---
        $this->assertActorNotLinked($ticket, $actor_itemtype, $new_observer_id, CommonITILActor::OBSERVER, 'Observer added from the simplified interface should be ignored');
    }

    /**
     * Forms (`Glpi\Form`) create tickets through `Session::callAsSystem()`: the actor filter is skipped
     * when right checks are disabled, so actors chosen in a form are kept even for a Self-Service user.
     */
    public function testCreateTicketAsSystemKeepsActorsOfSelfService(): void
    {
        // --- arrange ---
        $this->loginAsSelfService();
        $new_requester_id = getItemByTypeName(User::class, 'normal')->getID();
        $new_assignee_id = getItemByTypeName(User::class, 'tech')->getID();

        // --- act ---
        // Glpi\Form path: right checks disabled
        $ticket = Session::callAsSystem(fn() => $this->createItem(Ticket::class, [
            '_users_id_requester' => $new_requester_id,
            '_users_id_assign' => $new_assignee_id,
        ] + $this->getMinimalCreationInput(Ticket::class)));

        // --- assert ---
        $this->assertActorLinked($ticket, User::class, $new_requester_id, CommonITILActor::REQUESTER, 'Requester should be kept');
        $this->assertActorLinked($ticket, User::class, $new_assignee_id, CommonITILActor::ASSIGN, 'Assignee should be kept');
    }

    /**
     * The actor filter runs before the business rules: actors added by a rule are not filtered.
     * Ensures that a rule can still assign a group to a ticket created by a user without the assign right.
     */
    public function testRuleAssignsGroupToTicketOfSelfService(): void
    {
        // --- arrange ---
        $this->login();
        $new_assignee_group_id = getItemByTypeName(Group::class, '_test_group_1')->getID();
        $rule_builder = new RuleBuilder(__FUNCTION__, RuleTicket::class);
        $rule_builder->setCondtion(RuleTicket::ONADD)
            ->addCriteria('name', Rule::PATTERN_IS, __FUNCTION__)
            ->addAction('assign', '_groups_id_assign', $new_assignee_group_id);
        $this->createRule($rule_builder);
        $this->loginAsSelfService();
        assert(!(new Ticket())->canAssign(), 'Assign right must be missing: only the rule may set the assignee');

        // --- act ---
        $ticket = $this->createItem(Ticket::class, ['name' => __FUNCTION__] + $this->getMinimalCreationInput(Ticket::class));

        // --- assert ---
        $this->assertActorLinked($ticket, Group::class, $new_assignee_group_id, CommonITILActor::ASSIGN, 'Group assigned by the rule should be kept');
    }

    public static function itilClassProvider(): iterable
    {
        yield Ticket::class => ['itil_class' => Ticket::class];
        yield Change::class => ['itil_class' => Change::class];
        yield Problem::class => ['itil_class' => Problem::class];
    }

    public static function assigneeInputProvider(): iterable
    {
        $actors = [
            User::class => 'tech',
            Group::class => '_test_group_1',
            Supplier::class => '_suplier01_name',
        ];
        // every input format on Ticket
        foreach (['legacy', 'additional'] as $format) {
            foreach ($actors as $actor_itemtype => $actor_name) {
                yield "Ticket $format $actor_itemtype" => [
                    'itil_class' => Ticket::class,
                    'format' => $format,
                    'actor_itemtype' => $actor_itemtype,
                    'actor_name' => $actor_name,
                ];
            }
        }
        yield 'Ticket actors User' => [
            'itil_class' => Ticket::class,
            'format' => 'actors',
            'actor_itemtype' => User::class,
            'actor_name' => 'tech',
        ];
        // array items, as produced by rules: users only
        yield 'Ticket additional_array User' => [
            'itil_class' => Ticket::class,
            'format' => 'additional_array',
            'actor_itemtype' => User::class,
            'actor_name' => 'tech',
        ];
        // Change/Problem: assign right is the update right
        foreach ([Change::class, Problem::class] as $itil_class) {
            yield "$itil_class legacy User" => [
                'itil_class' => $itil_class,
                'format' => 'legacy',
                'actor_itemtype' => User::class,
                'actor_name' => 'tech',
            ];
        }
    }

    /**
     * @param class-string<CommonITILObject> $itil_class
     */
    private function createItil(string $itil_class, array $input = []): CommonITILObject
    {
        assert(is_a($itil_class, CommonITILObject::class, true), 'Only CommonITILObject class is allowed in ' . __FUNCTION__);

        return $this->createItem($itil_class, $input + $this->getMinimalCreationInput($itil_class));
    }

    /**
     * Self-Service user (post-only), with `_test_root_entity` as active entity (needed by the delegation check).
     */
    private function loginAsSelfService(): void
    {
        $this->login('post-only');
        $this->setEntity('_test_root_entity', true);
        assert(Session::getCurrentInterface() === 'helpdesk', 'Self-Service user must use the simplified interface');
    }

    /**
     * `$delegate_name` becomes delegate of `_test_group_1`, which `$member_name` joins.
     */
    private function addDelegation(string $delegate_name, string $member_name): void
    {
        $groups_id = getItemByTypeName(Group::class, '_test_group_1')->getID();
        $this->createItems(Group_User::class, [
            ['groups_id' => $groups_id, 'users_id' => getItemByTypeName(User::class, $member_name)->getID()],
            ['groups_id' => $groups_id, 'users_id' => getItemByTypeName(User::class, $delegate_name)->getID(), 'is_userdelegate' => 1],
        ]);
    }

    /**
     * Ticket: dedicated assign right; Change/Problem: update right.
     *
     * @param class-string<CommonITILObject> $itil_class
     */
    private function removeAssignRight(string $itil_class): void
    {
        $right = $itil_class === Ticket::class ? Ticket::ASSIGN : UPDATE;
        $_SESSION['glpiactiveprofile'][$itil_class::$rightname] &= ~$right;
    }

    /**
     * @param string $format `legacy` (`_<fk>_<type>`), `additional` (`_additional_*`),
     *                       `additional_array` (`_additional_*` with array items, as produced by rules; users only),
     *                       `actors` (`_actors`), `actors_lowercase` (`_actors` with a lowercase itemtype)
     * @param string $actor_type `requester`, `observer` or `assign`
     */
    private function buildActorInput(string $format, string $actor_type, string $actor_itemtype, int $actor_id): array
    {
        assert($format !== 'additional_array' || $actor_itemtype === User::class, 'additional_array format is only for users');

        return match ($format) {
            'legacy' => [sprintf('_%s_%s', getForeignKeyFieldForItemType($actor_itemtype), $actor_type) => $actor_id],
            'additional' => [
                ($actor_itemtype === User::class
                    ? sprintf('_additional_%ss', $actor_type)
                    : sprintf('_additional_%ss_%ss', strtolower($actor_itemtype), $actor_type)) => [$actor_id],
            ],
            'additional_array' => [sprintf('_additional_%ss', $actor_type) => [['users_id' => $actor_id, 'use_notification' => 1]]],
            'actors' => ['_actors' => [$actor_type => [['itemtype' => $actor_itemtype, 'items_id' => $actor_id]]]],
            'actors_lowercase' => ['_actors' => [$actor_type => [['itemtype' => strtolower($actor_itemtype), 'items_id' => $actor_id]]]],
        };
    }


    private function getActorLinkId(CommonITILObject $itil, string $actor_itemtype, int $actor_id, int $actor_type): int
    {
        $link = $itil->getActorObjectForItem($actor_itemtype);
        $found = $link->getFromDBByCrit([
            $link::getItilObjectForeignKey() => $itil->getID(),
            $link->getActorForeignKey() => $actor_id,
            'type' => $actor_type,
        ]);
        assert($found, 'Actor link must exist');

        return $link->getID();
    }
}
