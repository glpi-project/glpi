<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Debug;

/**
 * Class that handles profiling sections of code.
 * The data is viewable in the debug bar only. If the current user is not in debug mode, the profiler is disabled.
 */
final class Profiler
{
    /** @var ProfilerSection[] */
    private $current_sections = [];

    private $disabled = false;

    public const CATEGORY_CORE = 'core';
    public const CATEGORY_PLUGINS = 'plugins';
    public const CATEGORY_DB = 'db';
    public const CATEGORY_TWIG = 'twig';

    private static $instance;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function disable(): void
    {
        $this->disabled = true;
    }

    /**
     * Starts a new section in the profiler. This section will be stopped when Profiler::stop() is called with the same name.
     * @param string $name The name of the section. This name will be used to stop the section later.
     * @param string $category The category of the section. See Profiler::CATEGORY_* for some predefined categories.
     * @return void
     */
    public function start(string $name, string $category = self::CATEGORY_CORE): void
    {
        $debug_mode_or_pre_session = !isset($_SESSION['glpi_use_mode']) || $_SESSION['glpi_use_mode'] === \Session::DEBUG_MODE;
        if ($this->disabled || !$debug_mode_or_pre_session) {
            return;
        }

        // If any other section is running, this new one will be a child section of the last one
        $parent_id = null;
        if (count($this->current_sections)) {
            $parent_id = array_key_last($this->current_sections);
            $parent_id = $this->current_sections[$parent_id]->getId();
        }
        $this->current_sections[] = new ProfilerSection($category, $name, microtime(true) * 1000, $parent_id);
    }

    /**
     * Stops a section started with Profiler::start()
     * @param string $name The name of the section to stop. This name must be the same as the one used in Profiler::start()
     * @return void
     */
    public function stop(string $name): void
    {
        // get the last section with the given name and stop it
        $section = array_filter($this->current_sections, static function (ProfilerSection $section) use ($name) {
            return $section->getName() === $name;
        });
        if (count($section)) {
            $k = array_key_last($section);
            $section = array_pop($section);
            $section->end(microtime(true) * 1000);
            unset($this->current_sections[$k]);
            Profile::getCurrent()->setData('profiler', $section->toArray());
        }
    }

    /**
     * Stops all running sections
     * @return void
     */
    public function stopAll(): void
    {
        foreach ($this->current_sections as $section) {
            $this->stop($section->getName());
        }
    }

    /**
     * Checks if a section is running
     * @param string $name The name of the section to check
     * @return bool
     */
    public function isRunning(string $name): bool
    {
        $section = array_filter($this->current_sections, static function (ProfilerSection $section) use ($name) {
            return $section->getName() === $name;
        });
        return count($section) > 0;
    }
}
