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

/**
 * Class IPAddress_IPNetwork : Connection between IPAddress and IPNetwork
 *
 * @since 0.84
 **/
class IPAddress_IPNetwork extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1 = 'IPAddress';
    public static $items_id_1 = 'ipaddresses_id';

    public static $itemtype_2 = 'IPNetwork';
    public static $items_id_2 = 'ipnetworks_id';


    /**
     * Update IPNetwork's dependency
     *
     * @param $network IPNetwork object
     **/
    public static function linkIPAddressFromIPNetwork(IPNetwork $network)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $linkObject    = new self();
        $linkTable     = $linkObject->getTable();
        $ipnetworks_id = $network->getID();

       // First, remove all links of the current Network
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $linkTable,
            'WHERE'  => ['ipnetworks_id' => $ipnetworks_id]
        ]);
        foreach ($iterator as $link) {
            $linkObject->delete(['id' => $link['id']]);
        }

       // Then, look each IP address contained inside current Network
        $iterator = $DB->request([
            'SELECT' => [
                new \QueryExpression($DB->quoteValue($ipnetworks_id) . ' AS ' . $DB->quoteName('ipnetworks_id')),
                'id AS ipaddresses_id'
            ],
            'FROM'   => 'glpi_ipaddresses',
            'WHERE'  => $network->getCriteriaForMatchingElement('glpi_ipaddresses', 'binary', 'version'),
            'GROUP'  => 'id'
        ]);
        foreach ($iterator as $link) {
            $linkObject->add($link);
        }
    }


    /**
     * @param $ipaddress IPAddress object
     **/
    public static function addIPAddress(IPAddress $ipaddress)
    {

        $linkObject = new self();
        $input      = ['ipaddresses_id' => $ipaddress->getID()];

        $entity         = $ipaddress->getEntityID();
        $ipnetworks_ids = IPNetwork::searchNetworksContainingIP($ipaddress, $entity);
        if ($ipnetworks_ids !== false) {
           // Beware that invalid IPaddresses don't have any valid address !
            $entity = $ipaddress->getEntityID();
            foreach (IPNetwork::searchNetworksContainingIP($ipaddress, $entity) as $ipnetworks_id) {
                $input['ipnetworks_id'] = $ipnetworks_id;
                $linkObject->add($input);
            }
        }
    }
}
