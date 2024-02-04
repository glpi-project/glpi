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

use Glpi\Application\View\TemplateRenderer;

class Link_Itemtype extends CommonDBChild
{
   // From CommonDbChild
    public static $itemtype = 'Link';
    public static $items_id = 'links_id';

    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * Print the HTML array for device on link
     *
     * @param Link $link
     *
     * @return void
     **/
    public static function showForLink($link)
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        $links_id = $link->getField('id');

        $canedit  = $link->canEdit($links_id);
        $rand     = mt_rand();

        if (
            !Link::canView()
            || !$link->can($links_id, READ)
        ) {
            return false;
        }

        $iterator = $DB->request([
            'FROM'   => 'glpi_links_itemtypes',
            'WHERE'  => ['links_id' => $links_id],
            'ORDER'  => 'itemtype'
        ]);
        $types  = [];
        $used   = [];
        $numrows = count($iterator);
        foreach ($iterator as $data) {
            $types[$data['id']]      = $data;
            $used[$data['itemtype']] = $data['itemtype'];
        }

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/setup/externallink_itemtype.html.twig', [
                'links_id' => $links_id,
                'used' => $used,
                'no_header' => true,
            ]);
        }

        $entries = [];
        foreach ($types as $data) {
            if ($item = getItemForItemtype($data['itemtype'])) {
                $typename = $item::getTypeName(1);
                $entries[] = [
                    'itemtype' => static::class,
                    'id'       => $data['id'],
                    'type'     => $typename
                ];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nopager' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1)
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => $numrows,
                'container' => 'mass' . __CLASS__ . $rand,
                'specific_actions' => [
                    'purge'  => _x('button', 'Delete permanently')
                ]
            ],
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Link':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['links_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(_n(
                        'Associated item type',
                        'Associated item types',
                        Session::getPluralNumber()
                    ), $nb, $item::getType(), Link::getIcon());
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === Link::class) {
            self::showForLink($item);
        }
        return true;
    }

    /**
     *
     * Remove all associations for an itemtype
     *
     * @since 0.85
     *
     * @param string $itemtype  itemtype for which all link associations must be removed
     */
    public static function deleteForItemtype($itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->delete(
            self::getTable(),
            [
                'itemtype'  => ['LIKE', "%Plugin$itemtype%"]
            ]
        );
    }
}
