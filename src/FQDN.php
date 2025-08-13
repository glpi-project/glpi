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

/**
 * Fully Qualified Domain Name
 * @since 0.84
 */
class FQDN extends CommonDropdown
{
    public $dohistory = true;

    public static $rightname = 'internet';

    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Internet domain', 'Internet domains', $nb);
    }


    public function getAdditionalFields()
    {

        return [['name'    => 'fqdn',
            'label'   => __('FQDN'),
            'type'    => 'text',
            'comment'
                          => __('Fully Qualified Domain Name. Use the classical notation (labels separated by dots). For example: indepnet.net'),
            'list'    => true,
        ],
        ];
    }


    /**
     * \brief Prepare the input before adding or updating
     * Checking suppose that each FQDN is compose of dot separated array of labels and its unique
     * \see (FQDNLabel)
     *
     * @param array $input fields of the record to check
     *
     * @return boolean|array  false or fields checked and updated (lowercase for the fqdn field)
     **/
    public function prepareInput($input)
    {

        if (
            isset($input['fqdn'])
            || $this->isNewID($this->getID())
        ) {
            // Check that FQDN is not empty
            if (empty($input['fqdn'])) {
                Session::addMessageAfterRedirect(__s('FQDN must not be empty'), false, ERROR);
                return false;
            }

            // Transform it to lower case
            $input["fqdn"] = strtolower($input['fqdn']);

            // Then check its validity
            if (!self::checkFQDN($input["fqdn"])) {
                Session::addMessageAfterRedirect(__s('FQDN is not valid'), false, ERROR);
                return false;
            }
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        return $this->prepareInput(parent::prepareInputForAdd($input));
    }


    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput(parent::prepareInputForUpdate($input));
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(NetworkName::class, $ong, $options);
        $this->addStandardTab(NetworkAlias::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    /**
     * @return string the FQDN of the element, or "" if invalid FQDN
     **/
    public function getFQDN()
    {

        if ($this->can($this->getID(), READ)) {
            return $this->fields["fqdn"];
        }
        return "";
    }


    /**
     * Search FQDN id from string FDQDN
     *
     * @param string  $fqdn             value of the fdqn (for instance : indeptnet.net)
     * @param boolean $wildcard_search  true if we search with wildcard (false by default)
     *
     * @return integer|integer[]
     *    if $wildcard_search == false : the id of the fqdn, -1 if not found or several answers
     *    if $wildcard_search == true : an array of the id of the fqdn
     **/
    public static function getFQDNIDByFQDN($fqdn, $wildcard_search = false)
    {
        global $DB;

        if (empty($fqdn)) {
            return 0;
        }

        $fqdn = strtolower($fqdn);
        if ($wildcard_search) {
            $count = 0;
            $fqdn  = str_replace('*', '%', $fqdn, $count);
            if ($count == 0) {
                $fqdn = '%' . $fqdn . '%';
            }
            $relation = ['LIKE', $fqdn];
        } else {
            $relation = $fqdn;
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['fqdn' => $relation],
        ]);

        $fqdns_id_list = [];
        foreach ($iterator as $line) {
            $fqdns_id_list[] = $line['id'];
        }

        if (!$wildcard_search) {
            if (count($fqdns_id_list) != 1) {
                return -1;
            }
            return $fqdns_id_list[0];
        }

        return $fqdns_id_list;
    }


    /**
     * @param integer $ID  id of the FQDN
     *
     * @return string  the FQDN of the element, or "" if invalid FQDN
     **/
    public static function getFQDNFromID($ID)
    {

        $thisDomain = new self();
        if ($thisDomain->getFromDB($ID)) {
            return $thisDomain->getFQDN();
        }
        return "";
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'fqdn',
            'name'               => __('FQDN'),
            'datatype'           => 'string',
        ];

        return $tab;
    }


    /**
     * Check FQDN Validity
     *
     * @param string $fqdn  the FQDN to check
     *
     * @return boolean  true if the FQDN is valid
     **/
    public static function checkFQDN($fqdn)
    {

        // The FQDN must be compose of several labels separated by dots '.'
        $labels = explode(".", $fqdn);
        foreach ($labels as $label) {
            if (($label == "") || (!FQDNLabel::checkFQDNLabel($label))) {
                return false;
            }
        }
        return true;
    }

    public static function getIcon()
    {
        return "ti ti-world";
    }
}
