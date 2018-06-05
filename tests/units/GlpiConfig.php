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

/* Test for inc/glpi.class.php */

class GlpiConfig extends \GLPITestCase {

   public function testConstructor() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->integer($this->testedInstance->count())
               ->isIdenticalTo(61);

   }

   public function testGetTicketTypes() {
      $this->newTestedInstance;
      $this->output(
         function () {
            $this->exception(
               function () {
                  $this->array($this->testedInstance['ticket_types'])
                     ->isIdenticalTo($this->testedInstance['itil_types']);
               }
            )->message->contains('Use itil_types instead');
         }
      )->contains('GlpiConfig->offsetGet()');
   }

   public function testSetTicketTypes() {
      $this->newTestedInstance;
      $this->output(
         function () {
            $this->exception(
               function () {
                  $this->testedInstance['ticket_types'] = ['one', 'two'];
               }
            )->message->contains('See itil_types instead (well, in facts, do **not** override config!)');
         }
      )->contains('GlpiConfig->offsetSet()');
   }
}
