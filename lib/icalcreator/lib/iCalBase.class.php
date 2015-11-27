<?php
/*********************************************************************************/
/**
 *
 * iCalcreator, a PHP rfc2445/rfc5545 solution.
 *
 * @copyright Copyright (c) 2007-2015 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      http://kigkonsult.se/iCalcreator/index.php
 * @license   http://kigkonsult.se/downloads/dl.php?f=LGPL
 * @package   iCalcreator
 * @version   2.22
 */
/**
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/*********************************************************************************/
/**
 * iCalcreator base class
 *
 * properties and functions shared by vcalendar and calendarComponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 */
abstract class iCalBase {
/**
 *  @var array component property X-property value
 *  @access protected
 */
  protected $xprop;
/**  @var array container for sub-components */
  public $components;
/**  @var array $unparsed  calendar/components in 'raw' text... */
  public $unparsed;
/**
 *  @var bool   $allowEmpty  config variable
 *  @var string $language    config variable
 *  @var string $nl          config variable
 *  @var string $unique_id   config variable
 *  @var string $format      config variable
 *  @var string $dtzid       config variable
 *  @access protected
 */
  protected $allowEmpty;
  protected $language;
  protected $nl;
  protected $unique_id;
  protected $format;
  protected $dtzid;
/**
 *  @var string $componentStart1     valendar/component internal variable
 *  @var string $componentStart2     valendar/component internal variable
 *  @var string $componentEnd1       valendar/component internal variable
 *  @var string $componentEnd2       valendar/component internal variable
 *  @var string $elementStart1       valendar/component internal variable
 *  @var string $elementStart2       valendar/component internal variable
 *  @var string $elementEnd1         valendar/component internal variable
 *  @var string $elementEnd2         valendar/component internal variable
 *  @var string $intAttrDelimiter    valendar/component internal variable
 *  @var string $attributeDelimiter  valendar/component internal variable
 *  @var string $valueInit           valendar/component internal variable
 *  @access protected
 */
  protected $componentStart1;
  protected $componentStart2;
  protected $componentEnd1;
  protected $componentEnd2;
  protected $elementStart1;
  protected $elementStart2;
  protected $elementEnd1;
  protected $elementEnd2;
  protected $intAttrDelimiter;
  protected $attributeDelimiter;
  protected $valueInit;
/**
 *  @var array $xcaldecl  xCal declaration container
 *  @access protected
 */
  protected $xcaldecl;
/**
 * Property Name: x-prop
 */
/**
 * creates formatted output for calendar/component property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 * @uses iCalBase::$xprop
 * @uses vcalendar::getConfig()
 * @uses calendarComponent::getConfig()
 * @uses iCalBase::_createElement()
 * @uses iCalBase::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses iCalBase::$format
 * @uses iCalBase::$nl
 * @return string
 */
  public function createXprop() {
    if( empty( $this->xprop ) || !is_array( $this->xprop )) return FALSE;
    $output        = null;
    foreach( $this->xprop as $label => $xpropPart ) {
      if( ! isset( $xpropPart['value']) || ( empty( $xpropPart['value'] ) && !is_numeric( $xpropPart['value'] ))) {
        if( $this->getConfig( 'allowEmpty' ))
          $output .= $this->_createElement( $label );
        continue;
      }
      $attributes  = $this->_createParams( $xpropPart['params'], array( 'LANGUAGE' ));
      if( is_array( $xpropPart['value'] )) {
        foreach( $xpropPart['value'] as $pix => $theXpart )
          $xpropPart['value'][$pix] = iCalUtilityFunctions::_strrep( $theXpart, $this->format, $this->nl );
        $xpropPart['value']  = implode( ',', $xpropPart['value'] );
      }
      else
        $xpropPart['value'] = iCalUtilityFunctions::_strrep( $xpropPart['value'], $this->format, $this->nl );
      $output     .= $this->_createElement( $label, $attributes, $xpropPart['value'] );
    }
    return $output;
  }
/**
 * set calendar property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 * @param string $label
 * @param string $value
 * @param array $params optional
 * @uses vcalendar::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalBase::$xprop
 * @return bool
 */
  public function setXprop( $label, $value, $params=FALSE ) {
    if( empty( $label ))
      return FALSE;
    $label = strtoupper( $label );
    if( 'X-' != substr( $label, 0, 2 ))
      return FALSE;
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $xprop           = array( 'value' => $value );
    $xprop['params'] = iCalUtilityFunctions::_setParams( $params );
    if( ! is_array( $this->xprop ))
      $this->xprop = array();
    $this->xprop[$label] = $xprop;
    return TRUE;
  }
/**
 * create element format parts
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 * @uses iCalBase::$format
 * @uses iCalBase::$objName
 * @uses iCalBase::$componentStart1
 * @uses iCalBase::$elementStart1
 * @uses iCalBase::$componentStart2
 * @uses iCalBase::$elementStart2
 * @uses iCalBase::$componentEnd1
 * @uses iCalBase::$elementEnd1
 * @uses iCalBase::$componentEnd2
 * @uses iCalBase::$elementEnd2
 * @uses iCalBase::$nl;
 * @uses iCalBase::$intAttrDelimiter
 * @uses iCalBase::$attributeDelimiter
 * @uses iCalBase::$valueInit
 * @return bool
 */
  function _createFormat() {
    switch( $this->format ) {
      case 'xcal':
        $this->componentStart1    = $this->elementStart1 = '<';
        $this->componentStart2    = $this->elementStart2 = '>';
        $this->componentEnd1      = $this->elementEnd1   = '</';
        $this->componentEnd2      = $this->elementEnd2   = '>'.$this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = $this->nl;
        $this->valueInit          = null;
        break;
      default:
        $this->componentStart1    = 'BEGIN:';
        $this->componentStart2    = null;
        $this->componentEnd1      = 'END:';
        $this->componentEnd2      = $this->nl;
        $this->elementStart1      = null;
        $this->elementStart2      = null;
        $this->elementEnd1        = null;
        $this->elementEnd2        = $this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = ';';
        $this->valueInit          = ':';
        break;
    }
    return TRUE;
  }
/**
 * creates formatted output for calendar component property
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 * @param string $label property name
 * @param string $attributes property attributes
 * @param string $content property content (optional)
 * @uses iCalBase::$format
 * @uses iCalBase::$elementStart1
 * @uses iCalBase::$xcaldecl
 * @uses iCalBase::$intAttrDelimiter
 * @uses iCalBase::$attributeDelimiter
 * @uses iCalBase::_createElement()
 * @uses iCalBase::$elementStart2
 * @uses iCalBase::$nl
 * @uses iCalBase::$valueInit
 * @uses iCalUtilityFunctions::_size75()
 * @uses iCalBase::$elementEnd1
 * @uses iCalBase::$elementEnd2
 * @access protected
 * @return string
 */
  protected function _createElement( $label, $attributes=null, $content=FALSE ) {
    switch( $this->format ) {
      case 'xcal':
        $label = strtolower( $label );
        break;
      default:
        $label = strtoupper( $label );
        break;
    }
    $output = $this->elementStart1.$label;
    $categoriesAttrLang = null;
    $attachInlineBinary = FALSE;
    $attachfmttype      = null;
    if (( 'xcal' == $this->format) && ( 'x-' == substr( $label, 0, 2 ))) {
      $this->xcaldecl[] = array( 'xmldecl'  => 'ELEMENT'
                               , 'ref'      => $label
                               , 'type2'    => '(#PCDATA)' );
    }
    if( !empty( $attributes ))  {
      $attributes  = trim( $attributes );
      if ( 'xcal' == $this->format ) {
        $attributes2 = explode( $this->intAttrDelimiter, $attributes );
        $attributes  = null;
        foreach( $attributes2 as $aix => $attribute ) {
          $attrKVarr = explode( '=', $attribute );
          if( empty( $attrKVarr[0] ))
            continue;
          if( !isset( $attrKVarr[1] )) {
            $attrValue = $attrKVarr[0];
            $attrKey   = $aix;
          }
          elseif( 2 == count( $attrKVarr)) {
            $attrKey   = strtolower( $attrKVarr[0] );
            $attrValue = $attrKVarr[1];
          }
          else {
            $attrKey   = strtolower( $attrKVarr[0] );
            unset( $attrKVarr[0] );
            $attrValue = implode( '=', $attrKVarr );
          }
          if(( 'attach' == $label ) && ( in_array( $attrKey, array( 'fmttype', 'encoding', 'value' )))) {
            $attachInlineBinary = TRUE;
            if( 'fmttype' == $attrKey )
              $attachfmttype = $attrKey.'='.$attrValue;
            continue;
          }
          elseif(( 'categories' == $label ) && ( 'language' == $attrKey ))
            $categoriesAttrLang = $attrKey.'='.$attrValue;
          else {
            $attributes .= ( empty( $attributes )) ? ' ' : $this->attributeDelimiter.' ';
            $attributes .= ( !empty( $attrKey )) ? $attrKey.'=' : null;
            if(( '"' == substr( $attrValue, 0, 1 )) && ( '"' == substr( $attrValue, -1 ))) {
              $attrValue = substr( $attrValue, 1, ( strlen( $attrValue ) - 2 ));
              $attrValue = str_replace( '"', '', $attrValue );
            }
            $attributes .= '"'.htmlspecialchars( $attrValue ).'"';
          }
        }
      }
      else {
        $attributes = str_replace( $this->intAttrDelimiter, $this->attributeDelimiter, $attributes );
      }
    }
    if(( 'xcal' == $this->format) &&
       ((( 'attach' == $label ) && !$attachInlineBinary ) || ( in_array( $label, array( 'tzurl', 'url' ))))) {
      $pos = strrpos( $content, "/" );
      $docname = ( $pos !== false) ? substr( $content, (1 - strlen( $content ) + $pos )) : $content;
      $this->xcaldecl[] = array( 'xmldecl'  => 'ENTITY'
                               , 'uri'      => $docname
                               , 'ref'      => 'SYSTEM'
                               , 'external' => $content
                               , 'type'     => 'NDATA'
                               , 'type2'    => 'BINERY' );
      $attributes .= ( empty( $attributes )) ? ' ' : $this->attributeDelimiter.' ';
      $attributes .= 'uri="'.$docname.'"';
      $content = null;
      if( 'attach' == $label ) {
        $attributes = str_replace( $this->attributeDelimiter, $this->intAttrDelimiter, $attributes );
        $content = $this->nl.$this->_createElement( 'extref', $attributes, null );
        $attributes = null;
      }
    }
    elseif(( 'xcal' == $this->format) && ( 'attach' == $label ) && $attachInlineBinary ) {
      $content = $this->nl.$this->_createElement( 'b64bin', $attachfmttype, $content ); // max one attribute
    }
    $output .= $attributes;
    if( !$content && ( '0' != $content )) {
      switch( $this->format ) {
        case 'xcal':
          $output .= ' /';
          $output .= $this->elementStart2.$this->nl;
          return $output;
          break;
        default:
          $output .= $this->elementStart2.$this->valueInit;
          return iCalUtilityFunctions::_size75( $output, $this->nl );
          break;
      }
    }
    $output .= $this->elementStart2;
    $output .= $this->valueInit.$content;
    switch( $this->format ) {
      case 'xcal':
        return $output.$this->elementEnd1.$label.$this->elementEnd2;
        break;
      default:
        return iCalUtilityFunctions::_size75( $output, $this->nl );
        break;
    }
  }
/**
 * creates formatted output for calendar component property parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-31
 * @param array $params  optional
 * @param array $ctrKeys optional
 * @uses iCalBase::$intAttrDelimiter
 * @uses vcalendar::getConfig()
 * @uses calendarComponent::getConfig()
 * @access protected
 * @return string
 */
  protected function _createParams( $params=array(), $ctrKeys=array() ) {
    if( !is_array( $params ) || empty( $params ))
      $params = array();
    $attrLANG = $attr1 = $attr2 = $lang = null;
    $CNattrKey   = ( in_array( 'CN',       $ctrKeys )) ? TRUE : FALSE ;
    $LANGattrKey = ( in_array( 'LANGUAGE', $ctrKeys )) ? TRUE : FALSE ;
    $CNattrExist = $LANGattrExist = FALSE;
    $xparams = array();
    $params  = array_change_key_case( $params, CASE_UPPER );
    foreach( $params as $paramKey => $paramValue ) {
      if(( FALSE !== strpos( $paramValue, ':' )) ||
         ( FALSE !== strpos( $paramValue, ';' )) ||
         ( FALSE !== strpos( $paramValue, ',' )))
        $paramValue = '"'.$paramValue.'"';
      if( ctype_digit( (string) $paramKey )) {
        $xparams[]          = $paramValue;
        continue;
      }
      if( !in_array( $paramKey, array( 'ALTREP', 'CN', 'DIR', 'ENCODING', 'FMTTYPE', 'LANGUAGE', 'RANGE', 'RELTYPE', 'SENT-BY', 'TZID', 'VALUE' )))
        $xparams[$paramKey] = $paramValue;
      else
        $params[$paramKey]  = $paramValue;
    }
    ksort( $xparams, SORT_STRING );
    foreach( $xparams as $paramKey => $paramValue ) {
      if( ctype_digit( (string) $paramKey ))
        $attr2             .= $this->intAttrDelimiter.$paramValue;
      else
        $attr2             .= $this->intAttrDelimiter."$paramKey=$paramValue";
    }
    if( isset( $params['FMTTYPE'] )  && !in_array( 'FMTTYPE', $ctrKeys )) {
      $attr1               .= $this->intAttrDelimiter.'FMTTYPE='.$params['FMTTYPE'].$attr2;
      $attr2                = null;
    }
    if( isset( $params['ENCODING'] ) && !in_array( 'ENCODING',   $ctrKeys )) {
      if( !empty( $attr2 )) {
        $attr1             .= $attr2;
        $attr2              = null;
      }
      $attr1               .= $this->intAttrDelimiter.'ENCODING='.$params['ENCODING'];
    }
    if( isset( $params['VALUE'] )    && !in_array( 'VALUE',   $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'VALUE='.$params['VALUE'];
    if( isset( $params['TZID'] )     && !in_array( 'TZID',    $ctrKeys )) {
      $attr1               .= $this->intAttrDelimiter.'TZID='.$params['TZID'];
    }
    if( isset( $params['RANGE'] )    && !in_array( 'RANGE',   $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'RANGE='.$params['RANGE'];
    if( isset( $params['RELTYPE'] )  && !in_array( 'RELTYPE', $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'RELTYPE='.$params['RELTYPE'];
    if( isset( $params['CN'] )       && $CNattrKey ) {
      $attr1                = $this->intAttrDelimiter.'CN='.$params['CN'];
      $CNattrExist          = TRUE;
    }
    if( isset( $params['DIR'] )      && in_array( 'DIR',      $ctrKeys )) {
      $delim = ( FALSE !== strpos( $params['DIR'], '"' )) ? '' : '"';
      $attr1               .= $this->intAttrDelimiter.'DIR='.$delim.$params['DIR'].$delim;
    }
    if( isset( $params['SENT-BY'] )  && in_array( 'SENT-BY',  $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'SENT-BY='.$params['SENT-BY'];
    if( isset( $params['ALTREP'] )   && in_array( 'ALTREP',   $ctrKeys )) {
      $delim = ( FALSE !== strpos( $params['ALTREP'], '"' )) ? '' : '"';
      $attr1               .= $this->intAttrDelimiter.'ALTREP='.$delim.$params['ALTREP'].$delim;
    }
    if( isset( $params['LANGUAGE'] ) && $LANGattrKey ) {
      $attrLANG            .= $this->intAttrDelimiter.'LANGUAGE='.$params['LANGUAGE'];
      $LANGattrExist        = TRUE;
    }
    if( !$LANGattrExist ) {
      $lang = $this->getConfig( 'language' );
      if(( $CNattrExist || $LANGattrKey ) && $lang )
        $attrLANG .= $this->intAttrDelimiter.'LANGUAGE='.$lang;
    }
    return $attr1.$attrLANG.$attr2;
  }
}
