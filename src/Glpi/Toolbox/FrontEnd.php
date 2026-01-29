<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Toolbox;

use Glpi\Application\Environment;
use function Safe\fclose;
use function Safe\file_get_contents;
use function Safe\fsockopen;
use function Safe\gethostname;
use function Safe\json_decode;

class FrontEnd
{
    /**
     * Provide a cache key that can be use in URLs for given version, without actually exposing
     * the version to everyone.
     *
     * @param string $version
     *
     * @return string
     */
    public static function getVersionCacheKey(string $version): string
    {
        // using both gethostname() and GLPI_ROOT will provide a hardly predictable but stable token
        return sha1($version . gethostname() . GLPI_ROOT);
    }

    public static function isViteDevServerRunning(): bool
    {
        if (!Environment::get()->shouldEnableExtraDevAndDebugTools()) {
            return false;
        }
        static $is_vite_running;

        if (is_null($is_vite_running)) {
            try {
                $is_vite_running = @fsockopen(hostname: 'localhost', port: 5173, timeout: 0.1);
                if ($is_vite_running) {
                    fclose($is_vite_running);
                }
            } catch (\Exception) {
                $is_vite_running = false;
            }
        }
        return (bool) $is_vite_running;
    }

    public static function getViteDevServerClient(): string
    {
        return 'http://localhost:5173/@vite/client';
    }

    public static function getViteEntrypoint(): string
    {
        if (self::isViteDevServerRunning()) {
            return 'http://localhost:5173/js/src/vue/app.js';
        }
        $manifest = json_decode(
            file_get_contents(GLPI_ROOT . '/public/build/vue/.vite/manifest.json'),
            true
        );

        return 'build/vue/' . $manifest['js/src/vue/app.js']['file'];
    }
}
