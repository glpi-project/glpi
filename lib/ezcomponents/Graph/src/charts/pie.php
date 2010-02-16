<?php
/**
 * File containing the ezcGraphPieChart class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class for pie charts. Can only use one dataset which will be dispalyed as a 
 * pie chart.
 *
 * <code>
 *  // Create a new pie chart
 *  $chart = new ezcGraphPieChart();
 *
 *  // Add data to line chart
 *  $chart->data['sample dataset'] = new ezcGraphArrayDataSet(
 *      array(
 *          'one' => 1.2,
 *          'two' => 43.2,
 *          'three' => -34.14,
 *          'four' => 65,
 *          'five' => 123,
 *      )   
 *  );
 *
 *  // Render chart with default 2d renderer and default SVG driver
 *  $chart->render( 500, 200, 'pie_chart.svg' );
 * </code>
 *
 * Each chart consists of several chart elements which represents logical 
 * parts of the chart and can be formatted independently. The pie chart
 * consists of:
 *  - title ( {@link ezcGraphChartElementText} )
 *  - legend ( {@link ezcGraphChartElementLegend} )
 *  - background ( {@link ezcGraphChartElementBackground} )
 *
 * All elements can be configured by accessing them as properties of the chart:
 *
 * <code>
 *  $chart->legend->position = ezcGraph::RIGHT;
 * </code>
 *
 * The chart itself also offers several options to configure the appearance.
 * The extended configure options are available in 
 * {@link ezcGraphPieChartOptions} extending the {@link ezcGraphChartOptions}.
 *
 * @property ezcGraphPieChartOptions $options
 *           Chart options class
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphPieChart extends ezcGraphChart
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
        $this->options = new ezcGraphPieChartOptions( $options );

        parent::__construct( $options );

        $this->data = new ezcGraphChartSingleDataContainer( $this );
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
        // Only draw the first (and only) dataset
        $dataset = $this->data->rewind();
        $datasetName = $this->data->key();

        $this->driver->options->font = $this->options->font;

        // Calculate sum of all values to be able to calculate percentage
        $sum = 0;
        foreach ( $dataset as $name => $value )
        {
            if ( $value < 0 )
            {
                throw new ezcGraphInvalidDataException( "Values >= 0 required, '$name' => '$value'." );
            }

            $sum += $value;
        }
        if ( $this->options->sum !== false )
        {
            $sum = max( $sum, $this->options->sum );
        }

        if ( $sum <= 0 )
        {
            throw new ezcGraphInvalidDataException( "Pie charts require a value sum > 0, your value: '$sum'." );
        }

        $angle = 0;
        foreach ( $dataset as $label => $value )
        {
            // Skip rendering values which equals 0
            if ( $value <= 0 )
            {
                continue;
            }

            switch ( $dataset->displayType->default )
            {
                case ezcGraph::PIE:
                    $displayLabel = ( $this->options->labelCallback !== null
                        ? call_user_func( $this->options->labelCallback, $label, $value, $value / $sum )
                        : sprintf( $this->options->label, $label, $value, $value / $sum * 100 ) );

                    $renderer->drawPieSegment(
                        $boundings,
                        new ezcGraphContext( $datasetName, $label, $dataset->url[$label] ),
                        $dataset->color[$label],
                        $angle,
                        $angle += $value / $sum * 360,
                        $displayLabel,
                        $dataset->highlight[$label]
                    );
                    break;
                default:
                    throw new ezcGraphInvalidDisplayTypeException( $dataset->displayType->default );
                    break;
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
        return ezcGraph::PIE;
    }

    /**
     * Apply tresh hold
     *
     * Iterates over the dataset and applies the configured tresh hold to
     * the datasets data.
     * 
     * @return void
     */
    protected function applyThreshold()
    {
        if ( $this->options->percentThreshold || $this->options->absoluteThreshold )
        {
            $dataset = $this->data->rewind();

            $sum = 0;
            foreach ( $dataset as $value )
            {
                $sum += $value;
            }
            if ( $this->options->sum !== false )
            {
                $sum = max( $sum, $this->options->sum );
            }

            $unset = array();
            foreach ( $dataset as $label => $value )
            {
                if ( $label === $this->options->summarizeCaption )
                {
                    continue;
                }

                if ( ( $value <= $this->options->absoluteThreshold ) ||
                     ( ( $value / $sum ) <= $this->options->percentThreshold ) )
                {
                    if ( !isset( $dataset[$this->options->summarizeCaption] ) )
                    {
                        $dataset[$this->options->summarizeCaption] = $value;
                    }
                    else
                    {
                        $dataset[$this->options->summarizeCaption] += $value;
                    }
                    
                    $unset[] = $label;
                }
            }

            foreach ( $unset as $label )
            {
                unset( $dataset[$label] );
            }
        }
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

        // Apply tresh hold
        $this->applyThreshold();

        // Generate legend
        $this->elements['legend']->generateFromDataSet( $this->data->rewind() );

        // Get boundings from parameters
        $this->options->width = $width;
        $this->options->height = $height;

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
     * Render the pie chart
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
