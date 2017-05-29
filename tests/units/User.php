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

use \atoum;

/* Test for inc/user.class.php */

class User extends atoum {
   public function testGenerateUserToken() {
      $user = getItemByTypeName('User', TU_USER);
      $this->variable($user->fields['personal_token_date'])->isNull();
      $this->variable($user->fields['personal_token'])->isNull();

      $token = \User::getToken($user->getID());
      $this->string($token)->isNotEmpty();

      $user->getFromDB($user->getID());
      $this->string($user->fields['personal_token'])->isIdenticalTo($token);
      $this->string($user->fields['personal_token_date'])->isIdenticalTo($_SESSION['glpi_currenttime']);

      //reset
      $this->boolean(
         $user->update([
            'id'                    => $user->getID(),
            'personal_token'        => 'NULL',
            'personal_token_date'   => 'NULL'
         ])
      )->isTrue();
   }
}
