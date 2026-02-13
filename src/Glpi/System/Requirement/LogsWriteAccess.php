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

namespace Glpi\System\Requirement;

use Safe\Exceptions\FilesystemException;

use function Safe\touch;

/**
 * @since 9.5.0
 */
class LogsWriteAccess extends AbstractRequirement
{
    private string $log_dir;

    public function __construct(string $log_dir)
    {
        parent::__construct(
            __('Permissions for log files')
        );

        $this->log_dir = $log_dir;
    }

    protected function check()
    {
        if (!is_dir($this->log_dir) || !is_writable($this->log_dir)) {
            $this->validated = false;
            $this->validation_messages[] = sprintf(__('The log directory %s is not writable.'), $this->log_dir);
            return;
        }

        $file_path = $this->log_dir . '/php-errors.log';

        if (file_exists($file_path)) {
            if (!is_writable($file_path)) {
                $this->validated = false;
                $this->validation_messages[] = sprintf(__('The log file %s is not writable.'), $file_path);
                return;
            }
        } else {
            // Do not remove the file after touch(), as SELinux may prevent re-creation.
            try {
                touch($file_path);
            } catch (FilesystemException) {
                $this->validated = false;
                $this->validation_messages[] = sprintf(__('The log file %s could not be created.'), $file_path);
                return;
            }
        }

        $this->validated = true;
        $this->validation_messages[] = __('Write access to log files has been validated.');
    }
}
