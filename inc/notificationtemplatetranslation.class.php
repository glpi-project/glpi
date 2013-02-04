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

// Class Notification
class NotificationTemplateTranslation extends CommonDBChild {

   // From CommonDBChild
   public $itemtype  = 'NotificationTemplate';
   public $items_id  = 'notificationtemplates_id';
   public $dohistory = true;

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['mailing'][109];
      }
      return $LANG['mailing'][126];
   }


   function getName($with_comment=0) {
      global $CFG_GLPI,$LANG;

      if ($this->getField('language') != '') {
         $toadd = $CFG_GLPI['languages'][$this->getField('language')][0];
      } else {
         $toadd = $LANG['mailing'][126];
      }

      return $toadd;
   }


   function defineTabs($options=array()) {


      $ong = array();
      $ong['empty'] = $this->getTypeName(1); // History as single tab seems "strange"
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function showForm($ID, $options) {
      global $DB, $LANG, $CFG_GLPI;

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      if (empty($ID)) {
          if ($this->getEmpty()) {
             $notificationtemplates_id = $options['notificationtemplates_id'];
          }

       } else {
          if ($this->getFromDB($ID)) {
             $notificationtemplates_id = $this->getField('notificationtemplates_id');
          }
       }

      $canedit = Session::haveRight("config", "w");

      $template = new NotificationTemplate();
      $template->getFromDB($notificationtemplates_id);

      Html::initEditorSystem('content_html');

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$template->getTypeName()."</td>";
      echo "<td colspan='2'><a href='".Toolbox::getItemTypeFormURL('NotificationTemplate').
            "?id=".$notificationtemplates_id."'>".$template->getField('name')."</a>";
      echo "</td><td><a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=list_notificationtags&amp;sub_type=".
             $template->getField('itemtype')."' ,
             'glpipopup', 'height=400, width=1000, top=100, left=100,".
             " scrollbars=yes' );w.focus();\">".$LANG['mailing'][138]."</a></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][41] . "&nbsp;:</td><td colspan='3'>";

      //Get all used languages
      $used = self::getAllUsedLanguages($notificationtemplates_id);
      if ($ID > 0) {
         if (isset($used[$this->getField('language')])) {
            unset($used[$this->getField('language')]);
         }
      }
      Dropdown::showLanguages("language", array('display_none' => true,
                                                'value'        => $this->fields['language'],
                                                'used'         => $used));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['knowbase'][14] . "&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='subject'size='100' value='".$this->fields["subject"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>";
      echo $LANG['mailing'][115]. ' '.$LANG['mailing'][117]."&nbsp;:<br>(".$LANG['mailing'][128].")";
      echo "</td><td colspan='3'>";
      echo "<textarea cols='100' rows='15' name='content_text' >".$this->fields["content_text"];
      echo "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" .$LANG['mailing'][115]. ' '.$LANG['mailing'][116]."&nbsp;:</td><td colspan='3'>";
      echo "<textarea cols='100' rows='15' name='content_html'>".$this->fields["content_html"];
      echo "</textarea>";
      echo "<input type='hidden' name='notificationtemplates_id' value='".
             $template->getField('id')."'>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }


   function showSummary(NotificationTemplate $template, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      $nID     = $template->getField('id');
      $canedit = Session::haveRight("config", "w");


      if ($canedit) {
         echo "<div class='center'>".
              "<a href='".Toolbox::getItemTypeFormURL('NotificationTemplateTranslation').
                "?notificationtemplates_id=".$nID."'>". $LANG['mailing'][124]."</a></div><br>";
      }

      echo "<div class='center' id='tabsbody'>";
      Session::initNavigateListItems('NotificationTemplateTranslation',
                                     $template->getTypeName() . " = ". $template->fields["name"]);

      echo "<form name='form_language' id='form_language' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th></th><th>".$LANG['setup'][41]."</th></tr>";

      foreach ($DB->request('glpi_notificationtemplatetranslations',
                            array('notificationtemplates_id' => $nID)) as $data) {

         if ($this->getFromDB($data['id'])) {
            Session::addToNavigateListItems('NotificationTemplateTranslation',$data['id']);
            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='checkbox' name=\"languages[" . $data['id'] . "]\"></td>";
            echo "<td class='center'>";
            echo "<a href='".Toolbox::getItemTypeFormURL('NotificationTemplateTranslation').
                  "?id=".$data['id']."&notificationtemplates_id=".$nID."'>";

            if ($data['language'] != '') {
               echo $CFG_GLPI['languages'][$data['language']][0];

            } else {
               echo $LANG['mailing'][125];
            }

            echo "</a></td></tr>";
         }
      }
      echo "</table>";

      if ($canedit) {
         Html::openArrowMassives("form_language", true);
         Html::closeArrowMassives(array("delete_languages" => $LANG["buttons"][6]));
      }
      Html::closeForm();
   }


   function prepareInputForAdd($input) {
      return self::cleanContentHtml($input);
   }


   static function cleanContentHtml($input) {

      if (!$input['content_text']) {
         $input['content_text']
               = Html::clean(Toolbox::unclean_cross_side_scripting_deep($input['content_html']));
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      return self::cleanContentHtml($input);
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'language';
      $tab[1]['name']          = $LANG['setup'][41];
      $tab[1]['datatype']      = 'language';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'subject';
      $tab[2]['name']          = $LANG['knowbase'][14];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'content_html';
      $tab[3]['name']          = $LANG['mailing'][115]. ' '. $LANG['mailing'][116];
      $tab[3]['datatype']      = 'text';
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'content_text';
      $tab[4]['name']          = $LANG['mailing'][115]. ' '. $LANG['mailing'][117];
      $tab[4]['datatype']      = 'text';
      $tab[4]['massiveaction'] = false;

      return $tab;
   }


   static function getAllUsedLanguages($language_id) {

      $used_languages = getAllDatasFromTable('glpi_notificationtemplatetranslations',
                                             'notificationtemplates_id='.$language_id);
      $used = array();

      foreach ($used_languages as $used_language) {
         $used[$used_language['language']] = $used_language['language'];
      }

      return $used;
   }


   static function showAvailableTags($itemtype) {
      global $LANG;

      $target = NotificationTarget::getInstanceByType($itemtype);
      $target->getTags();

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['mailing'][140]."</th>
                <th>".$LANG['mailing'][139]."</th>
                <th>".$LANG['mailing'][119]."</th>
                <th>".$LANG['common'][17]."</th>
                <th>".$LANG['mailing'][147]."</th>
            </tr>";

      $tags = array();

      foreach ($target->tag_descriptions as $tag_type => $infos) {
         foreach($infos as $key => $val) {
            $infos[$key]['type'] = $tag_type;
         }
         $tags = array_merge($tags,$infos);
      }
      ksort($tags);
      foreach ($tags as $tag => $values) {

         if ($values['events'] == NotificationTarget::TAG_FOR_ALL_EVENTS) {
            $event = $LANG['common'][66];
         } else {
            $event = implode(', ',$values['events']);
         }

         $action = '';

         if ($values['foreach']) {
            $action = $LANG['mailing'][145];
         } else {
            $action = $LANG['mailing'][146];
         }

         if (!empty($values['allowed_values'])) {
            $allowed_values = implode(',',$values['allowed_values']);
         } else {
            $allowed_values = '';
         }

         echo "<tr class='tab_bg_1'><td>".$tag."</td>
               <td>".($values['type']==NotificationTarget::TAG_LANGUAGE?$LANG['mailing'][139].' : ':'').
               $values['label']."</td>
               <td>$event</td>
               <td>".$action."</td>
               <td>$allowed_values</td>
               </tr>";
      }
      echo "</table></div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'NotificationTemplate' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable($this->getTable(),
                                                                   "notificationtemplates_id = '".$item->getID()."'"));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='NotificationTemplate') {
         $temp = new self();
         $temp->showSummary($item);
      }
      return true;
   }

}
?>
