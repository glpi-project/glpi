<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Controller\Knowbase;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\RichText\RichText;
use KnowbaseItem;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class KnowbaseItemController extends AbstractController
{
    #[Route(
        "/Knowbase/KnowbaseItem/{knowbaseitems_id}/Content",
        name: "knowbaseitem_content",
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function content(Request $request): Response
    {
        $id = $request->get('knowbaseitems_id');
        if (!KnowbaseItem::canView()) {
            throw new AccessDeniedHttpException();
        }
        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB((int) $id)) {
            throw new NotFoundHttpException();
        } elseif (!$kbitem->canViewItem()) {
            throw new AccessDeniedHttpException();
        }
        return new Response($kbitem->fields['answer']);
    }

    #[Route(
        "/Knowbase/KnowbaseItem/{knowbaseitems_id}/Full",
        name: "knowbaseitem_full",
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function full(Request $request): Response
    {
        $id = $request->get('knowbaseitems_id');
        if (!KnowbaseItem::canView()) {
            throw new AccessDeniedHttpException();
        }
        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB((int) $id)) {
            throw new NotFoundHttpException();
        } elseif (!$kbitem->canViewItem()) {
            throw new AccessDeniedHttpException();
        }
        return new StreamedResponse(static function () use ($kbitem) {
            $kbitem->showFull();
        });
    }

    #[Route(
        "/Knowbase/KnowbaseItem/Search/{itemtype}/{items_id}",
        name: "knowbaseitem_search",
        requirements: [
            'items_id' => '\d+',
        ]
    )]
    public function search(Request $request): Response
    {
        global $CFG_GLPI, $DB;

        $itemtype = $request->get('itemtype');
        $items_id = $request->get('items_id');
        $start = (int) $request->get('start', 0);
        $contains = $request->get('contains');

        // Search a solution
        if (empty($contains)) {
            if (in_array($itemtype, $CFG_GLPI['kb_types'], true) && $item = getItemForItemtype($itemtype)) {
                if ($item->can($items_id, READ)) {
                    $contains = $item->getField('name');
                }
            }
        }

        $criteria = KnowbaseItem::getListRequest([
            'contains' => $contains,
        ]);
        $count_criteria = $criteria;
        $criteria['START'] = $start;
        $criteria['LIMIT'] = $_SESSION['glpilist_limit'];
        unset($count_criteria['SELECT'], $count_criteria['ORDERBY'], $count_criteria['GROUPBY']);
        $count_criteria['COUNT'] = 'cpt';

        $it = $DB->request($criteria);
        $total_count = $DB->request($count_criteria)->current()['cpt'] ?? 0;
        $results = [];

        foreach ($it as $data) {
            $icon_class = "";
            $icon_title = "";
            if (
                $data['is_faq']
                && (!Session::isMultiEntitiesMode()
                    || (isset($data['visibility_count'])
                        && $data['visibility_count'] > 0))
            ) {
                $icon_class = "ti ti-help faq";
                $icon_title = __("This item is part of the FAQ");
            } elseif (
                isset($data['visibility_count'])
                && $data['visibility_count'] <= 0
            ) {
                $icon_class = "ti ti-eye-off not-published";
                $icon_title = __("This item is not published yet");
            }

            $results[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'content_preview' => mb_substr(
                    string: RichText::getTextFromHtml(
                        content: $data['answer'],
                        preserve_line_breaks: true
                    ),
                    start: 0,
                    length: GLPI_TEXT_MAXSIZE
                ),
                'url' => KnowbaseItem::getFormURLWithID($data['id']),
                'icon' => $icon_class,
                'icon_title' => $icon_title,
            ];
        }

        $twig_params = [
            'contains' => $contains,
            'results' => $results,
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'is_ajax' => $request->get('ajax_reload', 0),
            'count' => $total_count,
            'start' => $start,
        ];

        return new StreamedResponse(static function () use ($twig_params) {
            TemplateRenderer::getInstance()->display('pages/tools/search_knowbaseitem.html.twig', $twig_params);
        });
    }
}
