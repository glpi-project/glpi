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

use ChangeValidation;
use Glpi\Tests\DbTestCase;
use ObjectLock;
use Ticket;
use TicketValidation;

class CommonITILValidationReadRightTest extends DbTestCase
{
    public function testTicketValidationCanViewWithReadRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = READ;

        $this->assertTrue(TicketValidation::canView());
    }

    public function testTicketValidationCanViewWithNoRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;

        $this->assertFalse(TicketValidation::canView());
    }

    public function testChangeValidationCanViewWithReadRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = READ;

        $this->assertTrue(ChangeValidation::canView());
    }

    public function testChangeValidationCanViewWithNoRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = 0;

        $this->assertFalse(ChangeValidation::canView());
    }

    public function testTicketValidationGetRightsIncludesRead(): void
    {
        $this->login();

        $validation = new TicketValidation();
        $rights = $validation->getRights();

        $this->assertArrayHasKey(READ, $rights);
    }

    public function testCommonITILValidationGetRightsIncludesRead(): void
    {
        $this->login();

        $validation = new ChangeValidation();
        $rights = $validation->getRights();

        $this->assertArrayHasKey(READ, $rights);
    }

    public function testGetTimelineItemsIncludesValidationWithReadRight(): void
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'Timeline validation test',
            'content'     => 'Test content',
            'entities_id' => 0,
        ]);

        $validation = $this->createItem(TicketValidation::class, [
            'tickets_id'   => $ticket->getID(),
            'entities_id'  => 0,
            'itemtype_target' => 'User',
            'items_id_target' => 2,
        ]);

        $this->assertGreaterThan(0, $validation->getID());

        $ticket->getFromDB($ticket->getID());

        // Simulate read-only access: only READ on ticketvalidation, READALL on ticket
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READALL;

        $timeline = $ticket->getTimelineItems();

        $validation_items = array_filter(
            $timeline,
            fn($item) => ($item['type'] ?? '') === TicketValidation::class
        );

        $this->assertNotEmpty($validation_items);
    }

    public function testGetTimelineItemsExcludesValidationWithNoRight(): void
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'Timeline validation no-right test',
            'content'     => 'Test content',
            'entities_id' => 0,
        ]);

        $this->createItem(TicketValidation::class, [
            'tickets_id'   => $ticket->getID(),
            'entities_id'  => 0,
            'itemtype_target' => 'User',
            'items_id_target' => 2,
        ]);

        $ticket->getFromDB($ticket->getID());

        // Same ticket right as the "includes" test, but no validation right
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READALL;

        $timeline = $ticket->getTimelineItems();

        $validation_items = array_filter(
            $timeline,
            fn($item) => ($item['type'] ?? '') === TicketValidation::class
        );

        $this->assertEmpty($validation_items);
    }

    public function testCanViewValidationAfterObjectLockSetReadOnlyProfile(): void
    {
        global $CFG_GLPI;

        $this->login();

        // Save original values
        $original_lock_use = $CFG_GLPI['lock_use_lock_item'] ?? 0;
        $original_lock_profile_id = $CFG_GLPI['lock_lockprofile_id'] ?? 0;
        $original_lock_profile = $CFG_GLPI['lock_lockprofile'] ?? null;

        // Ensure the user's active profile has validation rights (CREATE + READ)
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname]
            = READ | TicketValidation::CREATEREQUEST;
        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname]
            = READ | CREATE;

        // Simulate a lock profile that only allows READ
        $CFG_GLPI['lock_use_lock_item'] = 1;
        $CFG_GLPI['lock_lockprofile_id'] = 999;
        $CFG_GLPI['lock_lockprofile'] = [
            TicketValidation::$rightname  => READ,
            ChangeValidation::$rightname  => READ,
        ];

        try {
            ObjectLock::setReadOnlyProfile();

            // After applying the lock profile, the resulting rights should be:
            // user's rights & lock profile rights = (READ | CREATEREQUEST) & READ = READ
            $this->assertTrue(
                TicketValidation::canView(),
                'TicketValidation should be viewable in Object Lock read-only mode'
            );
            $this->assertTrue(
                ChangeValidation::canView(),
                'ChangeValidation should be viewable in Object Lock read-only mode'
            );

            ObjectLock::revertProfile();
        } finally {
            // Ensure profile is always reverted even if assertions fail
            if (isset($_SESSION['glpilocksavedprofile'])) {
                ObjectLock::revertProfile();
            }

            // Restore original config
            $CFG_GLPI['lock_use_lock_item'] = $original_lock_use;
            $CFG_GLPI['lock_lockprofile_id'] = $original_lock_profile_id;
            $CFG_GLPI['lock_lockprofile'] = $original_lock_profile;
        }
    }

    public function testCannotViewValidationAfterObjectLockWithNoReadInLockProfile(): void
    {
        global $CFG_GLPI;

        $this->login();

        // Save original values
        $original_lock_use = $CFG_GLPI['lock_use_lock_item'] ?? 0;
        $original_lock_profile_id = $CFG_GLPI['lock_lockprofile_id'] ?? 0;
        $original_lock_profile = $CFG_GLPI['lock_lockprofile'] ?? null;

        // User has full validation rights
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname]
            = READ | TicketValidation::CREATEREQUEST | TicketValidation::VALIDATEREQUEST;

        // Lock profile without READ on validation — simulates the pre-fix state
        $CFG_GLPI['lock_use_lock_item'] = 1;
        $CFG_GLPI['lock_lockprofile_id'] = 999;
        $CFG_GLPI['lock_lockprofile'] = [
            TicketValidation::$rightname => 0,
        ];

        try {
            ObjectLock::setReadOnlyProfile();

            // user rights & lock profile = (READ | CREATEREQUEST | VALIDATEREQUEST) & 0 = 0
            $this->assertFalse(
                TicketValidation::canView(),
                'TicketValidation should NOT be viewable when lock profile has no READ right'
            );

            ObjectLock::revertProfile();
        } finally {
            if (isset($_SESSION['glpilocksavedprofile'])) {
                ObjectLock::revertProfile();
            }

            $CFG_GLPI['lock_use_lock_item'] = $original_lock_use;
            $CFG_GLPI['lock_lockprofile_id'] = $original_lock_profile_id;
            $CFG_GLPI['lock_lockprofile'] = $original_lock_profile;
        }
    }
}
