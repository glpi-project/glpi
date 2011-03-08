<?php
/**
 * File containing the ezcGraphRenderer2dOptions class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class containing the basic options for renderers.
 *
 * Renderer options are used to define the general appearance of charts beside
 * the palettes. The renderer transforms chart primitives (like the legend, or
 * one pie slice) into image primitives, which are then rendered by the
 * drivers. The way this transformation is done, and which effects are also
 * rendered is specified by the values in this option class. 
 *
 * The example below shows some basic bar rendering options, which are
 * available in all renderers. You mya want to check the tutorial sections
 * about the renderer, which show example output for more renderer options.
 *
 * <code>
 *   $wikidata = include 'tutorial_wikipedia_data.php';
 *   
 *   $graph = new ezcGraphBarChart();
 *   $graph->title = 'Wikipedia articles';
 *   
 *   // Add data
 *   foreach ( $wikidata as $language => $data )
 *   {
 *       $graph->data[$language] = new ezcGraphArrayDataSet( $data );
 *   }
 *   
 *   // $graph->renderer = new ezcGraphRenderer2d();
 *   
 *   $graph->renderer->options->barMargin = .2;
 *   $graph->renderer->options->barPadding = .2;
 *   
 *   $graph->renderer->options->dataBorder = 0;
 *   
 *   $graph->render( 400, 150, 'tutorial_bar_chart_options.svg' );
 * </code>
 *
 * For additional options, which are special to some chart type you may
 * also want to check the option classes for the repective chart type you
 * are using and the elements of the chart. The chart type dependant option
 * classes are:
 *
 * - ezcGraphLineChartOptions
 * - ezcGraphPieChartOptions
 * - ezcGraphRadarChartOptions
 *
 * There may be additional options dependant on the renderer you are using.
 * You may want to check the extensions of this class:
 *
 * - ezcGraphRenderer2dOptions
 * - ezcGraphRenderer3dOptions
 *
 * @property float $maxLabelHeight
 *           Percent of chart height used as maximum height for pie chart 
 *           labels.
 * @property bool $showSymbol
 *           Indicates wheather to show the line between pie elements and 
 *           labels.
 * @property float $symbolSize
 *           Size of symbols used to connect a label with a pie.
 * @property float $moveOut
 *           Percent to move pie chart elements out of the middle on highlight.
 * @property int $titlePosition
 *           Position of title in a box.
 * @property int $titleAlignement
 *           Alignement of box titles.
 * @property float $dataBorder
 *           Factor to darken border of data elements, like lines, bars and 
 *           pie segments.
 * @property float $barMargin
 *           Percentual distance between bar blocks.
 * @property float $barPadding
 *           Percentual distance between bars.
 * @property float $pieChartOffset
 *           Offset for starting with first pie chart segment in degrees.
 * @property float $legendSymbolGleam
 *           Opacity of gleam in legend symbols
 * @property float $legendSymbolGleamSize
 *           Size of gleam in legend symbols
 * @property float $legendSymbolGleamColor
 *           Color of gleam in legend symbols
 * @property float $pieVerticalSize
 *           Percent of vertical space used for maximum pie chart size.
 * @property float $pieHorizontalSize
 *           Percent of horizontal space used for maximum pie chart size.
 * @property float $pieChartSymbolColor
 *           Color of pie chart symbols
 * @property float $pieChartGleam
 *           Enhance pie chart with gleam on top.
 * @property float $pieChartGleamColor
 *           Color used for gleam on pie charts.
 * @property float $pieChartGleamBorder
 *           Do not draw gleam on an outer border of this size.
 * @property bool $syncAxisFonts
 *           Synchronize fonts of axis. With the defaut true value, the only
 *           the fonts of the yAxis will be used.
 * @property bool $axisEndStyle
 *           Style of axis end markers. Defauls to arrow heads, but you may
 *           also use all symbol constants defined ein the ezcGraph class,
 *           especially ezcGraph::NO_SYMBOL.
 * @property bool $shortAxis
 *           Defines wheather to render the axis extending the chart boundings
 *           or stop them at the chart boundings. Deafults to false.
 * 
 * @version 1.5
 * @package Graph
 */
class ezcGraphRendererOptions extends ezcGraphChartOptions
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
        $this->properties['maxLabelHeight'] = .10;
        $this->properties['showSymbol'] = true;
        $this->properties['symbolSize'] = 6;
        $this->properties['moveOut'] = .1;
        $this->properties['titlePosition'] = ezcGraph::TOP;
        $this->properties['titleAlignement'] = ezcGraph::MIDDLE | ezcGraph::CENTER;
        $this->properties['dataBorder'] = .5;
        $this->properties['barMargin'] = .1;
        $this->properties['barPadding'] = .05;
        $this->properties['pieChartOffset'] = 0;
        $this->properties['pieChartSymbolColor'] = ezcGraphColor::fromHex( '#000000' );
        $this->properties['pieChartGleam'] = false;
        $this->properties['pieChartGleamColor'] = ezcGraphColor::fromHex( '#FFFFFF' );
        $this->properties['pieChartGleamBorder'] = 0;
        $this->properties['legendSymbolGleam'] = false;
        $this->properties['legendSymbolGleamSize'] = .9;
        $this->properties['legendSymbolGleamColor'] = ezcGraphColor::fromHex( '#FFFFFF' );
        $this->properties['pieVerticalSize'] = .5;
        $this->properties['pieHorizontalSize'] = .25;
        $this->properties['syncAxisFonts'] = true;
        $this->properties['axisEndStyle'] = ezcGraph::ARROW;
        $this->properties['shortAxis'] = false;

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
            case 'dataBorder':
            case 'pieChartGleam':
            case 'legendSymbolGleam':
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

            case 'maxLabelHeight':
            case 'moveOut':
            case 'barMargin':
            case 'barPadding':
            case 'legendSymbolGleamSize':
            case 'pieVerticalSize':
            case 'pieHorizontalSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'symbolSize':
            case 'titlePosition':
            case 'titleAlignement':
            case 'pieChartGleamBorder':
            case 'axisEndStyle':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties[$propertyName] = (int) $propertyValue;
                break;

            case 'showSymbol':
            case 'syncAxisFonts':
            case 'shortAxis':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }
                $this->properties[$propertyName] = (bool) $propertyValue;
                break;

            case 'pieChartOffset':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 360 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 360' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'pieChartSymbolColor':
            case 'pieChartGleamColor':
            case 'legendSymbolGleamColor':
                $this->properties[$propertyName] = ezcGraphColor::create( $propertyValue );
                break;

            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
}

?>
