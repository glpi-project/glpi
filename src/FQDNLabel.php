<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Safe\Exceptions\PcreException;

use function Safe\preg_match;

/**
 * Create an abstraction layer for any kind of internet label
 */


/// Class FQDNLabel - any kind of internet label (computer name as well as alias)
/// Since version 0.84
abstract class FQDNLabel extends CommonDBChild
{
    // Inherits from CommonDBChild as it must be attached to a specific element
    // (NetworkName, NetworkPort, ...)

    public function getInternetName()
    {

        // get the full computer name of the current object (for instance : forge.indepnet.net)
        return self::getInternetNameFromLabelAndDomainID(
            $this->fields["name"],
            $this->fields["fqdns_id"]
        );
    }

    public static function getIcon()
    {
        return 'ti ti-signature';
    }

    /**
     * Get the internet name from a label and a domain ID
     *
     * @param string  $label   the label of the computer or its alias
     * @param integer $domain  id of the domain that owns the item
     *
     * @return string  result the full internet name
     **/
    public static function getInternetNameFromLabelAndDomainID($label, $domain)
    {

        $domainName = FQDN::getFQDNFromID($domain);
        if (!empty($domainName)) {
            return $label . "." . $domainName;
        }
        return $label;
    }


    /**
     * \brief Check FQDN label
     * Check a label regarding section 2.1 of RFC 1123 : 63 lengths and no other characters
     * than alphanumerics. Minus ('-') is allowed if it is not at the end or begin of the lable.
     *
     * @param string $label  the label to check
     **/
    public static function checkFQDNLabel($label)
    {
        try {
            if (strlen($label) == 1) {
                if (!preg_match("/^[0-9A-Za-z]$/", $label, $regs)) {
                    return false;
                }
            } else {
                $fqdn_regex = "/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)$/";
                if (!preg_match($fqdn_regex, $label, $regs)) {
                    //check also Internationalized domain name
                    $idn = idn_to_ascii($label);
                    if (!preg_match($fqdn_regex, $idn, $regs)) {
                        return false;
                    }
                }
            }
        } catch (PcreException $e) {
            return false;
        }
        return true;
    }


    /**
     * @param $input
     **/
    public function prepareLabelInput($input)
    {
        if (isset($input['name']) && !empty($input['name'])) {
            // Empty names are allowed

            $input['name'] = strtolower($input['name']);

            // Before adding a name, we must unsure its is valid : it conforms to RFC
            if (!self::checkFQDNLabel($input['name'])) {
                Session::addMessageAfterRedirect(htmlescape(sprintf(
                    __('Invalid internet name: %s'),
                    $input['name']
                )), false, ERROR);
                return false;
            }
        }
        return $input;
    }


    /**
     * @param $input
     **/
    public function prepareIPNetworkFromInput($input)
    {

        //getIPNetwork from IPV4 if not set
        if (!isset($input['ipnetworks_id']) || (isset($input['ipnetworks_id']) && $input['ipnetworks_id'] == 0)) {
            if (isset($input['_ipaddresses'])) {
                foreach ($input['_ipaddresses'] as $value) {
                    //if its an ipv4, find it's IPNetwork
                    if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        // get first IPNetwork because :
                        // see IPNetwork::searchNetworks
                        // By ordering on the netmask, we ensure that the first element is the nearest one (ie:
                        // the last should be 0.0.0.0/0.0.0.0 of x.y.z.a/255.255.255.255 regarding the interested element
                        $ipnetworks_ids = IPNetwork::searchNetworksContainingIP($value, $input['entities_id']);
                        if (count($ipnetworks_ids)) {
                            $input['ipnetworks_id'] = reset($ipnetworks_ids);
                        } else {
                            unset($input['ipnetworks_id']);
                        }
                    }
                }
            }
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        $input = $this->prepareIPNetworkFromInput($input);
        return parent::prepareInputForAdd($this->prepareLabelInput($input));
    }


    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareIPNetworkFromInput($input);
        return parent::prepareInputForUpdate($this->prepareLabelInput($input));
    }


    /**
     * Get all label IDs corresponding to given string label and FQDN ID
     *
     * @param $label           string   label to search for
     * @param $fqdns_id        integer  the id of the FQDN that owns the label
     * @param $wildcard_search boolean  true if we search with wildcard (false by default)
     *
     * @return array two arrays (NetworkName and NetworkAlias) of the IDs
     **/
    public static function getIDsByLabelAndFQDNID($label, $fqdns_id, $wildcard_search = false)
    {
        global $DB;

        $label = strtolower($label);
        if ($wildcard_search) {
            $count = 0;
            $label = str_replace('*', '%', $label, $count);
            if ($count == 0) {
                $label = '%' . $label . '%';
            }
            $relation = ['LIKE',  $label];
        } else {
            $relation = $label;
        }

        $IDs = [];
        foreach (
            ['NetworkName'  => 'glpi_networknames',
                'NetworkAlias' => 'glpi_networkaliases',
            ] as $class => $table
        ) {
            $criteria = [
                'SELECT' => 'id',
                'FROM'   => $table,
                'WHERE'  => ['name' => $relation],
            ];

            if (
                is_array($fqdns_id) && count($fqdns_id) > 0
                || is_int($fqdns_id) && $fqdns_id > 0
            ) {
                $criteria['WHERE']['fqdns_id'] = $fqdns_id;
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $element) {
                $IDs[$class][] = $element['id'];
            }
        }
        return $IDs;
    }


    /**
     * Look for "computer name" inside all databases
     *
     * @param string  $fqdn             name to search (for instance : forge.indepnet.net)
     * @param boolean $wildcard_search  true if we search with wildcard (false by default)
     *
     * @return array
     *    each value of the array (corresponding to one NetworkPort) is an array of the
     *    items from the master item to the NetworkPort
     **/
    public static function getItemsByFQDN($fqdn, $wildcard_search = false)
    {

        $FQNDs_with_Items = [];

        if (!$wildcard_search) {
            if (!FQDN::checkFQDN($fqdn)) {
                return [];
            }
        }

        $position = strpos($fqdn, ".");
        if ($position !== false) {
            $label    = strtolower(substr($fqdn, 0, $position));
            $fqdns_id = FQDN::getFQDNIDByFQDN(substr($fqdn, $position + 1), $wildcard_search);
        } else {
            $label    = $fqdn;
            $fqdns_id = -1;
        }

        foreach (self::getIDsByLabelAndFQDNID($label, $fqdns_id, $wildcard_search) as $class => $IDs) {
            if (
                ($FQDNlabel = getItemForItemtype($class))
                && ($FQDNlabel instanceof CommonDBChild)
            ) {
                foreach ($IDs as $ID) {
                    if ($FQDNlabel->getFromDB($ID)) {
                        $FQNDs_with_Items[] = array_merge(
                            array_reverse($FQDNlabel->recursivelyGetItems()),
                            [clone $FQDNlabel]
                        );
                    }
                }
            } else {
                trigger_error(
                    sprintf('%s is not a valid item type', $class),
                    E_USER_WARNING
                );
            }
        }

        return $FQNDs_with_Items;
    }


    /**
     * Get an Object ID by its name (only if one result is found in the entity)
     *
     * @param string  $value  the name
     * @param integer $entity the entity to look for
     *
     * @return array  an array containing the object ID
     *    or an empty array is no value of serverals ID where found
     **/
    public static function getUniqueItemByFQDN($value, $entity)
    {

        $labels_with_items = self::getItemsByFQDN($value);
        // Filter : Do not keep ip not linked to asset
        if (count($labels_with_items)) {
            foreach ($labels_with_items as $key => $tab) {
                if (
                    isset($tab[0])
                    && (($tab[0] instanceof NetworkName)
                    || ($tab[0] instanceof NetworkPort)
                    || $tab[0]->isDeleted()
                    || $tab[0]->isTemplate()
                    || ($tab[0]->getEntityID() != $entity))
                ) {
                    unset($labels_with_items[$key]);
                }
            }
        }

        if (count($labels_with_items)) {
            // Get the first item that is matching entity
            foreach ($labels_with_items as $items) {
                foreach ($items as $item) {
                    if ($item->getEntityID() == $entity) {
                        $result = ["id"       => $item->getID(),
                            "itemtype" => $item->getType(),
                        ];
                        unset($labels_with_items);
                        return $result;
                    }
                }
            }
        }

        return [];
    }
}
