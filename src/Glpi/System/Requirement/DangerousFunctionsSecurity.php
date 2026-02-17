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

use function Safe\ini_get;

/**
 * @since 10.0.3
 */
class DangerousFunctionsSecurity extends AbstractRequirement
{
    /**
     * @var string[]
     */
    protected array $dangerous_functions = [
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
        'pcntl_alarm',
        'pcntl_fork',
        'pcntl_waitpid',
        'pcntl_wait',
        'pcntl_wifexited',
        'pcntl_wifstopped',
        'pcntl_wifsignaled',
        'pcntl_wifcontinued',
        'pcntl_wexitstatus',
        'pcntl_wtermsig',
        'pcntl_wstopsig',
        'pcntl_get_last_error',
        'pcntl_strerror',
        'pcntl_sigprocmask',
        'pcntl_sigwaitinfo',
        'pcntl_sigtimedwait',
        'pcntl_exec',
        'pcntl_getpriority',
        'pcntl_setpriority',
        'pcntl_unshare',
        'putenv',
        'dl',
        'openlog',
        'syslog',
        'closelog',
        'apache_child_terminate',
        'apache_setenv',
    ];

    public function __construct()
    {
        parent::__construct(
            __('Security configuration for dangerous functions'),
            __('Ensure dangerous functions are disabled.'),
            true,
            true,
        );
    }

    protected function check()
    {
        $enabled_functions = [];
        foreach ($this->dangerous_functions as $function) {
            if (function_exists($function)) {
                $enabled_functions[] = $function;
            }
        }
        $this->validation_messages[] = sprintf(
            __('Functions "%s" are enabled. Please disable them in php.ini (see disable_functions directive) to avoid security risks.'),
            implode(', ', $enabled_functions)
        );
    }
}