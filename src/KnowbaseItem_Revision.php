<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/// Class KnowbaseItem_Revision
/// since version 9.2
class KnowbaseItem_Revision extends CommonDBTM
{
    public static $rightname   = 'knowbase';

    public static function getTypeName($nb = 0)
    {
        return _n('Revision', 'Revisions', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            !($item instanceof CommonDBTM)
            || !$item->canUpdateItem()
        ) {
            return '';
        }

        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $where = [];
            if ($item instanceof KnowbaseItem) {
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

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Show linked items of a knowbase item
     *
     * @param $item                     CommonDBTM object
     * @param $withtemplate    integer  withtemplate param (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $item_id = $item->getID();
        $item_type = $item::getType();
        if (isset($_GET["start"])) {
            $start = intval($_GET["start"]);
        } else {
            $start = 0;
        }

        $kb_item_id = 0;
        $language   = '';
        if ($item->getType() == KnowbaseItem::getType()) {
            $kb_item_id = $item->getID();
        } else {
            $kb_item_id = $item->fields['knowbaseitems_id'];
            $language   = $item->fields['language'];
        }
        $where = [
            'knowbaseitems_id' => $kb_item_id,
            'language'         => $language,
        ];

        // Total Number of revisions
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
        echo "<table class='tab_cadre_fixehov'>";
        $header = '<tr>';
        $header .= "<th title='" . _sn('Revision', 'Revisions', 1) . "'>#</th>";
        $header .= "<th>&nbsp;</th>";
        $header .= "<th>" . __('Author')  . "</th>";
        $header .= "<th>" . __('Creation date') . "</th>";
        $header .= "<th></th></tr>";
        echo $header;

        $user = new User();
        $user->getFromDB($item->fields['users_id']);

       //current contents
        echo "<tr class='tab_bg_2'>";
        echo "<td>(" . __('cur')  . ")</td>" .
              "<td><input type='radio' name='oldid' value='0' style='visibility:hidden'/>" .
              "<input type='radio' name='diff' value='0' checked='checked'/></td>" .
              "<td>" . $user->getLink() . "</td>" .
              "<td class='tab_date'>" . $item->fields['date_mod'] . "</td>" .
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

            echo "<td>" . ($hasRevUser ? $user->getLink() : __('Unknown user')) . "</td>" .
             "<td class='tab_date'>" . $revision['date'] . "</td>";

            $form = null;
            if ($item->getType() == KnowbaseItem::getType()) {
                $form = KnowbaseItem::getFormURLWithID($revision['knowbaseitems_id']);
            } else {
                $form = KnowbaseItemTranslation::getFormURLWithID($revision['knowbaseitems_id']);
            }

            echo "<td><a href='#' data-rev='" . $revision['revision']  . "'
                    data-revid='" . $revision['id']  . "' class='show_rev'>" . __('show') . "</a>
                 - <a href='$form&to_rev={$revision['id']}' class='restore_rev'>" .
                    __('restore')  . "</a></td>";
            echo "</tr>";
        }

       // TODO: move script to deferred js loading
        echo Html::script("public/lib/jquery-prettytextdiff.js");
        echo Html::scriptBlock("
         $(function() {
            $(document).on('click', '.restore_rev', function(e) {
               lastClickedElement = e.target;
               return window.confirm(__('Do you want to restore the selected revision?'));
            });

            $(document).on('click', '.show_rev', function(e) {
               e.preventDefault();
               var _this = $(this);

               $.ajax({
                  url: '{$CFG_GLPI['root_doc']}/ajax/getKbRevision.php',
                  method: 'post',
                  cache: false,
                  data: {
                     revid: _this.data('revid')
                  }
               })
               .done(function(data) {
                  glpi_html_dialog({
                     title: __('Show revision %rev').replace(/%rev/, _this.data('rev')),
                     body: `<div>
                        <h2>\${__('Subject')}</h2>
                        <div class='text-wrap text-break'>\${data.name}</div>
                        <h2>\${__('Content')}</h2>
                        <div class='text-wrap text-break'>\${data.answer}</div>
                     </div>`,
                  });
               })
               .fail(function() {
                  glpi_alert({
                     title: __('Contact your GLPI admin!'),
                     message: __('Unable to load revision!'),
                  });
               });
            });

            $(document).on('click', '.compare', function(e) {
               e.preventDefault();
               var _oldid = $('[name=oldid]:checked').val();
               var _diffid = $('[name=diff]:checked').val();

               $.ajax({
                  url: '{$CFG_GLPI['root_doc']}/ajax/compareKbRevisions.php',
                  method: 'post',
                  cache: false,
                  data: {
                     oldid :  _oldid,
                     diffid: _diffid,
                     kbid  : '{$kb_item_id}'
                  }
               }).done(function(data) {
                  if (_diffid == 0) {
                     _diffid = __('current');
                  }

                  glpi_html_dialog({
                     title: __('Compare revisions old and diff')
                        .replace(/old/, _oldid)
                        .replace(/diff/, _diffid),
                     body: `<div id='compare_view'>
                        <table class='table'>
                           <tr>
                              <th></th>
                              <th>\${__('Original')}</th>
                              <th>\${__('Changed')}</th>
                              <th>\${__('Differences')}</th>
                           </tr>
                           <tr>
                              <th>\${__('Subject')}</th>
                              <td class='original text-wrap text-break'>\${data['old']['name']}</td>
                              <td class='changed text-wrap text-break'>\${data['diff']['name']}</td>
                              <td class='diff text-wrap text-break'></td>
                           </tr>
                           <tr>
                              <th>\${__('Content')}</th>
                              <td class='original text-wrap text-break'>\${data['old']['answer']}</td>
                              <td class='changed text-wrap text-break'>\${data['diff']['answer']}</td>
                              <td class='diff text-wrap text-break'></td>
                           </tr>
                        </table>
                     </div>`,
                     dialogclass: 'modal-xl'
                  });

                  $('#compare_view tr').prettyTextDiff();
               })
               .fail(function() {
                  glpi_alert({
                     title: __('Contact your GLPI admin!'),
                     message: __('Unable to load diff!'),
                  });
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
      ");

        echo $header;
        echo "</table>";
        echo "<button class='btn btn-sm btn-secondary compare'>" . _sx('button', 'Compare selected revisions') . "</button>";
        Html::printAjaxPager(self::getTypeName(1), $start, $number);
    }

    /**
     * Populate and create a new revision from KnowbaseItem information
     *
     * @param KnowbaseItem $item Knowledge base item
     *
     * @return integer|boolean ID of the revision created, or false on error
     */
    public function createNew(KnowbaseItem $item)
    {
        $this->getEmpty();
        unset($this->fields['id']);
        $this->fields['knowbaseitems_id'] = $item->fields['id'];
        $this->fields['name'] = Toolbox::addslashes_deep($item->fields['name']);
        $this->fields['answer'] = Toolbox::addslashes_deep($item->fields['answer']);
        $this->fields['date'] = $item->fields['date_mod'];
        $this->fields['revision'] = $this->getNewRevision();
        $this->fields['users_id'] = $item->fields['users_id'];
        return $this->addToDB();
    }

    /**
     * Populate and create a new revision from KnowbaseItemTranslation information
     *
     * @param KnowbaseItemTranslation $item Knowledge base item translation
     *
     * @return integer|boolean ID of the revision created, or false on error
     */
    public function createNewTranslated(KnowbaseItemTranslation $item)
    {
        $this->getEmpty();
        unset($this->fields['id']);
        $this->fields['knowbaseitems_id'] = $item->fields['knowbaseitems_id'];
        $this->fields['name'] = Toolbox::addslashes_deep($item->fields['name']);
        $this->fields['answer'] = Toolbox::addslashes_deep($item->fields['answer']);
        $this->fields['date'] = $item->fields['date_mod'];
        $this->fields['language'] = $item->fields['language'];
        $this->fields['revision'] = $this->getNewRevision();
        $this->fields['users_id'] = $item->fields['users_id'];
        return $this->addToDB();
    }

    /**
     * Get new revision number for item
     *
     * @return integer
     */
    private function getNewRevision()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'SELECT' => ['MAX' => 'revision AS revision'],
            'FROM'   => 'glpi_knowbaseitems_revisions',
            'WHERE'  => [
                'knowbaseitems_id'   => $this->fields['knowbaseitems_id'],
                'language'           => $this->fields['language']
            ]
        ])->current();

        $rev = $result['revision'];
        if ($rev === null) {
           //no revisions yet
            $rev = 1;
        } else {
            ++$rev;
        }

        return $rev;
    }
}
