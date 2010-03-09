<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

   //Signature to add to the template
   public $signature = '';

   //Store templates for each language
   public $templates_by_languages = array();

   static function getTypeName() {
      global $LANG;

      return $LANG['mailing'][113];
   }


   function defineTabs($options=array()){
      global $LANG;

      $tabs[1] = $LANG['common'][12];
      if ($this->fields['id'] > 0) {
         $tabs[12] = $LANG['title'][38];
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
      global $LANG, $CFG_GLPI;

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

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
      echo "<td colspan='3'>";
      autocompletionTextField($this, "name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][17] . "&nbsp;:</td><td colspan='3'>";
      Dropdown::dropdownTypes("itemtype",
                              ($this->fields['itemtype']?$this->fields['itemtype']:'Ticket'),
                              $CFG_GLPI["notificationtemplates_types"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td colspan='3'><textarea cols='45' rows='9' name='comment' >".$this->fields["comment"].
            "</textarea></td></tr>";

      $this->showFormButtons($options);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['linkfield']     = '';
      $tab[4]['name']          = $LANG['common'][17];
      $tab[4]['datatype']      = 'itemtypename';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      return $tab;
   }


   /**
    * Display templates available for an itemtype
    * @param $name the dropdown name
    * @param $itemtype display templates for this itemtype only
    * @param $value the dropdown's default value (0 by default)
    */
   static function dropdownTemplates($name, $itemtype, $value=0) {
      global $DB;

      Dropdown::show('NotificationTemplate', array('name'      => $name,
                                                   'value'     => $value,
                                                   'comment'   => 1,
                                                   'condition' => "`itemtype`='$itemtype'"));
   }


   function getAdditionnalProcessOption($options) {

      //Additionnal option can be given for template processing
      //For the moment, only option to see private tasks & followups is available
      if (!empty($options) && isset($options['sendprivate'])) {
         return 1;
      }
      return 0;
   }


   function getTemplateByLanguage(NotificationTarget $target, $user_infos=array(), $event,
                                  $options=array()) {
      global $LANG;

      $lang = array();

      $language = $user_infos['language'];

      if (isset($user_infos['additionnaloption'])) {
         $additionnaloption =  $user_infos['additionnaloption'];
      } else {
         $additionnaloption =  NotificationTarget::NO_OPTION;
      }

      if (!isset($this->templates_by_languages[$additionnaloption][$language])) {
         //Switch to the desired language
         $start = microtime(true);
         loadLanguage($language);

         //If event is raised by a plugin, load it in order to get the language file available
         if ($plug = isPluginItemType(get_class($target->obj))) {
            Plugin::loadLang(strtolower($plug['plugin']),$language);
         }

         //Get template's language data for in this language
         $options['additionnaloption'] = $additionnaloption;
         $data = &$target->getForTemplate($event,$options);

         //Restore default language
         loadLanguage();

         if ($template_datas = $this->getByLanguage($language)) {
            //Template processing
            $lang['subject'] = $target->getSubjectPrefix() .
                               NotificationTemplate::process($template_datas['subject'], $data);
            $lang['content_html'] = '';
            //If no html content, then send only in text
            if (!empty($template_datas['content_html'])) {
               $lang['content_html'] =
                     "<html><body>".NotificationTemplate::process($template_datas['content_html'],
                                                                  $data).
                     "<br /><br />".nl2br($this->signature)."</body></html>";
            }

            $lang['content_text'] = NotificationTemplate::process($template_datas['content_text'],
                                                                  $data).
                                    "\n\n".$this->signature;
            $this->templates_by_languages[$additionnaloption][$language] = $lang;
         }
      }

      return isset($this->templates_by_languages[$additionnaloption][$language]);
   }


   static function process ($string, $data) {

      $offset = $new_offset = 0;
      //Template processed
      $output = "";

      //Remove all
      $string = unclean_cross_side_scripting_deep($string);

      //First of all process the FOREACH tag
      if (preg_match_all("/##FOREACH[ ]?(FIRST|LAST)?[ ]?([0-9]*)?[ ]?([a-zA-Z-0-9\.]*)##/i",
                         $string, $out)) {

         foreach ($out[3] as $id => $tag_infos) {
            $regex = "/".$out[0][$id]."(.*)##ENDFOREACH".$tag_infos."##/is";

            if (preg_match($regex,$string,$tag_out)
                && isset($data[$tag_infos])
                && is_array($data[$tag_infos])) {

               $data_lang_foreach = $data;
               unset($data_lang_foreach[$tag_infos]);

               //Manage FIRST & LAST statement
               $foreachvalues = $data[$tag_infos];
               if (!empty($foreachvalues)) {
                  if (isset($out[1][$id]) && $out[1][$id] != '') {
                     if ($out[1][$id] == 'FIRST') {
                        $foreachvalues = array_reverse($foreachvalues);
                     }
                     if (isset ($out[2][$id]) && $out[2][$id]) {
                        $foreachvalues = array_slice($foreachvalues,0,$out[2][$id]);
                     } else {
                        $foreachvalues = array_slice($foreachvalues,0,1);
                     }
                  }
               }

               $output_foreach_string = "";
               foreach ($foreachvalues as $line) {
                  foreach ($line as $field => $value) {
                     if (!is_array($value)) {
                        $data_lang_foreach[$field] = $value;
                     }
                  }
                  $tmp = NotificationTemplate::processIf($tag_out[1],$data_lang_foreach);
                  $output_foreach_string .= strtr($tmp,$data_lang_foreach);
               }
               $string = str_replace($tag_out[0],$output_foreach_string,$string);
         } else {
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
   $string = strtr($string,$data);

   return $string;
   }


   static function processIf($string, $data) {

      if (preg_match_all("/##IF([a-z\.]*)[=]?(\w*)##/i",$string,$out)) {
//         print_r($out);
         foreach ($out[1] as $key => $tag_infos) {
            $if_field = $tag_infos;
            //Get the field tag value (if one)
            $regex_if = "/##IF".$if_field."[=]?\w*##(.*)##ENDIF".$if_field."##/is";

            //Get the else tag value (if one)
            $regex_else = "/##ELSE".$if_field."[=]?\w*##(.*)##ENDELSE".$if_field."##/is";

            if (empty($out[2][$key])){ // No = : check if ot empty or not null
               if (isset($data['##'.$if_field.'##'])
                  && $data['##'.$if_field.'##'] != ''
                  && $data['##'.$if_field.'##'] != '&nbsp;'
                  && !is_null($data['##'.$if_field.'##'])) {
                  $condition_ok=true;
               } else {
                  $condition_ok=false;
               }
            } else { // check exact match
               if (isset($data['##'.$if_field.'##'])
                  && $data['##'.$if_field.'##'] == $out[2][$key]) {
                  $condition_ok=true;
               } else {
                  $condition_ok=false;
               }
               
            }
            if ($condition_ok){ // Do IF
               $string = preg_replace($regex_if, "\\1", $string);
               $string = preg_replace($regex_else, "",  $string);
            } else { // Do ELSE
               $string = preg_replace($regex_if, "", $string);
               $string = preg_replace($regex_else, "\\1",  $string);
            }
         }
      }
      return $string;
   }


   function setSignature($signature) {
      $this->signature = $signature;
   }

   function getByLanguage($language) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_notificationtemplatetranslations`
                WHERE `notificationtemplates_id` = '".$this->getField('id')."'
                      AND `language` IN ('$language','')
                ORDER BY `language` DESC
                LIMIT 1";

      $iterator = $DB->request($query);
      if ($iterator->numrows()) {
         return $iterator->next();
      }
      //No template found at all!
      return false;
   }


   function getDataToSend(NotificationTarget $target, $user_infos, $options) {

      $language   = $user_infos['language'];
      $user_email = $user_infos['email'];

      $mailing_options['to']      = $user_email;
      $mailing_options['from']    = $target->getSender();
      $mailing_options['replyto'] = $target->getReplyTo();

      if (isset($user_infos['additionnaloption'])) {
         $additionnaloption =  $user_infos['additionnaloption'];
      } else {
         $additionnaloption =  NotificationTarget::NO_OPTION;
      }

      $template_data = $this->templates_by_languages[$additionnaloption][$language];
      $mailing_options['subject']      = $template_data['subject'];
      $mailing_options['content_html'] = $template_data['content_html'];
      $mailing_options['content_text'] = $template_data['content_text'];
      $mailing_options['items_id']     = $target->obj->getField('id');

      return $mailing_options;
   }

}
?>
