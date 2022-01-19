<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Application\View\Extension;

use Html;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class RoutingExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('index_path', [$this, 'indexPath']),
            new TwigFunction('path', [$this, 'path']),
            new TwigFunction('url', [$this, 'url']),
        ];
    }

    /**
     * Return index path.
     *
     * @return string
     */
    public function indexPath(): string
    {
        $index = '/index.php';
        if (Session::getLoginUserID() !== false) {
            $index = Session::getCurrentInterface() == 'helpdesk'
            ? 'front/helpdesk.public.php'
            : 'front/central.php';
        }
        return Html::getPrefixedUrl($index);
    }

    /**
     * Return domain-relative path of a resource.
     *
     * @param string $resource
     *
     * @return string
     */
    public function path(string $resource): string
    {
        return Html::getPrefixedUrl($resource);
    }

    /**
     * Return absolute URL of a resource.
     *
     * @param string $resource
     *
     * @return string
     */
    public function url(string $resource): string
    {
        global $CFG_GLPI;

        $prefix = $CFG_GLPI['url_base'];
        if (substr($resource, 0, 1) != '/') {
            $prefix .= '/';
        }

        return $prefix . $resource;
    }
}
