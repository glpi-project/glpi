<?php
/**
 * File containing the ezcGraphRenderer3dOptions class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class containing the extended options for the three dimensional renderer.
 *
 * The three dimensional renderer offers a visually improved rendering compared
 * with the two dimensional renderer. This results in more options configuring
 * the three dimensional effeks, shadows and gleams in the chart.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteEzRed();
 *   $graph->title = 'Access statistics';
 *   $graph->options->label = '%2$d (%3$.1f%%)';
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   $graph->data['Access statistics']->highlight['Explorer'] = true;
 *   
 *   $graph->renderer = new ezcGraphRenderer3d();
 *   
 *   $graph->renderer->options->moveOut = .2;
 *   
 *   $graph->renderer->options->pieChartOffset = 63;
 *   
 *   $graph->renderer->options->pieChartGleam = .3;
 *   $graph->renderer->options->pieChartGleamColor = '#FFFFFF';
 *   
 *   $graph->renderer->options->pieChartShadowSize = 5;
 *   $graph->renderer->options->pieChartShadowColor = '#000000';
 *   
 *   $graph->renderer->options->legendSymbolGleam = .5;
 *   $graph->renderer->options->legendSymbolGleamSize = .9;
 *   $graph->renderer->options->legendSymbolGleamColor = '#FFFFFF';
 *   
 *   $graph->renderer->options->pieChartSymbolColor = '#55575388';
 *   
 *   $graph->renderer->options->pieChartHeight = 5;
 *   $graph->renderer->options->pieChartRotation = .8;
 *   
 *   $graph->render( 400, 150, 'tutorial_pie_chart_3d.svg' );
 * </code>
 *
 * @property bool $seperateLines
 *           Indicates wheather the full depth should be used for each line in
 *           the chart, or beeing seperated by the count of lines.
 * @property float $fillAxis
 *           Transparency used to fill the axis polygon.
 * @property float $fillGrid
 *           Transparency used to fill the grid lines.
 * @property float $depth
 *           Part of picture used to simulate depth of three dimensional chart.
 * @property float $pieChartHeight
 *           Height of the pie charts border.
 * @property float $pieChartRotation
 *           Rotation of pie chart. Defines the percent of width used to 
 *           calculate the height of the ellipse.
 * @property int $pieChartShadowSize
 *           Size of shadows.
 * @property float $pieChartShadowTransparency
 *           Used transparency for pie chart shadows.
 * @property float $pieChartShadowColor
 *           Color used for pie chart shadows.
 * @property float $barDarkenSide
 *           Factor to darken the color used for the bars side polygon.
 * @property float $barDarkenTop
 *           Factor to darken the color used for the bars top polygon.
 * @property float $barChartGleam
 *           Transparancy for gleam on bar charts
 * 
 * @version 1.5
 * @package Graph
 */
class ezcGraphRenderer3dOptions extends ezcGraphRendererOptions
{
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['seperateLines'] = true;
        $this->properties['fillAxis'] = .8;
        $this->properties['fillGrid'] = 0;
        $this->properties['depth'] = .1;
        $this->properties['pieChartHeight'] = 10.;
        $this->properties['pieChartRotation'] = .6;
        $this->properties['pieChartShadowSize'] = 0;
        $this->properties['pieChartShadowTransparency'] = .3;
        $this->properties['pieChartShadowColor'] = ezcGraphColor::fromHex( '#000000' );
        $this->properties['pieChartGleam'] = false;
        $this->properties['pieChartGleamColor'] = ezcGraphColor::fromHex( '#FFFFFF' );
        $this->properties['barDarkenSide'] = .2;
        $this->properties['barDarkenTop'] = .4;
        $this->properties['barChartGleam'] = false;

        parent::__construct( $options );
    }

    /**
     * Set an option value
     * 
     * @param string $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBasePropertyNotFoundException
     *          If a property is not defined in this class
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'fillAxis':
            case 'fillGrid':
                if ( $propertyValue !== false &&
                     !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) || 
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'false OR 0 <= float <= 1' );
                }

                $this->properties[$propertyName] = ( 
                    $propertyValue === false
                    ? false
                    : (float) $propertyValue );
                break;

            case 'depth':
            case 'pieChartRotation':
            case 'pieChartShadowTransparency':
            case 'barDarkenSide':
            case 'barDarkenTop':
            case 'barChartGleam':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) || 
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'pieChartHeight':
            case 'pieChartShadowSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'seperateLines':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['seperateLines'] = $propertyValue;
                break;
            case 'pieChartShadowColor':
                $this->properties['pieChartShadowColor'] = ezcGraphColor::create( $propertyValue );
                break;
            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
}

?>
