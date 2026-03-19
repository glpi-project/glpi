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

namespace tests\units;

use Glpi\Tests\DbTestCase;

class AuthLDAPReplicateCountTest extends DbTestCase
{
    public function testCountReplicatesForLDAP(): void
    {
        $this->login();

        $ldap = new \AuthLDAP();
        $ldap_id = (int) $ldap->add([
            'name'        => 'LDAP_count_test',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
        ]);
        $this->assertGreaterThan(0, $ldap_id);
        $this->assertTrue($ldap->getFromDB($ldap_id));

        $this->assertSame(0, \AuthLDAP::countReplicatesForLDAP($ldap));

        $replicate = new \AuthLdapReplicate();
        $rep1_id = (int) $replicate->add([
            'name'         => 'Replicate1',
            'host'         => 'ldap1.example.com',
            'port'         => 389,
            'authldaps_id' => $ldap_id,
        ]);
        $this->assertGreaterThan(0, $rep1_id);

        $this->assertTrue($ldap->getFromDB($ldap_id));
        $this->assertSame(1, \AuthLDAP::countReplicatesForLDAP($ldap));

        $rep2_id = (int) $replicate->add([
            'name'         => 'Replicate2',
            'host'         => 'ldap2.example.com',
            'port'         => 389,
            'authldaps_id' => $ldap_id,
        ]);
        $this->assertGreaterThan(0, $rep2_id);

        $this->assertTrue($ldap->getFromDB($ldap_id));
        $this->assertSame(2, \AuthLDAP::countReplicatesForLDAP($ldap));
    }

    public function testGetTabNameForItemWithReplicateCounter(): void
    {
        $this->login();

        $ldap = new \AuthLDAP();
        $ldap_id = (int) $ldap->add([
            'name'        => 'LDAP_tab_count_test',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
        ]);
        $this->assertGreaterThan(0, $ldap_id);
        $this->assertTrue($ldap->getFromDB($ldap_id));

        $replicate = new \AuthLdapReplicate();
        $this->assertGreaterThan(0, (int) $replicate->add([
            'name'         => 'TabReplicate1',
            'host'         => 'ldaprep1.example.com',
            'port'         => 389,
            'authldaps_id' => $ldap_id,
        ]));

        $this->assertTrue($ldap->getFromDB($ldap_id));

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $result = $ldap->getTabNameForItem($ldap);
        $tab_html = $result[6];
        $this->assertStringContainsString('badge', $tab_html);
        $this->assertStringContainsString('1', strip_tags($tab_html));

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $result = $ldap->getTabNameForItem($ldap);
        $tab_html = $result[6];
        $this->assertStringNotContainsString('badge', $tab_html);
        $this->assertSame('Replicates', strip_tags($tab_html));
    }

    public function testCountReplicatesForNonExistentLDAP(): void
    {
        $this->login();

        $ldap = new \AuthLDAP();
        $this->assertSame(0, \AuthLDAP::countReplicatesForLDAP($ldap));
    }
}
