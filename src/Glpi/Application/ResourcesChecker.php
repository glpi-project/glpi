<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Application;

use FilesystemIterator;

final class ResourcesChecker
{
    public function __construct(private string $root_dir) {}

    /**
     * Check that all required resources are up-to-date.
     * If some dependencies are missing, exit with the corresponding message.
     */
    public function checkResources(): void
    {
        if ($this->isSourceCodeMixedOfMultipleVersions()) {
            echo 'Source code files of a previous GLPI version were detected.' . PHP_EOL;
            echo 'Please update GLPI by following the procedure described in the installation documentation.' . PHP_EOL;
            exit(1); // @phpstan-ignore glpi.forbidExit (Script execution should be stopped to prevent further errors)
        }

        if (!$this->shouldCheckResources()) {
            return;
        }
        if (!$this->areDependenciesUpToDate()) {
            echo 'Application dependencies are not up to date.' . PHP_EOL;
            echo 'Run "php bin/console dependencies install" in the glpi tree to fix this.' . PHP_EOL;
            exit(1); // @phpstan-ignore glpi.forbidExit (Script execution should be stopped to prevent further errors)
        }
        if (!$this->areLocalesUpToDate()) {
            echo 'Application locales have to be compiled.' . PHP_EOL;
            echo 'Run "php bin/console locales:compile" in the glpi tree to fix this.' . PHP_EOL;
            exit(1); // @phpstan-ignore glpi.forbidExit (Script execution should be stopped to prevent further errors)
        }
    }

    /**
     * Check if the GLPI source code files seems to contain a mix of multiple GLPI versions.
     */
    private function isSourceCodeMixedOfMultipleVersions(): bool
    {
        $version_dir = $this->root_dir . '/version';

        if (!\file_exists($version_dir)) {
            // Cannot check
            return false;
        }

        $file_iterator = new FilesystemIterator($version_dir);
        $version_files_count = iterator_count($file_iterator);

        return $version_files_count > 1;
    }

    /**
     * Check if installed dependencies are up-to-date.
     */
    private function areDependenciesUpToDate(): bool
    {
        // Check composer dependencies
        $autoload = $this->root_dir . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return false;
        } elseif (file_exists($this->root_dir . '/composer.lock')) {
            if (!file_exists($this->root_dir . '/.composer.hash')) {
                return false;
            } elseif (sha1_file($this->root_dir . '/composer.lock') != file_get_contents($this->root_dir . '/.composer.hash')) { // @phpstan-ignore theCodingMachineSafe.function, theCodingMachineSafe.function (Safe not installed at this point)
                return false;
            }
        }

        // Check node dependencies
        if (!file_exists($this->root_dir . '/public/lib')) {
            return false;
        } elseif (file_exists($this->root_dir . '/package-lock.json')) {
            if (!file_exists($this->root_dir . '/.package.hash')) {
                return false;
            } elseif (sha1_file($this->root_dir . '/package-lock.json') != file_get_contents($this->root_dir . '/.package.hash')) { // @phpstan-ignore theCodingMachineSafe.function, theCodingMachineSafe.function (Safe not installed at this point)
                return false;
            }
        }

        return true;
    }

    /**
     * Check if complied locale files are up-to-date.
     */
    private function areLocalesUpToDate(): bool
    {
        $locales_files = scandir($this->root_dir . '/locales'); // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
        $po_files = preg_grep('/\.po$/', $locales_files); // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
        $mo_files = preg_grep('/\.mo$/', $locales_files); // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
        if (count($mo_files) < count($po_files)) {
            return false;
        } elseif (file_exists($this->root_dir . '/locales/glpi.pot')) {
            // Assume that `locales/glpi.pot` file only exists when installation mode is GIT
            foreach ($po_files as $po_file) {
                $po_file = $this->root_dir . '/locales/' . $po_file;
                $mo_file = preg_replace('/\.po$/', '.mo', $po_file); // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
                if (!file_exists($mo_file) || filemtime($mo_file) < filemtime($po_file)) { // @phpstan-ignore theCodingMachineSafe.function, theCodingMachineSafe.function (Safe not loaded at this point)
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if the resources should be checked.
     */
    private function shouldCheckResources(): bool
    {
        // The file is special and will be executed before the autoload script
        // is loaded, thus we must require the needed file manually.
        require_once($this->root_dir . '/src/Glpi/Application/Environment.php');
        return Environment::get()->shouldExpectResourcesToChange($this->root_dir);
    }
}
