<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Toolbox;

use Glpi\Toolbox\Sanitizer;

/**
 * Test class for src/Glpi/Toolbox/dataexport.class.php
 */
class DataExport extends \GLPITestCase
{
    protected function normalizeValueForTextExportProvider(): iterable
    {
        // Standard value
        yield [
            'value'           => 'Some value',
            'expected_result' => 'Some value',
        ];

        // Entity name with cyrillic chars
        yield [
            'value'           => <<<HTML
<a id='Entity_1_1' href='/front/entity.form.php?id=1'><span class="text-muted">Root entity</span> &gt; <span >Это испытание!</span></a>
HTML,
            'expected_result' => 'Root entity > Это испытание!',
        ];

        // Malformed HTML (some special chars are not encoded)
        // It is not considered as sanitized, so it will be returned verbatim.
        yield [
            'value'           => '&#60;span&#62;span containing non encoded > special char&#60;/span&#62;',
            'expected_result' => '&#60;span&#62;span containing non encoded > special char&#60;/span&#62;',
        ];

        // Ticket title column
        yield [
            'value'           => Sanitizer::encodeHtmlSpecialChars(<<<HTML
<a id="Ticket1" href="/front/ticket.form.php?id=1" data-hasqtip="0">Ticket title</a>
<div id="contentTicket1" class="invisible"><div class="content"><p>Ticket content ...</p></div></div>
<script type="text/javascript">
//<![CDATA[

$(function(){\$('#Ticket1').qtip({
         position: { viewport: $(window) },
         content: {text: $('#contentTicket1')}, style: { classes: 'qtip-shadow qtip-bootstrap'}});});

//]]>
</script>
HTML),
            'expected_result' => 'Ticket title',
        ];

        // Ticket status
        yield [
            'value'           => Sanitizer::encodeHtmlSpecialChars(<<<HTML
<i class="itilstatus far fa-circle assigned me-1" title="" data-bs-toggle="tooltip" data-bs-original-title="Processing (assigned)" aria-label="Processing (assigned)"></i>&nbsp;Processing (assigned)</span>
HTML),
            'expected_result' => 'Processing (assigned)',
        ];
    }

    /**
     * @dataProvider normalizeValueForTextExportProvider
     */
    public function testNormalizeValueForTextExport(string $value, string $expected_result)
    {
        $dataexport = $this->newTestedInstance();

        $this->string($dataexport->normalizeValueForTextExport($value))->isEqualTo($expected_result);
    }
}
