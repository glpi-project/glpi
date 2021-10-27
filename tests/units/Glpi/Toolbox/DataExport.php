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

namespace tests\units\Glpi\Toolbox;

use Glpi\Toolbox\Sanitizer;

/**
 * Test class for src/Glpi/Toolbox/dataexport.class.php
 */
class DataExport extends \GLPITestCase {

   protected function normalizeValueForTextExportProvider(): iterable {
      // Standard value
      yield [
         'value'           => 'Some value',
         'expected_result' => 'Some value',
      ];

      // Ticket title column
      yield [
         'value'           => Sanitizer::sanitize(<<<HTML
<a id="Ticket1" href="/front/ticket.form.php?id=1" data-hasqtip="0">Ticket title</a>
<div id="contentTicket1" class="invisible"><div class="content"><p>Ticket content ...</p></div></div>
<script type="text/javascript">
//<![CDATA[

$(function(){\$('#Ticket1').qtip({
         position: { viewport: $(window) },
         content: {text: $('#contentTicket1')}, style: { classes: 'qtip-shadow qtip-bootstrap'}});});

//]]>
</script>
HTML, false),
         'expected_result' => "Ticket title ",
      ];
   }

   /**
    * @dataProvider normalizeValueForTextExportProvider
    */
   public function testNormalizeValueForTextExport(string $value, string $expected_result) {
      $dataexport = $this->newTestedInstance();

      $this->string($dataexport->normalizeValueForTextExport($value))->isEqualTo($expected_result);
   }
}
