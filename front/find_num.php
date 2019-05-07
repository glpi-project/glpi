<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

if (!$CFG_GLPI["use_anonymous_helpdesk"]) {
   exit();
}

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
echo "<!DOCTYPE html>\n";
echo "<html lang=\"{$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]}\">";
?>
<head>
    <meta charset="utf-8">
    <title>GLPI</title>

<?php
echo Html::scss('css/styles');
if (isset($_SESSION['glpihighcontrast_css']) && $_SESSION['glpihighcontrast_css']) {
   echo Html::scss('css/highcontrast');
}
$theme = isset($_SESSION['glpipalette']) ? $_SESSION['glpipalette'] : 'auror';
echo Html::scss('css/palettes/' . $theme);
echo Html::script($CFG_GLPI["root_doc"].'/script.js');
?>

</head>

<body>
    <script type="text/javascript">
function fillidfield(Type,Id) {

   window.opener.document.forms["helpdeskform"].elements["items_id"].value = Id;
   window.opener.document.forms["helpdeskform"].elements["itemtype"].value = Type;
   window.close();
}
</script>

<?php

echo "<div class='center'>";
echo "<p class='b'>".__('Search the ID of your hardware')."</p>";
echo " <form name='form1' method='post' action='".$_SERVER['PHP_SELF']."'>";

echo "<table class='tab_cadre_fixe'>";
echo "<tr><th height='29'>".__('Enter the first letters (user, item name, serial or asset number)').
     "</th></tr>";
echo "<tr><td class='tab_bg_1 center'>";
echo "<input name='NomContact' type='text' id='NomContact' >";
echo "<input type='hidden' name='send' value='1'>"; // bug IE ! La validation par enter ne fonctionne pas sans cette ligne  incroyable mais vrai !
echo "<input type='submit' name='send' value='". _sx('button', 'Search')."'>";
echo "</td></tr></table>";
Html::closeForm();
echo "</div>";

if (isset($_POST["send"])) {
   echo "<table class='tab_cadre_fixe'>";
   echo " <tr class='tab_bg3'>";
   echo " <td class='center b' width='30%'>".__('Alternate username')."</td>";
   echo " <td class='center b' width='20%'>".__('Hardware type')."</td>";
   echo " <td class='center b' width='30%'>"._n('Associated element', 'Associated elements', 2)."</td>";
   echo " <td class='center b' width='5%'>".__('ID')."</td>";
   echo " <td class='center b' width='10%'>".__('Serial number')."</td>";
   echo " <td class='center b' width='10%'>".__('Inventory number')."</td>";
   echo " </tr>";

   $types = ['Computer'         => __('Computer'),
                  'NetworkEquipment' => __('Network device'),
                  'Printer'          => __('Printer'),
                  'Monitor'          => __('Monitor'),
                  'Peripheral'       => __('Device')];
   foreach ($types as $type => $label) {
      $iterator = $DB->request([
         'SELECT' => ['name', 'id', 'contact', 'serial', 'otherserial'],
         'FROM'   => getTableForItemType($type),
         'WHERE'  => [
            'is_template'  => 0,
            'is_deleted'   => 0,
            'OR'           => [
               'contact'      => ['LIKE', '%' . $_POST['NomContact'] . '%'],
               'name'         => ['LIKE', '%' . $_POST['NomContact'] . '%'],
               'serial'       => ['LIKE', '%' . $_POST['NomContact'] . '%'],
               'otherserial'  => ['LIKE', '%' . $_POST['NomContact'] . '%'],
            ]
         ],
         'ORDER'           => ['name']
      ]);

      while ($ligne = $iterator->next()) {
         $Comp_num = $ligne['id'];
         $Contact  = $ligne['contact'];
         $Computer = $ligne['name'];
         $s1       = $ligne['serial'];
         $s2       = $ligne['otherserial'];
         echo " <tr class='tab_bg_1' onClick=\"fillidfield(".$type.",".$Comp_num.")\">";
         echo "<td class='center'>&nbsp;$Contact&nbsp;</td>";
         echo "<td class='center'>&nbsp;$label&nbsp;</td>";
         echo "<td class='center b'>&nbsp;$Computer&nbsp;</td>";
         echo "<td class='center'>&nbsp;$Comp_num&nbsp;</td>";
         echo "<td class='center'>&nbsp;$s1&nbsp;</td>";
         echo "<td class='center'>&nbsp;$s2&nbsp;</td>";
         echo "<td class='center'>";
         echo "</td></tr>";
      }
   }

   $iterator = $DB->request([
      'SELECT' => ['name', 'id'],
      'FROM'   => 'glpi_softwares',
      'WHERE'  => [
         'is_template'  => 0,
         'is_deleted'   => 0,
         'name'         => ['LIKE', "%{$_POST['NomContact']}%"]
      ],
      'ORDER'  => ['name']
   ]);

   while ($ligne = $iterator->next()) {
      $Comp_num = $ligne['id'];
      $Computer = $ligne['name'];
      echo " <tr class='tab_find' onClick=\"fillidfield('Software',".$Comp_num.")\">";
      echo "<td class='center'>&nbsp;</td>";
      echo "<td class='center'>&nbsp;"._n('Software', 'Software', 1)."&nbsp;</td>";
      echo "<td class='center b'>&nbsp;$Computer&nbsp;</td>";
      echo "<td class='center'>&nbsp;$Comp_num&nbsp;</td>";
      echo "<td class='center'>&nbsp;</td></tr>";
   }

   echo "</table>";
}
echo '</body></html>';
