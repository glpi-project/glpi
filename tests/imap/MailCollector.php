<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

use \atoum;

use \DbTestCase;

class MailCollector extends DbTestCase {
   public function beforeTestMethod($method) {
   }

   public function testGetEmpty() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([])
            ->boolean($this->testedInstance->getEmpty())
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([
                  'id'              => '',
                  'name'            => '',
                  'host'            => '',
                  'login'           => '',
                  'filesize_max'    => '2097152',
                  'is_active'       => 1,
                  'date_mod'        => '',
                  'comment'         => '',
                  'passwd'          => '',
                  'accepted'        => '',
                  'refused'         => '',
                  'use_kerberos'    => '',
                  'errors'          => '',
                  'use_mail_date'   => '',
                  'date_creation'   => '',
                  'requester_field' => ''
               ]);
   }
}
