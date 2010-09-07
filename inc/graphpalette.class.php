<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


/**
 * Light color pallet for ezcGraph based on GLPI-project style guidelines at
 * http://glpi-project.org
 *
 **/
class GraphPalette extends ezcGraphPalette {

   /**
    * Axiscolor
    *
    * @var ezcGraphColor
    **/
    protected $axisColor = '#e4b22b';


   /**
    * Color of grid lines
    *
    * @var ezcGraphColor
    **/
    protected $majorGridColor = '#D3D7DF';


   /**
    * Array with colors for datasets
    *
    * @var array
    **/
    protected $dataSetColor = array('#bfcc7a',
                                    '#d0d99d',
                                    '#e6b940',
                                    '#efd283',
                                    '#4F6C57');


   /**
    * Array with symbols for datasets
    *
    * @var array
    **/
    protected $dataSetSymbol = array(ezcGraph::BULLET);


   /**
    * Name of font to use
    *
    * @var string
    **/
    protected $fontName = 'sans-serif';


   /**
    * Fontcolor
    *
    * @var ezcGraphColor
    **/
    protected $fontColor = '#2E3436';


   /**
    * Backgroundcolor for chart
    *
    * @var ezcGraphColor
    **/
    protected $chartBackground = '#FFFFFF';


   /**
    * Padding in elements
    *
    * @var integer
    **/
    protected $padding = 1;


   /**
    * Margin of elements
    *
    * @var integer
    **/
    protected $margin = 0;

}

?>
