<?php
/**
 * File containing the ezcGraphRadarChart class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class for radar charts.
 * Can make use of an unlimited amount of datasets and will display them as
 * lines by default.
 * Rotation axis:
 *  - Labeled axis
 *  - Centered axis label renderer
 * Axis:
 *  - Numeric axis
 *  - radar axis label renderer
 *
 * <code>
 *  // Create a new radar chart
 *  $chart = new ezcGraphRadarChart();
 *
 *  // Add data to line chart
 *  $chart->data['sample dataset'] = new ezcGraphArrayDataSet(
 *      array(
 *          '100' => 1.2,
 *          '200' => 43.2,
 *          '300' => -34.14,
 *          '350' => 65,
 *          '400' => 123,
 *      )   
 *  );
 *
 *  // Render chart with default 2d renderer and default SVG driver
 *  $chart->render( 500, 200, 'radar_chart.svg' );
 * </code>
 *
 * Each chart consists of several chart elements which represents logical 
 * parts of the chart and can be formatted independently. The line chart
 * consists of:
 *  - title ( {@link ezcGraphChartElementText} )
 *  - legend ( {@link ezcGraphChartElementLegend} )
 *  - background ( {@link ezcGraphChartElementBackground} )
 *  - axis ( {@link ezcGraphChartElementNumericAxis} )
 *  - ratation axis ( {@link ezcGraphChartElementLabeledAxis} )
 *
 * The type of the axis may be changed and all elements can be configured by
 * accessing them as properties of the chart:
 *
 * The chart itself also offers several options to configure the appearance.
 * The extended configure options are available in 
 * {@link ezcGraphRadarChartOptions} extending the 
 * {@link ezcGraphChartOptions}.
 *
 * <code>
 *  $chart->legend->position = ezcGraph::RIGHT;
 * </code>
 *
 * @property ezcGraphRadarChartOptions $options
 *           Chart options class
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphRadarChart extends ezcGraphChart
{
    /**
     * Store major grid color for child axis.
     * 
     * @var ezcGraphColor
     */
    protected $childAxisColor;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->options = new ezcGraphRadarChartOptions( $options );
        $this->options->highlightFont = $this->options->font;

        parent::__construct();

        $this->elements['rotationAxis'] = new ezcGraphChartElementLabeledAxis();

        $this->addElement( 'axis', new ezcGraphChartElementNumericAxis() );
        $this->elements['axis']->position = ezcGraph::BOTTOM;
        $this->elements['axis']->axisLabelRenderer = new ezcGraphAxisRadarLabelRenderer();
        $this->elements['axis']->axisLabelRenderer->outerStep = true;

        $this->addElement( 'rotationAxis', new ezcGraphChartElementLabeledAxis() );

        // Do not render axis with default method, because we need an axis for
        // each label in dataset
        $this->renderElement['axis'] = false;
        $this->renderElement['rotationAxis'] = false;
    }

    /**
     * Set colors and border fro this element
     * 
     * @param ezcGraphPalette $palette Palette
     * @return void
     */
    public function setFromPalette( ezcGraphPalette $palette )
    {
        $this->childAxisColor = $palette->majorGridColor;

        parent::setFromPalette( $palette );
    }

    /**
     * Property write access
     * 
     * @throws ezcBasePropertyNotFoundException
     *          If Option could not be found
     * @throws ezcBaseValueException
     *          If value is out of range
     * @param string $propertyName Option name
     * @param mixed $propertyValue Option value;
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue ) 
    {
        switch ( $propertyName ) {
            case 'axis':
                if ( $propertyValue instanceof ezcGraphChartElementAxis )
                {
                    $this->addElement( 'axis', $propertyValue );
                    $this->elements['axis']->position = ezcGraph::BOTTOM;
                    $this->elements['axis']->axisLabelRenderer = new ezcGraphAxisRadarLabelRenderer();
                    $this->renderElement['axis'] = false;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphChartElementAxis' );
                }
                break;
            case 'rotationAxis':
                if ( $propertyValue instanceof ezcGraphChartElementAxis )
                {
                    $this->addElement( 'rotationAxis', $propertyValue );
                    $this->renderElement['rotationAxis'] = false;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphChartElementAxis' );
                }
                break;
            case 'renderer':
                if ( $propertyValue instanceof ezcGraphRadarRenderer )
                {
                    parent::__set( $propertyName, $propertyValue );
                }
                else 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphRadarRenderer' );
                }
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
        }
    }

    /**
     * Draws a single rotated axis
     *
     * Sets the axis label position depending on the axis rotation.
     * 
     * @param ezcGraphChartElementAxis $axis 
     * @param ezcGraphBoundings $boundings 
     * @param ezcGraphCoordinate $center 
     * @param float $position 
     * @param float $lastPosition 
     * @return void
     */
    protected function drawRotatedAxis( ezcGraphChartElementAxis $axis, ezcGraphBoundings $boundings, ezcGraphCoordinate $center, $position, $lastPosition = null )
    {
        // Set axis position depending on angle for better axis label 
        // positioning
        $angle = $position * 2 * M_PI;
        switch ( (int) ( ( $position + .125 ) * 4 ) )
        {
            case 0:
            case 4:
                $axis->position = ezcGraph::BOTTOM;
                break;
            case 1:
                $axis->position = ezcGraph::LEFT;
                break;
            case 2:
                $axis->position = ezcGraph::TOP;
                break;
            case 3:
                $axis->position = ezcGraph::RIGHT;
                break;
        }

        // Set last step to correctly draw grid
        if ( $axis->axisLabelRenderer instanceof ezcGraphAxisRadarLabelRenderer )
        {
            $axis->axisLabelRenderer->lastStep = $lastPosition;
        }

        // Do not draw axis label for last step
        if ( abs( $position - 1 ) <= .001 ) 
        {
            $axis->label = null;
        }

        $this->renderer->drawAxis(
            $boundings,
            clone $center,
            $dest = new ezcGraphCoordinate(
                $center->x + sin( $angle ) * ( $boundings->width / 2 ),
                $center->y - cos( $angle ) * ( $boundings->height / 2 )
            ),
            clone $axis,
            clone $axis->axisLabelRenderer
        );
    }

    /**
     * Render the assigned data
     *
     * Will renderer all charts data in the remaining boundings after drawing 
     * all other chart elements. The data will be rendered depending on the 
     * settings in the dataset.
     * 
     * @param ezcGraphRenderer $renderer Renderer
     * @param ezcGraphBoundings $boundings Remaining boundings
     * @return void
     */
    protected function renderData( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings )
    {
        // Apply axis space
        $xAxisSpace = ( $boundings->x1 - $boundings->x0 ) * $this->axis->axisSpace;
        $yAxisSpace = ( $boundings->y1 - $boundings->y0 ) * $this->axis->axisSpace;

        $center = new ezcGraphCoordinate(
            ( $boundings->width / 2 ),
            ( $boundings->height / 2 )
        );

        // We do not differentiate between display types in radar charts.
        $nr = $count = count( $this->data );

        // Draw axis at major steps of virtual axis
        $steps = $this->elements['rotationAxis']->getSteps();
        $lastStepPosition = null;
        $axisColor = $this->elements['axis']->border;
        foreach ( $steps as $step )
        {
            $this->elements['axis']->label = $step->label;
            $this->drawRotatedAxis( $this->elements['axis'], $boundings, $center, $step->position, $lastStepPosition );
            $lastStepPosition = $step->position;

            if ( count( $step->childs ) )
            {
                foreach ( $step->childs as $childStep )
                {
                    $this->elements['axis']->label = null;
                    $this->elements['axis']->border = $this->childAxisColor;

                    $this->drawRotatedAxis( $this->elements['axis'], $boundings, $center, $childStep->position, $lastStepPosition );
                    $lastStepPosition = $childStep->position;
                }
            }

            $this->elements['axis']->border = $axisColor;
        }

        // Display data
        $this->elements['axis']->position = ezcGraph::TOP;
        foreach ( $this->data as $datasetName => $data )
        {
            --$nr;
            // Determine fill color for dataset
            if ( $this->options->fillLines !== false )
            {
                $fillColor = clone $data->color->default;
                $fillColor->alpha = (int) round( ( 255 - $fillColor->alpha ) * ( $this->options->fillLines / 255 ) );
            }
            else
            {
                $fillColor = null;
            }

            // Draw lines for dataset
            $lastPoint = false;
            foreach ( $data as $key => $value )
            {
                $point = new ezcGraphCoordinate( 
                    $this->elements['rotationAxis']->getCoordinate( $key ),
                    $this->elements['axis']->getCoordinate( $value )
                ); 

                /* Transformation required for 3d like renderers ... 
                 * which axis should transform here?
                $point = $this->elements['xAxis']->axisLabelRenderer->modifyChartDataPosition( 
                    $this->elements['yAxis']->axisLabelRenderer->modifyChartDataPosition(
                        new ezcGraphCoordinate( 
                            $this->elements['xAxis']->getCoordinate( $key ),
                            $this->elements['yAxis']->getCoordinate( $value )
                        )
                    )
                ); 
                // */

                $renderer->drawRadarDataLine(
                    $boundings,
                    new ezcGraphContext( $datasetName, $key, $data->url[$key] ),
                    $data->color->default,
                    clone $center,
                    ( $lastPoint === false ? $point : $lastPoint ),
                    $point,
                    $nr,
                    $count,
                    $data->symbol[$key],
                    $data->color[$key],
                    $fillColor,
                    $this->options->lineThickness
                );

                $lastPoint = $point;
            }
        }
    }

    /**
     * Returns the default display type of the current chart type.
     * 
     * @return int Display type
     */
    public function getDefaultDisplayType()
    {
        return ezcGraph::LINE;
    }

    /**
     * Renders the basic elements of this chart type
     * 
     * @param int $width 
     * @param int $height 
     * @return void
     */
    protected function renderElements( $width, $height )
    {
        if ( !count( $this->data ) )
        {
            throw new ezcGraphNoDataException();
        }

        // Set image properties in driver
        $this->driver->options->width = $width;
        $this->driver->options->height = $height;

        // Calculate axis scaling and labeling
        foreach ( $this->data as $dataset )
        {
            $labels = array();
            $values = array();
            foreach ( $dataset as $label => $value )
            {
                $labels[] = $label;
                $values[] = $value;
            }

            $this->elements['axis']->addData( $values );
            $this->elements['rotationAxis']->addData( $labels );
        }

        $this->elements['axis']->calculateAxisBoundings();
        $this->elements['rotationAxis']->calculateAxisBoundings();

        // Generate legend
        $this->elements['legend']->generateFromDataSets( $this->data );

        // Get boundings from parameters
        $this->options->width = $width;
        $this->options->height = $height;

        // Render subelements
        $boundings = new ezcGraphBoundings();
        $boundings->x1 = $this->options->width;
        $boundings->y1 = $this->options->height;

        // Render subelements
        foreach ( $this->elements as $name => $element )
        {
            // Skip element, if it should not get rendered
            if ( $this->renderElement[$name] === false )
            {
                continue;
            }

            $this->driver->options->font = $element->font;
            $boundings = $element->render( $this->renderer, $boundings );
        }

        // Render graph
        $this->renderData( $this->renderer, $boundings );
    }

    /**
     * Render the line chart
     *
     * Renders the chart into a file or stream. The width and height are 
     * needed to specify the dimensions of the resulting image. For direct
     * output use 'php://stdout' as output file.
     * 
     * @param int $width Image width
     * @param int $height Image height
     * @param string $file Output file
     * @apichange
     * @return void
     */
    public function render( $width, $height, $file = null )
    {
        $this->renderElements( $width, $height );

        if ( !empty( $file ) )
        {
            $this->renderer->render( $file );
        }

        $this->renderedFile = $file;
    }

    /**
     * Renders this chart to direct output
     * 
     * Does the same as ezcGraphChart::render(), but renders directly to 
     * output and not into a file.
     *
     * @param int $width
     * @param int $height
     * @apichange
     * @return void
     */
    public function renderToOutput( $width, $height )
    {
        // @TODO: merge this function with render an deprecate ommit of third 
        // argument in render() when API break is possible
        $this->renderElements( $width, $height );
        $this->renderer->render( null );
    }
}
?>
