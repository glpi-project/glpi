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

use DbTestCase;

/* Test for inc/networkport.class.php */

class NetworkName extends DbTestCase
{
    public function testAddSimpleNetworkName()
    {
        $this->login();

       //First add IPNetwork
        $IPNetwork = new \IPNetwork();
        $ipnetwork_id = $IPNetwork->add([
            'name'               => "test",
            'network'            => '1.1.1.0 / 255.255.255.0',
            'gateway'            => '1.1.1.254',
            'entites_id'         => 0,
            'is_recursive'       => 1,
            'addressable'        => 0,
        ]);

        $this->integer((int)$ipnetwork_id)->isGreaterThan(0);
        $this->boolean($IPNetwork->getFromDB($ipnetwork_id))->isTrue();
        $current_ipnetwork = $IPNetwork->fields;

        unset($current_ipnetwork['id']);
        unset($current_ipnetwork['date_mod']);
        unset($current_ipnetwork['date_creation']);
        unset($current_ipnetwork['level']);
        unset($current_ipnetwork["ancestors_cache"]);
        unset($current_ipnetwork["sons_cache"]);

        $expected = [
            "entities_id"   => 0,
            "is_recursive"  => 1,
            "ipnetworks_id" => 0,
            "completename"  => "test",
            "addressable"   => 0,
            "version"       => 4,
            "name"          => "test",
            "address"       => "1.1.1.0",
            "address_0"     => 0,
            "address_1"     => 0,
            "address_2"     => 65535,
            "address_3"     => 16843008,
            "netmask"       => "255.255.255.0",
            "netmask_0"     => 4294967295,
            "netmask_1"     => 4294967295,
            "netmask_2"     => 4294967295,
            "netmask_3"     => 4294967040,
            "gateway"       => "1.1.1.254",
            "gateway_0"     => 0,
            "gateway_1"     => 0,
            "gateway_2"     => 65535,
            "gateway_3"     => 16843262,
            "comment"       => null,
            "network"       => "1.1.1.0 / 255.255.255.0",
        ];
        $this->array($current_ipnetwork)->isIdenticalTo($expected);

       //Second add NetworkName
        $Networkname = new \NetworkName();
        $networkname_id = $Networkname->add([
            'name'          => "test",
            '_ipaddresses'  => [-1 => '1.1.1.24'],
            'entities_id'    => 0,
            'items_id'      => 0,
            'itemtype'      => '',
            'fqdns_id'      => 0,
            'comment'       => '',
            'ipnetworks_id' => 0
        ]);

        $this->integer((int)$networkname_id)->isGreaterThan(0);
        $this->boolean($Networkname->getFromDB($networkname_id))->isTrue();
        $current_networkname = $Networkname->fields;

        unset($current_networkname['id']);
        unset($current_networkname['date_mod']);
        unset($current_networkname['date_creation']);

        $expected = [
            "entities_id"   => 0,
            "items_id"      => 0,
            "itemtype"      => "",
            "name"          => "test",
            "comment"       => "",
            "fqdns_id"      => 0,
            "ipnetworks_id" => $ipnetwork_id, //check the automatic recovery of IPNetwork previsouly created
            "is_deleted"    => 0,
            "is_dynamic"    => 0,
        ];
        $this->array($current_networkname)->isIdenticalTo($expected);
    }
}
