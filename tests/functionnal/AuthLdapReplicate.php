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

/* Test for inc/authldapreplicate.class.php */

class AuthLDAPReplicate extends DbTestCase {

   public function testCanCreate() {
      $this->Login();
      $this->boolean((boolean)\AuthLdapReplicate::canCreate())->isTrue();

      $_SESSION['glpiactiveprofile']['config'] = READ;
      $this->boolean((boolean)\AuthLdapReplicate::canCreate())->isFalse();

      $_SESSION['glpiactiveprofile']['config'] = 0;
      $this->boolean((boolean)\AuthLdapReplicate::canCreate())->isFalse();
   }

   public function testCanPurge() {
      $this->Login();
      $this->boolean((boolean)\AuthLdapReplicate::canPurge())->isTrue();

      $_SESSION['glpiactiveprofile']['config'] = READ;
      $this->boolean((boolean)\AuthLdapReplicate::canCreate())->isFalse();

      $_SESSION['glpiactiveprofile']['config'] = 0;
      $this->boolean((boolean)\AuthLdapReplicate::canCreate())->isFalse();
   }

   public function testGetForbiddenStandardMassiveAction() {
      $this->Login();
      $replicate = new \AuthLdapReplicate();
      $result    = $replicate->getForbiddenStandardMassiveAction();
      $this->array($result)->isIdenticalTo([0 => 'update']);
   }

   public function testPrepareInputForAddAndUpdate() {
      $replicate = new \AuthLdapReplicate();

      foreach (['prepareInputForAdd', 'prepareInputForUpdate'] as $method) {
         //Do not set a port : no port added
         $result = $replicate->$method([
            'name' => 'test',
            'host' => 'host'
         ]);
         $this->array($result)->nothasKey('port');

         //Port=0, change value to 389
         $result = $replicate->$method([
            'name' => 'test',
            'host' => 'host',
            'port' => 0
         ]);
         $this->integer($result['port'])->isIdenticalTo(389);

         //Port set : do not change it's value
         $result = $replicate->$method([
            'name' => 'test',
            'host' => 'host',
            'port' => 3389
         ]);
         $this->integer($result['port'])->isIdenticalTo(3389);
      }
   }
}
