<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/ldapconnection.class.php */

class LdapConnection extends DbTestCase {

   public function setUp() {
      parent::setUp();
      $ldap = new \AuthLDAP();
      $id = $ldap->add([
         'name'          => 'ldap',
         'host'          => 'ldap-master',
         'port'          => '3389',
         'login_field'   => 'uid',
         'basedn'        => 'dc=glpi,dc=org',
         'rootdn'        => 'cn=admin,dc=glpi,dc=org',
         'rootdn_passwd' => 'password',
         'condition'     => '(objectclass=inetOrgPerson)',
         'is_active'     => 1,
         'is_default'    => 1
      ]);
      $this->integer((int)$id)->isGreaterThan(0);
   }

   /**
   * @tags  ldap
   */
   public function testConnectToServer() {
      $ldap   = getItemByTypeName('AuthLDAP', 'ldap');

      //Anonymous connection
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port']);
      $this->variable($result)->isNotFalse();

      //Connection with a rootdn and password
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                \Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->variable($result)->isNotFalse();

      $result = LdapConnection::connectToServer('foo',
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                \Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->boolean($result)->isFalse();
   }
}
