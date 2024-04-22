<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use donatj\UserAgent\UserAgentParser;
use donatj\UserAgent\Platforms;
use Glpi\Application\ErrorHandler;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Console\Application;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\FrontEnd;
use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\URL;
use ScssPhp\ScssPhp\Compiler;

/**
 * Html Class
 * Inpired from Html/FormHelper for several functions
 **/
class Html
{
    /**
     * Clean display value deleting html tags
     *
     * @param string  $value      string value
     * @param boolean $striptags  strip all html tags
     * @param integer $keep_bad
     *          1 : neutralize tag anb content,
     *          2 : remove tag and neutralize content
     * @return string
     *
     * @deprecated 10.0.0
     **/
    public static function clean($value, $striptags = true, $keep_bad = 2)
    {
        Toolbox::deprecated('Use Toolbox::stripTags(), Glpi\RichText\RichText::getSafeHtml(), Glpi\RichText\RichText::getEnhancedHtml() or Glpi\RichText\RichText::getTextFromHtml().');

        $value = Html::entity_decode_deep($value);

       // Change <email@domain> to email@domain so it is not removed by htmLawed
       // Search for strings that is an email surrounded by `<` and `>` but that cannot be an HTML tag:
       // - absence of quotes indicate that values is not part of an HTML attribute,
       // - absence of > ensure that ending `>` has not been reached.
        $regex = "/(<[^\"'>]+?@[^>\"']+?>)/";
        $value = preg_replace_callback($regex, function ($matches) {
            return substr($matches[1], 1, (strlen($matches[1]) - 2));
        }, $value);

       // Clean MS office tags
        $value = str_replace(["<![if !supportLists]>", "<![endif]>"], '', $value);

        if ($striptags) {
           // Strip ToolTips
            $specialfilter = ['@<div[^>]*?tooltip_picture[^>]*?>.*?</div[^>]*?>@si',
                '@<div[^>]*?tooltip_text[^>]*?>.*?</div[^>]*?>@si',
                '@<div[^>]*?tooltip_picture_border[^>]*?>.*?</div[^>]*?>@si',
                '@<div[^>]*?invisible[^>]*?>.*?</div[^>]*?>@si'
            ];
            $value         = preg_replace($specialfilter, '', $value);

            $value = preg_replace("/<(p|br|div)( [^>]*)?" . ">/i", "\n", $value);
            $value = preg_replace("/(&nbsp;| |\xC2\xA0)+/", " ", $value);
        }

        $search = ['@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
            '@<style[^>]*?>.*?</style[^>]*?>@si', // Strip out style
            '@<title[^>]*?>.*?</title[^>]*?>@si', // Strip out title
            '@<!DOCTYPE[^>]*?>@si', // Strip out !DOCTYPE
        ];
        $value = preg_replace($search, '', $value);

       // Neutralize not well formatted html tags
        $value = preg_replace("/(<)([^>]*<)/", "&lt;$2", $value);

        $config = Toolbox::getHtmLawedSafeConfig();
        $config['keep_bad'] = $keep_bad; // 1: neutralize tag and content, 2 : remove tag and neutralize content
        if ($striptags) {
            $config['elements'] = 'none';
        }

        $value = htmLawed($value, $config);

       // Special case : remove the 'denied:' for base64 img in case the base64 have characters
       // combinaison introduce false positive
        foreach (['png', 'gif', 'jpg', 'jpeg'] as $imgtype) {
            $value = str_replace(
                'src="denied:data:image/' . $imgtype . ';base64,',
                'src="data:image/' . $imgtype . ';base64,',
                $value
            );
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace("/(\n[ ]*){2,}/", "\n\n", $value, -1);

        return trim($value);
    }


    /**
     * Recursivly execute html_entity_decode on an array
     *
     * @param string|array $value
     *
     * @return string|array
     **/
    public static function entity_decode_deep($value)
    {
        if (is_array($value)) {
            return array_map([__CLASS__, 'entity_decode_deep'], $value);
        }
        if (!is_string($value)) {
            return $value;
        }

        return html_entity_decode($value, ENT_QUOTES, "UTF-8");
    }


    /**
     * Recursivly execute htmlentities on an array
     *
     * @param string|array $value
     *
     * @return string|array
     **/
    public static function entities_deep($value)
    {
        if (is_array($value)) {
            return array_map([__CLASS__, 'entities_deep'], $value);
        }
        if (!is_string($value)) {
            return $value;
        }

        return htmlentities($value, ENT_QUOTES, "UTF-8");
    }


    /**
     * Convert a date YY-MM-DD to DD-MM-YY for calendar
     *
     * @param string       $time    Date to convert
     * @param integer|null $format  Date format
     *
     * @return null|string
     *
     * @see Toolbox::getDateFormats()
     **/
    public static function convDate($time, $format = null)
    {

        if (is_null($time) || trim($time) == '' || in_array($time, ['NULL', '0000-00-00', '0000-00-00 00:00:00'])) {
            return null;
        }

        if (!isset($_SESSION["glpidate_format"])) {
            $_SESSION["glpidate_format"] = 0;
        }
        if (!$format) {
            $format = $_SESSION["glpidate_format"];
        }

        try {
            $date = new \DateTime($time);
        } catch (\Throwable $e) {
            ErrorHandler::getInstance()->handleException($e);
            Session::addMessageAfterRedirect(
                sprintf(
                    __('%1$s %2$s'),
                    $time,
                    _x('adjective', 'Invalid')
                )
            );
            return $time;
        }
        $mask = 'Y-m-d';

        switch ($format) {
            case 1: // DD-MM-YYYY
                $mask = 'd-m-Y';
                break;
            case 2: // MM-DD-YYYY
                $mask = 'm-d-Y';
                break;
        }

        return $date->format($mask);
    }


    /**
     * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
     *
     * @param string       $time            Datetime to convert
     * @param integer|null $format          Datetime format
     * @param bool         $with_seconds    Indicates if seconds should be present in output
     *
     * @return null|string
     **/
    public static function convDateTime($time, $format = null, bool $with_seconds = false)
    {

        if (is_null($time) || ($time == 'NULL')) {
            return null;
        }

        return self::convDate($time, $format) . ' ' . substr($time, 11, $with_seconds ? 8 : 5);
    }


    /**
     * Clean string for input text field
     *
     * @param string $string
     *
     * @return string
     **/
    public static function cleanInputText($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        return preg_replace('/\'/', '&apos;', preg_replace('/\"/', '&quot;', $string));
    }


    /**
     * Clean all parameters of an URL. Get a clean URL
     *
     * @param string $url
     *
     * @return string
     **/
    public static function cleanParametersURL($url)
    {

        $url = preg_replace("/(\/[0-9a-zA-Z\.\-\_]+\.php).*/", "$1", $url);
        return preg_replace("/\?.*/", "", $url);
    }


    /**
     *  Resume text for followup
     *
     * @param string  $string  string to resume
     * @param integer $length  resume length (default 255)
     *
     * @return string
     **/
    public static function resume_text($string, $length = 255)
    {

        if (Toolbox::strlen($string) > $length) {
            $string = Toolbox::substr($string, 0, $length) . "&nbsp;(...)";
        }

        return $string;
    }


    /**
     * Clean post value for display in textarea
     *
     * @param string $value
     *
     * @return string
     **/
    public static function cleanPostForTextArea($value)
    {

        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }
        $order   = ['\r\n',
            '\n',
            "\\'",
            '\"',
            '\\\\'
        ];
        $replace = ["\n",
            "\n",
            "'",
            '"',
            "\\"
        ];
        return str_replace($order, $replace, $value);
    }


    /**
     * Convert a number to correct display
     *
     * @param float   $number        Number to display
     * @param boolean $edit          display number for edition ? (id edit use . in all case)
     * @param integer $forcedecimal  Force decimal number (do not use default value) (default -1)
     *
     * @return string
     **/
    public static function formatNumber($number, $edit = false, $forcedecimal = -1)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // Php 5.3 : number_format() expects parameter 1 to be double,
        if ($number == "") {
            $number = 0;
        } else if ($number == "-") { // used for not defines value (from Infocom::Amort, p.e.)
            return "-";
        }

        $number  = doubleval($number);
        $decimal = $CFG_GLPI["decimal_number"];
        if ($forcedecimal >= 0) {
            $decimal = $forcedecimal;
        }

       // Edit : clean display for mysql
        if ($edit) {
            return number_format($number, $decimal, '.', '');
        }

       // Display : clean display
        switch ($_SESSION['glpinumber_format']) {
            case 0: // French
                return number_format($number, $decimal, '.', ' ');

            case 2: // Other French
                return number_format($number, $decimal, ',', ' ');

            case 3: // No space with dot
                return number_format($number, $decimal, '.', '');

            case 4: // No space with comma
                return number_format($number, $decimal, ',', '');

            default: // English
                return number_format($number, $decimal, '.', ',');
        }
    }


    /**
     * Make a good string from the unix timestamp $sec
     *
     * @param int|float  $time         timestamp
     * @param boolean    $display_sec  display seconds ?
     * @param boolean    $use_days     use days for display ?
     *
     * @return string
     **/
    public static function timestampToString($time, $display_sec = true, $use_days = true)
    {

        $time = (float)$time;

        $sign = '';
        if ($time < 0) {
            $sign = '- ';
            $time = abs($time);
        }
        $time = floor($time);

       // Force display seconds if time is null
        if ($time < MINUTE_TIMESTAMP) {
            $display_sec = true;
        }

        $units = Toolbox::getTimestampTimeUnits($time);
        if ($use_days) {
            if ($units['day'] > 0) {
                if ($display_sec) {
                     //TRANS: %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
                     //       %4$d number of minutes, %5$d number of seconds
                     return sprintf(
                         __('%1$s%2$d days %3$d hours %4$d minutes %5$d seconds'),
                         $sign,
                         $units['day'],
                         $units['hour'],
                         $units['minute'],
                         $units['second']
                     );
                }
              //TRANS:  %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
              //        %4$d number of minutes
                return sprintf(
                    __('%1$s%2$d days %3$d hours %4$d minutes'),
                    $sign,
                    $units['day'],
                    $units['hour'],
                    $units['minute']
                );
            }
        } else {
            if ($units['day'] > 0) {
                $units['hour'] += 24 * $units['day'];
            }
        }

        if ($units['hour'] > 0) {
            if ($display_sec) {
               //TRANS:  %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes,
               //        %4$d number of seconds
                return sprintf(
                    __('%1$s%2$d hours %3$d minutes %4$d seconds'),
                    $sign,
                    $units['hour'],
                    $units['minute'],
                    $units['second']
                );
            }
           //TRANS: %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes
            return sprintf(__('%1$s%2$d hours %3$d minutes'), $sign, $units['hour'], $units['minute']);
        }

        if ($units['minute'] > 0) {
            if ($display_sec) {
               //TRANS:  %1$s is the sign (-or empty), %2$d number of minutes,  %3$d number of seconds
                return sprintf(
                    __('%1$s%2$d minutes %3$d seconds'),
                    $sign,
                    $units['minute'],
                    $units['second']
                );
            }
           //TRANS: %1$s is the sign (-or empty), %2$d number of minutes
            return sprintf(
                _n('%1$s%2$d minute', '%1$s%2$d minutes', $units['minute']),
                $sign,
                $units['minute']
            );
        }

        if ($display_sec) {
           //TRANS:  %1$s is the sign (-or empty), %2$d number of seconds
            return sprintf(
                _n('%1$s%2$s second', '%1$s%2$s seconds', $units['second']),
                $sign,
                $units['second']
            );
        }
        return '';
    }


    /**
     * Format a timestamp into a normalized string (hh:mm:ss).
     *
     * @param integer $time
     *
     * @return string
     **/
    public static function timestampToCsvString($time)
    {

        if ($time < 0) {
            $time = abs($time);
        }
        $time = floor($time);

        $units = Toolbox::getTimestampTimeUnits($time);

        if ($units['day'] > 0) {
            $units['hour'] += 24 * $units['day'];
        }

        return str_pad($units['hour'], 2, '0', STR_PAD_LEFT)
         . ':'
         . str_pad($units['minute'], 2, '0', STR_PAD_LEFT)
         . ':'
         . str_pad($units['second'], 2, '0', STR_PAD_LEFT);
    }


    /**
     * Redirection to $_SERVER['HTTP_REFERER'] page
     *
     * @return void
     **/
    public static function back()
    {
        self::redirect(self::getBackUrl());
    }


    /**
     * Redirection hack
     *
     * @param $dest string: Redirection destination
     * @param $http_response_code string: Forces the HTTP response code to the specified value
     *
     * @return void
     **/
    public static function redirect($dest, $http_response_code = 302)
    {

        $toadd = '';
        $dest = addslashes($dest);

        if (!headers_sent() && !Toolbox::isAjax()) {
            header("Location: $dest", true, $http_response_code);
            exit();
        }

        if (strpos($dest, "?") !== false) {
            $toadd = '&tokonq=' . Toolbox::getRandomString(5);
        } else {
            $toadd = '?tokonq=' . Toolbox::getRandomString(5);
        }

        echo "<script type='text/javascript'>
            NomNav = navigator.appName;
            if (NomNav=='Konqueror') {
               window.location='" . $dest . $toadd . "';
            } else {
               window.location='" . $dest . "';
            }
         </script>";
        exit();
    }

    /**
     * Redirection to Login page
     *
     * @param string $params  param to add to URL (default '')
     * @since 0.85
     *
     * @return void
     **/
    public static function redirectToLogin($params = '')
    {
        /**
         * @var int $AJAX_INCLUDE
         * @var array $CFG_GLPI
         */
        global $AJAX_INCLUDE, $CFG_GLPI;

        $dest     = $CFG_GLPI["root_doc"] . "/index.php";

        if (!$AJAX_INCLUDE) {
            $url_dest = preg_replace(
                '/^' . preg_quote($CFG_GLPI["root_doc"], '/') . '/',
                '',
                $_SERVER['REQUEST_URI']
            );
            $dest .= "?redirect=" . rawurlencode($url_dest);
        }

        if (!empty($params)) {
            if (str_contains($dest, '?')) {
                $dest .= '&' . $params;
            } else {
                $dest .= '?' . $params;
            }
        }

        self::redirect($dest);
    }


    /**
     * Display common message for item not found
     *
     * @return void
     **/
    public static function displayNotFoundError(string $additional_info = '')
    {
        /**
         * @var array $CFG_GLPI
         * @var bool $HEADER_LOADED
         */
        global $CFG_GLPI, $HEADER_LOADED;

        if (!$HEADER_LOADED) {
            if (!Session::getCurrentInterface()) {
                self::nullHeader(__('Access denied'));
            } else if (Session::getCurrentInterface() == "central") {
                self::header(__('Access denied'));
            } else if (Session::getCurrentInterface() == "helpdesk") {
                self::helpHeader(__('Access denied'));
            }
        }
        echo "<div class='center'><br><br>";
        echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='" . __s('Warning') . "'>";
        echo "<br><br><span class='b'>" . __('Item not found') . "</span></div>";
        $requested_url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        $user_id = Session::getLoginUserID() ?? 'Anonymous';
        $internal_message = "User ID: $user_id tried to access a non-existent item $requested_url. Additional information: $additional_info\n";
        $internal_message .= "\tStack Trace:\n";
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $frame) {
            $internal_message .= "\t\t" . $frame['file'] . ':' . $frame['line'] . ' ' . $frame['function'] . '()' . "\n";
        }
        Toolbox::logInFile('access-errors', $internal_message);
        self::nullFooter();
        exit();
    }


    /**
     * Display common message for privileges errors
     *
     * @return void
     **/
    public static function displayRightError(string $additional_info = '')
    {
        Toolbox::handleProfileChangeRedirect();
        $requested_url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Unknown');
        $user_id = Session::getLoginUserID() ?? 'Anonymous';
        if (empty($additional_info)) {
            $additional_info = __('No additional information given');
        }
        $internal_message = "User ID: $user_id tried to access or perform an action on $requested_url with insufficient rights. Additional information: $additional_info\n";
        $internal_message .= "\tStack Trace:\n";
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace_string = '';
        foreach ($backtrace as $frame) {
            $trace_string .= "\t\t" . $frame['file'] . ':' . $frame['line'] . ' ' . $frame['function'] . '()' . "\n";
        }
        $internal_message .= $trace_string;
        Toolbox::logInFile('access-errors', $internal_message);
        self::displayErrorAndDie(__("You don't have permission to perform this action."));
    }


    /**
     * Display a div containing messages set in session in the previous page
     **/
    public static function displayMessageAfterRedirect(bool $display_container = true)
    {
        TemplateRenderer::getInstance()->display('components/messages_after_redirect_toasts.html.twig', [
            'display_container' => $display_container
        ]);
    }


    public static function displayAjaxMessageAfterRedirect()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        echo Html::scriptBlock("
      displayAjaxMessageAfterRedirect = function() {
         $('.messages_after_redirect').remove();
         $.ajax({
            url:  '" . $CFG_GLPI['root_doc'] . "/ajax/displayMessageAfterRedirect.php',
            success: function(html) {
               $('body').append(html);
            }
         });
      }");
    }


    /**
     * Common Title Function
     *
     * @param string        $ref_pic_link    Path to the image to display (default '')
     * @param string        $ref_pic_text    Alt text of the icon (default '')
     * @param string        $ref_title       Title to display (default '')
     * @param array|string  $ref_btts        Extra items to display array(link=>text...) (default '')
     *
     * @return void
     **/
    public static function displayTitle($ref_pic_link = "", $ref_pic_text = "", $ref_title = "", $ref_btts = "")
    {

        echo "<div class='btn-group flex-wrap mb-3'>";
        if ($ref_pic_link != "") {
            $ref_pic_text = Toolbox::stripTags($ref_pic_text);
            echo Html::image($ref_pic_link, ['alt' => $ref_pic_text]);
        }

        if ($ref_title != "") {
            echo "<span class='btn bg-blue-lt pe-none' aria-disabled='true'>
            $ref_title
         </span>";
        }

        if (is_array($ref_btts) && count($ref_btts)) {
            foreach ($ref_btts as $key => $val) {
                echo "<a class='btn btn-outline-secondary' href='" . $key . "'>" . $val . "</a>";
            }
        }
        echo "</div>";
    }


    /**
     * Clean Display of Request
     *
     * @since 0.83.1
     *
     * @param string $request  SQL request
     *
     * @return string
     **/
    public static function cleanSQLDisplay($request)
    {

        $request = str_replace("<", "&lt;", $request);
        $request = str_replace(">", "&gt;", $request);
        $request = str_ireplace("UNION", "<br/>UNION<br/>", $request);
        $request = str_ireplace("UNION ALL", "<br/>UNION ALL<br/>", $request);
        $request = str_ireplace("FROM", "<br/>FROM", $request);
        $request = str_ireplace("WHERE", "<br/>WHERE", $request);
        $request = str_ireplace("INNER JOIN", "<br/>INNER JOIN", $request);
        $request = str_ireplace("LEFT JOIN", "<br/>LEFT JOIN", $request);
        $request = str_ireplace("ORDER BY", "<br/>ORDER BY", $request);
        $request = str_ireplace("SORT", "<br/>SORT", $request);

        return $request;
    }

    /**
     * Display Debug Information
     *
     * @param boolean $with_session with session information (true by default)
     * @param boolean $ajax         If we're called from ajax (false by default)
     *
     * @return void
     * @deprecated 10.0.0
     **/
    public static function displayDebugInfos($with_session = true, $ajax = false, $rand = null)
    {
        Toolbox::deprecated('Html::displayDebugInfo is not used anymore. It was replaced by a unified debug bar.');
    }


    /**
     * Display a Link to the last page using http_referer if available else use history.back
     **/
    public static function displayBackLink()
    {
        $url_referer = self::getBackUrl();
        if ($url_referer !== false) {
            echo "<a href='$url_referer'>" . __('Back') . "</a>";
        } else {
            echo "<a href='javascript:history.back();'>" . __('Back') . "</a>";
        }
    }

    /**
     * Return an url for getting back to previous page.
     * Remove `forcetab` parameter if exists to prevent bad tab display
     *
     * @param string $url_in optional url to return (without forcetab param), if empty, we will user HTTP_REFERER from server
     *
     * @since 9.2.2
     *
     * @return mixed [string|boolean] false, if failed, else the url string
     */
    public static function getBackUrl($url_in = "")
    {
        if (
            isset($_SERVER['HTTP_REFERER'])
            && strlen($url_in) == 0
        ) {
            $url_in = $_SERVER['HTTP_REFERER'];
        }
        if (strlen($url_in) > 0) {
            $url = parse_url($url_in);

            if (isset($url['query'])) {
                parse_str($url['query'], $parameters);
                unset($parameters['forcetab']);
                unset($parameters['tab_params']);
                $new_query = http_build_query($parameters);
                return str_replace($url['query'], $new_query, $url_in);
            }

            return $url_in;
        }
        return false;
    }


    /**
     * Simple Error message page
     *
     * @param string  $message  displayed before dying
     * @param boolean $minimal  set to true do not display app menu (false by default)
     *
     * @return void
     **/
    public static function displayErrorAndDie($message, $minimal = false)
    {
        /** @var bool $HEADER_LOADED */
        global $HEADER_LOADED;

        if (!$HEADER_LOADED) {
            if ($minimal || !Session::getCurrentInterface()) {
                self::nullHeader(__('Access denied'), '');
            } else if (Session::getCurrentInterface() == "central") {
                self::header(__('Access denied'), '');
            } else if (Session::getCurrentInterface() == "helpdesk") {
                self::helpHeader(__('Access denied'), '');
            }
        }

        TemplateRenderer::getInstance()->display('display_and_die.html.twig', [
            'title'   => __('Access denied'),
            'message' => $message,
            'link'    => Html::getBackUrl(),
        ]);

        self::nullFooter();
        exit();
    }


    /**
     * Add confirmation on button or link before action
     *
     * @param $string             string   to display or array of string for using multilines
     * @param $additionalactions  string   additional actions to do on success confirmation
     *                                     (default '')
     *
     * @return string
     **/
    public static function addConfirmationOnAction($string, $additionalactions = '')
    {

        return "onclick=\"" . Html::getConfirmationOnActionScript($string, $additionalactions) . "\"";
    }


    /**
     * Get confirmation on button or link before action
     *
     * @since 0.85
     *
     * @param $string             string   to display or array of string for using multilines
     * @param $additionalactions  string   additional actions to do on success confirmation
     *                                     (default '')
     *
     * @return string confirmation script
     **/
    public static function getConfirmationOnActionScript($string, $additionalactions = '')
    {

        if (!is_array($string)) {
            $string = [$string];
        }
        $string            = Toolbox::addslashes_deep($string);
        $additionalactions = trim($additionalactions);
        $out               = "";
        $multiple          = false;
        $close_string      = '';
       // Manage multiple confirmation
        foreach ($string as $tab) {
            if (is_array($tab)) {
                $multiple      = true;
                $out          .= "if (window.confirm('";
                $out          .= implode('\n', $tab);
                $out          .= "')){ ";
                $close_string .= "return true;} else { return false;}";
            }
        }
       // manage simple confirmation
        if (!$multiple) {
            $out          .= "if (window.confirm('";
            $out          .= implode('\n', $string);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
        }
        $out .= $additionalactions . (substr($additionalactions, -1) != ';' ? ';' : '') . $close_string;
        return $out;
    }


    /**
     * Manage progresse bars
     *
     * @since 0.85
     *
     * @param $id                 HTML ID of the progress bar
     * @param $options    array   progress status
     *                    - create    do we have to create it ?
     *                    - message   add or change the message
     *                    - percent   current level
     *
     *
     * @return string|void Generated HTML if `display` param is true, void otherwise.
     **/
    public static function progressBar($id, array $options = [])
    {

        $params            = [];
        $params['create']  = false;
        $params['message'] = null;
        $params['percent'] = -1;
        $params['display'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $out = '';
        if ($params['create']) {
            $out = <<<HTML
            <div class="progress bg-primary-emphasis bg-light" style="height: 16px" id="{$id}">
                <div class="progress-bar progress-bar-striped bg-info text-dark" role="progressbar"
                    style="width: 0%; overflow: visible;"
                    aria-valuenow="0"
                    aria-valuemin="0" aria-valuemax="100"
                    id="{$id}_text">
                </div>
            </div>
HTML;
        }

        if ($params['message'] !== null) {
            $out .= Html::scriptBlock(self::jsGetElementbyID($id . '_text') . ".html(\"" .
                                addslashes($params['message']) . "\");");
        }

        if (
            ($params['percent'] >= 0)
            && ($params['percent'] <= 100)
        ) {
            $out .= Html::scriptBlock(self::jsGetElementbyID($id . '_text') . ".css('width', '" .
                                $params['percent'] . "%' );");
        }

        if (!$params['display']) {
            return $out;
        }

        echo $out;
        if (!$params['create']) {
            self::glpi_flush();
        }
    }


    /**
     * Create a Dynamic Progress Bar
     *
     * @param string $msg  initial message (under the bar)
     *
     * @return void
     **/
    public static function createProgressBar($msg = "&nbsp;")
    {

        $options = ['create' => true];
        if ($msg != "&nbsp;") {
            $options['message'] = $msg;
        }

        self::progressBar('doaction_progress', $options);
    }

    /**
     * Change the Message under the Progress Bar
     *
     * @param string $msg message under the bar
     *
     * @return void
     **/
    public static function changeProgressBarMessage($msg = "&nbsp;")
    {

        self::progressBar('doaction_progress', ['message' => $msg]);
        self::glpi_flush();
    }


    /**
     * Change the Progress Bar Position
     *
     * @param float  $crt   Current Value (less then $tot)
     * @param float  $tot   Maximum Value
     * @param string $msg   message inside the bar (default is %)
     *
     * @return void
     **/
    public static function changeProgressBarPosition($crt, $tot, $msg = "")
    {

        $options = [];

        if (!$tot) {
            $options['percent'] = 0;
        } else if ($crt > $tot) {
            $options['percent'] = 100;
        } else {
            $options['percent'] = 100 * $crt / $tot;
        }

        if ($msg != "") {
            $options['message'] = $msg;
        }

        self::progressBar('doaction_progress', $options);
        self::glpi_flush();
    }


    /**
     * Display a simple progress bar
     *
     * @param integer $width       Width   of the progress bar
     * @param float   $percent     Percent of the progress bar
     * @param array   $options     possible options:
     *            - title : string title to display (default Progesssion)
     *            - simple : display a simple progress bar (no title / only percent)
     *            - forcepadding : boolean force str_pad to force refresh (default true)
     *
     * @return void
     **/
    public static function displayProgressBar($width, $percent, $options = [])
    {
        $param['title']        = __('Progress');
        $param['simple']       = false;
        $param['forcepadding'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $title = $param['title'];
        $label = "";
        if ($param['simple']) {
            $label = "$percent%";
            $title = "";
        }

        $output = <<<HTML
      $title
      <div class="progress" style="height: 15px; min-width: 50px;">
         <div class="progress-bar bg-info" role="progressbar" style="width: {$percent}%;"
            aria-valuenow="$percent" aria-valuemin="0" aria-valuemax="100">$label</div>
      </div>
HTML;

        if (!$param['forcepadding']) {
            echo $output;
        } else {
            echo Toolbox::str_pad($output, 4096);
            self::glpi_flush();
        }
    }


    /**
     * Include common HTML headers
     *
     * @param string $title   title used for the page (default '')
     * @param string $sector  sector in which the page displayed is
     * @param string $item    item corresponding to the page displayed
     * @param string $option  option corresponding to the page displayed
     * @param bool   $add_id  add current item id to the title ?
     *
     * @return void
     */
    public static function includeHeader(
        $title = '',
        $sector = 'none',
        $item = 'none',
        $option = '',
        bool $add_id = true,
        bool $allow_insecured_iframe = false
    ) {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

        // complete title with id if exist
        if ($add_id && isset($_GET['id']) && $_GET['id']) {
            $title = sprintf(__('%1$s - %2$s'), $title, $_GET['id']);
        }

        // Send UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");

        if (!$allow_insecured_iframe) {
            // Allow only frame from same server to prevent click-jacking
            header('x-frame-options:SAMEORIGIN');
        }

        // Send extra expires header
        self::header_nocache();

        $theme = $_SESSION['glpipalette'] ?? 'auror';

        $tpl_vars = [
            'lang'      => $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3],
            'title'     => $title,
            'theme'     => $theme,
            'css_files' => [],
            'js_files'  => [],
        ];

        $tpl_vars['css_files'][] = ['path' => 'public/lib/base.css'];

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax']) {
            Html::requireJs('notifications_ajax');
        }

        $tpl_vars['css_files'][] = ['path' => 'public/lib/leaflet.css'];
        Html::requireJs('leaflet');

        $tpl_vars['css_files'][] = ['path' => 'public/lib/flatpickr.css'];
        // Include dark theme as base (may be cleaner look than light; colors overriden by GLPI's stylesheet)
        $tpl_vars['css_files'][] = ['path' => 'public/lib/flatpickr/themes/dark.css'];
        Html::requireJs('flatpickr');

        $tpl_vars['css_files'][] = ['path' => 'public/lib/photoswipe.css'];
        Html::requireJs('photoswipe');

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $tpl_vars['css_files'][] = ['path' => 'public/lib/codemirror.css'];
            Html::requireJs('codemirror');
        }

        //on demand JS.
        if ($sector != 'none' || $item != 'none' || $option != '') {
            $jslibs = [];
            if (isset($CFG_GLPI['javascript'][$sector])) {
                if (isset($CFG_GLPI['javascript'][$sector][$item])) {
                    if (isset($CFG_GLPI['javascript'][$sector][$item][$option])) {
                        $jslibs = $CFG_GLPI['javascript'][$sector][$item][$option];
                    } else {
                        $jslibs = $CFG_GLPI['javascript'][$sector][$item];
                    }
                } else {
                    $jslibs = $CFG_GLPI['javascript'][$sector];
                }
            }

            // include more js libs for dashboard case
            $jslibs = array_merge($jslibs, [
                'gridstack',
                'charts',
                'clipboard',
                'sortable'
            ]);

            if (in_array('planning', $jslibs)) {
                Html::requireJs('planning');
            }

            if (in_array('fullcalendar', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'public/lib/fullcalendar.css'];
                Html::requireJs('fullcalendar');
            }

            if (in_array('reservations', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/reservations.scss'];
                Html::requireJs('reservations');
            }

            if (in_array('kanban', $jslibs)) {
                $tpl_vars['js_modules'][] = ['path' => 'js/modules/Kanban/Kanban.js'];
                Html::requireJs('kanban');
            }

            if (in_array('rateit', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'public/lib/jquery.rateit.css'];
                Html::requireJs('rateit');
            }

            if (in_array('dashboard', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/dashboard.scss'];
                Html::requireJs('dashboard');
            }

            if (in_array('marketplace', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/marketplace.scss'];
                Html::requireJs('marketplace');
            }

            if (in_array('rack', $jslibs)) {
                Html::requireJs('rack');
            }

            if (in_array('gridstack', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'public/lib/gridstack.css'];
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/gridstack-grids.scss'];
                Html::requireJs('gridstack');
            }

            if (in_array('masonry', $jslibs)) {
                Html::requireJs('masonry');
            }

            if (in_array('sortable', $jslibs)) {
                Html::requireJs('sortable');
            }

            if (in_array('tinymce', $jslibs)) {
                Html::requireJs('tinymce');
            }

            if (in_array('clipboard', $jslibs)) {
                Html::requireJs('clipboard');
            }

            if (in_array('charts', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'public/lib/chartist.css'];
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/chartist.scss'];
                Html::requireJs('charts');
            }

            if (
                in_array('codemirror', $jslibs)
                && $_SESSION['glpi_use_mode'] !== Session::DEBUG_MODE // codemirror is already loaded when debug mode is active
            ) {
                $tpl_vars['css_files'][] = ['path' => 'public/lib/codemirror.css'];
                Html::requireJs('codemirror');
            }

            if (in_array('cable', $jslibs)) {
                Html::requireJs('cable');
            }
        }

        if (Session::getCurrentInterface() == "helpdesk") {
            $tpl_vars['css_files'][] = ['path' => 'public/lib/jquery.rateit.css'];
            Html::requireJs('rateit');
        }

       //file upload is required... almost everywhere.
        Html::requireJs('fileupload');

       // load fuzzy search everywhere
        Html::requireJs('fuzzy');

       // load glpi dailog everywhere
        Html::requireJs('glpi_dialog');

       // load log filters everywhere
        Html::requireJs('log_filters');

        if (isset($_SESSION['glpihighcontrast_css']) && $_SESSION['glpihighcontrast_css']) {
            $tpl_vars['high_contrast'] = true;
        }

       // Add specific css for plugins
        if (isset($PLUGIN_HOOKS[Hooks::ADD_CSS]) && count($PLUGIN_HOOKS[Hooks::ADD_CSS])) {
            foreach ($PLUGIN_HOOKS[Hooks::ADD_CSS] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }

                $plugin_web_dir  = Plugin::getWebDir($plugin, false);
                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $tpl_vars['css_files'][] = [
                        'path' => "$plugin_web_dir/$file",
                        'options' => [
                            'version' => $plugin_version,
                        ]
                    ];
                }
            }
        }
        $tpl_vars['css_files'][] = ['path' => 'css/palettes/' . $theme . '.scss'];

        $tpl_vars['js_files'][] = ['path' => 'public/lib/base.js'];
        $tpl_vars['js_files'][] = ['path' => 'js/webkit_fix.js'];
        $tpl_vars['js_files'][] = ['path' => 'js/common.js'];

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $tpl_vars['js_modules'][] = ['path' => 'js/modules/Debug/Debug.js'];
        }

       // Search
        $tpl_vars['js_modules'][] = ['path' => 'js/modules/Search/ResultsView.js'];
        $tpl_vars['js_modules'][] = ['path' => 'js/modules/Search/Table.js'];

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $tpl_vars['glpi_request_id'] = \Glpi\Debug\Profile::getCurrent()->getID();
        }

        TemplateRenderer::getInstance()->display('layout/parts/head.html.twig', $tpl_vars);

        self::glpi_flush();
    }


    /**
     * @since 0.90
     *
     * @return array
     **/
    public static function getMenuInfos()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $can_read_dashboard      = Session::haveRight('dashboard', READ);
        $default_asset_dashboard = defined('TU_USER') ? "" : Glpi\Dashboard\Grid::getDefaultDashboardForMenu('assets');
        $default_asset_helpdesk  = defined('TU_USER') ? "" : Glpi\Dashboard\Grid::getDefaultDashboardForMenu('helpdesk');

        $menu = [
            'assets' => [
                'title' => _n('Asset', 'Assets', Session::getPluralNumber()),
                'types' => array_merge([
                    'Computer', 'Monitor', 'Software',
                    'NetworkEquipment', 'Peripheral', 'Printer',
                    'CartridgeItem', 'ConsumableItem', 'Phone',
                    'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment', 'Unmanaged', 'Cable'
                ], $CFG_GLPI['devices_in_menu']),
                'icon'    => 'ti ti-package'
            ],
        ];

        if ($can_read_dashboard && strlen($default_asset_dashboard) > 0) {
            $menu['assets']['default_dashboard'] = '/front/dashboard_assets.php';
        }

        $menu += [
            'helpdesk' => [
                'title' => __('Assistance'),
                'types' => [
                    'Ticket', 'Problem', 'Change',
                    'Planning', 'Stat', 'TicketRecurrent', 'RecurrentChange'
                ],
                'icon'    => 'ti ti-headset'
            ]
        ];

        if ($can_read_dashboard && strlen($default_asset_helpdesk) > 0) {
            $menu['helpdesk']['default_dashboard'] = '/front/dashboard_helpdesk.php';
        }

        $menu += [
            'management' => [
                'title' => __('Management'),
                'types' => [
                    'SoftwareLicense', 'Budget', 'Supplier', 'Contact', 'Contract',
                    'Document', 'Line', 'Certificate', 'Datacenter', 'Cluster', 'Domain',
                    'Appliance', 'Database'
                ],
                'icon'  => 'ti ti-wallet'
            ],
            'tools' => [
                'title' => __('Tools'),
                'types' => [
                    'Project', 'Reminder', 'RSSFeed', 'KnowbaseItem',
                    'ReservationItem', 'Report', 'MigrationCleaner',
                    'SavedSearch', 'Impact'
                ],
                'icon' => 'ti ti-briefcase'
            ],
            'plugins' => [
                'title' => _n('Plugin', 'Plugins', Session::getPluralNumber()),
                'types' => [],
                'icon'  => 'ti ti-puzzle'
            ],
            'admin' => [
                'title' => __('Administration'),
                'types' => [
                    'User', 'Group', 'Entity', 'Rule',
                    'Profile', 'QueuedNotification', 'Glpi\\Event', 'Glpi\Inventory\Inventory'
                ],
                'icon'  => 'ti ti-shield-check'
            ],
            'config' => [
                'title' => __('Setup'),
                'types' => [
                    'CommonDropdown', 'CommonDevice', 'Notification',
                    'SLM', 'Config', 'FieldUnicity', 'CronTask', 'Auth',
                    'MailCollector', 'Link', 'Plugin'
                ],
                'icon'  => 'ti ti-settings'
            ],

            // special items
            'preference' => [
                'title'   => __('My settings'),
                'default' => '/front/preference.php',
                'icon'    => 'fas fa-user-cog',
                'display' => false,
            ],
        ];

        return $menu;
    }

    /**
     * Generate menu array in $_SESSION['glpimenu'] and return the array
     *
     * @since  9.2
     *
     * @param  boolean $force do we need to force regeneration of $_SESSION['glpimenu']
     * @return array          the menu array
     */
    public static function generateMenuSession($force = false)
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;
        $menu = [];

        if (
            $force
            || !isset($_SESSION['glpimenu'])
            || !is_array($_SESSION['glpimenu'])
            || (count($_SESSION['glpimenu']) == 0)
        ) {
            $menu = self::getMenuInfos();

           // Permit to plugins to add entry to others sector !
            if (isset($PLUGIN_HOOKS["menu_toadd"]) && count($PLUGIN_HOOKS["menu_toadd"])) {
                foreach ($PLUGIN_HOOKS["menu_toadd"] as $plugin => $items) {
                    if (!Plugin::isPluginActive($plugin)) {
                        continue;
                    }
                    if (count($items)) {
                        foreach ($items as $key => $val) {
                            if (is_array($val)) {
                                foreach ($val as $k => $object) {
                                    $menu[$key]['types'][] = $object;
                                    if (empty($menu[$key]['icon']) && method_exists($object, 'getIcon')) {
                                        $menu[$key]['icon']    = $object::getIcon();
                                    }
                                }
                            } else {
                                if (isset($menu[$key])) {
                                    $menu[$key]['types'][] = $val;
                                }
                            }
                        }
                    }
                }
               // Move Setup menu ('config') to the last position in $menu (always last menu),
               // in case some plugin inserted a new top level menu
                $categories = array_keys($menu);
                $menu += array_splice($menu, array_search('config', $categories, true), 1);
            }

            foreach ($menu as $category => $entries) {
                if (isset($entries['types']) && count($entries['types'])) {
                    foreach ($entries['types'] as $type) {
                        $data = $type::getMenuContent();
                        if ($data) {
                          // Multi menu entries management
                            if (isset($data['is_multi_entries']) && $data['is_multi_entries']) {
                                if (!isset($menu[$category]['content'])) {
                                    $menu[$category]['content'] = [];
                                }
                                $menu[$category]['content'] += $data;
                            } else {
                                $menu[$category]['content'][strtolower($type)] = $data;
                            }
                            if (!isset($menu[$category]['title']) && isset($data['title'])) {
                                $menu[$category]['title'] = $data['title'];
                            }
                            if (!isset($menu[$category]['default']) && isset($data['default'])) {
                                   $menu[$category]['default'] = $data['default'];
                            }
                        }
                    }
                }
               // Define default link :
                if (! isset($menu[$category]['default']) && isset($menu[$category]['content']) && count($menu[$category]['content'])) {
                    foreach ($menu[$category]['content'] as $val) {
                        if (isset($val['page'])) {
                             $menu[$category]['default'] = $val['page'];
                             break;
                        }
                    }
                }
            }

            $allassets = [
                'Computer',
                'Monitor',
                'Peripheral',
                'NetworkEquipment',
                'Phone',
                'Printer'
            ];

            foreach ($allassets as $type) {
                if (isset($menu['assets']['content'][strtolower($type)])) {
                    $menu['assets']['content']['allassets']['title']            = __('Global');
                    $menu['assets']['content']['allassets']['shortcut']         = '';
                    $menu['assets']['content']['allassets']['page']             = '/front/allassets.php';
                    $menu['assets']['content']['allassets']['icon']             = 'fas fa-list';
                    $menu['assets']['content']['allassets']['links']['search']  = '/front/allassets.php';
                    break;
                }
            }

            $_SESSION['glpimenu'] = $menu;
           // echo 'menu load';
        } else {
            $menu = $_SESSION['glpimenu'];
        }

        return $menu;
    }

    /**
     * Generate menu array for simplified interface (helpdesk)
     *
     * @since  10
     *
     * @return array
     */
    public static function generateHelpMenu()
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;

        $menu = [
            'home' => [
                'default' => '/front/helpdesk.public.php',
                'title'   => __('Home'),
                'icon'    => 'fas fa-home',
            ],
        ];

        if (Session::haveRight("ticket", CREATE)) {
            $menu['create_ticket'] = [
                'default' => '/front/helpdesk.public.php?create_ticket=1',
                'title'   => __('Create a ticket'),
                'icon'    => 'ti ti-plus',
            ];
        }

        if (
            Session::haveRight("ticket", READ)
            || Session::haveRight("ticket", Ticket::READMY)
        ) {
            $menu['tickets'] = [
                'default' => '/front/ticket.php',
                'title'   => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                'icon'    => Ticket::getIcon(),
                'content' => [
                    'ticket' => [
                        'links' => [
                            'search'    => Ticket::getSearchURL(),
                            'lists'     => '',
                        ]
                    ]
                ]
            ];

            if (Session::haveRight("ticket", CREATE)) {
                $menu['tickets']['content']['ticket']['links']['add'] = '/front/helpdesk.public.php?create_ticket=1';
            }
        }

        if (Session::haveRightsOr("reservation", [READ, ReservationItem::RESERVEANITEM])) {
            $menu['reservation'] = [
                'default' => '/front/reservationitem.php',
                'title'   => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                'icon'    => ReservationItem::getIcon(),
            ];
        }

        if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
            $menu['faq'] = [
                'default' => '/front/helpdesk.faq.php',
                'title'   => __('FAQ'),
                'icon'    => KnowbaseItem::getIcon(),
            ];
        }

        if (
            isset($PLUGIN_HOOKS["helpdesk_menu_entry"])
            && count($PLUGIN_HOOKS["helpdesk_menu_entry"])
        ) {
            $menu['plugins'] = [
                'title' => __("Plugins"),
                'icon'  => Plugin::getIcon(),
            ];

            foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if ($active) {
                    $infos = Plugin::getInfo($plugin);
                    $link = "";
                    if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin])) {
                        $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin];

                        // Ensure menu entries have all a starting `/`
                        if (!str_starts_with($link, '/')) {
                            $link = '/' . $link;
                        }

                        // Prefix with plugin path if plugin path is missing
                        $plugin_dir = Plugin::getWebDir($plugin, false);
                        if (!str_starts_with($link, '/' . $plugin_dir)) {
                            $link = '/' . $plugin_dir . $link;
                        }
                    }
                    $infos['page'] = $link;
                    $infos['title'] = $infos['name'];
                    if (isset($PLUGIN_HOOKS["helpdesk_menu_entry_icon"][$plugin])) {
                        $infos['icon'] = $PLUGIN_HOOKS["helpdesk_menu_entry_icon"][$plugin];
                    }
                    $menu['plugins']['content'][$plugin] = $infos;
                }
            }
        }

        return $menu;
    }

    /**
     * Returns menu sector corresponding to given itemtype.
     *
     * @param string $itemtype
     *
     * @return string|null
     */
    public static function getMenuSectorForItemtype(string $itemtype): ?string
    {
        $menu = self::getMenuInfos();
        foreach ($menu as $sector => $params) {
            if (array_key_exists('types', $params) && in_array($itemtype, $params['types'])) {
                return $sector;
            }
        }
        return null;
    }


    /**
     * Print a nice HTML head for every page
     *
     * @param string $title   title of the page
     * @param string $url     not used anymore
     * @param string $sector  sector in which the page displayed is
     * @param string $item    item corresponding to the page displayed
     * @param string $option  option corresponding to the page displayed
     * @param bool   $add_id  add current item id to the title ?
     */
    public static function header(
        $title,
        $url = '',
        $sector = "none",
        $item = "none",
        $option = "",
        bool $add_id = true
    ) {
        /**
         * @var array $CFG_GLPI
         * @var bool $HEADER_LOADED
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $HEADER_LOADED, $DB;

        // If in modal : display popHeader
        if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
            return self::popHeader($title, $url, false, $sector, $item, $option);
        }
        // Print a nice HTML-head for every page
        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;
        // Force lower case for sector and item
        $sector = strtolower($sector);
        $item   = strtolower($item);

        \Glpi\Debug\Profiler::getInstance()->start('Html::includeHeader');
        self::includeHeader($title, $sector, $item, $option, $add_id);
        \Glpi\Debug\Profiler::getInstance()->stop('Html::includeHeader');

        $tmp_active_item = explode("/", $item);
        $active_item     = array_pop($tmp_active_item);
        $menu            = self::generateMenuSession($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
        $menu_active     = $menu[$sector]['content'][$active_item]['title'] ?? "";

        $menu = Plugin::doHookFunction("redefine_menus", $menu);

        $tpl_vars = [
            'menu'        => $menu,
            'sector'      => $sector,
            'item'        => $item,
            'option'      => $option,
            'menu_active' => $menu_active,
        ];
        $tpl_vars += self::getPageHeaderTplVars();

        TemplateRenderer::getInstance()->display('layout/parts/page_header.html.twig', $tpl_vars);

        if (
            $DB->isSlave()
            && !$DB->first_connection
        ) {
            echo "<div id='dbslave-float'>";
            echo "<a href='#see_debug'>" . __('SQL replica: read only') . "</a>";
            echo "</div>";
        }

        // call static function callcron() every 5min
        CronTask::callCron();
    }


    /**
     * Print footer for every page
     *
     * @param $keepDB boolean, closeDBConnections if false (false by default)
     **/
    public static function footer($keepDB = false)
    {
        /**
         * @var array $CFG_GLPI
         * @var bool $FOOTER_LOADED
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $FOOTER_LOADED, $PLUGIN_HOOKS;

       // If in modal : display popFooter
        if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
            return self::popFooter();
        }

       // Print foot for every page
        if ($FOOTER_LOADED) {
            return;
        }
        $FOOTER_LOADED = true;

        echo self::getCoreVariablesForJavascript(true);

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax'] && !Session::isImpersonateActive()) {
            $options = [
                'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ? $CFG_GLPI['notifications_ajax_check_interval'] : 5) * 1000,
                'sound'     => $CFG_GLPI['notifications_ajax_sound'] ? $CFG_GLPI['notifications_ajax_sound'] : false,
                'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
                'user_id'   => Session::getLoginUserID()
            ];
            $js = "$(function() {
            notifications_ajax = new GLPINotificationsAjax(" . json_encode($options) . ");
            notifications_ajax.start();
         });";
            echo Html::scriptBlock($js);
        }

        $tpl_vars = [
            'js_files' => [],
        ];

       // On demand scripts
        foreach ($_SESSION['glpi_js_toload'] ?? [] as $scripts) {
            if (!is_array($scripts)) {
                $scripts = [$scripts];
            }
            foreach ($scripts as $script) {
                $tpl_vars['js_files'][] = ['path' => $script];
            }
        }
        $_SESSION['glpi_js_toload'] = [];

       // Locales for js libraries
        if (isset($_SESSION['glpilanguage'])) {
           // select2
            $filename = sprintf(
                'public/lib/select2/js/i18n/%s.js',
                $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]
            );
            if (file_exists(GLPI_ROOT . '/' . $filename)) {
                $tpl_vars['js_files'][] = ['path' => $filename];
            }
        }

        $tpl_vars['js_files'][] = ['path' => 'js/misc.js'];

        if (isset($PLUGIN_HOOKS['add_javascript']) && count($PLUGIN_HOOKS['add_javascript'])) {
            foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_root_dir = Plugin::getPhpDir($plugin, true);
                $plugin_web_dir  = Plugin::getWebDir($plugin, false);
                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (file_exists($plugin_root_dir . "/{$file}")) {
                        $tpl_vars['js_files'][] = [
                            'path' => $plugin_web_dir . "/{$file}",
                            'options' => [
                                'version' => $plugin_version,
                            ]
                        ];
                    } else {
                        trigger_error("{$file} file not found from plugin $plugin!", E_USER_WARNING);
                    }
                }
            }
        }
        if (isset($PLUGIN_HOOKS['add_javascript_module']) && count($PLUGIN_HOOKS['add_javascript_module'])) {
            foreach ($PLUGIN_HOOKS["add_javascript_module"] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_root_dir = Plugin::getPhpDir($plugin, true);
                $plugin_web_dir  = Plugin::getWebDir($plugin, false);
                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (file_exists($plugin_root_dir . "/{$file}")) {
                        $tpl_vars['js_modules'][] = [
                            'path' => $plugin_web_dir . "/{$file}",
                            'options' => [
                                'version' => $plugin_version,
                            ]
                        ];
                    } else {
                        trigger_error("{$file} file not found from plugin $plugin!", E_USER_WARNING);
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('layout/parts/page_footer.html.twig', $tpl_vars);

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE && !str_starts_with($_SERVER['PHP_SELF'], $CFG_GLPI['root_doc'] . '/install/')) {
            \Glpi\Debug\Profiler::getInstance()->stopAll();
            (new Glpi\Debug\Toolbar())->show();
        }

        if (!$keepDB && function_exists('closeDBConnections')) {
            closeDBConnections();
        }
    }


    /**
     * Display Ajax Footer for debug
     **/
    public static function ajaxFooter()
    {
        // Not currently used. Old debug stuff is now in the new debug bar.
        // FIXME: Deprecate this in GLPI 10.1.
    }


    /**
     * Print a simple HTML head with links
     *
     * @param string $title  title of the page
     * @param array  $links  links to display
     **/
    public static function simpleHeader($title, $links = [])
    {
        /** @var bool $HEADER_LOADED */
        global $HEADER_LOADED;

       // Print a nice HTML-head for help page
        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;

        self::includeHeader($title);

       // force layout to horizontal if not connected
        if (!Session::getLoginUserID()) {
            $_SESSION['glpipage_layout'] = "horizontal";
        }

       // construct menu from passed links
        $menu = [];
        foreach ($links as $label => $url) {
            $menu[] = [
                'title'   => $label,
                'default' => $url
            ];
        }

        TemplateRenderer::getInstance()->display(
            'layout/parts/page_header.html.twig',
            [
                'menu' => $menu,
            ] + self::getPageHeaderTplVars()
        );

        CronTask::callCron();
    }


    /**
     * Print a nice HTML head for help page
     *
     * @param string $title  title of the page
     * @param string $sector  sector in which the page displayed is
     * @param string $item    item corresponding to the page displayed
     * @param string $option  option corresponding to the page displayed
     * @param bool   $add_id  add current item id to the title ?
     */
    public static function helpHeader(
        $title,
        string $sector = "self-service",
        string $item = "none",
        string $option = "",
        bool $add_id = true
    ) {
        /**
         * @var array $CFG_GLPI
         * @var bool $HEADER_LOADED
         */
        global $CFG_GLPI, $HEADER_LOADED;

        // Print a nice HTML-head for help page
        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;

        self::includeHeader($title, $sector, $item, $option, $add_id);

        $menu = self::generateHelpMenu();
        $menu = Plugin::doHookFunction("redefine_menus", $menu);

        $tmp_active_item = explode("/", $item);
        $active_item     = array_pop($tmp_active_item);
        $menu_active     = $menu[$sector]['content'][$active_item]['title'] ?? "";
        $tpl_vars = [
            'menu'        => $menu,
            'sector'      => $sector,
            'item'        => $item,
            'option'      => $option,
            'menu_active' => $menu_active,
        ];
        $tpl_vars += self::getPageHeaderTplVars();

        TemplateRenderer::getInstance()->display('layout/parts/page_header.html.twig', $tpl_vars);

        // call static function callcron() every 5min
        CronTask::callCron();
    }

    /**
     * Returns template variables that can be used for page header in any context.
     *
     * @return array
     */
    private static function getPageHeaderTplVars(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $founded_new_version = null;
        if (!empty($CFG_GLPI['founded_new_version'] ?? null)) {
            $current_version     = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
            $founded_new_version = version_compare($current_version, $CFG_GLPI['founded_new_version'], '<')
            ? $CFG_GLPI['founded_new_version']
            : null;
        }

        $user = Session::getLoginUserID() !== false ? User::getById(Session::getLoginUserID()) : null;

        $platform = "";
        if (!defined('TU_USER')) {
            $parser = new UserAgentParser();
            $ua = $parser->parse();
            $platform = $ua->platform();
        }

        $help_url_key = Session::getCurrentInterface() === 'central'
            ? 'central_doc_url'
            : 'helpdesk_doc_url';
        $help_url = !empty($CFG_GLPI[$help_url_key])
            ? $CFG_GLPI[$help_url_key]
            : 'http://glpi-project.org/documentation';

        return [
            'is_debug_active'       => $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE,
            'is_impersonate_active' => Session::isImpersonateActive(),
            'founded_new_version'   => $founded_new_version,
            'user'                  => $user instanceof User ? $user : null,
            'platform'              => $platform,
            'help_url'              => URL::sanitizeURL($help_url),
        ];
    }


    /**
     * Print footer for help page
     **/
    public static function helpFooter()
    {
        if (!isCommandLine()) {
            self::footer();
        };
    }


    /**
     * Print a nice HTML head with no controls
     *
     * @param string $title  title of the page
     * @param string $url    not used anymore
     **/
    public static function nullHeader($title, $url = '')
    {
        /** @var bool $HEADER_LOADED */
        global $HEADER_LOADED;

        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;
       // Print a nice HTML-head with no controls

       // Detect root_doc in case of error
        Config::detectRootDoc();

       // Send UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");

       // Send extra expires header if configured
        self::header_nocache();

        if (isCommandLine()) {
            return true;
        }

        self::includeHeader($title);

        TemplateRenderer::getInstance()->display('layout/parts/page_header_empty.html.twig');
    }


    /**
     * Print footer for null page
     **/
    public static function nullFooter()
    {
        if (!isCommandLine()) {
            self::footer();
        };
    }


    /**
     * Print a nice HTML head for modal window (nothing to display)
     *
     * @param string  $title    title of the page
     * @param string  $url      not used anymore
     * @param boolean $iframed  indicate if page loaded in iframe - css target
     * @param string  $sector    sector in which the page displayed is (default 'none')
     * @param string  $item      item corresponding to the page displayed (default 'none')
     * @param string  $option    option corresponding to the page displayed (default '')
     **/
    public static function popHeader(
        $title,
        $url = '',
        $in_modal = false,
        $sector = "none",
        $item = "none",
        $option = ""
    ) {
        /** @var bool $HEADER_LOADED */
        global $HEADER_LOADED;

       // Print a nice HTML-head for every page
        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;

        self::includeHeader($title, $sector, $item, $option); // Body
        echo "<body class='" . ($in_modal ? "in_modal" : "") . "'>";
        self::displayMessageAfterRedirect();
        echo "<div id='page'>"; // Force legacy styles for now
    }


    /**
     * Print a nice HTML head for iframed windows
     * This header remove any security for iframe (NO SAMEORIGIN, etc)
     * And should be used ONLY for iframing windows in other applications.
     * It should NOT be used for GLPI internal iframing.
     *
     * @since 10.0.7
     *
     * @param string  $sector    sector in which the page displayed is (default 'none')
     * @param string  $item      item corresponding to the page displayed (default 'none')
     * @param string  $option    option corresponding to the page displayed (default '')
     * @return void
     */
    public static function zeroSecurityIframedHeader(
        string $sector = "none",
        string $item = "none",
        string $option = ""
    ): void {
        /** @var bool $HEADER_LOADED */
        global $HEADER_LOADED;

        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;

        self::includeHeader('', $sector, $item, $option, true, true);
        echo "<body class='iframed'>";
        self::displayMessageAfterRedirect();
        echo "<div id='page'>";
    }


    /**
     * Print footer for a modal window
     **/
    public static function popFooter()
    {
        /** @var bool $FOOTER_LOADED */
        global $FOOTER_LOADED;

        if ($FOOTER_LOADED) {
            return;
        }
        $FOOTER_LOADED = true;

       // Print foot
        self::loadJavascript();
        echo "</body></html>";
    }


    /**
     * Flushes the system write buffers of PHP and whatever backend PHP is using (CGI, a web server, etc).
     * This attempts to push current output all the way to the browser with a few caveats.
     * @see https://www.sitepoint.com/php-streaming-output-buffering-explained/
     **/
    public static function glpi_flush()
    {

        if (
            function_exists("ob_flush")
            && (ob_get_length() !== false)
        ) {
            ob_flush();
        }

        flush();
    }


    /**
     * Set page not to use the cache
     **/
    public static function header_nocache()
    {

        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
    }



    /**
     * show arrow for massives actions : opening
     *
     * @param string  $formname
     * @param boolean $fixed     used tab_cadre_fixe in both tables
     * @param boolean $ontop     display on top of the list
     * @param boolean $onright   display on right of the list
     *
     * @deprecated 0.84
     **/
    public static function openArrowMassives($formname, $fixed = false, $ontop = false, $onright = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        Toolbox::deprecated('openArrowMassives() method is deprecated');

        if ($fixed) {
            echo "<table width='950px'>";
        } else {
            echo "<table width='80%'>";
        }

        $arrow = "fas fa-level-down-alt";
        if (!$ontop) {
            $arrow = "fas fa-level-up-alt";
        }

        echo "<tr>";
        if (!$onright) {
            echo "<td><i class='$arrow fa-flip-horizontal fa-lg mx-2'></i></td>";
        } else {
            echo "<td class='left' width='80%'></td>";
        }
        echo "<td class='center' style='white-space:nowrap;'>";
        echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\"
             href='#'>" . __('Check all') . "</a></td>";
        echo "<td>/</td>";
        echo "<td class='center' style='white-space:nowrap;'>";
        echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\"
             href='#'>" . __('Uncheck all') . "</a></td>";

        if ($onright) {
            echo "<td><i class='$arrow fa-lg mx-2'></i>";
        } else {
            echo "<td class='left' width='80%'>";
        }
    }


    /**
     * show arrow for massives actions : closing
     *
     * @param $actions array of action : $name -> $label
     * @param $confirm array of confirmation string (optional)
     *
     * @deprecated 0.84
     **/
    public static function closeArrowMassives($actions, $confirm = [])
    {

        Toolbox::deprecated('closeArrowMassives() method is deprecated');

        if (count($actions)) {
            foreach ($actions as $name => $label) {
                if (!empty($name)) {
                    echo "<input type='submit' name='$name' ";
                    if (is_array($confirm) && isset($confirm[$name])) {
                        echo self::addConfirmationOnAction($confirm[$name]);
                    }
                    echo "value=\"" . addslashes($label) . "\" class='btn btn-primary'>&nbsp;";
                }
            }
        }
        echo "</td></tr>";
        echo "</table>";
    }


    /**
     * Get "check All as" checkbox
     *
     * @since 0.84
     *
     * @param $container_id  string html of the container of checkboxes link to this check all checkbox
     * @param $rand          string rand value to use (default is auto generated)(default '')
     *
     * @return string
     **/
    public static function getCheckAllAsCheckbox($container_id, $rand = '')
    {

        if (empty($rand)) {
            $rand = mt_rand();
        }

        $out  = "<input title='" . __s('Check all as') . "' type='checkbox' class='form-check-input massive_action_checkbox'
                      title='" . __s('Check all as') . "'
                      name='_checkall_$rand' id='checkall_$rand'
                      onclick= \"if ( checkAsCheckboxes(this, '$container_id', '.massive_action_checkbox')) {return true;}\">";

       // permit to shift select checkboxes
        $out .= Html::scriptBlock("\$(function() {\$('#$container_id input[type=\"checkbox\"]').shiftSelectable();});");

        return $out;
    }


    /**
     * Get the jquery criterion for massive checkbox update
     * We can filter checkboxes by a container or by a tag. We can also select checkboxes that have
     * a given tag and that are contained inside a container
     *
     * @since 0.85
     *
     * @param array $options  array of parameters:
     *    - tag_for_massive tag of the checkboxes to update
     *    - container_id    if of the container of the checkboxes
     *
     * @return string  the javascript code for jquery criterion or empty string if it is not a
     *         massive update checkbox
     **/
    public static function getCriterionForMassiveCheckboxes(array $options)
    {

        $params                    = [];
        $params['tag_for_massive'] = '';
        $params['container_id']    = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        if (
            !empty($params['tag_for_massive'])
            || !empty($params['container_id'])
        ) {
           // Filtering on the container !
            if (!empty($params['container_id'])) {
                $criterion = '#' . $params['container_id'] . ' ';
            } else {
                $criterion = '';
            }

           // We only want the checkbox input
            $criterion .= 'input[type="checkbox"]';

           // Only the given massive tag !
            if (!empty($params['tag_for_massive'])) {
                $criterion .= '[data-glpicore-cb-massive-tags~="' . $params['tag_for_massive'] . '"]';
            }

           // Only enabled checkbox
            $criterion .= ':enabled';

            return addslashes($criterion);
        }
        return '';
    }


    /**
     * Get a checkbox.
     *
     * @since 0.85
     *
     * @param array $options  array of parameters:
     *    - title         its title
     *    - name          its name
     *    - id            its id
     *    - value         the value to set when checked
     *    - readonly      can we edit it ?
     *    - massive_tags  the tag to set for massive checkbox update
     *    - checked       is it checked or not ?
     *    - zero_on_empty do we send 0 on submit when it is not checked ?
     *    - specific_tags HTML5 tags to add
     *    - criterion     the criterion for massive checkbox
     *
     * @return string  the HTML code for the checkbox
     **/
    public static function getCheckbox(array $options)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $params                    = [];
        $params['title']           = '';
        $params['name']            = '';
        $params['rand']            = mt_rand();
        $params['id']              = "check_" . $params['rand'];
        $params['value']           = 1;
        $params['readonly']        = false;
        $params['massive_tags']    = '';
        $params['checked']         = false;
        $params['zero_on_empty']   = true;
        $params['specific_tags']   = [];
        $params['criterion']       = [];
        $params['class']           = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $out = "";

        if ($params['zero_on_empty']) {
            $out .= '<input type="hidden" name="' . $params['name'] . '" value="0" />';
        }

        $out .= "<input type='checkbox' class='form-check-input " . $params['class'] . "' title=\"" . $params['title'] . "\" ";
        if (isset($params['onclick'])) {
            $params['onclick'] = htmlspecialchars($params['onclick'], ENT_QUOTES);
            $out .= " onclick='{$params['onclick']}'";
        }

        foreach (['id', 'name', 'title', 'value'] as $field) {
            if (!empty($params[$field])) {
                $out .= " $field='" . $params[$field] . "'";
            }
        }

        $criterion = self::getCriterionForMassiveCheckboxes($params['criterion']);
        if (!empty($criterion)) {
            $out .= " onClick='massiveUpdateCheckbox(\"$criterion\", this)'";
        }

        if (!empty($params['massive_tags'])) {
            $params['specific_tags']['data-glpicore-cb-massive-tags'] = $params['massive_tags'];
        }

        if (!empty($params['specific_tags'])) {
            foreach ($params['specific_tags'] as $tag => $values) {
                if (is_array($values)) {
                    $values = implode(' ', $values);
                }
                $out .= " $tag='$values'";
            }
        }

        if ($params['readonly']) {
            $out .= " disabled='disabled'";
        }

        if ($params['checked']) {
            $out .= " checked";
        }

        $out .= ">";

        if (!empty($criterion)) {
            $out .= Html::scriptBlock("\$(function() {\$('$criterion').shiftSelectable();});");
        }

        return $out;
    }


    /**
     * @brief display a checkbox that $_POST 0 or 1 depending on if it is checked or not.
     * @see Html::getCheckbox()
     *
     * @since 0.85
     *
     * @param $options   array
     *
     * @return void
     **/
    public static function showCheckbox(array $options = [])
    {
        echo self::getCheckbox($options);
    }


    /**
     * Get the massive action checkbox
     *
     * @since 0.84
     *
     * @param string  $itemtype  Massive action itemtype
     * @param integer $id        ID of the item
     * @param array   $options
     *
     * @return string
     **/
    public static function getMassiveActionCheckBox($itemtype, $id, array $options = [])
    {

        $options['checked']       = (isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]));
        if (!isset($options['specific_tags']['data-glpicore-ma-tags'])) {
            $options['specific_tags']['data-glpicore-ma-tags'] = 'common';
        }

        if (empty($options['name'])) {
            // encode quotes and brackets to prevent maformed name attribute
            $id = htmlspecialchars($id, ENT_QUOTES);
            $id = str_replace(['[', ']'], ['&amp;#91;', '&amp;#93;'], $id);
            $options['name'] = "item[$itemtype][" . $id . "]";
        }

        $options['class']         = 'massive_action_checkbox';
        $options['zero_on_empty'] = false;

        return self::getCheckbox($options);
    }


    /**
     * Show the massive action checkbox
     *
     * @since 0.84
     *
     * @param string  $itemtype  Massive action itemtype
     * @param integer $id        ID of the item
     * @param array   $options
     *
     * @return void
     **/
    public static function showMassiveActionCheckBox($itemtype, $id, array $options = [])
    {
        echo Html::getMassiveActionCheckBox($itemtype, $id, $options);
    }


    /**
     * Display open form for massive action
     *
     * @since 0.84
     *
     * @param string $name  given name/id to the form
     *
     * @return void
     **/
    public static function openMassiveActionsForm($name = '')
    {
        echo Html::getOpenMassiveActionsForm($name);
    }


    /**
     * Get open form for massive action string
     *
     * @since 0.84
     *
     * @param string $name  given name/id to the form
     *
     * @return string
     **/
    public static function getOpenMassiveActionsForm($name = '')
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (empty($name)) {
            $name = 'massaction_' . mt_rand();
        }
        return  "<form name='$name' id='$name' method='post'
               action='" . $CFG_GLPI["root_doc"] . "/front/massiveaction.php'
               enctype='multipart/form-data'>";
    }


    /**
     * Display massive actions
     *
     * @since 0.84 (before Search::displayMassiveActions)
     * @since 0.85 only 1 parameter (in 0.84 $itemtype required)
     *
     * @todo replace 'hidden' by data-glpicore-ma-tags ?
     *
     * @param $options   array    of parameters
     * must contains :
     *    - container       : DOM ID of the container of the item checkboxes (since version 0.85)
     * may contains :
     *    - num_displayed   : integer number of displayed items. Permit to check suhosin limit.
     *                        (default -1 not to check)
     *    - ontop           : boolean true if displayed on top (default true)
     *    - forcecreate     : boolean force creation of modal window (default = false).
     *            Modal is automatically created when displayed the ontop item.
     *            If only a bottom one is displayed use it
     *    - check_itemtype   : string alternate itemtype to check right if different from main itemtype
     *                         (default empty)
     *    - check_items_id   : integer ID of the alternate item used to check right / optional
     *                         (default empty)
     *    - is_deleted       : boolean is massive actions for deleted items ?
     *    - extraparams      : string extra URL parameters to pass to massive actions (default empty)
     *                         if ([extraparams]['hidden'] is set : add hidden fields to post)
     *    - specific_actions : array of specific actions (do not use standard one)
     *    - add_actions      : array of actions to add (do not use standard one)
     *    - confirm          : string of confirm message before massive action
     *    - item             : CommonDBTM object that has to be passed to the actions
     *    - tag_to_send      : the tag of the elements to send to the ajax window (default: common)
     *    - display          : display or return the generated html (default true)
     *
     * @return bool|string     the html if display parameter is false, or true
     **/
    public static function showMassiveActions($options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

        $p['ontop']             = true;
        $p['num_displayed']     = -1;
        $p['forcecreate']       = false;
        $p['check_itemtype']    = '';
        $p['check_items_id']    = '';
        $p['is_deleted']        = false;
        $p['extraparams']       = [];
        $p['width']             = 800;
        $p['height']            = 400;
        $p['specific_actions']  = [];
        $p['add_actions']       = [];
        $p['confirm']           = '';
        $p['rand']              = '';
        $p['container']         = '';
        $p['display_arrow']     = true;
        $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
        $p['item']              = false;
        $p['tag_to_send']       = 'common';
        $p['display']           = true;

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        $url = $CFG_GLPI['root_doc'] . "/ajax/massiveaction.php";
        if ($p['container']) {
            $p['extraparams']['container'] = $p['container'];
        }
        if ($p['is_deleted']) {
            $p['extraparams']['is_deleted'] = 1;
        }
        if (!empty($p['check_itemtype'])) {
            $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
        }
        if (!empty($p['check_items_id'])) {
            $p['extraparams']['check_items_id'] = $p['check_items_id'];
        }
        if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
            $p['extraparams']['specific_actions'] = $p['specific_actions'];
        }
        if (is_array($p['add_actions']) && count($p['add_actions'])) {
            $p['extraparams']['add_actions'] = $p['add_actions'];
        }
        if ($p['item'] instanceof CommonDBTM) {
            $p['extraparams']['item_itemtype'] = $p['item']->getType();
            $p['extraparams']['item_items_id'] = $p['item']->getID();
        }

       // Manage modal window
        if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
            $p['extraparams']['hidden']['_is_modal'] = 1;
        }

        $identifier = md5($url . serialize($p['extraparams']) . $p['rand']);
        $max        = Toolbox::get_max_input_vars();
        $out = '';

        if (
            ($p['num_displayed'] >= 0)
            && ($max > 0)
            && ($max < ($p['num_displayed'] + 10))
        ) {
            if (
                !$p['ontop']
                || (isset($p['forcecreate']) && $p['forcecreate'])
            ) {
                $out .= "<span class='b'>";
                $out .= __('Selection too large, massive action disabled.') . "</span>";
                if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                    $out .= __('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
                }
            }
        } else {
           // Create Modal window on top
            if (
                $p['ontop']
                || (isset($p['forcecreate']) && $p['forcecreate'])
            ) {
                if (!empty($p['tag_to_send'])) {
                    $js_modal_fields  = "var items = $('";
                    if (!empty($p['container'])) {
                        $js_modal_fields .= '#' . $p['container'] . ' ';
                    }
                    $js_modal_fields .= "[data-glpicore-ma-tags~=" . $p['tag_to_send'] . "]').each(function( index ) {
                  fields[$(this).attr('name')] = $(this).val();
                  if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {
                     fields[$(this).attr('name')] = 0;
                  }
               });";
                } else {
                    $js_modal_fields = "";
                }

                $out .= Ajax::createModalWindow(
                    'modal_massiveaction_window' . $identifier,
                    $url,
                    [
                        'title'           => $p['title'],
                        'extraparams'     => $p['extraparams'],
                        'width'           => $p['width'],
                        'height'          => $p['height'],
                        'js_modal_fields' => $js_modal_fields,
                        'display'         => false,
                        'modal_class'     => "modal-xl",
                    ]
                );
            }
            $out .= "<a title='" . __('Massive actions') . "'
                     data-bs-toggle='tooltip' data-bs-placement='top'
                     class='btn btn-sm btn-outline-secondary me-1' ";
            if (is_array($p['confirm'] || strlen($p['confirm']))) {
                $out .= self::addConfirmationOnAction($p['confirm'], "modal_massiveaction_window$identifier.show();");
            } else {
                $out .= "onclick='modal_massiveaction_window$identifier.show();'";
            }
            $out .= " href='#modal_massaction_content$identifier' title=\"" . htmlentities($p['title'], ENT_QUOTES, 'UTF-8') . "\">";
            if ($p['display_arrow']) {
                $out .= "<i class='ti ti-corner-left-" . ($p['ontop'] ? 'down' : 'up') . " mt-1' style='margin-left: -2px;'></i>";
            }
            $out .= "<span>" . $p['title'] . "</span>";
            $out .= "</a>";

            if (
                !$p['ontop']
                || (isset($p['forcecreate']) && $p['forcecreate'])
            ) {
               // Clean selection
                $_SESSION['glpimassiveactionselected'] = [];
            }
        }

        if ($p['display']) {
            echo $out;
            return true;
        } else {
            return $out;
        }
    }


    /**
     * Display Date form with calendar
     *
     * @since 0.84
     *
     * @param string $name     name of the element
     * @param array  $options  array of possible options:
     *      - value        : default value to display (default '')
     *      - maybeempty   : may be empty ? (true by default)
     *      - canedit      :  could not modify element (true by default)
     *      - min          :  minimum allowed date (default '')
     *      - max          : maximum allowed date (default '')
     *      - showyear     : should we set/diplay the year? (true by default)
     *      - display      : boolean display of return string (default true)
     *      - calendar_btn : boolean display calendar icon (default true)
     *      - clear_btn    : boolean display clear icon (default true)
     *      - range        : boolean set the datepicket in range mode
     *      - rand         : specific rand value (default generated one)
     *      - yearrange    : set a year range to show in drop-down (default '')
     *      - required     : required field (will add required attribute)
     *      - placeholder  : text to display when input is empty
     *      - on_change    : function to execute when date selection changed
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function showDateField($name, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'value'        => '',
            'defaultDate'  => '',
            'maybeempty'   => true,
            'canedit'      => true,
            'min'          => '',
            'max'          => '',
            'showyear'     => false,
            'display'      => true,
            'range'        => false,
            'rand'         => mt_rand(),
            'calendar_btn' => true,
            'clear_btn'    => true,
            'yearrange'    => '',
            'multiple'     => false,
            'size'         => 10,
            'required'     => false,
            'placeholder'  => '',
            'on_change'    => '',
        ];

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        $required = $p['required'] == true
         ? " required='required'"
         : "";
        $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";

        $calendar_btn = $p['calendar_btn']
         ? "<a class='input-button' data-toggle>
               <i class='input-group-text far fa-calendar-alt fa-lg pointer'></i>
            </a>"
         : "";
        $clear_btn = $p['clear_btn'] && $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='" . __s('Clear') . "'>
               <i class='input-group-text fas fa-times-circle pointer'></i>
            </a>"
         : "";

        $mode = $p['range']
         ? "mode: 'range',"
         : "";

        $output = <<<HTML
      <div class="input-group flex-grow-1 flatpickr d-flex align-items-center" id="showdate{$p['rand']}">
         <input type="text" name="{$name}" size="{$p['size']}"
                {$required} {$disabled} data-input placeholder="{$p['placeholder']}" class="form-control rounded-start ps-2">
         $calendar_btn
         $clear_btn
      </div>
HTML;

        $date_format = Toolbox::getDateFormat('js');

        $min_attr = !empty($p['min'])
         ? "minDate: '{$p['min']}',"
         : "";
        $max_attr = !empty($p['max'])
         ? "maxDate: '{$p['max']}',"
         : "";
        $multiple_attr = $p['multiple']
         ? "mode: 'multiple',"
         : "";

        $value = is_array($p['value'])
         ? json_encode($p['value'])
         : "'{$p['value']}'";

        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $js = <<<JS
      $(function() {
         $("#showdate{$p['rand']}").flatpickr({
            defaultDate: {$value},
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: '{$date_format}',
            dateFormat: 'Y-m-d',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons
            weekNumbers: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            {$min_attr}
            {$max_attr}
            {$multiple_attr}
            {$mode}
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            },
            allowInput: true,
            onClose(dates, currentdatestring, picker){
               picker.setDate(picker.altInput.value, true, picker.config.altFormat)
            },
            plugins: [
               CustomFlatpickrButtons()
            ]
         });
      });
JS;

        $output .= Html::scriptBlock($js);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }


    /**
     * Display Color field
     *
     * @since 0.85
     *
     * @param string $name     name of the element
     * @param array  $options  array  of possible options:
     *   - value      : default value to display (default '')
     *   - display    : boolean display or get string (default true)
     *   - rand       : specific random value (default generated one)
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function showColorField($name, $options = [])
    {
        $p['value']      = '';
        $p['rand']       = mt_rand();
        $p['display']    = true;
        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }
        $field_id = Html::cleanId("color_" . $name . $p['rand']);
        $output   = "<input type='color' id='$field_id' name='$name' value='" . $p['value'] . "'>";

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }


    /**
     * Display DateTime form with calendar
     *
     * @since 0.84
     *
     * @param string $name     name of the element
     * @param array  $options  array  of possible options:
     *   - value      : default value to display (default '')
     *   - timestep   : step for time in minute (-1 use default config) (default -1)
     *   - maybeempty : may be empty ? (true by default)
     *   - canedit    : could not modify element (true by default)
     *   - mindate    : minimum allowed date (default '')
     *   - maxdate    : maximum allowed date (default '')
     *   - showyear   : should we set/diplay the year? (true by default)
     *   - display    : boolean display or get string (default true)
     *   - rand       : specific random value (default generated one)
     *   - required   : required field (will add required attribute)
     *   - on_change    : function to execute when date selection changed
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function showDateTimeField($name, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'value'      => '',
            'maybeempty' => true,
            'canedit'    => true,
            'mindate'    => '',
            'maxdate'    => '',
            'mintime'    => '',
            'maxtime'    => '',
            'timestep'   => -1,
            'showyear'   => true,
            'display'    => true,
            'rand'       => mt_rand(),
            'required'   => false,
            'on_change'  => '',
        ];

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        if ($p['timestep'] < 0) {
            $p['timestep'] = $CFG_GLPI['time_step'];
        }

        $date_value = '';
        $hour_value = '';
        if (!empty($p['value'])) {
            list($date_value, $hour_value) = explode(' ', $p['value']);
        }

        if (!empty($p['mintime'])) {
           // Check time in interval
            if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
                $hour_value = $p['mintime'];
            }
        }

        if (!empty($p['maxtime'])) {
           // Check time in interval
            if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
                $hour_value = $p['maxtime'];
            }
        }

       // reconstruct value to be valid
        if (!empty($date_value)) {
            $p['value'] = $date_value . ' ' . $hour_value;
        }

        $required = $p['required'] == true
         ? " required='required'"
         : "";
        $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
        $clear    = $p['maybeempty'] && $p['canedit']
         ? "<i class='input-group-text fas fa-times-circle fa-lg pointer' data-clear role='button' title='" . __s('Clear') . "'></i>"
         : "";

        $output = <<<HTML
         <div class="input-group flex-grow-1 flatpickr" id="showdate{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input class="form-control rounded-start ps-2">
            <i class="input-group-text far fa-calendar-alt fa-lg pointer" data-toggle="" role="button"></i>
            $clear
         </div>
HTML;

        $date_format = Toolbox::getDateFormat('js') . " H:i:S";

        $min_attr = !empty($p['min'])
         ? "minDate: '{$p['min']}',"
         : "";
        $max_attr = !empty($p['max'])
         ? "maxDate: '{$p['max']}',"
         : "";

        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $js = <<<JS
      $(function() {
         $("#showdate{$p['rand']}").flatpickr({
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: "{$date_format}",
            dateFormat: 'Y-m-d H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            enableSeconds: true,
            weekNumbers: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            minuteIncrement: "{$p['timestep']}",
            {$min_attr}
            {$max_attr}
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            },
            allowInput: true,
            onClose(dates, currentdatestring, picker){
               picker.setDate(picker.altInput.value, true, picker.config.altFormat)
            },
            plugins: [
               CustomFlatpickrButtons()
            ]
         });
      });
JS;
        $output .= Html::scriptBlock($js);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }

    /**
     * Display TimeField form
     *
     * @param string $name
     * @param array  $options
     *   - value      : default value to display (default '')
     *   - timestep   : step for time in minute (-1 use default config) (default -1)
     *   - maybeempty : may be empty ? (true by default)
     *   - canedit    : could not modify element (true by default)
     *   - mintime    : minimum allowed time (default '')
     *   - maxtime    : maximum allowed time (default '')
     *   - display    : boolean display or get string (default true)
     *   - rand       : specific random value (default generated one)
     *   - required   : required field (will add required attribute)
     *   - on_change  : function to execute when date selection changed
     * @return void
     */
    public static function showTimeField($name, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'value'      => '',
            'maybeempty' => true,
            'canedit'    => true,
            'mintime'    => '',
            'maxtime'    => '',
            'timestep'   => -1,
            'display'    => true,
            'rand'       => mt_rand(),
            'required'   => false,
            'on_change'  => '',
        ];

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        if ($p['timestep'] < 0) {
            $p['timestep'] = $CFG_GLPI['time_step'];
        }

        $hour_value = '';
        if (!empty($p['value'])) {
            $hour_value = $p['value'];
        }

        if (!empty($p['mintime'])) {
           // Check time in interval
            if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
                $hour_value = $p['mintime'];
            }
        }
        if (!empty($p['maxtime'])) {
           // Check time in interval
            if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
                $hour_value = $p['maxtime'];
            }
        }
       // reconstruct value to be valid
        if (!empty($hour_value)) {
            $p['value'] = $hour_value;
        }

        $required = $p['required'] == true
         ? " required='required'"
         : "";
        $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
        $clear    = $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='" . __s('Clear') . "'>
               <i class='input-group-text fas fa-times-circle pointer'></i>
            </a>"
         : "";

        $output = <<<HTML
         <div class="input-group flex-grow-1 flatpickr" id="showtime{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input class="form-control rounded-start ps-2">
            <a class="input-button" data-toggle>
               <i class="input-group-text far fa-clock fa-lg pointer"></i>
            </a>
            $clear
         </div>
HTML;
        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $js = <<<JS
      $(function() {
         $("#showtime{$p['rand']}").flatpickr({
            dateFormat: 'H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            noCalendar: true, // only time picker
            enableSeconds: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            minuteIncrement: "{$p['timestep']}",
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            }
         });
      });
JS;
        $output .= Html::scriptBlock($js);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }

    /**
     * Show generic date search
     *
     * @param string $element  name of the html element
     * @param string $value    default value
     * @param $options   array of possible options:
     *      - with_time display with time selection ? (default false)
     *      - with_future display with future date selection ? (default false)
     *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function showGenericDateTimeSearch($element, $value = '', $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p['with_time']          = false;
        $p['with_future']        = false;
        $p['with_days']          = true;
        $p['with_specific_date'] = true;
        $p['display']            = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        $rand   = mt_rand();
        $output = '';
       // Validate value
        if (
            ($value != 'NOW')
            && ($value != 'TODAY')
            && !preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)
            && !strstr($value, 'HOUR')
            && !strstr($value, 'MINUTE')
            && !strstr($value, 'DAY')
            && !strstr($value, 'WEEK')
            && !strstr($value, 'MONTH')
            && !strstr($value, 'YEAR')
        ) {
            $value = "";
        }

        if (empty($value)) {
            $value = 'NOW';
        }
        $specific_value = date("Y-m-d H:i:s");

        if (preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)) {
            $specific_value = $value;
            $value          = 0;
        }
        $output    .= "<table width='100%'><tr><td width='50%'>";

        $dates      = Html::getGenericDateTimeSearchItems($p);

        $output    .= Dropdown::showFromArray(
            "_select_$element",
            $dates,
            ['value'   => $value,
                'display' => false,
                'rand'    => $rand
            ]
        );
        $field_id   = Html::cleanId("dropdown__select_$element$rand");

        $output    .= "</td><td width='50%'>";
        $contentid  = Html::cleanId("displaygenericdate$element$rand");
        $output    .= "<span id='$contentid'></span>";

        $params     = ['value'         => '__VALUE__',
            'name'          => $element,
            'withtime'      => $p['with_time'],
            'specificvalue' => $specific_value
        ];

        $output    .= Ajax::updateItemOnSelectEvent(
            $field_id,
            $contentid,
            $CFG_GLPI["root_doc"] . "/ajax/genericdate.php",
            $params,
            false
        );
        $params['value']  = $value;
        $output    .= Ajax::updateItem(
            $contentid,
            $CFG_GLPI["root_doc"] . "/ajax/genericdate.php",
            $params,
            '',
            false
        );
        $output    .= "</td></tr></table>";

        if ($p['display']) {
            echo $output;
            return $rand;
        }
        return $output;
    }


    /**
     * Get items to display for showGenericDateTimeSearch
     *
     * @since 0.83
     *
     * @param array $options  array of possible options:
     *      - with_time display with time selection ? (default false)
     *      - with_future display with future date selection ? (default false)
     *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
     *
     * @return array of posible values
     * @see self::showGenericDateTimeSearch()
     **/
    public static function getGenericDateTimeSearchItems($options)
    {

        $params['with_time']          = false;
        $params['with_future']        = false;
        $params['with_days']          = true;
        $params['with_specific_date'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $dates = [];
        if ($params['with_time']) {
            $dates['NOW'] = __('Now');
            if ($params['with_days']) {
                $dates['TODAY'] = __('Today');
            }
        } else {
            $dates['NOW'] = __('Today');
        }

        if ($params['with_specific_date']) {
            $dates[0] = __('Specify a date');
        }

        if ($params['with_time']) {
            for ($i = 1; $i <= 24; $i++) {
                $dates['-' . $i . 'HOUR'] = sprintf(_n('- %d hour', '- %d hours', $i), $i);
            }

            for ($i = 1; $i <= 15; $i++) {
                $dates['-' . $i . 'MINUTE'] = sprintf(_n('- %d minute', '- %d minutes', $i), $i);
            }
        }

        for ($i = 1; $i <= 7; $i++) {
            $dates['-' . $i . 'DAY'] = sprintf(_n('- %d day', '- %d days', $i), $i);
        }

        if ($params['with_days']) {
            $dates['LASTSUNDAY']    = __('last Sunday');
            $dates['LASTMONDAY']    = __('last Monday');
            $dates['LASTTUESDAY']   = __('last Tuesday');
            $dates['LASTWEDNESDAY'] = __('last Wednesday');
            $dates['LASTTHURSDAY']  = __('last Thursday');
            $dates['LASTFRIDAY']    = __('last Friday');
            $dates['LASTSATURDAY']  = __('last Saturday');
        }

        for ($i = 1; $i <= 10; $i++) {
            $dates['-' . $i . 'WEEK'] = sprintf(_n('- %d week', '- %d weeks', $i), $i);
        }

        if ($params['with_days']) {
            $dates['BEGINMONTH']  = __('Beginning of the month');
        }

        for ($i = 1; $i <= 12; $i++) {
            $dates['-' . $i . 'MONTH'] = sprintf(_n('- %d month', '- %d months', $i), $i);
        }

        if ($params['with_days']) {
            $dates['BEGINYEAR']  = __('Beginning of the year');
        }

        for ($i = 1; $i <= 10; $i++) {
            $dates['-' . $i . 'YEAR'] = sprintf(_n('- %d year', '- %d years', $i), $i);
        }

        if ($params['with_future']) {
            if ($params['with_time']) {
                for ($i = 1; $i <= 24; $i++) {
                    $dates[$i . 'HOUR'] = sprintf(_n('+ %d hour', '+ %d hours', $i), $i);
                }
            }

            for ($i = 1; $i <= 7; $i++) {
                $dates[$i . 'DAY'] = sprintf(_n('+ %d day', '+ %d days', $i), $i);
            }

            for ($i = 1; $i <= 10; $i++) {
                $dates[$i . 'WEEK'] = sprintf(_n('+ %d week', '+ %d weeks', $i), $i);
            }

            if ($params['with_days']) {
                $dates['ENDMONTH']  = __('End of the month');
            }

            for ($i = 1; $i <= 12; $i++) {
                $dates[$i . 'MONTH'] = sprintf(_n('+ %d month', '+ %d months', $i), $i);
            }

            if ($params['with_days']) {
                $dates['ENDYEAR']  = __('End of the year');
            }

            for ($i = 1; $i <= 10; $i++) {
                $dates[$i . 'YEAR'] = sprintf(_n('+ %d year', '+ %d years', $i), $i);
            }
        }
        return $dates;
    }


    /**
     * Compute date / datetime value resulting of showGenericDateTimeSearch
     *
     * @since 0.83
     *
     * @param string         $val           date / datetime value passed
     * @param boolean        $force_day     force computation in days
     * @param integer|string $specifictime  set specific timestamp
     *
     * @return string  computed date / datetime value
     * @see self::showGenericDateTimeSearch()
     **/
    public static function computeGenericDateTimeSearch($val, $force_day = false, $specifictime = '')
    {

        if (empty($specifictime)) {
            $specifictime = strtotime($_SESSION["glpi_currenttime"]);
        }

        $format_use = "Y-m-d H:i:s";
        if ($force_day) {
            $format_use = "Y-m-d";
        }

       // Parsing relative date
        switch ($val) {
            case 'NOW':
                return date($format_use, $specifictime);

            case 'TODAY':
                return date("Y-m-d", $specifictime);
        }

       // Search on begin /end of month / year
        if (strstr($val, 'BEGIN') || strstr($val, 'END')) {
            $hour   = 0;
            $minute = 0;
            $second = 0;
            $month  = date("n", $specifictime);
            $day    = 1;
            $year   = date("Y", $specifictime);

            switch ($val) {
                case "BEGINYEAR":
                    $month = 1;
                    break;

                case "BEGINMONTH":
                    break;

                case "ENDYEAR":
                    $month = 12;
                    $day = 31;
                    break;

                case "ENDMONTH":
                    $day = date("t", $specifictime);
                    break;
            }

            return date($format_use, mktime($hour, $minute, $second, $month, $day, $year));
        }

       // Search on Last monday, sunday...
        if (strstr($val, 'LAST')) {
            $lastday = str_replace("LAST", "LAST ", $val);
            $hour   = 0;
            $minute = 0;
            $second = 0;
            $month  = date("n", strtotime($lastday));
            $day    = date("j", strtotime($lastday));
            $year   = date("Y", strtotime($lastday));

            return date($format_use, mktime($hour, $minute, $second, $month, $day, $year));
        }

       // Search on +- x days, hours...
        if (preg_match("/^(-?)(\d+)(\w+)$/", $val, $matches)) {
            if (in_array($matches[3], ['YEAR', 'MONTH', 'WEEK', 'DAY', 'HOUR', 'MINUTE'])) {
                $nb = intval($matches[2]);
                if ($matches[1] == '-') {
                    $nb = -$nb;
                }
               // Use it to have a clean delay computation (MONTH / YEAR have not always the same duration)
                $hour   = date("H", $specifictime);
                $minute = date("i", $specifictime);
                $second = 0;
                $month  = date("n", $specifictime);
                $day    = date("j", $specifictime);
                $year   = date("Y", $specifictime);

                switch ($matches[3]) {
                    case "YEAR":
                        $year += $nb;
                        break;

                    case "MONTH":
                        $month += $nb;
                        break;

                    case "WEEK":
                        $day += 7 * $nb;
                        break;

                    case "DAY":
                        $day += $nb;
                        break;

                    case "MINUTE":
                        $format_use = "Y-m-d H:i:s";
                        $minute    += $nb;
                        break;

                    case "HOUR":
                        $format_use = "Y-m-d H:i:s";
                        $hour      += $nb;
                        break;
                }
                return date($format_use, mktime($hour, $minute, $second, $month, $day, $year));
            }
        }
        return $val;
    }

    /**
     * Display or return a list of dates in a vertical way
     *
     * @since 9.2
     *
     * @param array $options  array of possible options:
     *      - title, do we need to append an H2 title tag
     *      - dates, an array containing a collection of theses keys:
     *         * timestamp
     *         * class, supported: passed, checked, now
     *         * label
     *      - display, boolean to precise if we need to display (true) or return (false) the html
     *      - add_now, boolean to precise if we need to add to dates array, an entry for now time
     *        (with now class)
     *
     * @return void|string
     *    void if option display=true
     *    string if option display=false (HTML code)
     *
     * @see self::showGenericDateTimeSearch()
     **/
    public static function showDatesTimelineGraph($options = [])
    {
        $default_options = [
            'title'   => '',
            'dates'   => [],
            'display' => true,
            'add_now' => true
        ];
        $options = array_merge($default_options, $options);

       //append now date if needed
        if ($options['add_now']) {
            $now = time();
            $options['dates'][$now . "_now"] = [
                'timestamp' => $now,
                'label' => __('Now'),
                'class' => 'now'
            ];
        }

        ksort($options['dates']);

       // format dates
        foreach ($options['dates'] as &$data) {
            $data['date'] = date("Y-m-d H:i:s", $data['timestamp']);
        }

       // get Html
        $out = TemplateRenderer::getInstance()->render(
            'components/dates_timeline.html.twig',
            [
                'title' => $options['title'],
                'dates' => $options['dates'],
            ]
        );

        if ($options['display']) {
            echo $out;
        } else {
            return $out;
        }
    }


    /**
     * Show a tooltip on an item
     *
     * @param $content   string   data to put in the tooltip
     * @param $options   array    of possible options:
     *   - applyto : string / id of the item to apply tooltip (default empty).
     *                  If not set display an icon
     *   - title : string / title to display (default empty)
     *   - contentid : string / id for the content html container (default auto generated) (used for ajax)
     *   - link : string / link to put on displayed image if contentid is empty
     *   - linkid : string / html id to put to the link link (used for ajax)
     *   - linktarget : string / target for the link
     *   - popup : string / popup action : link not needed to use it
     *   - img : string / url of a specific img to use
     *   - display : boolean / display the item : false return the datas
     *   - autoclose : boolean / autoclose the item : default true (false permit to scroll)
     *   - url: ?string If defined, load tooltip using an AJAX request on the supplied URL
     *
     * @return void|string
     *    void if option display=true
     *    string if option display=false (HTML code)
     **/
    public static function showToolTip($content, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $param = [
            'applyto'       => '',
            'title'         => '',
            'contentid'     => '',
            'link'          => '',
            'linkid'        => '',
            'linktarget'    => '_top',
            'awesome-class' => 'fa-info',
            'popup'         => '',
            'ajax'          => '',
            'display'       => true,
            'autoclose'     => true,
            'onclick'       => false,
            'link_class'    => '',
            'url'           => null,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

       // No empty content to have a clean display
        if (empty($content)) {
            $content = "&nbsp;";
        }
        $rand = mt_rand();
        $out  = '';

       // Force link for popup
        if (!empty($param['popup'])) {
            $param['link'] = '#';
        }

        if (empty($param['applyto'])) {
            if (!empty($param['link'])) {
                $out .= "<a id='" . (!empty($param['linkid']) ? $param['linkid'] : "tooltiplink$rand") . "'
                        class='dropdown_tooltip {$param['link_class']}'";

                if (!empty($param['linktarget'])) {
                    $out .= " target='" . $param['linktarget'] . "' ";
                }
                $out .= " href='" . $param['link'] . "'";

                if (!empty($param['popup'])) {
                    $out .= " data-bs-toggle='modal' data-bs-target='#tooltippopup$rand' ";
                }
                $out .= '>';
            }
            if (isset($param['img'])) {
               //for compatibility. Use fontawesome instead.
                $out .= "<img id='tooltip$rand' src='" . $param['img'] . "'>";
            } else {
                $out .= "<span id='tooltip$rand' class='fas {$param['awesome-class']} fa-fw'></span>";
            }

            if (!empty($param['link'])) {
                $out .= "</a>";
            }

            $param['applyto'] = (!empty($param['link']) && !empty($param['linkid'])) ? $param['linkid'] : "tooltip$rand";
        }

        if (empty($param['contentid'])) {
            $param['contentid'] = "content" . $param['applyto'];
        }

        $out .= "<div id='" . $param['contentid'] . "' class='tooltip-invisible'>$content</div>";
        if (!empty($param['popup'])) {
            $out .= Ajax::createIframeModalWindow(
                'tooltippopup' . $rand,
                $param['popup'],
                ['display' => false,
                    'width'   => 600,
                    'height'  => 300
                ]
            );
        }
        $js = "$(function(){";
        $js .= Html::jsGetElementbyID($param['applyto']) . ".qtip({
         position: { viewport: $(window) },
         content: {";
        if (!is_null($param['url'])) {
            $js .= "
                ajax: {
                    url: '" . $CFG_GLPI['root_doc'] . $param['url'] . "',
                    type: 'GET',
                    data: {},
                },
            ";
        }

        $js .= "text: " .  Html::jsGetElementbyID($param['contentid']);
        if (!$param['autoclose']) {
            $js .= ", title: {text: ' ',button: true}";
        }
        $js .= "}, style: { classes: 'qtip-shadow qtip-bootstrap'}, hide: {
                  fixed: true,
                  delay: 200,
                  leave: false,
                  when: { event: 'unfocus' }
                }";
        if ($param['onclick']) {
            $js .= ",show: 'click', hide: false,";
        } else if (!$param['autoclose']) {
            $js .= ",show: {
                        solo: true, // ...and hide all other tooltips...
                },";
        }
        $js .= "});";
        $js .= "});";
        $out .= Html::scriptBlock($js);

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }


    /**
     * Show div with auto completion
     *
     * @param CommonDBTM $item    item object used for create dropdown
     * @param string     $field   field to search for autocompletion
     * @param array      $options array of possible options:
     *    - name    : string / name of the select (default is field parameter)
     *    - value   : integer / preselected value (default value of the item object)
     *    - size    : integer / size of the text field
     *    - entity  : integer / restrict to a defined entity (default entity of the object if define)
     *                set to -1 not to take into account
     *    - user    : integer / restrict to a defined user (default -1 : no restriction)
     *    - option  : string / options to add to text field
     *    - display : boolean / if false get string
     *    - type    : string / html5 field type (number, date, text, ...) defaults to 'text'
     *    - required: boolean / whether the field is required
     *    - rand    : integer / pre-exsting random value
     *    - attrs   : array of attributes to add (['name' => 'value']
     *
     * @return void|string
     **/
    public static function autocompletionTextField(CommonDBTM $item, $field, $options = [])
    {
        Toolbox::deprecated('Autocompletion feature has been removed.');

        $params['name']   = $field;
        $params['value']  = '';

        if (array_key_exists($field, $item->fields)) {
            $params['value'] = $item->fields[$field];
        }
        $params['option'] = '';
        $params['type']   = 'text';
        $params['required']  = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $rand = (isset($params['rand']) ? $params['rand'] : mt_rand());
        $name    = "field_" . $params['name'] . $rand;

        $output = "<input " . $params['option'] . " type='text' id='text$name' class='form-control' name='" . $params['name'] . "'
                value=\"" . self::cleanInputText($params['value']) . "\"" .
                ($params['required'] ? ' required="required"' : '') . ">";

        if (!isset($options['display']) || $options['display']) {
            echo $output;
        } else {
            return $output;
        }
    }


    /**
     * Init the Editor System to a textarea
     *
     * @param string  $id            id of the html textarea to use
     * @param string  $rand          rand of the html textarea to use (if empty no image paste system)(default '')
     * @param boolean $display       display or get js script (true by default)
     * @param boolean $readonly      editor will be readonly or not
     * @param boolean $enable_images enable image pasting in rich text
     *
     * @return void|string
     *    integer if param display=true
     *    string if param display=false (HTML code)
     **/
    public static function initEditorSystem($id, $rand = '', $display = true, $readonly = false, $enable_images = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

       // load tinymce lib
        Html::requireJs('tinymce');

        $language = $_SESSION['glpilanguage'];
        if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
            $language = $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2];
            if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
                $language = "en_GB";
            }
        }
        $language_url = $CFG_GLPI['root_doc'] . '/public/lib/tinymce-i18n/langs6/' . $language . '.js';

       // Apply all GLPI styles to editor content
        $content_css = preg_replace('/^.*href="([^"]+)".*$/', '$1', self::scss(('css/palettes/' . $_SESSION['glpipalette'] ?? 'auror') . '.scss', ['force_no_version' => true]))
         . ',' . preg_replace('/^.*href="([^"]+)".*$/', '$1', self::css('public/lib/base.css', ['force_no_version' => true]));

        $cache_suffix = '?v=' . FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $readonlyjs   = $readonly ? 'true' : 'false';

        $invalid_elements = 'applet,canvas,embed,form,object';
        if (!$enable_images) {
            $invalid_elements .= ',img';
        }
        if (!GLPI_ALLOW_IFRAME_IN_RICH_TEXT) {
            $invalid_elements .= ',iframe';
        }

        $plugins = [
            'autoresize',
            'code',
            'directionality',
            'fullscreen',
            'link',
            'lists',
            'quickbars',
            'searchreplace',
            'table',
        ];
        if ($enable_images) {
            $plugins[] = 'image';
            $plugins[] = 'glpi_upload_doc';
        }
        if ($DB->use_utf8mb4) {
            $plugins[] = 'emoticons';
        }
        $pluginsjs = json_encode($plugins);

        $language_opts = '';
        if ($language !== 'en_GB') {
            $language_opts = json_encode([
                'language' => $language,
                'language_url' => $language_url
            ]);
        }

        $mandatory_field_msg = json_encode(__('The %s field is mandatory'));

        // init tinymce
        $js = <<<JS
         $(function() {
            var is_dark = $('html').css('--is-dark').trim() === 'true';
            var richtext_layout = "{$_SESSION['glpirichtext_layout']}";

            // init editor
            tinyMCE.init(Object.assign({
               license_key: 'gpl',

               link_default_target: '_blank',
               branding: false,
               selector: '#{$id}',
               text_patterns: false,

               plugins: {$pluginsjs},

               // Appearance
               skin_url: is_dark
                  ? CFG_GLPI['root_doc']+'/public/lib/tinymce/skins/ui/oxide-dark'
                  : CFG_GLPI['root_doc']+'/public/lib/tinymce/skins/ui/oxide',
               body_class: 'rich_text_container',
               content_css: '{$content_css}',
               highlight_on_focus: false,

               min_height: 150,
               resize: true,

               // disable path indicator in bottom bar
               elementpath: false,

               // inline toolbar configuration
               menubar: false,
               toolbar: richtext_layout == 'classic'
                  ? 'styles | bold italic | forecolor backcolor | bullist numlist outdent indent | emoticons table link image | code fullscreen'
                  : false,
               quickbars_insert_toolbar: richtext_layout == 'inline'
                  ? 'emoticons quicktable quickimage quicklink | bullist numlist | outdent indent '
                  : false,
               quickbars_selection_toolbar: richtext_layout == 'inline'
                  ? 'bold italic | styles | forecolor backcolor '
                  : false,
               contextmenu: richtext_layout == 'classic'
                  ? false
                  : 'copy paste | emoticons table image link | undo redo | code fullscreen',

               // Content settings
               entity_encoding: 'raw',
               invalid_elements: '{$invalid_elements}',
               readonly: {$readonlyjs},
               relative_urls: false,
               remove_script_host: false,

               // Misc options
               browser_spellcheck: true,
               cache_suffix: '{$cache_suffix}',

               // Security options
               // Iframes are disabled by default. We assume that administrator that enable it are aware of the potential security issues.
               sandbox_iframes: false,

               setup: function(editor) {
                  // "required" state handling
                  if ($('#$id').attr('required') == 'required') {
                     $('#$id').removeAttr('required'); // Necessary to bypass browser validation

                     editor.on('submit', function (e) {
                        if ($('#$id').val() == '') {
                           const field = $('#$id').closest('.form-field').find('label').text().replace('*', '').trim();
                           alert({$mandatory_field_msg}.replace('%s', field));
                           e.preventDefault();

                           // Prevent other events to run
                           // Needed to not break single submit forms
                           e.stopPropagation();
                        }
                     });
                     editor.on('keyup', function (e) {
                        editor.save();
                        if ($('#$id').val() == '') {
                           $(editor.container).addClass('required');
                        } else {
                           $(editor.container).removeClass('required');
                        }
                     });
                     editor.on('init', function (e) {
                        if (strip_tags($('#$id').val()) == '') {
                           $(editor.container).addClass('required');
                        }
                     });
                     editor.on('paste', function (e) {
                        // Remove required on paste event
                        // This is only needed when pasting with right click (context menu)
                        // Pasting with Ctrl+V is already handled by keyup event above
                        $(editor.container).removeClass('required');
                     });
                  }

                  editor.on('Change', function (e) {
                     // Nothing fancy here. Since this is only used for tracking unsaved changes,
                     // we want to keep the logic in common.js with the other form input events.
                     onTinyMCEChange(e);
                  });
                  // ctrl + enter submit the parent form
                  editor.addShortcut('ctrl+13', 'submit', function() {
                     editor.save();
                     submitparentForm($('#$id'));
                  });
               }
            }, {$language_opts}));
         });
JS;

        if ($display) {
            echo  Html::scriptBlock($js);
        } else {
            return  Html::scriptBlock($js);
        }
    }

    /**
     * Activate autocompletion for user templates in rich text editor.
     *
     * @param string $editor_id
     *
     * @return void
     *
     * @since 10.0.0
     */
    public static function activateUserTemplateAutocompletion(string $selector, array $values): void
    {
        $values = json_encode($values);

        echo Html::scriptBlock(<<<JAVASCRIPT
         $(
            function() {
               var editor_id = $('{$selector}').attr('id');
               var user_templates_autocomplete = new GLPI.RichText.ContentTemplatesParameters(
                  tinymce.get(editor_id),
                  {$values}
               );
               user_templates_autocomplete.register();
            }
         );
JAVASCRIPT
        );
    }

    /**
     * Insert an html link to the twig template variables documentation page
     *
     * @param string $preset_traget Preset of parameters for which to show documentation (key)
     * @param string|null $link_id  Useful if you need to interract with the link through client side code
     */
    public static function addTemplateDocumentationLink(
        string $preset_target,
        ?string $link_id = null
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $url = "/front/contenttemplates/documentation.php?preset=$preset_target";
        $link =  $CFG_GLPI['root_doc'] . $url;
        $params = [
            'target' => '_blank',
            'style'  => 'margin-top:6px; display: block'
        ];

        if (!is_null($link_id)) {
            $params['id'] = $link_id;
        }

        $text = __('Available variables') . ' <i class="fas fa-question-circle"></i>';

        echo Html::link($text, $link, $params);
    }

    /**
     * Insert an html link to the twig template variables documentation page and
     * move it before the given textarea.
     * Useful if you don't have access to the form where you want to put this link at
     *
     * @param string $selector JQuery selector to find the target textarea
     * @param string $preset_traget   Preset of parameters for which to show documentation (key)
     */
    public static function addTemplateDocumentationLinkJS(
        string $selector,
        string $preset_target
    ) {
        $link_id = "template_documentation_" . mt_rand();
        self::addTemplateDocumentationLink($preset_target, $link_id);

       // Move link before the given textarea
        echo Html::scriptBlock(<<<JAVASCRIPT
         $(
            function() {
               $('{$selector}').parent().append($('#{$link_id}'));
            }
         );
JAVASCRIPT
        );
    }


    /**
     * Print Ajax pager for list in tab panel
     *
     * @param string  $title              displayed above
     * @param integer $start              from witch item we start
     * @param integer $numrows            total items
     * @param string  $additional_info    Additional information to display (default '')
     * @param boolean $display            display if true, return the pager if false
     * @param string  $additional_params  Additional parameters to pass to tab reload request (default '')
     *
     * @return void|string
     **/
    public static function printAjaxPager($title, $start, $numrows, $additional_info = '', $display = true, $additional_params = '')
    {
        $list_limit = $_SESSION['glpilist_limit'];
       // Forward is the next step forward
        $forward = $start + $list_limit;

       // This is the end, my friend
        $end = $numrows - $list_limit;

       // Human readable count starts here
        $current_start = $start + 1;

       // And the human is viewing from start to end
        $current_end = $current_start + $list_limit - 1;
        if ($current_end > $numrows) {
            $current_end = $numrows;
        }
       // Empty case
        if ($current_end == 0) {
            $current_start = 0;
        }
       // Backward browsing
        if ($current_start - $list_limit <= 0) {
            $back = 0;
        } else {
            $back = $start - $list_limit;
        }

        if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
            $additional_params = '&' . $additional_params;
        }

        $out = '';
       // Print it
        $out .= "<div><table class='tab_cadre_pager'>";
        if (!empty($title)) {
            $out .= "<tr><th colspan='6'>$title</th></tr>";
        }
        $out .= "<tr>\n";

       // Back and fast backward button
        if (!$start == 0) {
            $out .= "<th class='left'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=0$additional_params\");'>
                     <i class='fa fa-step-backward' title=\"" . __s('Start') . "\"></i></a></th>";
            $out .= "<th class='left'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$back$additional_params\");'>
                     <i class='fa fa-chevron-left' title=\"" . __s('Previous') . "\"></i></a></th>";
        }

        $out .= "<td width='50%' class='tab_bg_2'>";
        $out .= self::printPagerForm('', false, $additional_params);
        $out .= "</td>";
        if (!empty($additional_info)) {
            $out .= "<td class='tab_bg_2'>";
            $out .= $additional_info;
            $out .= "</td>";
        }
       // Print the "where am I?"
        $out .= "<td width='50%' class='tab_bg_2 b'>";
       //TRANS: %1$d, %2$d, %3$d are page numbers
        $out .= sprintf(__('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
        $out .= "</td>\n";

       // Forward and fast forward button
        if ($forward < $numrows) {
            $out .= "<th class='right'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$forward$additional_params\");'>
                     <i class='fa fa-chevron-right' title=\"" . __s('Next') . "\"></i></a></th>";
            $out .= "<th class='right'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$end$additional_params\");'>
                     <i class='fa fa-step-forward' title=\"" . __s('End') . "\"></i></a></th>";
        }

       // End pager
        $out .= "</tr></table></div>";

        if ($display) {
            echo $out;
            return;
        }

        return $out;
    }


    /**
     * Clean Printing of and array in a table
     * ONLY FOR DEBUG
     *
     * @param array   $tab       the array to display
     * @param integer $pad       Pad used
     * @param boolean $jsexpand  Expand using JS ?
     *
     * @return void
     **/
    public static function printCleanArray($tab, $pad = 0, $jsexpand = false)
    {

        if (count($tab)) {
            echo "<table class='array-debug table table-striped'>";
           // For debug / no gettext
            echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";

            foreach ($tab as $key => $val) {
                $key = Sanitizer::encodeHtmlSpecialChars($key);
                echo "<tr><td>";
                echo $key;
                echo "</td><td>";
                $is_array = is_array($val);
                $rand     = mt_rand();
                if ($jsexpand && $is_array) {
                    echo "<a href=\"javascript:showHideDiv('content$key$rand','','','')\">";
                    echo "=></a>";
                } else {
                    echo "=>";
                }
                echo "</td><td>";

                if ($is_array) {
                    echo "<div id='content$key$rand' " . ($jsexpand ? "style=\"display:none;\"" : '') . ">";
                    self::printCleanArray($val, $pad + 1);
                    echo "</div>";
                } else {
                    if (is_bool($val)) {
                        if ($val) {
                              echo 'true';
                        } else {
                             echo 'false';
                        }
                    } else {
                        if (is_object($val)) {
                            if (method_exists($val, '__toString')) {
                                echo (string) $val;
                            } else {
                                echo "(object) " . get_class($val);
                            }
                        } else {
                            echo htmlentities($val ?? "");
                        }
                    }
                }
                echo "</td></tr>";
            }
            echo "</table>";
        } else {
            echo __('Empty array');
        }
    }



    /**
     * Print pager for search option (first/previous/next/last)
     *
     * @param integer        $start                   from witch item we start
     * @param integer        $numrows                 total items
     * @param string         $target                  page would be open when click on the option (last,previous etc)
     * @param string         $parameters              parameters would be passed on the URL.
     * @param integer|string $item_type_output        item type display - if >0 display export PDF et Sylk form
     * @param integer|string $item_type_output_param  item type parameter for export
     * @param string         $additional_info         Additional information to display (default '')
     *
     * @return void
     *
     **/
    public static function printPager(
        $start,
        $numrows,
        $target,
        $parameters,
        $item_type_output = 0,
        $item_type_output_param = 0,
        $additional_info = ''
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $list_limit = $_SESSION['glpilist_limit'];
       // Forward is the next step forward
        $forward = $start + $list_limit;

       // This is the end, my friend
        $end = $numrows - $list_limit;

       // Human readable count starts here

        $current_start = $start + 1;

       // And the human is viewing from start to end
        $current_end = $current_start + $list_limit - 1;
        if ($current_end > $numrows) {
            $current_end = $numrows;
        }

       // Empty case
        if ($current_end == 0) {
            $current_start = 0;
        }

       // Backward browsing
        if ($current_start - $list_limit <= 0) {
            $back = 0;
        } else {
            $back = $start - $list_limit;
        }

       // Print it
        echo "<div><table class='table align-middle'>";
        echo "<tr>";

        if (strpos($target, '?') == false) {
            $fulltarget = $target . "?" . $parameters;
        } else {
            $fulltarget = $target . "&" . $parameters;
        }
       // Back and fast backward button
        if (!$start == 0) {
            echo "<th class='left'>";
            echo "<a href='$fulltarget&amp;start=0' class='btn btn-sm btn-ghost-secondary me-2'
                  title=\"" . __s('Start') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='fa fa-step-backward'></i>";
            echo "</a>";
            echo "<a href='$fulltarget&amp;start=$back' class='btn btn-sm btn-ghost-secondary me-2'
                  title=\"" . __s('Previous') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='fa fa-chevron-left'></i>";
            echo "</a></th>";
        }

       // Print the "where am I?"
        echo "<td width='31%' class='tab_bg_2'>";
        self::printPagerForm("$fulltarget&amp;start=$start");
        echo "</td>";

        if (!empty($additional_info)) {
            echo "<td class='tab_bg_2'>";
            echo $additional_info;
            echo "</td>";
        }

        if (
            !empty($item_type_output)
            && isset($_SESSION["glpiactiveprofile"])
            && (Session::getCurrentInterface() == "central")
        ) {
            echo "<td class='tab_bg_2 responsive_hidden' width='30%'>";
            echo "<form method='GET' action='" . $CFG_GLPI["root_doc"] . "/front/report.dynamic.php'>";
            echo Html::hidden('item_type', ['value' => $item_type_output]);

            if ($item_type_output_param != 0) {
                echo Html::hidden(
                    'item_type_param',
                    ['value' => Toolbox::prepareArrayForInput($item_type_output_param)]
                );
            }

            $parameters = trim($parameters, '&amp;');
            if (strstr($parameters, 'start') === false) {
                $parameters .= "&amp;start=$start";
            }

            $split = explode("&amp;", $parameters);

            $count_split = count($split);
            for ($i = 0; $i < $count_split; $i++) {
                $pos    = Toolbox::strpos($split[$i], '=');
                $length = Toolbox::strlen($split[$i]);
                echo Html::hidden(Toolbox::substr($split[$i], 0, $pos), ['value' => urldecode(Toolbox::substr($split[$i], $pos + 1))]);
            }

            Dropdown::showOutputFormat($item_type_output);
            Html::closeForm();
            echo "</td>";
        }

        echo "<td width='20%' class='b'>";
       //TRANS: %1$d, %2$d, %3$d are page numbers
        printf(__('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
        echo "</td>";

       // Forward and fast forward button
        if ($forward < $numrows) {
            echo "<th class='right'>";
            echo "<a href='$fulltarget&amp;start=$forward' class='btn btn-sm btn-ghost-secondary'
                  title=\"" . __s('Next') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>
               <i class='fa fa-chevron-right'></i>";
            echo "</a>";
            echo "<a href='$fulltarget&amp;start=$end' class='btn btn-sm btn-ghost-secondary'
                  title=\"" . __s('End') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='fa fa-step-forward'></i>";
            echo "</a>";
            echo "</th>";
        }
       // End pager
        echo "</tr></table></div>";
    }


    /**
     * Display the list_limit combo choice
     *
     * @param string  $action             page would be posted when change the value (URL + param) (default '')
     * @param boolean $display            display the pager form if true, return it if false
     * @param string  $additional_params  Additional parameters to pass to tab reload request (default '')
     *
     * ajax Pager will be displayed if empty
     *
     * @return void|string
     **/
    public static function printPagerForm($action = "", $display = true, $additional_params = '')
    {

        if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
            $additional_params = '&' . $additional_params;
        }

        $out = '';
        if ($action) {
            $out .= "<form method='POST' action=\"$action\">";
            $out .= "<span class='responsive_hidden'>" . __('Display (number of items)') . "</span>&nbsp;";
            $out .= Dropdown::showListLimit("submit()", false);
        } else {
            $out .= "<form method='POST' action =''>\n";
            $out .= "<span class='responsive_hidden'>" . __('Display (number of items)') . "</span>&nbsp;";
            $out .= Dropdown::showListLimit("reloadTab(\"glpilist_limit=\"+this.value+\"$additional_params\")", false);
        }
        $out .= Html::closeForm(false);

        if ($display) {
            echo $out;
            return;
        }
        return $out;
    }


    /**
     * Create a title for list, as  "List (5 on 35)"
     *
     * @param $string String  text for title
     * @param $num    Integer number of item displayed
     * @param $tot    Integer number of item existing
     *
     * @since 0.83.1
     *
     * @return string
     **/
    public static function makeTitle($string, $num, $tot)
    {

        if (($num > 0) && ($num < $tot)) {
           // TRANS %1$d %2$d are numbers (displayed, total)
            $cpt = "<span class='primary-bg primary-fg count'>" .
            sprintf(__('%1$d on %2$d'), $num, $tot) . "</span>";
        } else {
           // $num is 0, so means configured to display nothing
           // or $num == $tot
            $cpt = "<span class='primary-bg primary-fg count'>$tot</span>";
        }
        return sprintf(__('%1$s %2$s'), $string, $cpt);
    }


    /**
     * create a minimal form for simple action
     *
     * @param $action   String   URL to call on submit
     * @param $btname   String   button name (maybe if name <> value)
     * @param $btlabel  String   button label
     * @param $fields   Array    field name => field  value
     * @param $btimage  String   button image uri (optional)   (default '')
     *                           If image name starts with "fa-", il will be turned into
     *                           a font awesome element rather than an image.
     * @param $btoption String   optional button option        (default '')
     * @param $confirm  String   optional confirm message      (default '')
     *
     * @since 0.84
     **/
    public static function getSimpleForm(
        $action,
        $btname,
        $btlabel,
        array $fields = [],
        $btimage = '',
        $btoption = '',
        $confirm = ''
    ) {

        if (GLPI_USE_CSRF_CHECK) {
            $fields['_glpi_csrf_token'] = Session::getNewCSRFToken();
        }
        $fields['_glpi_simple_form'] = 1;
        $button                      = $btname;
        if (!is_array($btname)) {
            $button          = [];
            $button[$btname] = $btname;
        }
        $fields          = array_merge($button, $fields);
        $javascriptArray = [];
        foreach ($fields as $name => $value) {
           /// TODO : trouble :  urlencode not available for array / do not pass array fields...
            if (!is_array($value)) {
               // Javascript no gettext
                $javascriptArray[] = "'$name': '" . urlencode($value) . "'";
            }
        }

        $link = "<a ";

        if (!empty($btoption)) {
            $link .= ' ' . $btoption . ' ';
        }
       // Do not force class if already defined
        if (!strstr($btoption, 'class=')) {
            if (empty($btimage)) {
                $link .= " class='btn btn-primary' ";
            } else {
                $link .= " class='pointer' ";
            }
        }
        $action  = " submitGetLink('$action', {" . implode(', ', $javascriptArray) . "});";

        if (is_array($confirm) || strlen($confirm)) {
            $link .= self::addConfirmationOnAction($confirm, $action);
        } else {
            $link .= " onclick=\"$action\" ";
        }

        $link .= '>';
        if (empty($btimage)) {
            $link .= $btlabel;
        } else {
            if (strpos($btimage, 'fa-') === 0) {
                $link .= "<span class='fas $btimage' title='$btlabel'><span class='sr-only'>$btlabel</span>";
            } else if (strpos($btimage, 'ti-') === 0) {
                $link .= "<span class='ti $btimage' title='$btlabel'><span class='sr-only'>$btlabel</span>";
            } else {
                $link .= "<img src='$btimage' title='$btlabel' alt='$btlabel' class='pointer'>";
            }
        }
        $link .= "</a>";

        return $link;
    }


    /**
     * create a minimal form for simple action
     *
     * @param $action   String   URL to call on submit
     * @param $btname   String   button name
     * @param $btlabel  String   button label
     * @param $fields   Array    field name => field  value
     * @param $btimage  String   button image uri (optional) (default '')
     * @param $btoption String   optional button option (default '')
     * @param $confirm  String   optional confirm message (default '')
     *
     * @since 0.83.3
     **/
    public static function showSimpleForm(
        $action,
        $btname,
        $btlabel,
        array $fields = [],
        $btimage = '',
        $btoption = '',
        $confirm = ''
    ) {

        echo self::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
    }


    /**
     * Create a close form part including CSRF token
     *
     * @param $display boolean Display or return string (default true)
     *
     * @since 0.83.
     *
     * @return string
     **/
    public static function closeForm($display = true)
    {
        $out = '';

        if (GLPI_USE_CSRF_CHECK) {
            $out .= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        }

        $out .= "</form>";
        if ($display) {
            echo $out;
            return true;
        }
        return $out;
    }


    /**
     * Get javascript code for hide an item
     *
     * @param $id string id of the dom element
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsHide($id)
    {
        return self::jsGetElementbyID($id) . ".hide();\n";
    }


    /**
     * Get javascript code for hide an item
     *
     * @param $id string id of the dom element
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsShow($id)
    {
        return self::jsGetElementbyID($id) . ".show();\n";
    }


    /**
     * Clean ID used for HTML elements
     *
     * @param $id string id of the dom element
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function cleanId($id)
    {
        return str_replace(['[',']'], '_', $id);
    }


    /**
     * Get javascript code to get item by id
     *
     * @param $id string id of the dom element
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsGetElementbyID($id)
    {
        return "$('#$id')";
    }


    /**
     * Set dropdown value
     *
     * @param $id      string   id of the dom element
     * @param $value   string   value to set
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsSetDropdownValue($id, $value)
    {
        return self::jsGetElementbyID($id) . ".trigger('setValue', '$value');";
    }

    /**
     * Get item value
     *
     * @param $id      string   id of the dom element
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsGetDropdownValue($id)
    {
        return self::jsGetElementbyID($id) . ".val()";
    }


    /**
     * Adapt dropdown to clean JS
     *
     * @param $id       string   id of the dom element
     * @param $params   array    of parameters
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsAdaptDropdown($id, $params = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $width = '';
        if (isset($params["width"]) && !empty($params["width"])) {
            $width = $params["width"];
            unset($params["width"]);
        }

        $placeholder = '';
        if (isset($params["placeholder"])) {
            $placeholder = "placeholder: " . json_encode($params["placeholder"]) . ",";
        }

        $templateresult    = $params["templateResult"] ?? "templateResult";
        $templateselection = $params["templateSelection"] ?? "templateSelection";

        $js = "$(function() {
         const select2_el = $('#$id').select2({
            $placeholder
            width: '$width',
            dropdownAutoWidth: true,
            dropdownParent: $('#$id').closest('div.modal, div.dropdown-menu, body'),
            quietMillis: 100,
            minimumResultsForSearch: " . $CFG_GLPI['ajax_limit_count'] . ",
            matcher: function(params, data) {
               // store last search in the global var
               query = params;

               // If there are no search terms, return all of the data
               if ($.trim(params.term) === '') {
                  return data;
               }

               var searched_term = getTextWithoutDiacriticalMarks(params.term);
               var data_text = typeof(data.text) === 'string'
                  ? getTextWithoutDiacriticalMarks(data.text)
                  : '';
               var select2_fuzzy_opts = {
                  pre: '<span class=\"select2-rendered__match\">',
                  post: '</span>',
               };

               if (data_text.indexOf('>') !== -1 || data_text.indexOf('<') !== -1) {
                  // escape text, if it contains chevrons (can already be escaped prior to this point :/)
                  data_text = jQuery.fn.select2.defaults.defaults.escapeMarkup(data_text);
               }

               // Skip if there is no 'children' property
               if (typeof data.children === 'undefined') {
                  var match  = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                  if (match == null) {
                     return false;
                  }
                  data.rendered_text = match.rendered_text;
                  data.score = match.score;
                  return data;
               }

               // `data.children` contains the actual options that we are matching against
               // also check in `data.text` (optgroup title)
               var filteredChildren = [];

               $.each(data.children, function (idx, child) {
                  var child_text = typeof(child.text) === 'string'
                     ? getTextWithoutDiacriticalMarks(child.text)
                     : '';

                  if (child_text.indexOf('>') !== -1 || child_text.indexOf('<') !== -1) {
                     // escape text, if it contains chevrons (can already be escaped prior to this point :/)
                     child_text = jQuery.fn.select2.defaults.defaults.escapeMarkup(child_text);
                  }

                  var match_child = fuzzy.match(searched_term, child_text, select2_fuzzy_opts);
                  var match_text  = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                  if (match_child !== null || match_text !== null) {
                     if (match_text !== null) {
                        data.score         = match_text.score;
                        data.rendered_text = match_text.rendered;
                     }

                     if (match_child !== null) {
                        child.score         = match_child.score;
                        child.rendered_text = match_child.rendered;
                     }
                     filteredChildren.push(child);
                  }
               });

               // If we matched any of the group's children, then set the matched children on the group
               // and return the group object
               if (filteredChildren.length) {
                  var modifiedData = $.extend({}, data, true);
                  modifiedData.children = filteredChildren;

                  // You can return modified objects from here
                  // This includes matching the `children` how you want in nested data sets
                  return modifiedData;
               }

               // Return `null` if the term should not be displayed
               return null;
            },
            templateResult: $templateresult,
            templateSelection: $templateselection,
         })
         .bind('setValue', function(e, value) {
            $('#$id').val(value).trigger('change');
         })
         $('label[for=$id]').on('click', function(){ $('#$id').select2('open'); });
         $('#$id').on('select2:open', function(e){
            const search_input = document.querySelector(`.select2-search__field[aria-controls='select2-\${e.target.id}-results']`);
            if (search_input) {
               search_input.focus();
            }
         });
      });";
        return Html::scriptBlock($js);
    }


    /**
     * Create Ajax dropdown to clean JS
     *
     * @param $name
     * @param $field_id   string   id of the dom element
     * @param $url        string   URL to get datas
     * @param $params     array    of parameters
     *            must contains :
     *                if single select
     *                   - 'value'       : default value selected
     *                   - 'valuename'   : default name of selected value
     *                if multiple select
     *                   - 'values'      : default values selected
     *                   - 'valuesnames' : default names of selected values
     *
     * @since 0.85.
     *
     * @return string
     **/
    public static function jsAjaxDropdown($name, $field_id, $url, $params = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $default_options = [
            'value'               => 0,
            'valuename'           => Dropdown::EMPTY_VALUE,
            'multiple'            => false,
            'values'              => [],
            'valuesnames'         => [],
            'on_change'           => '',
            'width'               => '80%',
            'placeholder'         => '',
            'display_emptychoice' => false,
            'specific_tags'       => [],
            'parent_id_field'     => null,
        ];
        $params = array_merge($default_options, $params);

        $value = $params['value'];
        $width = $params["width"];
        $valuename = $params['valuename'];
        $on_change = $params["on_change"];
        $placeholder = $params['placeholder'] ?? '';
        $multiple = $params['multiple'];
        unset($params["on_change"]);
        unset($params["width"]);

        $allowclear =  "false";
        if (strlen($placeholder) > 0 && !$params['display_emptychoice']) {
            $allowclear = "true";
        }

        $options = [
            'id'        => $field_id,
            'selected'  => $value
        ];

       // manage multiple select (with multiple values)
        if ($params['multiple']) {
            $values = array_combine($params['values'], $params['valuesnames']);
            $options['multiple'] = 'multiple';
            $options['selected'] = $params['values'];
        } else {
            $values = [];

           // simple select (multiple = no)
            if ($value !== null) {
                $values = ["$value" => $valuename];
            }
        }
        $parent_id_field = $params['parent_id_field'];

        unset($params['placeholder']);
        unset($params['value']);
        unset($params['valuename']);
        unset($params['parent_id_field']);

        foreach ($params['specific_tags'] as $tag => $val) {
            if (is_array($val)) {
                $val = implode(' ', $val);
            }
            $options[$tag] = $val;
        }

        $output = '';

        $js = "
         var params_$field_id = {";
        foreach ($params as $key => $val) {
           // Specific boolean case
            if (is_bool($val)) {
                $js .= "$key: " . ($val ? 1 : 0) . ",\n";
            } else {
                $js .= "$key: " . json_encode($val) . ",\n";
            }
        }
        $js .= "};

         const select2_el = $('#$field_id').select2({
            width: '$width',
            multiple: '$multiple',
            placeholder: " . json_encode($placeholder) . ",
            allowClear: $allowclear,
            minimumInputLength: 0,
            quietMillis: 100,
            dropdownAutoWidth: true,
            dropdownParent: $('#$field_id').closest('div.modal, div.dropdown-menu, body'),
            minimumResultsForSearch: " . $CFG_GLPI['ajax_limit_count'] . ",
            ajax: {
               url: '$url',
               dataType: 'json',
               type: 'POST',
               data: function (params) {
                  query = params;
                  return $.extend({}, params_$field_id, {
                     searchText: params.term,";

        if ($parent_id_field !== null) {
            $js .= "
                     parent_id : document.getElementById('" . $parent_id_field . "').value,";
        }
        $js .= "
                     page_limit: " . $CFG_GLPI['dropdown_max'] . ", // page size
                     page: params.page || 1, // page number
                  });
               },
               processResults: function (data, params) {
                  params.page = params.page || 1;
                  var more = (data.count >= " . $CFG_GLPI['dropdown_max'] . ");

                  return {
                     results: data.results,
                     pagination: {
                           more: more
                     }
                  };
               }
            },
            templateResult: templateResult,
            templateSelection: templateSelection
         })
         .bind('setValue', function(e, value) {
            $.ajax('$url', {
               data: $.extend({}, params_$field_id, {
                  _one_id: value,
               }),
               dataType: 'json',
               type: 'POST',
            }).done(function(data) {

               var iterate_options = function(options, value) {
                  var to_return = false;
                  $.each(options, function(index, option) {
                     if (option.hasOwnProperty('id')
                         && option.id == value) {
                        to_return = option;
                        return false; // act as break;
                     }

                     if (option.hasOwnProperty('children')) {
                        to_return = iterate_options(option.children, value);
                     }
                  });

                  return to_return;
               };

               var option = iterate_options(data.results, value);
               if (option !== false) {
                  var newOption = new Option(option.text, option.id, true, true);
                   $('#$field_id').append(newOption).trigger('change');
               }
            });
         });
         ";
        if (!empty($on_change)) {
            $js .= " $('#$field_id').on('change', function(e) {" .
                  stripslashes($on_change) . "});";
        }

        $js .= " $('label[for=$field_id]').on('click', function(){ $('#$field_id').select2('open'); });";
        $js .= " $('#$field_id').on('select2:open', function(e){";
        $js .= "    const search_input = document.querySelector(`.select2-search__field[aria-controls='select2-\${e.target.id}-results']`);";
        $js .= "    if (search_input) {";
        $js .= "       search_input.focus();";
        $js .= "    }";
        $js .= " });";

        $output .= Html::scriptBlock('$(function() {' . $js . '});');

       // display select tag
        $options['class'] = $params['class'] ?? 'form-select';
        $output .= self::select($name, $values, $options);

        return $output;
    }


    /**
     * Creates a formatted IMG element.
     *
     * This method will set an empty alt attribute if no alt and no title is not supplied
     *
     * @since 0.85
     *
     * @param string $path     Path to the image file
     * @param array  $options  array of HTML attributes
     *        - `url` If provided an image link will be generated and the link will point at
     *               `$options['url']`.
     * @return string completed img tag
     **/
    public static function image($path, $options = [])
    {

        if (!isset($options['title'])) {
            $options['title'] = '';
        }

        if (!isset($options['alt'])) {
            $options['alt'] = $options['title'];
        }

        if (
            empty($options['title'])
            && !empty($options['alt'])
        ) {
            $options['title'] = $options['alt'];
        }

        $url = false;
        if (!empty($options['url'])) {
            $url = $options['url'];
            unset($options['url']);
        }

        $class = "";
        if ($url) {
            $class = "class='pointer'";
        }

        $image = sprintf('<img src="%1$s" %2$s %3$s />', $path, Html::parseAttributes($options), $class);
        if ($url) {
            return Html::link($image, $url);
        }
        return $image;
    }


    /**
     * Creates an HTML link.
     *
     * @since 0.85
     *
     * @param string $text     The content to be wrapped by a tags.
     * @param string $url      URL parameter
     * @param array  $options  Array of HTML attributes:
     *     - `confirm` JavaScript confirmation message.
     *     - `confirmaction` optional action to do on confirmation
     * @return string an `a` element.
     **/
    public static function link($text, $url, $options = [])
    {

        if (isset($options['confirm'])) {
            if (!empty($options['confirm'])) {
                $confirmAction  = '';
                if (isset($options['confirmaction'])) {
                    if (!empty($options['confirmaction'])) {
                        $confirmAction = $options['confirmaction'];
                    }
                    unset($options['confirmaction']);
                }
                $options['onclick'] = Html::getConfirmationOnActionScript(
                    $options['confirm'],
                    $confirmAction
                );
            }
            unset($options['confirm']);
        }
       // Do not escape title if it is an image or a i tag (fontawesome)
        if (!preg_match('/<i(mg)?.*/', $text)) {
            $text = Html::cleanInputText($text);
        }

        return sprintf(
            '<a href="%1$s" %2$s>%3$s</a>',
            Html::cleanInputText($url),
            Html::parseAttributes($options),
            $text
        );
    }


    /**
     * Creates a hidden input field.
     *
     * If value of options is an array then recursively parse it
     * to generate as many hidden input as necessary
     *
     * @since 0.85
     *
     * @param string $fieldName  Name of a field
     * @param array  $options    Array of HTML attributes.
     *
     * @return string A generated hidden input
     **/
    public static function hidden($fieldName, $options = [])
    {

        if ((isset($options['value'])) && (is_array($options['value']))) {
            $result = '';
            foreach ($options['value'] as $key => $value) {
                $options2          = $options;
                $options2['value'] = $value;
                $result           .= static::hidden($fieldName . '[' . $key . ']', $options2) . "\n";
            }
            return $result;
        }
        return sprintf(
            '<input type="hidden" name="%1$s" %2$s />',
            Html::cleanInputText($fieldName),
            Html::parseAttributes($options)
        );
    }


    /**
     * Creates a text input field.
     *
     * @since 0.85
     *
     * @param string $fieldName  Name of a field
     * @param array  $options    Array of HTML attributes.
     *
     * @return string A generated hidden input
     **/
    public static function input($fieldName, $options = [])
    {
        $type = 'text';
        if (isset($options['type'])) {
            $type = $options['type'];
            unset($options['type']);
        }
        if (!isset($options['class'])) {
            $options['class'] = "form-control";
        }
        return sprintf(
            '<input type="%1$s" name="%2$s" %3$s />',
            $type,
            Html::cleanInputText($fieldName),
            Html::parseAttributes($options)
        );
    }

    /**
     * Creates a select tag
     *
     * @since 9.3
     *
     * @param string $ame      Name of the field
     * @param array  $values   Array of the options
     * @param mixed  $selected Current selected option
     * @param array  $options  Array of HTML attributes
     *
     * @return string
     */
    public static function select($name, array $values = [], $options = [])
    {
        $selected = false;
        if (isset($options['selected'])) {
            $selected = $options['selected'];
            unset($options['selected']);
        }
        $select = sprintf(
            '<select name="%1$s" %2$s>',
            self::cleanInputText($name),
            self::parseAttributes($options)
        );
        foreach ($values as $key => $value) {
            $select .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                self::cleanInputText($key),
                ($selected != false && (
                $key == $selected
                || is_array($selected) && in_array($key, $selected))
                ) ? ' selected="selected"' : '',
                Html::entities_deep($value)
            );
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * Creates a submit button element. This method will generate input elements that
     * can be used to submit, and reset forms by using $options. Image submits can be created by supplying an
     * image option
     *
     * @since 0.85
     *
     * @param string $caption  caption of the input
     * @param array  $options  Array of options.
     *     - image : will use a submit image input
     *     - `confirm` JavaScript confirmation message.
     *     - `confirmaction` optional action to do on confirmation
     *
     * @return string A HTML submit button
     **/
    public static function submit($caption, $options = [])
    {

        $image = false;
        if (isset($options['image'])) {
            if (preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $options['image'])) {
                $image = $options['image'];
            }
            unset($options['image']);
        }

       // Set default class to submit
        if (!isset($options['class'])) {
            $options['class'] = 'btn';
        }
        if (isset($options['confirm'])) {
            if (!empty($options['confirm'])) {
                $confirmAction  = '';
                if (isset($options['confirmaction'])) {
                    if (!empty($options['confirmaction'])) {
                        $confirmAction = $options['confirmaction'];
                    }
                    unset($options['confirmaction']);
                }
                $options['onclick'] = Html::getConfirmationOnActionScript(
                    $options['confirm'],
                    $confirmAction
                );
            }
            unset($options['confirm']);
        }

        if ($image) {
            $options['title'] = $caption;
            $options['alt']   = $caption;
            return sprintf(
                '<input type="image" src="%s" %s />',
                Html::cleanInputText($image),
                Html::parseAttributes($options)
            );
        }

        $icon = "";
        if (isset($options['icon'])) {
            $icon = "<i class='{$options['icon']}'></i>";
        }

        $button = "<button type='submit' value='%s' %s>
               $icon
               <span>$caption</span>
            </button>&nbsp;";

        return sprintf($button, strip_tags(Html::cleanInputText($caption)), Html::parseAttributes($options));
    }


    /**
     * Creates an accessible, stylable progress bar control.
     * @since 9.5.0
     * @param int $max    The maximum value of the progress bar.
     * @param int $value    The current value of the progress bar.
     * @param array $params  Array of options:
     *                         - rand: Random int for the progress id. Default is a new random int.
     *                         - tooltip: Text to show in the tooltip. Default is nothing.
     *                         - append_percent_tt: If true, the percent will be appended to the tooltip.
     *                               In this case, it will also be automatically updated. Default is true.
     *                         - text: Text to show in the progress bar. Default is nothing.
     *                         - append_percent_text: If true, the percent will be appended to the text.
     *                               In this case, it will also be automatically updated. Default is false.
     * @return string     The progress bar HTML
     */
    public static function progress($max, $value, $params = [])
    {
        $p = [
            'rand'            => mt_rand(),
            'tooltip'         => '',
            'append_percent'  => true
        ];
        $p = array_replace($p, $params);

        $tooltip = trim($p['tooltip'] . ($p['append_percent'] ? " {$value}%" : ''));
        $calcWidth = ($value / $max) * 100;

        $html = <<<HTML
         <div class="progress" style="height: 12px"
              id="{progress{$p['rand']}}"
              data-progressid="{$p['rand']}"
              data-append-percent="{$p['append_percent']}"
              onchange="updateProgress('{$p['rand']}')"
              max="{$max}"
              value="{$value}"
              title="{$tooltip}" data-bs-toggle="tooltip">
            <div class="progress-bar progress-bar-striped bg-info progress-fg"
                 role="progressbar"
                 style="width: {$calcWidth}%;"
                 aria-valuenow="{$value}"
                 aria-valuemin="0"
                 aria-valuemax="{$max}">
            </div>
         </div>
HTML;
        return $html;
    }


    /**
     * Returns a space-delimited string with items of the $options array.
     *
     * @since 0.85
     *
     * @param $options Array of options.
     *
     * @return string Composed attributes.
     **/
    public static function parseAttributes($options = [])
    {

        if (!is_string($options)) {
            $attributes = [];

            foreach ($options as $key => $value) {
                $attributes[] = Html::formatAttribute($key, $value);
            }
            $out = implode(' ', $attributes);
        } else {
            $out = $options;
        }
        return $out;
    }


    /**
     * Formats an individual attribute, and returns the string value of the composed attribute.
     *
     * @since 0.85
     *
     * @param string $key    The name of the attribute to create
     * @param string $value  The value of the attribute to create.
     *
     * @return string The composed attribute.
     **/
    public static function formatAttribute($key, $value)
    {

        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        return sprintf('%1$s="%2$s"', $key, Html::cleanInputText($value));
    }


    /**
     * Wrap $script in a script tag.
     *
     * @since 0.85
     *
     * @param string $script  The script to wrap
     *
     * @return string
     **/
    public static function scriptBlock($script)
    {

        $script = "\n" . '//<![CDATA[' . "\n\n" . $script . "\n\n" . '//]]>' . "\n";

        return sprintf('<script type="text/javascript">%s</script>', $script);
    }


    /**
     * Returns one or many script tags depending on the number of scripts given.
     *
     * @since 0.85
     * @since 9.2 Path is now relative to GLPI_ROOT. Add $minify parameter.
     *
     * @param string  $url     File to include (relative to GLPI_ROOT)
     * @param array   $options Array of HTML attributes
     * @param boolean $minify  Try to load minified file (defaults to true)
     *
     * @return string
     **/
    public static function script($url, $options = [], $minify = true)
    {
        $version = GLPI_VERSION;
        if (isset($options['version'])) {
            $version = $options['version'];
            unset($options['version']);
        }

        $type = (isset($options['type']) && $options['type'] === 'module') ||
         preg_match('/^js\/modules\//', $url) === 1 ? 'module' : 'text/javascript';

        if ($minify === true) {
            $url = self::getMiniFile($url);
        }

        $url = self::getPrefixedUrl($url);

        if ($version) {
            $url .= '?v=' . FrontEnd::getVersionCacheKey($version);
        }

        // Convert filesystem path to URL path (fix issues with Windows directory separator)
        $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

        return sprintf('<script type="%s" src="%s"></script>', $type, $url);
    }


    /**
     * Creates a link element for CSS stylesheets.
     *
     * @since 0.85
     * @since 9.2 Path is now relative to GLPI_ROOT. Add $minify parameter.
     *
     * @param string  $url     File to include (relative to GLPI_ROOT)
     * @param array   $options Array of HTML attributes
     * @param boolean $minify  Try to load minified file (defaults to true)
     *
     * @return string CSS link tag
     **/
    public static function css($url, $options = [], $minify = true)
    {
        if ($minify === true) {
            $url = self::getMiniFile($url);
        }
        $url = self::getPrefixedUrl($url);

        return self::csslink($url, $options);
    }

    /**
     * Creates a link element for SCSS stylesheets.
     *
     * @since 9.4
     *
     * @param string  $url                   File to include (relative to GLPI_ROOT)
     * @param array   $options               Array of HTML attributes
     * @param bool    $force_compiled_file   Force usage of compiled file, even in debug mode (usefull for install/update process)
     *
     * @return string CSS link tag
     **/
    public static function scss($url, $options = [], bool $force_compiled_file = false)
    {
        $prod_file = self::getScssCompilePath($url);

        if (
            file_exists($prod_file)
            && ($force_compiled_file || $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE)
        ) {
            $url = self::getPrefixedUrl(str_replace(GLPI_ROOT, '', $prod_file));
        } else {
            $file = $url;
            $url = self::getPrefixedUrl('/front/css.php');
            $url .= '?file=' . $file;
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                $url .= '&debug';
            }
        }

        return self::csslink($url, $options);
    }

    /**
     * Creates a link element for (S)CSS stylesheets.
     *
     * @since 9.4
     *
     * @param string $url      File to include (raltive to GLPI_ROOT)
     * @param array  $options  Array of HTML attributes
     *
     * @return string CSS link tag
     **/
    private static function csslink($url, $options)
    {
        if (!isset($options['media']) || $options['media'] == '') {
            $options['media'] = 'all';
        }

        if (!isset($options['force_no_version']) || !$options['force_no_version']) {
            $version = GLPI_VERSION;
            if (isset($options['version'])) {
                $version = $options['version'];
                unset($options['version']);
            }

            $url .= ((strpos($url, '?') !== false) ? '&' : '?') . 'v=' . FrontEnd::getVersionCacheKey($version);
        }

        // Convert filesystem path to URL path (fix issues with Windows directory separator)
        $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

        return sprintf(
            '<link rel="stylesheet" type="text/css" href="%s" %s>',
            $url,
            Html::parseAttributes($options)
        );
    }


    /**
     * Creates an input file field. Send file names in _$name field as array.
     * Files are uploaded in files/_tmp/ directory
     *
     * @since 9.2
     *
     * @param $options       array of options
     *    - name                string   field name (default filename)
     *    - onlyimages          boolean  restrict to image files (default false)
     *    - filecontainer       string   DOM ID of the container showing file uploaded:
     *                                   use selector to display
     *    - showfilesize        boolean  show file size with file name
     *    - showtitle           boolean  show the title above file list
     *                                   (with max upload size indication)
     *    - enable_richtext     boolean  switch to richtext fileupload
     *    - editor_id           string   id attribute for the richtext editor
     *    - pasteZone           string   DOM ID of the paste zone
     *    - dropZone            string   DOM ID of the drop zone
     *    - rand                string   already computed rand value
     *    - display             boolean  display or return the generated html (default true)
     *    - only_uploaded_files boolean  show only the uploaded files block, i.e. no title, no dropzone
     *                                   (should be false when upload has to be enable only from rich text editor)
     *    - required            boolean  display a required mark
     *
     * @return void|string   the html if display parameter is false
     **/
    public static function file($options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $randupload             = mt_rand();

        $p['name']                = 'filename';
        $p['onlyimages']          = false;
        $p['filecontainer']       = 'fileupload_info' . $randupload;
        $p['showfilesize']        = true;
        $p['showtitle']           = true;
        $p['enable_richtext']     = false;
        $p['pasteZone']           = false;
        $p['dropZone']            = 'dropdoc' . $randupload;
        $p['rand']                = $randupload;
        $p['values']              = [];
        $p['display']             = true;
        $p['multiple']            = false;
        $p['uploads']             = [];
        $p['editor_id']           = null;
        $p['only_uploaded_files'] = false;
        $p['required']            = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $display = "";
        if ($p['only_uploaded_files']) {
            $display .= "<div class='fileupload only-uploaded-files'>";
        } else {
            $display .= "<div class='fileupload draghoverable' id='{$p['dropZone']}'>";

            if ($p['showtitle']) {
                $display .= "<b>";
                $display .= sprintf(__('%1$s (%2$s)'), __('File(s)'), Document::getMaxUploadSize());
                $display .= DocumentType::showAvailableTypesLink([
                    'display' => false,
                    'rand'    => $p['rand']
                ]);
                if ($p['required']) {
                    $display .= '<span class="required">*</span>';
                }
                $display .= "</b>";
            }
        }

        $display .= self::uploadedFiles([
            'filecontainer' => $p['filecontainer'],
            'name'          => $p['name'],
            'display'       => false,
            'uploads'       => $p['uploads'],
            'editor_id'     => $p['editor_id'],
        ]);

        $max_file_size  = $CFG_GLPI['document_max_size'] * 1024 * 1024;
        $max_chunk_size = round(Toolbox::getPhpUploadSizeLimit() * 0.9); // keep some place for extra data

        $required = "";
        if ($p['required']) {
            $required = "required='required'";
        }

        if (!$p['only_uploaded_files']) {
           // manage file upload without tinymce editor
            $display .= "<span class='b'>" . __('Drag and drop your file here, or') . '</span><br>';
        }
        $display .= "<input id='fileupload{$p['rand']}' type='file' name='_uploader_" . $p['name'] . "[]'
                      class='form-control'
                      $required
                      data-uploader-name=\"{$p['name']}\"
                      data-url='" . $CFG_GLPI["root_doc"] . "/ajax/fileupload.php'
                      data-form-data='{\"name\": \"_uploader_" . $p['name'] . "\", \"showfilesize\": \"" . $p['showfilesize'] . "\"}'"
                      . ($p['multiple'] ? " multiple='multiple'" : "")
                      . ($p['onlyimages'] ? " accept='.gif,.png,.jpg,.jpeg'" : "") . ">";

        $display .= "<div id='progress{$p['rand']}' style='display:none'>" .
                "<div class='uploadbar' style='width: 0%;'></div></div>";
        $progressall_js = "
        progressall: function(event, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress{$p['rand']}').show();
            $('#progress{$p['rand']} .uploadbar')
                .text(progress + '%')
                .css('width', progress + '%')
                .show();
        },
        ";

        $display .= Html::scriptBlock("
      $(function() {
         var fileindex{$p['rand']} = 0;
         $('#fileupload{$p['rand']}').fileupload({
            dataType: 'json',
            pasteZone: " . ($p['pasteZone'] !== false
                           ? "$('#{$p['pasteZone']}')"
                           : "false") . ",
            dropZone:  " . ($p['dropZone'] !== false
                           ? "$('#{$p['dropZone']}')"
                           : "false") . ",
            acceptFileTypes: " . ($p['onlyimages']
                                    ? "/(\.|\/)(gif|jpe?g|png)$/i"
                                 : DocumentType::getUploadableFilePattern()) . ",
            maxFileSize: {$max_file_size},
            maxChunkSize: {$max_chunk_size},
            add: function (e, data) {
               // disable submit button during upload
               $(this).closest('form').find(':submit').prop('disabled', true);
               // randomize filename
               for (var i = 0; i < data.files.length; i++) {
                  data.files[i].uploadName = uniqid('', true) + data.files[i].name;
               }
               // call default handler
               $.blueimp.fileupload.prototype.options.add.call(this, e, data);
            },
            done: function (event, data) {
               handleUploadedFile(
                  data.files, // files as blob
                  data.result._uploader_{$p['name']}, // response from '/ajax/fileupload.php'
                  '{$p['name']}',
                  $('#{$p['filecontainer']}'),
                  '{$p['editor_id']}'
               );
               // enable submit button after upload
               $(this).closest('form').find(':submit').prop('disabled', false);
               // remove required
                $('#fileupload{$p['rand']}').removeAttr('required');
            },
            fail: function (e, data) {
                // enable submit button after upload
                $(this).closest('form').find(':submit').prop('disabled', false);
               const err = 'responseText' in data.jqXHR && data.jqXHR.responseText.length > 0
                  ? data.jqXHR.responseText
                  : data.jqXHR.statusText;
               alert(err);
            },
            processfail: function (e, data) {
                // enable submit button after upload
                $(this).closest('form').find(':submit').prop('disabled', false);
               $.each(
                  data.files,
                  function(index, file) {
                     if (file.error) {
                        $('#progress{$p['rand']}').show();
                        $('#progress{$p['rand']} .uploadbar')
                           .text(file.error)
                           .css('width', '100%')
                           .show();
                        return;
                     }
                  }
               );
            },
            messages: {
              acceptFileTypes: __('Filetype not allowed'),
              maxFileSize: __('File is too big'),
            },
            $progressall_js
         });
      });");

        $display .= "</div>"; // .fileupload

        if ($p['display']) {
            echo $display;
        } else {
            return $display;
        }
    }

    /**
     * Display an html textarea  with extended options
     *
     * @since 9.2
     *
     * @param  array  $options with these keys:
     *  - name (string):              corresponding html attribute
     *  - filecontainer (string):     dom id for the upload filelist
     *  - rand (string):              random param to avoid overriding between textareas
     *  - editor_id (string):         id attribute for the textarea
     *  - value (string):             value attribute for the textarea
     *  - enable_richtext (bool):     enable tinymce for this textarea
     *  - enable_images (bool):       enable image pasting in tinymce (default: true)
     *  - enable_fileupload (bool):   enable the inline fileupload system
     *  - display (bool):             display or return the generated html
     *  - cols (int):                 textarea cols attribute (witdh)
     *  - rows (int):                 textarea rows attribute (height)
     *  - required (bool):            textarea is mandatory
     *  - uploads (array):            uploads to recover from a prevous submit
     *
     * @return mixed          the html if display paremeter is false or true
     */
    public static function textarea($options = [])
    {
       //default options
        $p['name']              = 'text';
        $p['filecontainer']     = 'fileupload_info';
        $p['rand']              = mt_rand();
        $p['editor_id']         = 'text' . $p['rand'];
        $p['value']             = '';
        $p['enable_richtext']   = false;
        $p['enable_images']     = true;
        $p['enable_fileupload'] = false;
        $p['display']           = true;
        $p['cols']              = 100;
        $p['rows']              = 15;
        $p['multiple']          = true;
        $p['required']          = false;
        $p['uploads']           = [];

       //merge default options with options parameter
        $p = array_merge($p, $options);

        $required = $p['required'] ? 'required="required"' : '';
        $display = '';
        $display .= "<textarea class='form-control' name='" . $p['name'] . "' id='" . $p['editor_id'] . "'
                             rows='" . $p['rows'] . "' cols='" . $p['cols'] . "' $required>" .
                  $p['value'] . "</textarea>";

        if ($p['enable_richtext']) {
            $display .= Html::initEditorSystem($p['editor_id'], $p['rand'], false, false, $p['enable_images']);
        }
        if (!$p['enable_fileupload'] && $p['enable_richtext'] && $p['enable_images']) {
            $p_rt = $p;
            $p_rt['display'] = false;
            $p_rt['only_uploaded_files'] = true;
            $display .= Html::file($p_rt);
        }

        if ($p['enable_fileupload']) {
            $p_rt = $p;
            unset($p_rt['name']);
            $p_rt['display'] = false;
            $display .= Html::file($p_rt);
        }

        if ($p['display']) {
            echo $display;
            return true;
        } else {
            return $display;
        }
    }


    /**
     * Display uploaded files area
     * @see displayUploadedFile() in fileupload.js
     *
     * @param $options       array of options
     *    - name                string   field name (default filename)
     *    - filecontainer       string   DOM ID of the container showing file uploaded:
     *    - editor_id           string   id attribute for the textarea
     *    - display             bool     display or return the generated html
     *    - uploads             array    uploads to display (done in a previous form submit)
     * @return void|string   the html if display parameter is false
     */
    private static function uploadedFiles($options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       //default options
        $p['filecontainer']     = 'fileupload_info';
        $p['name']              = 'filename';
        $p['editor_id']         = '';
        $p['display']           = true;
        $p['uploads']           = [];

       //merge default options with options parameter
        $p = array_merge($p, $options);

       // div who will receive and display file list
        $display = "<div id='" . $p['filecontainer'] . "' class='fileupload_info'>";
        if (isset($p['uploads']['_' . $p['name']])) {
            foreach ($p['uploads']['_' . $p['name']] as $uploadId => $upload) {
                $prefix  = substr($upload, 0, 23);
                $displayName = substr($upload, 23);

                // get the extension icon
                $extension = pathinfo(GLPI_TMP_DIR . '/' . $upload, PATHINFO_EXTENSION);
                $extensionIcon = '/pics/icones/' . $extension . '-dist.png';
                if (!is_readable(GLPI_ROOT . $extensionIcon)) {
                    $extensionIcon = '/pics/icones/defaut-dist.png';
                }
                $extensionIcon = $CFG_GLPI['root_doc'] . $extensionIcon;

                // Rebuild the minimal data to show the already uploaded files
                $upload = [
                    'name'    => $upload,
                    'id'      => 'doc' . $p['name'] . mt_rand(),
                    'display' => $displayName,
                    'size'    => filesize(GLPI_TMP_DIR . '/' . $upload),
                    'prefix'  => $prefix,
                ];
                $tag = $p['uploads']['_tag_' . $p['name']][$uploadId];
                $tag = [
                    'name' => $tag,
                    'tag'  => "#$tag#",
                ];

                // Show the name and size of the upload
                $display .= "<p id='" . $upload['id'] . "'>&nbsp;";
                $display .= "<img src='$extensionIcon' title='$extension'>&nbsp;";
                $display .= "<b>" . $upload['display'] . "</b>&nbsp;(" . Toolbox::getSize($upload['size']) . ")";

                $name = '_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $upload['name']]);

                $name = '_prefix_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $upload['prefix']]);

                $name = '_tag_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $tag['name']]);

                // show button to delete the upload
                $getEditor = 'null';
                if ($p['editor_id'] != '') {
                    $getEditor = "tinymce.get('" . $p['editor_id'] . "')";
                }
                $textTag = $tag['tag'];
                $domItems = "{0:'" . $upload['id'] . "', 1:'" . $upload['id'] . "'+'2'}";
                $deleteUpload = "deleteImagePasted($domItems, '$textTag', $getEditor)";
                $display .= '<span class="fas fa-times-circle pointer" onclick="' . $deleteUpload . '"></span>';

                $display .= "</p>";
            }
        }
        $display .= "</div>";

        if ($p['display']) {
            echo $display;
            return true;
        } else {
            return $display;
        }
    }


    /**
     * Display choice matrix
     *
     * @since 0.85
     * @param $columns   array   of column field name => column label
     * @param $rows      array    of field name => array(
     *      'label' the label of the row
     *      'columns' an array of specific information regaring current row
     *                and given column indexed by column field_name
     *                 * a string if only have to display a string
     *                 * an array('value' => ???, 'readonly' => ???) that is used to Dropdown::showYesNo()
     * @param $options   array   possible:
     *       'title'         of the matrix
     *       'first_cell'    the content of the upper-left cell
     *       'row_check_all' set to true to display a checkbox to check all elements of the row
     *       'col_check_all' set to true to display a checkbox to check all elements of the col
     *       'rand'          random number to use for ids
     *
     * @return integer random value used to generate the ids
     **/
    public static function showCheckboxMatrix(array $columns, array $rows, array $options = [])
    {

        $param['title']                = '';
        $param['first_cell']           = '&nbsp;';
        $param['row_check_all']        = false;
        $param['col_check_all']        = false;
        $param['rand']                 = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $number_columns = (count($columns) + 1);
        if ($param['row_check_all']) {
            $number_columns += 1;
        }

        // count checked
        $nb_cb_per_col = [];
        foreach ($columns as $col_name => $column) {
            $nb_cb_per_col[$col_name] = [
                'total'   => 0,
                'checked' => 0
            ];
        }

        $nb_cb_per_row = [];
        foreach ($rows as $row_name => $row) {
            if ((!is_string($row)) && (!is_array($row))) {
                continue;
            }

            if (!is_string($row)) {
                $nb_cb_per_row[$row_name] = [
                    'total'   => 0,
                    'checked' => 0
                ];

                foreach ($columns as $col_name => $column) {
                    if (array_key_exists($col_name, $row['columns'])) {
                        $content = $row['columns'][$col_name];
                        if (
                            is_array($content)
                            && array_key_exists('checked', $content)
                        ) {
                            $nb_cb_per_col[$col_name]['total']++;
                            $nb_cb_per_row[$row_name]['total']++;
                            if ($content['checked']) {
                                $nb_cb_per_col[$col_name]['checked']++;
                                $nb_cb_per_row[$row_name]['checked']++;
                            }
                        }
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('components/checkbox_matrix.html.twig', [
            'title'          => $param['title'],
            'columns'        => $columns,
            'rows'           => $rows,
            'param'          => $param,
            'number_columns' => $number_columns,
            'nb_cb_per_col'  => $nb_cb_per_col,
            'nb_cb_per_row'  => $nb_cb_per_row,
        ]);

        return $param['rand'];
    }



    /**
     * This function provides a mecanism to send html form by ajax
     *
     * @param string $selector selector of a HTML form
     * @param string $success  jacascript code of the success callback
     * @param string $error    jacascript code of the error callback
     * @param string $complete jacascript code of the complete callback
     *
     * @see https://api.jquery.com/jQuery.ajax/
     *
     * @since 9.1
     **/
    public static function ajaxForm($selector, $success = "console.log(html);", $error = "console.error(html)", $complete = '')
    {
        echo Html::scriptBlock("
      $(function() {
         var lastClicked = null;
         $('input[type=submit], button[type=submit]').click(function(e) {
            e = e || event;
            lastClicked = e.currentTarget || e.srcElement;
         });

         $('$selector').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.closest('form').serializeArray();
            //push submit button
            formData.push({
               name: $(lastClicked).attr('name'),
               value: $(lastClicked).val()
            });

            $.ajax({
               url: form.attr('action'),
               type: form.attr('method'),
               data: formData,
               success: function(html) {
                  $success
               },
               error: function(html) {
                  $error
               },
               complete: function(html) {
                  $complete
               }
            });
         });
      });
      ");
    }

    /**
     * In this function, we redefine 'window.alert' javascript function
     * by a prettier dialog.
     *
     * @since 9.1
     **/
    public static function redefineAlert()
    {

        echo self::scriptBlock("
      window.old_alert = window.alert;
      window.alert = function(message, caption) {
         // Don't apply methods on undefined objects... ;-) #3866
         if(typeof message == 'string') {
            message = message.replace('\\n', '<br>');
         }
         caption = caption || '" . _sn('Information', 'Information', 1) . "';

         glpi_alert({
            title: caption,
            message: message,
         });
      };");
    }


    /**
     * Summary of confirmCallback
     * Is a replacement for Javascript native confirm function
     * Beware that native confirm is synchronous by nature (will block
     * browser waiting an answer from user, but that this is emulating the confirm behaviour
     * by using callbacks functions when user presses 'Yes' or 'No' buttons.
     *
     * @since 9.1
     *
     * @param $msg            string      message to be shown
     * @param $title          string      title for dialog box
     * @param $yesCallback    string      function that will be called when 'Yes' is pressed
     *                                    (default null)
     * @param $noCallback     string      function that will be called when 'No' is pressed
     *                                    (default null)
     **/
    public static function jsConfirmCallback($msg, $title, $yesCallback = null, $noCallback = null)
    {
        return "glpi_confirm({
         title: '" . Toolbox::addslashes_deep($title) . "',
         message: '" . Toolbox::addslashes_deep($msg) . "',
         confirm_callback: function() {
            " . ($yesCallback !== null ? '(' . $yesCallback . ')()' : '') . "
         },
         cancel_callback: function() {
            " . ($noCallback !== null ? '(' . $noCallback . ')()' : '') . "
         },
      });
      ";
    }


    /**
     * In this function, we redefine 'window.confirm' javascript function
     * by a prettier dialog.
     * This dialog is normally asynchronous and can't return a boolean like naive window.confirm.
     * We manage this behavior with a global variable 'confirmed' who watchs the acceptation of dialog.
     * In this case, we trigger a new click on element to return the value (and without display dialog)
     *
     * @since 9.1
     */
    public static function redefineConfirm()
    {

        echo self::scriptBlock("
      var confirmed = false;
      var lastClickedElement;

      // store last clicked element on dom
      $(document).click(function(event) {
          lastClickedElement = $(event.target);
      });

      // asynchronous confirm dialog with jquery ui
      var newConfirm = function(message, caption) {
         message = message.replace('\\n', '<br>');
         caption = caption || '';

         glpi_confirm({
            title: caption,
            message: message,
            confirm_callback: function() {
               confirmed = true;

               //trigger click on the same element (to return true value)
               lastClickedElement.click();

               // re-init confirmed (to permit usage of 'confirm' function again in the page)
               // maybe timeout is not essential ...
               setTimeout(function() {
                  confirmed = false;
               }, 100);
            }
         });
      };

      window.nativeConfirm = window.confirm;

      // redefine native 'confirm' function
      window.confirm = function (message, caption) {
         // if watched var isn't true, we can display dialog
         if(!confirmed) {
            // call asynchronous dialog
            newConfirm(message, caption);
         }

         // return early
         return confirmed;
      };");
    }


    /**
     * Summary of jsAlertCallback
     * Is a replacement for Javascript native alert function
     * Beware that native alert is synchronous by nature (will block
     * browser waiting an answer from user, but that this is emulating the alert behaviour
     * by using a callback function when user presses 'Ok' button.
     *
     * @since 9.1
     *
     * @param $msg          string   message to be shown
     * @param $title        string   title for dialog box
     * @param $okCallback   string   function that will be called when 'Ok' is pressed
     *                               (default null)
     **/
    public static function jsAlertCallback($msg, $title, $okCallback = null)
    {
        return "glpi_alert({
         title: '" . Toolbox::addslashes_deep($title) . "',
         message: '" . Toolbox::addslashes_deep($msg) . "',
         ok_callback: function() {
            " . ($okCallback !== null ? '(' . $okCallback . ')()' : '') . "
         },
      });";
    }


    /**
     * Get image html tag for image document.
     *
     * @param int    $document_id  identifier of the document
     * @param int    $width        witdh of the final image
     * @param int    $height       height of the final image
     * @param bool   $addLink      boolean, do we need to add an anchor link
     * @param string $more_link    append to the link (ex &test=true)
     *
     * @return string
     *
     * @since 9.4.3
     **/
    public static function getImageHtmlTagForDocument($document_id, $width, $height, $addLink = true, $more_link = "")
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $document = new Document();
        if (!$document->getFromDB($document_id)) {
            return '';
        }

        $base_path = $CFG_GLPI['root_doc'];
        if (isCommandLine()) {
            $base_path = parse_url($CFG_GLPI['url_base'], PHP_URL_PATH);
        }

       // Add only image files : try to detect mime type
        $ok   = false;
        $mime = '';
        if (isset($document->fields['filepath'])) {
            $fullpath = GLPI_DOC_DIR . "/" . $document->fields['filepath'];
            $mime = Toolbox::getMime($fullpath);
            $ok   = Toolbox::getMime($fullpath, 'image');
        }

        if (!($ok || empty($mime))) {
            return '';
        }

        $out = '';
        if ($addLink) {
            $out .= '<a '
                 . 'href="' . $base_path . '/front/document.send.php?docid=' . $document_id . $more_link . '" '
                 . 'target="_blank" '
                 . '>';
        }
        $out .= '<img ';
        if (isset($document->fields['tag'])) {
            $out .= 'alt="' . $document->fields['tag'] . '" ';
        }
        $out .= 'width="' . $width . '" '
              . 'src="' . $base_path . '/front/document.send.php?docid=' . $document_id . $more_link . '" '
              . '/>';
        if ($addLink) {
            $out .= '</a>';
        }

        return $out;
    }

    /**
     * Get copyright message in HTML (used in footers)
     * @since 9.1
     * @param boolean $withVersion include GLPI version ?
     * @return string HTML copyright
     */
    public static function getCopyrightMessage($withVersion = true)
    {
        $message = "<a href=\"https://glpi-project.org/\" title=\"Powered by Teclib and contributors\" class=\"copyright\">";
        $message .= "GLPI ";
       // if required, add GLPI version (eg not for login page)
        if ($withVersion) {
            $message .= GLPI_VERSION . " ";
        }
        $message .= "Copyright (C) 2015-" . GLPI_YEAR . " Teclib' and contributors" .
         "</a>";
        return $message;
    }

    /**
     * A a required javascript lib
     *
     * @param string|array $name Either a know name, or an array defining lib
     *
     * @return void
     */
    public static function requireJs($name)
    {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

        if (isset($_SESSION['glpi_js_toload'][$name])) {
           //already in stack
            return;
        }
        switch ($name) {
            case 'glpi_dialog':
                $_SESSION['glpi_js_toload'][$name][] = 'js/glpi_dialog.js';
                break;
            case 'clipboard':
                $_SESSION['glpi_js_toload'][$name][] = 'js/clipboard.js';
                break;
            case 'tinymce':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/tinymce.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/UserMention.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/ContentTemplatesParameters.js';
                break;
            case 'planning':
                $_SESSION['glpi_js_toload'][$name][] = 'js/planning.js';
                break;
            case 'flatpickr':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/flatpickr.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/flatpickr_buttons_plugin.js';
                if (isset($_SESSION['glpilanguage'])) {
                    $filename = "public/lib/flatpickr/l10n/" .
                    strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]) . ".js";
                    if (file_exists(GLPI_ROOT . '/' . $filename)) {
                        $_SESSION['glpi_js_toload'][$name][] = $filename;
                        break;
                    }
                }
                break;
            case 'fullcalendar':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/fullcalendar.js';
                if (isset($_SESSION['glpilanguage'])) {
                    foreach ([2, 3] as $loc) {
                        $filename = "public/lib/fullcalendar/core/locales/" .
                         strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][$loc]) . ".js";
                        if (file_exists(GLPI_ROOT . '/' . $filename)) {
                            $_SESSION['glpi_js_toload'][$name][] = $filename;
                            break;
                        }
                    }
                }
                break;
            case 'kanban':
                break;
            case 'rateit':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jquery.rateit.js';
                break;
            case 'fileupload':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jquery-file-upload.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/fileupload.js';
                break;
            case 'charts':
                $_SESSION['glpi_js_toload']['charts'][] = 'public/lib/chartist.js';
                break;
            case 'notifications_ajax':
                $_SESSION['glpi_js_toload']['notifications_ajax'][] = 'js/notifications_ajax.js';
                break;
            case 'fuzzy':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/fuzzy.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/fuzzysearch.js';
                break;
            case 'dashboard':
                $_SESSION['glpi_js_toload'][$name][] = 'js/dashboard.js';
                break;
            case 'marketplace':
                $_SESSION['glpi_js_toload'][$name][] = 'js/marketplace.js';
                break;
            case 'gridstack':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/gridstack.js';
                break;
            case 'masonry':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/masonry.js';
                break;
            case 'sortable':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/sortable.js';
                break;
            case 'rack':
                $_SESSION['glpi_js_toload'][$name][] = 'js/rack.js';
                break;
            case 'leaflet':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/leaflet.js';
                break;
            case 'log_filters':
                $_SESSION['glpi_js_toload'][$name][] = 'js/log_filters.js';
                break;
            case 'codemirror':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/codemirror.js';
                break;
            case 'photoswipe':
                $_SESSION['glpi_js_toload'][$name][] = 'public/lib/photoswipe.js';
                break;
            case 'reservations':
                $_SESSION['glpi_js_toload'][$name][] = 'js/reservations.js';
                break;
            case 'cable':
                $_SESSION['glpi_js_toload'][$name][] = 'js/cable.js';
                break;
            default:
                $found = false;
                if (isset($PLUGIN_HOOKS['javascript']) && isset($PLUGIN_HOOKS['javascript'][$name])) {
                    $found = true;
                    $jslibs = $PLUGIN_HOOKS['javascript'][$name];
                    if (!is_array($jslibs)) {
                        $jslibs = [$jslibs];
                    }
                    foreach ($jslibs as $jslib) {
                        $_SESSION['glpi_js_toload'][$name][] = $jslib;
                    }
                }
                if (!$found) {
                    trigger_error("JS lib $name is not known!", E_USER_WARNING);
                }
        }
    }


    /**
     * Load javascripts
     *
     * @return void
     */
    private static function loadJavascript()
    {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

       // transfer core variables to javascript side
        echo self::getCoreVariablesForJavascript(true);

       //load on demand scripts
        if (isset($_SESSION['glpi_js_toload'])) {
            foreach ($_SESSION['glpi_js_toload'] as $key => $script) {
                if (is_array($script)) {
                    foreach ($script as $s) {
                        echo Html::script($s);
                    }
                } else {
                    echo Html::script($script);
                }
                unset($_SESSION['glpi_js_toload'][$key]);
            }
        }

       //locales for js libraries
        if (isset($_SESSION['glpilanguage'])) {
           // select2
            $filename = "public/lib/select2/js/i18n/" .
                     $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2] . ".js";
            if (file_exists(GLPI_ROOT . '/' . $filename)) {
                echo Html::script($filename);
            }
        }

       // Some Javascript-Functions which we may need later
        self::redefineAlert();
        self::redefineConfirm();

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax'] && !Session::isImpersonateActive()) {
            $options = [
                'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ? $CFG_GLPI['notifications_ajax_check_interval'] : 5) * 1000,
                'sound'     => $CFG_GLPI['notifications_ajax_sound'] ? $CFG_GLPI['notifications_ajax_sound'] : false,
                'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
                'user_id'   => Session::getLoginUserID()
            ];
            $js = "$(function() {
            notifications_ajax = new GLPINotificationsAjax(" . json_encode($options) . ");
            notifications_ajax.start();
         });";
            echo Html::scriptBlock($js);
        }

       // Add specific javascript for plugins
        if (isset($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]) && count($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT])) {
            foreach ($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_root_dir = Plugin::getPhpDir($plugin, true);
                $plugin_web_dir  = Plugin::getWebDir($plugin, false);
                $version = Plugin::getPluginFilesVersion($plugin);
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (file_exists($plugin_root_dir . "/{$file}")) {
                        echo Html::script("$plugin_web_dir/{$file}", [
                            'version'   => $version,
                            'type'      => 'text/javascript'
                        ]);
                    } else {
                        trigger_error("{$file} file not found from plugin {$plugin}!", E_USER_WARNING);
                    }
                }
            }
        }

        if (isset($PLUGIN_HOOKS['add_javascript_module']) && count($PLUGIN_HOOKS['add_javascript_module'])) {
            foreach ($PLUGIN_HOOKS["add_javascript_module"] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_root_dir = Plugin::getPhpDir($plugin, true);
                $plugin_web_dir  = Plugin::getWebDir($plugin, false);
                $version = Plugin::getPluginFilesVersion($plugin);
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (file_exists($plugin_root_dir . "/{$file}")) {
                        echo self::script("$plugin_web_dir/{$file}", [
                            'version'   => $version,
                            'type'      => 'module'
                        ]);
                    } else {
                        trigger_error("{$file} file not found from plugin {$plugin}!", E_USER_WARNING);
                    }
                }
            }
        }

        if (file_exists(GLPI_ROOT . "/js/analytics.js")) {
            echo Html::script("js/analytics.js");
        }
    }


    /**
     * transfer some var of php to javascript
     * (warning, don't expose all keys of $CFG_GLPI, some shouldn't be available client side)
     *
     * @param bool $full if false, don't expose all variables from CFG_GLPI (only url_base & root_doc)
     *
     * @since 9.5
     * @return string
     */
    public static function getCoreVariablesForJavascript(bool $full = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // prevent leak of data for non logged sessions
        $full = $full && (Session::getLoginUserID(true) !== false);

        $cfg_glpi = "var CFG_GLPI  = {
            'url_base': '" . (isset($CFG_GLPI['url_base']) ? $CFG_GLPI["url_base"] : '') . "',
            'root_doc': '" . $CFG_GLPI["root_doc"] . "',
        };";

        if ($full) {
            $debug = (isset($_SESSION['glpi_use_mode'])
                   && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            $cfg_glpi = "var CFG_GLPI  = " . json_encode(Config::getSafeConfig(true), $debug ? JSON_PRETTY_PRINT : 0) . ";";
        }

        $plugins_path = [];
        foreach (Plugin::getPlugins() as $key) {
            $plugins_path[$key] = Plugin::getWebDir($key, false);
        }
        $plugins_path = 'var GLPI_PLUGINS_PATH = ' . json_encode($plugins_path) . ';';

        return self::scriptBlock("
            $cfg_glpi
            $plugins_path
        ");
    }

    /**
     * Get a stylesheet or javascript path, minified if any
     * Return minified path if minified file exists and not in
     * debug mode, else standard path
     *
     * @param string $file_path File path part
     *
     * @return string
     */
    private static function getMiniFile($file_path)
    {
        $debug = (isset($_SESSION['glpi_use_mode'])
         && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $file_minpath = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file_path);
        if (file_exists(GLPI_ROOT . '/' . $file_minpath)) {
            if (!$debug || !file_exists(GLPI_ROOT . '/' . $file_path)) {
                return $file_minpath;
            }
        }

        return $file_path;
    }

    /**
     * Return prefixed URL
     *
     * @since 9.2
     *
     * @param string $url Original URL (not prefixed)
     *
     * @return string
     */
    final public static function getPrefixedUrl($url)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $prefix = $CFG_GLPI['root_doc'];
        if (substr($url, 0, 1) != '/') {
            $prefix .= '/';
        }
        return $prefix . $url;
    }

    /**
     * Add the HTML code to refresh the current page at a define interval of time
     *
     * @param int|false   $timer    The time (in minute) to refresh the page
     * @param string|null $callback A javascript callback function to execute on timer
     *
     * @return string
     */
    public static function manageRefreshPage($timer = false, $callback = null)
    {
        if (!$timer) {
            $timer = $_SESSION['glpirefresh_views'] ?? 0;
        }

        if ($callback === null) {
            $callback = 'window.location.reload()';
        }

        $text = "";
        if ($timer > 0) {
           // set timer to millisecond from minutes
            $timer = $timer * MINUTE_TIMESTAMP * 1000;

           // call callback function to $timer interval
            $text = self::scriptBlock("window.setInterval(function() {
               $callback
            }, $timer);");
        }

        return $text;
    }

    /**
     * Manage events from js/fuzzysearch.js
     *
     * @since 9.2
     *
     * @param string $action action to switch (should be actually 'getHtml' or 'getList')
     *
     * @return string
     */
    public static function fuzzySearch($action = '')
    {
        switch ($action) {
            case 'getHtml':
                $shortcut = "<kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>G</kbd>";
                if (!defined('TU_USER')) {
                    $parser = new UserAgentParser();
                    $ua = $parser->parse();
                    if ($ua->platform() === Platforms::MACINTOSH) {
                        $shortcut = "<kbd>⌥ (option)</kbd> + <kbd>⌘ (command)</kbd> + <kbd>G</kbd>";
                    }
                }

                $modal_header = __("Go to menu");
                $placeholder  = __("Start typing to find a menu");
                $alert        = sprintf(
                    __("Tip: You can call this modal with %s keys combination"),
                    "<kbd>$shortcut</kbd>"
                );

                $html = <<<HTML
               <div class="modal" tabindex="-1" id="fuzzysearch">
                  <div class="modal-dialog">
                     <div class="modal-content">
                        <div class="modal-header">
                           <h5 class="modal-title">
                              <i class="ti ti-arrow-big-right me-2"></i>
                              {$modal_header}
                           </h5>
                           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                           <div class="alert alert-info d-flex" role="alert">
                              <i class="fas fa-exclamation-circle fa-2x me-2"></i>
                              <p>{$alert}</p>
                           </div>
                           <input type="text" class="form-control" placeholder="{$placeholder}">
                           <ul class="results list-group mt-2"></ul>
                        </div>
                     </div>
                  </div>
               </div>

HTML;
                return $html;
            break;

            default:
                $fuzzy_entries = [];

               // retrieve menu
                foreach ($_SESSION['glpimenu'] as $firstlvl) {
                    if (isset($firstlvl['default'])) {
                        if (strlen($firstlvl['title']) > 0) {
                            $fuzzy_entries[] = [
                                'url'   => $firstlvl['default'],
                                'title' => $firstlvl['title']
                            ];
                        }
                    }

                    if (isset($firstlvl['default_dashboard'])) {
                        if (strlen($firstlvl['title']) > 0) {
                            $fuzzy_entries[] = [
                                'url'   => $firstlvl['default_dashboard'],
                                'title' => $firstlvl['title'] . " > " . __('Dashboard')
                            ];
                        }
                    }

                    if (isset($firstlvl['content'])) {
                        foreach ($firstlvl['content'] as $menu) {
                            if (isset($menu['title']) && strlen($menu['title']) > 0) {
                                $fuzzy_entries[] = [
                                    'url'   => $menu['page'],
                                    'title' => $firstlvl['title'] . " > " . $menu['title']
                                ];

                                if (isset($menu['options'])) {
                                    foreach ($menu['options'] as $submenu) {
                                        if (isset($submenu['title']) && strlen($submenu['title']) > 0) {
                                            $fuzzy_entries[] = [
                                                'url'   => $submenu['page'],
                                                'title' => $firstlvl['title'] . " > " .
                                               $menu['title'] . " > " .
                                               $submenu['title']
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

               // return the entries to ajax call
                return json_encode($fuzzy_entries);
            break;
        }
    }

    /**
     * Invert the input color (usefull for label bg on top of a background)
     * inpiration: https://github.com/onury/invert-color
     *
     * @since  9.3
     *
     * @param  string  $hexcolor the color, you can pass hex color (prefixed or not by #)
     *                           You can also pass a short css color (ex #FFF)
     * @param  boolean $bw       default true, should we invert the color or return black/white function of the input color
     * @param  boolean $sb       default true, should we soft the black/white to a dark/light grey
     * @return string            the inverted color prefixed by #
     */
    public static function getInvertedColor($hexcolor = "", $bw = true, $sbw = true)
    {
        if (strpos($hexcolor, '#') !== false) {
            $hexcolor = trim($hexcolor, '#');
        }
       // convert 3-digit hex to 6-digits.
        if (strlen($hexcolor) == 3) {
            $hexcolor = $hexcolor[0] + $hexcolor[0]
                   + $hexcolor[1] + $hexcolor[1]
                   + $hexcolor[2] + $hexcolor[2];
        }
        if (strlen($hexcolor) != 6) {
            throw new \Exception('Invalid HEX color.');
        }

        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));

        if ($bw) {
            return ($r * 0.299 + $g * 0.587 + $b * 0.114) > 100
            ? ($sbw
               ? '#303030'
               : '#000000')
             : ($sbw
               ? '#DFDFDF'
               : '#FFFFFF');
        }
       // invert color components
        $r = 255 - $r;
        $g = 255 - $g;
        $b = 255 - $b;

       // pad each with zeros and return
        return "#"
         + str_pad($r, 2, '0', STR_PAD_LEFT)
         + str_pad($g, 2, '0', STR_PAD_LEFT)
         + str_pad($b, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Compile SCSS styleshet
     *
     * @param array $args Arguments. May contain:
     *                      - v: version to append (will default to GLPI_VERSION)
     *                      - debug: if present, will not use Crunched formatter
     *                      - file: filerepresentation  to load
     *                      - reload: force reload and recache
     *                      - nocache: do not use nor update cache
     *
     * @return string
     */
    public static function compileScss($args)
    {
        /**
         * @var array $CFG_GLPI
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $CFG_GLPI, $GLPI_CACHE;

        if (!isset($args['file']) || empty($args['file'])) {
            throw new \InvalidArgumentException('"file" argument is required.');
        }

        $ckey = 'css_';
        $ckey .= isset($args['v']) ? $args['v'] : GLPI_VERSION;

        $scss = new Compiler();
        if (isset($args['debug'])) {
            $ckey .= '_sourcemap';
            $scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
            $scss->setSourceMapOptions(
                [
                    'sourceMapBasepath' => GLPI_ROOT . '/',
                    'sourceRoot'        => $CFG_GLPI['root_doc'] . '/',
                ]
            );
        }

        $file = $args['file'];

        $ckey .= '_' . $file;

        if (!str_ends_with($file, '.scss')) {
           // Prevent include of file if ext is not .scss
            $file .= '.scss';
        }

       // Requested file path
        $path = GLPI_ROOT . '/' . $file;

       // Alternate file path (prefixed by a "_", i.e. "_highcontrast.scss").
        $pathargs = explode('/', $file);
        $pathargs[] = '_' . array_pop($pathargs);
        $pathalt = GLPI_ROOT . '/' . implode('/', $pathargs);

        if (!file_exists($path) && !file_exists($pathalt)) {
            trigger_error('Requested file ' . $path . ' does not exists.', E_USER_WARNING);
            return '';
        }
        if (!file_exists($path)) {
            $path = $pathalt;
        }

       // Prevent import of a file from ouside GLPI dir
        $path = realpath($path);
        if (
            !str_starts_with($path, realpath(GLPI_ROOT))
            && !str_starts_with($path, realpath(GLPI_PLUGIN_DOC_DIR)) // Allow files generated by plugins
        ) {
            trigger_error('Requested file ' . $path . ' is outside GLPI file tree.', E_USER_WARNING);
            return '';
        }

       // Fix issue with Windows directory separator
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        $import = '@import "' . $path . '";';
        $fckey = 'css_raw_file_' . $file;
        $file_hash = self::getScssFileHash($path);

        //check if files has changed
        if (!isset($args['nocache']) && $file_hash != $GLPI_CACHE->get($fckey)) {
            //file has changed
            $args['reload'] = true;
        }

       // Enable imports of ".scss" files from "css/lib", when path starts with "~".
        $scss->addImportPath(
            function ($path) {
                $file_chunks = [];
                if (!preg_match('/^~@?(?<directory>.*)\/(?<file>[^\/]+)(?:(\.scss)?)/', $path, $file_chunks)) {
                    return null;
                }

                $possible_filenames = [
                    sprintf('%s/css/lib/%s/%s.scss', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file']),
                    sprintf('%s/css/lib/%s/_%s.scss', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file']),
                ];
                foreach ($possible_filenames as $filename) {
                    if (file_exists($filename)) {
                        return $filename;
                    }
                }

                return null;
            }
        );

        if (!isset($args['reload']) && !isset($args['nocache'])) {
            $css = $GLPI_CACHE->get($ckey);
            if ($css !== null) {
                return $css;
            }
        }

        $css = '';
        try {
            Toolbox::logDebug("Compile $file");
            $result = $scss->compileString($import, dirname($path));
            $css = $result->getCss();
            if (!isset($args['nocache'])) {
                $GLPI_CACHE->set($ckey, $css);
                $GLPI_CACHE->set($fckey, $file_hash);
            }
        } catch (\Throwable $e) {
            ErrorHandler::getInstance()->handleException($e, true);
            if (isset($args['debug'])) {
                $msg = 'An error occurred during SCSS compilation: ' . $e->getMessage();
                $msg = str_replace(["\n", "\"", "'"], ['\00000a', '\0022', '\0027'], $msg);
                $css = <<<CSS
              html::before {
                 background: #F33;
                 content: '$msg';
                 display: block;
                 padding: 20px;
                 position: sticky;
                 top: 0;
                 white-space: pre-wrap;
                 z-index: 9999;
              }
CSS;
            }

            /** @var \Glpi\Console\Application $application */
            global $application;
            if ($application instanceof Application) {
                throw $e;
            }
        }

        return $css;
    }

    /**
     * Returns SCSS file hash.
     * This function evaluates recursivly imports to compute a hash that represent the whole
     * contents of the final SCSS.
     *
     * @param string $filepath
     *
     * @return null|string
     */
    public static function getScssFileHash(string $filepath)
    {

        if (!is_file($filepath) || !is_readable($filepath)) {
            return null;
        }

        $contents = file_get_contents($filepath);
        $hash = md5($contents);

        $matches = [];
        if (!preg_match_all('/@import\s+[\'"](?<url>~?@?[^\'"]*)[\'"];/', $contents, $matches)) {
            return $hash;
        }

        foreach ($matches['url'] as $import_url) {
            $potential_paths = [];

            $has_extension   = preg_match('/\.s?css$/', $import_url);
            $is_from_lib     = preg_match('/^~/', $import_url);
            $import_dirname  = dirname(preg_replace('/^~?@?/', '', $import_url)); // Remove leading ~ and @ from lib path
            $import_filename = basename($import_url) . ($has_extension ? '' : '.scss');

            if ($is_from_lib) {
               // Search file in libs
                $potential_paths[] = GLPI_ROOT . '/css/lib/' . $import_dirname . '/' . $import_filename;
                $potential_paths[] = GLPI_ROOT . '/css/lib/' . $import_dirname . '/_' . $import_filename;
            } else {
               // Search using path relative to current file
                $potential_paths[] = dirname($filepath) . '/' . $import_dirname . '/' . $import_filename;
                $potential_paths[] = dirname($filepath) . '/' . $import_dirname . '/_' . $import_filename;
            }

            foreach ($potential_paths as $path) {
                if (is_file($path)) {
                    $hash .= self::getScssFileHash($path);
                    break;
                }
            }
        }

        return $hash;
    }

    /**
     * Get scss compilation path for given file.
     *
     * @param string $root_dir
     *
     * @return array
     *
     * @TODO GLPI 10.1 Handle SCSS compiled directory in plugins.
     */
    public static function getScssCompilePath($file, string $root_dir = GLPI_ROOT)
    {
        $file = preg_replace('/\.scss$/', '', $file);

        return self::getScssCompileDir($root_dir) . '/' . str_replace('/', '_', $file) . '.min.css';
    }

    /**
     * Get scss compilation directory.
     *
     * @param string $root_dir
     *
     * @return string
     */
    public static function getScssCompileDir(string $root_dir = GLPI_ROOT)
    {
        return $root_dir . '/css_compiled';
    }

    /**
     * Return a relative for the given timestamp
     *
     * @param mixed $ts
     * @return string
     *
     * @since 10.0.0
     */
    public static function timestampToRelativeStr($ts)
    {
        if ($ts === null) {
            return __('Never');
        }
        if (!ctype_digit($ts)) {
            $ts = strtotime($ts);
        }
        $ts_date = new DateTime();
        $ts_date->setTimestamp($ts);

        $diff = time() - $ts;
        if ($diff == 0) {
            return __('Now');
        } else if ($diff > 0) {
            $date = new DateTime(date('Y-m-d', $ts));
            $today = new DateTime('today');
            $day_diff = $date->diff($today)->days;
            if ($day_diff == 0) {
                if ($diff < 60) {
                    return __('Just now');
                }
                if ($diff < 3600) {
                    return  sprintf(__('%s minutes ago'), floor($diff / 60));
                }
                if ($diff < 86400) {
                    return  sprintf(__('%s hours ago'), floor($diff / 3600));
                }
            }
            if ($day_diff == 1) {
                return __('Yesterday');
            }
            if ($day_diff < 14) {
                return sprintf(__('%s days ago'), $day_diff);
            }
            if ($day_diff < 31) {
                return sprintf(__('%s weeks ago'), floor($day_diff / 7));
            }
            if ($day_diff < 60) {
                return __('Last month');
            }

            return IntlDateFormatter::formatObject($ts_date, 'MMMM Y', $_SESSION['glpilanguage'] ?? 'en_GB');
        } else {
            $diff     = abs($diff);
            $today = new DateTime('today');
            $date = new DateTime(date('Y-m-d', $ts));
            $day_diff = $today->diff($date)->days;
            if ($day_diff == 0) {
                if ($diff < 120) {
                    return __('In a minute');
                }
                if ($diff < 3600) {
                    return sprintf(__('In %s minutes'), floor($diff / 60));
                }
                if ($diff < 7200) {
                    return __('In an hour');
                }
                if ($diff < 86400) {
                    return sprintf(__('In %s hours'), floor($diff / 3600));
                }
            }
            if ($day_diff == 1) {
                return __('Tomorrow');
            }
            if ($day_diff < 14) {
                return sprintf(__('In %s days'), $day_diff);
            }
            if ($day_diff < 31) {
                return sprintf(__('In %s weeks'), floor($day_diff / 7));
            }
            if ($day_diff < 60) {
                return __('Next month');
            }

            return IntlDateFormatter::formatObject($ts_date, 'MMMM Y', $_SESSION['glpilanguage'] ?? 'en_GB');
        }

        return "";
    }
}
