<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

/**
 * Ajax Class
**/
class Ajax {


   /**
    * Create modal window
    * After display it using $name.dialog("open");
    *
    * @since version 0.84
    *
    * @param $name            name of the js object
    * @param $url             URL to display in modal
    * @param $options array   of possible options:
    *     - width      (default 800)
    *     - height     (default 400)
    *     - modal      is a modal window ? (default true)
    *     - container  specify a html element to render (default empty to html.body)
    *     - title      window title (default empty)
    *     - display    display or get string ? (default true)
   **/
   static function createModalWindow($name, $url, $options=array() ) {

      $param = array('width'           => 800,
                     'height'          => 400,
                     'modal'           => true,
                     'container'       => '',
                     'title'           => '',
                     'extraparams'     => array(),
                     'display'         => true,
                     'js_modal_fields' => '');

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  = "<script type='text/javascript'>\n";
      $out .= "var $name=";
      if (!empty($param['container'])) {
         $out .= Html::jsGetElementbyID(Html::cleanId($param['container']));
      } else {
         $out .= "$('<div />')";
      }
      $out .= ".dialog({\n
         width:".$param['width'].",\n
         autoOpen: false,\n
         height:".$param['height'].",\n
         modal: ".($param['modal']?'true':'false').",\n
         title: \"".addslashes($param['title'])."\",\n
         open: function (){
            var fields = ";
      if (is_array($param['extraparams']) && count($param['extraparams'])) {
         $out .= json_encode($param['extraparams'],JSON_FORCE_OBJECT);
      } else {
         $out .= '{}';
      }
      $out .= ";\n";
      if (!empty($param['js_modal_fields'])) {
         $out .= $param['js_modal_fields']."\n";
      }
      $out .= "            $(this).load('$url', fields);
         }
      });\n";
      $out .= "</script>\n";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


   /**
    * Create fixed modal window
    * After display it using $name.dialog("open");
    *
    * @since version 0.84
    *
    * @param $name            name of the js object
    * @param $options array   of possible options:
    *          - width       (default 800)
    *          - height      (default 400)
    *          - modal       is a modal window ? (default true)
    *          - container   specify a html element to render (default empty to html.body)
    *          - title       window title (default empty)
    *          - display     display or get string ? (default true)
   **/
   static function createFixedModalWindow($name, $options=array() ) {

      $param = array('width'     => 800,
                     'height'    => 400,
                     'modal'     => true,
                     'container' => '',
                     'title'     => '',
                     'display'   => true);

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  =  "<script type='text/javascript'>\n";
      $out .= "var $name=";
      if (!empty($param['container'])) {
         $out .= Html::jsGetElementbyID(Html::cleanId($param['container']));
      } else {
         $out .= "$('<div></div>')";
      }
      $out .= ".dialog({\n
         width:".$param['width'].",\n
         autoOpen: false,\n
         height:".$param['height'].",\n
         modal: ".($param['modal']?'true':'false').",\n
         title: \"".addslashes($param['title'])."\"\n
         });\n";
      $out .= "</script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }

   }


   /**
    * Create modal window in Iframe
    * After display it using Html::jsGetElementbyID($domid).dialog("open");
    *
    * @since version 0.85
    *
    * @param $domid           DOM ID of the js object
    * @param $url             URL to display in modal
    * @param $options array   of possible options:
    *          - width          (default 800)
    *          - height         (default 400)
    *          - modal          is a modal window ? (default true)
    *          - title          window title (default empty)
    *          - display        display or get string ? (default true)
    *          - reloadonclose  reload main page on close ? (default false)
   **/
   static function createIframeModalWindow($domid, $url, $options=array() ) {

      $param = array('width'         => 1050,
                     'height'        => 500,
                     'modal'         => true,
                     'title'         => '',
                     'display'       => true,
                     'reloadonclose' => false);

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }
      $url .= (strstr($url,'?') ?'&' :  '?').'_in_modal=1';

      $out  = "<div id=\"$domid\">";
      $out .= "<iframe id='Iframe$domid' width='100%' height='100%' marginWidth='0' marginHeight='0'
                frameBorder='0' scrolling='auto'></iframe></div>";

      $out .= "<script type='text/javascript'>
            $('#$domid').dialog({
               modal: true,
               autoOpen: false,
               height: ".$param['height'].",
               width: ".$param['width'].",
               draggable: true,
               resizeable: true,
               open: function(ev, ui){
               $('#Iframe$domid').attr('src','$url');},";
      if ($param['reloadonclose']) {
         $out .= "close: function(ev, ui) { window.location.reload() },";
      }

      $out.= "title: \"".addslashes($param['title'])."\"});
            </script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


   /**
    * Input text used as search system in ajax system
    *
    * @param $id   ID of the ajax item
    * @param $size size of the input text field (default 4)
    * @deprecated since version 0.85
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
    * @deprecated since version 0.85
   **/
   static function getSearchTextForDropdown($id, $size=4) {
      global $CFG_GLPI;

      //TRANS: %s is the character used as wildcard in ajax search
//       return "<input title=\"".sprintf(__s('Search (%s for all)'), $CFG_GLPI["ajax_wildcard"]).
//              "\" type='text' ondblclick=\"this.value='".
//              $CFG_GLPI["ajax_wildcard"]."';\" id='search_$id' name='____data_$id' size='$size'>\n";
      return "<input title=\"".sprintf(__s('Search (%s for all)'), '*').
             "\" type='text' ondblclick=\"this.value='*';\" id='search_$id' name='____data_$id' size='$size'>\n";
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
    * @param $type                     itemtype for active tab
    * @param $ID                       ID of element for active tab (default 0)
    * @param $orientation              orientation of tabs (default vertical may also be horizontal)
    *
    * @return nothing
   **/
   static function createTabs($tabdiv_id='tabspanel', $tabdivcontent_id='tabcontent', $tabs=array(),
                              $type, $ID=0, $orientation='vertical') {
      global $CFG_GLPI;

      /// TODO need to clean params !!
      $active_tabs = Session::getActiveTab($type);

      $rand = mt_rand();
      if (count($tabs) > 0) {
         echo "<div id='tabs$rand' class='center $orientation'>";
         if (CommonGLPI::isLayoutWithMain() 
             && !CommonGLPI::isLayoutExcludedPage()) {
            $orientation = 'horizontal';
         }
         echo "<ul>";
         $current = 0;
         $selected_tab = 0;
         foreach ($tabs as $key => $val) {
            if ($key == $active_tabs) {
               $selected_tab = $current;
            }
            echo "<li><a title=\"".
                 str_replace(array("<sup class='tab_nb'>", '</sup>'),'',$val['title'])."\" ";
            echo " href='".$val['url'].(isset($val['params'])?'?'.$val['params']:'')."'>";
            // extract sup information
//             $title = '';
//             $limit = 16;
            // No title strip for horizontal menu
//             if ($orientation == 'vertical') {
//                if (preg_match('/(.*)(<sup>.*<\/sup>)/',$val['title'], $regs)) {
//                   $title = Html::resume_text(trim($regs[1]),$limit-2).$regs[2];
//                } else {
//                   $title = Html::resume_text(trim($val['title']),$limit);
//                }
//             } else {
               $title = $val['title'];
//             }
            echo $title."</a></li>";
            $current ++;
         }
         echo "</ul>";
         echo "</div>";
         echo "<div id='loadingtabs$rand' class='invisible'>".
              "<div class='loadingindicator'>".__s('Loading...')."</div></div>";
         $js = "
         forceReload$rand = false;
         $('#tabs$rand').tabs({
            active: $selected_tab,
            // Loading indicator
            beforeLoad: function (event, ui) {
               if ($(ui.panel).html()
                   && !forceReload$rand) {
                  event.preventDefault();
               } else {
                  ui.panel.html($('#loadingtabs$rand').html());
                  forceReload$rand = false;
                }
            },
            ajaxOptions: {type: 'POST'},
            activate : function( event, ui ) {
               // Get future value
               var newIndex = ui.newTab.parent().children().index(ui.newTab);
               $.get('".$CFG_GLPI['root_doc']."/ajax/updatecurrenttab.php',
                  { itemtype: '$type', id: '$ID', tab: newIndex });
            }
         });";

         if ($orientation=='vertical') {
            $js .=  "$('#tabs$rand').tabs().addClass( 'ui-tabs-vertical ui-helper-clearfix' );";
         }

         if (CommonGLPI::isLayoutWithMain()
             && !CommonGLPI::isLayoutExcludedPage()) {
            $js .=  "$('#tabs$rand').scrollabletabs();";
         } else {
            $js .=  "$('#tabs$rand').removeClass( 'ui-corner-top' ).addClass( 'ui-corner-left' );";
         }

         $js .=  "// force reload
            function reloadTab(add) {
               forceReload$rand = true;
               var current_index = $('#tabs$rand').tabs('option','active');
               // Save tab
               currenthref = $('#tabs$rand ul>li a').eq(current_index).attr('href');
               $('#tabs$rand ul>li a').eq(current_index).attr('href',currenthref+'&'+add);
               $('#tabs$rand').tabs( 'load' , current_index);
               // Restore tab
               $('#tabs$rand ul>li a').eq(current_index).attr('href',currenthref);
            };";
         echo Html::scriptBlock($js);
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
//          $forceloadfor = array($CFG_GLPI['ajax_wildcard']);
         $forceloadfor = array('*');
      }
      // Need to define min size for text search
      if ($minsize < 0) {
//          $minsize = $CFG_GLPI['ajax_min_textsearch_load'];
         $minsize = 0;
      }
      if ($buffertime < 0) {
         $buffertime = 0;
//         $buffertime = $CFG_GLPI['ajax_buffertime_load'];
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
            if ($buffertime > 0) {
               $output .= "var last$zone$event = 0;";
            }
            $output .= Html::jsGetElementbyID(Html::cleanId($zone)).".on(
                '$event',
                function(event) {";
                  /// TODO manage buffer time !! ?
                  if ($buffertime > 0) {
//                      $output.= "var elapsed = new Date().getTime() - last$zone$event;
//                            last$zone$event = new Date().getTime();
//                            if (elapsed < $buffertime) {
//                               return;
//                            }";
                  }

                  $condition = '';
                  if ($minsize >= 0) {
                     $condition = Html::jsGetElementbyID(Html::cleanId($zone)).".val().length >= $minsize ";
                  }
                  if (count($forceloadfor)) {
                     foreach ($forceloadfor as $value) {
                        if (!empty($condition)) {
                           $condition .= " || ";
                        }
                        $condition .= Html::jsGetElementbyID(Html::cleanId($zone)).".val() == '$value'";
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
    * @param $options    array    of options
    *  - toupdate : array / Update a specific item on select change on dropdown
    *               (need value_fieldname, to_update,
    *                url (@see Ajax::updateItemOnSelectEvent for information)
    *                and may have moreparams)
    * @param $display    boolean  display or get string (default true)
   **/
   static function commonDropdownUpdateItem($options, $display=true) {

      $field     = '';
      $fieldname = '';

      $output    = '';
      // Old scheme
      if (isset($options["update_item"])
          && (is_array($options["update_item"]) || (strlen($options["update_item"]) > 0))) {
         $field     = "update_item";
         $fieldname = 'myname';
      }
      // New scheme
      if (isset($options["toupdate"])
          && (is_array($options["toupdate"]) || (strlen($options["toupdate"]) > 0))) {
         $field     = "toupdate";
         $fieldname = 'name';
      }

      if (!empty($field)) {
         $datas = $options[$field];
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

             $output .= self::updateItemOnSelectEvent("dropdown_".$options["name"].$options["rand"],
                                                      $data['to_update'], $data['url'],
                                                      $paramsupdate, $display);
            }
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

      $out = Html::jsGetElementbyID($toupdate).".load('$url'\n";
      if (count($parameters)) {
         $out .= ",{";
         $first = true;
         foreach ($parameters as $key => $val) {
            if ($first) {
               $first = false;
            } else {
               $out .= ",";
            }

            $out .= $key.":";
            if (!is_array($val) && preg_match('/^__VALUE(\d+)__$/',$val,$regs)) {
               $out .=  Html::jsGetElementbyID(Html::cleanId($toobserve[$regs[1]])).".val()";

            } else if (!is_array($val) && $val==="__VALUE__") {
               $out .=  Html::jsGetElementbyID(Html::cleanId($toobserve)).".val()";

            } else {
               $out .=  json_encode($val);
            }
         }
         $out .= "}\n";

      }
      $out.= ")\n";
      if ($display) {
         echo $out;
      } else {
         return $out;
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
