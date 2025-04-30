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

namespace Glpi\Controller\ItemType\Form;

use Contact;
use Glpi\Controller\GenericFormController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Routing\Attribute\ItemtypeFormRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactFormController extends GenericFormController
{
    #[ItemtypeFormRoute(Contact::class)]
    public function __invoke(Request $request): Response
    {
        $request->attributes->set('class', Contact::class);

        if ($request->query->has('getvcard')) {
            return $this->generateVCard($request);
        }

        return parent::__invoke($request);
    }

    private function generateVCard(Request $request): Response
    {
        $id = $request->query->getInt('id');

        if (Contact::isNewID($id)) {
            throw new BadRequestHttpException();
        }

        $contact = new Contact();
        if (!$contact->can($id, READ)) {
            throw new AccessDeniedHttpException();
        }

        return new StreamedResponse(fn() => $contact->generateVcard());
    }
}
