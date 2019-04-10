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

namespace Glpi\Application\View\TwigExtension;

class Reflection extends \Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('call_static', [$this, 'callStaticMethod']),
            new \Twig_SimpleFunction('get_static', [$this, 'getStaticProperty']),
            new \Twig_SimpleFunction('getitem', [$this, 'getItemForItemtype']),
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('instanceof', [$this, 'isInstanceOf']),
        ];
    }

    public function callStaticMethod($class, $method, array $args = [])
    {
        $refl = new \reflectionClass($class);

       // Check that method is static AND public
        if ($refl->hasMethod($method) && $refl->getMethod($method)->isStatic() && $refl->getMethod($method)->isPublic()) {
            return call_user_func_array($class.'::'.$method, $args);
        }

        throw new \RuntimeException(sprintf('Invalid static method call for class %s and method %s', $class, $method));
    }

    public function getStaticProperty($class, $property)
    {
        $refl = new \reflectionClass($class);

       // Check that property is static AND public
        if ($refl->hasProperty($property) && $refl->getProperty($property)->isStatic() && $refl->getProperty($property)->isPublic()) {
            return $refl->getProperty($property)->getValue();
        }

        throw new \RuntimeException(sprintf('Invalid static property get for class %s and property %s', $class, $property));
    }

    public function getItemForItemtype($itemtype)
    {
        return getItemForItemtype($itemtype);
    }

    public function isInstanceOf($object, $class)
    {
        $reflection = new \ReflectionClass($class);
        return $reflection->isInstance($object);
    }
}
