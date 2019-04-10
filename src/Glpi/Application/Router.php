<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace Glpi\Application;

use Doctrine\Common\Annotations\Reader;
use Psr\Container\ContainerInterface;
use Slim\Router as BaseRouter;

class Router extends BaseRouter
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader, ContainerInterface $container)
    {
        parent::__construct();

        $this->annotationReader = $annotationReader;
        $this->container = $container;
    }

    /**
     * Map annotated routes from controller.
     *
     * @param string $controllerClass
     *
     * @return void
     */
    public function mapContainerAnnotatedRoutes(string $controllerClass)
    {
        $controllerClassRef = new \ReflectionClass($controllerClass);
        $methods = $controllerClassRef->getMethods(\ReflectionMethod::IS_PUBLIC);

        /* @var \ReflectionMethod $methodRef */
        foreach ($methods as $methodRef) {
            /* @var \Glpi\Annotation\Route $routeAnnotation */
            $routeAnnotation = $this->annotationReader->getMethodAnnotation(
                $methodRef,
                \Glpi\Annotation\Route::class
            );

            if (null === $routeAnnotation) {
                continue;
            }

            $route = $this->map(
                [$routeAnnotation->method],
                $routeAnnotation->pattern,
                $controllerClass . ':' . $methodRef->getName()
            );
            $route->setName($routeAnnotation->name);
        }
    }
}
