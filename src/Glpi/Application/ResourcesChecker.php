<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

final class ResourcesChecker
{
    public function __construct(private string $root_dir)
    {
    }

    /**
     * Check that all required resources are up-to-date.
     * If some dependencies are missing, exit with the corresponding message.
     */
    public function checkResources(): void
    {
        if (!$this->shouldCheckResources()) {
            return;
        }
        if (!$this->areDependenciesUpToDate()) {
            echo 'Application dependencies are not up to date.' . PHP_EOL;
            echo 'Run "php bin/console dependencies install" in the glpi tree to fix this.' . PHP_EOL;
            exit();
        }
        if (!$this->areLocalesUpToDate()) {
            echo 'Application locales have to be compiled.' . PHP_EOL;
            echo 'Run "php bin/console locales:compile" in the glpi tree to fix this.' . PHP_EOL;
            exit();
        }
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
        } else if (file_exists($this->root_dir . '/composer.lock')) {
            if (!file_exists($this->root_dir . '/.composer.hash')) {
                return false;
            } else if (sha1_file($this->root_dir . '/composer.lock') != file_get_contents($this->root_dir . '/.composer.hash')) {
                return false;
            }
        }

        // Check node dependencies
        if (!file_exists($this->root_dir . '/public/lib')) {
            return false;
        } else if (file_exists($this->root_dir . '/package-lock.json')) {
            if (!file_exists($this->root_dir . '/.package.hash')) {
                return false;
            } else if (sha1_file($this->root_dir . '/package-lock.json') != file_get_contents($this->root_dir . '/.package.hash')) {
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
        $locales_files = scandir($this->root_dir . '/locales');
        $po_files = preg_grep('/\.po$/', $locales_files);
        $mo_files = preg_grep('/\.mo$/', $locales_files);
        if (count($mo_files) < count($po_files)) {
            return false;
        } else if (file_exists($this->root_dir . '/locales/glpi.pot')) {
            // Assume that `locales/glpi.pot` file only exists when installation mode is GIT
            foreach ($po_files as $po_file) {
                $po_file = $this->root_dir . '/locales/' . $po_file;
                $mo_file = preg_replace('/\.po$/', '.mo', $po_file);
                if (!file_exists($mo_file) || filemtime($mo_file) < filemtime($po_file)) {
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
        // Only production/staging environment are considered as environments where resources are not supposed to change.
        $env = $_ENV['GLPI_ENVIRONMENT_TYPE'] ?? $_SERVER['GLPI_ENVIRONMENT_TYPE'] ?? 'production';
        if (!in_array($env, ['staging', 'production'])) {
            return true;
        }

        // If GLPI is install direcly by cloning the git repository, then it is preferable to check
        // resources state.
        if (is_dir($this->root_dir . '/.git')) {
            return true;
        }

        return false;
    }
}
