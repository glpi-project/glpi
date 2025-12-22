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

namespace Glpi\Controller\ItemType\Form;

use CommonDBTM;
use Glpi\Controller\GenericFormController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RedirectResponse;
use Glpi\Routing\Attribute\ItemtypeFormRoute;
use Plug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlugFormController extends GenericFormController
{
    #[ItemtypeFormRoute(Plug::class)]
    public function __invoke(Request $request): Response
    {
        global $CFG_GLPI;

        if ($request->request->has('add_several')) {
            $main_itemtype = $request->request->getString('itemtype_main');
            $main_item_id  = $request->request->getInt('items_id_main');

            if (!\in_array($main_itemtype, $CFG_GLPI['plug_types'], true)) {
                throw new BadRequestHttpException();
            }

            $main_item = \getItemForItemtype($main_itemtype);
            if (!($main_item instanceof CommonDBTM) || !$main_item->getFromDB($main_item_id)) {
                throw new BadRequestHttpException();
            }

            $base_input = [
                'itemtype_main'     => $main_itemtype,
                'items_id_main'     => $main_item_id,
                'entities_id'       => $main_item->getEntityID(),
                'is_recursive'      => $main_item->isRecursive(),
            ];

            $plug = new Plug();
            if (!$plug->can(-1, CREATE, $base_input)) {
                throw new AccessDeniedHttpException();
            }

            $name = $request->request->get('name');
            for ($i = 0; $i < $request->request->get('number'); $i++) {
                $input = $base_input + [
                    'name' => $name . " - " . ($i + 1),
                    'number' => $i + 1,
                ];
                $plug->add($input);
            }
            return new RedirectResponse($main_item->getLinkURL());
        }

        // Handle action using the generic controller
        $request->attributes->set('class', Plug::class);
        return parent::__invoke($request);
    }
}
