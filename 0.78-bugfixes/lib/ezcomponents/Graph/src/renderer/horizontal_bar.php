<?php
/**
 * File containing the two dimensional renderer
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class to transform horizontal bar charts primitives into image primitives.
 * Renders charts in a two dimensional view.
 *
 * The class options are defined in the class {@link ezcGraphRenderer2dOptions}
 * extending the basic renderer options in {@link ezcGraphRendererOptions}.
 *
 * <code>
 *  @TODO: Add example
 * </code>
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphHorizontalRenderer 
    extends
        ezcGraphRenderer2d
    implements
        ezcGraphHorizontalBarRenderer
{
    /**
     * Draw horizontal bar
     *
     * Draws a horizontal bar as a data element in a line chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $position Position of data point
     * @param float $stepSize Space which can be used for bars
     * @param int $dataNumber Number of dataset
     * @param int $dataCount Count of datasets in chart
     * @param int $symbol Symbol to draw for line
     * @param float $axisPosition Position of axis for drawing filled lines
     * @return void
     */
    public function drawHorizontalBar(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $position,
        $stepSize,
        $dataNumber = 1,
        $dataCount = 1,
        $symbol = ezcGraph::NO_SYMBOL,
        $axisPosition = 0. )
    {
        // Apply margin
        $margin = $stepSize * $this->options->barMargin;
        $padding = $stepSize * $this->options->barPadding;
        $barHeight = ( $stepSize - $margin ) / $dataCount - $padding;
        $offset = - $stepSize / 2 + $margin / 2 + ( $dataCount - $dataNumber - 1 ) * ( $padding + $barHeight ) + $padding / 2;

        $barPointArray = array(
            new ezcGraphCoordinate(
                $boundings->x0 + ( $boundings->width ) * $axisPosition,
                $boundings->y0 + ( $boundings->height ) * $position->y + $offset
            ),
            new ezcGraphCoordinate(
                $boundings->x0 + ( $boundings->width ) * $position->x,
                $boundings->y0 + ( $boundings->height ) * $position->y + $offset
            ),
            new ezcGraphCoordinate(
                $boundings->x0 + ( $boundings->width ) * $position->x,
                $boundings->y0 + ( $boundings->height ) * $position->y + $offset + $barHeight
            ),
            new ezcGraphCoordinate(
                $boundings->x0 + ( $boundings->width ) * $axisPosition,
                $boundings->y0 + ( $boundings->height ) * $position->y + $offset + $barHeight
            ),
        );

        $this->addElementReference(
            $context,
            $this->driver->drawPolygon(
                $barPointArray,
                $color,
                true
            )
        );

        if ( $this->options->dataBorder > 0 )
        {
            $darkened = $color->darken( $this->options->dataBorder );
            $this->driver->drawPolygon(
                $barPointArray,
                $darkened,
                false,
                1
            );
        }
    }
    
    /**
     * Draw bar
     *
     * Draws a bar as a data element in a line chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $position Position of data point
     * @param float $stepSize Space which can be used for bars
     * @param int $dataNumber Number of dataset
     * @param int $dataCount Count of datasets in chart
     * @param int $symbol Symbol to draw for line
     * @param float $axisPosition Position of axis for drawing filled lines
     * @return void
     */
    public function drawBar(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $position,
        $stepSize,
        $dataNumber = 1,
        $dataCount = 1,
        $symbol = ezcGraph::NO_SYMBOL,
        $axisPosition = 0. )
    {
        throw new ezcBaseFunctionalityNotSupportedException(
            "A normal bar chart",
            "Only horizontal bar charts can be renderered with the ezcGraphHorizontalRenderer"
        );
    }
    
    /**
     * Draw data line
     *
     * Draws a line as a data element in a line chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $start Starting point
     * @param ezcGraphCoordinate $end Ending point
     * @param int $dataNumber Number of dataset
     * @param int $dataCount Count of datasets in chart
     * @param int $symbol Symbol to draw for line
     * @param ezcGraphColor $symbolColor Color of the symbol, defaults to linecolor
     * @param ezcGraphColor $fillColor Color to fill line with
     * @param float $axisPosition Position of axis for drawing filled lines
     * @param float $thickness Line thickness
     * @return void
     */
    public function drawDataLine(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        $dataNumber = 1,
        $dataCount = 1,
        $symbol = ezcGraph::NO_SYMBOL,
        ezcGraphColor $symbolColor = null,
        ezcGraphColor $fillColor = null,
        $axisPosition = 0.,
        $thickness = 1. )
    {
        throw new ezcBaseFunctionalityNotSupportedException(
            "A normal line chart",
            "Only horizontal bar charts can be renderered with the ezcGraphHorizontalRenderer"
        );
    }
}

?>
