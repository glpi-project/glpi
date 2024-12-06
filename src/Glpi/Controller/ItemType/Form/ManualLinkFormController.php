<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Controller\GenericFormController;
use Glpi\Routing\Attribute\ItemtypeFormLegacyRoute;
use Glpi\Routing\Attribute\ItemtypeFormRoute;
use ManualLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Glpi\Exception\Http\NotFoundHttpException;

class ManualLinkFormController extends GenericFormController
{
    #[ItemtypeFormRoute(ManualLink::class)]
    #[ItemtypeFormLegacyRoute(ManualLink::class)]
    public function __invoke(Request $request): Response
    {
        $request->attributes->set('class', ManualLink::class);

        if ($request->query->has('id') && !(new ManualLink())->getFromDB($_REQUEST['id'])) {
            throw new NotFoundHttpException('No item found for given id');
        }

        if (
            $request->query->has('id')
            && $request->query->get('itemtype')
            && $request->query->get('items_id')
        ) {
            return $this->handleItemTypes($request);
        }

        return parent::__invoke($request);
    }

    public function handleItemTypes(Request $request): Response
    {
        $link = new ManualLink();
        $id = $link->isNewItem() ? null : $link->fields['id'];
        $itemtype = $link->isNewItem() ? $request->query->get('itemtype') : $link->fields['itemtype'];
        $items_id = $link->isNewItem() ? $request->query->get('items_id') : $link->fields['items_id'];

        $form_options = $link->getFormOptionsFromUrl($request->query->all());
        $form_options['formoptions'] = 'data-track-changes=true';
        if ($link->maybeTemplate()) {
            $form_options['withtemplate'] = $request->query->get('withtemplate', '');
        }
        $form_options['itemtype'] = $itemtype;
        $form_options['items_id'] = $items_id;

        return $this->render('pages/generic_form.html.twig', [
            'id' => $id,
            'object_class' => $link::class,
            'form_options' => $form_options,
        ]);
    }
}
