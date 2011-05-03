<?php
/**
 * File containing the ezcGraphPaletteBlack class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Dark color pallet for ezcGraph based on Tango style guidelines at
 * http://tango-project.org/Generic_Icon_Theme_Guidelines
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphPaletteBlack extends ezcGraphPalette
{
    /**
     * Axiscolor 
     * 
     * @var ezcGraphColor
     */
    protected $axisColor = '#EEEEEC';

    /**
     * Color of grid lines
     * 
     * @var ezcGraphColor
     */
    protected $majorGridColor = '#888A85';

    /**
     * Color of minor grid lines
     * 
     * @var ezcGraphColor
     */
    protected $minorGridColor = '#888A8588';

    /**
     * Array with colors for datasets
     * 
     * @var array
     */
    protected $dataSetColor = array(
        '#729FCF',
        '#EF2929',
        '#FCE94F',
        '#8AE234',
        '#F57900',
        '#AD7FA8',

    );

    /**
     * Array with symbols for datasets 
     * 
     * @var array
     */
    protected $dataSetSymbol = array(
        ezcGraph::BULLET,
    );

    /**
     * Name of font to use
     * 
     * @var string
     */
    protected $fontName = 'sans-serif';

    /**
     * Fontcolor 
     * 
     * @var ezcGraphColor
     */
    protected $fontColor = '#D3D7CF';

    /**
     * Backgroundcolor 
     * 
     * @var ezcGraphColor
     */
    protected $chartBackground = '#2E3436';

    /**
     * Border color for chart elements
     * 
     * @var string
     */
    protected $elementBorderColor = '#555753';

    /**
     * Border width for chart elements
     * 
     * @var integer
     */
    protected $elementBorderWidth = 1;

    /**
     * Padding in elements
     * 
     * @var integer
     */
    protected $padding = 1;

    /**
     * Margin of elements
     * 
     * @var integer
     */
    protected $margin = 1;
}

?>
