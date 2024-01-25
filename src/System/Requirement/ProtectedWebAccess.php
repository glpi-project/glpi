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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 *
 * @TODO Remove it in GLPI 10.1.
 */
class ProtectedWebAccess extends AbstractRequirement
{
    /**
     * Paths of directories to check.
     *
     * @var string[]
     */
    private $directories;

    /**
     * @param array $directories  Paths of directories to check.
     */
    public function __construct(array $directories)
    {
        parent::__construct(
            __('Protected access to files directory'),
            __('Web access to GLPI var directories should be disabled to prevent unauthorized access to them.'),
            true,
            false,
            null // $out_of_context will be computed on check
        );

        $this->directories = $directories;
    }

    protected function check()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isCommandLine()) {
            $this->out_of_context = true;
            $this->validated = false;
            $this->validation_messages[] = __('Checking that web access to files directory is protected cannot be done on CLI context.');
            return;
        }

        $check_access = false;
        foreach ($this->directories as $dir) {
            if (str_starts_with($dir, GLPI_ROOT)) {
               // Only check access if one of the data directories is under GLPI document root.
                $check_access = true;
                break;
            }
        }

        if (isset($_REQUEST['skipCheckWriteAccessToDirs']) || !$check_access) {
            $this->out_of_context = true;
            return;
        }

        $oldhand = set_error_handler(function ($errno, $errmsg, $filename, $linenum) {
            return true;
        });
        $oldlevel = error_reporting(0);

       //create a context to set timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 2.0
            ]
        ]);

        $protocol = 'http';
        if (isset($_SERVER['HTTPS'])) {
            $protocol = 'https';
        }
        $uri = $protocol . '://' . $_SERVER['SERVER_NAME'] . $CFG_GLPI['root_doc'];

        if ($fic = fopen($uri . '/index.php?skipCheckWriteAccessToDirs=1', 'r', false, $context)) {
            fclose($fic);
            if ($fic = fopen($uri . '/files/_log/php-errors.log', 'r', false, $context)) {
                fclose($fic);

                $this->validated = false;
                $this->validation_messages[] = __('Web access to the files directory should not be allowed');
                $this->validation_messages[] = __('Check the .htaccess file and the web server configuration.');
            } else {
                $this->validated = true;
                $this->validation_messages[] = __('Web access to files directory is protected');
            }
        } else {
            $this->validated = false;
            $this->validation_messages[] = __('Web access to the files directory should not be allowed but this cannot be checked automatically on this instance.');
            $this->validation_messages[] = sprintf(
                __('Make sure access to %s (%s) is forbidden; otherwise review .htaccess file and web server configuration.'),
                __('error log file'),
                $CFG_GLPI['root_doc'] . '/files/_log/php-errors.log'
            );
        }

        error_reporting($oldlevel);
        set_error_handler($oldhand);
    }
}
