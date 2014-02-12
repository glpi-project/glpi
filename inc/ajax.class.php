<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Ajax
class Ajax {


   /**
    * Create modal window
    * After display it using $name.show()
    * May be constraint to a predefined html item setting renderTo options
    *
    * @since version 0.84
    *
    * @param $name            name of the js object
    * @param $url             URL to display in modal
    * @param $options array   of possible options:
    *          - width (default 800)
    *          - height (default 400)
    *          - modal : is a modal window ? (default true)
    *          - container : specify a html element to render (default empty to html.body)
    *          - title : window title (default empty)
   **/
   static function createModalWindow($name, $url, $options=array() ) {

      $param = array('width'       => 800,
                     'height'      => 400,
                     'modal'       => true,
                     'container'   => '',
                     'title'       => '',
                     'extraparams' => array());

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }
      echo "<script type='text/javascript'>";
      echo "var $name=new Ext.Window({
         layout:'fit',
         width:".$param['width'].",
         height:".$param['height'].",
         closeAction:'hide',
         modal: ".($param['modal']?'true':'false').",
         ".(!empty($param['container'])?"renderTo: '".$param['container']."',":'')."
         autoScroll: true,
         title: \"".addslashes($param['title'])."\",
         autoLoad: {url: '$url',
                    scripts: true,
                    nocache: true";
         if (is_array($param['extraparams']) && count($param['extraparams'])) {
            echo ", params: '".Toolbox::append_params($param['extraparams'])."'";
         }
         echo "}";

     echo " }); ";
      echo "</script>";
   }


   /**
    * Create fixed modal window
    * After display it using $name.show()
    * May be constraint to a predefined html item setting container options
    *
    * @since version 0.84
    *
    * @param $name            name of the js object
    * @param $options array   of possible options:
    *          - width (default 800)
    *          - height (default 400)
    *          - modal : is a modal window ? (default true)
    *          - container : specify a html element to render (default empty to html.body)
    *          - title : window title (default empty)
   **/
   static function createFixedModalWindow($name, $options=array() ) {

      $param = array('width'     => 800,
                     'height'    => 400,
                     'modal'     => true,
                     'container'  => '',
                     'title'     => '');

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }
      echo "<script type='text/javascript'>";
      echo "var $name=new Ext.Window({
         layout:'fit',
         width:".$param['width'].",
         height:".$param['height'].",
         closeAction:'hide',
         modal: ".($param['modal']?'true':'false').",
         ".(!empty($param['container'])?"applyTo: '".$param['container']."',":'')."
         autoScroll: true,
         title: \"".addslashes($param['title'])."\"
         }); ";
      echo "</script>";
   }


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

      // $_SESSION["glpipopup"]["rand"] is not use here but do check to be sure that
      // we are in popup
      if (isset($_SESSION["glpipopup"]["rand"])) {
         echo "<script type='text/javascript' >\n";
         echo "window.opener.location.reload(true)";
         echo "</script>";
      }
   }


   /**
    * Call from a popup Windows, refresh the tab in main window
    *
    * @since version 0.84
   **/
   static function refreshPopupTab() {

      echo "<script type='text/javascript' >\n";
      echo "window.opener.reloadTab()";
      echo "</script>";
   }


   /**
    * Input text used as search system in ajax system
    *
    * @param $id   ID of the ajax item
    * @param $size size of the input text field (default 4)
   **/
   static function displaySearchTextForDropdown($id, $size=4) {
      echo self::getSearchTextForDropdown($id, $size);
   }


   /**
    * Input text used as search system in ajax system
    * @since version 0.84
    *
    * @param $id   ID of the ajax item
    * @param $size size of the input text field (default 4)
   **/
   static function getSearchTextForDropdown($id, $size=4) {
      global $CFG_GLPI;

      //TRANS: %s is the character used as wildcard in ajax search
      return "<input title=\"".sprintf(__s('Search (%s for all)'), $CFG_GLPI["ajax_wildcard"]).
             "\" type='text' ondblclick=\"this.value='".
             $CFG_GLPI["ajax_wildcard"]."';\" id='search_$id' name='____data_$id' size='$size'>\n";
   }


   /**
    *  Create Ajax Tabs apply to 'tabspanel' div. Content is displayed in 'tabcontent'
    *
    * @param $tabdiv_id                ID of the div containing the tabs (default 'tabspanel')
    * @param $tabdivcontent_id         ID of the div containing the content loaded by tabs
    *                                  (default 'tabcontent')
    * @param $tabs               array of tabs to create :
    *                                  tabs is array('key' => array('title'=> 'x',
    *                                                                url    => 'url_toload',
    *                                                                params => 'url_params')...
    * @param $type                     for active tab
    * @param $size                     width of tabs panel (default 950)
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
                     title: \"".addslashes($val['title'])."\",
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
    * @param $toobserve             id (or array of id) of the select to observe
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $events       array    of the observed events (default 'change')
    * @param $minsize               minimum size of data to update content (default -1)
    * @param $buffertime            minimum time to wait before reload (default -1)
    * @param $forceloadfor array    of content which must force update content
    * @param $display      boolean  display or get string (default true)
   **/
   static function updateItemOnEvent($toobserve, $toupdate, $url, $parameters=array(),
                                     $events=array("change"), $minsize=-1, $buffertime=-1,
                                     $forceloadfor=array(), $display=true) {

      $output  = "<script type='text/javascript'>";
      $output .= self::updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters, $events,
                                               $minsize, $buffertime, $forceloadfor, false);
      $output .=  "</script>";
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Javascript code for update an item when a select item changed
    *
    * @param $toobserve             id of the select to observe
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $display      boolean  display or get string (default true)
   **/
   static function updateItemOnSelectEvent($toobserve, $toupdate, $url, $parameters=array(),
                                           $display=true) {

      return self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, array("change"),
                                     -1, -1, array(), $display);
   }


   /**
    * Javascript code for update an item when a Input text item changed
    *
    * @param $toobserve             id of the Input text to observe
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $minsize               minimum size of data to update content (default -1)
    * @param $buffertime            minimum time to wait before reload (default -1)
    * @param $forceloadfor array    of content which must force update content
    * @param $display      boolean  display or get string (default true)
    *
   **/
   static function updateItemOnInputTextEvent($toobserve, $toupdate, $url, $parameters=array(),
                                              $minsize=-1, $buffertime=-1, $forceloadfor=array(),
                                              $display=true) {
      global $CFG_GLPI;

      if (count($forceloadfor) == 0) {
         $forceloadfor = array($CFG_GLPI['ajax_wildcard']);
      }
      // Need to define min size for text search
      if ($minsize < 0) {
         $minsize = $CFG_GLPI['ajax_min_textsearch_load'];
      }
      if ($buffertime < 0) {
         $buffertime = $CFG_GLPI['ajax_buffertime_load'];
      }

      return self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters,
                                     array("dblclick", "keyup"),  $minsize, $buffertime,
                                     $forceloadfor, $display);
   }


   /**
    * Javascript code for update an item when another item changed (Javascript code only)
    *
    * @param $toobserve             id (or array of id) of the select to observe
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $events       array    of the observed events (default 'change')
    * @param $minsize               minimum size of data to update content (default -1)
    * @param $buffertime            minimum time to wait before reload (default -1)
    * @param $forceloadfor array    of content which must force update content
    * @param $display      boolean  display or get string (default true)
   **/
   static function updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters=array(),
                                           $events=array("change"), $minsize = -1, $buffertime=-1,
                                           $forceloadfor=array(), $display=true) {

      if (is_array($toobserve)) {
         $zones = $toobserve;
      } else {
         $zones = array($toobserve);
      }
      $output = '';
      foreach ($zones as $zone) {
         foreach ($events as $event) {
            $output .= "
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
                     $output .= "if ($condition) {";
                  }
                  $output .= self::updateItemJsCode($toupdate, $url, $parameters, $toobserve, false);
                  if (!empty($condition)) {
                     $output .= "}";
                  }

               $output .=  "}";
               if ($buffertime > 0) {
                  $output.= ", this, { buffer : $buffertime }";
               }
            $output .=");\n";
         }
      }
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param $options array of options
    *  - toupdate : array / Update a specific item on select change on dropdown
    *               (need value_fieldname, to_update,
    *                url (@see Ajax::updateItemOnSelectEvent for information)
    *                and may have moreparams)
   **/
   static function commonDropdownUpdateItem($options) {

      if (isset($options["update_item"])
          && (is_array($options["update_item"]) || (strlen($options["update_item"]) > 0))) {

         if (!is_array($options["update_item"])) {
            $datas = Toolbox::decodeArrayFromInput($options["update_item"]);
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

             self::updateItemOnSelectEvent("dropdown_".$options["myname"].$options["rand"],
                                           $data['to_update'], $data['url'], $paramsupdate);
            }
         }
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $toobserve             id of another item used to get value in case of __VALUE__ used
    *                               or
    *                      array    of id to get value in case of __VALUE#__ used (default '')
    * @param $display      boolean  display or get string (default true)
   **/
   static function updateItemJsCode($toupdate, $url, $parameters=array(), $toobserve="",
                                    $display=true) {

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
               $out .=  Toolbox::prepareArrayForInput($val);
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
      if ($display) {
         echo $out;
      } else {
         return $out;
      }
   }


   /**
    * Complete Dropdown system using ajax to get datas
    *
    * @param $use_ajax              Use ajax search system (if not display a standard dropdown)
    * @param $relativeurl           Relative URL to the root directory of GLPI
    * @param $params       array    of parameters to send to ajax URL
    * @param $default               Default datas to print in case of $use_ajax (default '&nbsp;')
    * @param $rand                  Random parameter used (default 0)
    * @param $display      boolean  display or get string (default true)

   **/
   static function dropdown($use_ajax, $relativeurl, $params=array(), $default="&nbsp;", $rand=0,
                            $display=true) {
      global $CFG_GLPI, $DB;

      $initparams = $params;
      if ($rand == 0) {
         $rand = mt_rand();
      }
      $locoutput = '';
      if ($use_ajax) {
         $locoutput .= self::getSearchTextForDropdown($rand);
         $locoutput .= self::updateItemOnInputTextEvent("search_$rand", "results_$rand",
                                                        $CFG_GLPI["root_doc"].$relativeurl,
                                                        $params,
                                                        $CFG_GLPI['ajax_min_textsearch_load'],
                                                        $CFG_GLPI['ajax_buffertime_load'],
                                                        array(), false);
      }
      $locoutput .=  "<span id='results_$rand'>\n";
      if (!$use_ajax) {
         // Save post datas if exists
         $oldpost = array();
         if (isset($_POST) && count($_POST)) {
            $oldpost = $_POST;
         }
         $_POST = $params;
         $_POST["searchText"] = $CFG_GLPI["ajax_wildcard"];
         ob_start();
         include (GLPI_ROOT.$relativeurl);
         $locoutput .= ob_get_contents();
         ob_end_clean();

         // Restore $_POST datas
         if (count($oldpost)) {
            $_POST = $oldpost;
         }
      } else {
         $locoutput .=  $default;
      }

      $locoutput .=  "</span>\n";
      $locoutput .=  "<script type='text/javascript'>";
      $locoutput .=  "function update_results_$rand() {";
      if ($use_ajax) {
         $locoutput .= self::updateItemJsCode("results_$rand", $CFG_GLPI['root_doc'].$relativeurl,
                                              $initparams, "search_$rand", false);
      } else {
         $initparams["searchText"] = $CFG_GLPI["ajax_wildcard"];
         $locoutput               .= self::updateItemJsCode("results_$rand",
                                                            $CFG_GLPI['root_doc'].$relativeurl,
                                                            $initparams, '', false);
      }
      $locoutput .=  "}";
      $locoutput .=  "</script>";

      if ($display) {
         echo $locoutput;
      } else {
         return $locoutput;
      }
   }


   /**
    * Javascript code for update an item
    *
    * @param $toupdate              id of the item to update
    * @param $url                   Url to get datas to update the item
    * @param $parameters   array    of parameters to send to ajax URL
    * @param $toobserve             id of another item used to get value in case of __VALUE__ used
    *                               (default '')
    * @param $display      boolean  display or get string (default true)
    *
   **/
   static function updateItem($toupdate, $url, $parameters=array(), $toobserve="", $display=true) {

      $output = "<script type='text/javascript'>";
      $output .= self::updateItemJsCode($toupdate,$url,$parameters,$toobserve, false);
      $output .= "</script>";
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }

}
?>