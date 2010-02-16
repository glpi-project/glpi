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
 *  // Create a new line chart
 *  $chart = new ezcGraphBarChart();
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
class ezcGraphBarChart extends ezcGraphLineChart
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

        $this->elements['xAxis']->axisLabelRenderer = new ezcGraphAxisBoxedLabelRenderer();
    }

    /**
     * Returns the default display type of the current chart type.
     * 
     * @return int Display type
     */
    public function getDefaultDisplayType()
    {
        return ezcGraph::BAR;
    }
}
?>
