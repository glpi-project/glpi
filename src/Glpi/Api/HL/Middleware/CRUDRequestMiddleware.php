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

namespace Glpi\Api\HL\Middleware;

use CommonDBTM;
use Glpi\Api\HL\Controller\AbstractController;

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
        $item = getItemForItemtype($itemtype);
        if ($specific_item) {
            $items_id = $input->request->getAttribute('id');

            if (!$item->getFromDB($items_id)) {
                $input->response = AbstractController::getNotFoundErrorResponse();
                return;
            }
            $input->request->setParameter('_item', $item);
        }

        $next($input);
    }
}
