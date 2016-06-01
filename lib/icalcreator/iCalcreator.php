<?php
/*********************************************************************************/
/**
 *
 * A PHP implementation of rfc2445/rfc5545.
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
/**
 * iCalcreator.php
 *
 * iCalcreator (class) files includes
 *
 * @package icalcreator
 * @copyright Copyright (c) 2007-2015 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @since 2.21.14 - 2015-04-02
 */
/*********************************************************************************/
/**
 *         Do NOT remove or change version!!
 */
define( 'ICALCREATOR_VERSION', 'iCalcreator 2.22' );
/*********************************************************************************/
if( !defined( 'ICALCREATOR_LIB_DIR' ))
  define( 'ICALCREATOR_LIB_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR );
/**
 * iCalLoader
 *
 * load iCalcreator src and util classes
 *
 * @param string $class
 * @return void
 */
function iCalLoader( $class ) {
  $file  = ICALCREATOR_LIB_DIR . $class . '.class.php';
  if( file_exists( $file ))
    include $file;
}
spl_autoload_register( 'iCalLoader' );
/**
 * iCalcreator add-on functionality functions
 */
include ICALCREATOR_LIB_DIR . 'iCal.XML.inc.php';
include ICALCREATOR_LIB_DIR . 'iCal.vCard.inc.php';
include ICALCREATOR_LIB_DIR . 'iCal.tz.inc.php';
