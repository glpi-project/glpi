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

use DbTestCase;

/* Test for inc/calendar.class.php */

class Calendar extends DbTestCase {

   public function testComputeEndDate() {
      $calendar = new \Calendar;

      // get Default calendar
      $calendar->getFromDB(1);

      // ## test future dates
      $end_date = $calendar->ComputeEndDate("2018-11-19 10:00:00", 7 * DAY_TIMESTAMP, 0, true);
      $this->string($end_date)->isEqualTo("2018-11-28 10:00:00");
      // end of day
      $end_date = $calendar->ComputeEndDate("2018-11-19 10:00:00", 7 * DAY_TIMESTAMP, 0, true, true);
      $this->string($end_date)->isEqualTo("2018-11-28 20:00:00");

      // ## test past dates
      $end_date = $calendar->ComputeEndDate("2018-11-19 10:00:00", -7 * DAY_TIMESTAMP, 0, true);
      $this->string($end_date)->isEqualTo("2018-11-08 10:00:00");
      // end of day
      $end_date = $calendar->ComputeEndDate("2018-11-19 10:00:00", -7 * DAY_TIMESTAMP, 0, true, true);
      $this->string($end_date)->isEqualTo("2018-11-08 20:00:00");
   }
}
