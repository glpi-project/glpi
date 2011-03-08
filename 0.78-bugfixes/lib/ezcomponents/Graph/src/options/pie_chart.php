<?php
/**
 * File containing the ezcGraphPieChartOption class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class containing the basic options for pie charts.
 *
 * For additional options configuring the apperance of the chart you may also
 * want to check the option classes to configure the respective renderer you
 * are using:
 *
 * - ezcGraphRendererOptions
 * - ezcGraphRenderer2dOptions
 * - ezcGraphRenderer3dOptions
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteEzRed();
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *
 *   $graph->options->label = '%1$s (%3$.1f)';
 *   $graph->options->percentThreshold = .05;
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
 *   $graph->render( 400, 150, 'tutorial_pie_chart_options.svg' );
 * </code>
 *
 * @property string $label
 *           String used to label pies
 *              %1$s    Name of pie
 *              %2$d    Value of pie
 *              %3$.1f  Percentage
 * @property callback $labelCallback
 *           Callback function to format pie chart labels.
 *           Function will receive 3 parameters:
 *              string function( label, value, percent )
 * @property float $sum
 *           Fixed sum of values. This should be used for incomplete pie 
 *           charts.
 * @property float $percentThreshold
 *           Values with a lower percentage value are aggregated.
 * @property float $absoluteThreshold
 *           Values with a lower absolute value are aggregated.
 * @property string $summarizeCaption
 *           Caption for values summarized because they are lower then the
 *           configured tresh hold.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphPieChartOptions extends ezcGraphChartOptions
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
        $this->properties['label'] = '%1$s: %2$d (%3$.1f%%)';
        $this->properties['labelCallback'] = null;
        $this->properties['sum'] = false;

        $this->properties['percentThreshold'] = .0;
        $this->properties['absoluteThreshold'] = .0;
        $this->properties['summarizeCaption'] = 'Misc';

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
            case 'label':
                $this->properties['label'] = (string) $propertyValue;
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
            case 'sum':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties['sum'] = (float) $propertyValue;
                break;
            case 'percentThreshold':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) || 
                     ( $propertyValue > 1 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties['percentThreshold'] = (float) $propertyValue;
                break;
            case 'absoluteThreshold':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties['absoluteThreshold'] = (float) $propertyValue;
                break;
            case 'summarizeCaption':
                $this->properties['summarizeCaption'] = (string) $propertyValue;
                break;
            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
}

?>
