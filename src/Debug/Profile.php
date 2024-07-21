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

namespace Glpi\Debug;

final class Profile
{
    private string $id;

    private ?string $parent_id;

    /**
     * @var bool If true, the profile is loaded from an old request so current debug info should not be accessible from this profile
     */
    private bool $is_readonly = false;

    /**
     * @var array|null Debug info for this profile. This is only set for loaded/readonly profiles.
     */
    private ?array $debug_info = null;

    private array $additional_info = [];

    private static ?self $current = null;

    public function __construct(string $id, ?string $parent_id)
    {
        $this->id = $id;
        $this->parent_id = $parent_id;
        // Register a shutdown function to save the profile
        register_shutdown_function(function () {
            // Stop all profiler timers (should just be the main php_request one unless something died)
            Profiler::getInstance()->stopAll();
            $this->save();
        });
    }

    public static function getCurrent(): self
    {
        if (self::$current === null) {
            $id = $_SERVER['HTTP_X_GLPI_AJAX_ID'] ?? bin2hex(random_bytes(8));
            $parent_id = $_SERVER['HTTP_X_GLPI_AJAX_PARENT_ID'] ?? null;
            self::$current = new self($id, $parent_id);
        }
        return self::$current;
    }

    public static function pull(string $id): ?self
    {
        if (!isset($_SESSION['debug_profiles'][$id])) {
            return null;
        }

        try {
            $profile_data = json_decode(gzdecode($_SESSION['debug_profiles'][$id]), true, 512, JSON_THROW_ON_ERROR);
            $profile = new self($profile_data['id'], $profile_data['parent_id']);
            $profile->is_readonly = true;
            $profile->debug_info = $profile_data;

            unset($_SESSION['debug_profiles'][$id]);

            return $profile;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getParentID(): ?string
    {
        return $this->parent_id;
    }

    public function setData(string $widget, $data)
    {
        if (!array_key_exists($widget, $this->additional_info)) {
            $this->additional_info[$widget] = [];
        }
        $this->additional_info[$widget][] = $data;
    }

    public function addSQLQueryData(string $query, int $time, int $rows = 0, string $errors = '', string $warnings = '')
    {
        if (!array_key_exists('sql', $this->additional_info)) {
            $this->additional_info['sql'] = [
                'queries' => [],
            ];
        }
        $next_num = count($this->additional_info['sql']['queries'] ?? []);
        $this->additional_info['sql']['queries'][] = [
            'num' => $next_num,
            'query' => $query,
            'time' => $time,
            'rows' => $rows,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function getDebugInfo(): array
    {
        if ($this->is_readonly) {
            return $this->debug_info;
        }

        $execution_time = -1;
        if (isset($this->additional_info['profiler'])) {
            $main_section = array_values(array_filter($this->additional_info['profiler'], static function (array $section) {
                return $section['category'] === Profiler::CATEGORY_CORE && $section['name'] === 'php_request';
            }));
            if (count($main_section)) {
                $execution_time = $main_section[0]['end'] - $main_section[0]['start'];
            }
        }

        /**
         * Each top-level key corresponds to a debug toolbar widget id
         */
        $debug_info = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'server_performance' => [
                'execution_time' => (float) $execution_time,
                'memory_usage' => memory_get_usage(),
                'memory_peak' => memory_get_peak_usage(),
                'memory_limit' => \Toolbox::getMemoryLimit(),
            ],
            'sql' => [
                'queries' => [],
            ],
            'globals' => []
        ];

        if ($this->parent_id === null) {
            // We only need these for top-level requests. For AJAX, this data is already known by the client.
            $debug_info['globals']['get'] = $_GET;
            $debug_info['globals']['post'] = $_POST;
        }
        $session = $_SESSION ?? [];
        unset($session['debug_profiles']);
        $debug_info['globals']['session'] = $session;
        $debug_info['globals']['server'] = $_SERVER;

        foreach ($this->additional_info as $widget => $data) {
            if (!array_key_exists($widget, $debug_info)) {
                $debug_info[$widget] = $data;
            } else {
                $debug_info[$widget] = array_merge($debug_info[$widget], $data);
            }
        }

        return $debug_info;
    }

    public function save(): void
    {
        if (isAPI() || isCommandLine()) {
            // No saving debug info for API or CLI requests
            return;
        }
        if ($this->is_readonly) {
            return;
        }
        if ($this->parent_id === null) {
            // Don't save top-level requests. The data is sent in the response in a script tag when the bar is initialized.
            return;
        }

        $info = $this->getDebugInfo();

        try {
            $json = json_encode($info, JSON_THROW_ON_ERROR);
            $gz = gzencode($json, 9);
            $_SESSION['debug_profiles'][$this->id] = $gz;
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
