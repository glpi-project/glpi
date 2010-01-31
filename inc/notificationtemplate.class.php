<?php
/*
 * @version $Id: notification.class.php 10030 2010-01-05 11:11:22Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class Notification
class NotificationTemplate extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['mailing'][113];
   }


   function defineTabs($options=array()){
      global $LANG;

      $tabs[1] = $LANG['common'][12];
      if ($this->fields['id'] > 0) {
         $tabs[12]=$LANG['title'][38];
      }

      return $tabs;
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'r');
   }

   function showForm($ID, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

     $spotted = false;
      if (empty ($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }
      $this->showTabs($options);
      $this->showFormHeader($options);

     //echo "<div id='contenukb'>";
      echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"].
             "/lib/tiny_mce/tiny_mce.js'></script>";
      echo "<script language='javascript' type='text/javascript''>";
      echo "tinyMCE.init({
         language : '".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]."',
         mode : 'exact',
         elements: 'content_html',
         plugins : 'table,directionality,paste,safari,searchreplace',
         theme : 'advanced',
         entity_encoding : 'numeric', ";
         // directionality + search replace plugin
      echo "theme_advanced_buttons1_add : 'ltr,rtl,search,replace',";
      echo "theme_advanced_toolbar_location : 'top',
         theme_advanced_toolbar_align : 'left',
         theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent',
         theme_advanced_buttons2 : 'forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator',
         theme_advanced_buttons3 : ''});";
      echo "</script>";


      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td rowspan='4' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='4'><textarea cols='45' rows='9' name='comment' >"
         .$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][41] . "&nbsp;:</td><td>";
      $language = ($this->fields['language'] !=''?$this->fields['language']:
                                                    $_SESSION['glpilanguage']);
      Dropdown::showLanguages("language", $language);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['mailing'][114] . "&nbsp;:</td><td>";
      Dropdown::showYesNo('is_default',$this->fields['is_default']);
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][17] . "</td><td>";
      Dropdown::dropdownTypes("itemtype",
                              ($this->fields['itemtype']?$this->fields['itemtype']:'Ticket'),
                              $CFG_GLPI["notificationtemplates_types"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['knowbase'][14] . "&nbsp;:</td><td colspan='3'>";
      echo "<textarea cols='100' rows='2' name='subject' >"
         .$this->fields["subject"]."</textarea></td></tr>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['mailing'][115]. ' '. $LANG['mailing'][117].
           "&nbsp;:</td><td colspan='3'>";
      echo "<textarea cols='100' rows='9' name='content_text' >"
         .$this->fields["content_text"]."</textarea></td></tr>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['mailing'][115]. ' '. $LANG['mailing'][116].
           "&nbsp;:</td><td colspan='3'>";
      echo "<textarea cols='100' rows='9' name='content_html' >"
         .$this->fields["content_html"]."</textarea></td></tr>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_notificationtemplates';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'NotificationTemplate';

      $tab[2]['table']         = 'glpi_notificationtemplates';
      $tab[2]['field']         = 'is_default';
      $tab[2]['linkfield']     = '';
      $tab[2]['name']          = $LANG['mailing'][114];
      $tab[2]['datatype']      = 'bool';

      $tab[3]['table']         = 'glpi_notificationtemplates';
      $tab[3]['field']         = 'language';
      $tab[3]['linkfield']     = '';
      $tab[3]['name']          = $LANG['setup'][41];
      $tab[3]['datatype']      = 'language';

      $tab[4]['table']         = 'glpi_notificationtemplates';
      $tab[4]['field']         = 'itemtype';
      $tab[4]['linkfield']     = '';
      $tab[4]['name']          = $LANG['common'][17];
      $tab[4]['datatype']      = 'itemtypename';

      $tab[5]['table']         = 'glpi_notificationtemplates';
      $tab[5]['field']         = 'subject';
      $tab[5]['linkfield']     = '';
      $tab[5]['name']          = $LANG['knowbase'][14];
      $tab[5]['datatype']     = 'text';

      $tab[6]['table']         = 'glpi_notificationtemplates';
      $tab[6]['field']         = 'content_html';
      $tab[6]['linkfield']     = '';
      $tab[6]['name']          = $LANG['mailing'][115]. ' '. $LANG['mailing'][116];
      $tab[6]['datatype']     = 'text';

      $tab[7]['table']         = 'glpi_notificationtemplates';
      $tab[7]['field']         = 'content_text';
      $tab[7]['linkfield']     = '';
      $tab[7]['name']          = $LANG['mailing'][115]. ' '. $LANG['mailing'][117];
      $tab[7]['datatype']     = 'text';

      $tab[8]['table']     = 'glpi_notificationtemplates';
      $tab[8]['field']     = 'comment';
      $tab[8]['linkfield'] = 'comment';
      $tab[8]['name']      = $LANG['common'][25];
      $tab[8]['datatype']  = 'text';

      return $tab;
   }

   function prepareInputForAdd($input) {
      return NotificationTemplate::cleanContentHtml($input);
   }

   static function cleanContentHtml($input) {
      if (!$input['content_text']) {
         $input['content_text'] = html_clean(unclean_cross_side_scripting_deep($input['content_html']));
      }
      return $input;
   }

   function prepareInputForUpdate($input) {
      return NotificationTemplate::cleanContentHtml($input);
   }

   /**
    * Display templates available for an itemtype
    * @param name the dropdown name
    * @param itemtype display templates for this itemtype only
    * @param value the dropdown's default value (0 by default)
    */
   static function dropdownTemplates($name,$itemtype,$value=0) {
      global $DB;

      Dropdown::show('NotificationTemplate',array('name'=>$name,'value'=>$value,'comment'=>1,
                           'condition'=>"`itemtype`='$itemtype'"));
   }

   /**
    * Get default template for an itemtype
    */
   static function getDefault($itemtype='') {
      global $DB;

      if ($itemtype != '') {
         foreach ($DB->request('glpi_notificationtemplates', array('is_default'=>1,
                                                                   'itemtype'=>$itemtype)) as $data) {
            return $data['id'];
         }
      }
      return 0;
   }

   function post_updateItem($history=1) {
      global $DB;

      if (in_array('is_default',$this->updates) && $this->input["is_default"]==1) {
         $query = "UPDATE ".
                   $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'
                      AND itemtype='".$this->getType()."'";
         $DB->query($query);
      }
   }

   function getTemplateByLanguage(NotificationTarget $target, $language, $event) {
      $lang = array();

      //Switch to the desired language
      loadLanguage($language);
      //Get template's language data for in this language
      $data = $target->getDatasForTemplate($event);
      //Restore default language
      loadLanguage();

      //Template processing
      $lang['subject'] = strtr($this->getField('subject'),$data);
      $lang['content_html'] =  NotificationTemplate::process($this->getField('content_html'),$data);
      $lang['content_text'] =  NotificationTemplate::process($this->getField('content_text'),$data);
      return $lang;
   }

   static function process ($string, $data) {
      $offset = $new_offset = 0;
      //Template processed
      $output = "";

      //Remove all
      $string = unclean_cross_side_scripting_deep($string);
      $string = preg_replace(array('/\r/','/\r\n/','/\n/'),array('','',''),$string);


      //First of all process the FOREACH tag
      if (preg_match_all("/##FOREACH([a-zA-Z-0-9\.]*)##/i",$string,$out)) {
         foreach ($out[1] as $id => $tag_infos) {

            $regex= "/##FOREACH".$tag_infos."##(.*)##ENDFOREACH".$tag_infos."##/";
            preg_match($regex,$string,$tag_out);
            logDebug("TAG=$tag_infos");
            if (isset($data[$tag_infos]) && is_array($data[$tag_infos])) {

               $data_lang_foreach = $data;
               unset($data_lang_foreach[$tag_infos]);

               $output_foreach_string = "";
               foreach ($data[$tag_infos] as $line) {
                  foreach ($line as $field => $value) {
                     $data_lang_foreach[$field] = $value;
                  }

                  foreach ($data_lang_foreach as $field=>$value) {
                     if (is_array($value)) {
                        unset($data_lang_foreach[$field]);
                     }
                  }
                  $tmp = NotificationTemplate::processIf($tag_out[1],$data_lang_foreach);
                  $output_foreach_string.=strtr($tmp,$data_lang_foreach);
               }
               $string = str_replace($tag_out[0],$output_foreach_string,$string);
         }
         else {
            $string = str_replace($tag_out,'',$string);
         }
      }
   }

   foreach ($data as $field=>$value) {
      if (is_array($value)) {
          unset($data[$field]);
      }
   }

   //Now process IF statements
   $string = NotificationTemplate::processIf($string,$data);
   $string= strtr($string,$data);

   return $string;
   }

   private static function processIf($string, $data) {


      foreach ($data as $field=>$value) {
         if (is_array($value)) {
            unset($data[$field]);
         }
      }

      if (preg_match_all("/##IF([a-z\.]*)##/i",$string,$out)) {
         foreach ($out[1] as $tag_infos) {
            $if_field = $tag_infos;

            //Get the field tag value (if one)
            $regex_if= "/##IF".$if_field."##(.*)##ENDIF".$if_field."##/";

            //Get the else tag value (if one)
            $regex_else= "/##ELSE".$if_field."##(.*)##ENDELSE".$if_field."##/";

            //If field exists in template's data -> replace the IF sentence
            if (preg_match($regex_if,$string,$tag_if_out)) {
               if (isset($data['##'.$if_field.'##']) && $data['##'.$if_field.'##'] != '') {
                  $replace = strtr($tag_if_out[1],$data);
                  $tmp = array($tag_if_out[0]=>$replace);

                  //Now check if there's an else statement
                  if (preg_match($regex_else,$string,$tag_else_out)) {
                     $tmp[$tag_else_out[0]] = '';
                  }
               }
               else {
                  $tmp[$tag_if_out[0]] = '';

                  //If an ELSE statement exists -> use it
                  if (preg_match($regex_else,$string,$tag_else_out)) {
                     $replace = strtr($tag_else_out[1],$data);
                     $tmp[$tag_else_out[0]]= $replace;
                  }
                  else {
                  }
               }
               $string = strtr($string,$tmp);
            }
         }
      }

      return $string;
   }
}
?>
