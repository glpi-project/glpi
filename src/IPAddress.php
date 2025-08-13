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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;

use function Safe\preg_match;

/**
 * Represent an IPv4 or an IPv6 address. Both textual (ie. human readable)
 * and binary (ie. : used for request) are present
 * @since 0.84
 */


/** Class IPAddress : Represents an IPv4 or an IPv6 address. Both textual (ie. human readable)
 * and binary (ie. : used for SQL requests) are present inside the DB.
 * The class itself contains three protected attributes. If the address is valid, then, these
 * attributes are not empty.
 * This object is usefull for SQL research and binary<=>textual conversions.
 * @warning textual (ie. human readable) representation is not unique for IPv6 addresses :
 * 2001:db8:0:85a3\::ac1f:8001 = 2001:db8:0:85a3:0:0:ac1f:8001
 * @warning All textual representation of IPv6 addresses conforms to RFC 5952 : they are
 * automatically converted by IPAddress::setAddressFromString().
 * @since 0.84
 **/
class IPAddress extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype       = 'itemtype';
    public static $items_id       = 'items_id';
    public $dohistory             = false;

    public $history_blacklist     = ['binary_0', 'binary_1', 'binary_2', 'binary_3'];

    /**
     * Version of the address. Should be 4 or 6, or empty if not valid address
     * @var integer|string
     * @phpstan-var 4|6|''
     */
    protected $version = '';

    /**
     * Human-readable representation of the IP address.
     *
     * Examples:
     * - 192.168.0.0
     * - 2001:db8:0:85a3\::ac1f:8001
     * @var string
     */
    protected $textual = '';

    /**
     * The binary representation of the IP address.
     *
     * For IPv4 addresses, the first three bytes are set to [0, 0, 0xffff]
     * This is used for SQL requests.
     * @var string|int[]
     */
    protected $binary  = [0, 0, 0, 0];

    /**
     * @var bool Is the IPv4 address in dotted quoad format?
     */
    protected $isDottedQuoadFormat = false;

    public static $rightname  = 'internet';

    //////////////////////////////////////////////////////////////////////////////
    // CommonDBTM related methods
    //////////////////////////////////////////////////////////////////////////////


    /**
     * @param IPAddress|string|integer[] $ipaddress (default '')
     **/
    public function __construct($ipaddress = '')
    {

        // First, be sure that the parent is correctly initialised
        parent::__construct();

        // If $ipaddress if empty, then, empty address !
        if ($ipaddress != '') {
            // If $ipaddress if an IPAddress, then just clone it
            if ($ipaddress instanceof IPAddress) {
                $this->version = $ipaddress->version;
                $this->textual = $ipaddress->textual;
                $this->binary  = $ipaddress->binary;
                $this->fields  = $ipaddress->fields;
            } else {
                // Else, check a binary then a string
                if (!$this->setAddressFromBinary($ipaddress)) {
                    $this->setAddressFromString($ipaddress);
                }
            }
        }
    }

    public static function getTypeName($nb = 0)
    {
        return _n('IP address', 'IP addresses', $nb);
    }

    /**
     * @param $input
     **/
    public function prepareInput($input)
    {

        // If $input['name'] does not exists, then, don't check anything !
        if (isset($input['name'])) {
            // WARNING: we must in every case, because, sometimes, fields are partially feels

            // If previous value differs from current one, then check it !
            $this->setAddressFromString($input['name']);
            if (!$this->is_valid()) {
                if (isset($input['is_dynamic']) && $input['is_dynamic']) {
                    // We allow invalid IPs that are dynamics !
                    $input['version']  = 0;
                    $input['binary_0'] = 0;
                    $input['binary_1'] = 0;
                    $input['binary_2'] = 0;
                    $input['binary_3'] = 0;
                    return $input;
                }
                //TRANS: %s is the invalid address
                $msg = sprintf(__('%1$s: %2$s'), __('Invalid IP address'), $input['name']);
                Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                return false;
            }
        }
        if (isset($input['itemtype']) && isset($input['items_id'])) {
            $input['mainitemtype'] = 'NULL';
            $input['mainitems_id'] = 0;
            if ($input['itemtype'] == 'NetworkName') {
                $name = new NetworkName();
                if ($name->getFromDB($input['items_id'])) {
                    if ($port = getItemForItemtype($name->getField('itemtype'))) {
                        if ($port->getFromDB($name->getField('items_id'))) {
                            if (isset($port->fields['itemtype']) && isset($port->fields['items_id'])) {
                                $input['mainitemtype'] = $port->fields['itemtype'];
                                $input['mainitems_id'] = $port->fields['items_id'];
                            }
                        }
                    }
                }
            }
        }

        return array_merge($input, $this->setArrayFromAddress($input, "version", "name", "binary"));
    }

    public function prepareInputForAdd($input)
    {
        return parent::prepareInputForAdd($this->prepareInput($input));
    }

    public function prepareInputForUpdate($input)
    {
        return parent::prepareInputForUpdate($this->prepareInput($input));
    }

    public function post_addItem()
    {
        IPAddress_IPNetwork::addIPAddress($this);
        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        if (
            (isset($this->oldvalues['name']))
            || (isset($this->oldvalues['entities_id']))
        ) {
            $link = new IPAddress_IPNetwork();
            $link->cleanDBonItemDelete($this->getType(), $this->getID());
            $link->addIPAddress($this);
        }

        parent::post_updateItem($history);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                IPAddress_IPNetwork::class,
            ]
        );
    }

    public function post_getFromDB()
    {
        // Don't forget set local object from DB field
        $this->setAddressFromArray($this->fields, "version", "name", "binary");
    }

    public static function showForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $DB;

        if ($item::class !== IPNetwork::class) {
            // Not supported
            return;
        }

        $start       = (int) ($_GET["start"] ?? 0);
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");

        if ($sort === '') {
            $sort = 'ipaddress';
        }
        if ($order === '') {
            $order = 'ASC';
        }

        $orderby = match ($sort) {
            'ipaddress' => [
                "binary_0 $order",
                "binary_1 $order",
                "binary_2 $order",
                "binary_3 $order",
            ],
            'item' => [
                "item_type $order",
                "item_id $order",
            ],
            default => "$sort $order",
        };

        $criteria = self::getCriteriaLinkedToNetwork($item);
        $criteria['START'] = $start;
        $criteria['LIMIT'] = $_SESSION['glpilist_limit'];
        $criteria['ORDER'] = $orderby;

        $entries = [];
        $it = $DB->request($criteria);

        $item_objs = [];
        $networkport = new NetworkPort();
        $networkname = new NetworkName();
        foreach ($it as $data) {
            if (!array_key_exists($data['item_type'], $item_objs)) {
                $item_objs[$data['item_type']] = getItemForItemtype($data['item_type']);
            }
            $linked_item = $item_objs[$data['item_type']];
            $linked_item->getFromDB($data['item_id']);
            $networkport->getFromDB($data['port_id']);
            $networkname->getFromDB($data['name_id']);

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'ipaddress' => $data['ip'],
                'item' => $linked_item->getLink(),
                'port_id' => $networkport->getLink(),
                'name_id' => $networkname->getLink(),
                'entity' => $data['entity'],
                'is_dynamic' => Dropdown::getYesNo($data['is_dynamic']),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'sort' => $sort,
            'order' => $order,
            'columns' => [
                'ipaddress' => self::getTypeName(1),
                'item' => _n('Item', 'Items', 1),
                'port_id' => NetworkPort::getTypeName(Session::getPluralNumber()),
                'name_id' => NetworkName::getTypeName(1),
                'entity' => Entity::getTypeName(1),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'item' => 'raw_html',
                'port_id' => 'raw_html',
                'name_id' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case 'IPNetwork':
                self::showForItem($item, $withtemplate);
                break;
        }
        return true;
    }

    /**
     * @param $item      CommonDBTM object
     **/
    public static function countForItem(CommonDBTM $item)
    {
        global $DB;

        switch ($item->getType()) {
            case 'IPNetwork':
                $result = $DB->request([
                    'COUNT'  => 'cpt',
                    'FROM'   => 'glpi_ipaddresses_ipnetworks',
                    'WHERE'  => [
                        'ipnetworks_id'   => $item->getID(),
                    ],
                ])->current();
                return $result['cpt'];
        }
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            ($item instanceof CommonDBTM)
            && $item->getID()
            && $item->can($item->getField('id'), READ)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    //////////////////////////////////////////////////////////////////////////////
    // IP address specific methods (check, transformation ...)
    //////////////////////////////////////////////////////////////////////////////


    /**
     * Disable the address
     **/
    public function disableAddress()
    {
        $this->version = '';
        $this->textual = '';
        $this->binary  = '';
    }

    /**
     * \brief Fill an array from the the local address object
     * Fill an array from the the local address object. Usefull for feeding $input variable for
     * preparing input to alter database.
     * If the field name is empty, then, the field is not set
     * If the object is not valid, then, version = 0, textual = "" and binary = (0, 0, 0, 0)
     *
     * @param array  $array         the array to Fill
     * @param string $versionField  the name of the key inside $array that contains de IP version number
     * @param string $textualField  the name of the key inside $array that contains de textual version
     * @param string $binaryField   the name of the key inside $array that contains de binary.
     *                              Each element of the array is post-fixed by _i, with i the index
     *
     * @return array the array altered
     **/
    public function setArrayFromAddress(array $array, $versionField, $textualField, $binaryField)
    {
        if (!empty($versionField)) {
            $version = $this->getVersion();
            if ($version !== false) {
                $array[$versionField] = $version;
            } else {
                $array[$versionField] = "0";
            }
        }

        if (!empty($textualField)) {
            $textual = $this->getTextual();
            if ($textual !== false) {
                $array[$textualField] = $textual;
            } else {
                $array[$textualField] = "";
            }
        }

        if (!empty($binaryField)) {
            $binary = $this->getBinary();
            for ($i = 0; $i < 4; ++$i) {
                if ($binary !== false) {
                    $array[$binaryField . "_" . $i] = $binary[$i];
                } else {
                    $array[$binaryField . "_" . $i] = '0';
                }
            }
        }
        return $array;
    }

    /**
     * \brief Fill the local address object from an array
     * Fill the local address object from an array. Usefull for reading $input
     *
     * @param array  $array         the array to Fill
     * @param string $versionField  the name of the key inside $array that contains de IP version number
     * @param string $textualField  the name of the key inside $array that contains de textual version
     * @param string $binaryField   the name of the key inside $array that contains de binary.
     *                              Each element of the array is post-fixed by _i, with i the index
     *
     * If the field name is empty, then, the field is not set
     *
     * @return boolean successfully defined
     **/
    public function setAddressFromArray(array $array, $versionField, $textualField, $binaryField)
    {

        // First, we empty the fields to notify that this address is not valid
        $this->disableAddress();

        if (!isset($array[$versionField])) {
            return false;
        }
        if (!isset($array[$textualField])) {
            return false;
        }
        if (
            (!isset($array[$binaryField . "_0"]) || !is_numeric($array[$binaryField . "_0"]))
            || (!isset($array[$binaryField . "_1"]) || !is_numeric($array[$binaryField . "_0"]))
            || (!isset($array[$binaryField . "_2"]) || !is_numeric($array[$binaryField . "_0"]))
            || (!isset($array[$binaryField . "_3"]) || !is_numeric($array[$binaryField . "_0"]))
        ) {
            return false;
        }

        $this->version    = $array[$versionField];
        $this->textual    = $array[$textualField];
        $this->binary     = [];
        $this->binary[0]  = ($array[$binaryField . "_0"] + 0);
        $this->binary[1]  = ($array[$binaryField . "_1"] + 0);
        $this->binary[2]  = ($array[$binaryField . "_2"] + 0);
        $this->binary[3]  = ($array[$binaryField . "_3"] + 0);
        return true;
    }

    /**
     * Check address validity
     **/
    public function is_valid()
    {
        return (($this->version != '') && ($this->textual != '') && ($this->binary != ''));
    }

    public function getVersion()
    {
        return $this->version !== '' ? $this->version : false;
    }

    public function is_ipv4()
    {
        return ($this->getVersion() == 4);
    }

    public function is_ipv6()
    {
        return ($this->getVersion() == 6);
    }

    public function getTextual()
    {
        return $this->textual !== '' ? $this->textual : false;
    }

    public function getBinary()
    {
        return $this->binary !== '' ? $this->binary : false;
    }

    /**
     * Transform an IPv4 address to IPv6
     *
     * @param integer|integer[] $address (bytes[4] or bytes) the address to transform.
     *
     * @return integer[]|false IPv6 mapped address
     **/
    public static function getIPv4ToIPv6Address($address)
    {
        if (is_numeric($address)) {
            return [0, 0, 0xffff, $address];
        }
        if ((is_array($address)) && (count($address) == 4)) {
            return self::getIPv4ToIPv6Address($address[3]);
        }
        return false;
    }

    /**
     * Check an address to see if it is IPv4 mapped to IPv6 address
     *
     * @param integer[] $address (bytes[4]) the address to check
     *
     * @return boolean
     **/
    public static function isIPv4MappedToIPv6Address($address)
    {

        if (is_array($address) && (count($address) == 4)) {
            if (($address[0] == 0) && ($address[1] == 0) && ($address[2] == 0xffff)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Replace textual representation by its canonical form.
     *
     * @return void
     **/
    public function canonicalizeTextual()
    {
        $this->setAddressFromBinary($this->getBinary());
    }

    /**
     * \brief define an address from a string
     * Convert a textual address (string) to binary one. Opposite function that
     * setAddressFromBinary(). If item is valid ($itemtype not empty and $items_id > 0) then first
     * try to find it inside the database and load it from database.
     * \warning The resulting binary form is created inside the current object
     *
     * @param string  $address   textual (ie. human readable) address
     * @param string  $itemtype  type of the item this address has to be attached (default '')
     * @param integer $items_id  id of the item this address has to be attached (default -1)
     *
     * @return boolean address is valid
     **/
    public function setAddressFromString($address, $itemtype = "", $items_id = -1)
    {
        global $DB;

        $this->disableAddress();

        if (!is_string($address)) {
            return false;
        }

        $address = trim($address);

        if (empty($address)) {
            return false;
        }

        if (
            !empty($itemtype)
            && ($items_id > 0)
        ) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => static::getTable(),
                'WHERE'  => [
                    'items_id'  => $items_id,
                    'itemtype'  => $itemtype,
                    'name'      => $address,
                ],
            ]);

            if (count($iterator) === 1) {
                $line = $iterator->current();
                if ($this->getFromDB($line["id"])) {
                    return true;
                }
            }
        }

        //if it IPV4 dotted-quoad format ::ffff:192.168.1.1
        //remove ::ffff: to manage only IPV4 part
        //keep in memory that have a special format
        $this->isDottedQuoadFormat = false;
        if (preg_match("/^::ffff:(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/", $address, $regs)) {
            $address = substr($address, 7);
            $this->isDottedQuoadFormat = true;
        }

        $binary = null;
        $singletons = explode(".", $address);
        // First, check to see if it is an IPv4 address
        if (count($singletons) === 4) {
            $binary = 0;
            foreach ($singletons as $singleton) {
                if (!is_numeric($singleton)) {
                    return false;
                }
                $singleton = (int) $singleton;
                if (($singleton < 0) || ($singleton > 255)) {
                    return false;
                }
                $binary *= 256;
                $binary += (int) $singleton;
            }
            $binary  = self::getIPv4ToIPv6Address($binary);
        }

        // Else, it should be an IPv6 address
        $singletons = explode(":", $address);
        // Minimum IPv6 address is "::". So, we check that there is at least 3 singletons in the array
        // And no more than 8 singletons
        if ((count($singletons) >= 3) && (count($singletons) <= 8)) {
            $empty_count = 0;
            foreach ($singletons as $singleton) {
                $singleton = trim($singleton);
                // First, we check that each singleton is 4 hexadecimal !
                if (!preg_match("/^[0-9A-Fa-f]{0,4}$/", $singleton, $regs)) {
                    return false;
                }
                if ($singleton === '') {
                    $empty_count++;
                }
            }

            // EXTREMITY CHECKS :
            // If it starts with colon : the second one must be empty too (ie.: :2001 is not valid)
            $start_with_empty = ($singletons[0] === '');
            if (($start_with_empty) && ($singletons[1] !== '')) {
                return false;
            }

            // If it ends with colon : the previous one must be empty too (ie.: 2001: is not valid)
            $end_with_empty = ($singletons[count($singletons) - 1] === '');
            if (($end_with_empty) && ($singletons[count($singletons) - 2] !== '')) {
                return false;
            }
            // END OF EXTREMITY CHECKS

            // The number of empty singletons depends on the type of contraction
            switch ($empty_count) {
                case 0: // No empty singleton => no contraction at all
                    // Thus, its side must be 8 !
                    if (count($singletons) != 8) {
                        return false;
                    }
                    break;

                case 1:
                    // One empty singleton : must be in the middle, otherwise EXTREMITY CHECKS
                    // would return false
                    break;

                case 2: // If there is two empty singletons then it must be at the beginning or the end
                    if (!($start_with_empty xor $end_with_empty)) {
                        return false;
                    }
                    // Thus remove one of both empty singletons.
                    if ($start_with_empty) {
                        unset($singletons[0]);
                    } else { // $end_with_empty == true
                        unset($singletons[count($singletons) - 1]);
                    }
                    break;

                case 3: // Only '::' allows three empty singletons ('::x::' = four empty singletons)
                    if (!($start_with_empty && $end_with_empty)) {
                        return false;
                    }
                    // Middle value must be '' otherwise EXTREMITY CHECKS returned an error
                    if (count($singletons) != 3) {
                        return false;
                    }
                    $singletons = [''];
                    break;

                default:
                    return false;
            }

            // Here, we are sure that $singletons are valids and only contains 1 empty singleton that
            // will be convert to as many '0' as necessary to reach 8 singletons

            $numberEmpty = 9 - count($singletons); // = 8 - (count($singletons) - 1)

            $epanded = [];
            foreach ($singletons as $singleton) {
                if ($singleton === '') {
                    $epanded = array_merge($epanded, array_fill(0, $numberEmpty, 0));
                } else {
                    $epanded[] = hexdec($singleton);
                }
            }

            $binary = [];
            for ($i = 0; $i < 4; $i++) {
                $binary[$i] = $epanded[2 * $i + 0] * 65536 + $epanded[2 * $i + 1];
            }
        }

        // $binary is an array that is only defined for IPv4 or IPv6 address
        if ($binary !== null && $binary !== false) {
            // Calling setAddressFromBinary is usefull to recheck one more time inside
            // glpi_ipaddresses table and to make canonical textual version
            return $this->setAddressFromBinary($binary, $itemtype, $items_id);
        }

        // Else, it is not IPv4 nor IPv6 address
        return false;
    }

    /**
     * \brief define an address from a binary
     * Convert a binary address (bytes[4]) to textual one. Opposite function that
     * setAddressFromString(). If item is valid ($itemtype not empty and $items_id > 0) then first
     * try to find it inside the database and load it from database. textual version is condensed
     * one (ie : 2001:db8:0:85a3\::ac1f:8001 rather than 2001:0db8:0000:85a3:0000:0000:ac1f:8001)
     * \warning The resulting binary form is created inside the current object
     *
     * @param integer[] $address   (bytes[4]) binary (ie. SQL requests) address
     * @param string    $itemtype  type of the item this address has to be attached (default '')
     * @param integer   $items_id  id of the item this address has to be attached (default -1)
     *
     * @return boolean address is valid
     **/
    public function setAddressFromBinary($address, $itemtype = "", $items_id = -1)
    {
        global $DB;

        $this->disableAddress();
        if ((!is_array($address)) || (count($address) != 4)) {
            return false;
        }
        if (
            !empty($itemtype)
            && ($items_id > 0)
        ) {
            $where = [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
            ];

            for ($i = 0; $i < 4; ++$i) {
                $where["binary_$i"] = $address[$i];
            }

            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => static::getTable(),
                'WHERE'  => $where,
            ]);

            if (count($iterator) === 1) {
                $line = $iterator->current();
                if ($this->getFromDB($line["id"])) {
                    return true;
                }
            }
        }
        $binary      = [];
        $textual     = [];
        $currentNull = "";
        foreach ($address as $singleton) {
            if (!is_numeric($singleton)) {
                return false;
            }
            $singleton = (int) $singleton;
            $binary[]  = $singleton;
            $singleton = str_pad(dechex($singleton), 8, "0", STR_PAD_LEFT);
            $elt       = ltrim(substr($singleton, 0, 4), "0");
            if (empty($elt)) {
                $textual[]    = "0";
                $currentNull .= "1";
            } else {
                $currentNull .= "0";
                $textual[]    = $elt;
            }
            $elt = ltrim(substr($singleton, 4, 4), "0");
            if (empty($elt)) {
                $textual[]    = "0";
                $currentNull .= "1";
            } else {
                $currentNull .= "0";
                $textual[]    = $elt;
            }
        }

        if (count($binary) === 4) {
            if (self::isIPv4MappedToIPv6Address($binary)) {
                $this->version = 4;
            } else {
                $this->version = 6;
            }
        } else {
            return false;
        }

        $this->binary = $binary;
        if ($this->getVersion() == 4) {
            $hexValue = str_pad($textual[6], 4, "0", STR_PAD_LEFT) . str_pad(
                $textual[7],
                4,
                "0",
                STR_PAD_LEFT
            );
            $textual  = [];
            for ($i = 0; $i < 4; $i++) {
                $textual[] = hexdec($hexValue[2 * $i + 0] . $hexValue[2 * $i + 1]);
            }
            $textual = implode('.', $textual);
        } else {
            foreach (["11111111", "1111111", "111111", "11111", "1111", "111", "11"] as $elt) {
                $pos = strpos($currentNull, $elt);
                if ($pos !== false) {
                    $first = array_slice($textual, 0, $pos);
                    if (count($first) === 0) {
                        $first = [""];
                    }
                    $second = array_slice($textual, $pos + strlen($elt));
                    if (count($second) === 0) {
                        $second = [""];
                    }
                    $textual = array_merge($first, [""], $second);
                    break;
                }
            }
            $textual = implode(':', $textual);
        }

        $prefix = "";

        //If it is a special format, add prefix previously removed (to manage IPV4 part)
        if ($this->isDottedQuoadFormat) {
            $prefix = "::ffff:";
        }

        $this->textual = $prefix . $textual;
        return true;
    }

    /**
     * \brief add value to the address for iterator on addresses
     *
     * @param (integer|float)[] $address   (in and out) the address to increment or decrement
     * @param integer   $value     the value to add or remove. Must be betwwen -0xffffffff and +0xffffffff
     *
     * @return boolean true if the increment is valid
     **/
    public static function addValueToAddress(&$address, $value)
    {

        if (
            !is_array($address)
            || (count($address) != 4)
            || !is_numeric($value)
            || ($value < -0xffffffff)
            || ($value > 0xffffffff)
        ) {
            return false;
        }

        for ($i = 3; $i >= 0; --$i) {
            $address[$i] += $value;
            if ($address[$i] < 0) {
                $address[$i] += (0x80000000 * 2);
                $value        = -1; // For next value for right to left ...
            } elseif ($address[$i] > 0xffffffff) {
                $address[$i] -= (0x80000000 * 2);
                $value        = 1; // For next value for right to left ...
            } else {
                break;
            }
        }

        return true;
    }

    /**
     * \brief get absolute value of an integer
     * Convert a negative integer to positiv float. That is usefull as integer, in PHP are signed 32
     * bits values. As such, they are limited from +2 147 483 647 to ???2 147 483 648. Thus, when
     * working on integer with bit-wise boolean operations (&, |, ^, ~), the sign of the operand
     * remain inside the result. That make problem as IP address are only positiv ones.
     *
     * @param integer $value the integer that we want the absolute value
     *
     * @return float value that is the absolute of $value
     *
     **/
    public static function convertNegativeIntegerToPositiveFloat($value)
    {
        if ((int) $value && ($value < 0)) {
            $value = (float) $value + (float) 0x80000000 * 2;
        }
        return $value;
    }

    /**
     * Search IP Addresses
     *
     * @param string $IPaddress  the address to search
     *
     * @return array  each value of the array (corresponding to one IPAddress) is an array of the
     *                items from the master item to the IPAddress
     **/
    public static function getItemsByIPAddress($IPaddress)
    {
        global $DB;

        // We must resolv binary address :
        //    1??) we don't know if the IP address is valid
        //    2??) we don't know its version
        //    3??) binary request is more efficient than textual one (polymorphism of IPv6 addresses)
        $address = new self();

        if (!$address->setAddressFromString($IPaddress)) {
            return [];
        }

        $criteria = [
            'SELECT' => 'gip.id',
            'FROM'   => 'glpi_ipaddresses AS gip',
            'WHERE'  => ['gip.version' => $address->version],
        ];
        $startIndex = (($address->version == 4) ? 3 : 1);
        $binaryIP = $address->getBinary();
        for ($i = $startIndex; $i < 4; ++$i) {
            $criteria['WHERE']["gip.binary_$i"] = $binaryIP[$i];
        }
        $iterator = $DB->request($criteria);
        $addressesWithItems = [];
        foreach ($iterator as $result) {
            if ($address->getFromDB($result['id'])) {
                $addressesWithItems[] = array_merge(
                    array_reverse($address->recursivelyGetItems()),
                    [clone $address]
                );
            }
        }
        return $addressesWithItems;
    }

    /**
     * Get an Object ID by its IP address (only if one result is found in the entity)
     *
     * @param string  $value   the ip address
     * @param integer $entity  the entity to look for
     *
     * @return array containing the object ID
     *         or an empty array is no value of serverals ID where found
     **/
    public static function getUniqueItemByIPAddress($value, $entity)
    {

        $addressesWithItems = self::getItemsByIPAddress($value);

        // Filter : Do not keep ip not linked to asset
        if (count($addressesWithItems)) {
            foreach ($addressesWithItems as $key => $tab) {
                if (
                    isset($tab[0])
                    && (($tab[0] instanceof NetworkName)
                    || ($tab[0] instanceof IPAddress)
                    || ($tab[0] instanceof NetworkPort)
                    || $tab[0]->isDeleted()
                    || $tab[0]->isTemplate()
                    || ($tab[0]->getEntityID() != $entity))
                ) {
                    unset($addressesWithItems[$key]);
                }
            }
        }

        if (count($addressesWithItems)) {
            // Get the first item that is matching entity
            foreach ($addressesWithItems as $items) {
                foreach ($items as $item) {
                    if ($item->getEntityID() == $entity) {
                        $result = ["id"       => $item->getID(),
                            "itemtype" => $item->getType(),
                        ];
                        unset($addressesWithItems);
                        return $result;
                    }
                }
            }
        }
        return [];
    }

    /**
     * Check if two addresses are equals
     *
     * @param IPAddress|string|integer[] $ipaddress  the ip address to check with this
     *
     * @return boolean true if and only if both addresses are binary equals.
     **/
    public function equals($ipaddress)
    {

        // To normalise the address, just make new one
        $ipaddress = new self($ipaddress);

        if (
            !is_array($this->binary)
            || (count($this->binary) !== 4)
            || (count($ipaddress->binary) !== 4)
            || ($this->version != $ipaddress->version)
        ) {
            return false;
        }

        for ($index = 0; $index < 4; $index++) {
            if ($this->binary[$index] != $ipaddress->binary[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $itemtype
     * @param $base                  HTMLTableBase object
     * @param $super                 HTMLTableSuperHeader object (default NULL)
     * @param $father                HTMLTableHeader object (default NULL)
     * @param $options      array
     **/
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column_name = self::class;

        if ($itemtype == 'IPNetwork') {
            $base->addHeader('Item', _sn('Item', 'Items', 1), $super, $father);
            $base->addHeader('NetworkPort', htmlescape(NetworkPort::getTypeName(0)), $super, $father);
            $base->addHeader('NetworkName', htmlescape(NetworkName::getTypeName(1)), $super, $father);
            $base->addHeader('Entity', htmlescape(Entity::getTypeName(1)), $super, $father);
        } else {
            if (isset($options['dont_display'][$column_name])) {
                return;
            }

            $content = htmlescape(self::getTypeName());
            if (isset($options['column_links'][$column_name])) {
                $content = "<a href='" . htmlescape($options['column_links'][$column_name]) . "'>$content</a>";
            }

            $father = $base->addHeader($column_name, $content, $super, $father);

            if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
                $father = $base->addHeader(
                    $column_name . '_dynamic',
                    __s('Automatic inventory'),
                    $super,
                    $father
                );
            }

            IPNetwork::getHTMLTableHeader(self::class, $base, $super, $father, $options);
        }
    }

    /**
     * Get the SQL criteria required to show a list of IP Addresses linked to the given IPNetwork
     *
     * @param IPNetwork $network
     * @return array
     */
    private static function getCriteriaLinkedToNetwork(IPNetwork $network): array
    {
        global $CFG_GLPI, $DB;

        $queries = [];
        $main_criteria = [
            'SELECT'       => [
                'ADDR.binary_0 AS binary_0',
                'ADDR.binary_1 AS binary_1',
                'ADDR.binary_2 AS binary_2',
                'ADDR.binary_3 AS binary_3',
                'ADDR.name AS ip',
                'ADDR.id AS id',
                'ADDR.itemtype AS addr_item_type',
                'ADDR.items_id AS addr_item_id',
                'ADDR.is_dynamic AS is_dynamic',
                'glpi_entities.completename AS entity',
            ],
            'FROM'         => 'glpi_ipaddresses_ipnetworks AS LINK',
            'INNER JOIN'   => [
                'glpi_ipaddresses AS ADDR' => [
                    'ON' => [
                        'ADDR'   => 'id',
                        'LINK'   => 'ipaddresses_id', [
                            'AND' => [
                                'ADDR.itemtype' => 'NetworkName',
                                'ADDR.is_deleted' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'LEFT JOIN'    => [
                'glpi_entities'             => [
                    'ON' => [
                        'ADDR'            => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
            ],
            'WHERE'        => [
                'LINK.ipnetworks_id' => $network->getID(),
            ],
        ];

        foreach ($CFG_GLPI["networkport_types"] as $itemtype) {
            $table = getTableForItemType($itemtype);
            $criteria = $main_criteria;
            $criteria['SELECT'] = array_merge($criteria['SELECT'], [
                'NAME.id AS name_id',
                'PORT.id AS port_id',
                'ITEM.id AS item_id',
                new QueryExpression($DB::quoteValue($itemtype), 'item_type'),
            ]);
            $criteria['INNER JOIN'] += [
                'glpi_networknames AS NAME'   => [
                    'ON' => [
                        'NAME'   => 'id',
                        'ADDR'   => 'items_id', [
                            'AND' => [
                                'NAME.itemtype' => 'NetworkPort',
                            ],
                        ],
                    ],
                ],
                'glpi_networkports AS PORT'   => [
                    'ON' => [
                        'NAME'   => 'items_id',
                        'PORT'   => 'id', [
                            'AND' => [
                                'PORT.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ],
                "$table AS ITEM"              => [
                    'ON' => [
                        'ITEM'   => 'id',
                        'PORT'   => 'items_id',
                    ],
                ],
            ];
            $criteria['WHERE'] = $itemtype::getSystemSQLCriteria('ITEM');
            $queries[] = $criteria;
        }

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            'NAME.id AS name_id',
            'PORT.id AS port_id',
            new QueryExpression('NULL', 'item_id'),
            new QueryExpression('NULL', 'item_type'),
        ]);
        $criteria['INNER JOIN'] += [
            'glpi_networknames AS NAME'   => [
                'ON' => [
                    'NAME'   => 'id',
                    'ADDR'   => 'items_id', [
                        'AND' => [
                            'NAME.itemtype' => 'NetworkPort',
                        ],
                    ],
                ],
            ],
            'glpi_networkports AS PORT'   => [
                'ON' => [
                    'NAME'   => 'items_id',
                    'PORT'   => 'id', [
                        'AND' => [
                            'NOT' => [
                                'PORT.itemtype' => $CFG_GLPI['networkport_types'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $queries[] = $criteria;

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            'NAME.id AS name_id',
            new QueryExpression('NULL', 'port_id'),
            new QueryExpression('NULL', 'item_id'),
            new QueryExpression('NULL', 'item_type'),
        ]);
        $criteria['INNER JOIN'] += [
            'glpi_networknames AS NAME'   => [
                'ON' => [
                    'NAME'   => 'id',
                    'ADDR'   => 'items_id', [
                        'AND' => [
                            'NAME.itemtype' => ['!=', 'NetworkPort'],
                        ],
                    ],
                ],
            ],
        ];
        $queries[] = $criteria;

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            new QueryExpression('NULL', 'name_id'),
            new QueryExpression('NULL', 'port_id'),
            new QueryExpression('NULL', 'item_id'),
            new QueryExpression('NULL', 'item_type'),
        ]);
        $criteria['INNER JOIN']['glpi_ipaddresses AS ADDR']['ON'][0]['AND']['ADDR.itemtype'] = ['!=', 'NetworkName'];
        $queries[] = $criteria;

        $union = new QueryUnion($queries);
        return [
            'FROM'   => $union,
        ];
    }

    /**
     * @param HTMLTableRow $row
     * @param CommonDBTM $item
     * @param HTMLTableCell $father
     * @param array $options
     **/
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        global $DB;

        if ($item instanceof IPNetwork) {
            $criteria = self::getCriteriaLinkedToNetwork($item);

            if (
                ($options['order'] == 'ip')
                || ($options['order'] == 'itemtype')
            ) {
                $criteria['ORDERBY'] = [
                    'binary_0',
                    'binary_1',
                    'binary_2',
                    'binary_3',
                ];
            }

            if (isset($options['SQL_options'])) {
                $criteria = array_merge($criteria, $options['SQL_options']);
            }
            $iterator = $DB->request($criteria);

            $canedit              = (isset($options['canedit']) && $options['canedit']);
            $options['createRow'] = false;
            $address              = new self();

            $ipaddress   = new self();
            $networkname = new NetworkName();
            $networkport = new NetworkPort();

            $item = null;
            foreach ($iterator as $line) {
                unset($row);

                if (
                    ($options['order'] == 'itemtype')
                    && !empty($line['item_type'])
                ) {
                    $row = $options['group_' . $line['item_type']]->createRow();
                }

                if (!isset($row)) {
                    $row = $options['group_None']->createRow();
                }

                $ip_header  = $row->getGroup()->getSuperHeaderByName('IP Address');
                $item_header = $row->getGroup()->getHeaderByName('Item', 'Item');
                $port_header = $row->getGroup()->getHeaderByName('Item', 'NetworkPort');
                $name_header = $row->getGroup()->getHeaderByName('Item', 'NetworkName');
                $entity_header = $row->getGroup()->getHeaderByName('Item', 'Entity');

                $row->addCell($ip_header, htmlescape($line['ip']), $father);

                if (!empty($line['name_id'])) {
                    $networkname->getFromDB($line['name_id']);
                    $row->addCell($name_header, $networkname->getLink(), $father);

                    if (!empty($line['port_id'])) {
                        $networkport->getFromDB($line['port_id']);
                        $row->addCell($port_header, $networkport->getLink(), $father);

                        if ((!empty($line['item_id'])) && (!empty($line['item_type']))) {
                            $itemtype = $line['item_type'];
                            $item     = getItemForItemtype($itemtype);
                            $item->getFromDB($line['item_id']);
                            $row->addCell($item_header, $item->getLink(), $father);
                        }
                    }
                    $row->addCell($entity_header, htmlescape($line['entity']), $father);
                } elseif ((!empty($line['addr_item_id'])) && (!empty($line['addr_item_type']))) {
                    $itemtype = $line['addr_item_type'];
                    $item     = getItemForItemtype($itemtype);
                    $item->getFromDB($line['addr_item_id']);
                    if ($item instanceof CommonDBChild) {
                        $items    = $item->recursivelyGetItems();
                        $elements = [$item->getLink()];
                        foreach ($items as $item_) {
                            $elements[] = $item_->getLink();
                        }
                        $row->addCell($item_header, implode(' &gt; ', $elements), $father);
                    } else {
                        $row->addCell($item_header, $item->getLink(), $father);
                    }
                    $row->addCell($entity_header, htmlescape($line['entity']), $father);
                }
            }
        } else {
            if (isset($options['dont_display']['IPAddress'])) {
                return;
            }

            $header = $row->getGroup()->getHeaderByName('Internet', self::class);
            if (!$header) {
                return;
            }

            if (empty($item)) {
                if (empty($father)) {
                    return;
                }
                $item = $father->getItem();
            }

            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'items_id'     => $item->getID(),
                    'itemtype'     => $item->getType(),
                    'is_deleted'   => 0,
                ],
            ]);

            $canedit              = (isset($options['canedit']) && $options['canedit']);
            $createRow            = (isset($options['createRow']) && $options['createRow']);
            $options['createRow'] = false;
            $address              = new self();

            foreach ($iterator as $ipaddress) {
                if ($address->getFromDB($ipaddress['id'])) {
                    if ($createRow) {
                        $row = $row->createRow();
                    }

                    $this_cell = $row->addCell($header, htmlescape($address->fields['name']), $father);

                    if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
                        $dyn_header = $row->getGroup()->getHeaderByName('Internet', self::class . '_dynamic');
                        $this_cell  = $row->addCell(
                            $dyn_header,
                            htmlescape(Dropdown::getYesNo($address->fields['is_dynamic'])),
                            $this_cell
                        );
                    }

                    IPNetwork::getHTMLTableCellsForItem($row, $address, $this_cell, $options);
                }
            }
        }
    }
}
