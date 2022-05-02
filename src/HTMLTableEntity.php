<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * @since 0.84
 *
 * The base entity for the table. The entity is the base of kind of cell (header or not). It
 * provides facilities to manage the cells such as attributs or specific content (mixing of strings
 * and call of method during table display)
 **/
abstract class HTMLTableEntity
{
    private $html_id    = '';
    private $html_style = [];
    private $html_class = [];

    private $content;


    /**
     * Constructor of an entity
     *
     * @param string $content
     *    The content of a cell, header, ... Can simply be a string. But it can also
     *    be a call to a specific function during the rendering of the table in case
     *    of direct display function (for instance: Dropdown::showNumber). A function
     *    call is an array containing two elements : 'function', the name the function
     *    and 'parameters', an array of the parameters given to the function.
     **/
    public function __construct($content)
    {
        $this->content = $content;
    }


    /**
     * @param $origin
     **/
    public function copyAttributsFrom(HTMLTableEntity $origin)
    {

        $this->html_id    = $origin->html_id;
        $this->html_style = $origin->html_style;
        $this->html_class = $origin->html_class;
    }


    /**
     * @param $html_id
     **/
    public function setHTMLID($html_id)
    {
        $this->html_id = $html_id;
    }


    /**
     * userfull ? function never called
     *
     * @param $html_style
     **/
    public function setHTMLStyle($html_style)
    {
        if (is_array($html_style)) {
            $this->html_style = array_merge($this->html_style, $html_style);
        } else {
            $this->html_style[] = $html_style;
        }
    }


    /**
     * @param $html_class
     **/
    public function setHTMLClass($html_class)
    {
        if (is_array($html_class)) {
            $this->html_class = array_merge($this->html_class, $html_class);
        } else {
            $this->html_class[] = $html_class;
        }
    }


    /**
     * @param $options   array
     **/
    public function displayEntityAttributs(array $options = [])
    {

        $id = $this->html_id;
        if (isset($options['id'])) {
            $id = $options['id'];
        }
        if (!empty($id)) {
            echo " id='$id'";
        }

        $style = $this->html_style;
        if (isset($options['style'])) {
            if (is_array($options['style'])) {
                $style = array_merge($style, $options['style']);
            } else {
                $style[] = $options['style'];
            }
        }
        if (count($style) > 0) {
            echo " style='" . implode(';', $style) . "'";
        }

        $class = $this->html_class;
        if (isset($options['class'])) {
            if (is_array($options['class'])) {
                $class = array_merge($class, $options['class']);
            } else {
                $class[] = $options['class'];
            }
        }
        if (count($class) > 0) {
            echo " class='" . implode(' ', $class) . "'";
        }
    }


    /**
     * @param $content
     **/
    public function setContent($content)
    {
        $this->content = $content;
    }


    public function displayContent()
    {

        if (is_array($this->content)) {
            foreach ($this->content as $content) {
                if (is_string($content)) {
                   // Manage __RAND__ to be computed on display
                    $content = str_replace('__RAND__', mt_rand(), $content);
                    echo $content;
                } else if (isset($content['function'])) {
                    if (isset($content['parameters'])) {
                        $parameters = $content['parameters'];
                    } else {
                        $parameters = [];
                    }
                    call_user_func_array($content['function'], $parameters);
                }
            }
        } else {
            // Manage __RAND__ to be computed on display
            $content = $this->content;
            $content = str_replace('__RAND__', mt_rand(), $content);
            echo $content;
        }
    }
}
