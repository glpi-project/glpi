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
 * NotificationTemplate Class
**/
class NotificationTemplate extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   //Signature to add to the template
   public $signature = '';

   //Store templates for each language
   public $templates_by_languages = array();

   static $rightname = 'config';



   static function getTypeName($nb=0) {
      return _n('Notification template', 'Notification templates', $nb);
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('NotificationTemplateTranslation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Reset already computed templates
   **/
   function resetComputedTemplates() {
      $this->templates_by_languages = array();
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }

     $spotted = false;

      if (empty($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }

      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Type') . "</td><td colspan='3'>";
      Dropdown::showItemTypes('itemtype', $CFG_GLPI["notificationtemplates_types"],
                              array('value' => ($this->fields['itemtype']
                                                ?$this->fields['itemtype'] :'Ticket')));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Comments')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='60' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('CSS')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='60' rows='5' name='css' >".$this->fields["css"]."</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }


   function getSearchOptions() {

      $tab                     = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = __('Type');
      $tab[4]['datatype']      = 'itemtypename';
      $tab[4]['itemtype_list'] = 'notificationtemplates_types';
      $tab[4]['massiveaction'] = false;

      $tab[16]['table']        = $this->getTable();
      $tab[16]['field']        = 'comment';
      $tab[16]['name']         = __('Comments');
      $tab[16]['datatype']     = 'text';

      return $tab;
   }


   /**
    * Display templates available for an itemtype
    *
    * @param $name      the dropdown name
    * @param $itemtype  display templates for this itemtype only
    * @param $value     the dropdown's default value (0 by default)
   **/
   static function dropdownTemplates($name, $itemtype, $value=0) {
      global $DB;

      self::dropdown(array('name'       => $name,
                            'value'     => $value,
                            'comment'   => 1,
                            'condition' => "`itemtype`='$itemtype'"));
   }


   /**
    * @param $options
   **/
   function getAdditionnalProcessOption($options) {

      //Additionnal option can be given for template processing
      //For the moment, only option to see private tasks & followups is available
      if (!empty($options)
          && isset($options['sendprivate'])) {
         return 1;
      }
      return 0;
   }


   /**
    * @param $target             NotificationTarget object
    * @param $user_infos   array
    * @param $event
    * @param $options      array
    *
    * @return id of the template in templates_by_languages / false if computation failed
   **/
   function getTemplateByLanguage(NotificationTarget $target, $user_infos=array(), $event,
                                  $options=array()) {

      $lang     = array();
      $language = $user_infos['language'];

      if (isset($user_infos['additionnaloption'])) {
         $additionnaloption =  $user_infos['additionnaloption'];
      } else {
         $additionnaloption =  array();
      }
      
      $tid  = $language;
      $tid .= serialize($additionnaloption);

      $tid  = sha1($tid);

      if (!isset($this->templates_by_languages[$tid])) {
         //Switch to the desired language
         $start = microtime(true);
         Session::loadLanguage($language);

         //If event is raised by a plugin, load it in order to get the language file available
         if ($plug = isPluginItemType(get_class($target->obj))) {
            Plugin::loadLang(strtolower($plug['plugin']),$language);
         }

         //Get template's language data for in this language
         $options['additionnaloption'] = $additionnaloption;
         $data = &$target->getForTemplate($event,$options);

         $footer_string = Html::entity_decode_deep(sprintf(__('Automatically generated by GLPI %s'), GLPI_VERSION));

         //Restore default language
         Session::loadLanguage();
         if ($plug = isPluginItemType(get_class($target->obj))) {
            Plugin::loadLang(strtolower($plug['plugin']));
         }

         if ($template_datas = $this->getByLanguage($language)) {
            //Template processing

            // Decode html chars to have clean text
            $template_datas['content_text']
                                       = Html::entity_decode_deep($template_datas['content_text']);
            $save_data                 = $data;
            $data                      = Html::entity_decode_deep($data);

            $template_datas['subject'] = Html::entity_decode_deep($template_datas['subject']);
            $this->signature           = Html::entity_decode_deep($this->signature);

            $lang['subject']           = $target->getSubjectPrefix($event) .
                                          self::process($template_datas['subject'], $data);
            $lang['content_html']      = '';
            $add_header                = Html::entity_decode_deep($target->getContentHeader());
            $add_footer                = Html::entity_decode_deep($target->getContentFooter());

            //If no html content, then send only in text
            if (!empty($template_datas['content_html'])) {
               // Encode in HTML all chars
               $data_html = Html::entities_deep($data);
               $data_html = Html::nl2br_deep($data_html);
               // Restore HTML tags
               if (count($target->html_tags)) {
                  foreach ($target->html_tags as $tag) {
                     if (isset($save_data[$tag])) {
                        $data_html[$tag] = $save_data[$tag];
                     }
                  }
               }

               $signature_html = Html::entities_deep($this->signature);
               $signature_html = Html::nl2br_deep($signature_html);

               $template_datas['content_html'] = self::process($template_datas['content_html'],
                                                               $data_html);

               $lang['content_html'] =
                     "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>".
                     "<html>
                        <head>
                         <META http-equiv='Content-Type' content='text/html; charset=utf-8'>
                         <title>".Html::entities_deep($lang['subject'])."</title>
                         <style type='text/css'>
                           ".$this->fields['css']."
                         </style>
                        </head>
                        <body>\n".(!empty($add_header)?$add_header."\n<br><br>":'').
                        $template_datas['content_html'].
                     "<br><br>-- \n<br>".$signature_html.
                     //TRANS %s is the GLPI version
                     "<br>$footer_string".
                     "<br><br>\n".(!empty($add_footer)?$add_footer."\n<br><br>":'').
                     "\n</body></html>";
            }

            $lang['content_text']
                  = (!empty($add_header)?$add_header."\n\n":'').                    Html::clean(self::process($template_datas['content_text'],
                                $data)."\n\n-- \n".$this->signature.
                                      "\n".Html::entity_decode_deep(sprintf(__('Automatically generated by GLPI %s'), GLPI_VERSION)))."\n\n".$add_footer;
            $this->templates_by_languages[$tid] = $lang;
         }
      }
      if (isset($this->templates_by_languages[$tid])) {
         return $tid;
      }
      return false;
   }


   /**
    * @param $string
    * @param $data
   **/
   static function process($string, $data) {

      $offset = $new_offset = 0;
      //Template processed
      $output = "";

      $cleandata = array();
      // clean data for strtr
      foreach ($data as $field => $value) {
         if (!is_array($value)) {
            $cleandata[$field] = $value;
         }
      }

      //Remove all
      $string = Toolbox::unclean_cross_side_scripting_deep($string);

      //First of all process the FOREACH tag
      if (preg_match_all("/##FOREACH[ ]?(FIRST|LAST)?[ ]?([0-9]*)?[ ]?([a-zA-Z-0-9\.]*)##/i",
                         $string, $out)) {

         foreach ($out[3] as $id => $tag_infos) {
            $regex = "/".$out[0][$id]."(.*)##ENDFOREACH".$tag_infos."##/Uis";

            if (preg_match($regex,$string,$tag_out)
                && isset($data[$tag_infos])
                && is_array($data[$tag_infos])) {

               $data_lang_foreach = $cleandata;
               unset($data_lang_foreach[$tag_infos]);

               //Manage FIRST & LAST statement
               $foreachvalues = $data[$tag_infos];
               if (!empty($foreachvalues)) {
                  if (isset($out[1][$id]) && ($out[1][$id] != '')) {

                     if ($out[1][$id] == 'FIRST') {
                        $foreachvalues = array_reverse($foreachvalues);
                     }

                     if (isset($out[2][$id]) && $out[2][$id]) {
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
                  $tmp                    = self::processIf($tag_out[1], $data_lang_foreach);
                  $output_foreach_string .= strtr($tmp, $data_lang_foreach);
               }
               $string = str_replace($tag_out[0],$output_foreach_string, $string);

            } else {
               $string = str_replace($tag_out, '', $string);
            }
         }
      }

      //Now process IF statements
      $string = self::processIf($string, $cleandata);
      $string = strtr($string, $cleandata);

      return $string;
   }


   /**
    * @param $string
    * @param $data
   **/
   static function processIf($string, $data) {

      if (preg_match_all("/##IF([a-z\.]*)[=]?(.*?)##/i",$string,$out)) {
         foreach ($out[1] as $key => $tag_infos) {
            $if_field = $tag_infos;
            //Get the field tag value (if one)
            $regex_if = "/##IF".$if_field."[=]?.*##(.*)##ENDIF".$if_field."##/Uis";
            //Get the else tag value (if one)
            $regex_else = "/##ELSE".$if_field."[=]?.*##(.*)##ENDELSE".$if_field."##/Uis";

            if (empty($out[2][$key]) && !strlen($out[2][$key]) ) { // No = : check if ot empty or not null

               if (isset($data['##'.$if_field.'##'])
                   && $data['##'.$if_field.'##'] != '0'
                   && $data['##'.$if_field.'##'] != ''
                   && $data['##'.$if_field.'##'] != '&nbsp;'
                   && !is_null($data['##'.$if_field.'##'])) {

                  $condition_ok = true;

               } else {
                  $condition_ok = false;
               }

            } else { // check exact match

               if (isset($data['##'.$if_field.'##'])
                   && (Html::entity_decode_deep($data['##'.$if_field.'##'])
                           == Html::entity_decode_deep($out[2][$key]))) {

                  $condition_ok = true;

               } else {
                  $condition_ok = false;
               }
            }

            // Force only one replacement to permit multiple use of the same condition
            if ($condition_ok) { // Do IF
               $string = preg_replace($regex_if, "\\1", $string,1);
               $string = preg_replace($regex_else, "",  $string,1);

            } else { // Do ELSE
               $string = preg_replace($regex_if, "", $string,1);
               $string = preg_replace($regex_else, "\\1",  $string,1);
            }
         }
      }
      return $string;
   }


   /**
    * @param $signature
   **/
   function setSignature($signature) {
      $this->signature = $signature;
   }


   /**
    * @param $language
   **/
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


   /**
    * @param $target              NotificationTarget object
    * @param $tid          string template computed id
    * @param $user_infos   array
    * @param $options      array
   **/
   function getDataToSend(NotificationTarget $target, $tid, array $user_infos, array $options) {

      $language   = $user_infos['language'];
      $user_email = $user_infos['email'];
      $user_name  = $user_infos['username'];

      $sender     = $target->getSender($options);
      $replyto    = $target->getReplyTo($options);

      $mailing_options['to']          = $user_email;
      $mailing_options['toname']      = $user_name;
      $mailing_options['from']        = $sender['email'];
      $mailing_options['fromname']    = $sender['name'];
      $mailing_options['replyto']     = $replyto['email'];
      $mailing_options['replytoname'] = $replyto['name'];
      $mailing_options['messageid']   = $target->getMessageID();

      $template_data    = $this->templates_by_languages[$tid];
      $mailing_options['subject']      = $template_data['subject'];
      $mailing_options['content_html'] = $template_data['content_html'];
      $mailing_options['content_text'] = $template_data['content_text'];
      $mailing_options['items_id']     = $target->obj->getField('id');
      if ($target->obj->getType() == 'Ticket') {
         if (isset($target->obj->documents)) {
            $mailing_options['documents'] = $target->obj->documents;
         }
      }

      return $mailing_options;
   }

}
?>
