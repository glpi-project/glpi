<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Ajax
class Ajax {


   /**
    * Call from a popup Windows, refresh the dropdown in main window
   **/
   static function refreshDropdownPopupInMainWindow() {

      if (isset($_SESSION["glpipopup"]["rand"])) {
         echo "<script type='text/javascript' >\n";
         echo "window.opener.update_results_".$_SESSION["glpipopup"]["rand"]."();";
         echo "</script>";
      }
   }


   /**
    * Call from a popup Windows, refresh the dropdown in main window
   **/
   static function refreshPopupMainWindow() {

      if (isset($_SESSION["glpipopup"]["rand"])) {
         echo "<script type='text/javascript' >\n";
         echo "window.opener.location.reload(true)";
         echo "</script>";
      }
   }


   /**
    * Input text used as search system in ajax system
    *
    * @param $id ID of the ajax item
    * @param $size size of the input text field
    *
   **/
   static function displaySearchTextForDropdown($id, $size=4) {
      global $CFG_GLPI, $LANG;

      echo "<input title=\"".$LANG['buttons'][0]." (".$CFG_GLPI['ajax_wildcard']." ".
           $LANG['search'][1].")\" type='text' ondblclick=\"this.value='".
           $CFG_GLPI["ajax_wildcard"]."';\" id='search_$id' name='____data_$id' size='$size'>\n";
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
   static function createTabs($tabdiv_id='tabspanel', $tabdivcontent_id='tabcontent', $tabs=array(),
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
                  tabpanel.setActiveTab('$default_tab');";
            echo "}";

            echo "// force reload
               function reloadTab(add) {
                  var tab = tabpanel.getActiveTab();
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


   /**
    * Javascript code for update an item when another item changed
    *
    * @param $toobserve id (or array of id) of the select to observe
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    * @param $events array of the observed events
    * @param $minsize minimum size of data to update content
    * @param $forceloadfor array of content which must force update content
    *
   **/
   static function updateItemOnEvent($toobserve, $toupdate, $url, $parameters=array(),
                                      $events=array("change"), $minsize = -1, $forceloadfor=array()) {

      echo "<script type='text/javascript'>";
      self::updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters, $events, $minsize,
                                     $forceloadfor);
      echo "</script>";
   }


   /**
    * Javascript code for update an item when a select item changed
    *
    * @param $toobserve id of the select to observe
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    *
   **/
   static function updateItemOnSelectEvent($toobserve, $toupdate, $url, $parameters=array()) {

      self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, array("change"));
   }


   /**
    * Javascript code for update an item when a Input text item changed
    *
    * @param $toobserve id of the Input text to observe
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    * @param $minsize minimum size of data to update content
    * @param $forceloadfor array of content which must force update content
    *
   **/
   static function updateItemOnInputTextEvent($toobserve, $toupdate, $url, $parameters=array(),
                                              $minsize=-1, $forceloadfor=array()) {
      global $CFG_GLPI;

      if (count($forceloadfor)==0) {
         $forceloadfor = array($CFG_GLPI['ajax_wildcard']);
      }
      // Need to define min size for text search
      if ($minsize < 0) {
         $minsize = $CFG_GLPI['ajax_min_textsearch_load'];
      }

      self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, array("dblclick", "keyup"),
                            $minsize, $forceloadfor);
   }


   /**
    * Javascript code for update an item when another item changed (Javascript code only)
    *
    * @param $toobserve id (or array of id) of the select to observe
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    * @param $events array of the observed events
    * @param $minsize minimum size of data to update content
    * @param $forceloadfor array of content which must force update content
    *
   **/
   static function updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters=array(),
                                           $events=array("change"), $minsize = -1,
                                           $forceloadfor=array()) {

      if (is_array($toobserve)) {
         $zones = $toobserve;
      } else {
         $zones = array($toobserve);
      }

      foreach ($zones as $zone) {
         foreach ($events as $event) {
            echo "
               Ext.get('$zone').on(
                '$event',
                function() {";
                  $condition = '';
                  if ($minsize >= 0) {
                     $condition = " Ext.get('$zone').getValue().length >= $minsize ";
                  }
                  if (count($forceloadfor)) {
                     foreach ($forceloadfor as $value) {
                        if (!empty($condition)) {
                           $condition .= " || ";
                        }
                        $condition .= "Ext.get('$zone').getValue() == '$value'";
                     }
                  }
                  if (!empty($condition)) {
                     echo "if ($condition) {";
                  }
                  self::updateItemJsCode($toupdate, $url, $parameters, $toobserve);
                  if (!empty($condition)) {
                     echo "}";
                  }

          echo "});\n";
         }
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param $options array of options
    *    - toupdate : array / Update a specific item on select change on dropdown
    *                   (need value_fieldname, to_update, url (see Ajax::updateItemOnSelectEvent for informations)
    *                   and may have moreparams)
    *
   **/
   static function commonDropdownUpdateItem($options) {

      if (isset($options["update_item"])
           && (is_array($options["update_item"]) || strlen($options["update_item"])>0)) {

         if (!is_array($options["update_item"])) {
            $datas = unserialize(stripslashes($options["update_item"]));
         } else {
            $datas = $options["update_item"];
         }


         if (is_array($datas) && count($datas)) {
            // Put it in array
            if (isset($datas['to_update'])) {
               $datas = array($datas);
            }
            foreach ($datas as $data) {
               $paramsupdate = array();
               if (isset($data['value_fieldname'])) {
                  $paramsupdate = array($data['value_fieldname'] => '__VALUE__');
               }
   
               if (isset($data["moreparams"])
                  && is_array($data["moreparams"])
                  && count($data["moreparams"])) {
   
                  foreach ($data["moreparams"] as $key => $val) {
                     $paramsupdate[$key] = $val;
                  }
               }
   
               Ajax::updateItemOnSelectEvent("dropdown_".$options["myname"].$options["rand"],
                                             $data['to_update'], $data['url'], $paramsupdate);
            }
         }
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    * @param $toobserve id of another item used to get value in case of __VALUE__ used
    *                   array of id to get value in case of __VALUE#__ used
    *
   **/
   static function updateItemJsCode($toupdate, $url, $parameters=array(), $toobserve="") {

      // Get it from a Ext.Element object
      $out = "Ext.get('$toupdate').load({
          url: '$url',
          scripts: true";

      if (count($parameters)) {
         $out .= ",
             params:'";
         $first = true;
         foreach ($parameters as $key => $val) {
            if ($first) {
               $first = false;
            } else {
               $out .= "&";
            }

            $out .= $key."=";
            if (is_array($val)) {
               $out .=  rawurlencode(serialize($val));

            } else if (preg_match('/^__VALUE(\d+)__$/',$val,$regs)) {
               $out .=  "'+Ext.get('".$toobserve[$regs[1]]."').getValue()+'";

            } else if ($val==="__VALUE__") {
               $out .=  "'+Ext.get('$toobserve').getValue()+'";

            } else {
               if (preg_match("/['\"]/",$val)) {
                  $out .=  rawurlencode($val);
               } else {
                  $out .=  $val;
               }
            }
         }
         $out .= "'\n";
      }
      $out .= "});";
      echo $out;
   }


   /**
    * Complete Dropdown system using ajax to get datas
    *
    * @param $use_ajax Use ajax search system (if not display a standard dropdown)
    * @param $relativeurl Relative URL to the root directory of GLPI
    * @param $params Parameters to send to ajax URL
    * @param $default Default datas t print in case of $use_ajax
    * @param $rand Random parameter used
    *
   **/
   static function dropdown($use_ajax, $relativeurl, $params=array(), $default="&nbsp;", $rand=0) {
      global $CFG_GLPI, $DB, $LANG;

      $initparams = $params;
      if ($rand == 0) {
         $rand = mt_rand();
      }

      if ($use_ajax) {
         self::displaySearchTextForDropdown($rand);
         self::updateItemOnInputTextEvent("search_$rand", "results_$rand",
                                           $CFG_GLPI["root_doc"].$relativeurl, $params,
                                           $CFG_GLPI['ajax_min_textsearch_load']);
      }
      echo "<span id='results_$rand'>\n";
      if (!$use_ajax) {
         // Save post datas if exists
         $oldpost = array();
         if (isset($_POST) && count($_POST)) {
            $oldpost = $_POST;
         }
         $_POST = $params;
         $_POST["searchText"] = $CFG_GLPI["ajax_wildcard"];
         include (GLPI_ROOT.$relativeurl);
         // Restore $_POST datas
         if (count($oldpost)) {
            $_POST = $oldpost;
         }
      } else {
         echo $default;
      }
      echo "</span>\n";
      echo "<script type='text/javascript'>";
      echo "function update_results_$rand() {";
      if ($use_ajax) {
         self::updateItemJsCode("results_$rand", $CFG_GLPI['root_doc'].$relativeurl, $initparams,
                                 "search_$rand");
      } else {
         $initparams["searchText"]=$CFG_GLPI["ajax_wildcard"];
         self::updateItemJsCode("results_$rand", $CFG_GLPI['root_doc'].$relativeurl, $initparams);
      }
      echo "}";
      echo "</script>";
   }


   /**
    * Javascript code for update an item
    *
    * @param $toupdate id of the item to update
    * @param $url Url to get datas to update the item
    * @param $parameters Parameters to send to ajax URL
    * @param $toobserve id of another item used to get value in case of __VALUE__ used
    *
   **/
   static function updateItem($toupdate, $url, $parameters=array(), $toobserve="") {

      echo "<script type='text/javascript'>";
      self::updateItemJsCode($toupdate,$url,$parameters,$toobserve);
      echo "</script>";
   }

}

?>