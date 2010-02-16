<?php
/**
 * File containing the three dimensional renderer
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class to transform chart primitives into image primitives. This renderer
 * renders the charts in a isometric three dimensional view.
 *
 * The class options are defined in the class {@link ezcGraphRenderer3dOptions}
 * extending the basic renderer options in {@link ezcGraphRendererOptions}.
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
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphRenderer3d 
    extends 
        ezcGraphRenderer
    implements
        ezcGraphStackedBarsRenderer
{

    /**
     * Pie segment labels divided into two array, containing the labels on the
     * left and right side of the pie chart center.
     * 
     * @var array
     */
    protected $pieSegmentLabels = array(
        0 => array(),
        1 => array(),
    );

    /**
     * Contains the boundings used for pie segments
     * 
     * @var ezcGraphBoundings
     */
    protected $pieSegmentBoundings = false;

    /**
     * Array with symbols for post processing, which ensures, that the symbols
     * are rendered topmost.
     * 
     * @var array
     */
    protected $linePostSymbols = array();

    /**
     * Array containing lines from the axis and grid which should be redrawn on
     * top of the data.
     * 
     * @var array
     */
    protected $frontLines = array();

    /**
     * Collects circle sectors to draw shadow in background of all circle 
     * sectors.
     * 
     * @var array
     */
    protected $circleSectors = array();

    /**
     * Collects bar sides to draw them in a post processing step to simulate
     * a simple z buffer.
     *  array(
     *      array(
     *          'index' => (int) // used for sorting
     *          'context' => ezcGraphContext // context of call
     *          'method' => (string) // method of driver to call
     *          'parameters' => array // parameters for method call
     *      ), ...
     *  )
     *
     * @var array
     */
    protected $barPostProcessing = array();

    /**
     * Options
     * 
     * @var ezcGraphRenderer3dOptions
     */
    protected $options;

    /**
     * Depth of displayed pseudo three dimensional line chart elements.
     * 
     * @var float
     */
    protected $depth = false;

    /**
     * Factor to reduce the width according to depth 
     * 
     * @var float
     */
    protected $xDepthFactor = false;

    /**
     * Factor to reduce the height according to depth 
     * 
     * @var float
     */
    protected $yDepthFactor = false;

    /**
     * Boundings for the chart data
     * 
     * @var ezcGraphBoundings
     */
    protected $dataBoundings = false;

    /**
     * Collect axis labels, so that the axis are drawn, when all axis spaces 
     * are known.
     * 
     * @var array
     */
    protected $axisLabels = array();
    
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->options = new ezcGraphRenderer3dOptions( $options );
    }

    /**
     * __get 
     * 
     * @param mixed $propertyName 
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return mixed
     * @ignore
     */
    public function __get( $propertyName )
    {
        switch ( $propertyName )
        {
            case 'options':
                return $this->options;
            default:
                return parent::__get( $propertyName );
        }
    }

    /**
     * Calculate the display coordinate from a coordinate
     *
     * Calculates the display coordinate of a coordinate depending on the 
     * depth setting and the distance of the coordinate to the front of the 
     * chart.
     * 
     * @param ezcGraphCoordinate $c Coordinate
     * @param float $front Distance to front (0 - 1)
     * @return ezcGraphCoordinate Resulting coordinate
     */
    protected function get3dCoordinate( ezcGraphCoordinate $c, $front = 1. )
    {
        return new ezcGraphCoordinate(
            ( $c->x - $this->dataBoundings->x0 ) * $this->xDepthFactor + $this->dataBoundings->x0 + $this->depth * $front,
            ( $c->y - $this->dataBoundings->y0 ) * $this->yDepthFactor + $this->dataBoundings->y0 + $this->depth * ( 1 - $front )
        );
    }

    /**
     * Draw pie segment
     *
     * Draws a single pie segment
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of pie segment
     * @param float $startAngle Start angle
     * @param float $endAngle End angle
     * @param mixed $label Label of pie segment
     * @param bool $moveOut Move out from middle for hilighting
     * @return void
     */
    public function drawPieSegment(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        $startAngle = .0,
        $endAngle = 360.,
        $label = false,
        $moveOut = false )
    {
        // Apply offset
        $startAngle += $this->options->pieChartOffset;
        $endAngle += $this->options->pieChartOffset;

        // Calculate position and size of pie
        $center = new ezcGraphCoordinate(
            $boundings->x0 + ( $boundings->x1 - $boundings->x0 ) / 2,
            $boundings->y0 + ( $boundings->y1 - $boundings->y0 ) / 2 
                - $this->options->pieChartHeight / 2 
        );

        // Limit radius to fourth of width and half of height at maximum
        $radius = min(
            ( $boundings->x1 - $boundings->x0 ) * $this->options->pieHorizontalSize,
            ( $boundings->y1 - $boundings->y0 ) * $this->options->pieVerticalSize
        );

        // Move pie segment out of the center
        if ( $moveOut )
        {
            $direction = ( $endAngle + $startAngle ) / 2;

            $center = new ezcGraphCoordinate(
                $center->x + $this->options->moveOut * $radius * cos( deg2rad( $direction ) ),
                $center->y + $this->options->moveOut * $radius * sin( deg2rad( $direction ) ) * $this->options->pieChartRotation
            );
        }

        // Add circle sector to queue
        $this->circleSectors[] = array(
            'center' =>     $center,
            'context' =>    $context,
            'width' =>      $radius * 2 * ( 1 - $this->options->moveOut ),
            'height' =>     $radius * 2 * ( 1 - $this->options->moveOut ) * $this->options->pieChartRotation - $this->options->pieChartHeight,
            'start' =>      $startAngle,
            'end' =>        $endAngle,
            'color' =>      $color,
        );

        if ( $label )
        {
            // Determine position of label
            $direction = ( $endAngle + $startAngle ) / 2;
            $pieSegmentCenter = new ezcGraphCoordinate(
                $center->x + cos( deg2rad( $direction ) ) * $radius,
                $center->y + sin( deg2rad( $direction ) ) * $radius * $this->options->pieChartRotation
            );

            // Split labels up into left a right site and index them on their
            // y position
            $this->pieSegmentLabels[(int) ($pieSegmentCenter->x > $center->x)][(int) ( $pieSegmentCenter->y * 100 )] = array(
                new ezcGraphCoordinate(
                    $center->x + cos( deg2rad( $direction ) ) * $radius * 2 / 3 * ( 1 - $this->options->moveOut ),
                    $center->y + sin( deg2rad( $direction ) ) * ( $radius - $this->options->pieChartHeight ) * 2 / 3 * ( 1 - $this->options->moveOut ) * $this->options->pieChartRotation
                ),
                $label,
                $context,
            );
        }

        if ( !$this->pieSegmentBoundings )
        {
            $this->pieSegmentBoundings = $boundings;
        }
    }

    /**
     * Draws the collected pie segment labels
     *
     * All labels are collected and drawn later to be able to partition the 
     * available space for the labels woth knowledge of the overall label 
     * count and their required size and optimal position.
     * 
     * @return void
     */
    protected function finishPieSegmentLabels()
    {
        if ( $this->pieSegmentBoundings === false )
        {
            return true;
        }

        $boundings = $this->pieSegmentBoundings;

        // Calculate position and size of pie
        $center = new ezcGraphCoordinate(
            $boundings->x0 + ( $boundings->x1 - $boundings->x0 ) / 2,
            $boundings->y0 + ( $boundings->y1 - $boundings->y0 ) / 2
        );

        // Limit radius to fourth of width and half of height at maximum
        $radius = min(
            ( $boundings->width ) * $this->options->pieHorizontalSize,
            ( $boundings->height ) * $this->options->pieVerticalSize
        );

        $pieChartHeight = min(
            $radius * 2 + $radius / max( 1, count ( $this->pieSegmentLabels[0] ), count( $this->pieSegmentLabels[1] ) ) * 4,
            $boundings->height
        );
        $pieChartYPosition = $boundings->y0 + ( ( $boundings->height ) - $pieChartHeight ) / 2;

        // Calculate maximum height of labels
        $labelHeight = min(
            ( count( $this->pieSegmentLabels[0] )
                ? $pieChartHeight / count( $this->pieSegmentLabels[0] )
                : $pieChartHeight
            ),
            ( count( $this->pieSegmentLabels[1] )
                ? $pieChartHeight / count( $this->pieSegmentLabels[1] )
                : $pieChartHeight
            ),
            ( $pieChartHeight ) * $this->options->maxLabelHeight
        );

        $symbolSize = $this->options->symbolSize;

        foreach ( $this->pieSegmentLabels as $side => $labelPart )
        {
            $minHeight = $pieChartYPosition;
            $toShare = $pieChartHeight - count( $labelPart ) * $labelHeight;

            // Sort to draw topmost label first
            ksort( $labelPart );
            $sign = ( $side ? -1 : 1 );

            foreach ( $labelPart as $height => $label )
            {
                $height = (int) ( $height / 100 );

                if ( ( $height - $labelHeight / 2 ) > $minHeight )
                {
                    $share = min( $toShare, ( $height - $labelHeight / 2) - $minHeight );
                    $minHeight += $share;
                    $toShare -= $share;
                }

                // Determine position of label
                $minHeight += max( 0, $height - $minHeight - $labelHeight ) / $pieChartHeight * $toShare;
                $verticalDistance = ( $center->y - $minHeight - $labelHeight / 2 ) / $radius;

                $labelPosition = new ezcGraphCoordinate(
                    $center->x - 
                    $sign * (
                        abs( $verticalDistance ) > 1
                        // If vertical distance to center is greater then the
                        // radius, use the centerline for the horizontal 
                        // position
                        ? max (
                            5,
                            abs( $label[0]->x - $center->x )
                        )
                        // Else place the label outside of the pie chart
                        : ( cos ( asin ( $verticalDistance ) ) * $radius + 
                            $symbolSize * (int) $this->options->showSymbol 
                        )
                    ),
                    $minHeight + $labelHeight / 2
                );

                if ( $this->options->showSymbol )
                {
                    // Draw label
                    $this->driver->drawLine(
                        $label[0],
                        $labelPosition,
                        $this->options->pieChartSymbolColor,
                        1
                    );

                    $this->driver->drawCircle(
                        $label[0],
                        $symbolSize,
                        $symbolSize,
                        $this->options->pieChartSymbolColor,
                        true
                    );
                    $this->driver->drawCircle(
                        $labelPosition,
                        $symbolSize,
                        $symbolSize,
                        $this->options->pieChartSymbolColor,
                        true
                    );
                }

                $this->addElementReference( $label[2], 
                    $this->driver->drawTextBox(
                        $label[1],
                        new ezcGraphCoordinate(
                            ( !$side ? $boundings->x0 : $labelPosition->x + $symbolSize ),
                            $minHeight
                        ),
                        ( !$side ? $labelPosition->x - $boundings->x0 - $symbolSize : $boundings->x1 - $labelPosition->x - $symbolSize ),
                        $labelHeight,
                        ( !$side ? ezcGraph::RIGHT : ezcGraph::LEFT ) | ezcGraph::MIDDLE
                    )
                );

                // Add used space to minHeight
                $minHeight += $labelHeight;
            }
        }
    }

    /**
     * Draws the collected circle sectors
     *
     * All circle sectors are collected and drawn later to be able to render 
     * the shadows of the pie segments in the back of all pie segments, and 
     * ensure the correct drawing order for all pie segment elements.
     * 
     * @return void
     */
    protected function finishCirleSectors()
    {
        $zBuffer = array();

        $shadows = array();
        $shadowCenter = false;
        $shadowEndAngle = false;

        // Add circle sector sides to simple z buffer prioriry list
        foreach ( $this->circleSectors as $circleSector )
        {
            // Draw shadow if wanted
            if ( $this->options->pieChartShadowSize > 0 )
            {
                if ( $shadowEndAngle === false )
                {
                    $shadowStartAngle = $circleSector['start'];
                    $shadowEndAngle = $circleSector['end'];
                    $shadowCenter = $circleSector['center'];
                }
                elseif ( $circleSector['center'] == $shadowCenter )
                {
                    $shadowEndAngle = $circleSector['end'];
                }
                else
                {
                    $shadows[] = array( 
                        'center' => $shadowCenter,
                        'start' => $shadowStartAngle, 
                        'end' => $shadowEndAngle,
                        'width' => $circleSector['width'],
                        'height' => $circleSector['height'],
                    );

                    $shadowCenter = $circleSector['center'];
                    $shadowStartAngle = $circleSector['start'];
                    $shadowEndAngle = $circleSector['end'];
                }
            }

            $darkenedColor = $circleSector['color']->darken( $this->options->dataBorder );

            $center = (int) ( $circleSector['center']->y + sin( deg2rad( $circleSector['start'] + ( $circleSector['end'] - $circleSector['start'] ) / 2 ) ) * $circleSector['height'] / 2 + $this->options->pieChartHeight / 2 + 1 );

            $zBuffer[$center][] = array(
                'method' => 'drawCircularArc',
                'paramenters' => array(
                    $circleSector['center'],
                    $circleSector['width'],
                    $circleSector['height'],
                    $this->options->pieChartHeight,
                    $circleSector['start'],
                    $circleSector['end'],
                    $circleSector['color']
                )
            );

            // Left side
            $polygonPoints = array(
                $circleSector['center'],
                new ezcGraphCoordinate(
                    $circleSector['center']->x,
                    $circleSector['center']->y + $this->options->pieChartHeight
                ),
                new ezcGraphCoordinate(
                    $circleSector['center']->x + cos( deg2rad( $circleSector['start'] ) ) * $circleSector['width'] / 2,
                    $circleSector['center']->y + sin( deg2rad( $circleSector['start'] ) ) * $circleSector['height'] / 2 + $this->options->pieChartHeight
                ),
                new ezcGraphCoordinate(
                    $circleSector['center']->x + cos( deg2rad( $circleSector['start'] ) ) * $circleSector['width'] / 2,
                    $circleSector['center']->y + sin( deg2rad( $circleSector['start'] ) ) * $circleSector['height'] / 2
                ),
            );

            // Get average y coordinate for polygon to use for zBuffer
            $center = 0;
            foreach ( $polygonPoints as $point )
            {
                $center += $point->y;
            }
            $center = (int) ( $center / count( $polygonPoints ) );

            $zBuffer[$center][] = array(
                'method' => 'drawPolygon',
                'paramenters' => array(
                    $polygonPoints,
                    $circleSector['color'],
                    true
                ),
            );

            $zBuffer[$center][] = array(
                'method' => 'drawPolygon',
                'paramenters' => array(
                    $polygonPoints,
                    $darkenedColor,
                    false
                ),
            );

            // Right side
            $polygonPoints = array(
                $circleSector['center'],
                new ezcGraphCoordinate(
                    $circleSector['center']->x,
                    $circleSector['center']->y + $this->options->pieChartHeight
                ),
                new ezcGraphCoordinate(
                    $circleSector['center']->x + cos( deg2rad( $circleSector['end'] ) ) * $circleSector['width'] / 2,
                    $circleSector['center']->y + sin( deg2rad( $circleSector['end'] ) ) * $circleSector['height'] / 2 + $this->options->pieChartHeight
                ),
                new ezcGraphCoordinate(
                    $circleSector['center']->x + cos( deg2rad( $circleSector['end'] ) ) * $circleSector['width'] / 2,
                    $circleSector['center']->y + sin( deg2rad( $circleSector['end'] ) ) * $circleSector['height'] / 2
                ),
            );

            // Get average y coordinate for polygon to use for zBuffer
            $center = 0;
            foreach ( $polygonPoints as $point )
            {
                $center += $point->y;
            }
            $center = (int) ( $center / count( $polygonPoints ) );

            $zBuffer[$center][] = array(
                'method' => 'drawPolygon',
                'paramenters' => array(
                    $polygonPoints,
                    $circleSector['color'],
                    true
                ),
            );

            $zBuffer[$center][] = array(
                'method' => 'drawPolygon',
                'paramenters' => array(
                    $polygonPoints,
                    $darkenedColor,
                    false
                ),
            );
        }

        if ( $this->options->pieChartShadowSize > 0 )
        {
            $shadows[] = array( 
                'center' => $shadowCenter,
                'start' => $shadowStartAngle, 
                'end' => $shadowEndAngle,
                'width' => $circleSector['width'],
                'height' => $circleSector['height'],
            );
        }

        // Draw collected shadows
        foreach ( $shadows as $circleSector )
        {
            for ( $i = $this->options->pieChartShadowSize; $i > 0; --$i )
            {
                $startAngle = $circleSector['start'];
                $endAngle = $circleSector['end'];

                $startAngle = $circleSector['start'] - ( $this->options->pieChartShadowSize - $i );
                $endAngle = $circleSector['end'] + ( $this->options->pieChartShadowSize - $i );

                if ( ( $endAngle - $startAngle ) >= 360 )
                {
                    $this->driver->drawCircle(
                        new ezcGraphCoordinate(
                            $circleSector['center']->x,
                            $circleSector['center']->y + $this->options->pieChartHeight
                        ),
                        $circleSector['width'] + $i * 2,
                        $circleSector['height'] + $i * 2,
                        $this->options->pieChartShadowColor->transparent( 1 - ( $this->options->pieChartShadowTransparency / $this->options->pieChartShadowSize ) ),
                        true
                    );
                }
                else
                {
                    $this->driver->drawCircleSector(
                        new ezcGraphCoordinate(
                            $circleSector['center']->x,
                            $circleSector['center']->y + $this->options->pieChartHeight
                        ),
                        $circleSector['width'] + $i * 2,
                        $circleSector['height'] + $i * 2,
                        $startAngle,
                        $endAngle,
                        $this->options->pieChartShadowColor->transparent( 1 - ( $this->options->pieChartShadowTransparency / $this->options->pieChartShadowSize ) ),
                        true
                    );
                }
            }
        }

        ksort( $zBuffer );
        foreach ( $zBuffer as $sides )
        {
            foreach ( $sides as $side )
            {
                call_user_func_array( array( $this->driver, $side['method'] ), $side['paramenters'] );
            }
        }

        // Draw circle sector for front
        foreach ( $this->circleSectors as $circleSector )
        {
            $this->addElementReference( $circleSector['context'],
                $this->driver->drawCircleSector(
                    $circleSector['center'],
                    $circleSector['width'],
                    $circleSector['height'],
                    $circleSector['start'],
                    $circleSector['end'],
                    $circleSector['color'],
                    true
                )
            );

            if ( $this->options->pieChartGleam !== false )
            {
                $gradient = new ezcGraphLinearGradient(
                    $circleSector['center'],
                    new ezcGraphCoordinate(
                        $circleSector['center']->x - $circleSector['width'] / 2,
                        $circleSector['center']->y - $circleSector['height'] / 2
                    ),
                    $this->options->pieChartGleamColor->transparent( 1 ),
                    $this->options->pieChartGleamColor->transparent( $this->options->pieChartGleam )
                );

                $this->addElementReference( $circleSector['context'],
                    $this->driver->drawCircleSector(
                        $circleSector['center'],
                        $circleSector['width'] - $this->options->pieChartGleamBorder * 2,
                        $circleSector['height'] - $this->options->pieChartGleamBorder * 2 * $this->options->pieChartRotation,
                        $circleSector['start'],
                        $circleSector['end'],
                        $gradient,
                        true
                    )
                );
            }

            $darkenedColor = $circleSector['color']->darken( $this->options->dataBorder );
            $this->driver->drawCircleSector(
                $circleSector['center'],
                $circleSector['width'],
                $circleSector['height'],
                $circleSector['start'],
                $circleSector['end'],
                $darkenedColor,
                false
            );

            if ( $this->options->pieChartGleam !== false )
            {
                $radialGradient = new ezcGraphRadialGradient(
                    new ezcGraphCoordinate(
                        $circleSector['center']->x + $circleSector['width'] / 2 * cos( deg2rad( 135 ) ),
                        $circleSector['center']->y + $circleSector['height'] / 2 * sin( deg2rad( 135 ) )
                    ),
                    $circleSector['width'],
                    $circleSector['height'],
                    $this->options->pieChartGleamColor->transparent( $this->options->pieChartGleam ),
                    $this->options->pieChartGleamColor->transparent( .8 )
                );

                $this->driver->drawCircularArc(
                    $circleSector['center'],
                    $circleSector['width'],
                    $circleSector['height'],
                    0,
                    $circleSector['start'],
                    $circleSector['end'],
                    $radialGradient,
                    false
                );
            }
        }
    }

    /**
     * Draw collected front lines
     *
     * Draw all grid and axis lines, which should be redrawn in front of the 
     * data.
     * 
     * @return void
     */
    protected function finishFrontLines()
    {
        foreach ( $this->frontLines as $line )
        {
            $this->driver->drawLine(
                $line[0],
                $line[1],
                $line[2],
                $line[3]
            );
        }
    }

    /**
     * Draw the collected line symbols
     *
     * Symbols for the data lines are collected and delayed to ensure that 
     * they are not covered and hidden by other data lines.
     * 
     * @return void
     */
    protected function finishLineSymbols()
    {
        foreach ( $this->linePostSymbols as $symbol )
        {
            $this->addElementReference( $symbol['context'],
                $this->drawSymbol(
                    $symbol['boundings'],
                    $symbol['color'],
                    $symbol['symbol']
                )
            );
        }
    }
    
    /**
     * Draws a bar with a rectangular ground shape.
     * 
     * @param ezcGraphContext $context
     * @param ezcGraphColor $color
     * @param ezcGraphCoordinate $position
     * @param float $barWidth
     * @param float $offset
     * @param float $axisPosition
     * @param float $startDepth
     * @param float $midDepth
     * @param float $endDepth
     * @return void
     */
    protected function drawRectangularBar(
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $position,
        $barWidth,
        $offset,
        $axisPosition,
        $startDepth,
        $midDepth,
        $endDepth )
    {
        $barPolygonArray = array(
            new ezcGraphCoordinate( 
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset,
                $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
            ),
            new ezcGraphCoordinate( 
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset,
                $this->dataBoundings->y0 + $this->yAxisSpace + $position->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
            ),
            new ezcGraphCoordinate( 
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth,
                $this->dataBoundings->y0 + $this->yAxisSpace + $position->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
            ),
            new ezcGraphCoordinate( 
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth,
                $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
            ),
        );

        // Draw right bar side
        $this->barPostProcessing[] = array(
            'index' => $barPolygonArray[2]->x + ( 1 - $position->y ),
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                array(
                    $this->get3dCoordinate( $barPolygonArray[2], $startDepth ),
                    $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                    $this->get3dCoordinate( $barPolygonArray[3], $endDepth ),
                    $this->get3dCoordinate( $barPolygonArray[2], $endDepth ),
                ),
                $color->darken( $this->options->barDarkenSide ),
                true
            ),
        );

        // Draw top side
        $this->barPostProcessing[] = array(
            'index' => $barPolygonArray[1]->x + ( 1 - $position->y ),
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                ( $barPolygonArray[1]->y < $barPolygonArray[3]->y
                ?   array(
                        $this->get3dCoordinate( $barPolygonArray[1], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[2], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[2], $endDepth ),
                        $this->get3dCoordinate( $barPolygonArray[1], $endDepth ),
                    )
                :   array(
                        $this->get3dCoordinate( $barPolygonArray[0], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[3], $endDepth ),
                        $this->get3dCoordinate( $barPolygonArray[0], $endDepth ),
                    )
                ),
                $color->darken( $this->options->barDarkenTop ),
                true
            ),
        );

        // Draw top side gleam
        if ( $this->options->barChartGleam !== false )
        {
            $this->barPostProcessing[] = array(
                'index' => $barPolygonArray[1]->x + 1 + ( 1 - $position->y ),
                'method' => 'drawPolygon',
                'context' => $context,
                'parameters' => array(
                    ( $barPolygonArray[1]->y < $barPolygonArray[3]->y
                    ?   array(
                            $this->get3dCoordinate( $barPolygonArray[1], $startDepth ),
                            $this->get3dCoordinate( $barPolygonArray[2], $startDepth ),
                            $this->get3dCoordinate( $barPolygonArray[2], $endDepth ),
                            $this->get3dCoordinate( $barPolygonArray[1], $endDepth ),
                        )
                    :   array(
                            $this->get3dCoordinate( $barPolygonArray[0], $startDepth ),
                            $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                            $this->get3dCoordinate( $barPolygonArray[3], $endDepth ),
                            $this->get3dCoordinate( $barPolygonArray[0], $endDepth ),
                        )
                    ),
                    new ezcGraphLinearGradient(
                        ( $barPolygonArray[1]->y < $barPolygonArray[3]->y
                        ? $this->get3dCoordinate( $barPolygonArray[2], $endDepth )
                        : $this->get3dCoordinate( $barPolygonArray[3], $endDepth )
                        ),
                        ( $barPolygonArray[1]->y < $barPolygonArray[3]->y
                        ? $this->get3dCoordinate( $barPolygonArray[1], $startDepth )
                        : $this->get3dCoordinate( $barPolygonArray[0], $startDepth )
                        ),
                        ezcGraphColor::fromHex( '#FFFFFFFF' ),
                        ezcGraphColor::fromHex( '#FFFFFF' )->transparent( 1 - $this->options->barChartGleam )
                    ),
                    true
                ),
            );
        }

        // Draw front side
        $this->barPostProcessing[] = array(
            'index' => $barPolygonArray[1]->x + ( 1 - $position->y ),
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                array(
                    $this->get3dCoordinate( $barPolygonArray[0], $startDepth ),
                    $this->get3dCoordinate( $barPolygonArray[1], $startDepth ),
                    $this->get3dCoordinate( $barPolygonArray[2], $startDepth ),
                    $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                ),
                $color,
                true
            ),
        );

        // Draw front side gleam
        if ( $this->options->barChartGleam !== false )
        {
            $this->barPostProcessing[] = array(
                'index' => $barPolygonArray[1]->x + 1 + ( 1 - $position->y ),
                'method' => 'drawPolygon',
                'context' => $context,
                'parameters' => array(
                    array(
                        $this->get3dCoordinate( $barPolygonArray[0], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[1], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[2], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                    ),
                    new ezcGraphLinearGradient(
                        $this->get3dCoordinate( $barPolygonArray[3], $startDepth ),
                        $this->get3dCoordinate( $barPolygonArray[1], $startDepth ),
                        ezcGraphColor::fromHex( '#FFFFFFFF' ),
                        ezcGraphColor::fromHex( '#FFFFFF' )->transparent( 1 - $this->options->barChartGleam )
                    ),
                    true
                ),
            );
        }
    }

    /**
     * Draws a bar with a diamond ground shape.
     * 
     * @param ezcGraphContext $context
     * @param ezcGraphColor $color
     * @param ezcGraphCoordinate $position
     * @param float $barWidth
     * @param float $offset
     * @param float $axisPosition
     * @param float $startDepth
     * @param float $midDepth
     * @param float $endDepth
     * @return void
     */
    protected function drawDiamondBar(
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $position,
        $barWidth,
        $offset,
        $axisPosition,
        $startDepth,
        $midDepth,
        $endDepth )
    {
        $barCoordinateArray = array(
            // The bottom point of the diamond is moved to .7 instead 
            // of .5 because it looks more correct, even it is wrong...
            'x' => array(
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset,
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth * .7,
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth,
                $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth * .3,
            ),
            'y' => array(
                $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) ),
                $this->dataBoundings->y0 + $this->yAxisSpace + $position->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) ),
            ),
        );

        // Left side
        $this->barPostProcessing[] = array(
            'index' => $barCoordinateArray['x'][0],
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                array(
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][0], $barCoordinateArray['y'][0] ), $midDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][0], $barCoordinateArray['y'][1] ), $midDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $barCoordinateArray['y'][1] ), $startDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $barCoordinateArray['y'][0] ), $startDepth ),
                ),
                $color,
                true
            ),
        );

        // Right side
        $this->barPostProcessing[] = array(
            'index' => $barCoordinateArray['x'][1],
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                array(
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][2], $barCoordinateArray['y'][0] ), $midDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][2], $barCoordinateArray['y'][1] ), $midDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $barCoordinateArray['y'][1] ), $startDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $barCoordinateArray['y'][0] ), $startDepth ),
                ),
                $color->darken( $this->options->barDarkenSide ),
                true
            ),
        );

        $topLocation = min(
            $barCoordinateArray['y'][0],
            $barCoordinateArray['y'][1]
        );

        // Top side
        $this->barPostProcessing[] = array(
            'index' => $barCoordinateArray['x'][0],
            'method' => 'drawPolygon',
            'context' => $context,
            'parameters' => array(
                array(
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $topLocation ), $startDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][2], $topLocation ), $midDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][3], $topLocation ), $endDepth ),
                    $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][0], $topLocation ), $midDepth ),
                ),
                $color->darken( $this->options->barDarkenTop ),
                true
            ),
        );

        // Top side gleam
        if ( $this->options->barChartGleam !== false )
        {
            $this->barPostProcessing[] = array(
                'index' => $barCoordinateArray['x'][0] + 1,
                'method' => 'drawPolygon',
                'context' => $context,
                'parameters' => array(
                    array(
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][1], $topLocation ), $startDepth ),
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][2], $topLocation ), $midDepth ),
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][3], $topLocation ), $endDepth ),
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][0], $topLocation ), $midDepth ),
                    ),
                    new ezcGraphLinearGradient(
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][2], $topLocation ), $midDepth ),
                        $this->get3dCoordinate( new ezcGraphCoordinate( $barCoordinateArray['x'][0], $topLocation ), $midDepth ),
                        ezcGraphColor::fromHex( '#FFFFFFFF' ),
                        ezcGraphColor::fromHex( '#FFFFFF' )->transparent( 1 - $this->options->barChartGleam )
                    ),
                    true
                ),
            );
        }
    }

    /**
     * Draws a bar with a circular ground shape.
     * 
     * @param ezcGraphContext $context
     * @param ezcGraphColor $color
     * @param ezcGraphCoordinate $position
     * @param float $barWidth
     * @param float $offset
     * @param float $axisPosition
     * @param float $startDepth
     * @param float $midDepth
     * @param float $endDepth
     * @param int $symbol
     * @return void
     */
    protected function drawCircularBar(
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $position,
        $barWidth,
        $offset,
        $axisPosition,
        $startDepth,
        $midDepth,
        $endDepth,
        $symbol )
    {
        $barCenterTop = new ezcGraphCoordinate(
            $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth / 2,
            $this->dataBoundings->y0 + $this->yAxisSpace + $position->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
            
        );
        $barCenterBottom = new ezcGraphCoordinate(
            $this->dataBoundings->x0 + $this->xAxisSpace + $position->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ) + $offset + $barWidth / 2,
            $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );

        if ( $barCenterTop->y > $barCenterBottom->y )
        {
            $tmp = $barCenterTop;
            $barCenterTop = $barCenterBottom;
            $barCenterBottom = $tmp;
        }

        $this->barPostProcessing[] = array(
            'index' => $barCenterBottom->x,
            'method' => 'drawCircularArc',
            'context' => $context,
            'parameters' => array(
                $this->get3dCoordinate( $barCenterTop, $midDepth ),
                $barWidth,
                $barWidth / 2,
                ( $barCenterBottom->y - $barCenterTop->y ) * $this->yDepthFactor,
                0,
                180,
                $color
            ),
        );

        $this->barPostProcessing[] = array(
            'index' => $barCenterBottom->x + 1,
            'method' => 'drawCircle',
            'context' => $context,
            'parameters' => array(
                $top = $this->get3dCoordinate( $barCenterTop, $midDepth ),
                $barWidth,
                $barWidth / 2,
                ( $symbol === ezcGraph::CIRCLE
                    ? new ezcGraphLinearGradient(
                        new ezcGraphCoordinate(
                            $top->x - $barWidth / 2,
                            $top->y
                        ),
                        new ezcGraphCoordinate(
                            $top->x + $barWidth / 2,
                            $top->y
                        ),
                        $color->darken( $this->options->barDarkenTop ),
                        $color
                    )    
                    : $color
                )
            ),
        );
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
        // Apply margin
        $margin = $stepSize * $this->options->barMargin;
        $padding = $stepSize * $this->options->barPadding;
        $barWidth = ( $stepSize - $margin ) / $dataCount - $padding;
        $offset = - $stepSize / 2 + $margin / 2 + ( $dataCount - $dataNumber - 1 ) * ( $padding + $barWidth ) + $padding / 2;

        if ( $barWidth < 0 )
        {
            $offset -= $barWidth = abs( $barWidth );
        }

        $startDepth = $this->options->barMargin;
        $midDepth = .5;
        $endDepth = 1 - $this->options->barMargin;

        switch ( $symbol )
        {
            case ezcGraph::NO_SYMBOL:
                $this->drawRectangularBar(
                    $context,
                    $color,
                    $position,
                    $barWidth,
                    $offset,
                    $axisPosition,
                    $startDepth,
                    $midDepth,
                    $endDepth
                );
                break;
            case ezcGraph::DIAMOND:
                $this->drawDiamondBar(
                    $context,
                    $color,
                    $position,
                    $barWidth,
                    $offset,
                    $axisPosition,
                    $startDepth,
                    $midDepth,
                    $endDepth
                );
                break;
            case ezcGraph::BULLET:
            case ezcGraph::CIRCLE:
                $this->drawCircularBar(
                    $context,
                    $color,
                    $position,
                    $barWidth,
                    $offset,
                    $axisPosition,
                    $startDepth,
                    $midDepth,
                    $endDepth,
                    $symbol
                );
                break;
        }
    }
 
    /**
     * Draw stacked bar
     *
     * Draws a stacked bar part as a data element in a line chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $start
     * @param ezcGraphCoordinate $position
     * @param float $stepSize Space which can be used for bars
     * @param int $symbol Symbol to draw for line
     * @param float $axisPosition Position of axis for drawing filled lines
     * @return void
     */
    public function drawStackedBar(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $position,
        $stepSize,
        $symbol = ezcGraph::NO_SYMBOL,
        $axisPosition = 0. )
    {
        // Apply margin
        $margin = $stepSize * $this->options->barMargin;
        $barWidth = $stepSize - $margin;
        $offset = - $stepSize / 2 + $margin / 2;

        if ( $barWidth < 0 )
        {
            $offset -= $barWidth = abs( $barWidth );
        }

        $startDepth = $this->options->barMargin;
        $midDepth = .5;
        $endDepth = 1 - $this->options->barMargin;

        switch ( $symbol )
        {
            case ezcGraph::NO_SYMBOL:
            case ezcGraph::DIAMOND:
            case ezcGraph::BULLET:
            case ezcGraph::CIRCLE:
                $this->drawRectangularBar(
                    $context,
                    $color,
                    $position,
                    $barWidth,
                    $offset,
                    $start->y,
                    $startDepth,
                    $midDepth,
                    $endDepth
                );
                break;
        }
    }

    /**
     * Draw all collected bar elements
     *
     * Draw all collected bar elements after sorting them depending of their
     * position to simulate simple z buffering.
     * 
     * @access protected
     * @return void
     */
    protected function finishBars()
    {
        if ( !count( $this->barPostProcessing ) )
        {
            return true;
        }

        $zIndexArray = array();
        foreach ( $this->barPostProcessing as $key => $barPolygon )
        {
            $zIndexArray[$key] = $barPolygon['index'];
        }

        array_multisort(
            $zIndexArray, SORT_ASC, SORT_NUMERIC,
            $this->barPostProcessing
        );

        foreach ( $this->barPostProcessing as $bar )
        {
            $this->addElementReference( $bar['context'],
                call_user_func_array(
                    array( $this->driver, $bar['method'] ),
                    $bar['parameters']
                )
            );
        }
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
        $dataNumber = 0,
        $dataCount = 1,
        $symbol = ezcGraph::NO_SYMBOL,
        ezcGraphColor $symbolColor = null,
        ezcGraphColor $fillColor = null,
        $axisPosition = 0.,
        $thickness = 1. )
    {
        // Calculate line width based on options
        if ( $this->options->seperateLines )
        {
            $startDepth = ( 1 / $dataCount ) * $dataNumber;
            $endDepth = ( 1 / $dataCount ) * ( $dataNumber + 1 );
        }
        else
        {
            $startDepth = false;
            $endDepth = true;
        }

        // Determine Coordinates depending on boundings and data point position
        $startCoord = new ezcGraphCoordinate( 
            $this->dataBoundings->x0 + $this->xAxisSpace + $start->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ),
            $this->dataBoundings->y0 + $this->yAxisSpace + $start->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );
        $endCoord = new ezcGraphCoordinate( 
            $this->dataBoundings->x0 + $this->xAxisSpace + $end->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ),
            $this->dataBoundings->y0 + $this->yAxisSpace + $end->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );

        // 3D-fy coordinates
        $linePolygonPoints = array(
            $this->get3dCoordinate( $startCoord, $startDepth ),
            $this->get3dCoordinate( $endCoord, $startDepth ),
            $this->get3dCoordinate( $endCoord, $endDepth ),
            $this->get3dCoordinate( $startCoord, $endDepth ),
        );

        $startAxisCoord = new ezcGraphCoordinate( 
            $this->dataBoundings->x0 + $this->xAxisSpace + $start->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ),
            $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );
        $endAxisCoord = new ezcGraphCoordinate( 
            $this->dataBoundings->x0 + $this->xAxisSpace + $end->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ),
            $this->dataBoundings->y0 + $this->yAxisSpace + $axisPosition * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );

        // 3D-fy coordinates
        $axisPolygonPoints = array(
            $this->get3dCoordinate( $startAxisCoord, $startDepth ),
            $this->get3dCoordinate( $endAxisCoord, $startDepth ),
            $this->get3dCoordinate( $endAxisCoord, $endDepth ),
            $this->get3dCoordinate( $startAxisCoord, $endDepth ),
        );

        // Perhaps fill up line
        if ( $fillColor !== null &&
             $start->x != $end->x )
        {
            $startValue = $axisPosition - $start->y;
            $endValue = $axisPosition - $end->y;

            if ( ( $startValue == 0 ) ||
                 ( $endValue == 0 ) ||
                 ( $startValue / abs( $startValue ) == $endValue / abs( $endValue ) ) )
            {
                // Values have the same sign or are on the axis
                $this->driver->drawPolygon(
                    array(
                        $linePolygonPoints[0],
                        $linePolygonPoints[1],
                        $this->get3dCoordinate( $endAxisCoord, $startDepth ),
                        $this->get3dCoordinate( $startAxisCoord, $startDepth ),
                    ),
                    $fillColor,
                    true
                );
            }
            else
            {
                // values are on differente sides of the axis - split the filled polygon
                $startDiff = abs( $axisPosition - $start->y );
                $endDiff = abs( $axisPosition - $end->y );

                $cuttingPosition = $startDiff / ( $endDiff + $startDiff );
                $cuttingPoint = new ezcGraphCoordinate(
                    $startCoord->x + ( $endCoord->x - $startCoord->x ) * $cuttingPosition,
                    $startAxisCoord->y
                );

                $this->driver->drawPolygon(
                    array(
                        $this->get3dCoordinate( $startAxisCoord, $startDepth ),
                        $linePolygonPoints[0],
                        $this->get3dCoordinate( $cuttingPoint, $startDepth ),
                    ),
                    $fillColor,
                    true
                );

                $this->driver->drawPolygon(
                    array(
                        $this->get3dCoordinate( $endAxisCoord, $startDepth ),
                        $linePolygonPoints[1],
                        $this->get3dCoordinate( $cuttingPoint, $startDepth ),
                    ),
                    $fillColor,
                    true
                );
            }

            // Draw closing foo
            $this->driver->drawPolygon(
                array(
                    $linePolygonPoints[2],
                    $linePolygonPoints[1],
                    $this->get3dCoordinate( $endAxisCoord, $startDepth ),
                    $this->get3dCoordinate( $endAxisCoord, $endDepth ),
                ),
                $fillColor,
                true
            );
        }


        // Draw line
        $this->driver->drawPolygon(
            $linePolygonPoints,
            $color,
            true,
            $thickness
        );

        // Draw polygon border
        if ( $this->options->dataBorder > 0 )
        {
            $this->driver->drawPolygon(
                $linePolygonPoints,
                $color->darken( $this->options->dataBorder ),
                false,
                $thickness
            );
        }

        // Draw line symbol
        if ( $this->options->showSymbol && 
             ( $symbol !== ezcGraph::NO_SYMBOL ) )
        {
            if ( $symbolColor === null )
            {
                $symbolColor = $color;
            }

            $this->linePostSymbols[] = array(
                'boundings' => new ezcGraphBoundings(
                    $linePolygonPoints[2]->x - $this->options->symbolSize / 2,
                    $linePolygonPoints[2]->y - $this->options->symbolSize / 2,
                    $linePolygonPoints[2]->x + $this->options->symbolSize / 2,
                    $linePolygonPoints[2]->y + $this->options->symbolSize / 2
                ),
                'color' => $symbolColor,
                'context' => $context,
                'symbol' => $symbol,
            );
        }
    }
    
    /**
     * Draws a highlight textbox for a datapoint.
     *
     * A highlight textbox for line and bar charts means a box with the current 
     * value in the graph.
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphCoordinate $end Ending point
     * @param float $axisPosition Position of axis for drawing filled lines
     * @param int $dataNumber Number of dataset
     * @param int $dataCount Count of datasets in chart
     * @param ezcGraphFontOptions $font Font used for highlight string
     * @param string $text Acutual value
     * @param int $size Size of highlight text
     * @param ezcGraphColor $markLines
     * @param int $xOffset
     * @param int $yOffset
     * @param float $stepSize
     * @param int $type
     * @return void
     */
    public function drawDataHighlightText(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphCoordinate $end,
        $axisPosition = 0.,
        $dataNumber = 1,
        $dataCount = 1,
        ezcGraphFontOptions $font,
        $text,
        $size,
        ezcGraphColor $markLines = null,
        $xOffset = 0,
        $yOffset = 0,
        $stepSize = 0.,
        $type = ezcGraph::LINE )
    {
        $this->driver->options->font = $font;
        $width = $this->dataBoundings->width / $dataCount;

        // Calculate line width based on options
        if ( $this->options->seperateLines )
        {
            $endDepth = ( 1 / $dataCount ) * ( $dataNumber + 1 );
        }
        else
        {
            $endDepth = true;
        }
        
        $dataPoint = new ezcGraphCoordinate( 
            $this->dataBoundings->x0 + $this->xAxisSpace + $end->x * ( $this->dataBoundings->x1 - ( $this->dataBoundings->x0 + 2 * $this->xAxisSpace ) ),
            $this->dataBoundings->y0 + $this->yAxisSpace + $end->y * ( $this->dataBoundings->y1 - ( $this->dataBoundings->y0 + 2 * $this->yAxisSpace ) )
        );

        if ( $end->y < $axisPosition )
        {
            $this->driver->drawTextBox(
                $text,
                $this->get3dCoordinate( new ezcGraphCoordinate(
                    $dataPoint->x - $width / 2,
                    $dataPoint->y - $size - $font->padding - $this->options->symbolSize
                ), $endDepth ),
                $width * $this->xDepthFactor,
                $size,
                ezcGraph::CENTER | ezcGraph::BOTTOM
            );
        }
        else
        {
            $this->driver->drawTextBox(
                $text,
                $this->get3dCoordinate( new ezcGraphCoordinate(
                    $dataPoint->x - $width / 2,
                    $dataPoint->y + $font->padding + $this->options->symbolSize
                ), $endDepth ),
                $width * $this->xDepthFactor,
                $size,
                ezcGraph::CENTER | ezcGraph::TOP
            );
        }
    }
    
    /**
     * Draw legend
     *
     * Will draw a legend in the bounding box
     * 
     * @param ezcGraphBoundings $boundings Bounding of legend
     * @param ezcGraphChartElementLegend $legend Legend to draw;
     * @param int $type Type of legend: Protrait or landscape
     * @return void
     */
    public function drawLegend(
        ezcGraphBoundings $boundings,
        ezcGraphChartElementLegend $legend,
        $type = ezcGraph::VERTICAL )
    {
        $labels = $legend->labels;
        
        // Calculate boundings of each label
        if ( $type & ezcGraph::VERTICAL )
        {
            $labelWidth = $boundings->x1 - $boundings->x0;
            $labelHeight = min( 
                ( $boundings->y1 - $boundings->y0 ) / count( $labels ) - $legend->spacing, 
                $legend->symbolSize + 2 * $legend->padding
            );
        }
        else
        {
            $labelWidth = ( $boundings->x1 - $boundings->x0 ) / count( $labels ) - $legend->spacing;
            $labelHeight = min(
                $boundings->height,
                $legend->symbolSize + 2 * $legend->padding
            );
        }

        $symbolSize = $labelHeight - 2 * $legend->padding;

        // Draw all labels
        $labelPosition = new ezcGraphCoordinate( $boundings->x0, $boundings->y0 );
        foreach ( $labels as $label )
        {
            $this->elements['legend_url'][$label['label']] = $label['url'];

            $this->elements['legend'][$label['label']]['symbol'] = $this->drawSymbol(
                new ezcGraphBoundings(
                    $labelPosition->x + $legend->padding,
                    $labelPosition->y + $legend->padding,
                    $labelPosition->x + $legend->padding + $symbolSize,
                    $labelPosition->y + $legend->padding + $symbolSize
                ),
                $label['color'],
                $label['symbol']
            );

            $this->elements['legend'][$label['label']]['text'] = $this->driver->drawTextBox(
                $label['label'],
                new ezcGraphCoordinate(
                    $labelPosition->x + 2 * $legend->padding + $symbolSize,
                    $labelPosition->y + $legend->padding
                ),
                $labelWidth - $symbolSize - 3 * $legend->padding,
                $labelHeight - 2 * $legend->padding,
                ezcGraph::LEFT | ezcGraph::MIDDLE
            );

            $labelPosition->x += ( $type === ezcGraph::VERTICAL ? 0 : $labelWidth + $legend->spacing );
            $labelPosition->y += ( $type === ezcGraph::VERTICAL ? $labelHeight + $legend->spacing : 0 );
        }
    }
    
    /**
     * Draw box
     *
     * Box are wrapping each major chart element and draw border, background
     * and title to each chart element.
     *
     * Optionally a padding and margin for each box can be defined.
     * 
     * @param ezcGraphBoundings $boundings Boundings of the box
     * @param ezcGraphColor $background Background color
     * @param ezcGraphColor $borderColor Border color
     * @param int $borderWidth Border width
     * @param int $margin Margin
     * @param int $padding Padding
     * @param mixed $title Title of the box
     * @param int $titleSize Size of title in the box
     * @return ezcGraphBoundings Remaining inner boundings
     */
    public function drawBox(
        ezcGraphBoundings $boundings,
        ezcGraphColor $background = null,
        ezcGraphColor $borderColor = null,
        $borderWidth = 0,
        $margin = 0,
        $padding = 0,
        $title = false,
        $titleSize = 16 )
    {
        // Apply margin
        $boundings->x0 += $margin;
        $boundings->y0 += $margin;
        $boundings->x1 -= $margin;
        $boundings->y1 -= $margin;
        
        if ( $background instanceof ezcGraphColor )
        {
            // Draw box background
            $this->driver->drawPolygon(
                array(
                    new ezcGraphCoordinate( $boundings->x0, $boundings->y0 ),
                    new ezcGraphCoordinate( $boundings->x1, $boundings->y0 ),
                    new ezcGraphCoordinate( $boundings->x1, $boundings->y1 ),
                    new ezcGraphCoordinate( $boundings->x0, $boundings->y1 ),
                ),
                $background,
                true
            );
        }

        if ( ( $borderColor instanceof ezcGraphColor ) &&
             ( $borderWidth > 0 ) )
        {
            // Draw border
            $this->driver->drawPolygon(
                array(
                    new ezcGraphCoordinate( $boundings->x0, $boundings->y0 ),
                    new ezcGraphCoordinate( $boundings->x1, $boundings->y0 ),
                    new ezcGraphCoordinate( $boundings->x1, $boundings->y1 ),
                    new ezcGraphCoordinate( $boundings->x0, $boundings->y1 ),
                ),
                $borderColor,
                false,
                $borderWidth
            );

            // Reduce local boundings by borderWidth
            $boundings->x0 += $borderWidth;
            $boundings->y0 += $borderWidth;
            $boundings->x1 -= $borderWidth;
            $boundings->y1 -= $borderWidth;
        }

        // Apply padding
        $boundings->x0 += $padding;
        $boundings->y0 += $padding;
        $boundings->x1 -= $padding;
        $boundings->y1 -= $padding;

        // Add box title
        if ( $title !== false )
        {
            switch ( $this->options->titlePosition )
            {
                case ezcGraph::TOP:
                    $this->driver->drawTextBox(
                        $title,
                        new ezcGraphCoordinate( $boundings->x0, $boundings->y0 ),
                        $boundings->x1 - $boundings->x0,
                        $titleSize,
                        $this->options->titleAlignement
                    );

                    $boundings->y0 += $titleSize + $padding;
                    $boundings->y1 -= $titleSize + $padding;
                    break;
                case ezcGraph::BOTTOM:
                    $this->driver->drawTextBox(
                        $title,
                        new ezcGraphCoordinate( $boundings->x0, $boundings->y1 - $titleSize ),
                        $boundings->x1 - $boundings->x0,
                        $titleSize,
                        $this->options->titleAlignement
                    );

                    $boundings->y1 -= $titleSize + $padding;
                    break;
            }
        }

        return $boundings;
    }
    
    /**
     * Draw text
     *
     * Draws the provided text in the boundings
     * 
     * @param ezcGraphBoundings $boundings Boundings of text
     * @param string $text Text
     * @param int $align Alignement of text
     * @param ezcGraphRotation $rotation
     * @return void
     */
    public function drawText(
        ezcGraphBoundings $boundings,
        $text,
        $align = ezcGraph::LEFT,
        ezcGraphRotation $rotation = null )
    {
        if ( $this->depth === false )
        {
            // We are not 3d for now, wg. rendering normal text boxes like the
            // title
            $topleft = new ezcGraphCoordinate( 
                $boundings->x0, 
                $boundings->y0
            );
            $bottomright = new ezcGraphCoordinate( 
                $boundings->x1, 
                $boundings->y1
            );
        }
        else
        {
            // The 3d part started
            $topleft = $this->get3dCoordinate( 
                new ezcGraphCoordinate( 
                    $boundings->x0, 
                    $boundings->y0
                ), false 
            );
            $bottomright = $this->get3dCoordinate( 
                new ezcGraphCoordinate( 
                    $boundings->x1, 
                    $boundings->y1
                ), false 
            );

            // Also modify rotation accordingly
            if ( $rotation !== null )
            {
                $rotation = new ezcGraphRotation(
                    $rotation->getRotation(),
                    $this->get3dCoordinate( $rotation->getCenter(), false )
                );
            }
        }

        $this->driver->drawTextBox(
            $text,
            $topleft,
            $bottomright->x - $topleft->x,
            $bottomright->y - $topleft->y,
            $align,
            $rotation
        );
    }

    /**
     * Draw grid line
     *
     * Draw line for the grid in the chart background
     * 
     * 
     * @param ezcGraphCoordinate $start Start point
     * @param ezcGraphCoordinate $end End point
     * @param ezcGraphColor $color Color of the grid line
     * @return void
     */
    public function drawGridLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color )
    {
        $gridPolygonCoordinates = array(
            $this->get3dCoordinate( $start, false ),
            $this->get3dCoordinate( $end, false ),
            $this->get3dCoordinate( $end, true ),
            $this->get3dCoordinate( $start, true ),
        );

        // Draw grid polygon
        if ( $this->options->fillGrid === 0 )
        {
            $this->driver->drawLine(
                $gridPolygonCoordinates[2],
                $gridPolygonCoordinates[3],
                $color
            );
        }
        else
        {
            if ( $this->options->fillGrid === 1 )
            {
                $this->driver->drawPolygon(
                    $gridPolygonCoordinates,
                    $color,
                    true
                );
            }
            else
            {
                $this->driver->drawPolygon(
                    $gridPolygonCoordinates,
                    $color->transparent( $this->options->fillGrid ),
                    true
                );
            }
            
            // Draw grid lines - scedule some for later to be drawn in front of 
            // the data
            $this->frontLines[] = array(
                $gridPolygonCoordinates[0],
                $gridPolygonCoordinates[1],
                $color,
                1
            );
        
            $this->frontLines[] = array(
                $gridPolygonCoordinates[1],
                $gridPolygonCoordinates[2],
                $color,
                1
            );

            $this->driver->drawLine(
                $gridPolygonCoordinates[2],
                $gridPolygonCoordinates[3],
                $color,
                1
            );

            $this->frontLines[] = array(
                $gridPolygonCoordinates[3],
                $gridPolygonCoordinates[0],
                $color,
                1
            );
        }
    }

    /**
     * Draw step line
     *
     * Draw a step (marker for label position) on a axis.
     * 
     * @param ezcGraphCoordinate $start Start point
     * @param ezcGraphCoordinate $end End point
     * @param ezcGraphColor $color Color of the grid line
     * @return void
     */
    public function drawStepLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color )
    {
        $stepPolygonCoordinates = array(
            $this->get3dCoordinate( $start, true ),
            $this->get3dCoordinate( $end, true ),
            $this->get3dCoordinate( $end, false ),
            $this->get3dCoordinate( $start, false ),
        );

        // Draw step polygon
        if ( ( $this->options->fillAxis > 0 ) &&
             ( $this->options->fillAxis < 1 ) )
        {
            $this->driver->drawPolygon(
                $stepPolygonCoordinates,
                $color->transparent( $this->options->fillAxis ),
                true
            );

            $this->driver->drawPolygon(
                $stepPolygonCoordinates,
                $color,
                false
            );
        }
        else
        {
            $this->driver->drawPolygon(
                $stepPolygonCoordinates,
                $color,
                ! (bool) $this->options->fillAxis
            );
        }
    }
    
    /**
     * Draw axis
     *
     * Draws an axis form the provided start point to the end point. A specific 
     * angle of the axis is not required.
     *
     * For the labeleing of the axis a sorted array with major steps and an 
     * array with minor steps is expected, which are build like this:
     *  array(
     *      array(
     *          'position' => (float),
     *          'label' => (string),
     *      )
     *  )
     * where the label is optional.
     *
     * The label renderer class defines how the labels are rendered. For more
     * documentation on this topic have a look at the basic label renderer 
     * class.
     *
     * Additionally it can be specified if a major and minor grid are rendered 
     * by defining a color for them. The axis label is used to add a caption 
     * for the axis.
     * 
     * @param ezcGraphBoundings $boundings Boundings of axis
     * @param ezcGraphCoordinate $start Start point of axis
     * @param ezcGraphCoordinate $end Endpoint of axis
     * @param ezcGraphChartElementAxis $axis Axis to render
     * @param ezcGraphAxisLabelRenderer $labelClass Used label renderer
     * @return void
     */
    public function drawAxis(
        ezcGraphBoundings $boundings,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        ezcGraphChartElementAxis $axis,
        ezcGraphAxisLabelRenderer $labelClass = null )
    {
        // Calculate used space for three dimensional effects
        if ( $this->depth === false )
        {
            $this->depth = min(
                ( $boundings->x1 - $boundings->x0 ) * $this->options->depth,
                ( $boundings->y1 - $boundings->y0 ) * $this->options->depth
            );

            $this->xDepthFactor = 1 - $this->depth / ( $boundings->x1 - $boundings->x0 );
            $this->yDepthFactor = 1 - $this->depth / ( $boundings->y1 - $boundings->y0 );

            $this->dataBoundings = clone $boundings;
        }

        // Clone boundings to not be affected by internal mofifications
        $boundings = clone $boundings;

        switch ( $axis->position )
        {
            case ezcGraph::TOP:
            case ezcGraph::BOTTOM:
                $this->xAxisSpace = ( $this->dataBoundings->x1 - $this->dataBoundings->x0 ) * $axis->axisSpace;
                break;
            case ezcGraph::LEFT:
            case ezcGraph::RIGHT:
                $this->yAxisSpace = ( $this->dataBoundings->y1 - $this->dataBoundings->y0 ) * $axis->axisSpace;
                break;
        }

        // Determine normalized direction
        $direction = new ezcGraphVector(
            $start->x - $end->x,
            $start->y - $end->y
        );
        $direction->unify();

        $start->x += $boundings->x0;
        $start->y += $boundings->y0;
        $end->x += $boundings->x0;
        $end->y += $boundings->y0;

        // Shorten drawn axis, if requested.
        if ( ( $this->options->shortAxis === true ) &&
             ( ( $axis->position === ezcGraph::TOP ) ||
               ( $axis->position === ezcGraph::BOTTOM ) ) )
        {
            $axisStart = clone $start;
            $axisEnd   = clone $end;

            $axisStart->y += $boundings->height * $axis->axisSpace *
                ( $axis->position === ezcGraph::TOP ? 1 : -1 );
            $axisEnd->y   -= $boundings->height * $axis->axisSpace *
                ( $axis->position === ezcGraph::TOP ? 1 : -1 );
        }
        elseif ( ( $this->options->shortAxis === true ) &&
             ( ( $axis->position === ezcGraph::LEFT ) ||
               ( $axis->position === ezcGraph::RIGHT ) ) )
        {
            $axisStart = clone $start;
            $axisEnd   = clone $end;

            $axisStart->x += $boundings->width * $axis->axisSpace *
                ( $axis->position === ezcGraph::LEFT ? 1 : -1 );
            $axisEnd->x   -= $boundings->width * $axis->axisSpace *
                ( $axis->position === ezcGraph::LEFT ? 1 : -1 );
        }
        else
        {
            $axisStart = $start;
            $axisEnd   = $end;
        }

        $axisPolygonCoordinates = array(
            $this->get3dCoordinate( $axisStart, true ),
            $this->get3dCoordinate( $axisEnd, true ),
            $this->get3dCoordinate( $axisEnd, false ),
            $this->get3dCoordinate( $axisStart, false ),
        );

        // Draw axis
        if ( ( $this->options->fillAxis > 0 ) &&
             ( $this->options->fillAxis < 1 ) )
        {
            $this->driver->drawPolygon(
                $axisPolygonCoordinates,
                $axis->border->transparent( $this->options->fillAxis ),
                true
            );
        }
        else
        {
            $this->driver->drawPolygon(
                $axisPolygonCoordinates,
                $axis->border,
                ! (bool) $this->options->fillAxis
            );
        }

        // Draw axis lines - scedule some for later to be drawn in front of 
        // the data
        $this->driver->drawLine(
            $axisPolygonCoordinates[0],
            $axisPolygonCoordinates[1],
            $axis->border,
            1
        );
    
        $this->frontLines[] = array(
            $axisPolygonCoordinates[1],
            $axisPolygonCoordinates[2],
            $axis->border,
            1
        );

        $this->frontLines[] = array(
            $axisPolygonCoordinates[2],
            $axisPolygonCoordinates[3],
            $axis->border,
            1
        );

        $this->frontLines[] = array(
            $axisPolygonCoordinates[3],
            $axisPolygonCoordinates[0],
            $axis->border,
            1
        );

        // Draw small arrowhead
        $this->drawAxisArrowHead(
            $axisPolygonCoordinates[1],
            $direction,
            max(
                $axis->minArrowHeadSize,
                min(
                    $axis->maxArrowHeadSize,
                    abs( ceil( ( ( $end->x - $start->x ) + ( $end->y - $start->y ) ) * $axis->axisSpace / 4 ) )
                )
            ),
            $axis->border
        );

        // Draw axis label
        if ( $axis->label !== false )
        {
            $width = $this->dataBoundings->x1 - $this->dataBoundings->x0;
            switch ( $axis->position )
            {
                case ezcGraph::TOP:
                    $this->driver->drawTextBox(
                        $axis->label,
                        new ezcGraphCoordinate(
                            $axisPolygonCoordinates[2]->x + $axis->labelMargin - $width * ( 1 - $axis->axisSpace * 2 ),
                            $axisPolygonCoordinates[2]->y - $axis->labelMargin - $axis->labelSize
                        ),
                        $width * ( 1 - $axis->axisSpace * 2 ) - $axis->labelMargin,
                        $axis->labelSize,
                        ezcGraph::TOP | ezcGraph::RIGHT
                    );
                    break;
                case ezcGraph::BOTTOM:
                    $this->driver->drawTextBox(
                        $axis->label,
                        new ezcGraphCoordinate(
                            $axisPolygonCoordinates[1]->x + $axis->labelMargin,
                            $axisPolygonCoordinates[1]->y + $axis->labelMargin
                        ),
                        $width * ( 1 - $axis->axisSpace * 2 ) - $axis->labelMargin,
                        $axis->labelSize,
                        ezcGraph::TOP | ezcGraph::LEFT
                    );
                    break;
                case ezcGraph::LEFT:
                    $this->driver->drawTextBox(
                        $axis->label,
                        new ezcGraphCoordinate(
                            $axisPolygonCoordinates[1]->x - $width,
                            $axisPolygonCoordinates[1]->y - $axis->labelSize - $axis->labelMargin
                        ),
                        $width - $axis->labelMargin,
                        $axis->labelSize,
                        ezcGraph::BOTTOM | ezcGraph::RIGHT
                    );
                    break;
                case ezcGraph::RIGHT:
                    $this->driver->drawTextBox(
                        $axis->label,
                        new ezcGraphCoordinate(
                            $axisPolygonCoordinates[1]->x,
                            $axisPolygonCoordinates[1]->y - $axis->labelSize - $axis->labelMargin
                        ),
                        $width - $axis->labelMargin,
                        $axis->labelSize,
                        ezcGraph::BOTTOM | ezcGraph::LEFT
                    );
                    break;
            }
        }

        // Collect axis labels and draw, when all axisSpaces are collected
        $this->axisLabels[] = array(
            'object' => $labelClass,
            'boundings' => $boundings,
            'start' => clone $start,
            'end' => clone $end,
            'axis' => $axis,
        );

        if ( $this->xAxisSpace && $this->yAxisSpace )
        {
            foreach ( $this->axisLabels as $axisLabel )
            {
                // If font should not be synchronized, use font configuration from
                // each axis
                if ( $this->options->syncAxisFonts === false )
                {
                    $this->driver->options->font = $axisLabel['axis']->font;
                }

                switch ( $axisLabel['axis']->position )
                {
                    case ezcGraph::RIGHT:
                    case ezcGraph::LEFT:
                        $axisLabel['start']->x += $this->xAxisSpace * ( $axisLabel['start'] > $axisLabel['end'] ? -1 : 1 );
                        $axisLabel['end']->x -= $this->xAxisSpace * ( $axisLabel['start'] > $axisLabel['end'] ? -1 : 1 );
                        break;
                    case ezcGraph::TOP:
                    case ezcGraph::BOTTOM:
                        $axisLabel['start']->y += $this->yAxisSpace * ( $axisLabel['start'] > $axisLabel['end'] ? -1 : 1 );
                        $axisLabel['end']->y -= $this->yAxisSpace * ( $axisLabel['start'] > $axisLabel['end'] ? -1 : 1 );
                        break;
                }

                $axisLabel['object']->renderLabels(
                    $this,
                    $axisLabel['boundings'],
                    $axisLabel['start'],
                    $axisLabel['end'],
                    $axisLabel['axis']
                );
            }
        }
    }

    /**
     * Draw background image
     *
     * Draws a background image at the defined position. If repeat is set the
     * background image will be repeated like any texture.
     * 
     * @param ezcGraphBoundings $boundings Boundings for the background image
     * @param string $file Filename of background image
     * @param int $position Position of background image
     * @param int $repeat Type of repetition
     * @return void
     */
    public function drawBackgroundImage(
        ezcGraphBoundings $boundings,
        $file,
        $position = 48, // ezcGraph::CENTER | ezcGraph::MIDDLE
        $repeat = ezcGraph::NO_REPEAT )
    {
        $imageData = getimagesize( $file );
        $imageWidth = $imageData[0];
        $imageHeight = $imageData[1];

        $imageWidth = min( $imageWidth, $boundings->x1 - $boundings->x0 );
        $imageHeight = min( $imageHeight, $boundings->y1 - $boundings->y0 );

        $imagePosition = new ezcGraphCoordinate( 
            $boundings->x0, 
            $boundings->y0
        );

        // Determine x position
        switch ( true ) {
            case ( $repeat & ezcGraph::HORIZONTAL ):
                // If is repeated on this axis fall back to position zero
            case ( $position & ezcGraph::LEFT ):
                $imagePosition->x = $boundings->x0;
                break;
            case ( $position & ezcGraph::RIGHT ):
                $imagePosition->x = max( 
                    $boundings->x1 - $imageWidth,
                    $boundings->x0
                );
                break;
            default:
                $imagePosition->x = max(
                    $boundings->x0 + ( $boundings->x1 - $boundings->x0 - $imageWidth ) / 2,
                    $boundings->x0
                );
                break;
        }

        // Determine y position
        switch ( true ) {
            case ( $repeat & ezcGraph::VERTICAL ):
                // If is repeated on this axis fall back to position zero
            case ( $position & ezcGraph::TOP ):
                $imagePosition->y = $boundings->y0;
                break;
            case ( $position & ezcGraph::BOTTOM ):
                $imagePosition->y = max( 
                    $boundings->y1 - $imageHeight,
                    $boundings->y0
                );
                break;
            default:
                $imagePosition->y = max(
                    $boundings->y0 + ( $boundings->y1 - $boundings->y0 - $imageHeight ) / 2,
                    $boundings->y0
                );
                break;
        }

        // Texturize backround based on position and repetition
        $position = new ezcGraphCoordinate(
            $imagePosition->x,
            $imagePosition->y
        );
        
        do 
        {
            $position->y = $imagePosition->y;

            do 
            {
                $this->driver->drawImage( 
                    $file, 
                    $position, 
                    $imageWidth, 
                    $imageHeight 
                );

                $position->y += $imageHeight;
            }
            while ( ( $position->y < $boundings->y1 ) &&
                    ( $repeat & ezcGraph::VERTICAL ) );
            
            $position->x += $imageWidth;
        }
        while ( ( $position->x < $boundings->x1 ) &&
                ( $repeat & ezcGraph::HORIZONTAL ) );
    }

    /**
     * Call all postprocessing functions
     * 
     * @return void
     */
    protected function finish()
    {
        $this->finishCirleSectors();
        $this->finishPieSegmentLabels();
        $this->finishBars();
        $this->finishLineSymbols();
        $this->finishFrontLines();

        return true;
    }

    /**
     * Reset renderer properties
     *
     * Reset all renderer properties, which were calculated during the
     * rendering process, to offer a clean environment for rerendering.
     * 
     * @return void
     */
    protected function resetRenderer()
    {
        parent::resetRenderer();

        // Also reset special 3D renderer options
        $this->pieSegmentLabels = array(
            0 => array(),
            1 => array(),
        );
        $this->pieSegmentBoundings = false;
        $this->linePostSymbols     = array();
        $this->frontLines          = array();
        $this->circleSectors       = array();
        $this->barPostProcessing   = array();
        $this->depth               = false;
        $this->xDepthFactor        = false;
        $this->yDepthFactor        = false;
        $this->dataBoundings       = false;
        $this->axisLabels          = array();
    }
}

?>
