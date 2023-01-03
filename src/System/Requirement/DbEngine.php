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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
class DbEngine extends AbstractRequirement
{
    /**
     * DB instance.
     *
     * @var \DBmysql
     */
    private $db;

    public function __construct(\DBmysql $db)
    {
        $this->title = __('DB engine version');
        $this->db = $db;
    }

    protected function check()
    {
        $version_string = $this->db->getVersion();

        $server  = preg_match('/-MariaDB/', $version_string) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $version_string);

        switch ($server) {
            case 'MariaDB':
                $min_version = '10.2';
                break;
            case 'MySQL':
            default:
                $min_version = '5.7';
                break;
        }
        $is_supported = version_compare($version, $min_version, '>=');

        if ($is_supported) {
            $this->validated = true;
            $this->validation_messages[] = sprintf(
                __('Database engine version (%s) is supported.'),
                $version
            );
        } else {
            $msg = sprintf(__('Database engine version (%s) is not supported.'), $version);
            $msg .= ' ' . sprintf('Minimum required version is %s %s.', $server, $min_version);
            $this->validated = false;
            $this->validation_messages[] = $msg;
        }
    }
}
