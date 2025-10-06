<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateDevLdapCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_dev_ldap');
        $this->setDescription('Create a ready to use AuthLDAP object.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $CFG_GLPI;

        $ldap = new AuthLDAP();
        $id = $ldap->add([
            'name'          => 'openldap',
            'host'          => 'openldap',
            'basedn'        => 'dc=glpi,dc=org',
            'rootdn'        => 'cn=admin,dc=glpi,dc=org',
            'port'          => '389',
            'condition'     => '(objectClass=inetOrgPerson)',
            'login_field'   => 'uid',
            'sync_field'    => 'entryuuid',
            'use_tls'       => 0,
            'use_dn'        => 1,
            'is_active'     => 1,
            'rootdn_passwd' => 'admin',
            'use_bind'      => 1,
        ]);
        if (!$id) {
            $output->writeln("<error>Failed to create AuthLDAP</error>");
        }

        $link = $CFG_GLPI['url_base'] . $ldap->getLinkURL();
        $output->writeLn("<info>$link</info>");
        return Command::SUCCESS;
    }
}
