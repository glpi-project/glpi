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
        $request->attributes->set('class', Plug::class);
        if (
            $request->request->has('add_several')
        ) {
            $mainitemtype = $request->request->get('mainitemtype');

            if (!is_a($mainitemtype, CommonDBTM::class, true)) {
                throw new \LogicException(sprintf(
                    __('Invalid main item type: %s'),
                    $mainitemtype
                ));
            }

            if (empty($request->request->get('name'))) {
                throw new \LogicException(__('Plug name is required'));
            }

            $mainitem = new $mainitemtype();
            $mainitem->getFromDB($request->request->get('mainitems_id'));

            $plug = new Plug();
            $plug->checkGlobal(CREATE);

            for ($i = 0; $i < $request->request->get('number'); $i++) {
                $input = [
                    'mainitemtype'  => $request->request->get('mainitemtype'),
                    'mainitems_id'  => $request->request->get('mainitems_id'),
                    'name'          => $request->request->get('name') . " - " . ($i + 1),
                    'entities_id'   => $mainitem->fields['entities_id'] ?? 0,
                    'is_recursive'  => $mainitem->fields['is_recursive'] ?? 1,
                ];
                $plug->add($input);
            }
            return new RedirectResponse($mainitem->getLinkURL());
        }
        return parent::__invoke($request);
    }
}
