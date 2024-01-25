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

namespace tests\units;

class SNMPCredential extends \FrontBaseClass
{
    public function testCreate()
    {
        $this->logIn();
        $this->addToCleanup(\SNMPCredential::class, ['name' => 'thetestuuidtoremove']);

        //load snmp credential form
        $crawler = $this->http_client->request('GET', $this->base_uri . 'front/snmpcredential.form.php');

        $auth_passphrase = '¡<av€ry$3"cur€p@ssp\'hr@se>!';
        $priv_passphrase = '>>P4ss"ph&ase<<';
        $crawler = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/snmpcredential.form.php',
            [
                'add'  => true,
                'name' => 'thetestuuidtoremove',
                'snmpversion' => 3,
                'username' => 'snmpuser',
                'auth_passphrase' => $auth_passphrase,
                'priv_passphrase' => $priv_passphrase,
                '_glpi_csrf_token' => $crawler->filter('input[name=_glpi_csrf_token]')->attr('value')
            ]
        );

        $credential = new \SNMPCredential();
        $this->boolean($credential->getFromDBByCrit(['name' => 'thetestuuidtoremove']))->isTrue();

        $this->string($credential->fields['auth_passphrase'])->isNotIdenticalTo($auth_passphrase);
        $this->string((new \GLPIKey())->decrypt($credential->fields['auth_passphrase']))->isIdenticalTo($auth_passphrase);
        $this->string($credential->fields['priv_passphrase'])->isNotIdenticalTo($priv_passphrase);
        $this->string((new \GLPIKey())->decrypt($credential->fields['priv_passphrase']))->isIdenticalTo($priv_passphrase);
    }
}
