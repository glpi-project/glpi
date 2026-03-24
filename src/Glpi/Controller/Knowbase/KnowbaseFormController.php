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

use Entity;
use Entity_KnowbaseItem;
use Glpi\Controller\CrudControllerTrait;
use Glpi\Controller\GenericFormController;
use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RedirectResponse;
use Glpi\Routing\Attribute\ItemtypeFormRoute;
use Group;
use Session;
use Group_KnowbaseItem;
use Html;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User;

final class KnowbaseFormController extends GenericFormController
{
    use CrudControllerTrait;

    #[ItemtypeFormRoute(KnowbaseItem::class)]
    public function __invoke(Request $request): Response
    {
        $request->attributes->set('class', KnowbaseItem::class);

        if ($request->request->has('addvisibility')) {
            return $this->addPermission($request);
        }

        return parent::__invoke($request);
    }

    private function addPermission(Request $request): Response
    {
        $id = $request->request->getInt('knowbaseitems_id');
        $kb = new KnowbaseItem();
        if (!$kb->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $input = ['knowbaseitems_id' => $id];
        $type = $request->request->getString('_type');
        if (empty($type)) {
            throw new BadRequestHttpException();
        }

        if ($type === User::class) {
            $input['users_id'] = $request->request->getInt('users_id');
            $class             = KnowbaseItem_User::class;
        } elseif ($type === Group::class) {
            $input['groups_id']    = $request->request->getInt('groups_id');
            $input['entities_id']  = $request->request->getInt('entities_id');
            $input['is_recursive'] = $request->request->getBoolean('is_recursive');
            $class                 = Group_KnowbaseItem::class;
        } elseif ($type === Profile::class) {
            $input['profiles_id']  = $request->request->getInt('profiles_id');
            $input['entities_id']  = $request->request->getInt('entities_id');
            $input['is_recursive'] = $request->request->getBoolean('is_recursive');
            $class                 = KnowbaseItem_Profile::class;
        } elseif ($type === Entity::class) {
            $input['entities_id']  = $request->request->getInt('entities_id');
            $input['is_recursive'] = $request->request->getBoolean('is_recursive');
            $class                 = Entity_KnowbaseItem::class;
        } else {
            throw new BadRequestHttpException();
        }

        if (isset($input['entities_id']) && $input['entities_id'] == -1) {
            $input['entities_id'] = null;
            $input['no_entity_restriction'] = 1;
        }

        $item = new $class();
        if ($item->add($input)) {
            Event::log(
                $id,
                "knowbaseitem",
                4,
                "tools",
                sprintf(__('%s adds a target'), $_SESSION["glpiname"])
            );
        } else {
            Session::addMessageAfterRedirect(
                __s('This target already exists for this article.'),
                false,
                ERROR
            );
        }

        $back = Html::getBackUrl();
        if ($back) {
            return new RedirectResponse($back);
        } else {
            return new Response();
        }
    }
}
