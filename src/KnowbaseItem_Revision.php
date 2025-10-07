<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

/**
 * Class KnowbaseItem_Revision
 * @since 9.2.0
 * @todo Extend CommonDBChild
 */
class KnowbaseItem_Revision extends CommonDBTM
{
    public static $rightname   = 'knowbase';

    public static function getTypeName($nb = 0)
    {
        return _n('Revision', 'Revisions', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-history';
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
                    'language'         => '',
                ];
            } else {
                $where = [
                    'knowbaseitems_id' => $item->fields['knowbaseitems_id'],
                    'language'         => $item->fields['language'],
                ];
            }

            $nb = countElementsInTable(
                'glpi_knowbaseitems_revisions',
                $where
            );
        }
        return self::createTabEntry(self::getTypeName($nb), $nb, $item::class);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Show linked items of a knowbase item
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate withtemplate param (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        if (isset($_GET["start"])) {
            $start = (int) $_GET["start"];
        } else {
            $start = 0;
        }

        $language   = '';
        if ($item::class === KnowbaseItem::class) {
            $kb_item_id = $item->getID();
        } else {
            $kb_item_id = (int) $item->fields['knowbaseitems_id'];
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
            echo '<div class="alert alert-info">' . __s('No revisions') . '</div>';
            return;
        }

        $user = new User();
        $user->getFromDB($item->fields['users_id']);
        $revisions = $DB->request([
            'SELECT' => [
                'id',
                'knowbaseitems_id',
                'revision',
                'users_id',
                'date',
            ],
            'FROM' => 'glpi_knowbaseitems_revisions',
            'WHERE' => $where,
            'ORDER' => 'id DESC',
        ]);

        $is_checked = true;
        $author_cache = [
            $item->fields['users_id'] => $user->getLink(),
        ];
        $entries = [
            [
                'number' => __('cur'),
                'selections' => '<input type="radio" name="oldid" value="0" style="visibility:hidden"/><input type="radio" name="diff" value="0" checked="checked"/>',
                'author' => $author_cache[$item->fields['users_id']],
                'date_creation' => $item->fields['date_mod'],
                'actions' => '',
            ],
        ];
        $show_msg = __s('show');
        $restore_msg = __s('restore');
        foreach ($revisions as $revision) {
            if (!isset($author_cache[$revision['users_id']])) {
                // Before GLPI 9.3.1, author was not stored in revision.
                // See https://github.com/glpi-project/glpi/issues/4377.
                $hasRevUser = $user->getFromDB($revision['users_id']);
                $author_cache[$revision['users_id']] = $hasRevUser ? $user->getLink() : __s('Unknown user');
            }

            $oldid_checked = $is_checked ? ' checked="checked"' : '';
            if ($is_checked) {
                $is_checked = false;
            }
            $selection_controls = '
                <input type="radio" name="oldid" value="' . htmlescape($revision['id']) . '" ' . $oldid_checked . '/>
                <input type="radio" name="diff" value="' . htmlescape($revision['id']) . '"/>
            ';

            if ($item::class === KnowbaseItem::class) {
                $form = KnowbaseItem::getFormURLWithID($revision['knowbaseitems_id']);
            } else {
                $form = KnowbaseItemTranslation::getFormURLWithID($revision['knowbaseitems_id']);
            }
            $actions = '
                <a href="#"
                   data-rev="' . htmlescape($revision['revision']) . '"
                   data-revid="' . htmlescape($revision['id']) . '"
                   class="show_rev">
                    ' . $show_msg . '
                </a>
                - <a href="' . htmlescape("{$form}&to_rev={$revision['id']}") . '" class="restore_rev">
                    ' . $restore_msg . '
                </a>
            ';

            $entries[] = [
                'number' => $revision['revision'],
                'selections' => $selection_controls,
                'author' => $author_cache[$revision['users_id']],
                'date_creation' => $revision['date'],
                'actions' => $actions,
            ];
        }

        echo Html::script('js/modules/Knowbase.js', ['type' => 'module']);
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'number' => '#',
                'selections' => '',
                'author' => __('Author'),
                'date_creation' => __('Creation date'),
                'actions' => '',
            ],
            'formatters' => [
                'selections' => 'raw_html',
                'author' => 'raw_html',
                'date_creation' => 'datetime',
                'actions' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => $number,
            'showmassiveactions' => false,
        ]);
        echo "<button class='btn btn-sm btn-secondary compare' data-kbitem_id='{$kb_item_id}'>" . _sx('button', 'Compare selected revisions') . "</button>";
    }

    /**
     * Populate and create a new revision from KnowbaseItem or KnowbaseItemTranslation information
     *
     * @param KnowbaseItem|KnowbaseItemTranslation $item Knowledge base item
     *
     * @return integer|false ID of the revision created, or false on error
     */
    public function createNew(KnowbaseItem|KnowbaseItemTranslation $item)
    {
        $this->getEmpty();
        unset($this->fields['id']);
        $is_translation = $item::class === KnowbaseItemTranslation::class;
        $this->fields['knowbaseitems_id'] = $item->fields[$is_translation ? 'knowbaseitems_id' : 'id'];
        $this->fields['name'] = $item->fields['name'];
        $this->fields['answer'] = $item->fields['answer'];
        $this->fields['date'] = $item->fields['date_mod'];
        if ($is_translation) {
            $this->fields['language'] = $item->fields['language'];
        }
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
        global $DB;

        $result = $DB->request([
            'SELECT' => ['MAX' => 'revision AS revision'],
            'FROM'   => 'glpi_knowbaseitems_revisions',
            'WHERE'  => [
                'knowbaseitems_id'   => $this->fields['knowbaseitems_id'],
                'language'           => $this->fields['language'],
            ],
        ])->current();

        $rev = $result['revision'];
        if ($rev === null) {
            // no revisions yet
            $rev = 1;
        } else {
            ++$rev;
        }

        return $rev;
    }
}
