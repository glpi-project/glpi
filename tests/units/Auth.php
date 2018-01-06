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

use \DbTestCase;

/* Test for inc/auth.class.php */

class Auth extends DbTestCase {

   protected function loginProvider() {
      return [
         ['john', 1],
         ['john doe', 1],
         ['john_doe', 1],
         ['john-doe', 1],
         ['john.doe', 1],
         ['john \'o doe', 1],
         ['john@doe.com', 1],
         ['@doe.com', 1],
         ['john " doe', 0],
         ['john^doe', 0],
         ['john$doe', 0],
         [null, 0],
         ['', 0]
      ];
   }

   /**
    * @dataProvider loginProvider
    */
   public function testIsValidLogin($login, $isvalid) {
      $this->variable(\Auth::isValidLogin($login))->isIdenticalTo($isvalid);
   }
}
