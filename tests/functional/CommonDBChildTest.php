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

use CommonDBChild;
use Computer;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\Group;

class CommonDBChildTest extends DbTestCase
{
    public function tearDown(): void
    {
        unset($GLOBALS['GLPI_IS_COMMAND_LINE']);
        parent::tearDown();
    }

    /**
     * No core CommonDBChild flags itself as requiring re-authentication, so this
     * uses a stub to prove the new can(..., &$reauth_needed) signature is inherited
     * from CommonDBTM through the CommonDBChild/CommonDBConnexity hierarchy and that
     * the reauth flag is correctly produced. Exercises the new-item CREATE path
     * (no DB load needed).
     */
    #[Group('reauth')]
    public function testCanProducesReauthFlagThroughChildHierarchy(): void
    {
        // --- arrange ---
        $this->login();
        $this->fakeWebContext();
        $instance = $this->getFakeCommonDBChild(true);

        // --- act + assert : not re-authenticated → can() returns false and sets reauth_needed ---
        $this->setReauthenticated(false);
        $reauth_needed = null;
        $input = [];
        $this->assertFalse($instance->can(-1, CREATE, $input, $reauth_needed));
        $this->assertTrue($reauth_needed);

        // --- act + assert : re-authenticated → can() returns true and clears reauth_needed ---
        $this->setReauthenticated(true);
        $reauth_needed = null;
        $input = [];
        $this->assertTrue($instance->can(-1, CREATE, $input, $reauth_needed));
        $this->assertFalse($reauth_needed);
    }

    #[Group('reauth')]
    public function testCanWithoutCreateRightDoesNotRequireReauth(): void
    {
        // --- arrange ---
        $this->login();
        $this->fakeWebContext();
        $this->setReauthenticated(false);
        $instance = $this->getFakeCommonDBChild(false);

        // --- act + assert ---
        $reauth_needed = null;
        $input = [];
        $this->assertFalse($instance->can(-1, CREATE, $input, $reauth_needed));
        $this->assertFalse($reauth_needed);
    }

    /** Creates an anonymous CommonDBChild stub; $rights_granted controls whether static can*() methods return true. */
    private function getFakeCommonDBChild(bool $rights_granted): CommonDBChild
    {
        return new class ($rights_granted) extends CommonDBChild {
            public static string $itemtype = Computer::class;
            public static string $items_id = 'computers_id';
            public static bool $allowed = true;

            public function __construct(bool $allowed = true)
            {
                self::$allowed = $allowed;
            }

            public static function canCreate(): bool
            {
                return self::$allowed;
            }

            // No real parent item in this stub : keep the item-level check trivially true.
            public function canCreateItem(): bool
            {
                return true;
            }

            public static function itemTypeRequiresReauthentication(): bool
            {
                return true;
            }

            // Avoid any DB access when can() initializes a new item.
            public function getEmpty()
            {
                $this->fields = ['id' => 0];
                return true;
            }
        };
    }

    private function fakeWebContext(): void
    {
        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        $_SERVER['REQUEST_URI'] = '/front/computer.form.php';
    }

    private function setReauthenticated(bool $reauthenticated): void
    {
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        if ($reauthenticated) {
            (new ReAuthManager())->authenticate();
        } else {
            unset($_SESSION['glpi_reauth_until']);
        }
    }

    public function testPrepareInputForAddWithMandatoryFkeyRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static string $itemtype = Computer::class;
            public static string $items_id = 'computers_id';
            public static bool $mustBeAttached = true;

            public static bool $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertFalse($instance->prepareInputForAdd(['foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['computers_id' => 9999999999, 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #9999999999 is invalid.']);

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['computers_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => $valid_id, 'foo' => 'bar'])
        );
    }

    public function testPrepareInputForAddWithOptionalFkeyRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static string $itemtype = Computer::class;
            public static string $items_id = 'computers_id';
            public static bool $mustBeAttached = false;

            public static bool $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertEquals(
            ['foo' => 'bar'],
            $instance->prepareInputForAdd(['foo' => 'bar'])
        );

        $this->assertEquals(
            ['computers_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => 9999999999, 'foo' => 'bar'])
        );

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['computers_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => $valid_id, 'foo' => 'bar'])
        );
    }
    public function testPrepareInputForAddWithMandatoryPolymorphicRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static string $itemtype = 'itemtype';
            public static string $items_id = 'items_id';
            public static bool $mustBeAttached = true;

            public static bool $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertFalse($instance->prepareInputForAdd(['foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item null #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'Computer', 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'NotAClass', 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item NotAClass #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => 9999999999,  'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #9999999999 is invalid.']);

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'])
        );
    }

    public function testPrepareInputForAddWithOptionalPolymorphicRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static string $itemtype = 'itemtype';
            public static string $items_id = 'items_id';
            public static bool $mustBeAttached = false;

            public static bool $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertEquals(
            ['foo' => 'bar'],
            $instance->prepareInputForAdd(['foo' => 'bar'])
        );

        $this->assertEquals(
            ['items_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['items_id' => 9999999999, 'foo' => 'bar'])
        );

        $this->assertEquals(
            ['itemtype' => '', 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'NotAClass', 'foo' => 'bar'])
        );

        $this->assertEquals(
            ['itemtype' => '', 'items_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => 9999999999,  'foo' => 'bar'])
        );

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'])
        );
    }
}
