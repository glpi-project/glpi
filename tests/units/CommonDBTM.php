<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

namespace tests\units;

/* Test for inc/commondbtm.class.php */

class CommonDBTM extends \GLPITestCase {

   /**
    * @covers CommonDBTM::showDates()
    *
    * @return void
    */
   public function testShowDates() {
      $common = new \CommonDBTM();

      $common->fields = ['id' => '105'];
      $creation = '2017-01-01';
      $edition = '2017-01-05';

      //not dates, empty line
      $result = $common->showDates();
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan=''></th><th colspan=''></th></tr>"
      );

      $common->fields = [
         'id'              => 105,
         'date_creation'   => $creation
      ];

      //only creation date, secodn column empty
      $result = $common->showDates();
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan=''>Created on 2017-01-01 </th><th colspan=''></th></tr>"
      );

      $common->fields = [
         'id'        => 105,
         'date_mod'  => $edition
      ];

      //only modification date, first column empty
      $result = $common->showDates();
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan=''></th><th colspan=''>Last update on 2017-01-05 </th></tr>"
      );

      $common->fields = [
         'id'              => 105,
         'date_creation'   => $creation,
         'date_mod'        => $edition
      ];

      //both dates, no column empty
      $result = $common->showDates();
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan=''>Created on 2017-01-01 </th><th colspan=''>Last update on 2017-01-05 </th></tr>"
      );

      $options = [
         'colspan'   => 3
      ];

      //both dates, with a specific colspan
      $result = $common->showDates($options);
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan='3'>Created on 2017-01-01 </th><th colspan='3'>Last update on 2017-01-05 </th></tr>"
      );

      $common->fields['template_name'] = 'Faked template';

      //both dates, created from a template
      $result = $common->showDates($options);
      $this->string($result)->isIdenticalTo(
         "<tr class='tab_bg_1 footerRow'><th colspan='1'>Created on 2017-01-01 </th><th colspan='1'>Last update on 2017-01-05 </th>" .
         "<th colspan='2'>Created from the template Faked template</th></tr>"
      );

      $result = null;
      $noresult = null;

      //ensure function does not output anything when display is set to false
      /*ob_start();
      $result = $common->showDates($options);
      $obresult = ob_get_contents();
      ob_end_clean();

      $this->assertEquals(
         "<tr class='tab_bg_1 footerRow'><th colspan='1'>Created on 2017-01-01 </th><th colspan='1'>Last update on 2017-01-05 </th>" .
         "<th colspan='2'>Created from the template Faked template</th></tr>",
         $result
      );
      $this->assertEquals("", $obresult);

      //ensure function does output when display is set to true
      $options['display'] = true;
      ob_start();
      $result = $common->showDates($options);
      $obresult = ob_get_contents();
      ob_end_clean();

      $this->assertEquals("", $result);
      $this->assertEquals(
         "<tr class='tab_bg_1 footerRow'><th colspan='1'>Created on 2017-01-01 </th><th colspan='1'>Last update on 2017-01-05 </th>" .
         "<th colspan='2'>Created from the template Faked template</th></tr>",
         $obresult
      );*/
   }
}
