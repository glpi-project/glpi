<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file: Manage all internet related elements
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT."/inc/includes.php");

Session::checkRight('internet', 'r');

Html::header(__('Internet'), $_SERVER['PHP_SELF']);

// Redirect management
if (isset($_GET["redirect"])) {
   Toolbox::manageRedirect($_GET["redirect"]);
}

function displayItemSearchLink($itemtype) {
   echo "<table class='tab_cadrehov' >";
   echo "<tr><th colspan='2'>";
   echo "<a href='".Toolbox::getItemTypeSearchURL($itemtype)."'>";
   $numberItems = countElementsInTable(getTableForItemType($itemtype));
   echo $itemtype::createTabEntry($itemtype::getTypeName($numberItems), $numberItems);
   echo "</a>";
   echo "</th></tr>";
   echo "</table>";
}

echo "<div class='center'>\n";

// Display the links to the internet elements
echo "<table class='tab_cadre_fixe'>\n";
echo "<tr><th colspan='2'>".__('Internet elements')."</th></tr>\n";

echo "<tr>";
echo "<td width='50%'>";
displayItemSearchLink('IPNetwork');
echo "</td>";

echo "<td width='50%'>";
displayItemSearchLink('FQDN');
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td width='50%'>";
displayItemSearchLink('NetworkName');
echo "</td>";

echo "<td width='50%'>";
displayItemSearchLink('NetworkAlias');
echo "</td>";
echo "</tr>";
echo "</table>\n";

// Display the search methods
echo "<table class='tab_cadre_fixe'>\n";
echo "<tr><th>".__('Search internet element')."</th></tr>\n";

echo "<tr>";
echo "<td width='50%' style='text-align: center'>";

echo "<form method='post' action='".$_SERVER['PHP_SELF']."'>\n";
echo "<input type='text' name='value'>&nbsp;-&nbsp;";

$types = array('label'   => __('FQDN (NetworkName or NetworkAlias)'),
               'IP' => __('IP address'),
               'MAC'   => __('MAC address'),
               'network' => __('Networks owning an address'));

Dropdown::ShowFromArray('type', $types);

echo "&nbsp;-&nbsp;<input type='checkbox' name='exact'>&nbsp;". __('Exact match');
echo "&nbsp;<input type='submit' name='search' value='" . __('Search') . "' class='submit'>";
echo "</form>";

echo "</td>";
echo "</tr>";

if (isset($_POST['search'])) {
   $value = $_POST['value'];
   $allow_wildcard = !isset($_POST['exact']);
   $type = $_POST['type'];
   switch ($type) {
   case 'label':
      $items = FQDNLabel::getItemsByFQDN($value, $allow_wildcard);
      $msg = sprintf(__('List of FQDN that are "%s"'), $value);
      break;
   case 'IP':
      $items = IPAddress::getItemsByIPAddress($value);
      $msg = sprintf(__('List of IP that are "%s"'), $value);
      break;
   case 'MAC':
      $items = NetworkPortInstantiation::getItemsByMac($value, $allow_wildcard);
      $msg = sprintf(__('List of MAC that are "%s"'), $value);
      break;
   case 'network':
      $msg = '';
      $items = array();
      break;
   }
   if (isset($items)) {
      echo "<tr><th>";

      if ($allow_wildcard) {
         echo sprintf('%1$s (%2$s)', $msg, __('allow wildcards'))
      } else {
         echo sprintf('%1$s (%2$s)', $msg, __('exact match'))
      }
      echo "</th></tr>";
      foreach ($items as $item) {
         echo "<tr><td>";
         echo $item[0]->getType()." : ";
         $elements_to_display = array();
         foreach ($item as $child) {
            if ($child instanceof FQDNLabel) {
               $name = $child->getInternetName();
            } else {
               $name = $child->getName();
            }
            $element_to_display = "<a href='".$child->getLinkURL()."'>$name</a>";
            if ((isset($child->fields['mac'])) && ($type == 'MAC') && ($allow_wildcard)) {
               $element_to_display .= " [" . $child->fields['mac'] . "]";
            }
            $elements_to_display[] = $element_to_display;
         }
         echo implode(' &gt; ', $elements_to_display);
         echo "</td></tr>";
      }
   }
}

echo "</table>\n";

echo "</div>\n";


Html::footer();
?>
