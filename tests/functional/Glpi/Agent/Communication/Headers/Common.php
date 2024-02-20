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

namespace tests\units\Glpi\Agent\Communication\Headers;

use GLPITestCase;

/* Test for inc/glpi/agent/communication/headers/common.class.php */

class Common extends GLPITestCase
{
    protected function namesProvider(): array
    {
        return [
            [
                'propname' => 'content_type',
                'headername' => 'Content-Type'
            ], [
                'propname' => 'glpi_agent_id',
                'headername' => 'GLPI-Agent-ID'
            ], [
                'propname' => 'glpi_cryptokey_id',
                'headername' => 'GLPI-CryptoKey-ID'
            ], [
                'propname' => 'glpi_any_any',
                'headername' => 'GLPI-Any-Any'
            ], [
                'propname' => 'header_with_id_in',
                'headername' => 'Header-With-ID-In'
            ]
        ];
    }

    /**
     * @dataProvider namesProvider
     */
    public function testHeaders($propname, $headername)
    {
        $this
        ->if($this->newTestedInstance)
        ->then
         ->string($this->testedInstance->getHeaderName($propname))
            ->isIdenticalTo($headername);
    }

   /* Useful only when legacy will no longer be the default */
   /*public function testGetHeadersWException() {
      $this
         ->exception(
            function() {
               $this
                  ->if($this->newTestedInstance)
                  ->then
                  ->array($this->testedInstance->getHeaders());
            }
         )->hasMessage('Content-Type HTTP header is mandatory!');
   }*/

    public function testGetHeaders()
    {
        $instance = $this->newTestedInstance;
        $instance->setHeader('Content-Type', 'application/xml');
        $instance->setHeader('GLPI-Agent-ID', 'anything');

        $this->array($instance->getHeaders())
         ->hasKeys(['Content-Type', 'Pragma', 'GLPI-Agent-ID']);

        $instance = $this->newTestedInstance;
        $instance->setHeaders([
            'Content-Type' => 'application/xml',
            'GLPI-Agent-ID' => 'anything'
        ]);

        $this->array($instance->getHeaders())
         ->hasKeys(['Content-Type', 'Pragma', 'GLPI-Agent-ID']);
    }

    public function testGetRequireds()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
         ->array($this->testedInstance->getRequireds())
         ->hasSize(5);
    }

    public function testGetHeadersNames()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
         ->array($this->testedInstance->getHeadersNames())
         ->hasSize(1);
    }
}
