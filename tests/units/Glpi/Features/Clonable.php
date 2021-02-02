<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace tests\units\Glpi\Features;

/**
 * Test for the {@link \Glpi\Features\Clonable} feature
 */
class Clonable extends \DbTestCase {

   public function massiveActionTargetingProvider() {
      return [
         [\Computer::class, true],
         [\Monitor::class, true],
         [\Software::class, true],
         [\Ticket::class, true],
         [\Plugin::class, false],
         [\Config::class, false]
      ];
   }

   /**
    * @param $class
    * @param $result
    * @dataProvider massiveActionTargetingProvider
    */
   public function testMassiveActionTargeting($class, $result) {
      $this->login();
      $ma_prefix = 'MassiveAction' . \MassiveAction::CLASS_ACTION_SEPARATOR;
      $actions = \MassiveAction::getAllMassiveActions($class);
      $this->boolean(array_key_exists($ma_prefix . 'clone', $actions))->isIdenticalTo($result);
   }
}