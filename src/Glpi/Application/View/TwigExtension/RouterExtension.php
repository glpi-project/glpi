<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
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
namespace Glpi\Application\View\TwigExtension;

use Twig_Extension;
use Twig_SimpleFunction;
use Glpi\Application\Router;

class RouterExtension extends Twig_Extension
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getName()
    {
        return 'router';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('path_for', array($this, 'pathFor')),
            new Twig_SimpleFunction('base_path', array($this, 'basePath')),
        ];
    }

    public function pathFor($name, $data = [], $queryParams = [])
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    public function basePath()
    {
        return $this->router->getBasePath();
    }
}
