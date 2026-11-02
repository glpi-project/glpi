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

namespace Glpi\System\Requirement;

/**
 * Check that the security key files stored in the configuration directory can
 * be used, i.e. existing ones can be read, and missing ones can be generated.
 */
class SecurityKeysAccess extends AbstractRequirement
{
    /**
     * Security key files names, relative to the configuration directory.
     *
     * @var string[]
     */
    private const KEY_FILES = [
        'glpicrypt.key', // see \GLPIKey class
        'oauth.pem',     // see \Glpi\OAuth\Server class
        'oauth.pub',     // see \Glpi\OAuth\Server class
    ];

    /**
     * Configuration directory path.
     *
     * @var string
     */
    private $config_dir;

    /**
     * @param string $config_dir Configuration directory path.
     */
    public function __construct(string $config_dir = GLPI_CONFIG_DIR)
    {
        parent::__construct(
            __('Permissions for security keys files')
        );

        $this->config_dir = $config_dir;
    }

    protected function check()
    {
        $this->validated = true;

        // Missing keys are generated during the installation/update process.
        $is_dir_writable = is_writable($this->config_dir);

        foreach (self::KEY_FILES as $filename) {
            $file_path = $this->config_dir . '/' . $filename;

            if (!file_exists($file_path)) {
                if (!$is_dir_writable) {
                    $this->validated = false;
                    $this->validation_messages[] = sprintf(__('The security key file %s could not be created.'), $file_path);
                }
            } elseif (!is_readable($file_path)) {
                $this->validated = false;
                $this->validation_messages[] = sprintf(__('The security key file %s is not readable.'), $file_path);
            }
        }

        if ($this->validated) {
            $this->validation_messages[] = __('Security key files are accessibles.');
        }
    }
}
