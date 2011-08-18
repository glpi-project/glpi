<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//*************************************************************************************************
//*************************************************************************************************
//***********  Fonctions d'affichage header footer helpdesk pager *********************************
//*************************************************************************************************
//*************************************************************************************************




/**
 * Display Debug Informations
 *
 * @param $with_session with session information
**/
function displayDebugInfos($with_session=true) {
   global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $SQL_TOTAL_TIMER, $DEBUG_AUTOLOAD;

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
      echo "<div id='debug'>";
      echo "<h1><a id='see_debug' name='see_debug'>GLPI MODE DEBUG</a></h1>";

      if ($CFG_GLPI["debug_sql"]) {
         echo "<h2>SQL REQUEST : ";
         echo $SQL_TOTAL_REQUEST." Queries ";
         echo "took  ".array_sum($DEBUG_SQL['times'])."s  </h2>";

         echo "<table class='tab_cadre'><tr><th>N&#176; </th><th>Queries</th><th>Time</th>";
         echo "<th>Errors</th></tr>";

         foreach ($DEBUG_SQL['queries'] as $num => $query) {
            echo "<tr class='tab_bg_".(($num%2)+1)."'><td>$num</td><td>";
            echo str_ireplace("ORDER BY","<br>ORDER BY",
                        str_ireplace("SORT","<br>SORT",
                              str_ireplace("LEFT JOIN","<br>LEFT JOIN",
                                    str_ireplace("INNER JOIN","<br>INNER JOIN",
                                          str_ireplace("WHERE","<br>WHERE",
                                                str_ireplace("FROM","<br>FROM",
                                                      str_ireplace("UNION","<br>UNION<br>",
                                                            str_replace(">","&gt;",
                                                                str_replace("<","&lt;",$query)))))))));
            echo "</td><td>";
            echo $DEBUG_SQL['times'][$num];
            echo "</td><td>";
            if (isset($DEBUG_SQL['errors'][$num])) {
               echo $DEBUG_SQL['errors'][$num];
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>";
         }
         echo "</table>";
      }

      if ($CFG_GLPI["debug_vars"]) {
         echo "<h2>AUTOLOAD</h2>";
         echo "<p>" . implode(', ', $DEBUG_AUTOLOAD) . "</p>";
         echo "<h2>POST VARIABLE</h2>";
         Html::printCleanArray($_POST);
         echo "<h2>GET VARIABLE</h2>";
         Html::printCleanArray($_GET);
         if ($with_session) {
            echo "<h2>SESSION VARIABLE</h2>";
            Html::printCleanArray($_SESSION);
         }
      }
      echo "</div>";
   }
}




/**
 * Show generic date search
 *
 * @param $element name of the html element
 * @param $value default value
 * @param $with_time display with time selection ?
 * @param $with_future display with future date selection ?
 *
 * @return rand value of dropdown
**/
function showGenericDateTimeSearch($element, $value='', $with_time=false, $with_future=false) {
   global $LANG, $CFG_GLPI;

   $rand = mt_rand();

   // Validate value
   if ($value!='NOW'
       && !preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)
       && !strstr($value,'HOUR')
       && !strstr($value,'DAY')
       && !strstr($value,'WEEK')
       && !strstr($value,'MONTH')
       && !strstr($value,'YEAR')) {

      $value = "";
   }

   if (empty($value)) {
      $value = 'NOW';
   }
   $specific_value = date("Y-m-d H:i:s");

   if (preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)) {
      $specific_value = $value;
      $value          = 0;
   }
   echo "<table><tr><td>";
   echo "<select id='genericdate$element$rand' name='_select_$element'>";

   $val = 'NOW';
   echo "<option value='$val' ".($value===$val?'selected':'').">".$LANG['calendar'][16]."</option>";
   echo "<option value='0' ".($value===0?'selected':'').">".$LANG['calendar'][17]."</option>";

   if ($with_time) {
      for ($i=1 ; $i<=24 ; $i++) {
         $val = '-'.$i.'HOUR';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "- $i ".$LANG['gmt'][1]."</option>";
      }
   }

   for ($i=1 ; $i<=7 ; $i++) {
      $val = '-'.$i.'DAY';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][12]."</option>";
   }

   for ($i=1 ; $i<=10 ; $i++) {
      $val = '-'.$i.'WEEK';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][13]."</option>";
   }

   for ($i=1 ; $i<=12 ; $i++) {
      $val = '-'.$i.'MONTH';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][14]."</option>";
   }

   for ($i=1 ; $i<=10 ; $i++) {
      $val = '-'.$i.'YEAR';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][15]."</option>";
   }

   if ($with_future) {
      if ($with_time) {
         for ($i=1 ; $i<=24 ; $i++) {
            $val = $i.'HOUR';
            echo "<option value='$val' ".($value===$val?'selected':'').">";
            echo "+ $i ".$LANG['gmt'][1]."</option>";
         }
      }

      for ($i=1 ; $i<=7 ; $i++) {
         $val = $i.'DAY';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][12]."</option>";
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $val = $i.'WEEK';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][13]."</option>";
      }

      for ($i=1 ; $i<=12 ; $i++) {
         $val = $i.'MONTH';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][14]."</option>";
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $val = $i.'YEAR';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][15]."</option>";
      }
   }

   echo "</select>";

   echo "</td><td>";
   echo "<div id='displaygenericdate$element$rand'></div>";

   $params = array('value'          => '__VALUE__',
                    'name'          => $element,
                    'withtime'      => $with_time,
                    'specificvalue' => $specific_value);

   Ajax::updateItemOnSelectEvent("genericdate$element$rand", "displaygenericdate$element$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/genericdate.php", $params);

   $params['value'] = $value;
   Ajax::updateItem("displaygenericdate$element$rand", $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                  $params);

   echo "</td></tr></table>";
   return $rand;
}




/**
 *  Create Ajax Tabs apply to 'tabspanel' div. Content is displayed in 'tabcontent'
 *
 * @param $tabdiv_id ID of the div containing the tabs
 * @param $tabdivcontent_id ID of the div containing the content loaded by tabs
 * @param $tabs array of tabs to create : tabs is array( 'key' => array('title'=>'x',url=>'url_toload',params='url_params')...
 * @param $type for active tab
 * @param $size width of tabs panel
 *
 * @return nothing
**/
function createAjaxTabs($tabdiv_id='tabspanel', $tabdivcontent_id='tabcontent', $tabs=array(),
                        $type, $size=950) {
   global $CFG_GLPI;

   $active_tabs = Session::getActiveTab($type);

   if (count($tabs)>0) {
      echo "<script type='text/javascript'>

            var tabpanel = new Ext.TabPanel({
            applyTo: '$tabdiv_id',
            width:$size,
            enableTabScroll: true,
            resizeTabs: false,
            collapsed: true,
            plain: true,
            plugins: [{
                ptype: 'tabscrollermenu',
                maxText  : 50,
                pageSize : 30
            }],
            items: [";
            $first = true;
            $default_tab = $active_tabs;

            if (!isset($tabs[$active_tabs])) {
               $default_tab = key($tabs);
            }

            foreach ($tabs as $key => $val) {
               if ($first) {
                  $first = false;
               } else {
                  echo ",";
               }

               echo "{
                  title: \"".$val['title']."\",
                  id: '$key',";
               if (!empty($key) && $key != 'empty') {
                  echo "autoLoad: {url: '".$val['url']."',
                        scripts: true,
                        nocache: true";
                        if (isset($val['params'])) {
                           echo ", params: '".$val['params']."'";
                        }
                  echo "},";
               }

               echo "  listeners:{ // Force glpi_tab storage
                       beforeshow : function(panel) {
                        /* clean content because append data instead of replace it : no more problem */
                        /* Problem with IE6... But clean data for tabpanel before show. Do it on load default tab ?*/
                        /*tabpanel.body.update('');*/
                        /* update active tab*/
                        Ext.Ajax.request({
                           url : '".$CFG_GLPI['root_doc'].
                                  "/ajax/updatecurrenttab.php?itemtype=$type&glpi_tab=".
                                  urlencode($key)."',
                           success: function(objServerResponse) {
                           //alert(objServerResponse.responseText);
                        }
                        });
                     }
                  }";
               echo "}";
            } // Foreach tabs
         echo "]});";

         echo "/// Define view point";
         echo "tabpanel.expand();";

         echo "// force first load
            function loadDefaultTab() {
               tabpanel.body=Ext.get('$tabdivcontent_id');
               // See before
               tabpanel.body.update('');
               tabpanel.Session::setActiveTab('$default_tab');";
         echo "}";

         echo "// force reload
            function reloadTab(add) {
               var tab = tabpanel.Session::getActiveTab();
               var opt = tab.autoLoad;
               if (add) {
                  if (opt.params)
                     opt.params = opt.params + '&' + add;
                  else
                     opt.params = add;
               }
               tab.getUpdater().update(opt);";
         echo "}";
      echo "</script>";
   }
}





?>
