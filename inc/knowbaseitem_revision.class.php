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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class KnowbaseItem_Revision
/// since version 9.2
class KnowbaseItem_Revision extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Revision', 'Revisions', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$item->canUpdateItem()) {
         return '';
      }

      $nb = 0;
      if ($_SESSION['glpishow_count_on_tabs']) {
         $where = [];
         if ($item->getType() == KnowbaseItem::getType()) {
            $where = [
               'knowbaseitems_id' => $item->getID(),
               'language'         => ''
            ];
         } else {
            $where = [
               'knowbaseitems_id' => $item->fields['knowbaseitems_id'],
               'language'         => $item->fields['language']
            ];
         }

         $nb = countElementsInTable(
            'glpi_knowbaseitems_revisions',
            $where
         );
      }
      return self::createTabEntry(self::getTypeName($nb), $nb);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForItem($item, $withtemplate);
      return true;
   }

   /**
    * Show linked items of a knowbase item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB, $CFG_GLPI;

      $item_id = $item->getID();
      $item_type = $item::getType();
      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      // Total Number of revisions
      if ($item->getType() == KnowbaseItem::getType()) {
         $where = [
            'knowbaseitems_id' => $item->getID(),
            'language'         => ''
         ];
      } else {
         $where = [
            'knowbaseitems_id' => $item->fields['knowbaseitems_id'],
            'language'         => $item->fields['language']
         ];
      }

      $number = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
       );

      // No revisions in database
      if ($number < 1) {
         $no_txt = __('No revisions');
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>$no_txt</th></tr>";
         echo "</table>";
         echo "</div>";
         return;
      }
      // Display the pager

      Html::printAjaxPager(self::getTypeName(1), $start, $number);
      // Output events
      echo "<div class='center'>";
      echo "<input type='button' name='compare' value='"._sx('button', 'Compare selected revisions').
             "' class='submit compare'>";
      echo "<table class='tab_cadre_fixehov'>";
      $header = '<tr>';
      $header .= "<th title='" . __s('Revision') . "'>#</th>";
      $header .= "<th>&nbsp;</th>";
      $header .= "<th>" . __('Author')  . "</th>";
      $header .= "<th>".__('Creation date')."</th>";
      $header .= "<th></th></tr>";
      echo $header;

      $user = new User();
      $user->getFromDB($item->fields['users_id']);

      //current contents
      echo "<tr class='tab_bg_2'>";
      echo "<td>(" . __('cur')  . ")</td>" .
              "<td><input type='radio' name='oldid' value='0' style='visibility:hidden'/>" .
              "<input type='radio' name='diff' value='0' checked='checked'/></td>" .
              "<td>" . $user->getLink() . "</td>".
              "<td class='tab_date'>". $item->fields['date_mod'] . "</td>" .
              "<td></td>" .
              "</tr>";

      $revisions = $DB->request(
         'glpi_knowbaseitems_revisions',
         $where + ['ORDER' => 'id DESC']
      );

      $is_checked = true;
      foreach ($revisions as $revision) {
         // Before GLPI 9.3.1, author was not stored in revision.
         // See https://github.com/glpi-project/glpi/issues/4377.
         $hasRevUser = $user->getFromDB($revision['users_id']);

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $revision['revision']  . "</td>" .
                 "<td><input type='radio' name='oldid' value='{$revision['id']}'";

         if ($is_checked) {
            echo " checked='checked'";
            $is_checked = false;
         }

         echo "/> <input type='radio' name='diff' value='{$revision['id']}'/></td>";

         echo "<td>" . ($hasRevUser ? $user->getLink() : __('Unknown user')) . "</td>".
             "<td class='tab_date'>". $revision['date_creation'] . "</td>";

         $form = null;
         if ($item->getType() == KnowbaseItem::getType()) {
            $form = KnowbaseItem::getFormURLWithID($revision['knowbaseitems_id']);
         } else {
            $form = KnowbaseItemTranslation::getFormURLWithID($revision['knowbaseitems_id']);
         }

         echo "<td><a href='#' data-rev='" . $revision['revision']  . "'
                    data-revid='" . $revision['id']  . "' class='show'>" . __('show') . "</a>
                 - <a href='$form&to_rev={$revision['id']}' class='restore'>".
                    __('restore')  . "</a></td>";
         echo "</tr>";
      }

      echo Html::script("public/lib/jquery-prettytextdiff.js");
      echo "<script type='text/javascript'>
            $(function() {
               $('.restore').on('click', function(e) {
                  lastClickedElement = e.target;
                  return window.confirm('" . __s('Do you want to restore the selected revision?')  . "');
               });

               $('.show').on('click', function(e) {
                  e.preventDefault();
                  var _this = $(this);

                  $.ajax({
                     url: '{$CFG_GLPI['root_doc']}/ajax/getKbRevision.php',
                     method: 'post',
                     cache: false,
                     data: {
                        'revid': _this.data('revid')
                     },
                     success: function(data) {
                        var title = '" . __('Show revision %rev') . "'.replace(/%rev/, _this.data('rev'));
                        var html = '<div title=\"' + title + '\" id=\"compare_view\"><table class=\"tab_cadre_fixehov\">';
                        html += '<h2>".__('Subject')."</h2>';
                        html += '<div>' + data.name + '</div>';
                        html += '<h2>".__('Content')."</h2>';
                        html += '<div>' + data.answer + '</div>';
                        html += '</div>';
                        $(html).appendTo('body').dialog({
                           height: 'auto',
                           width: 'auto',
                           modal: true
                        });
                     },
                     error: function() { ".
                        Html::jsAlertCallback(__('Contact your GLPI admin!'), __('Unable to load revision!'))."
                     }
                  });
               });

               $('.compare').on('click', function(e) {
                  e.preventDefault();
                  var _oldid = $('[name=oldid]:checked').val();
                  var _diffid = $('[name=diff]:checked').val();

                  $.ajax({
                     url: '{$CFG_GLPI['root_doc']}/ajax/compareKbRevisions.php',
                     method: 'post',
                     cache: false,
                     data: {
                        'oldid' :  _oldid,
                        'diffid': _diffid,
                        'kbid'  : '{$revision['knowbaseitems_id']}'
                     },
                     success: function(data) {
                        if (_diffid == 0) {
                           _diffid = '" . __('current') . "';
                        }
                        var title = '" . __s('Compare revisions old and diff') . "'.replace(/old/, _oldid).replace(/diff/, _diffid);
                        var html_compare = '<div title=\"' + title + '\" id=\"compare_view\"><table class=\"tab_cadre_fixehov\">';
                        html_compare += '<tr><th></th><th>" . __s('Original') . "</th><th>" . __s('Changed') ."</th><th>" . __('Differences')  . "</th></tr>';
                        html_compare += '<tr><th>" . __s('Subject') . "</th><td class=\"original\">' + data['old']['name'] + '</td><td class=\"changed\">' + data['diff']['name'] + '</td><td class=\"diff\"></td></tr>';
                        html_compare += '<tr><th>" . __s('Content')  . "</th><td class=\"original\">' + data['old']['answer'] + '</td><td class=\"changed\">' + data['diff']['answer'] + '</td><td class=\"diff\"></td></tr>';
                        html_compare += '</table></div>';
                        $(html_compare).appendTo('body').dialog({
                           height: 'auto',
                           width: 'auto',
                           modal: true
                        });
                        $('#compare_view tr').prettyTextDiff();
                     },
                     error: function() { ".
                        Html::jsAlertCallback(__('Contact your GLPI admin!'), __('Unable to load requested comparison!'))."
                     }
                  });
               });

               $('[name=diff]:gt(0)').css('visibility', 'hidden');
               $('[name=oldid]').on('click', function(e) {
                  var _index = $(this).index('[name=oldid]');

                  var _checked_index = $('[name=diff]:checked').index('[name=diff]');
                  if (_checked_index >= _index) {
                     $('[name=diff]:eq(' + (_index - 1) +')').prop('checked', true);
                  }

                  $('[name=diff]:gt(' + _index + '), [name=diff]:eq(' + _index + ')').css('visibility', 'hidden');
                  $('[name=diff]:lt(' + _index + ')').css('visibility', 'visible');
               });
            });
         </script>";

      echo $header;
      echo "</table>";
      echo "<input type='button' name='compare' value='"._sx('button', 'Compare selected revisions')."' class='submit compare'>";
      echo "</div>";
      Html::printAjaxPager(self::getTypeName(1), $start, $number);
   }

   /**
    * Populate and create a new revision from KnowbaseItem informations
    *
    * @param KnowbaseItem $item Knowledge base item
    *
    * @return boolean
    */
   public function createNew(KnowbaseItem $item) {
      $this->getEmpty();
      $this->fields['knowbaseitems_id'] = $item->fields['id'];
      $this->fields['name'] = Toolbox::addslashes_deep($item->fields['name']);
      $this->fields['answer'] = Toolbox::clean_cross_side_scripting_deep(
         Toolbox::addslashes_deep($item->fields['answer'])
      );
      $this->fields['date_creation'] = $item->fields['date_mod'];
      $this->fields['revision'] = $this->getNewRevision();
      $this->fields['users_id'] = $item->fields['users_id'];
      $this->addToDB();
   }

   /**
    * Populate and create a new revision from KnowbaseItem informations
    *
    * @param KnowbaseItemTranslation $item Knowledge base item translation
    *
    * @return boolean
    */
   public function createNewTranslated(KnowbaseItemTranslation $item) {
      $this->getEmpty();
      $this->fields['knowbaseitems_id'] = $item->fields['knowbaseitems_id'];
      $this->fields['name'] = $item->fields['name'];
      $this->fields['answer'] = $item->fields['answer'];
      $this->fields['date_creation'] = $item->fields['date_mod'];
      $this->fields['language'] = $item->fields['language'];
      $this->fields['revision'] = $this->getNewRevision();
      $this->fields['users_id'] = $item->fields['users_id'];
      $this->addToDB();
   }

   /**
    * Get new revision number for item
    *
    * @return integer
    */
   private function getNewRevision() {
      global $DB;

      $rev = null;
      $last_rev = $DB->query(
         "SELECT MAX(revision)+1 AS new_revision FROM glpi_knowbaseitems_revisions
            WHERE knowbaseitems_id='" . $this->fields['knowbaseitems_id'] .
           "' AND language='" . $this->fields['language'] . "'"
       );

      if ($last_rev) {
         $rev = $DB->result($last_rev, 0, 0);
      }

      if ($rev === null) {
         //no revisions yet
         $rev = 1;
      }

      return $rev;
   }
}
