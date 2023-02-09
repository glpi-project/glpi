<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Api\HL\Middleware;

use CommonDBTM;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\RoutePath;
use Glpi\Http\Request;
use Glpi\Http\Response;

class CRUDRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        if (!$input->request->hasAttribute('itemtype')) {
            $next($input);
            return;
        }

        $specific_item = $input->request->hasAttribute('id');
        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype = $input->request->getAttribute('itemtype');
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        if ($specific_item) {
            $items_id = $input->request->getAttribute('id');

            if (!$item->getFromDB($items_id)) {
                $input->response = AbstractController::getNotFoundErrorResponse();
                return;
            }
            if (!$item->canViewItem()) {
                $input->response = AbstractController::getAccessDeniedErrorResponse();
                return;
            }
            $input->request->setParameter('_item', $item);
        }

        $force = $input->request->hasParameter('force');
        // Global permission checks
        $passed = match ($input->request->getMethod()) {
            'GET' => $itemtype::canView(),
            'POST' => $itemtype::canCreate(),
            'PATCH' => $itemtype::canUpdate(),
            'DELETE' => $force ? $itemtype::canPurge() : $itemtype::canDelete()
        };
        if ($passed && $specific_item) {
            // Specific permission checks
            $passed = match ($input->request->getMethod()) {
                'GET' => $item->canViewItem(),
                'POST' => $item->canCreateItem(),
                'PATCH' => $item->canUpdateItem(),
                'DELETE' => $force ? $item->canPurgeItem() : $item->canDeleteItem()
            };
        }
        if (!$passed) {
            $input->response = AbstractController::getAccessDeniedErrorResponse();
            return;
        }

        $next($input);
    }
}
