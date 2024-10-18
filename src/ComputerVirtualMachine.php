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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;

/**
 * Virtual machine management
 */


/**
 * ComputerVirtualMachine Class
 *
 * Class to manage virtual machines
 **/
class ComputerVirtualMachine extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype = 'Computer';
    public static $items_id = 'computers_id';
    public $dohistory       = true;


    public static function getTypeName($nb = 0)
    {
        return __('Virtualization');
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            !$withtemplate
            && $item instanceof Computer
            && Computer::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    ['computers_id' => $item->getID(), 'is_deleted' => 0 ]
                );
            }
            return self::createTabEntry(self::getTypeName(), $nb);
        }
        return '';
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Lock', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForVirtualMachine($item);
        self::showForComputer($item);
        return true;
    }


    public function post_getEmpty()
    {

        $this->fields["vcpu"] = '0';
        $this->fields["ram"]  = '0';
    }


    /**
     * Display form
     *
     * @param integer $ID
     * @param array   $options
     *
     * @return boolean TRUE if form is ok
     **/
    public function showForm($ID, array $options = [])
    {
        if (!Session::haveRight("computer", UPDATE)) {
            return false;
        }

        $comp = new Computer();

        if ($ID > 0) {
            $this->check($ID, READ);
            $comp->getFromDB($this->fields['computers_id']);
        } else {
           // Create item
            $this->check(-1, CREATE, $options);
            $comp->getFromDB($options['computers_id']);
        }

        $linked_computer = "";
        if ($link_computer = self::findVirtualMachine($this->fields)) {
            $computer = new Computer();
            if ($computer->getFromDB($link_computer)) {
                $linked_computer = $computer->getLink(['comments' => true]);
            }
        }

        $options['canedit'] = Session::haveRight("computer", UPDATE);
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('components/form/computervirtualmachine.html.twig', [
            'item'                      => $this,
            'computer'                  => $comp,
            'params'                    => $options,
            'linked_computer'           => $linked_computer,
        ]);

        return true;
    }


    /**
     * Show hosts for a virtualmachine
     *
     * @param $comp   Computer object that represents the virtual machine
     *
     * @return void
     **/
    public static function showForVirtualMachine(Computer $comp)
    {

        $ID = $comp->fields['id'];

        if (!$comp->getFromDB($ID) || !$comp->can($ID, READ)) {
            return;
        }

        echo "<div class='center'>";

        if (isset($comp->fields['uuid']) && ($comp->fields['uuid'] != '')) {
            $hosts = getAllDataFromTable(
                self::getTable(),
                [
                    'RAW' => [
                        'LOWER(uuid)' => self::getUUIDRestrictCriteria($comp->fields['uuid'])
                    ]
                ]
            );

            if (!empty($hosts)) {
                echo "<table class='tab_cadre_fixehov'>";
                echo  "<tr class='noHover'><th colspan='2' >" . __('List of hosts') . "</th></tr>";

                $header = "<tr><th>" . __('Name') . "</th>";
                $header .= "<th>" . Entity::getTypeName(1) . "</th>";
                $header .= "</tr>";
                echo $header;

                $computer = new Computer();
                foreach ($hosts as $host) {
                    $class = $host['is_deleted'] ? "deleted" : "";
                    echo "<tr class='tab_bg_2 $class' >";
                    echo "<td>";
                    if ($computer->can($host['computers_id'], READ)) {
                        echo "<a href='" . Computer::getFormURLWithID($computer->fields['id']) . "'>";
                        echo $computer->fields['name'] . "</a>";
                        $tooltip = "<table><tr><td>" . __('Name') . "</td><td>" . $computer->fields['name'] .
                             '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Serial number') . "</td><td>" . $computer->fields['serial'] .
                             '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Comments') . "</td><td>" . $computer->fields['comment'] .
                             '</td></tr></table>';
                        echo "&nbsp; " . Html::showToolTip($tooltip, ['display' => false]);
                    } else {
                        echo $computer->fields['name'];
                    }
                    echo "</td>";
                    echo "<td>";
                    echo Dropdown::getDropdownName('glpi_entities', $computer->fields['entities_id']);
                    echo "</td></tr>";
                }
                echo $header;
                echo "</table>";
            }
        }
        echo "</div>";
        if (!empty($hosts)) {
            echo "<br>";
        }
    }


    /**
     * Print the computers disks
     *
     * @param Computer $comp Computer object
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showForComputer(Computer $comp)
    {

        $ID = $comp->fields['id'];

        if (!$comp->getFromDB($ID) || !$comp->can($ID, READ)) {
            return false;
        }
        $canedit = $comp->canEdit($ID);

        if ($canedit) {
            echo "<div class='center firstbloc'>" .
                "<a class='btn btn-primary' href='" . ComputerVirtualMachine::getFormURL() . "?computers_id=$ID'>";
            echo __('Add a virtual machine');
            echo "</a></div>\n";
        }

        echo "<div class='center table-responsive'>";

        $virtualmachines = getAllDataFromTable(
            self::getTable(),
            [
                'WHERE'  => [
                    'computers_id' => $ID
                ],
                'ORDER'  => 'name'
            ]
        );

        echo "<table class='tab_cadre_fixehov'>";

        Session::initNavigateListItems(
            'ComputerVirtualMachine',
            sprintf(
                __('%1$s = %2$s'),
                Computer::getTypeName(1),
                (empty($comp->fields['name'])
                ? "($ID)" : $comp->fields['name'])
            )
        );

        if (empty($virtualmachines)) {
            echo "<tr><th>" . __('No virtualized environment associated with the computer') . "</th></tr>";
        } else {
            echo "<tr class='noHover'><th colspan='10'>" . __('List of virtualized environments') . "</th></tr>";

            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . _n('Comment', 'Comments', 1) . "</th>";
            $header .= "<th>" . __('Automatic inventory') . "</th>";
            $header .= "<th>" . VirtualMachineType::getTypeName(1) . "</th>";
            $header .= "<th>" . VirtualMachineSystem::getTypeName(1) . "</th>";
            $header .= "<th>" . _n('State', 'States', 1) . "</th>";
            $header .= "<th>" . __('UUID') . "</th>";
            $header .= "<th>" . _x('quantity', 'Processors number') . "</th>";
            $header .= "<th>" . sprintf(__('%1$s (%2$s)'), _n('Memory', 'Memories', 1), __('Mio')) . "</th>";
            $header .= "<th>" . __('Machine') . "</th>";
            $header .= "</tr>";
            echo $header;

            $vm = new self();
            foreach ($virtualmachines as $virtualmachine) {
                $vm->getFromDB($virtualmachine['id']);
                $class = $virtualmachine['is_deleted'] ? "deleted" : "";
                echo "<tr class='tab_bg_2 $class'>";
                echo "<td>" . $vm->getLink() . "</td>";
                echo "<td>" . $virtualmachine['comment'] . "</td>";
                echo "<td>" . Dropdown::getYesNo($vm->isDynamic()) . "</td>";
                echo "<td>";
                echo Dropdown::getDropdownName(
                    'glpi_virtualmachinetypes',
                    $virtualmachine['virtualmachinetypes_id']
                );
                echo "</td>";
                echo "<td>";
                echo Dropdown::getDropdownName(
                    'glpi_virtualmachinesystems',
                    $virtualmachine['virtualmachinesystems_id']
                );
                echo "</td>";
                echo "<td>";
                echo Dropdown::getDropdownName(
                    'glpi_virtualmachinestates',
                    $virtualmachine['virtualmachinestates_id']
                );
                echo "</td>";
                echo "<td>" . $virtualmachine['uuid'] . "</td>";
                echo "<td>" . $virtualmachine['vcpu'] . "</td>";
                echo "<td>" . $virtualmachine['ram'] . "</td>";
                echo "<td>";
                if ($link_computer = self::findVirtualMachine($virtualmachine)) {
                     $computer = new Computer();
                    if ($computer->can($link_computer, READ)) {
                        $url  = "<a href='" . $computer->getFormURLWithID($link_computer) . "'>";
                        $url .= $computer->fields["name"] . "</a>";

                        $tooltip = "<table><tr><td>" . __('Name') . "</td><td>" . $computer->fields['name'] .
                            '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Serial number') . "</td><td>" . $computer->fields['serial'] .
                            '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Comments') . "</td><td>" . $computer->fields['comment'] .
                            '</td></tr></table>';

                        $url .= "&nbsp; " . Html::showToolTip($tooltip, ['display' => false]);
                    } else {
                        $url = $computer->fields['name'];
                    }
                    echo $url;
                }
                echo "</td>";
                echo "</tr>";
                Session::addToNavigateListItems('ComputerVirtualMachine', $virtualmachine['id']);
            }
            echo $header;
        }
        echo "</table>";
        echo "</div>";
    }


    /**
     * Get correct uuid sql search for virtualmachines
     *
     * @since 9.3.1
     *
     * @param string $uuid the uuid given
     *
     * @return array the restrict SQL clause which contains uuid, uuid with first block flipped,
     * uuid with 3 first block flipped
     **/
    public static function getUUIDRestrictCriteria($uuid)
    {

       //More infos about uuid, please see wikipedia :
       //http://en.wikipedia.org/wiki/Universally_unique_identifier
       //Some uuid are not conform, so preprocessing is necessary
       //A good uuid likes lik : 550e8400-e29b-41d4-a716-446655440000

       //Case one : for example some uuid are like that :
       //56 4d 77 d0 6b ef 3d da-4d 67 5c 80 a9 52 e2 c9
        $pattern  = "/([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ";
        $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})-";
        $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ";
        $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})/";
        if (preg_match($pattern, $uuid)) {
            $uuid = preg_replace($pattern, "$1$2$3$4-$5$6-$7$8-$9$10-$11$12$13$14$15$16", $uuid);
        }

       //Case two : why this code ? Because some dmidecode < 2.10 is buggy.
       //On unix is flips first block of uuid and on windows flips 3 first blocks...
        $in      = [strtolower($uuid)];
        $regexes = [
            "/([\w]{2})([\w]{2})([\w]{2})([\w]{2})(.*)/"                                        => "$4$3$2$1$5",
            "/([\w]{2})([\w]{2})([\w]{2})([\w]{2})-([\w]{2})([\w]{2})-([\w]{2})([\w]{2})(.*)/"  => "$4$3$2$1-$6$5-$8$7$9"
        ];
        foreach ($regexes as $pattern => $replace) {
            $reverse_uuid = preg_replace($pattern, $replace, $uuid);
            if ($reverse_uuid) {
                $in[] = strtolower($reverse_uuid);
            }
        }

        return Sanitizer::sanitize($in);
    }


    /**
     * Find a virtual machine by uuid
     *
     * @param array $fields  Array of virtualmachine fields
     *
     * @return integer|boolean ID of the computer that have this uuid or false otherwise
     **/
    public static function findVirtualMachine($fields = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($fields['uuid']) || empty($fields['uuid'])) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_computers',
            'WHERE'  => [
                'RAW' => [
                    'LOWER(uuid)'  => self::getUUIDRestrictCriteria($fields['uuid'])
                ]
            ]
        ]);

       //Virtual machine found, return ID
        if (count($iterator) == 1) {
            $result = $iterator->current();
            return $result['id'];
        } else if (count($iterator) > 1) {
            trigger_error(
                sprintf(
                    'findVirtualMachine expects to get one result, %1$s found in query "%2$s".',
                    count($iterator),
                    $iterator->getSql()
                ),
                E_USER_WARNING
            );
        }

        return false;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'uuid',
            'name'               => __('UUID'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'ram',
            'name'               => _n('Memory', 'Memories', 1),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'vcpu',
            'name'               => __('processor number'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab = array_merge($tab, Computer::rawSearchOptionsToAdd("ComputerVirtualMachine"));

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        $tab = [];

        $name = _n('Virtual machine', 'Virtual machines', Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'virtualmachine',
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '160',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '161',
            'table'              => 'glpi_virtualmachinestates',
            'field'              => 'name',
            'name'               => _n('State', 'States', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '162',
            'table'              => 'glpi_virtualmachinesystems',
            'field'              => 'name',
            'name'               => VirtualMachineSystem::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '163',
            'table'              => 'glpi_virtualmachinetypes',
            'field'              => 'name',
            'name'               => VirtualMachineType::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '164',
            'table'              => self::getTable(),
            'field'              => 'vcpu',
            'name'               => __('processor number'),
            'datatype'           => 'number',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '165',
            'table'              => self::getTable(),
            'field'              => 'ram',
            'name'               => _n('Memory', 'Memories', 1),
            'datatype'           => 'string',
            'unit'               => 'auto',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '166',
            'table'              => self::getTable(),
            'field'              => 'uuid',
            'name'               => __('UUID'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '179',
            'table'              => self::getTable(),
            'field'              => 'comment',
            'name'               => __('Virtual machine Comment'),
            'forcegroupby'       => true,
            'datatype'           => 'string',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        return $tab;
    }
}
