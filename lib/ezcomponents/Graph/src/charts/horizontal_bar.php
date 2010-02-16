<?php
/**
 * File containing the ezcGraphBarChart class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class for bar charts. Can make use of an unlimited amount of datasets and 
 * will display them as bars by default.
 * X axis:
 *  - Labeled axis
 *  - Boxed axis label renderer
 * Y axis:
 *  - Numeric axis
 *  - Exact axis label renderer
 *
 * <code>
 *  // Create a new horizontal bar chart
 *  $chart = new ezcGraphHorizontalBarChart();
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
 *  // Render chart with the special designated renderer and default SVG driver
 *  $chart->renderer = new ezcGraphHorizontalRenderer();
 *  $chart->render( 500, 200, 'bar_chart.svg' );
 * </code>
 *
 * Each chart consists of several chart elements which represents logical 
 * parts of the chart and can be formatted independently. The bar chart
 * consists of:
 *  - title ( {@link ezcGraphChartElementText} )
 *  - legend ( {@link ezcGraphChartElementLegend} )
 *  - background ( {@link ezcGraphChartElementBackground} )
 *  - xAxis ( {@link ezcGraphChartElementLabeledAxis} )
 *  - yAxis ( {@link ezcGraphChartElementNumericAxis} )
 *
 * The type of the axis may be changed and all elements can be configured by
 * accessing them as properties of the chart:
 *
 * <code>
 *  $chart->legend->position = ezcGraph::RIGHT;
 * </code>
 *
 * The chart itself also offers several options to configure the appearance. As
 * bar charts extend line charts the the extended configure options are
 * available in {@link ezcGraphLineChartOptions} extending the 
 * {@link ezcGraphChartOptions}.
 *
 * @property ezcGraphLineChartOptions $options
 *           Chart options class
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphHorizontalBarChart extends ezcGraphBarChart
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
        parent::__construct();

        $this->addElement( 'xAxis', new ezcGraphChartElementNumericAxis() );
        $this->elements['xAxis']->axisLabelRenderer = new ezcGraphAxisCenteredLabelRenderer();
        $this->elements['xAxis']->position = ezcGraph::LEFT;

        $this->addElement( 'yAxis', new ezcGraphChartElementLabeledAxis() );
        $this->elements['yAxis']->axisLabelRenderer = new ezcGraphAxisBoxedLabelRenderer();
        $this->elements['yAxis']->position = ezcGraph::BOTTOM;

        $this->renderer = new ezcGraphHorizontalRenderer();
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
    protected function renderData( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphBoundings $innerBoundings )
    {
        // Use inner boundings for drawning chart data
        $boundings = $innerBoundings;

        $yAxisNullPosition = $this->elements['xAxis']->getCoordinate( false );

        // Initialize counters
        $nr = array();
        $count = array();

        foreach ( $this->data as $data )
        {
            if ( !isset( $nr[$data->displayType->default] ) )
            {
                $nr[$data->displayType->default] = 0;
                $count[$data->displayType->default] = 0;
            }

            $nr[$data->displayType->default]++;
            $count[$data->displayType->default]++;
        }

        $checkedRegularSteps = false;

        // Display data
        foreach ( $this->data as $datasetName => $data )
        {
            --$nr[$data->displayType->default];

            // Check which axis should be used
            $xAxis = ( $data->xAxis->default ? $data->xAxis->default: $this->elements['xAxis'] );
            $yAxis = ( $data->yAxis->default ? $data->yAxis->default: $this->elements['yAxis'] );

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

            // Ensure regular steps on axis when used with bar charts and
            // precalculate some values use to render bar charts
            //
            // Called only once and only when bars should be rendered
            if ( ( $checkedRegularSteps === false ) &&
                 ( $data->displayType->default === ezcGraph::BAR ) )
            {
                $height = $this->calculateStepWidth( $yAxis, $xAxis, $boundings->height )->y;
            }

            // Draw lines for dataset
            $lastPoint = false;
            foreach ( $data as $key => $value )
            {
                // Calculate point in chart
                $point = $xAxis->axisLabelRenderer->modifyChartDataPosition( 
                    $yAxis->axisLabelRenderer->modifyChartDataPosition(
                        new ezcGraphCoordinate( 
                            $xAxis->getCoordinate( $value ),
                            $yAxis->getCoordinate( $key )
                        )
                    )
                );

                // Render depending on display type of dataset
                switch ( true )
                {
                    case $data->displayType->default === ezcGraph::BAR:
                        $renderer->drawHorizontalBar(
                            $boundings,
                            new ezcGraphContext( $datasetName, $key, $data->url[$key] ),
                            $data->color[$key],
                            $point,
                            $height,
                            $nr[$data->displayType->default],
                            $count[$data->displayType->default],
                            $data->symbol[$key],
                            $yAxisNullPosition
                        );

                        // Render highlight string if requested
                        if ( $data->highlight[$key] )
                        {
                            $renderer->drawDataHighlightText(
                                $boundings,
                                new ezcGraphContext( $datasetName, $key, $data->url[$key] ),
                                $point,
                                $yAxisNullPosition,
                                $nr[$data->displayType->default],
                                $count[$data->displayType->default],
                                $this->options->highlightFont,
                                ( $data->highlightValue[$key] ? $data->highlightValue[$key] : $value ),
                                $this->options->highlightSize + $this->options->highlightFont->padding * 2,
                                ( $this->options->highlightLines ? $data->color[$key] : null ),
                                ( $this->options->highlightXOffset ? $this->options->highlightXOffset : 0 ),
                                ( $this->options->highlightYOffset ? $this->options->highlightYOffset : 0 ),
                                $height,
                                $data->displayType->default
                            );
                        }
                        break;
                    default:
                        throw new ezcGraphInvalidDisplayTypeException( $data->displayType->default );
                        break;
                }
    
                // Store last point, used to connect lines in line chart.
                $lastPoint = $point;
            }
        }
    }

    /**
     * Aggregate and calculate value boundings on axis.
     *
     * This function is nearly the same as in ezcGraphLineChart, but reverses
     * the usage of keys and values for the axis.
     * 
     * @return void
     */
    protected function setAxisValues()
    {
        // Virtual data set build for agrregated values sums for bar charts
        $virtualBarSumDataSet = array( array(), array() );

        // Calculate axis scaling and labeling
        foreach ( $this->data as $dataset )
        {
            $nr = 0;
            $labels = array();
            $values = array();
            foreach ( $dataset as $label => $value )
            {
                $labels[] = $label;
                $values[] = $value;

                // Build sum of all bars
                if ( $this->options->stackBars &&
                     ( $dataset->displayType->default === ezcGraph::BAR ) )
                {
                    if ( !isset( $virtualBarSumDataSet[(int) $value >= 0][$nr] ) )
                    {
                        $virtualBarSumDataSet[(int) $value >= 0][$nr++] = $value;
                    }
                    else
                    {
                        $virtualBarSumDataSet[(int) $value >= 0][$nr++] += $value;
                    }
                }
            }

            // Check if data has been associated with another custom axis, use
            // default axis otherwise.
            if ( $dataset->xAxis->default )
            {
                $dataset->xAxis->default->addData( $values );
            }
            else
            {
                $this->elements['xAxis']->addData( $values );
            }

            if ( $dataset->yAxis->default )
            {
                $dataset->yAxis->default->addData( array_reverse( $labels ) );
            }
            else
            {
                $this->elements['yAxis']->addData( array_reverse( $labels ) );
            }
        }

        // There should always be something assigned to the main x and y axis.
        if ( !$this->elements['xAxis']->initialized ||
             !$this->elements['yAxis']->initialized )
        {
            throw new ezcGraphNoDataException();
        }

        // Calculate boundings from assigned data
        $this->elements['xAxis']->calculateAxisBoundings();
        $this->elements['yAxis']->calculateAxisBoundings();
    }
}
?>
