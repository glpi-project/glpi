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

/**
 * @since 9.5.0
 */
class SeLinux extends AbstractRequirement
{
    public function __construct()
    {
        parent::__construct(
            __('SELinux configuration'),
            null,
            true,
            false,
            null, // $out_of_context will be computed on check
        );
    }

    protected function check()
    {
        $is_slash_separator = DIRECTORY_SEPARATOR == '/';
        $are_bin_existing = $this->doesSelinuxBinariesExists();
        $are_functions_existing = $this->doesSelinuxIsEnabledFunctionExists()
            && $this->doesSelinuxIsEnabledFunctionExists() //@phpstan-ignore-line
            && $this->doesSelinuxBooleanFunctionExists();

        $exec_enabled = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')), true);

        if (!$is_slash_separator || (!$are_bin_existing && !$are_functions_existing)) {
            // This is not a SELinux system
            $this->out_of_context = true;
            $this->validated = false;
            return;
        }

        if ($this->doesSelinuxIsEnabledFunctionExists() && $this->doesSelinuxGetenforceFunctionExists()) {
            // Use https://pecl.php.net/package/selinux
            if (!$this->isSelinuxEnabled()) {
                $mode = 'disabled';
            } else {
                $mode = $this->getSelinxEnforceStatus();
                // Make it human-readable, with same output as the command
                if ($mode == 1) {
                    $mode = 'enforcing';
                } else if ($mode == 0) {
                    $mode = 'permissive';
                }
            }
        } else {
            exec('/usr/sbin/getenforce', $mode);
            if (!$mode) {
                $mode = 'unknown';
            } else {
                $mode = strtolower(array_pop($mode));
            }
        }
        if (!in_array($mode, ['enforcing', 'permissive', 'disabled'])) {
            $mode = 'unknown';
        }

        //TRANS: %s is mode name (Permissive, Enforcing, Disabled or Unknown)
        $this->title = sprintf(__('SELinux mode is %s'), ucfirst($mode));

        if ('enforcing' !== $mode) {
            $this->validated = false;
            $this->validation_messages[] = __('For security reasons, SELinux mode should be Enforcing.');
            return;
        }

        // No need to check file context as DirectoryWriteAccess requirements will show issues

        $bools = [
            'httpd_can_network_connect',
            'httpd_can_network_connect_db',
            'httpd_can_sendmail',
        ];

        $has_missing_boolean = false;

        foreach ($bools as $bool) {
            if ($this->doesSelinuxBooleanFunctionExists()) {
                $state = $this->getSelinuxBoolean($bool);
                if ($state == 1) {
                    $state = 'on';
                } else if ($state == 0) {
                    $state = 'off';
                }
            } else {
                // command result is something like "httpd_can_network_connect --> on"
                if ($exec_enabled) {
                    $state = preg_replace(
                        '/^.*(on|off)$/',
                        '$1',
                        strtolower(exec('/usr/sbin/getsebool ' . $bool))
                    );
                } else {
                    $state = 'unknown';
                }
            }
            if (!in_array($state, ['on', 'off'])) {
                $state = 'unknown';
            }

            if ('on' !== $state) {
                $has_missing_boolean = true;
                $this->validation_messages[] = sprintf(
                    __('SELinux boolean %s is %s, some features may require this to be on.'),
                    $bool,
                    $state
                );
            }
        }

        $this->validated = !$has_missing_boolean;

        if (!$has_missing_boolean) {
            $this->validation_messages[] = __('SELinux configuration is OK.');
        }
    }

    protected function doesSelinuxBinariesExists(): bool
    {
        return file_exists('/usr/sbin/getenforce') && file_exists('/usr/sbin/getsebool');
    }

    protected function doesSelinuxIsEnabledFunctionExists(): bool
    {
        return function_exists('selinux_is_enabled');
    }

    protected function doesSelinuxGetenforceFunctionExists(): bool
    {
        return function_exists('selinux_getenforce');
    }

    protected function doesSelinuxBooleanFunctionExists(): bool
    {
        return function_exists('selinux_get_boolean_active');
    }

    protected function isSelinuxEnabled(): bool
    {
        return function_exists('selinux_is_enabled') && selinux_is_enabled();
    }

    protected function getSelinxEnforceStatus(): int
    {
        if (function_exists('selinux_getenforce')) {
            return selinux_getenforce();
        }
        return 0;
    }

    protected function getSelinuxBoolean(string $bool): int
    {
        if (function_exists('selinux_get_boolean_active')) {
            return selinux_get_boolean_active($bool);
        }
        return 0;
    }
}
