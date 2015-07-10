<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief 
*/


/**
 * Light color pallet for ezcGraph based on GLPI-project style guidelines at
 * http://glpi-project.org
 *
 **/
class GraphPalette extends ezcGraphPalette {

   /**
    * Axiscolor
    *
    * ezcGraphColor
   **/
   protected $axisColor = '#e4b22b';


   /**
    * Color of grid lines
    *
    * ezcGraphColor
   **/
   protected $majorGridColor = '#D3D7DF';


   /**
    * Array with colors for datasets
    *
    * array
   **/
   protected $dataSetColor = array('#3465A4',
                                    '#4E9A06',
                                    '#CC0000',
                                    '#EDD400',
                                    '#75505B',
                                    '#F57900',
                                    '#204A87',
                                    '#C17D11');


   /**
    * Array with symbols for datasets
    *
    * array
   **/
   protected $dataSetSymbol = array(ezcGraph::BULLET);


   /**
    * Name of font to use
    *
    * string
   **/
   protected $fontName = 'sans-serif';


   /**
    * Fontcolor
    *
    * ezcGraphColor
   **/
   protected $fontColor = '#2E3436';


   /**
    * Backgroundcolor for chart
    *
    * ezcGraphColor
   **/
   protected $chartBackground = '#FFFFFF';


   /**
    * Padding in elements
    *
    * integer
   **/
   protected $padding = 1;


   /**
    * Margin of elements
    *
    * integer
   **/
   protected $margin = 0;

}
?>
