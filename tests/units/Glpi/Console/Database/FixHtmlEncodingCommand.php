<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace tests\units\Glpi\Console\Database;

use GLPITestCase;

class FixHtmlEncodingCommand extends GLPITestCase
{
    public function providerDoubleEncoding()
    {
        yield [
            'input'    => '&#38;lt;foo bar&#38;gt;',
            'expected' => '&#60;foo bar&#62;',
        ];
    }

    /**
     * @DataProvider providerDoubleEncoding
     *
     * @return void
     */
    public function testDoubleEncoding($input, $expected)
    {
        $instance = $this->newTestedInstance();
        $output = $this->callPrivateMethod($instance, 'doubleEncoding', $input);
        $this->string($output)->isEqualTo($expected);
    }

    public function providerEmailsInOutlookGenetaredContent()
    {
        yield [
            'input' => '&amp;lt;helpdesk@some-domain.com&amp;gt;',
            'expected' => '&lt;helpdesk@some-domain.com&gt;',
        ];

        yield [
            'input' => '&#38;amp;lt;helpdesk@some-domain.com&#38;amp;gt;',
            'expected' => '&lt;helpdesk@some-domain.com&gt;',
        ];
    }

    /**
     * @DataProvider providerEmailsInOutlookGenetaredContent
     *
     * @param string $input
     * @param string $expected
     * @return void
     */
    public function testEmailsInOutlookGenetaredContent($input, $expected)
    {
        $instance = $this->newTestedInstance();
        $output = $this->callPrivateMethod($instance, 'emailsInOutlookGenetaredContent', $input);
        $this->string($output)->isEqualTo($expected);
    }
}