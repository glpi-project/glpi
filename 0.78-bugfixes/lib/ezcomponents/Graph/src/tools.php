<?php
/**
 * File containing the abstract ezcGraph class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Toolkit for several operation with graphs
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphTools
{
    /**
     * Create an XHtml image map from a chart with gd driver. The name of the 
     * image map can be specified and will be ezcGraphImageMap otherwise.
     * 
     * @param ezcGraphChart $chart 
     * @param string $name
     * @return string
     */
    public static function createImageMap( ezcGraphChart $chart, $name = null )
    {
        if ( $name === null )
        {
            $name = 'ezcGraphImageMap';
        }

        if ( ! ( $chart->driver instanceof ezcGraphGdDriver ) )
        {
            throw new ezcGraphToolsIncompatibleDriverException( $chart->driver, 'ezcGraphGdDriver' );
        }

        $elements = $chart->renderer->getElementReferences();

        if ( !count( $elements ) )
        {
            throw new ezcGraphToolsNotRenderedException( $chart );
        }
    
        $imageMap = sprintf( "<map name=\"%s\">\n", $name );

        // Iterate over legends elements
        if ( isset( $elements['legend'] ) )
        {
            foreach ( $elements['legend'] as $objectName => $polygones )
            {
                $url = $elements['legend_url'][$objectName];

                if ( empty( $url ) )
                {
                    continue;
                }

                foreach ( $polygones as $shape => $polygone )
                {
                    $coordinateString = '';
                    foreach ( $polygone as $coordinate )
                    {
                        $coordinateString .= sprintf( '%d,%d,', $coordinate->x, $coordinate->y );
                    }

                    $imageMap .= sprintf( "\t<area shape=\"poly\" coords=\"%s\" href=\"%s\" alt=\"%s\" />\n",
                        substr( $coordinateString, 0, -1 ),
                        $url,
                        $objectName
                    );
                }
            }
        }

        // Iterate over data
        foreach ( $elements['data'] as $dataset => $datapoints )
        {
            foreach ( $datapoints as $datapoint => $polygones )
            {
                $url = $chart->data[$dataset]->url[$datapoint];

                if ( empty( $url ) )
                {
                    continue;
                }

                foreach ( $polygones as $polygon )
                {
                    $coordinateString = '';
                    foreach ( $polygon as $coordinate )
                    {
                        $coordinateString .= sprintf( '%d,%d,', $coordinate->x, $coordinate->y );
                    }

                    $imageMap .= sprintf( "\t<area shape=\"poly\" coords=\"%s\" href=\"%s\" alt=\"%s\" />\n",
                        substr( $coordinateString, 0, -1 ),
                        $url,
                        $datapoint
                    );
                }
            }
        }

        return $imageMap . "</map>\n";
    }

    /**
     * Add links to clickable SVG elements in a chart with SVG driver.
     * 
     * @param ezcGraphChart $chart 
     * @return void
     */
    public static function linkSvgElements( ezcGraphChart $chart )
    {
        if ( ! ( $chart->driver instanceof ezcGraphSvgDriver ) )
        {
            throw new ezcGraphToolsIncompatibleDriverException( $chart->driver, 'ezcGraphSvgDriver' );
        }

        $fileName = $chart->getRenderedFile();

        if ( !$fileName )
        {
            throw new ezcGraphToolsNotRenderedException( $chart );
        }

        $dom = new DOMDocument();
        $dom->load( $fileName );
        $xpath = new DomXPath( $dom );

        $elements = $chart->renderer->getElementReferences();

        // Link chart elements
        foreach ( $elements['data'] as $dataset => $datapoints )
        {
            foreach ( $datapoints as $datapoint => $ids )
            {
                $url = $chart->data[$dataset]->url[$datapoint];

                if ( empty( $url ) )
                {
                    continue;
                }

                foreach ( $ids as $id )
                {
                    $element = $xpath->query( '//*[@id = \'' . $id . '\']' )->item( 0 );

                    $element->setAttribute( 'style', $element->getAttribute( 'style' ) . ' cursor: ' . $chart->driver->options->linkCursor . ';' );
                    $element->setAttribute( 'onclick', "top.location = '{$url}'" );
                }
            }
        }

        // Link legend elements
        if ( isset( $elements['legend'] ) )
        {
            foreach ( $elements['legend'] as $objectName => $ids )
            {
                $url = $elements['legend_url'][$objectName];

                if ( empty( $url ) )
                {
                    continue;
                }

                foreach ( $ids as $id )
                {
                    $element = $xpath->query( '//*[@id = \'' . $id . '\']' )->item( 0 );

                    $element->setAttribute( 'style', $element->getAttribute( 'style' ) . ' cursor: ' . $chart->driver->options->linkCursor . ';' );
                    $element->setAttribute( 'onclick', "top.location = '{$url}'" );
                }
            }
        }

        $dom->save( $fileName );
    }
}

?>
