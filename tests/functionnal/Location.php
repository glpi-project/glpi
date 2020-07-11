<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/location.class.php */

class Location extends DbTestCase {

   public function testInheritGeolocation() {
      $location1 = new \Location();
      $location1_id = $location1->add([
         'name'         => 'inherit_geo_test_parent',
         'latitude'     => '48.8566',
         'longitude'    => '2.3522',
         'altitude'     => '115'
      ]);
      $this->integer((int) $location1_id)->isGreaterThan(0);
      $location2 = new \Location();
      $location2_id = $location2->add([
         'locations_id' => $location1_id,
         'name'         => 'inherit_geo_test_child',
      ]);
      $this->integer((int) $location2_id)->isGreaterThan(0);
      $this->string($location2->fields['latitude'])->isEqualTo($location1->fields['latitude']);
      $this->string($location2->fields['longitude'])->isEqualTo($location1->fields['longitude']);
      $this->string($location2->fields['altitude'])->isEqualTo($location1->fields['altitude']);

      // Make sure we don't overwrite data a user sets
      $location3 = new \Location();
      $location3_id = $location3->add([
         'locations_id' => $location1_id,
         'name'         => 'inherit_geo_test_child2',
         'latitude'     => '41.3851',
         'longitude'    => '2.1734',
         'altitude'     => '39'
      ]);
      $this->integer((int) $location3_id)->isGreaterThan(0);
      $this->string($location3->fields['latitude'])->isEqualTo('41.3851');
      $this->string($location3->fields['longitude'])->isEqualTo('2.1734');
      $this->string($location3->fields['altitude'])->isEqualTo('39');
   }
}
