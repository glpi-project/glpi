<?php
/**
 * File containing the abstract ezcGraphAxisNoLabelRenderer class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Omits axis labels, steps and grid.
 *
 * <code>
 *   $chart->xAxis->axisLabelRenderer = new ezcGraphAxisNoLabelRenderer();
 * </code>
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphAxisNoLabelRenderer extends ezcGraphAxisLabelRenderer
{
    /**
     * Render Axis labels
     *
     * Render labels for an axis.
     *
     * @param ezcGraphRenderer $renderer Renderer used to draw the chart
     * @param ezcGraphBoundings $boundings Boundings of the axis
     * @param ezcGraphCoordinate $start Axis starting point
     * @param ezcGraphCoordinate $end Axis ending point
     * @param ezcGraphChartElementAxis $axis Axis instance
     * @return void
     */
    public function renderLabels(
        ezcGraphRenderer $renderer,
        ezcGraphBoundings $boundings,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        ezcGraphChartElementAxis $axis )
    {
        return true;
    }
}
?>
