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


/**
 * @file
 * @brief
 */
if (!defined('GLPI_ROOT')) {

   die("Sorry. You can't access this file directly");

}


/**
 * NotificationTemplate Class
 *
 **/
class NotificationTemplate extends CommonDBTM {


   // From CommonDBTM
   public $dohistory = true;
   
   
   // Signature to add to the template
   public $signature = '';
   
   
   // Store templates for each language
   public $templates_by_languages = array();
   
   
   // 
   static $rightname = 'config';
   
   
   /**
    * Return Type Name
    *
    * @param $nb
    *
    * @return mixed
    **/
   static function getTypeName($nb = 0) {
   
      return _n('Notification template', 'Notification templates', $nb);
   
   }
   
   
   /**
    * Can create
    *
    * @return mixed
    **/
   static function canCreate() {
   
      return static::canUpdate();
   
   }
   
   
   /**
    * Can Purge
    * @since version 0.85
    *
    * @return mixed
    **/
   static function canPurge() {
   
      return static::canUpdate();
   
   }
   
   
   /**
    * Define Tabs
    *
    * @param options array Options
    *
    * @return array
    **/
   function defineTabs($options = array()) {
   
      $ong = array();
      
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('NotificationTemplateTranslation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      
      return $ong;
   
   }
   
   
   /**
    * Reset already computed templates
    *
    * @return boolean
    **/
   function resetComputedTemplates() {
   
      $this->templates_by_languages = array();
      
      return true;
   
   }
   
   
   /**
    * Show HTML Form
    *
    * @param $ID
    * @param $options array Options
    *
    * @return boolean
    **/
   function showForm($ID, $options = array()) {
   
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
      
      echo '<tr class="tab_bg_1">' . "\n";
      
      echo '<td>' . __('Name') . '</td>' . "\n";
      
      echo '<td colspan="3">';
      Html::autocompletionTextField($this, 'name');
      echo '</td>' . "\n";
      
      echo '</tr>' . "\n";
      
      
      echo '<tr class="tab_bg_1">' . "\n";
      
      echo '<td>' . __('Type') . '</td>' . "\n";
      
      echo '<td colspan="3">';
      Dropdown::showItemTypes('itemtype', $CFG_GLPI['notificationtemplates_types'], array('value' => ($this->fields['itemtype']?$this->fields['itemtype'] :'Ticket')));
      echo '</td>' . "\n";
      
      echo '</tr>' . "\n";
      
      
      echo '<tr class="tab_bg_1">' . "\n";
      
      echo '<td>' . __('Comments') . '</td>' . "\n";
      
      echo '<td colspan="3">';
      echo '<textarea cols="60" rows="5" name="comment" >' . $this->fields['comment'] . '</textarea>';
      echo '</td>' . "\n";
      
      echo '</tr>' . "\n";
      
      
      echo '<tr class="tab_bg_1">' . "\n";
      
      echo '<td>' . __('CSS') . '</td>' . "\n";
      
      echo '<td colspan="3">';
      echo '<textarea cols="60" rows="5" name="css">' . $this->fields['css'] . '</textarea>';
      echo '</td>' . "\n";
      
      echo '</tr>' . "\n";
      
      $this->showFormButtons($options);
      
      return true;
   
   }
   
   
   /**
    * Return search options
    *
    * @return array
    **/
   function getSearchOptions() {
   
      $tab = array();
      
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
    * @param $name string The dropdown name
    * @param $itemtype string Display templates for this itemtype only
    * @param $value integer The dropdown's default value (0 by default)
    *
    * @return mixed
    **/
   static function dropdownTemplates($name, $itemtype, $value = 0) {
   
      // [???]
      global $DB;
      
      $options = array(
         'name'      => $name,
         'value'     => $value,
         'comment'   => 1,
         'condition' => "`itemtype`='$itemtype'",
      );
      
      self::dropdown($options);
   
   }
   
   
   /**
    * Return additionnal process options
    *
    * @param $options array Options
    *
    * @return integer
    **/
   function getAdditionnalProcessOption($options) {
   
      //
      $output = 0;
      
      // Additionnal option can be given for template processing
      // For the moment, only option to see private tasks & followups is available
      if (!empty($options) && isset($options['sendprivate'])) {
      
         $output = 1;
      
      }
      
      return $output;
   
   }
   
   
   /**
    * Return template by language
    *
    * @param $target NotificationTarget object Target
    * @param $user_infos array
    * @param $event
    * @param $options array
    *
    * @return mixed id of the template in templates_by_languages / false if computation failed
    **/
   function getTemplateByLanguage(NotificationTarget $target, $user_infos = array(), $event, $options = array()) {
   
      //
      $output = false;
      $lang = array();
      $language = $user_infos['language'];
      $additionnaloption = array();
      
      // Load user's additionnal option
      if (isset($user_infos['additionnaloption'])) {
      
         $additionnaloption =  $user_infos['additionnaloption'];
      
      }
      
      // Generate template id (hash)
      $tid = sha1($language . serialize($additionnaloption));
      
      // Check if template exists
      if (!isset($this->templates_by_languages[$tid])) {
      
         // [???] mesure de temps de traitement
         $start = microtime(true);
         
         // Switch to the desired language
         Session::loadLanguage($language);
         
         // If event is raised by a plugin, load it in order to get the language file available
         if ($plug = isPluginItemType(get_class($target->obj))) {
         
            Plugin::loadLang(strtolower($plug['plugin']), $language);
         
         }
         
         // Set additionnal option
         $options['additionnaloption'] = $additionnaloption;
         
         // Get template's language data for this language
         $data = &$target->getForTemplate($event, $options);
         
         // Generate GLPI Signature
         $glpi_signature = sprintf(__('Automatically generated by GLPI %s'), GLPI_VERSION);
         
         // Footer
         $footer_string = Html::entity_decode_deep($glpi_signature);
         
         // Restore default language
         Session::loadLanguage();
         
         // Restore plugin default language
         if ($plug = isPluginItemType(get_class($target->obj))) {
         
            Plugin::loadLang(strtolower($plug['plugin']));
         
         }
         
         // Read template
         if ($template_datas = $this->getByLanguage($language)) {
         
            // Decode html chars to have clean text
            $template_datas['subject']      = Html::entity_decode_deep($template_datas['subject']);
            $template_datas['content_text'] = Html::entity_decode_deep($template_datas['content_text']);
            
            $save_data = $data;
            $data = Html::entity_decode_deep($data);
            
            $this->signature = Html::entity_decode_deep($this->signature);
            
            $lang['subject']      = $target->getSubjectPrefix($event) . self::process($template_datas['subject'], $data);
            $lang['content_html'] = '';
            $lang['content_text'] = '';
            
            // 
            $add_header = Html::entity_decode_deep($target->getContentHeader());
            $add_footer = Html::entity_decode_deep($target->getContentFooter());
            
            // Prepare text contents
            $main_text = Html::clean(self::process($template_datas['content_text'], $data));
            $signature_text = $this->signature;
            
            
            // Generate text context
            $content_text = '';
            
            if (!empty($add_header)) {
            
               $content_text .= $add_header . "\n";
               $content_text .= "\n";
            
            }
            
            // Main content
            $content_text .= $main_text;
            $content_text .= "\n\n";
            
            $content_text .= '--' . "\n";
            
            // Signature
            if (!empty($signature_text)) {
            
               $content_text .= $signature_text . "\n";
            
            }
            
            // GLPI's signature
            if (!empty($footer_string)) {
            
               $content_text .= $footer_string . "\n";
            
            }
            
            $content_text .= "\n\n";
            
            if (!empty($add_footer)) {
            
               $content_text .= $add_footer;
            
            }
            
            $lang['content_text'] = $content_text;
            
            
            // If no html content, then send only in text
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
               
               // Prepare HTML contents
               $subject_html = Html::entities_deep($lang['subject']);
               $main_html = self::process($template_datas['content_html'], $data_html);
               $signature_html = Html::entities_deep($this->signature);
               $signature_html = Html::nl2br_deep($signature_html);
               
               
               // Generate HTML content
               $content_html = '';
               $content_html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
               $content_html .= '<html>' . "\n";
               
               $content_html .= '<head>' . "\n";
               $content_html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n";
               $content_html .= '<title>' . $subject_html . '</title>' . "\n";
               $content_html .= '<style type="text/css">' . $this->fields['css'] . '</style>' . "\n";
               $content_html .= '</head>' . "\n";
               
               $content_html .= '<body>' . "\n";
               
               if (!empty($add_header)) {
               
                  $content_html .= $add_header . "\n";
                  $content_html .= '<br />' . "\n";
                  $content_html .= '<br />' . "\n";
               
               }
               
               // Main content
               $content_html .= $main_html;
               $content_html .= '<br />' . "\n";
               $content_html .= '<br />' . "\n";
               
               $content_html .= '--';
               
               // Signature
               if (!empty($signature_html)) {
               
                  $content_html .= '<br />' . "\n";
                  $content_html .= $signature_html . "\n";
               
               }
               
               // GLPI's signature
               if (!empty($footer_string)) {
               
                  $content_html .= '<br />' . "\n";
                  $content_html .= $footer_string . "\n";
               
               }
               
               $content_html .= '<br />' . "\n";
               $content_html .= '<br />' . "\n";
               
               if (!empty($add_footer)) {
               
                  $content_html .= $add_footer . "\n";
                  $content_html .= '<br />' . "\n";
                  $content_html .= '<br />' . "\n";
               
               }
               
               $content_html .= '</body>' . "\n";
               $content_html .= '</html>' . "\n";
               
               $lang['content_html'] = $content_html;
            
            }
            
            // associate $tid content
            $this->templates_by_languages[$tid] = $lang;
         
         }
      
      }
      
      if (isset($this->templates_by_languages[$tid])) {
      
         $output = $tid;
      
      }
      
      return $output;
   
   }
   
   
   /**
    * Process template
    *
    * @param $string string Text to process
    * @param $data array Data
    *
    * @return string
    **/
   static function process($string, $data) {
   
      // [???]
      $offset = $new_offset = 0;
      
      // Template processed
      $output = '';
      $cleandata = array();
      
      // Clean data for strtr
      foreach ($data as $field => $value) {
      
         if (!is_array($value)) {
         
            $cleandata[$field] = $value;
         
         }
      
      }
      
      // Remove all
      $string = Toolbox::unclean_cross_side_scripting_deep($string);
      
      // First of all process the FOREACH tag
      if (preg_match_all("/##FOREACH[ ]?(FIRST|LAST)?[ ]?([0-9]*)?[ ]?([a-zA-Z-0-9\.]*)##/i", $string, $out)) {
      
         foreach ($out[3] as $id => $tag_infos) {
         
            $regex = "/" . $out[0][$id] . "(.*)##ENDFOREACH" . $tag_infos . "##/Uis";
            
            if (preg_match($regex, $string, $tag_out) && isset($data[$tag_infos]) && is_array($data[$tag_infos])) {
            
               $data_lang_foreach = $cleandata;
               unset($data_lang_foreach[$tag_infos]);
               
               // Manage FIRST & LAST statement
               $foreachvalues = $data[$tag_infos];
               
               if (!empty($foreachvalues)) {
               
                  if (isset($out[1][$id]) && ($out[1][$id] != '')) {
                  
                     if ($out[1][$id] == 'FIRST') {
                     
                        $foreachvalues = array_reverse($foreachvalues);
                     
                     }
                     
                     if (isset($out[2][$id]) && $out[2][$id]) {
                     
                        $foreachvalues = array_slice($foreachvalues, 0, $out[2][$id]);
                     
                     } else {
                     
                        $foreachvalues = array_slice($foreachvalues, 0, 1);
                     
                     }
                  
                  }
               
               }
               
               $output_foreach_string = '';
               
               foreach ($foreachvalues as $line) {
               
                  foreach ($line as $field => $value) {
                  
                     if (!is_array($value)) {
                     
                        $data_lang_foreach[$field] = $value;
                     
                     }
                  
                  }
                  
                  $tmp = self::processIf($tag_out[1], $data_lang_foreach);
                  $output_foreach_string .= strtr($tmp, $data_lang_foreach);
               
               }
               
               $string = str_replace($tag_out[0], $output_foreach_string, $string);
            
            } else {
            
               $string = str_replace($tag_out, '', $string);
            
            }
         
         }
      
      }
      
      // Now process IF statements
      $string = self::processIf($string, $cleandata);
      $string = strtr($string, $cleandata);
      
      return $string;
   
   }
   
   
   /**
    * Process If Clause
    *
    * @param $string string Text to process
    * @param $data array Data
    *
    * @return string
    **/
   static function processIf($string, $data) {
   
      if (preg_match_all("/##IF([a-z\.]*)[=]?(.*?)##/i", $string, $out)) {
      
         foreach ($out[1] as $key => $tag_infos) {
         
            $if_field = $tag_infos;
            $if_field_data = false;
            
            if (isset($data['##' . $if_field . '##'])) {
            
               $if_field_data = $data['##' . $if_field . '##'];
            
            }
            
            // Get the field tag value (if one)
            $regex_if = "/##IF" . $if_field . "[=]?.*##(.*)##ENDIF" . $if_field . "##/Uis";
            
            // Get the else tag value (if one)
            $regex_else = "/##ELSE" . $if_field . "[=]?.*##(.*)##ENDELSE" . $if_field . "##/Uis";
            
            // No = : check if not empty or not null
            if (empty($out[2][$key]) && !strlen($out[2][$key]) ) {
            
               if ($if_field_data && ($if_field_data != '0') && ($if_field_data != '') && ($if_field_data != '&nbsp;') && !is_null($if_field_data)) {
               
                  $condition_ok = true;
               
               } else {
               
                  $condition_ok = false;
               
               }
            
            } else {
            
               // Check exact match
               if ($if_field_data && (Html::entity_decode_deep($if_field_data) == Html::entity_decode_deep($out[2][$key]))) {
               
                  $condition_ok = true;
               
               } else {
               
                  $condition_ok = false;
               
               }
            
            }
            
            // Force only one replacement to permit multiple use of the same condition
            if ($condition_ok) {
            
               $string = preg_replace($regex_if, "\\1", $string, 1);
               $string = preg_replace($regex_else, "", $string, 1);
            
            } else {
            
               $string = preg_replace($regex_if, "", $string, 1);
               $string = preg_replace($regex_else, "\\1", $string, 1);
            
            }
         
         }
      
      }
      
      return $string;
   
   }
   
   
   /**
    * Set signature
    *
    * @param $signature string Signature
    *
    * @return boolean
    **/
   function setSignature($signature) {
   
      $this->signature = $signature;
      
      return true;
   
   }
   
   
   /**
    * Return Translation
    *
    * @param $language string Template's language
    *
    * @return mixed
    **/
   function getByLanguage($language) {
   
      global $DB;
      
      $output = false;
      
      $query = "
         SELECT *
         FROM
            `glpi_notificationtemplatetranslations`
         WHERE
            `notificationtemplates_id` = '" . $this->getField('id') . "'
            AND `language` IN ('" . $language . "', '')
         ORDER BY
            `language` DESC
         LIMIT 1
      ";
      
      $iterator = $DB->request($query);
      
      if ($iterator->numrows()) {
      
         $output = $iterator->next();
      
      }
      
      return $output;
   
   }
   
   
   /**
    * Return data to send
    *
    * @param $target NotificationTarget object
    * @param $tid string Template computed id
    * @param $user_infos array User's informations
    * @param $options array Options
    *
    * @return array
    **/
   function getDataToSend(NotificationTarget $target, $tid, array $user_infos, array $options) {
   
      // [???]
      $language = $user_infos['language'];
      
      //
      $user_email = $user_infos['email'];
      $user_name  = $user_infos['username'];
      $sender = $target->getSender($options);
      $replyto = $target->getReplyTo($options);
      $template_data = $this->templates_by_languages[$tid];
      
      $mailing_options['to']           = $user_email;
      $mailing_options['toname']       = $user_name;
      $mailing_options['from']         = $sender['email'];
      $mailing_options['fromname']     = $sender['name'];
      $mailing_options['replyto']      = $replyto['email'];
      $mailing_options['replytoname']  = $replyto['name'];
      $mailing_options['messageid']    = $target->getMessageID();
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