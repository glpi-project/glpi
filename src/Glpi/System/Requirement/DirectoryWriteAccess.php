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

namespace Glpi\System\Requirement;

use Safe\Exceptions\FilesystemException;
use Toolbox;

use function Safe\realpath;

/**
 * @since 9.5.0
 */
class DirectoryWriteAccess extends AbstractRequirement
{
    /**
     * Directory path.
     *
     * @var string
     */
    private $path;

    /**
     * @param string      $path         Directory path.
     * @param bool        $optional     Indicated if write access is optional.
     * @param string|null $description  Requirement description.
     */
    public function __construct(string $path, bool $optional = false, ?string $description = null)
    {
        try {
            switch (realpath($path)) {
                case realpath(GLPI_CACHE_DIR):
                    $title = __('Permissions for cache files');
                    break;
                case realpath(GLPI_CONFIG_DIR):
                    $title = __('Permissions for setting files');
                    break;
                case realpath(GLPI_CRON_DIR):
                    $title = __('Permissions for automatic actions files');
                    break;
                case realpath(GLPI_DOC_DIR):
                    $title = __('Permissions for document files');
                    break;
                case realpath(GLPI_GRAPH_DIR):
                    $title = __('Permissions for graphic files');
                    break;
                case realpath(GLPI_LOCK_DIR):
                    $title = __('Permissions for lock files');
                    break;
                case realpath(GLPI_MARKETPLACE_DIR):
                    $title = __('Permissions for marketplace directory');
                    break;
                case realpath(GLPI_PLUGIN_DOC_DIR):
                    $title = __('Permissions for plugins document files');
                    break;
                case realpath(GLPI_PICTURE_DIR):
                    $title = __('Permissions for pictures files');
                    break;
                case realpath(GLPI_RSS_DIR):
                    $title = __('Permissions for rss files');
                    break;
                case realpath(GLPI_SESSION_DIR):
                    $title = __('Permissions for session files');
                    $session_handler = ini_get('session.save_handler'); // @phpstan-ignore theCodingMachineSafe.function
                    $optional = $session_handler !== false && strtolower($session_handler) !== 'files';
                    $description = __('If you have "session.save_handler" set to something besides "files" in your php.ini, this is not required.');
                    break;
                case realpath(GLPI_TMP_DIR):
                    $title = __('Permissions for temporary files');
                    break;
                case realpath(GLPI_UPLOAD_DIR):
                    $title = __('Permissions for upload files');
                    break;
                default:
                    $title = sprintf(__('Permissions for directory %s'), $path);
                    break;
            }
        } catch (FilesystemException $e) {
            $title = sprintf(__('Permissions for directory %s'), $path);
        }

        parent::__construct($title, $description, $optional);

        $this->path = $path;
    }

    protected function check()
    {

        $result = Toolbox::testWriteAccessToDirectory($this->path);

        $this->validated = $result === 0;

        if (0 === $result) {
            $this->validated = true;
            $this->validation_messages[] = sprintf(__('Write access to %s has been validated.'), $this->path);
        } else {
            switch ($result) {
                case 1:
                    $this->validation_messages[] = sprintf(__("The file was created in %s but can't be deleted."), $this->path);
                    break;
                case 2:
                    $this->validation_messages[] = sprintf(__('The file could not be created in %s.'), $this->path);
                    break;
                case 3:
                    $this->validation_messages[] = sprintf(__('The directory was created in %s but could not be removed.'), $this->path);
                    break;
                case 4:
                    $this->validation_messages[] = sprintf(__('The directory could not be created in %s.'), $this->path);
                    break;
            }
        }
    }
}
