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

use CommonITILActor;
use CommonITILObject;
use Entity;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use Session;
use Supplier;
use Ticket;
use User;

/**
 * Actors not eligible for their role, or not visible in the entity of the ITIL object, are ignored:
 * the ITIL object is saved without them, as the actor dropdown (`Dropdown::getDropdownActors()`) offers.
 *
 * - a supplier may only be assigned;
 * - a group needs the flag of the role: `is_requester`, `is_watcher` (observer), `is_assign`;
 * - a group or a supplier must belong to the entity of the ITIL object, or to a parent entity with `is_recursive`.
 *
 * Not checked in cron nor with right checks disabled (`Session::callAsSystem()`).
 * Super-admin session: eligibility is tested, not rights.
 *
 * The result of `update()` is not always checked: an input whose actors are all ignored may make it fail.
 * Only the actors stored in database matter.
 *
 * Declined per ITIL object by the subclasses, see getITILClass().
 */
abstract class AbstractCommonITILObjectActorEligibilityTest extends DbTestCase
{
    private const array FORMATS = ['legacy', 'additional', 'additional_array'];

    private const array ROLE_FLAGS = [
        'requester' => 'is_requester',
        'observer'  => 'is_watcher',
        'assign'    => 'is_assign',
    ];

    abstract public function getITILClass(): CommonITILObject;

    #[DataProvider('formatProvider')]
    public function testCreateWithSupplierAssigned(string $format): void
    {
        // --- arrange ---
        $this->login();
        $supplier_id = getItemByTypeName(Supplier::class, '_suplier01_name', true);

        // --- act ---
        $itil = $this->createItil($this->buildActorInput($format, 'assign', Supplier::class, [$supplier_id]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Supplier::class, $supplier_id, CommonITILActor::ASSIGN), 'Supplier should be assigned');
    }

    /**
     * A supplier may only be assigned.
     */
    #[DataProvider('supplierForbiddenRoleFormatProvider')]
    public function testCannotCreateWithSupplierAsRequesterOrObserver(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $supplier_id = getItemByTypeName(Supplier::class, '_suplier01_name', true);

        // --- act ---
        $itil = $this->createItil($this->buildActorInput($format, $role, Supplier::class, [$supplier_id]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $supplier_id, $this->getActorType($role)), "Supplier as $role should be ignored");
    }

    /**
     * The flag of the role is enough: the other flags are unset.
     */
    #[DataProvider('groupRoleFormatProvider')]
    public function testCreateWithGroupHavingRoleFlag(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $group = $this->createGroup('_test_root_entity', 0, [$role]);

        // --- act ---
        $itil = $this->createItil($this->buildActorInput($format, $role, Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $group->getID(), $this->getActorType($role)), "Group with the flag of the $role role should be set");
    }

    /**
     * The other flags are set: only the flag of the role is missing.
     */
    #[DataProvider('groupRoleFormatProvider')]
    public function testCannotCreateWithGroupWithoutRoleFlag(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $all_roles_except_tested_one = array_diff(array_keys(self::ROLE_FLAGS), [$role]);
        $group = $this->createGroup('_test_root_entity', 0, $all_roles_except_tested_one);

        // --- act ---
        $itil = $this->createItil($this->buildActorInput($format, $role, Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $group->getID(), $this->getActorType($role)), "Group without the flag of the $role role should be ignored");
    }

    /**
     * Entities: ITIL object and group in _test_child_1 (same entity), group not recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithGroupOfSameEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $group = $this->createGroup('_test_child_1', 0, array_keys(self::ROLE_FLAGS));

        // --- act ---
        $itil = $this->createItil(['entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)] + $this->buildActorInput($format, 'requester', Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $group->getID(), CommonITILActor::REQUESTER), 'Group of the same entity should be set');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its parent _test_root_entity, recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithGroupOfRecursiveParentEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $group = $this->createGroup('_test_root_entity', 1, array_keys(self::ROLE_FLAGS));
        assert(in_array($group->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Group entity must be a parent of the ITIL object entity');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildActorInput($format, 'requester', Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $group->getID(), CommonITILActor::REQUESTER), 'Recursive group of a parent entity should be set');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its sibling _test_child_2, recursive.
     *
     * The group is recursive: recursion does not make it visible from a sibling entity.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithGroupOfSiblingEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity = getItemByTypeName(Entity::class, '_test_child_1');
        $group_entity = getItemByTypeName(Entity::class, '_test_child_2');
        assert($itil_entity->fields['entities_id'] === $group_entity->fields['entities_id'], 'Both entities must have the same parent entity');
        assert(!in_array($group_entity->getID(), getAncestorsOf(Entity::getTable(), $itil_entity->getID())), 'Group entity must not be a parent of the ITIL object entity');
        $group = $this->createGroup('_test_child_2', 1, array_keys(self::ROLE_FLAGS));

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity->getID()] + $this->buildActorInput($format, 'requester', Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $group->getID(), CommonITILActor::REQUESTER), 'Group of a sibling entity should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its parent _test_root_entity, not recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithGroupOfParentEntityWithoutRecursion(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $group = $this->createGroup('_test_root_entity', 0, array_keys(self::ROLE_FLAGS));
        assert(in_array($group->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Group entity must be a parent of the ITIL object entity: only the missing recursion hides the group');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildActorInput($format, 'requester', Group::class, [$group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $group->getID(), CommonITILActor::REQUESTER), 'Non recursive group of a parent entity should be ignored');
    }

    /**
     * Entities: ITIL object and supplier in _test_child_1 (same entity), supplier not recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithSupplierOfSameEntityAssigned(string $format): void
    {
        // --- arrange ---
        $this->login();
        $supplier = $this->createSupplier('_test_child_1', 0);

        // --- act ---
        $itil = $this->createItil(['entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)] + $this->buildActorInput($format, 'assign', Supplier::class, [$supplier->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Supplier::class, $supplier->getID(), CommonITILActor::ASSIGN), 'Supplier of the same entity should be assigned');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its parent _test_root_entity, recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithSupplierOfRecursiveParentEntityAssigned(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $supplier = $this->createSupplier('_test_root_entity', 1);
        assert(in_array($supplier->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Supplier entity must be a parent of the ITIL object entity: only recursion makes the supplier visible');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildActorInput($format, 'assign', Supplier::class, [$supplier->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Supplier::class, $supplier->getID(), CommonITILActor::ASSIGN), 'Recursive supplier of a parent entity should be assigned');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its sibling _test_child_2, recursive.
     *
     * The supplier is recursive: recursion does not make it visible from a sibling entity.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithSupplierOfSiblingEntityAssigned(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity = getItemByTypeName(Entity::class, '_test_child_1');
        $supplier_entity = getItemByTypeName(Entity::class, '_test_child_2');
        assert($itil_entity->fields['entities_id'] === $supplier_entity->fields['entities_id'], 'Both entities must have the same parent entity');
        assert(!in_array($supplier_entity->getID(), getAncestorsOf(Entity::getTable(), $itil_entity->getID())), 'Supplier entity must not be a parent of the ITIL object entity');
        $supplier = $this->createSupplier('_test_child_2', 1);

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity->getID()] + $this->buildActorInput($format, 'assign', Supplier::class, [$supplier->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $supplier->getID(), CommonITILActor::ASSIGN), 'Supplier of a sibling entity should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its parent _test_root_entity, not recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithSupplierOfParentEntityWithoutRecursionAssigned(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $supplier = $this->createSupplier('_test_root_entity', 0);
        assert(in_array($supplier->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Supplier entity must be a parent of the ITIL object entity: only the missing recursion hides the supplier');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildActorInput($format, 'assign', Supplier::class, [$supplier->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $supplier->getID(), CommonITILActor::ASSIGN), 'Non recursive supplier of a parent entity should be ignored');
    }

    /**
     * Partial removal in a list: only the group without the flag is removed, the eligible group of the
     * same key is kept and the ITIL object is created with its other fields (checked by createItem()).
     */
    #[DataProvider('formatProvider')]
    public function testCreateKeepsGroupHavingRequesterFlagAndIgnoresGroupWithout(string $format): void
    {
        // --- arrange ---
        $this->login();
        $eligible_group = $this->createGroup('_test_root_entity', 0, ['requester']);
        $non_eligible_group = $this->createGroup('_test_root_entity', 0, ['observer', 'assign']);

        // --- act ---
        // non eligible group first: a reindexed list would shift the eligible one
        $itil = $this->createItil($this->buildActorInput($format, 'requester', Group::class, [$non_eligible_group->getID(), $eligible_group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $eligible_group->getID(), CommonITILActor::REQUESTER), 'Group with the requester flag should be kept');
        $this->assertFalse($this->isActorLinked($itil, Group::class, $non_eligible_group->getID(), CommonITILActor::REQUESTER), 'Group without the requester flag should be ignored');
    }

    /**
     * An assigned user needs Ticket OWN in the entity of the ITIL object, as in the actor dropdown of the form.
     * Change and Problem have no OWN right of their own: the form, shared by the three ITIL objects, uses the Ticket one.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithUserAssignedHavingOwnRight(string $format): void
    {
        // --- arrange ---
        $this->login();
        $tech_id = getItemByTypeName(User::class, 'tech', true);
        assert(Profile::haveUserRight($tech_id, 'ticket', Ticket::OWN, $this->getTestRootEntity(true)), 'tech must have Ticket OWN in the entity of the ITIL object');

        // --- act ---
        $itil = $this->createItil($this->buildUserAssignInput($format, $tech_id));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, User::class, $tech_id, CommonITILActor::ASSIGN), 'User with Ticket OWN should be assigned');
    }

    /**
     * Change and Problem have no OWN right of their own: the form, shared by the three ITIL objects, uses the Ticket one.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithUserAssignedWithoutOwnRight(string $format): void
    {
        // --- arrange ---
        $this->login();
        $post_only_id = getItemByTypeName(User::class, 'post-only', true);
        assert(!Profile::haveUserRight($post_only_id, 'ticket', Ticket::OWN, $this->getTestRootEntity(true)), 'post-only must not have Ticket OWN in the entity of the ITIL object');

        // --- act ---
        $itil = $this->createItil($this->buildUserAssignInput($format, $post_only_id));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, User::class, $post_only_id, CommonITILActor::ASSIGN), 'User without Ticket OWN should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, user with Ticket OWN in its sibling _test_child_2 only, recursive.
     *
     * The link is recursive: recursion does not give the right in a sibling entity.
     */
    #[DataProvider('formatProvider')]
    public function testCannotCreateWithUserAssignedHavingOwnRightInSiblingEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $user = $this->createUserWithProfile(getItemByTypeName(Profile::class, 'Technician', true), '_test_child_2', 1);
        assert(Profile::haveUserRight($user->getID(), 'ticket', Ticket::OWN, getItemByTypeName(Entity::class, '_test_child_2', true)), 'User must have Ticket OWN in the sibling entity');
        assert(!Profile::haveUserRight($user->getID(), 'ticket', Ticket::OWN, $itil_entity_id), 'User must not have Ticket OWN in the entity of the ITIL object: only the entity hides the user');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildUserAssignInput($format, $user->getID()));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, User::class, $user->getID(), CommonITILActor::ASSIGN), 'User with Ticket OWN in a sibling entity only should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, user with Ticket OWN in its parent _test_root_entity, recursive.
     */
    #[DataProvider('formatProvider')]
    public function testCreateWithUserAssignedHavingOwnRightInRecursiveParentEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $user = $this->createUserWithProfile(getItemByTypeName(Profile::class, 'Technician', true), '_test_root_entity', 1);
        assert(Profile::haveUserRight($user->getID(), 'ticket', Ticket::OWN, $itil_entity_id), 'User must have Ticket OWN in the entity of the ITIL object, through the recursive link to its parent entity');

        // --- act ---
        $itil = $this->createItil(['entities_id' => $itil_entity_id] + $this->buildUserAssignInput($format, $user->getID()));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, User::class, $user->getID(), CommonITILActor::ASSIGN), 'User with Ticket OWN in a recursive parent entity should be assigned');
    }

    /**
     * No exception for self-assignment: the user may assign himself (canAssignToMe()), but the form does not offer him without Ticket OWN.
     *
     * Ticket: canAssignToMe() through STEAL; Change, Problem: canAssignToMe() through UPDATE on the object. No Ticket OWN.
     */
    public function testCannotAssignHimselfWithStealRightWithoutOwnRight(): void
    {
        // --- arrange ---
        $this->login();
        $profile = $this->createItem(Profile::class, [
            'interface' => 'central',
            'ticket'    => CREATE | Ticket::STEAL,
            'change'    => CREATE | UPDATE,
            'problem'   => CREATE | UPDATE,
        ] + $this->getMinimalCreationInput(Profile::class));
        $user = $this->createUserWithProfile($profile->getID(), '_test_root_entity', 1);
        assert(!Profile::haveUserRight($user->getID(), 'ticket', Ticket::OWN, $this->getTestRootEntity(true)), 'User must not have Ticket OWN in the entity of the ITIL object');
        $this->login($user->fields['name']);
        assert($this->getITILClass()->canAssignToMe(), 'User must be allowed to assign himself: only the missing Ticket OWN removes the assignment');

        // --- act ---
        $itil = $this->createItil($this->buildUserAssignInput('legacy', $user->getID()));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, User::class, $user->getID(), CommonITILActor::ASSIGN), 'User assigning himself without Ticket OWN should be ignored');
    }

    #[DataProvider('formatProvider')]
    public function testAssignSupplier(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $new_supplier_id = getItemByTypeName(Supplier::class, '_suplier01_name', true);

        // --- act ---
        $this->updateItem($itil::class, $itil->getID(), $this->buildActorInput($format, 'assign', Supplier::class, [$new_supplier_id]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Supplier::class, $new_supplier_id, CommonITILActor::ASSIGN), 'Supplier should be assigned');
    }

    /**
     * Same as testCannotCreateWithSupplierAsRequesterOrObserver(), on update.
     */
    #[DataProvider('supplierForbiddenRoleFormatProvider')]
    public function testCannotAddSupplierAsRequesterOrObserver(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $new_supplier_id = getItemByTypeName(Supplier::class, '_suplier01_name', true);

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, $role, Supplier::class, [$new_supplier_id]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $new_supplier_id, $this->getActorType($role)), "Supplier as $role should be ignored");
    }

    #[DataProvider('groupRoleFormatProvider')]
    public function testAddGroupHavingRoleFlag(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $new_group = $this->createGroup('_test_root_entity', 0, [$role]);

        // --- act ---
        $this->updateItem($itil::class, $itil->getID(), $this->buildActorInput($format, $role, Group::class, [$new_group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $new_group->getID(), $this->getActorType($role)), "Group with the flag of the $role role should be set");
    }

    /**
     * Same as testCannotCreateWithGroupWithoutRoleFlag(), on update.
     */
    #[DataProvider('groupRoleFormatProvider')]
    public function testCannotAddGroupWithoutRoleFlag(string $format, string $role): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $all_roles_except_tested_one = array_diff(array_keys(self::ROLE_FLAGS), [$role]);
        $new_group = $this->createGroup('_test_root_entity', 0, $all_roles_except_tested_one);

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, $role, Group::class, [$new_group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $new_group->getID(), $this->getActorType($role)), "Group without the flag of the $role role should be ignored");
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its parent _test_root_entity, recursive.
     */
    #[DataProvider('formatProvider')]
    public function testAddGroupOfRecursiveParentEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil(['entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)]);
        $new_group = $this->createGroup('_test_root_entity', 1, array_keys(self::ROLE_FLAGS));

        // --- act ---
        $this->updateItem($itil::class, $itil->getID(), $this->buildActorInput($format, 'requester', Group::class, [$new_group->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Group::class, $new_group->getID(), CommonITILActor::REQUESTER), 'Recursive group of a parent entity should be set');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its sibling _test_child_2, recursive.
     *
     * Same as testCannotCreateWithGroupOfSiblingEntity(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testCannotAddGroupOfSiblingEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity = getItemByTypeName(Entity::class, '_test_child_1');
        $group_entity = getItemByTypeName(Entity::class, '_test_child_2');
        assert($itil_entity->fields['entities_id'] === $group_entity->fields['entities_id'], 'Both entities must have the same parent entity');
        assert(!in_array($group_entity->getID(), getAncestorsOf(Entity::getTable(), $itil_entity->getID())), 'Group entity must not be a parent of the ITIL object entity');
        $itil = $this->createItil(['entities_id' => $itil_entity->getID()]);
        $new_group = $this->createGroup('_test_child_2', 1, array_keys(self::ROLE_FLAGS));

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, 'requester', Group::class, [$new_group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $new_group->getID(), CommonITILActor::REQUESTER), 'Group of a sibling entity should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, group in its parent _test_root_entity, not recursive.
     *
     * Same as testCannotCreateWithGroupOfParentEntityWithoutRecursion(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testCannotAddGroupOfParentEntityWithoutRecursion(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $itil = $this->createItil(['entities_id' => $itil_entity_id]);
        $new_group = $this->createGroup('_test_root_entity', 0, array_keys(self::ROLE_FLAGS));
        assert(in_array($new_group->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Group entity must be a parent of the ITIL object entity: only the missing recursion hides the group');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, 'requester', Group::class, [$new_group->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Group::class, $new_group->getID(), CommonITILActor::REQUESTER), 'Non recursive group of a parent entity should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its parent _test_root_entity, recursive.
     *
     * Same as testCreateWithSupplierOfRecursiveParentEntityAssigned(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testAssignSupplierOfRecursiveParentEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil(['entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)]);
        $new_supplier = $this->createSupplier('_test_root_entity', 1);

        // --- act ---
        $this->updateItem($itil::class, $itil->getID(), $this->buildActorInput($format, 'assign', Supplier::class, [$new_supplier->getID()]));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, Supplier::class, $new_supplier->getID(), CommonITILActor::ASSIGN), 'Recursive supplier of a parent entity should be assigned');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its sibling _test_child_2, recursive.
     *
     * Same as testCannotCreateWithSupplierOfSiblingEntityAssigned(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testCannotAssignSupplierOfSiblingEntity(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity = getItemByTypeName(Entity::class, '_test_child_1');
        $supplier_entity = getItemByTypeName(Entity::class, '_test_child_2');
        assert($itil_entity->fields['entities_id'] === $supplier_entity->fields['entities_id'], 'Both entities must have the same parent entity');
        assert(!in_array($supplier_entity->getID(), getAncestorsOf(Entity::getTable(), $itil_entity->getID())), 'Supplier entity must not be a parent of the ITIL object entity');
        $itil = $this->createItil(['entities_id' => $itil_entity->getID()]);
        $new_supplier = $this->createSupplier('_test_child_2', 1);

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, 'assign', Supplier::class, [$new_supplier->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $new_supplier->getID(), CommonITILActor::ASSIGN), 'Supplier of a sibling entity should be ignored');
    }

    /**
     * Entities (_test_root_entity > _test_child_1, _test_child_2): ITIL object in _test_child_1, supplier in its parent _test_root_entity, not recursive.
     *
     * Same as testCannotCreateWithSupplierOfParentEntityWithoutRecursionAssigned(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testCannotAssignSupplierOfParentEntityWithoutRecursion(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil_entity_id = getItemByTypeName(Entity::class, '_test_child_1', true);
        $itil = $this->createItil(['entities_id' => $itil_entity_id]);
        $new_supplier = $this->createSupplier('_test_root_entity', 0);
        assert(in_array($new_supplier->fields['entities_id'], getAncestorsOf(Entity::getTable(), $itil_entity_id)), 'Supplier entity must be a parent of the ITIL object entity: only the missing recursion hides the supplier');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildActorInput($format, 'assign', Supplier::class, [$new_supplier->getID()]));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, Supplier::class, $new_supplier->getID(), CommonITILActor::ASSIGN), 'Non recursive supplier of a parent entity should be ignored');
    }

    /**
     * Same as testCreateWithUserAssignedHavingOwnRight(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testAssignUserHavingOwnRight(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $new_assignee_id = getItemByTypeName(User::class, 'tech', true);
        assert(Profile::haveUserRight($new_assignee_id, 'ticket', Ticket::OWN, $itil->fields['entities_id']), 'tech must have Ticket OWN in the entity of the ITIL object');

        // --- act ---
        $this->updateItem($itil::class, $itil->getID(), $this->buildUserAssignInput($format, $new_assignee_id));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, User::class, $new_assignee_id, CommonITILActor::ASSIGN), 'User with Ticket OWN should be assigned');
    }

    /**
     * Same as testCannotCreateWithUserAssignedWithoutOwnRight(), on update.
     */
    #[DataProvider('formatProvider')]
    public function testCannotAssignUserWithoutOwnRight(string $format): void
    {
        // --- arrange ---
        $this->login();
        $itil = $this->createItil();
        $new_assignee_id = getItemByTypeName(User::class, 'post-only', true);
        assert(!Profile::haveUserRight($new_assignee_id, 'ticket', Ticket::OWN, $itil->fields['entities_id']), 'post-only must not have Ticket OWN in the entity of the ITIL object');

        // --- act ---
        // update() not checked: may fail when the whole input is filtered
        $itil->update(['id' => $itil->getID()] + $this->buildUserAssignInput($format, $new_assignee_id));

        // --- assert ---
        $this->assertFalse($this->isActorLinked($itil, User::class, $new_assignee_id, CommonITILActor::ASSIGN), 'User without Ticket OWN should be ignored');
    }

    /**
     * Recurrent ITIL objects are created by the cron: their actors are kept even when not eligible.
     */
    public function testCreateInCronKeepsNonEligibleActors(): void
    {
        // --- arrange ---
        $this->login();
        $non_eligible_requesters = $this->createNonEligibleRequesters();
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
        assert(Session::isCron(), 'Cron mode is required: no eligibility check');

        // --- act ---
        $itil = $this->createItil($this->buildNonEligibleRequestersInput($non_eligible_requesters));

        // --- assert ---
        foreach ($non_eligible_requesters as $label => [$actor_itemtype, $actor_id]) {
            $this->assertTrue($this->isActorLinked($itil, $actor_itemtype, $actor_id, CommonITILActor::REQUESTER), "$label should be kept in cron");
        }
    }

    /**
     * Forms (`Glpi\Form`) create ITIL objects through `Session::callAsSystem()`: their actors are kept even
     * when not eligible. Only the actors are checked, `callAsSystem()` changes other behaviors.
     */
    public function testCreateAsSystemKeepsNonEligibleActors(): void
    {
        // --- arrange ---
        $this->login();
        $non_eligible_requesters = $this->createNonEligibleRequesters();

        // --- act ---
        $itil = Session::callAsSystem(fn() => $this->createItil($this->buildNonEligibleRequestersInput($non_eligible_requesters)));

        // --- assert ---
        foreach ($non_eligible_requesters as $label => [$actor_itemtype, $actor_id]) {
            $this->assertTrue($this->isActorLinked($itil, $actor_itemtype, $actor_id, CommonITILActor::REQUESTER), "$label should be kept as system");
        }
    }

    /**
     * Same as testCreateInCronKeepsNonEligibleActors(), for an assigned user without Ticket OWN.
     */
    public function testCreateInCronKeepsUserAssignedWithoutOwnRight(): void
    {
        // --- arrange ---
        $this->login();
        $post_only_id = getItemByTypeName(User::class, 'post-only', true);
        assert(!Profile::haveUserRight($post_only_id, 'ticket', Ticket::OWN, $this->getTestRootEntity(true)), 'post-only must not have Ticket OWN in the entity of the ITIL object');
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
        assert(Session::isCron(), 'Cron mode is required: no eligibility check');

        // --- act ---
        $itil = $this->createItil($this->buildUserAssignInput('legacy', $post_only_id));

        // --- assert ---
        $this->assertTrue($this->isActorLinked($itil, User::class, $post_only_id, CommonITILActor::ASSIGN), 'User without Ticket OWN should be kept in cron');
    }

    /**
     * Input formats of the actors, see buildActorInput().
     */
    public static function formatProvider(): iterable
    {
        foreach (self::FORMATS as $format) {
            yield $format => ['format' => $format];
        }
    }

    public static function groupRoleFormatProvider(): iterable
    {
        return self::buildRoleFormatCases(array_keys(self::ROLE_FLAGS));
    }

    public static function supplierForbiddenRoleFormatProvider(): iterable
    {
        return self::buildRoleFormatCases(['requester', 'observer']);
    }

    /**
     * formatProvider() cases, for each role.
     *
     * @param string[] $roles `requester`, `observer` or `assign`
     */
    private static function buildRoleFormatCases(array $roles): iterable
    {
        foreach (self::formatProvider() as $label => $case) {
            foreach ($roles as $role) {
                yield "$label $role" => $case + ['role' => $role];
            }
        }
    }

    private function createItil(array $input = []): CommonITILObject
    {
        $itil_class = $this->getITILClass()::class;

        return $this->createItem($itil_class, $input + $this->getMinimalCreationInput($itil_class));
    }

    /**
     * @param string[] $enabled_roles roles whose flag is set, the flags of the other roles are unset
     */
    private function createGroup(string $entity_name, int $is_recursive, array $enabled_roles): Group
    {
        $role_flags = [];
        foreach (self::ROLE_FLAGS as $role => $flag) {
            $role_flags[$flag] = (int) in_array($role, $enabled_roles, true);
        }

        return $this->createItem(Group::class, [
            'entities_id'  => getItemByTypeName(Entity::class, $entity_name, true),
            'is_recursive' => $is_recursive,
        ] + $role_flags + $this->getMinimalCreationInput(Group::class));
    }

    private function createSupplier(string $entity_name, int $is_recursive): Supplier
    {
        return $this->createItem(Supplier::class, [
            'entities_id'  => getItemByTypeName(Entity::class, $entity_name, true),
            'is_recursive' => $is_recursive,
        ] + $this->getMinimalCreationInput(Supplier::class));
    }

    /**
     * User created for the test: its single profile link (entity, recursion) is what is tested.
     * `_profiles_id` replaces the default profile link User::post_addItem() would add otherwise.
     */
    private function createUserWithProfile(int $profiles_id, string $entity_name, int $is_recursive): User
    {
        return $this->createItem(User::class, [
            '_profiles_id'  => $profiles_id,
            '_entities_id'  => getItemByTypeName(Entity::class, $entity_name, true),
            '_is_recursive' => $is_recursive,
        ] + $this->getMinimalCreationInput(User::class));
    }

    /**
     * One non eligible requester per rule, for an ITIL object in `_test_child_1`.
     *
     * @return array<string, array{0: class-string, 1: int}> [actor itemtype, actor id], keyed by reason
     */
    private function createNonEligibleRequesters(): array
    {
        return [
            'Supplier as requester'        => [Supplier::class, $this->createSupplier('_test_child_1', 0)->getID()],
            'Group without requester flag' => [Group::class, $this->createGroup('_test_child_1', 0, ['observer', 'assign'])->getID()],
            'Group of a sibling entity'    => [Group::class, $this->createGroup('_test_child_2', 1, array_keys(self::ROLE_FLAGS))->getID()],
        ];
    }

    /**
     * @param array<string, array{0: class-string, 1: int}> $non_eligible_requesters see createNonEligibleRequesters()
     */
    private function buildNonEligibleRequestersInput(array $non_eligible_requesters): array
    {
        $input = ['entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)];
        foreach ($non_eligible_requesters as [$actor_itemtype, $actor_id]) {
            $input[sprintf('_%s_requester', getForeignKeyFieldForItemType($actor_itemtype))][] = $actor_id;
        }

        return $input;
    }

    /**
     * @param string $format `legacy` (`_<fk>_<type>`: a single actor as a value, several as a list),
     *                       `additional` (`_additional_*` with ids),
     *                       `additional_array` (`_additional_*` with arrays containing the foreign key)
     * @param string $role `requester`, `observer` or `assign`
     * @param class-string<Group|Supplier> $actor_itemtype
     * @param int[] $actor_ids
     */
    private function buildActorInput(string $format, string $role, string $actor_itemtype, array $actor_ids): array
    {
        assert(in_array($actor_itemtype, [Group::class, Supplier::class], true), 'Only groups and suppliers are handled in ' . __FUNCTION__);

        $actor_fkey = getForeignKeyFieldForItemType($actor_itemtype);
        $additional_key = sprintf('_additional_%ss_%ss', strtolower($actor_itemtype), $role);

        return match ($format) {
            'legacy' => [sprintf('_%s_%s', $actor_fkey, $role) => count($actor_ids) === 1 ? reset($actor_ids) : $actor_ids],
            'additional' => [$additional_key => $actor_ids],
            'additional_array' => [$additional_key => array_map(fn(int $actor_id) => [$actor_fkey => $actor_id], $actor_ids)],
        };
    }

    /**
     * Formats of buildActorInput() (groups and suppliers only), for a single assigned user.
     */
    private function buildUserAssignInput(string $format, int $user_id): array
    {
        return match ($format) {
            'legacy' => ['_users_id_assign' => $user_id],
            'additional' => ['_additional_assigns' => [$user_id]],
            'additional_array' => ['_additional_assigns' => [['users_id' => $user_id]]],
        };
    }

    /**
     * @param string $role `requester`, `observer` or `assign`
     */
    private function getActorType(string $role): int
    {
        return constant(CommonITILActor::class . '::' . strtoupper($role));
    }

    private function isActorLinked(CommonITILObject $itil, string $actor_itemtype, int $actor_id, int $actor_type): bool
    {
        $link = $itil->getActorObjectForItem($actor_itemtype);

        return countElementsInTable($link::getTable(), [
            $link::getItilObjectForeignKey() => $itil->getID(),
            $link->getActorForeignKey() => $actor_id,
            'type' => $actor_type,
        ]) > 0;
    }
}
