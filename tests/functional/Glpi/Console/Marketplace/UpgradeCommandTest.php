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

namespace tests\units\Glpi\Console\Marketplace;

use Glpi\Console\Marketplace\UpgradeCommand;
use Glpi\Tests\GLPITestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class UpgradeCommandTest extends GLPITestCase
{
    public function testUpgradeActivePluginsSkipsPluginsThatWereNotUpdated(): void
    {
        $command = new class extends UpgradeCommand {
            public array $processed = [];

            protected function upgradePlugin(string $plugin_key, int $plugin_id, OutputInterface $output): bool
            {
                $this->processed[] = $plugin_key;
                return true;
            }

            protected function hasConflictingAutoloader(string $plugin_key): bool
            {
                return false;
            }
        };

        // `asort()`-ed by plugin id, so processing order is `aaa`, `mmm`, `zzz`.
        $active_plugins = [
            'zzz' => 3,
            'aaa' => 1,
            'mmm' => 2,
        ];
        $updated_plugins = ['zzz', 'aaa']; // `mmm` has not been updated and must be skipped.

        $has_errors = $this->callPrivateMethod(
            $command,
            'upgradeActivePlugins',
            $active_plugins,
            $updated_plugins,
            new NullOutput()
        );

        $this->assertFalse($has_errors);
        $this->assertEquals(['aaa', 'zzz'], $command->processed);
    }

    public function testUpgradeActivePluginsReportsFailureWithoutInterruptingProcessing(): void
    {
        $command = new class extends UpgradeCommand {
            public array $processed = [];

            protected function upgradePlugin(string $plugin_key, int $plugin_id, OutputInterface $output): bool
            {
                $this->processed[] = $plugin_key;
                return $plugin_key !== 'faulty';
            }

            protected function getPluginAutoloaderClass(string $plugin_key): ?string
            {
                return null;
            }
        };

        $active_plugins = [
            'first'  => 1,
            'faulty' => 2,
            'last'   => 3,
        ];
        $updated_plugins = ['first', 'faulty', 'last'];

        $has_errors = $this->callPrivateMethod(
            $command,
            'upgradeActivePlugins',
            $active_plugins,
            $updated_plugins,
            new NullOutput()
        );

        $this->assertTrue($has_errors);
        // All plugins must have been processed, including the ones after the failing one.
        $this->assertEquals(['first', 'faulty', 'last'], $command->processed);
    }

    public function testUpgradeActivePluginsWithNoActivePlugins(): void
    {
        $command = new UpgradeCommand();

        $has_errors = $this->callPrivateMethod($command, 'upgradeActivePlugins', [], [], new NullOutput());

        $this->assertFalse($has_errors);
    }

    public function testUpgradeActivePluginsSkipsPluginWithCollidingAutoloader(): void
    {
        $command = new class extends UpgradeCommand {
            public array $processed = [];

            protected function upgradePlugin(string $plugin_key, int $plugin_id, OutputInterface $output): bool
            {
                $this->processed[] = $plugin_key;
                return true;
            }

            protected function hasConflictingAutoloader(string $plugin_key): bool
            {
                // Simulate `conflicting` sharing the same already-loaded autoloader class as a
                // plugin processed earlier in the batch (or in a previous request).
                return $plugin_key === 'conflicting';
            }
        };

        $active_plugins = [
            'safe'        => 1,
            'conflicting' => 2,
        ];
        $updated_plugins = ['safe', 'conflicting'];

        $has_errors = $this->callPrivateMethod(
            $command,
            'upgradeActivePlugins',
            $active_plugins,
            $updated_plugins,
            new NullOutput()
        );

        $this->assertTrue($has_errors);
        // The conflicting plugin must have been skipped, not passed to `upgradePlugin()`.
        $this->assertEquals(['safe'], $command->processed);
    }

    public function testHasConflictingAutoloaderReturnsFalseWhenNoVendorDir(): void
    {
        $command = new UpgradeCommand();

        $conflict = $this->callPrivateMethod($command, 'hasConflictingAutoloader', 'a-plugin-key-that-does-not-exist');

        $this->assertFalse($conflict);
    }

    public function testHasConflictingAutoloaderReturnsFalseWhenClassNotYetLoaded(): void
    {
        $plugin_root = $this->createPluginAutoloaderFixture('test_upgrade_autoloader_fixture_unloaded', 'ComposerAutoloaderInitUnloaded');

        try {
            $command = new UpgradeCommand();
            $conflict = $this->callPrivateMethod($command, 'hasConflictingAutoloader', 'test_upgrade_autoloader_fixture_unloaded');
        } finally {
            (new Filesystem())->remove($plugin_root);
        }

        // The class is declared on disk but was never `require`d, so it is not a real conflict.
        $this->assertFalse($conflict);
    }

    public function testHasConflictingAutoloaderReturnsFalseForItsOwnAlreadyLoadedAutoloader(): void
    {
        $plugin_root = $this->createPluginAutoloaderFixture('test_upgrade_autoloader_fixture_own', 'ComposerAutoloaderInitOwn');

        try {
            require $plugin_root . '/vendor/composer/autoload_real.php';

            $command = new UpgradeCommand();
            $conflict = $this->callPrivateMethod($command, 'hasConflictingAutoloader', 'test_upgrade_autoloader_fixture_own');
        } finally {
            (new Filesystem())->remove($plugin_root);
        }

        $this->assertFalse($conflict);
    }

    public function testHasConflictingAutoloaderReturnsTrueWhenClassDeclaredByAnotherPlugin(): void
    {
        $shared_class = 'ComposerAutoloaderInitShared' . \bin2hex(\random_bytes(4));
        $plugin_root_a = $this->createPluginAutoloaderFixture('test_upgrade_autoloader_fixture_a', $shared_class);
        $plugin_root_b = $this->createPluginAutoloaderFixture('test_upgrade_autoloader_fixture_b', $shared_class);

        try {
            // Only plugin `a`'s file is actually loaded, simulating it being processed first in the batch.
            require $plugin_root_a . '/vendor/composer/autoload_real.php';

            $command = new UpgradeCommand();
            $conflict = $this->callPrivateMethod($command, 'hasConflictingAutoloader', 'test_upgrade_autoloader_fixture_b');
        } finally {
            $filesystem = new Filesystem();
            $filesystem->remove($plugin_root_a);
            $filesystem->remove($plugin_root_b);
        }

        $this->assertTrue($conflict);
    }

    private function createPluginAutoloaderFixture(string $plugin_key, string $class_name): string
    {
        $plugin_root = GLPI_ROOT . '/plugins/' . $plugin_key;
        \mkdir($plugin_root . '/vendor/composer', 0o777, true);
        \file_put_contents(
            $plugin_root . '/vendor/composer/autoload_real.php',
            "<?php\n\nclass {$class_name}\n{\n}\n"
        );

        return $plugin_root;
    }
}
