<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem;
use KnowbaseItem_Item;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LinkItemModalController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/LinkItemModal",
        name: "knowbase_link_item_modal",
        requirements: ['id' => '\d+']
    )]
    public function __invoke(int $id): Response
    {
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }
        if (!$kb->canUpdateItem()) {
            throw new AccessDeniedHttpException();
        }

        $visibility = KnowbaseItem::getVisibilityCriteria();
        $condition = (isset($visibility['WHERE']) && count($visibility['WHERE']))
            ? $visibility['WHERE'] : [];
        $used_items = KnowbaseItem_Item::getItems($kb, 0, 0, true);

        return $this->render(
            'pages/tools/kb/knowbaseitem_item.html.twig',
            [
                'item' => $kb,
                'visibility_condition' => $condition,
                'used_knowbase_items' => $used_items,
            ]
        );
    }
}
