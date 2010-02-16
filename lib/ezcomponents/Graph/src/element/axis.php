<?php
/**
 * File containing the abstract ezcGraphChartElementAxis class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class to represent an axis as a chart element
 *
 * Chart elements can be understood as widgets or layout container inside the
 * chart. The actual transformation to images happens inside the renderers.
 * They represent all elements inside the chart and contain mostly general
 * formatting options, while the renderer itself might define additional
 * formatting options for some chart elments. You can find more about the
 * general formatting options for chart elements in the base class
 * ezcGraphChartElement.
 *
 * The axis elements are special elements, as the border and background
 * settings do not apply directly as axis axis are not put inside any boxes.
 * The border value is reused for the color of the axis itself.
 *
 * Generally you should select the axis which matches your data best. By
 * default a labeled x axis and a numeric y axis are used. If you are using
 * date or time values on either axis, you should for example use a
 * ezcGraphChartElementDateAxis. The currently available axis types are:
 *
 * - ezcGraphChartElementDateAxis
 * - ezcGraphChartElementLabeledAxis
 * - ezcGraphChartElementLogarithmicalAxis
 * - ezcGraphChartElementNumericAxis
 *
 * Beside this there are several option to define the general layout of the
 * axis labels. The $formatString option may be used to add additional text to
 * each label on the axis, like a percent sign on the y axis:
 *
 * <code>
 *  $chart->xAxis->formatString = '%s %%';
 * </code>
 *
 * For more complex formatting operations for the label you may assign a custom
 * formatter function to the property $labelCallback.
 *
 * The orientation of labels and their position relatively to the axis ticks is
 * calcualted and rendered by the ezcGraphAxisLabelRenderer classes. You can
 * choose between different axis label renderer, or create you own, and assign
 * an instance of one to the property $axisLabelRenderer. Currently the
 * available axis label renderers are:
 * 
 * - ezcGraphAxisBoxedLabelRenderer
 *
 *   Renders grid and labels like commonly used in bar charts, with the label
 *   between two grid lines.
 *  
 * - ezcGraphAxisCenteredLabelRenderer
 *
 *   Centers the label right next to a tick. Commonly used for labeled axis.
 *
 * - ezcGraphAxisExactLabelRenderer
 *
 *   Put the label next to each tick. Commonly used for numeric axis.
 *
 * - ezcGraphAxisNoLabelRenderer
 *
 *   Renders no labels.
 *
 * - ezcGraphAxisRadarLabelRenderer
 *
 *   Special label renderer for radar charts.
 *
 * - ezcGraphAxisRotatedLabelRenderer
 *
 *   Accepts a rotation angle for the texts put at some axis, which might be
 *   useful for longer textual labels on the axis.
 *
 * The label renderer used by default is different depending on the axis type.
 *
 * @property float $nullPosition
 *           The position of the null value.
 * @property float $axisSpace
 *           Percent of the chart space used to display axis labels and 
 *           arrowheads instead of data values.
 * @property float $outerAxisSpace
 *           Percent of the chart space used to display axis arrow at the outer
 *           side of the axis. If set to null, the axisSpace will be used here.
 * @property ezcGraphColor $majorGrid
 *           Color of major majorGrid.
 * @property ezcGraphColor $minorGrid
 *           Color of minor majorGrid.
 * @property mixed $majorStep
 *           Labeled major steps displayed on the axis. @TODO: Should be moved
 *           to numeric axis.
 * @property mixed $minorStep
 *           Non labeled minor steps on the axis. @TODO: Should be moved to
 *           numeric axis.
 * @property string $formatString
 *           Formatstring to use for labeling of the axis.
 * @property string $label
 *           Axis label
 * @property int $labelSize
 *           Size of axis label
 * @property int $labelMargin
 *           Distance between label an axis
 * @property int $minArrowHeadSize
 *           Minimum Size used to draw arrow heads.
 * @property int $maxArrowHeadSize
 *           Maximum Size used to draw arrow heads.
 * @property ezcGraphAxisLabelRenderer $axisLabelRenderer
 *           AxisLabelRenderer used to render labels and grid on this axis.
 * @property callback $labelCallback
 *           Callback function to format chart labels.
 *           Function will receive two parameters and should return a 
 *           reformatted label.
 *              string function( label, step )
 * @property float $chartPosition
 *           Position of the axis in the chart. Only useful for additional
 *           axis. The basic chart axis will be automatically positioned.
 * @property-read bool $initialized
 *           Property indicating if some values were associated with axis, or a
 *           scaling has been set manually.
 * @property float $labelRotation
 *           Rotation of the axis label in degree
 *
 * @version 1.5
 * @package Graph
 */
abstract class ezcGraphChartElementAxis extends ezcGraphChartElement
{
    /**
     * Axis label renderer class
     * 
     * @var ezcGraphAxisLabelRenderer
     */
    protected $axisLabelRenderer;

    /**
     * Optionally set inner boundings. May be null depending on the used chart
     * implementation.
     * 
     * @var ezcGraphBoundings
     */
    protected $innerBoundings;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['nullPosition'] = false;
        $this->properties['axisSpace'] = .1;
        $this->properties['outerAxisSpace'] = null;
        $this->properties['majorGrid'] = false;
        $this->properties['minorGrid'] = false;
        $this->properties['majorStep'] = null;
        $this->properties['minorStep'] = null;
        $this->properties['formatString'] = '%s';
        $this->properties['label'] = false;
        $this->properties['labelSize'] = 14;
        $this->properties['labelMargin'] = 2;
        $this->properties['minArrowHeadSize'] = 4;
        $this->properties['maxArrowHeadSize'] = 8;
        $this->properties['labelCallback'] = null;
        $this->properties['chartPosition'] = null;
        $this->properties['initialized'] = false;
        $this->properties['labelRotation'] = 0.;

        parent::__construct( $options );

        if ( !isset( $this->axisLabelRenderer ) )
        {
            $this->axisLabelRenderer = new ezcGraphAxisExactLabelRenderer();
        }
    }

    /**
     * Set colors and border fro this element
     * 
     * @param ezcGraphPalette $palette Palette
     * @return void
     */
    public function setFromPalette( ezcGraphPalette $palette )
    {
        $this->border = $palette->axisColor;
        $this->padding = $palette->padding;
        $this->margin = $palette->margin;
        $this->majorGrid = $palette->majorGridColor;
        $this->minorGrid = $palette->minorGridColor;
    }

    /**
     * __set 
     * 
     * @param mixed $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBaseValueException
     *          If a submitted parameter was out of range or type.
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return void
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'nullPosition':
                $this->properties['nullPosition'] = (float) $propertyValue;
                break;
            case 'axisSpace':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue >= 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float < 1' );
                }

                $this->properties['axisSpace'] = (float) $propertyValue;
                break;
            case 'outerAxisSpace':
                if ( !is_null( $propertyValue ) &&
                     ( !is_numeric( $propertyValue ) ||
                       ( $propertyValue < 0 ) ||
                       ( $propertyValue >= 1 ) ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'null, or 0 <= float < 1' );
                }

                $this->properties['outerAxisSpace'] = $propertyValue;
                break;
            case 'majorGrid':
                $this->properties['majorGrid'] = ezcGraphColor::create( $propertyValue );
                break;
            case 'minorGrid':
                $this->properties['minorGrid'] = ezcGraphColor::create( $propertyValue );
                break;
            case 'majorStep':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }
                
                $this->properties['majorStep'] = (float) $propertyValue;
                break;
            case 'minorStep':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }
                
                $this->properties['minorStep'] = (float) $propertyValue;
                break;
            case 'formatString':
                $this->properties['formatString'] = (string) $propertyValue;
                break;
            case 'label':
                $this->properties['label'] = (string) $propertyValue;
                break;
            case 'labelSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 6 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 6' );
                }
                
                $this->properties['labelSize'] = (int) $propertyValue;
                break;
            case 'labelMargin':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }
                
                $this->properties['labelMargin'] = (int) $propertyValue;
                break;
            case 'maxArrowHeadSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }
                
                $this->properties['maxArrowHeadSize'] = (int) $propertyValue;
                break;
            case 'minArrowHeadSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }
                
                $this->properties['minArrowHeadSize'] = (int) $propertyValue;
                break;
            case 'axisLabelRenderer':
                if ( $propertyValue instanceof ezcGraphAxisLabelRenderer )
                {
                    $this->axisLabelRenderer = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphAxisLabelRenderer' );
                }
                break;
            case 'labelCallback':
                if ( is_callable( $propertyValue ) )
                {
                    $this->properties['labelCallback'] = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'callback function' );
                }
                break;
            case 'chartPosition':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties['chartPosition'] = (float) $propertyValue;
                break;
            case 'labelRotation':
                if ( !is_numeric( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float' );
                }
                
                $this->properties['labelRotation'] = fmod( (float) $propertyValue, 360. );
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
                break;
        }
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
            case 'axisLabelRenderer':
                return $this->axisLabelRenderer;
            default:
                return parent::__get( $propertyName );
        }
    }

    /**
     * Get coordinate for a dedicated value on the chart
     * 
     * @param float $value Value to determine position for
     * @return float Position on chart
     */
    abstract public function getCoordinate( $value );

    /**
     * Return count of minor steps
     * 
     * @return integer Count of minor steps
     */
    abstract public function getMinorStepCount();

    /**
     * Return count of major steps
     * 
     * @return integer Count of major steps
     */
    abstract public function getMajorStepCount();

    /**
     * Get label for a dedicated step on the axis
     * 
     * @param integer $step Number of step
     * @return string label
     */
    abstract public function getLabel( $step );

    /**
     * Return array of steps on this axis
     * 
     * @return array( ezcGraphAxisStep )
     */
    public function getSteps()
    {
        $majorSteps = $this->getMajorStepCount();
        $minorStepsPerMajorStepCount = ( $this->getMinorStepCount() / $majorSteps );

        $majorStepSize = 1 / $majorSteps;
        $minorStepSize = ( $minorStepsPerMajorStepCount > 0 ? $majorStepSize / $minorStepsPerMajorStepCount : 0 );

        $steps = array();
        for ( $major = 0; $major <= $majorSteps; ++$major )
        {
            $majorStep = new ezcGraphAxisStep(
                $majorStepSize * $major,
                $majorStepSize,
                $this->getLabel( $major ),
                array(),
                $this->isZeroStep( $major ),
                ( $major === $majorSteps )
            );

            if ( ( $minorStepsPerMajorStepCount > 0 ) &&
                 ( $major < $majorSteps ) )
            {
                // Do not add minor steps at major steps positions
                for( $minor = 1; $minor < $minorStepsPerMajorStepCount; ++$minor )
                {
                    $majorStep->childs[] = new ezcGraphAxisStep(
                        ( $majorStepSize * $major ) + ( $minorStepSize * $minor ),
                        $minorStepSize
                    );
                }
            }

            $steps[] = $majorStep;
        }

        return $steps;
    }

    /**
     * Is zero step
     *
     * Returns true if the given step is the one on the initial axis position
     * 
     * @param int $step Number of step
     * @return bool Status If given step is initial axis position
     */
    abstract public function isZeroStep( $step );

    /**
     * Add data for this axis
     * 
     * @param array $values
     * @return void
     */
    abstract public function addData( array $values );

    /**
     * Calculate axis bounding values on base of the assigned values 
     * 
     * @abstract
     * @access public
     * @return void
     */
    abstract public function calculateAxisBoundings();

    /**
     * Render the axis 
     * 
     * @param ezcGraphRenderer $renderer Renderer
     * @param ezcGraphBoundings $boundings Boundings for the axis
     * @return ezcGraphBoundings Remaining boundings
     */
    public function render( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphBoundings $innerBoundings = null )
    {
        $this->innerBoundings = $innerBoundings;
        $startSpace = $this->axisSpace;
        $endSpace   = $this->outerAxisSpace === null ? $this->axisSpace : $this->outerAxisSpace;

        switch ( $this->position )
        {
            case ezcGraph::TOP:
                $start = new ezcGraphCoordinate(
                    $boundings->width * $startSpace +
                        $this->nullPosition * $boundings->width * ( 1 - ( $startSpace + $endSpace ) ),
                    0
                );
                $end = new ezcGraphCoordinate(
                    $boundings->width * $startSpace +
                        $this->nullPosition * $boundings->width * ( 1 - ( $startSpace + $endSpace ) ),
                    $boundings->height
                );
                break;
            case ezcGraph::BOTTOM:
                $start = new ezcGraphCoordinate(
                    $boundings->width * $startSpace +
                        $this->nullPosition * $boundings->width * ( 1 - ( $startSpace + $endSpace ) ),
                    $boundings->height
                );
                $end = new ezcGraphCoordinate(
                    $boundings->width * $startSpace +
                        $this->nullPosition * $boundings->width * ( 1 - ( $startSpace + $endSpace ) ),
                    0
                );
                break;
            case ezcGraph::LEFT:
                $start = new ezcGraphCoordinate(
                    0,
                    $boundings->height * $endSpace +
                        $this->nullPosition * $boundings->height * ( 1 - ( $startSpace + $endSpace ) )
                );
                $end = new ezcGraphCoordinate(
                    $boundings->width,
                    $boundings->height * $endSpace +
                        $this->nullPosition * $boundings->height * ( 1 - ( $startSpace + $endSpace ) )
                );
                break;
            case ezcGraph::RIGHT:
                $start = new ezcGraphCoordinate(
                    $boundings->width,
                    $boundings->height * $endSpace +
                        $this->nullPosition * $boundings->height * ( 1 - ( $startSpace + $endSpace ) )
                );
                $end = new ezcGraphCoordinate(
                    0,
                    $boundings->height * $endSpace +
                        $this->nullPosition * $boundings->height * ( 1 - ( $startSpace + $endSpace ) )
                );
                break;
        }

        $renderer->drawAxis(
            $boundings,
            $start,
            $end,
            $this,
            $this->axisLabelRenderer,
            $innerBoundings
        );

        return $boundings;   
    }
}

?>
