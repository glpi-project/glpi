<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Console\Application;
use Glpi\Dashboard\Grid;
use Glpi\Debug\Profile;
use Glpi\Debug\Profiler;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Exception\RedirectException;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\ServiceCatalog;
use Glpi\Inventory\Inventory;
use Glpi\Plugin\Hooks;
use Glpi\System\Log\LogViewer;
use Glpi\Toolbox\FrontEnd;
use Glpi\Toolbox\URL;
use Glpi\UI\ThemeManager;
use Safe\DateTime;
use Safe\Exceptions\FilesystemException;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\HttpFoundation\Request;

use function Safe\file_get_contents;
use function Safe\filemtime;
use function Safe\filesize;
use function Safe\json_encode;
use function Safe\mktime;
use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;
use function Safe\realpath;
use function Safe\strtotime;

/**
 * Html Class
 * Inpired from Html/FormHelper for several functions
 **/
class Html
{
    /**
     * Memory required to compile the main GLPI scss file (`css/glpi.scss`).
     * It currently requires 120 MB, but may increase a bit when the dependencies are updated.
     */
    public const MAIN_SCSS_COMPILATION_REQUIRED_MEMORY = 192 * 1024 * 1024;

    /**
     * Recursivly execute html_entity_decode on an array
     *
     * @param string|array $value
     *
     * @return string|array
     *
     * @deprecated 11.0.0
     **/
    public static function entity_decode_deep($value)
    {
        Toolbox::deprecated();

        if (is_array($value)) {
            return array_map([self::class, 'entity_decode_deep'], $value);
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
     *
     * @deprecated 11.0.0
     **/
    public static function entities_deep($value)
    {
        Toolbox::deprecated();

        if (is_array($value)) {
            return array_map([self::class, 'entities_deep'], $value);
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
        if ($format === null) {
            $format = (int) $_SESSION["glpidate_format"];
        }

        try {
            $date = new DateTime($time);
        } catch (Throwable $e) {
            ErrorHandler::logCaughtException($e);
            ErrorHandler::displayCaughtExceptionMessage($e);
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('%1$s %2$s'),
                    $time,
                    _x('adjective', 'Invalid')
                ))
            );
            return $time;
        }
        $mask = match ($format) {
            1 => 'd-m-Y', // DD-MM-YYYY
            2 => 'm-d-Y', // MM-DD-YYYY
            default => 'Y-m-d',
        };

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
        if (is_null($time) || ($time === 'NULL')) {
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
     *
     * @deprecated 11.0.0
     **/
    public static function cleanInputText($string)
    {
        Toolbox::deprecated();

        if (!is_string($string)) {
            return $string;
        }
        return htmlescape($string);
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
     * Cut a text if longer than than the expected length.
     * This method always encodes the HTML special chars of the provided text.
     *
     * @param string  $string  string to resume
     * @param integer $length  resume length (default 255)
     *
     * @return string
     **/
    public static function resume_text($string, $length = 255)
    {
        $append = '';
        if (mb_strlen($string, 'UTF-8') > $length) {
            $string = mb_substr($string, 0, $length, 'UTF-8');
            $append = '&nbsp;(...)';
        }

        return \htmlescape($string) . $append;
    }

    /**
     * Clean post value for display in textarea
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     **/
    public static function cleanPostForTextArea($value)
    {
        Toolbox::deprecated();

        // As input is no more sanitized automatically, this method does not need to revert backslashes anymore.
        return $value;
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
        global $CFG_GLPI;

        // Php 5.3 : number_format() expects parameter 1 to be double,
        if ($number === '') {
            $number = 0;
        } elseif ($number === "-") { // used for not defines value (from Infocom::Amort, p.e.)
            return "-";
        }

        $number  = (float) $number;
        $decimal = $CFG_GLPI["decimal_number"];
        if ($forcedecimal >= 0) {
            $decimal = $forcedecimal;
        }

        // Edit : clean display for mysql
        if ($edit) {
            return number_format($number, $decimal, '.', '');
        }

        // Display : clean display
        return match ((int) $_SESSION['glpinumber_format']) {
            0 => number_format($number, $decimal, '.', ' '),
            2 => number_format($number, $decimal, ',', ' '),
            3 => number_format($number, $decimal, '.', ''),
            4 => number_format($number, $decimal, ',', ''),
            default => number_format($number, $decimal, '.', ','),
        };
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
        $time = (float) $time;

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
     * Redirect to the previous page.
     *
     * @return never
     **/
    public static function back(): never
    {
        self::redirect(self::getBackUrl());
    }


    /**
     * Redirection hack
     *
     * @param string $dest Redirection destination
     * @param int    $http_response_code Forces the HTTP response code to the specified value
     *
     * @return never
     **/
    public static function redirect($dest, $http_response_code = 302): never
    {
        throw new RedirectException($dest, $http_response_code);
    }


    /**
     * Display common message for item not found
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public static function displayNotFoundError(string $additional_info = '')
    {
        Toolbox::deprecated('Throw a `Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException` exception instead.');

        throw new NotFoundHttpException();
    }


    /**
     * Display common message for privileges errors
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public static function displayRightError(string $additional_info = '')
    {
        Toolbox::deprecated('Throw a `Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException` exception instead.');

        throw new AccessDeniedHttpException();
    }


    /**
     * Display a div containing messages set in session in the previous page
     **/
    public static function displayMessageAfterRedirect(bool $display_container = true)
    {
        TemplateRenderer::getInstance()->display('components/messages_after_redirect_toasts.html.twig', [
            'display_container' => $display_container,
        ]);
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
            " . htmlescape($ref_title) . "
         </span>";
        }

        if (is_array($ref_btts) && count($ref_btts)) {
            foreach ($ref_btts as $key => $val) {
                echo "<a class='btn btn-outline-secondary' href='" . htmlescape($key) . "'>" . htmlescape($val) . "</a>";
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
     * Display a Link to the last page.
     **/
    public static function displayBackLink()
    {
        echo '<a href="' . htmlescape(self::getBackUrl()) . '">' . __s('Back') . "</a>";
    }

    /**
     * Return an URL for getting back to previous page.
     * Remove `forcetab` parameter if exists to prevent bad tab display.
     * If the referer does not match a valid URL, return value will be the GLPI index page.
     *
     * @since 9.2.2
     * @since 11.0.0 The `$url_in` parameter has been removed.
     *
     * @return string|false Referer URL or false if referer URL is invalid.
     */
    public static function getBackUrl()
    {
        global $CFG_GLPI;

        $referer_url  = self::getRefererUrl();

        if ($referer_url === null) {
            return $CFG_GLPI['url_base'];
        }

        $referer_query = parse_url($referer_url, PHP_URL_QUERY);

        if ($referer_query !== null) {
            $parameters = [];
            parse_str($referer_query, $parameters);
            unset($parameters['forcetab'], $parameters['tab_params']);
            $new_query = http_build_query($parameters);

            $referer_url = str_replace($referer_query, $new_query, $referer_url);
            $referer_url = rtrim($referer_url, '?'); // remove `?` when there is no parameters
            return $referer_url;
        }

        return $referer_url;
    }

    /**
     * Return the referer URL.
     * If the referer is invalid, return value will be null.
     *
     * @since 11.0.0
     *
     * @return string|null
     */
    public static function getRefererUrl(): ?string
    {
        $referer = URL::sanitizeURL($_SERVER['HTTP_REFERER'] ?? '');

        $referer_host = parse_url($referer, PHP_URL_HOST);
        $referer_path = parse_url($referer, PHP_URL_PATH);

        if ($referer_host === null || $referer_path === null) {
            // Filter invalid referer.
            return null;
        }

        return $referer;
    }


    /**
     * Simple Error message page
     *
     * @param string  $message  displayed before dying
     * @param boolean $minimal  set to true do not display app menu (false by default)
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public static function displayErrorAndDie($message, $minimal = false): void
    {
        Toolbox::deprecated('Throw a `Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException` exception instead.');

        throw new BadRequestHttpException();
    }

    /**
     * Add confirmation on button or link before action
     *
     * @param string $string             to display or array of string for using multilines
     * @param string $additionalactions  additional actions to do on success confirmation
     *                                     (default '')
     *
     * @return string
     **/
    public static function addConfirmationOnAction($string, $additionalactions = '')
    {
        return "onclick=\"" . htmlescape(Html::getConfirmationOnActionScript($string, $additionalactions)) . "\"";
    }


    /**
     * Get confirmation on button or link before action
     *
     * @since 0.85
     *
     * @param string $string             to display or array of string for using multilines
     * @param string $additionalactions  additional actions to do on success confirmation
     *                                     (default '')
     *
     * @return string confirmation script
     **/
    public static function getConfirmationOnActionScript($string, $additionalactions = '')
    {

        if (!is_array($string)) {
            $string = [$string];
        }
        $additionalactions = trim($additionalactions);
        $out               = "";
        $multiple          = false;
        $close_string      = '';
        // Manage multiple confirmation
        foreach ($string as $tab) {
            if (is_array($tab)) {
                $multiple      = true;
                $out          .= "if (window.confirm(";
                $out          .= json_encode(htmlescape(implode("\n", $tab)));
                $out          .= ")){ ";
                $close_string .= "return true;} else { return false;}";
            }
        }
        // manage simple confirmation
        if (!$multiple) {
            $out          .= "if (window.confirm(";
            $out          .= json_encode(htmlescape(implode("\n", $string)));
            $out          .= ")){ ";
            $close_string .= "return true;} else { return false;}";
        }
        $out .= $additionalactions . (!str_ends_with($additionalactions, ';') ? ';' : '') . $close_string;
        return $out;
    }


    /**
     * Manage progresse bars
     *
     * @since 0.85
     *
     * @param string $id HTML ID of the progress bar
     * @param array $options progress status options
     *                    - create    do we have to create it ?
     *                    - message   add or change the message (HTML allowed. Text content must be escaped)
     *                    - percent   current level (Must be cast to a numeric type)
     *
     *
     * @return string|void Generated HTML if `display` param is true, void otherwise.
     *
     * @deprecated 11.0.0
     */
    public static function progressBar($id, array $options = [])
    {
        Toolbox::deprecated(
            '`Html::progressBar()` is deprecated.'
            . ' Use the `Html::getProgressBar()` method to get a static progress bar HTML snippet,'
            . ' or the `ProgressIndicator` JS module to display a progress bar related to a process progression.'
        );

        $params = [
            'create'    => false,
            'message'   => null,
            'percent'   => -1,
            'display'   => true,
            'colors'    => null,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                if ($key === 'colors' && $val !== null) {
                    $params['colors'] = array_merge([
                        'bg' => null,
                        'fg' => null,
                        'border' => null,
                        'text' => null,
                    ], $val);
                } else {
                    $params[$key] = $val;
                }
            }
        }

        $out = '';
        if ($params['create']) {
            $apply_custom_colors = $params['colors'] !== null;
            $outer_style = 'height: 16px;';
            if ($apply_custom_colors) {
                if ($params['colors']['bg']) {
                    $outer_style .= 'background-color: ' . $params['colors']['bg'] . ';';
                }
                if ($params['colors']['border']) {
                    $outer_style .= 'border: 1px solid ' . $params['colors']['border'] . ';';
                }
            }
            $inner_style = 'width: 0%; overflow: visible;';
            $inner_class = 'progress-bar text-dark';
            if (!$apply_custom_colors) {
                $inner_class .= ' progress-bar-striped bg-info';
            } else {
                if ($params['colors']['fg']) {
                    $inner_style .= 'background-color: ' . $params['colors']['fg'] . ';';
                }
                if ($params['colors']['text']) {
                    $inner_style .= 'color: ' . $params['colors']['text'] . ';';
                }
            }
            $out = '
                <div class="progress bg-primary-emphasis bg-light" style="' . htmlescape($outer_style) . '" id="' . htmlescape($id) . '">
                   <div class="' . htmlescape($inner_class) . '" role="progressbar"
                         style="' . htmlescape($inner_style) . '"
                         aria-valuenow="0"
                         aria-valuemin="0" aria-valuemax="100"
                         id="' . htmlescape($id) . '_text">
                   </div>
                </div>
            ';
        }

        if ($params['message'] !== null) {
            $out .= Html::scriptBlock(
                sprintf(
                    '$("#%s_text").html("%s");',
                    jsescape($id),
                    jsescape($params['message'])
                )
            );
        }

        if (
            ($params['percent'] >= 0)
            && ($params['percent'] <= 100)
        ) {
            $out .= Html::scriptBlock(
                sprintf(
                    '$("#%s_text").css("width", "%d%%");',
                    jsescape($id),
                    (int) $params['percent']
                )
            );
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
     * @param array  $options See {@link Html::progressBar()} for available options (excluding message)
     *
     * @return string|void
     *
     * @deprecated 11.0.0
     */
    public static function createProgressBar($msg = null, array $options = [])
    {
        Toolbox::deprecated(
            '`Html::createProgressBar()` is deprecated.'
            . ' Use the `Html::getProgressBar()` method to get a static progress bar HTML snippet,'
            . ' or the `ProgressIndicator` JS module to display a progress bar related to a process progression.'
        );

        $options = array_replace([
            'create' => true,
            'display' => true,
        ], $options);
        $options['message'] = $msg;

        if (!$options['display']) {
            return self::progressBar('doaction_progress', $options);
        }
        self::progressBar('doaction_progress', $options);
    }

    /**
     * Change the Message under the Progress Bar
     *
     * @param string $msg message under the bar
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public static function changeProgressBarMessage($msg = "&nbsp;")
    {
        Toolbox::deprecated(
            '`Html::changeProgressBarMessage()` is deprecated.'
            . ' Use the `ProgressIndicator` JS module to display a progress bar related to a process progression.'
        );

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
     *
     * @deprecated 11.0.0
     */
    public static function changeProgressBarPosition($crt, $tot, $msg = "")
    {
        Toolbox::deprecated(
            '`Html::changeProgressBarPosition()` is deprecated.'
            . ' Use the `ProgressIndicator` JS module to display a progress bar related to a process progression.'
        );

        $options = [];

        if (!$tot) {
            $options['percent'] = 0;
        } elseif ($crt > $tot) {
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
     *
     * @deprecated 11.0.0
     */
    public static function displayProgressBar($width, $percent, $options = [])
    {
        Toolbox::deprecated(
            '`Html::displayProgressBar()` is deprecated.'
            . ' Use the `Html::getProgressBar()` method to get a static progress bar HTML snippet,'
            . ' or the `ProgressIndicator` JS module to display a progress bar related to a process progression.'
        );

        $param['title']        = __('Progress');
        $param['simple']       = false;
        $param['forcepadding'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $title   = htmlescape($param['title']);
        $percent = htmlescape($percent);
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
     * Returns a static progress bar HTML snippet.
     *
     * @param float $percentage
     * @param string $label
     *
     * @return string
     */
    public static function getProgressBar(float $percentage, ?string $label = null): string
    {
        if ($label === null) {
            $label = floor($percentage) . ' %';
        }

        return TemplateRenderer::getInstance()->renderFromStringTemplate(
            <<<TWIG
              <div class="progress" style="height: 15px; min-width: 50px;">
                 <div class="progress-bar bg-info" role="progressbar" style="width: {{ percentage }}%;"
                    aria-valuenow="{{ percentage }}" aria-valuemin="0" aria-valuemax="100">{{ label }}</div>
              </div>
TWIG,
            [
                'percentage' => $percentage,
                'label'      => $label,
            ]
        );
    }

    /**
     * Include common HTML headers
     *
     * @param string $title   title used for the page (default '')
     * @param string $sector  sector in which the page displayed is
     * @param string $item    item corresponding to the page displayed
     * @param string $option  option corresponding to the page displayed
     * @param bool   $add_id  add current item id to the title ?
     * @param bool   $allow_insecured_iframe  allow insecured iframe (default false)
     * @param bool   $display display the header (default true)
     *
     * @return string|void Generated HTML if `display` param is false, void otherwise.
     * @phpstan-return ($display is true ? void : string)
     */
    public static function includeHeader(
        $title = '',
        $sector = 'none',
        $item = 'none',
        $option = '',
        bool $add_id = true,
        bool $allow_insecured_iframe = false,
        bool $display = true
    ) {
        global $CFG_GLPI;

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

        $theme = ThemeManager::getInstance()->getCurrentTheme();
        $lang = $_SESSION['glpilanguage'] ?? Session::getPreferredLanguage();

        $tpl_vars = [
            'lang'               => $CFG_GLPI["languages"][$lang][3],
            'title'              => $title,
            'theme'              => $theme,
            'is_anonymous_page'  => false,
            'css_files'          => [],
            'js_files'           => [],
            'custom_header_tags' => [],
        ];

        $tpl_vars['css_files'][] = ['path' => 'lib/base.css'];

        Html::requireJs('tinymce');

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax']) {
            Html::requireJs('notifications_ajax');
        }

        $tpl_vars['css_files'][] = ['path' => 'lib/leaflet.css'];
        Html::requireJs('leaflet');

        $tpl_vars['css_files'][] = ['path' => 'lib/flatpickr.css'];
        // Include dark theme as base (may be cleaner look than light; colors overriden by GLPI's stylesheet)
        $tpl_vars['css_files'][] = ['path' => 'lib/flatpickr/themes/dark.css'];
        Html::requireJs('flatpickr');

        $tpl_vars['css_files'][] = ['path' => 'lib/photoswipe.css'];
        Html::requireJs('photoswipe');

        $is_monaco_added = false;
        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $tpl_vars['js_modules'][] = ['path' => 'js/modules/Monaco/MonacoEditor.js'];
            $tpl_vars['css_files'][] = ['path' => 'lib/monaco.css'];
            $is_monaco_added = true;

            Html::requireJs('clipboard');
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

            if (in_array('dashboard', $jslibs)) {
                // include more js libs for dashboard case
                $jslibs = array_merge($jslibs, [
                    'gridstack',
                    'charts',
                    'clipboard',
                ]);
            }

            if (in_array('planning', $jslibs)) {
                Html::requireJs('planning');
            }

            if (in_array('fullcalendar', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'lib/fullcalendar.css'];
                Html::requireJs('fullcalendar');
            }

            if (in_array('reservations', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/reservations.scss'];
                Html::requireJs('reservations');
            }

            if (in_array('rateit', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'lib/jquery.rateit.css'];
                Html::requireJs('rateit');
            }

            if (in_array('dashboard', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/dashboard.scss'];
            }

            if (in_array('marketplace', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/marketplace.scss'];
                Html::requireJs('marketplace');
            }

            if (in_array('kb', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/standalone/kb.scss'];
            }

            if (in_array('rack', $jslibs)) {
                Html::requireJs('rack');
            }

            if (in_array('gridstack', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'lib/gridstack.css'];
                Html::requireJs('gridstack');
            }

            if (in_array('masonry', $jslibs)) {
                Html::requireJs('masonry');
            }

            if (in_array('clipboard', $jslibs)) {
                Html::requireJs('clipboard');
            }

            if (in_array('charts', $jslibs)) {
                Html::requireJs('charts');
            }

            if (in_array('cable', $jslibs)) {
                Html::requireJs('cable');
            }

            if (in_array('monaco', $jslibs) && !$is_monaco_added) {
                $tpl_vars['js_modules'][] = ['path' => 'js/modules/Monaco/MonacoEditor.js'];
                $tpl_vars['css_files'][] = ['path' => 'lib/monaco.css'];
            }

            if (in_array('home-scss-file', $jslibs)) {
                $tpl_vars['css_files'][] = ['path' => 'css/helpdesk_home.scss'];
            }
        }

        if (Session::getCurrentInterface() == "helpdesk") {
            $tpl_vars['css_files'][] = ['path' => 'lib/jquery.rateit.css'];
            Html::requireJs('rateit');
        }

        // Sortable required for drag and drop of display preferences and some other things like dashboards, kanban, etc
        Html::requireJs('sortable');

        //file upload is required... almost everywhere.
        Html::requireJs('fileupload');

        // load fuzzy search everywhere
        Html::requireJs('fuzzy');

        // load glpi dailog everywhere
        Html::requireJs('glpi_dialog');

        // load log filters everywhere
        Html::requireJs('log_filters');

        $tpl_vars['css_files'][] = ['path' => 'lib/tabler.css'];
        $tpl_vars['css_files'][] = ['path' => 'css/glpi.scss'];
        $tpl_vars['css_files'][] = ['path' => 'css/core_palettes.scss'];
        foreach (ThemeManager::getInstance()->getAllThemes() as $info) {
            if (!$info->isCustomTheme()) {
                continue;
            }
            $theme_path = $info->getKey() . '?is_custom_theme=1';
            // Custom theme files might be modified by external source
            $theme_path .= "&lastupdate=" . filemtime($info->getPath(false));
            $tpl_vars['css_files'][] = ['path' => $theme_path];
        }


        $tpl_vars['js_files'][] = ['path' => 'lib/base.js'];
        $tpl_vars['js_files'][] = ['path' => 'js/webkit_fix.js'];
        $tpl_vars['js_modules'][] = ['path' => 'build/vue/app.js'];
        $tpl_vars['js_files'][] = ['path' => 'js/common_ajax_controller.js'];
        $tpl_vars['js_files'][] = ['path' => 'js/common.js'];

        // Search
        $tpl_vars['js_modules'][] = ['path' => 'js/modules/Search/ResultsView.js'];
        $tpl_vars['js_modules'][] = ['path' => 'js/modules/Search/Table.js'];

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $tpl_vars['glpi_request_id'] = Profile::getCurrent()->getID();
        }

        if ($display) {
            TemplateRenderer::getInstance()->display('layout/parts/head.html.twig', $tpl_vars);
        } else {
            return TemplateRenderer::getInstance()->render('layout/parts/head.html.twig', $tpl_vars);
        }
    }


    /**
     * Get menu layout information.
     * This does not include any plugin menu info.
     * @since 0.90
     *
     * @return array
     **/
    public static function getMenuInfos()
    {
        global $CFG_GLPI;

        $can_read_dashboard      = Session::haveRight('dashboard', READ);
        $default_asset_dashboard = defined('TU_USER') ? "" : Grid::getDefaultDashboardForMenu('assets');
        $default_asset_helpdesk  = defined('TU_USER') ? "" : Grid::getDefaultDashboardForMenu('helpdesk');

        $menu = [
            'assets' => [
                'title' => _n('Asset', 'Assets', Session::getPluralNumber()),
                'types' => array_merge(
                    [
                        'Computer', 'Monitor', 'Software',
                        'NetworkEquipment', 'Peripheral', 'Printer',
                        'CartridgeItem', 'ConsumableItem', 'Phone',
                        'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment', 'Unmanaged', 'Cable',
                    ],
                    AssetDefinitionManager::getInstance()->getCustomObjectClassNames(),
                    $CFG_GLPI['devices_in_menu']
                ),
                'icon'    => 'ti ti-package',
            ],
        ];

        if ($can_read_dashboard && strlen($default_asset_dashboard) > 0) {
            $menu['assets']['default_dashboard'] = '/front/dashboard_assets.php';
        }

        $menu += [
            'helpdesk' => [
                'title' => __('Assistance'),
                'types' => [
                    'Ticket', ServiceCatalog::class, 'Problem', 'Change',
                    'Planning', 'Stat', 'TicketRecurrent', 'RecurrentChange',
                ],
                'icon'    => 'ti ti-headset',
            ],
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
                    'Appliance', 'Database',
                ],
                'icon'  => 'ti ti-wallet',
            ],
            'tools' => [
                'title' => __('Tools'),
                'types' => [
                    'Project', 'Reminder', 'RSSFeed', 'KnowbaseItem',
                    'ReservationItem', 'Report',
                    'SavedSearch', 'Impact',
                ],
                'icon' => 'ti ti-briefcase',
            ],
            'plugins' => [
                'title' => _n('Plugin', 'Plugins', Session::getPluralNumber()),
                'types' => [],
                'icon'  => 'ti ti-puzzle',
            ],
            'admin' => [
                'title' => __('Administration'),
                'types' => [
                    'User', 'Group', 'Entity', 'Rule',
                    'Profile', 'QueuedNotification', LogViewer::class,
                    Inventory::class, Form::class,
                ],
                'icon'  => 'ti ti-shield-check',
            ],
            'config' => [
                'title' => __('Setup'),
                'types' => [
                    AssetDefinition::class,
                    'CommonDropdown', 'CommonDevice', 'Notification', 'Webhook',
                    'SLM', 'Config', 'FieldUnicity', 'CronTask', 'Auth',
                    'OAuthClient', 'MailCollector', 'Link', 'Plugin',
                ],
                'icon'  => 'ti ti-settings',
            ],

            // special items
            'preference' => [
                'title'   => __('My settings'),
                'default' => '/front/preference.php',
                'icon'    => 'ti ti-user-cog',
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
     * @return array the menu array
     */
    public static function generateMenuSession($force = false)
    {
        global $PLUGIN_HOOKS;
        $menu = [];

        if (
            $force
            || Environment::get()->shouldExpectResourcesToChange()
            || !isset($_SESSION['glpimenu'])
            || !is_array($_SESSION['glpimenu'])
            || (count($_SESSION['glpimenu']) == 0)
        ) {
            $menu = self::getMenuInfos();

            // Permit to plugins to add entry to others sector !
            if (isset($PLUGIN_HOOKS[Hooks::MENU_TOADD]) && count($PLUGIN_HOOKS[Hooks::MENU_TOADD])) {
                foreach ($PLUGIN_HOOKS[Hooks::MENU_TOADD] as $plugin => $items) {
                    if (!Plugin::isPluginActive($plugin)) {
                        continue;
                    }
                    if (count($items)) {
                        foreach ($items as $key => $val) {
                            if (is_array($val)) {
                                foreach ($val as $k => $object) {
                                    $menu[$key]['types'][] = $object;
                                    if (empty($menu[$key]['icon']) && method_exists($object, 'getIcon')) {
                                        /** @var class-string $object */
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
                'Printer',
            ];

            foreach ($allassets as $type) {
                if (isset($menu['assets']['content'][strtolower($type)])) {
                    $menu['assets']['content']['allassets']['title']            = __('Global');
                    $menu['assets']['content']['allassets']['shortcut']         = '';
                    $menu['assets']['content']['allassets']['page']             = '/front/allassets.php';
                    $menu['assets']['content']['allassets']['icon']             = AllAssets::getIcon();
                    $menu['assets']['content']['allassets']['links']['search']  = '/front/allassets.php';
                    break;
                }
            }

            $_SESSION['glpimenu'] = $menu;
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
        global $PLUGIN_HOOKS;

        $menu = [
            'home' => [
                'default' => '/Helpdesk',
                'title'   => __('Home'),
                'icon'    => 'ti ti-home',
            ],
        ];

        $session_info = Session::getCurrentSessionInfo();
        if ($session_info === null) {
            // Unlogged users should not have any other menu entries.
            return $menu;
        }

        $entity = Entity::getById($session_info->getCurrentEntityId());
        if (!$entity) {
            // Safety check, will never happen but help with static analysis.
            throw new RuntimeException("Cant load current entity");
        }

        if (
            Session::haveRight("ticket", CREATE)
            && $entity->isServiceCatalogEnabled()
        ) {
            $menu['create_ticket'] = [
                'default' => ServiceCatalog::getSearchURL(false),
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
                        ],
                    ],
                ],
            ];

            if (Session::haveRight("ticket", CREATE)) {
                $menu['tickets']['content']['ticket']['links']['add'] = ServiceCatalog::getSearchURL(false);
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
            isset($PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY])
            && count($PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY])
        ) {
            $menu['plugins'] = [
                'title' => __("Plugins"),
                'icon'  => Plugin::getIcon(),
            ];

            foreach ($PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY] as $plugin => $active) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if ($active) {
                    $infos = Plugin::getInfo($plugin);
                    $link = "";
                    if (is_string($PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY][$plugin])) {
                        $link = $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY][$plugin];

                        // Ensure menu entries have all a starting `/`
                        if (!str_starts_with($link, '/')) {
                            $link = '/' . $link;
                        }

                        // Prefix with plugin path if plugin path is missing
                        if (!str_starts_with($link, "/plugins/{$plugin}/")) {
                            $link = "/plugins/{$plugin}{$link}";
                        }
                    }
                    $infos['page'] = $link;
                    $infos['title'] = $infos['name'];
                    if (isset($PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY_ICON][$plugin])) {
                        $infos['icon'] = $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY_ICON][$plugin];
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
        $menu = self::generateMenuSession();
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
         * @var bool $HEADER_LOADED
         */
        global $CFG_GLPI, $HEADER_LOADED, $DB;

        // If in modal : display popHeader
        if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
            return self::popHeader($title, '', false, $sector, $item, $option);
        }
        // Print a nice HTML-head for every page
        if ($HEADER_LOADED) {
            return;
        }
        $HEADER_LOADED = true;
        // Force lower case for sector and item
        $sector = strtolower($sector);
        $item   = strtolower($item);

        Profiler::getInstance()->start('Html::includeHeader');
        self::includeHeader($title, $sector, $item, $option, $add_id);
        Profiler::getInstance()->stop('Html::includeHeader');

        $menu = self::generateMenuSession();
        $menu = Plugin::doHookFunction(Hooks::REDEFINE_MENUS, $menu);

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

        if (
            $DB->isSlave()
            && !$DB->first_connection
        ) {
            echo "<div id='dbslave-float'>";
            echo "<a href='#see_debug'>" . __s('SQL replica: read only') . "</a>";
            echo "</div>";
        }

        // call static function callcron() every 5min
        CronTask::callCron();
    }


    /**
     * Print footer for every page
     *
     * @since 11.0.0 The `$keepDB` parameter has been removed.
     */
    public static function footer()
    {
        /**
         * @var bool $FOOTER_LOADED
         */
        global $CFG_GLPI, $FOOTER_LOADED;

        // If in modal : display popFooter
        if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
            return self::popFooter();
        }

        // Print foot for every page
        if ($FOOTER_LOADED) {
            return;
        }
        $FOOTER_LOADED = true;

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax'] && !Session::isImpersonateActive()) {
            $options = [
                'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ?: 5) * 1000,
                'sound'     => $CFG_GLPI['notifications_ajax_sound'] ?: false,
                'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
                'user_id'   => Session::getLoginUserID(),
            ];
            $js = "$(function() {
            notifications_ajax = new GLPINotificationsAjax(" . json_encode($options) . ");
            notifications_ajax.start();
         });";
            echo Html::scriptBlock($js);
        }

        $tpl_vars = [
            'js_files' => [],
            'js_modules' => [],
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
                'lib/select2/js/i18n/%s.js',
                $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]
            );
            if (file_exists(GLPI_ROOT . '/public/' . $filename)) {
                $tpl_vars['js_files'][] = ['path' => $filename];
            }
        }

        $tpl_vars['js_files'][] = ['path' => 'js/misc.js'];

        $tpl_vars['debug_info'] = null;

        self::displayMessageAfterRedirect();
        Profiler::getInstance()->stopAll();
        if (
            $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE
            && !str_starts_with(Request::createFromGlobals()->getPathInfo(), '/install/')
        ) {
            $tpl_vars['debug_info'] = Profile::getCurrent()->getDebugInfo();
        }

        TemplateRenderer::getInstance()->display('layout/parts/page_footer.html.twig', $tpl_vars);
    }

    /**
     * Display Ajax Footer for debug
     *
     * @deprecated 11.0.0
     */
    public static function ajaxFooter()
    {
        // Not currently used. Old debug stuff is now in the new debug bar.
        Toolbox::deprecated();
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
                'default' => $url,
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
        $menu = Plugin::doHookFunction(Hooks::REDEFINE_MENUS, $menu);

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
        $parser = new UserAgentParser();
        try {
            $ua = $parser->parse();
            $platform = $ua->platform();
        } catch (InvalidArgumentException $e) {
            // To avoid log overload, we suppress the InvalidArgumentException error.
            // Some non-standard clients, such as bots or simplified HTTP services,
            // don’t always send the User-Agent header,
            // and privacy-focused browsers or extensions may also block it.
            // Additionally, server configurations like proxies or firewalls
            // may remove this header for security reasons.
        }

        $help_url_key = Session::getCurrentInterface() === 'central'
            ? 'central_doc_url'
            : 'helpdesk_doc_url';
        $help_url = !empty($CFG_GLPI[$help_url_key])
            ? $CFG_GLPI[$help_url_key]
            : 'https://glpi-project.org/documentation';

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
        self::footer();
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

        // Send UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");

        // Send extra expires header if configured
        self::header_nocache();

        self::includeHeader($title);

        TemplateRenderer::getInstance()->display('layout/parts/page_header_empty.html.twig');
    }


    /**
     * Print footer for null page
     **/
    public static function nullFooter()
    {
        self::footer();
    }


    /**
     * Print a nice HTML head for modal window (nothing to display)
     *
     * @param string  $title    title of the page
     * @param string  $url      not used anymore
     * @param boolean $in_modal indicate if page loaded in modal - css target
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
        string $title = "",
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

        self::includeHeader($title, $sector, $item, $option, true, true);
        echo "<body class='iframed'>";
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
        self::displayMessageAfterRedirect();
        echo "</body></html>";
    }


    /**
     * Flushes the system write buffers of PHP and whatever backend PHP is using (CGI, a web server, etc).
     * This attempts to push current output all the way to the browser with a few caveats.
     * @see https://www.sitepoint.com/php-streaming-output-buffering-explained/
     *
     * @deprecated 11.0.0
     */
    public static function glpi_flush()
    {
        trigger_error(
            '`Html::glpi_glush()` no longer has any effect.',
            E_USER_WARNING
        );
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
     * Get "check All as" checkbox
     *
     * @since 0.84
     *
     * @param string $container_id  html of the container of checkboxes link to this check all checkbox
     * @param null|int|'__RAND__'   $rand          rand value to use (default is auto generated)
     *
     * @return string
     **/
    public static function getCheckAllAsCheckbox($container_id, $rand = null)
    {
        if ($rand === null) {
            $rand = mt_rand();
        } elseif ($rand !== "__RAND__") {
            $rand = (int) $rand;
        }

        $out  = "<input title='" . __s('Check all as') . "' type='checkbox' class='form-check-input massive_action_checkbox'
                      title='" . __s('Check all as') . "'
                      name='_checkall_$rand' id='checkall_$rand'
                      onclick= \"if ( checkAsCheckboxes(this, '" . htmlescape(jsescape($container_id)) . "', '.massive_action_checkbox')) {return true;}\">";

        // permit to shift select checkboxes
        $out .= Html::scriptBlock("\$(function() {\$('#" . jsescape($container_id) . " input[type=\"checkbox\"]').shiftSelectable();});");

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

        if (count($options)) {
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

            return $criterion;
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

        if (count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $out = "";

        if ($params['zero_on_empty']) {
            $out .= '<input type="hidden" name="' . htmlescape($params['name']) . '" value="0" />';
        }

        $out .= "<input type='checkbox' class='form-check-input " . htmlescape($params['class']) . "' title=\"" . htmlescape($params['title']) . "\" ";
        if (isset($params['onclick'])) {
            $out .= " onclick='" . htmlescape($params['onclick']) . "'";
        }

        foreach (['id', 'name', 'title', 'value'] as $field) {
            if (!empty($params[$field])) {
                $out .= " $field='" . htmlescape($params[$field]) . "'";
            }
        }

        $criterion = self::getCriterionForMassiveCheckboxes($params['criterion']);
        if (!empty($criterion)) {
            $out .= " onClick='massiveUpdateCheckbox(\"" . htmlescape(jsescape($criterion)) . "\", this)'";
        }

        if (!empty($params['massive_tags'])) {
            $params['specific_tags']['data-glpicore-cb-massive-tags'] = $params['massive_tags'];
        }

        if (!empty($params['specific_tags'])) {
            foreach ($params['specific_tags'] as $tag => $values) {
                if (is_array($values)) {
                    $values = implode(' ', $values);
                }
                $tag = htmlescape($tag);
                $values = htmlescape($values);
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
            $out .= Html::scriptBlock("\$(function() {\$('" . jsescape($criterion) . "').shiftSelectable();});");
        }

        return $out;
    }


    /**
     * @brief display a checkbox that $_POST 0 or 1 depending on if it is checked or not.
     * @see Html::getCheckbox()
     *
     * @since 0.85
     *
     * @param array $options
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
     * @param string|integer $id        ID of the item
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
            $id = htmlescape($id);
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
     * @param string|integer $id        ID of the item
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
        global $CFG_GLPI;

        if (empty($name)) {
            $name = 'massaction_' . mt_rand();
        }

        $name = htmlescape($name);

        return  "<form name='$name' id='$name' method='post'
               action='" . htmlescape($CFG_GLPI["root_doc"]) . "/front/massiveaction.php'
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
     * @param array $options  Array of parameters
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
     *    - action_button_classes : string of classes to add to the action button
     *    - display          : display or return the generated html (default true)
     *
     * @return bool|string     the html if display parameter is false, or true
     **/
    public static function showMassiveActions($options = [])
    {
        global $CFG_GLPI;

        /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

        $p['ontop']                 = true;
        $p['num_displayed']         = -1;
        $p['forcecreate']           = false;
        $p['check_itemtype']        = '';
        $p['check_items_id']        = '';
        $p['is_deleted']            = false;
        $p['extraparams']           = [];
        $p['width']                 = 800;
        $p['height']                = 400;
        $p['specific_actions']      = [];
        $p['add_actions']           = [];
        $p['confirm']               = '';
        $p['rand']                  = '';
        $p['container']             = '';
        $p['display_arrow']         = true;
        $p['title']                 = _n('Action', 'Actions', Session::getPluralNumber());
        $p['item']                  = false;
        $p['tag_to_send']           = 'common';
        $p['action_button_classes'] = 'btn btn-sm btn-primary me-2';
        $p['display']               = true;

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
                $out .= "<span class='btn btn-sm border-danger text-danger me-1'>
                            <i class='ti ti-corner-left-down mt-1' style='margin-left: -2px;'></i>"
                            . __s('Selection too large, massive action disabled.')
                        . "</span>";
                if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                    $out .= Html::showToolTip(
                        __s('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.'),
                        ['display' => false, 'awesome-class' => 'btn btn-sm border-danger text-danger me-1 fa-info']
                    );
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
                        $js_modal_fields .= '#' . jsescape($p['container']) . ' ';
                    }
                    $js_modal_fields .= "[data-glpicore-ma-tags~=" . jsescape($p['tag_to_send']) . "]').each(function( index ) {
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
            $out .= "<a role=\"button\" title='" . __s('Massive actions') . "'
                     data-bs-toggle='tooltip' data-bs-placement='" . ($p['ontop'] ? "bottom" : "top") . "'
                     class='" . htmlescape($p['action_button_classes']) . "' ";
            if (is_array($p['confirm']) || strlen($p['confirm'])) {
                $out .= self::addConfirmationOnAction($p['confirm'], "modal_massiveaction_window$identifier.show();");
            } else {
                $out .= "onclick='modal_massiveaction_window$identifier.show();'";
            }
            $out .= " href='#modal_massaction_content$identifier' title=\"" . htmlescape($p['title']) . "\">";
            if ($p['display_arrow']) {
                $out .= "<i class='ti ti-corner-left-" . ($p['ontop'] ? 'down' : 'up') . " mt-1' style='margin-left: -2px;'></i>";
            }
            $out .= "<span>" . htmlescape($p['title']) . "</span>";
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

        $calendar_tooltip = __s('Enter or select a date');
        $calendar_btn = $p['calendar_btn']
         ? "<button type='button' class='btn btn-outline-secondary btn-sm' data-toggle>
                <i class='ti ti-calendar'></i>
                <span class='sr-only'>" . $calendar_tooltip . "</span>
            </button>"
         : "";
        $clear_btn = $p['clear_btn'] && $p['maybeempty'] && $p['canedit']
         ? "<button type='button' class='btn btn-outline-secondary btn-sm' data-toggle data-clear title='" . __s('Clear') . "'>
                    <i class='ti ti-circle-x'></i>
                </button>"
         : "";

        $mode = $p['range']
         ? "mode: 'range',"
         : "";

        $name = htmlescape($name);
        $rand = (int) $p['rand'];
        $size = (int) $p['size'];
        $placeholder = htmlescape($p['placeholder']);

        $output = <<<HTML
      <div class="button-group flex-grow-1 flatpickr d-flex align-items-center" id="showdate{$rand}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{$calendar_tooltip}">
         <input type="text" name="{$name}" size="{$size}"
                {$required} {$disabled} data-input placeholder="{$placeholder}" class="form-control rounded-start ps-2">
         $calendar_btn
         $clear_btn
      </div>
HTML;

        $date_format = jsescape(Toolbox::getDateFormat('js'));

        $min_attr = !empty($p['min']) ? sprintf("minDate: '%s',", jsescape($p['min'])) : "";
        $max_attr = !empty($p['max']) ? sprintf("maxDate: '%s',", jsescape($p['max'])) : "";
        $multiple_attr = $p['multiple'] ? "mode: 'multiple'," : "";

        $value = json_encode($p['value']);

        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $locale_language = jsescape($locale['language']);
        $locale_region   = jsescape($locale['region']);
        $js = <<<JS
      $(function() {
         $("#showdate{$rand}").flatpickr({
            defaultDate: {$value},
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: '{$date_format}',
            dateFormat: 'Y-m-d',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons
            weekNumbers: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale_language}", "{$locale_region}"),
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
            return (int) $p['rand'];
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
        $output   = "<input type='color' id='" . htmlescape($field_id) . "' name='" . htmlescape($name) . "' value='" . htmlescape($p['value']) . "'>";

        if ($p['display']) {
            echo $output;
            return (int) $p['rand'];
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
            [$date_value, $hour_value] = explode(' ', $p['value']);
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

        $required = $p['required'] ? " required='required'" : "";
        $disabled = !$p['canedit'] ? " disabled='disabled'" : "";
        $clear    = $p['maybeempty'] && $p['canedit']
         ? "<button type='button' class='btn btn-outline-secondary btn-sm' data-toggle title='" . __s('Clear') . "'>
                    <i class='ti ti-circle-x' data-clear></i>
                </button>"
         : "";

        $name = htmlescape($name);
        $value = htmlescape($p['value']);
        $show_datepicker_label = __s('Show date picker');
        $rand = (int) $p['rand'];
        $output = <<<HTML
         <div class="btn-group flex-grow-1 flatpickr" id="showdate{$rand}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{$show_datepicker_label}">
            <input type="text" name="{$name}" value="{$value}"
                   {$required} {$disabled} data-input class="form-control rounded-start ps-2">
            <button type='button' class='btn btn-outline-secondary btn-sm' data-toggle>
                <i class='ti ti-calendar-time'></i>
            </button>
            $clear
         </div>
HTML;

        $date_format = Toolbox::getDateFormat('js') . " H:i:S";

        $min_attr = !empty($p['mindate']) ? sprintf("minDate: '%s',", jsescape($p['mindate'])) : "";
        $max_attr = !empty($p['maxdate']) ? sprintf("maxDate: '%s',", jsescape($p['maxdate'])) : "";
        $timestep = (int) $p['timestep'];

        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $locale_language = jsescape($locale['language']);
        $locale_region   = jsescape($locale['region']);
        $js = <<<JS
      $(function() {
         $("#showdate{$rand}").flatpickr({
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: "{$date_format}",
            dateFormat: 'Y-m-d H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            enableSeconds: true,
            weekNumbers: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale_language}", "{$locale_region}"),
            minuteIncrement: $timestep,
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
            return (int) $p['rand'];
        }
        return $output;
    }

    /**
     * Show generic date search
     *
     * @param string $element  name of the html element
     * @param string $value    default value
     * @param array $options   Array of possible options:
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
        global $CFG_GLPI;

        $p = [
            'with_time'   => false,
            'with_future' => false,
            'with_days'   => true,
            'with_specific_date' => true,
            'display'     => true,
        ];

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
        $output    .= "<table><tr><td>";

        $dates      = Html::getGenericDateTimeSearchItems($p);

        $output    .= Dropdown::showFromArray(
            "_select_$element",
            $dates,
            ['value'   => $value,
                'display' => false,
                'rand'    => $rand,
            ]
        );
        $field_id   = Html::cleanId("dropdown__select_$element$rand");

        $output    .= "</td><td>";
        $contentid  = Html::cleanId("displaygenericdate$element$rand");
        $output    .= "<span id='" . htmlescape($contentid) . "'></span>";

        $params     = ['value'         => '__VALUE__',
            'name'          => $element,
            'withtime'      => $p['with_time'],
            'specificvalue' => $specific_value,
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
            $month  = (int) date("n", $specifictime);
            $day    = 1;
            $year   = (int) date("Y", $specifictime);

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
            $month  = (int) date("n", strtotime($lastday));
            $day    = (int) date("j", strtotime($lastday));
            $year   = (int) date("Y", strtotime($lastday));

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
     *         The key should contain a string starting with the timestamp, it is used to order the displayed events
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
            'add_now' => true,
        ];
        $options = array_merge($default_options, $options);

        //append now date if needed
        if ($options['add_now']) {
            $now = time();
            $options['dates'][$now . "_now"] = [
                'timestamp' => $now,
                'label' => __('Now'),
                'class' => 'now',
            ];
        }

        ksort($options['dates']);

        // format dates
        foreach ($options['dates'] as &$data) {
            $data['date'] = $data['timestamp'] !== null
                ? date("Y-m-d H:i:s", $data['timestamp'])
                : null;
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
     * @param string $content  data to put in the tooltip
     * @param array{
     *   applyto?: string,       // id of the target element
     *   title?: string,         // title to display
     *   contentid?: string,     // id of the HTML container for the content
     *   link?: string,          // link on the displayed icon if contentid is empty
     *   linkid?: string,        // HTML id of the link
     *   linktarget?: string,    // link target
     *   awesome-class?: string, // class of the icon to display (default 'fa-info')
     *   popup?: string,         // popup action
     *   img?: string,           // URL of a specific image
     *   display?: bool,         // display or return the data, default true
     *   autoclose?: bool,       // auto close (default true)
     *   onclick?: bool,         // false (default) to show on hover, true to show on click
     *   url?: string|null       // AJAX URL to load the tooltip
     * } $options
     *
     * @return void|string
     *    void if option display=true
     *    string if option display=false (HTML code)
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     * @psalm-taint-sink html $content (string will be added to HTML source)
     */
    public static function showToolTip($content, $options = [])
    {
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
                $out .= "<a id='" . (!empty($param['linkid']) ? htmlescape($param['linkid']) : "tooltiplink$rand") . "'
                        class='dropdown_tooltip " . htmlescape($param['link_class']) . "'";

                if (!empty($param['linktarget'])) {
                    $out .= " target='" . htmlescape($param['linktarget']) . "' ";
                }
                $out .= " href='" . htmlescape($param['link']) . "'";

                if (!empty($param['popup'])) {
                    $out .= " data-bs-toggle='modal' data-bs-target='#tooltippopup$rand' ";
                }
                $out .= '>';
            }
            if (isset($param['img'])) {
                //for compatibility. Use fontawesome instead.
                $out .= "<img id='tooltip$rand' src='" . htmlescape($param['img']) . "'>";
            } else {
                $class = htmlescape($param['awesome-class']);
                $out .= "<span id='tooltip$rand' class='fas {$class} fa-fw'></span>";
            }

            if (!empty($param['link'])) {
                $out .= "</a>";
            }

            $param['applyto'] = (!empty($param['link']) && !empty($param['linkid'])) ? $param['linkid'] : "tooltip$rand";
        }

        if (empty($param['contentid'])) {
            $param['contentid'] = "content" . $param['applyto'];
        }

        $out .= "<div id='" . htmlescape($param['contentid']) . "' class='tooltip-invisible'>$content</div>";
        if (!empty($param['popup'])) {
            $out .= Ajax::createIframeModalWindow(
                'tooltippopup' . $rand,
                $param['popup'],
                ['display' => false,
                    'width'   => 600,
                    'height'  => 300,
                ]
            );
        }


        $js = "$(function(){";
        $js .= "$('#" . jsescape($param['applyto']) . "').qtip({
         position: { viewport: $(window) },
         content: {";
        if (!is_null($param['url'])) {
            $js .= "
                ajax: {
                    url: '" . jsescape($CFG_GLPI['root_doc'] . $param['url']) . "',
                    type: 'GET',
                    data: {},
                },
            ";
        }

        $js .= "text: $('#" . jsescape($param['contentid']) . "')";
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
        } elseif (!$param['autoclose']) {
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
     * Init the Editor System to a textarea
     *
     * @param string  $id               id of the html textarea to use
     * @param string  $rand             rand of the html textarea to use (if empty no image paste system)(default '')
     * @param boolean $display          display or get js script (true by default)
     * @param boolean $readonly         editor will be readonly or not
     * @param boolean $enable_images    enable image pasting in rich text
     * @param int     $editor_height    editor default height
     * @param array   $add_body_classes tinymce iframe's body classes
     * @param bool    $toolbar          tinymce toolbar (default: true)
     * @param string  $toolbar_location tinymce toolbar location (default: top)
     * @param bool    $init             init the editor (default: true)
     * @param string  $placeholder      textarea placeholder
     * @param bool    $statusbar        tinymce statusbar (default: true)
     * @param string  $content_style    content style to apply to the editor
     *
     * @return void|string
     *    integer if param display=true
     *    string if param display=false (HTML code)
     **/
    public static function initEditorSystem(
        $id,
        $rand = '',
        $display = true,
        $readonly = false,
        $enable_images = true,
        int $editor_height = 150,
        array $add_body_classes = [],
        string $toolbar_location = 'top',
        bool $init = true,
        string $placeholder = '',
        bool $toolbar = true,
        bool $statusbar = true,
        string $content_style = '',
        bool $init_on_demand = false
    ) {
        global $CFG_GLPI, $DB;

        $language = $_SESSION['glpilanguage'];
        if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
            $language = $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2];
            if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
                $language = "en_GB";
            }
        }
        $language_url = $CFG_GLPI['root_doc'] . '/lib/tinymce-i18n/langs6/' . $language . '.js';

        // Apply all GLPI styles to editor content
        $theme = ThemeManager::getInstance()->getCurrentTheme();
        $content_css_paths = [
            'css/glpi.scss',
            'css/core_palettes.scss',
        ];
        if ($theme->isCustomTheme()) {
            $content_css_paths[] = $theme->getPath();
        }
        $content_css = preg_replace('/^.*href="([^"]+)".*$/', '$1', self::css('lib/base.css', ['force_no_version' => true]));
        $content_css .= ',' . preg_replace('/^.*href="([^"]+)".*$/', '$1', self::css('lib/tabler.css', ['force_no_version' => true]));
        $content_css .= ',' . implode(',', array_map(static fn($path) => preg_replace('/^.*href="([^"]+)".*$/', '$1', self::scss($path, ['force_no_version' => true])), $content_css_paths));
        // Fix & encoding so it can be loaded as expected in debug mode
        $content_css = str_replace('&amp;', '&', $content_css);
        $skin_url = preg_replace('/^.*href="([^"]+)".*$/', '$1', self::css('css/tinymce_empty_skin', ['force_no_version' => true], false));

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
            'autolink',
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
                'language_url' => $language_url,
            ]);
        }

        $mandatory_field_msg = jsescape(__('The %s field is mandatory'));

        // Add custom classes to tinymce body
        $body_class = "rich_text_container";
        foreach ($add_body_classes as $class) {
            $body_class .= " $class";
        }

        // Compute init option as "string boolean" so it can be inserted directly into the js output
        $init = $init ? 'true' : 'false';

        // Compute init_on_demand option as "string boolean" so it can be inserted directly into the js output
        $init_on_demand = $init_on_demand ? 'true' : 'false';

        // Compute toolbar option as "string boolean" so it can be inserted directly into the js output
        $toolbar = $toolbar ? 'true' : 'false';

        // Compute statusbar option as "string boolean" so it can be inserted directly into the js output
        $statusbar = $statusbar ? 'true' : 'false';

        // Sanitize/escape values
        $id = self::sanitizeDomId($id);
        $skin_url = jsescape($skin_url);
        $body_class = jsescape($body_class);
        $content_css = jsescape($content_css);
        $content_style = jsescape($content_style);
        $placeholder = jsescape($placeholder);
        $toolbar_location = jsescape($toolbar_location);
        $cache_suffix = jsescape($cache_suffix);

        $js = <<<JS
            $(function() {
                const html_el = $('html');
                var richtext_layout = "{$_SESSION['glpirichtext_layout']}";

                // Store config in global var so the editor can be reinitialized from the client side if needed
                tinymce_editor_configs['{$id}'] = Object.assign({
                    license_key: 'gpl',

                    link_default_target: '_blank',
                    branding: false,
                    selector: '#' + $.escapeSelector('{$id}'),
                    text_patterns: false,
                    paste_webkit_styles: 'all',

                    plugins: {$pluginsjs},

                    // Appearance
                    skin_url: '{$skin_url}', // Doesn't matter which skin is used. We include the proper skins in the core GLPI styles.
                    body_class: '{$body_class}',
                    content_css: '{$content_css}',
                    content_style: '{$content_style}',
                    highlight_on_focus: false,
                    autoresize_bottom_margin: 1, // Avoid excessive bottom padding
                    autoresize_overflow_padding: 0,

                    min_height: $editor_height,
                    height: $editor_height, // Must be used with min_height to prevent "height jump" when the page is loaded
                    resize: true,

                    // disable path indicator in bottom bar
                    elementpath: false,

                    placeholder: "{$placeholder}",

                    // inline toolbar configuration
                    menubar: false,
                    toolbar_location: '{$toolbar_location}',
                    toolbar: {$toolbar} && richtext_layout == 'classic'
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

                    // Status bar configuration
                    statusbar: {$statusbar},

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

                    init_instance_callback: (editor) => {
                        const page_root_el = $(document.documentElement);
                        const root_el = $(editor.dom.doc.documentElement);
                        // Copy data-glpi-theme and data-glpi-theme-dark from page html element to editor root element
                        const to_copy = ['data-glpi-theme', 'data-glpi-theme-dark'];
                        for (const attr of to_copy) {
                            if (page_root_el.attr(attr) !== undefined) {
                                root_el.attr(attr, page_root_el.attr(attr));
                            }
                        }
                    },
                    setup: function(editor) {
                        // "required" state handling
                        if ($('#$id').attr('required') == 'required') {
                            $('#$id').removeAttr('required'); // Necessary to bypass browser validation

                            editor.on('submit', function (e) {
                                if ($('#$id').val() == '') {
                                    const field = $('#$id').closest('.form-field').find('label').text().replace('*', '').trim();
                                    alert('{$mandatory_field_msg}'.replace('%s', field));
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
                        // Propagate click event to allow other components to
                        // listen to it
                        editor.on('click', function (e) {
                            $(document).trigger('tinyMCEClick', [e]);
                        });

                        // Simulate focus on content-editable tinymce
                        editor.on('click focus', function (e) {
                            // Some focus events don't have the correct target and cant be handled
                            if (!$(e.target.editorContainer).length) {
                                return;
                            }

                            // Clear focus on other editors
                            $('.simulate-focus').removeClass('simulate-focus');

                            // Simulate input focus on our current editor
                            $(e.target.editorContainer)
                                .closest('.content-editable-tinymce')
                                .addClass('simulate-focus');
                        });

                        editor.on('Change', function (e) {
                            // Nothing fancy here. Since this is only used for tracking unsaved changes,
                            // we want to keep the logic in common.js with the other form input events.
                            onTinyMCEChange(e);

                            // Propagate event to the document to allow other components to listen to it
                            $(document).trigger('tinyMCEChange', [e]);
                        });

                        editor.on('input', function (e) {
                            // Propagate event to allow other components to listen to it
                            const textarea = $('#' + e.target.dataset.id);
                            textarea.trigger('tinyMCEInput', [e]);
                        });

                        // ctrl + enter submit the parent form
                        editor.addShortcut('ctrl+13', 'submit', function() {
                            editor.save();
                            submitparentForm($('#$id'));
                        });
                    }
                }, {$language_opts});

                // Init tinymce
                if ({$init}) {
                    tinyMCE.init(tinymce_editor_configs['{$id}']);
                }

                if ({$init_on_demand}) {
                    const textarea = $('#{$id}');
                    const div = $(`<div role="textbox" tabindex="0" class="text-muted text-break" data-glpi-tinymce-init-on-demand-render="{$id}">\${textarea.val() || textarea.attr('placeholder') || ''}</div>`);
                    textarea.after(div).hide();
                }
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
     * @param string $selector Selector of the textarea to activate autocompletion for
     * @param array  $values   Array of values to use for autocompletion
     *
     * @return void
     *
     * @since 10.0.0
     */
    public static function activateUserTemplateAutocompletion(string $selector, array $values): void
    {
        $selector = jsescape($selector);
        $values   = json_encode($values);

        echo Html::scriptBlock(
            <<<JAVASCRIPT
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
     * @param string $preset_target Preset of parameters for which to show documentation (key)
     * @param string|null $link_id  Useful if you need to interract with the link through client side code
     */
    public static function addTemplateDocumentationLink(
        string $preset_target,
        ?string $link_id = null
    ) {
        global $CFG_GLPI;

        $url = "/front/contenttemplates/documentation.php?preset=$preset_target";
        $link =  $CFG_GLPI['root_doc'] . $url;

        echo sprintf(
            '<a href="%1$s" %2$s target="_blank" style="margin-top:6px; display: block">%3$s <i class="ti ti-help"></i></a>',
            htmlescape($link),
            !is_null($link_id) ? sprintf('id="%s"', htmlescape($link_id)) : '',
            __s('Available variables')
        );
    }

    /**
     * Insert an html link to the twig template variables documentation page and
     * move it before the given textarea.
     * Useful if you don't have access to the form where you want to put this link at
     *
     * @param string $selector JQuery selector to find the target textarea
     * @param string $preset_target   Preset of parameters for which to show documentation (key)
     */
    public static function addTemplateDocumentationLinkJS(
        string $selector,
        string $preset_target
    ) {
        $link_id = "template_documentation_" . mt_rand();
        self::addTemplateDocumentationLink($preset_target, $link_id);

        $selector = jsescape($selector);

        // Move link before the given textarea
        echo Html::scriptBlock(
            <<<JAVASCRIPT
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
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     * @psalm-taint-sink html $additional_info (string will be added to HTML source)
     *
     * @TODO Deprecate $additional_info, $display and $additional_params params in GLPI 11.1, they are not used.
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

        if (!empty($additional_params) && !str_starts_with($additional_params, '&')) {
            $additional_params = '&' . $additional_params;
        }

        $out = '';
        // Print it
        $out .= "<div><table class='tab_cadre_pager'>";
        if (!empty($title)) {
            $out .= "<tr><th colspan='6'>" . htmlescape($title) . "</th></tr>";
        }
        $out .= "<tr>\n";

        // Back and fast backward button
        if (!$start == 0) {
            $out .= "<th class='left'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=0" . htmlescape(jsescape($additional_params)) . "\");'>
                     <i class='ti ti-chevrons-left' title=\"" . __s('Start') . "\"></i></a></th>";
            $out .= "<th class='left'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$back" . htmlescape(jsescape($additional_params)) . "\");'>
                     <i class='ti ti-chevron-left' title=\"" . __s('Previous') . "\"></i></a></th>";
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
        $out .= sprintf(__s('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
        $out .= "</td>\n";

        // Forward and fast forward button
        if ($forward < $numrows) {
            $out .= "<th class='right'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$forward" . htmlescape(jsescape($additional_params)) . "\");'>
                     <i class='ti ti-chevron-right' title=\"" . __s('Next') . "\"></i></a></th>";
            $out .= "<th class='right'><a class='btn btn-sm btn-icon btn-ghost-secondary' href='javascript:reloadTab(\"start=$end" . htmlescape(jsescape($additional_params)) . "\");'>
                     <i class='ti ti-chevrons-right' title=\"" . __s('End') . "\"></i></a></th>";
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
                echo "<tr><td>";
                echo htmlescape($key);
                echo "</td><td>";
                $is_array = is_array($val);
                $rand     = mt_rand();
                if ($jsexpand && $is_array) {
                    echo "<a href=\"javascript:showHideDiv('content" . htmlescape(jsescape($key . $rand)) . "','','','')\">";
                    echo "=></a>";
                } else {
                    echo "=>";
                }
                echo "</td><td>";

                if ($is_array) {
                    echo "<div id='content" . htmlescape($key . $rand) . "' " . ($jsexpand ? "style=\"display:none;\"" : '') . ">";
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
                                echo htmlescape((string) $val);
                            } else {
                                echo htmlescape("(object) " . get_class($val));
                            }
                        } else {
                            echo htmlescape($val);
                        }
                    }
                }
                echo "</td></tr>";
            }
            echo "</table>";
        } else {
            echo __s('Empty array');
        }
    }



    /**
     * Print pager for search option (first/previous/next/last)
     *
     * @param integer        $start                   from witch item we start
     * @param integer        $numrows                 total items
     * @param string         $target                  page would be open when click on the option (last,previous etc)
     * @param string         $parameters              parameters would be passed on the URL.
     * @param integer|string $item_type_output        item type display - if >0 display export
     * @param integer|array  $item_type_output_param  item type parameter for export
     * @param string         $additional_info         Additional information to display (default '')
     *
     * @return void
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     * @psalm-taint-sink html $additional_info (string will be added to HTML source)
     *
     * @TODO Deprecate $additional_info param in GLPI 11.1, it is not used.
     * @TODO Accept an array of key/values in the $parameters param to ease its usage/escaping.
     */
    public static function printPager(
        $start,
        $numrows,
        $target,
        $parameters,
        $item_type_output = 0,
        $item_type_output_param = 0,
        $additional_info = ''
    ) {
        global $CFG_GLPI;

        $start      = (int) $start;
        $numrows    = (int) $numrows;
        $list_limit = (int) $_SESSION['glpilist_limit'];

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

        if (!str_contains($target, '?')) {
            $fulltarget = $target . "?" . $parameters;
        } else {
            $fulltarget = $target . "&" . $parameters;
        }
        // Back and fast backward button
        if (!$start == 0) {
            echo "<th class='left'>";
            echo "<a href='" . htmlescape($fulltarget) . "&amp;start=0' class='btn btn-sm btn-ghost-secondary me-2'
                  title=\"" . __s('Start') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='ti ti-chevrons-left'></i>";
            echo "</a>";
            echo "<a href='" . htmlescape($fulltarget) . "&amp;start=$back' class='btn btn-sm btn-ghost-secondary me-2'
                  title=\"" . __s('Previous') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='ti ti-chevron-left'></i>";
            echo "</a></th>";
        }

        // Print the "where am I?"
        echo "<td width='31%' class='tab_bg_2'>";
        self::printPagerForm("$fulltarget&start=$start");
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
            && $numrows > 0
        ) {
            echo "<td class='tab_bg_2 responsive_hidden' width='30%'>";
            echo "<form method='GET' action='" . htmlescape($CFG_GLPI["root_doc"]) . "/front/report.dynamic.php'>";
            echo Html::hidden('item_type', ['value' => $item_type_output]);

            if (is_array($item_type_output_param)) {
                echo Html::hidden(
                    'item_type_param',
                    ['value' => Toolbox::prepareArrayForInput($item_type_output_param)]
                );
            }

            $parameters = trim($parameters, '&');
            if (!str_contains($parameters, 'start')) {
                $parameters .= "&start=$start";
            }

            $split = explode("&", $parameters);

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
        printf(__s('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
        echo "</td>";

        // Forward and fast forward button
        if ($forward < $numrows) {
            echo "<th class='right'>";
            echo "<a href='" . htmlescape($fulltarget) . "&amp;start=$forward' class='btn btn-sm btn-ghost-secondary'
                  title=\"" . __s('Next') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>
               <i class='ti ti-chevron-right'></i>";
            echo "</a>";
            echo "<a href='" . htmlescape($fulltarget) . "&amp;start=$end' class='btn btn-sm btn-ghost-secondary'
                  title=\"" . __s('End') . "\" data-bs-toggle='tooltip' data-bs-placement='top'>";
            echo "<i class='ti ti-chevrons-right'></i>";
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

        if (!empty($additional_params) && !str_starts_with($additional_params, '&')) {
            $additional_params = '&' . $additional_params;
        }

        $out = '';
        if ($action) {
            $out .= "<form method='POST' action=\"" . htmlescape($action) . "\">";
            $out .= "<span class='responsive_hidden'>" . __s('Display (number of items)') . "</span>&nbsp;";
            $out .= Dropdown::showListLimit("submit()", false);
        } else {
            $out .= "<form method='POST' action =''>\n";
            $out .= "<span class='responsive_hidden'>" . __s('Display (number of items)') . "</span>&nbsp;";
            $out .= Dropdown::showListLimit("reloadTab(\"glpilist_limit=\"+this.value+\"" . jsescape($additional_params) . "\")", false);
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
     * @param string $string Text for title
     * @param integer $num   Number of item displayed
     * @param integer $tot   Number of item existing
     *
     * @since 0.83.1
     *
     * @return string
     **/
    public static function makeTitle($string, $num, $tot)
    {
        if (($num > 0) && ($num < $tot)) {
            // TRANS %1$d %2$d are numbers (displayed, total)
            $cpt = "<span class='primary-bg primary-fg count'>"
            . htmlescape(sprintf(__('%1$d on %2$d'), $num, $tot)) . "</span>";
        } else {
            // $num is 0, so means configured to display nothing
            // or $num == $tot
            $cpt = "<span class='primary-bg primary-fg count'>" . htmlescape($tot) . "</span>";
        }
        return sprintf(__s('%1$s %2$s'), htmlescape($string), $cpt);
    }


    /**
     * create a minimal form for simple action
     *
     * @param string       $action   URL to call on submit
     * @param string|array $btname   Button name (maybe if name <> value)
     * @param string       $btlabel  Button label
     * @param array        $fields   Field name => field  value
     * @param string       $btimage  Button image uri (optional)   (default '')
     *                           If image name starts with "fa-" or "ti-", it will be turned into
     *                           a FontAwesone/Tabler icon rather than an image.
     * @param string       $btoption Optional button option        (default '')
     * @param string|array $confirm  Optional confirm message      (default '')
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

        $fields['_glpi_csrf_token'] = Session::getNewCSRFToken();
        $fields['_glpi_simple_form'] = 1;
        $button                      = $btname;
        if (!is_array($btname)) {
            $button          = [];
            $button[$btname] = $btname;
        }
        $fields          = array_merge($button, $fields);
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
        $action  = " submitGetLink('" . jsescape($action) . "', " . json_encode($fields) . ");";

        if (is_array($confirm) || strlen($confirm)) {
            $link .= self::addConfirmationOnAction($confirm, $action);
        } else {
            $link .= " onclick=\"" . htmlescape($action) . "\" ";
        }

        // Ensure $btlabel is properly escaped
        $btlabel = htmlescape($btlabel);
        $btimage = htmlescape($btimage);
        $link .= '>';
        if (empty($btimage)) {
            $link .= $btlabel;
        } else {
            if (str_starts_with($btimage, 'fa-')) {
                $link .= "<span class='fas $btimage' title='$btlabel'><span class='sr-only'>$btlabel</span>";
            } elseif (str_starts_with($btimage, 'ti-')) {
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
     * @param string       $action   URL to call on submit
     * @param string       $btname   Button name
     * @param string       $btlabel  Button label
     * @param array        $fields   Field name => field  value
     * @param string       $btimage  Button image uri (optional) (default '')
     * @param string       $btoption Optional button option (default '')
     * @param string|array $confirm  Optional confirm message (default '')
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
     * @param boolean $display Display or return string (default true)
     *
     * @since 0.83.
     *
     * @return string|true
     * @phpstan-return ($display is true ? true : string)
     **/
    public static function closeForm($display = true)
    {
        $out = Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        $out .= "</form>";
        if ($display) {
            echo $out;
            return true;
        }
        return $out;
    }

    /**
     * Clean ID used for HTML elements
     *
     * @param string $id ID of the dom element
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
     * @param string $id ID of the dom element
     *
     * @since 0.85.
     *
     * @return string
     *
     * @deprecated 11.0.0
     **/
    public static function jsGetElementbyID($id)
    {
        Toolbox::deprecated();

        $id = jsescape($id);

        return "$('#$id')";
    }

    /**
     * Set dropdown value
     *
     * @param string $id      ID of the dom element
     * @param string $value   Value to set
     *
     * @since 0.85.
     *
     * @return string
     *
     * @deprecated 11.0.0
     **/
    public static function jsSetDropdownValue($id, $value)
    {
        Toolbox::deprecated();

        $value = jsescape($value);

        return self::jsGetElementbyID($id) . ".trigger('setValue', '$value');";
    }

    /**
     * Get item value
     *
     * @param string $id  ID of the dom element
     *
     * @since 0.85.
     *
     * @return string
     *
     * @deprecated 11.0.0
     **/
    public static function jsGetDropdownValue($id)
    {
        Toolbox::deprecated();

        return self::jsGetElementbyID($id) . ".val()";
    }


    /**
     * Adapt dropdown to clean JS
     *
     * @param string $id     ID of the dom element
     * @param array $params  Array of parameters
     *
     * @since 0.85.
     *
     * @return string
     *
     * @TODO In GLPI 12.0 (BC-break), allow only values that matches the `^\w+$` pattern (i.e. a function name) for the following parameters:
     *       `templateResult`, `templateSelection`.
     */
    public static function jsAdaptDropdown($id, $params = [])
    {
        global $CFG_GLPI;

        $width = '';
        if (!empty($params["width"])) {
            $width = $params["width"];
            unset($params["width"]);
        }

        $dropdown_css_class  = $params["dropdownCssClass"] ?? '';
        $placeholder         = $params["placeholder"] ?? '';
        $ajax_limit_count    = (int) $CFG_GLPI['ajax_limit_count'];
        $templateresult      = $params["templateResult"] ?? "templateResult";
        $templateselection   = $params["templateSelection"] ?? "templateSelection";

        // escape values for JS
        $id = jsescape($id);
        $width = jsescape($width);
        $dropdown_css_class = jsescape($dropdown_css_class);
        $placeholder = jsescape($placeholder);

        $js = <<<JS
            select2_configs['{$id}'] = {
                type: 'adapt',
                field_id: '{$id}',
                width: '{$width}',
                dropdown_css_class: '{$dropdown_css_class}',
                placeholder: '{$placeholder}',
                ajax_limit_count: {$ajax_limit_count},
                templateresult: {$templateresult},
                templateselection: {$templateselection},
            };
JS;

        if ($params['init'] ?? true) {
            $js .= "setupAdaptDropdown(window.select2_configs['{$id}']);";
        }

        return Html::scriptBlock($js);
    }


    /**
     * Create Ajax dropdown to clean JS
     *
     * @param string $name
     * @param string $field_id  ID of the dom element
     * @param string $url       URL to get datas
     * @param array $params     Array of parameters, see $default_options below
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
     *
     * @TODO In GLPI 12.0 (BC-break), allow only values that matches the `^\w+$` pattern (i.e. a function name) for the following parameters:
     *        `on_change`, `templateResult`, `templateSelection`.
     **/
    public static function jsAjaxDropdown($name, $field_id, $url, $params = [])
    {
        global $CFG_GLPI;

        $default_options = [
            'init'                => true,
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
            'templateResult'      => 'templateResult',
            'templateSelection'   => 'templateSelection',
            'container_css_class' => '',
            'aria_label'          => '',
            'required'            => false,
        ];
        $params = array_merge($default_options, $params);

        $value = $params['value'];
        $width = $params["width"];
        $valuename = $params['valuename'];
        $on_change = $params["on_change"];
        $placeholder = $params['placeholder'] ?? '';
        $multiple = $params['multiple'];
        $templateResult = $params['templateResult'];
        $templateSelection = $params['templateSelection'];
        $aria_label = $params['aria_label'];
        $emptyLabel = $params['emptylabel'] ?? '';

        unset($params["on_change"], $params["width"]);

        $allowclear =  "false";
        if ($placeholder !== '' && !$params['display_emptychoice']) {
            $allowclear = "true";
        }

        $options = [
            'id'        => $field_id,
            'selected'  => $value,
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

        unset($params['placeholder'], $params['value'], $params['valuename'], $params['parent_id_field']);

        if (!empty($aria_label)) {
            $params['specific_tags']['aria-label'] = $aria_label;
        }
        unset($params['aria_label']);

        foreach ($params['specific_tags'] as $tag => $val) {
            if (is_array($val)) {
                $val = implode(' ', $val);
            }
            $options[$tag] = $val;
        }

        $ajax_limit_count = (int) $CFG_GLPI['ajax_limit_count'];
        $dropdown_max     = (int) $CFG_GLPI['dropdown_max'];

        $output = '';

        // Escape variables for JS
        foreach ($params as $key => $val) {
            // Specific boolean case: cast them to integer to prevent issues when passing `"false"` strings through
            // URL / form body.
            // see #21025
            if (is_bool($val)) {
                $params[$key] = $val ? 1 : 0;
            }
        }

        $field_id            = jsescape($field_id);
        $width               = jsescape($width);
        $multiple            = jsescape($multiple);
        $placeholder         = jsescape($placeholder);
        $url                 = jsescape($url);
        $parent_id_field     = jsescape($parent_id_field);
        $on_change           = json_encode($on_change); // will be executed with `eval()`, do not use jsescape to not alter JS tokens
        $container_css_class = jsescape($params['container_css_class']);
        $js_params           = json_encode($params);

        $js = <<<JS
            select2_configs['{$field_id}'] = {
                type: 'ajax',
                field_id: '{$field_id}',
                width: '{$width}',
                multiple: '{$multiple}',
                placeholder: '{$placeholder}',
                allowclear: {$allowclear},
                ajax_limit_count: {$ajax_limit_count},
                dropdown_max: {$dropdown_max},
                url: '{$url}',
                parent_id_field: '{$parent_id_field}',
                on_change: {$on_change},
                templateResult: {$templateResult},
                templateSelection: {$templateSelection},
                container_css_class: '{$container_css_class}',
                params: {$js_params}
            };
JS;

        if ($params['init']) {
            $js .= "setupAjaxDropdown(window.select2_configs['{$field_id}']);";
        }


        // display select tag
        $options['class'] = $params['class'] ?? 'form-select';

        if ((bool) $params['required'] === true) {
            $options['required'] = 'required';

            if (!empty($emptyLabel)) {
                $selectVarName = "select_" . mt_rand();
                $formVarName = "form_" . mt_rand();
                $jsEmptyLabel = jsescape($emptyLabel);
                $errorMessage = jsescape(__('This field is mandatory'));

                $js .= <<<JS
                    const $selectVarName = document.getElementById('{$field_id}');
                    const $formVarName = $selectVarName.closest('form');
                    if ($formVarName) {
                        $formVarName.addEventListener("submit", (evt) => {
                            if ($selectVarName.options[$selectVarName.selectedIndex].text === '$jsEmptyLabel') {
                                $selectVarName.setCustomValidity('$errorMessage');
                                $selectVarName.reportValidity();

                                // Error, we stop the form from submitting
                                evt.preventDefault();
                                evt.stopPropagation();
                            }
                        });

                        \$('#$field_id').on('change', function (e) {
                          $selectVarName.setCustomValidity('');
                        });

                        // Make sure the hidden <select> has the same size than the select2 container, to display the error message at a correct position
                        $formVarName.addEventListener('invalid', function (event) {
                          const element = event.target;

                          if (element.classList.contains('select2-hidden-accessible')) {
                            const select2Container = element.nextElementSibling;

                            element.style.setProperty("height", `\${select2Container.offsetHeight}px`, "important");
                            element.style.setProperty("width", `\${select2Container.offsetWidth}px`, "important");
                          }
                        }, true); // Use capture phase because 'invalid' events do not bubble

                    }
JS;
            }
        }

        $output .= Html::scriptBlock('$(function() {' . $js . '});');
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

        if ($url && !array_key_exists('class', $options)) {
            $options['class'] = 'pointer';
        }

        $image = sprintf('<img src="%1$s" %2$s />', htmlescape($path), Html::parseAttributes($options));
        if ($url) {
            return sprintf(
                '<a href="%1$s">%2$s</a>',
                htmlescape($url),
                $image
            );
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
     *
     * @TODO Deprecate this method in GLPI 11.1, it is not used anymore in GLPI itself.
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

        $text = htmlescape($text);

        return sprintf(
            '<a href="%1$s" %2$s>%3$s</a>',
            htmlescape($url),
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
            htmlescape($fieldName),
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
            htmlescape($type),
            htmlescape($fieldName),
            Html::parseAttributes($options)
        );
    }

    /**
     * Creates a select tag
     *
     * @since 9.3
     *
     * @param string $name     Name of the field
     * @param array  $values   Array of the options
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
        $select = '';
        if (isset($options['multiple']) && $options['multiple']) {
            $input_options = [];
            if (isset($options['disabled'])) {
                $input_options['disabled'] = $options['disabled'];
            }

            $original_field_name = str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;

            $select .= sprintf(
                '<input type="hidden" name="%1$s" value="" %2$s>',
                htmlescape($original_field_name),
                self::parseAttributes($input_options)
            );
        }
        $select .= sprintf(
            '<select name="%1$s" %2$s>',
            htmlescape($name),
            self::parseAttributes($options)
        );
        foreach ($values as $key => $value) {
            $select .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                htmlescape($key),
                $selected != false && ($key == $selected || (is_array($selected) && in_array($key, $selected)))
                    ? ' selected="selected"'
                    : '',
                htmlescape($value)
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
                htmlescape($image),
                Html::parseAttributes($options)
            );
        }

        $icon = "";
        if (isset($options['icon'])) {
            $icon = sprintf('<i class="%s"></i>&nbsp;', htmlescape($options['icon']));
            unset($options['icon']);
        }

        return sprintf(
            '<button type="submit" value="%s" %s>%s<span>%s</span></button>',
            htmlescape($caption),
            Html::parseAttributes($options),
            $icon,
            htmlescape($caption)
        );
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
        $max   = (int) $max;
        $value = (int) $value;

        $p = [
            'rand'            => mt_rand(),
            'tooltip'         => '',
            'append_percent'  => true,
        ];
        $p = array_replace($p, $params);

        $tooltip = trim($p['tooltip'] . ($p['append_percent'] ? " {$value}%" : ''));
        $calcWidth = ($value / $max) * 100;

        // escape variables for HTML
        $rand           = (int) $p['rand'];
        $append_percent = htmlescape($p['append_percent']);
        $tooltip        = htmlescape($tooltip);

        $html = <<<HTML
         <div class="progress" style="height: 12px"
              id="progress{$rand}"
              data-progressid="progress{$rand}"
              data-append-percent="{$append_percent}"
              onchange="updateProgress('progress{$rand}')"
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
     * @param array $options Array of options.
     *
     * @return string Composed attributes.
     * @used-by templates/components/form/fields_macros.html.twig
     * @used-by templates/components/form/buttons.html.twig
     **/
    public static function parseAttributes($options = [])
    {
        $attributes = [];

        foreach ($options as $key => $value) {
            $attributes[] = Html::formatAttribute($key, $value);
        }

        return implode(' ', $attributes);
    }


    /**
     * Formats an individual attribute, and returns the string value of the composed attribute.
     *
     * @since 0.85
     *
     * @param string $key    The name of the attribute to create
     * @param string|array $value  The value of the attribute to create.
     *
     * @return string The composed attribute.
     **/
    public static function formatAttribute($key, $value)
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        return sprintf('%1$s="%2$s"', htmlescape($key), htmlescape($value));
    }


    /**
     * Wrap $script in a script tag.
     *
     * @since 0.85
     *
     * @param string $script  The script to wrap
     *
     * @return string
     *
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
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

        $type = (isset($options['type']) && $options['type'] === 'module')
         || preg_match('/^js\/modules\//', $url) === 1 ? 'module' : 'text/javascript';

        if ($minify === true) {
            $url = self::getMiniFile($url);
        }

        $url = self::getPrefixedUrl($url);

        if ($version) {
            $url .= '?v=' . FrontEnd::getVersionCacheKey($version);
        }

        // Convert filesystem path to URL path (fix issues with Windows directory separator)
        $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

        return sprintf('<script type="%s" src="%s"></script>', htmlescape($type), htmlescape($url));
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
     * @param string  $url      File to include (relative to GLPI_ROOT)
     * @param array   $options  Array of HTML attributes
     *
     * @return string CSS link tag
     *
     * @since 11.0.0 The `$no_debug` parameter has bbeen removed.
     **/
    public static function scss($url, $options = [])
    {
        $prod_file = self::getScssCompilePath($url);

        if (
            file_exists($prod_file)
            && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE
        ) {
            $url = self::getPrefixedUrl(str_replace(GLPI_ROOT . '/public', '', $prod_file));
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

            $url .= ((str_contains($url, '?')) ? '&' : '?') . 'v=' . FrontEnd::getVersionCacheKey($version);
        }

        // Convert filesystem path to URL path (fix issues with Windows directory separator)
        $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

        return sprintf(
            '<link rel="stylesheet" type="text/css" href="%s" %s>',
            htmlescape($url),
            Html::parseAttributes($options)
        );
    }


    /**
     * Creates an input file field. Send file names in _$name field as array.
     * Files are uploaded in files/_tmp/ directory
     *
     * @since 9.2
     *
     * @param array $options    Array of options
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
        global $CFG_GLPI;

        $randupload = $options['rand'] ?? mt_rand();

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
            $display .= "<div class='fileupload draghoverable' id='" . htmlescape($p['dropZone']) . "'>";

            if ($p['showtitle']) {
                $display .= "<b>";
                $display .= htmlescape(sprintf(__('%1$s (%2$s)'), __('File(s)'), Document::getMaxUploadSize()));
                $display .= DocumentType::showAvailableTypesLink([
                    'display' => false,
                    'rand'    => $p['rand'],
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

        $max_file_size  = ((int) $CFG_GLPI['document_max_size']) * 1024 * 1024;
        $max_chunk_size = round(Toolbox::getPhpUploadSizeLimit() * 0.9); // keep some place for extra data

        $required = "";
        if ($p['required']) {
            $required = "required='required'";
        }

        // sanitize name and random id
        $name    = self::sanitizeInputName($p['name']);
        $rand_id = self::sanitizeDomId($p['rand']);

        if (!$p['only_uploaded_files']) {
            // manage file upload without tinymce editor
            $display .= "<span class='b'>" . __s('Drag and drop your file here, or') . '</span><br>';
        }
        $display .= "<input id='fileupload{$rand_id}' type='file' name='_uploader_{$name}[]'
                      class='form-control'
                      $required
                      data-uploader-name=\"" . htmlescape($p['name']) . "\"
                      data-url='" . htmlescape($CFG_GLPI["root_doc"]) . "/ajax/fileupload.php'
                      data-form-data='{\"name\": \"_uploader_{$name}\", \"showfilesize\": " . ($p['showfilesize'] ? 'true' : 'false') . "}'"
                      . ($p['multiple'] ? " multiple='multiple'" : "")
                      . ($p['onlyimages'] ? " accept='.gif,.png,.jpg,.jpeg'" : "") . ">";

        $display .= "<div id='progress{$rand_id}' style='display:none'>"
                . "<div role='progressbar' class='uploadbar' style='width: 0%;'></div></div>";
        $progressall_js = "
        progressall: function(event, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress{$rand_id}').show();
            $('#progress{$rand_id} .uploadbar')
                .text(progress + '%')
                .css('width', progress + '%')
                .show();
        },
        ";

        $display .= Html::scriptBlock("
      $(function() {
         var fileindex{$rand_id} = 0;
         $('#fileupload{$rand_id}').fileupload({
            dataType: 'json',
            pasteZone: " . ($p['pasteZone'] !== false
                           ? "$('#" . jsescape($p['pasteZone']) . "')"
                           : "false") . ",
            dropZone:  " . ($p['dropZone'] !== false
                           ? "$('#" . jsescape($p['dropZone']) . "')"
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
                  data.result._uploader_{$name}, // response from '/ajax/fileupload.php'
                  \"{$name}\",
                  $('#" . jsescape($p['filecontainer']) . "'),
                  '" . jsescape($p['editor_id']) . "'
               );
               // enable submit button after upload
               $(this).closest('form').find(':submit').prop('disabled', false);
               // remove required
                $('#fileupload{$rand_id}').removeAttr('required');
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
                        $('#progress{$rand_id}').show();
                        $('#progress{$rand_id} .uploadbar')
                           .text(file.error)
                           .css('width', '100%')
                           .show();
                        return;
                     }
                  }
               );
            },
            messages: {
              acceptFileTypes: '" . jsescape(__('Filetype not allowed')) . "',
              maxFileSize: '" . jsescape(__('File is too big')) . "',
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

        $rand = $options['rand'] ?? mt_rand();

        $p['name']              = 'text';
        $p['rand']              = $rand;
        $p['filecontainer']     = 'fileupload_info' . $rand;
        $p['editor_id']         = 'text' . $rand;
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
        $display .= "<textarea class='form-control' name='" . htmlescape($p['name']) . "' id='" . htmlescape($p['editor_id']) . "'
                             rows='" . ((int) $p['rows']) . "' cols='" . ((int) $p['cols']) . "' $required>"
                  . htmlescape($p['value']) . "</textarea>";

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
     * @param array $options  Array of options
     *    - name                string   field name (default filename)
     *    - filecontainer       string   DOM ID of the container showing file uploaded:
     *    - editor_id           string   id attribute for the textarea
     *    - display             bool     display or return the generated html
     *    - uploads             array    uploads to display (done in a previous form submit)
     * @return string|true   The html if display parameter is false
     */
    private static function uploadedFiles($options = [])
    {
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
        $display = "<div id='" . htmlescape($p['filecontainer']) . "' class='fileupload_info'>";
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
                $display .= "<p id='" . htmlescape($upload['id']) . "'>&nbsp;";
                $display .= "<img src='" . htmlescape($extensionIcon) . "' title='" . htmlescape($extension) . "'>&nbsp;";
                $display .= "<b>" . htmlescape($upload['display']) . "</b>&nbsp;(" . htmlescape(Toolbox::getSize($upload['size'])) . ")";

                $name = '_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $upload['name']]);

                $name = '_prefix_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $upload['prefix']]);

                $name = '_tag_' . $p['name'] . '[' . $uploadId . ']';
                $display .= Html::hidden($name, ['value' => $tag['name']]);

                // show button to delete the upload
                $getEditor = 'null';
                if ($p['editor_id'] != '') {
                    $getEditor = "tinymce.get('" . jsescape($p['editor_id']) . "')";
                }
                $textTag = json_encode($tag['tag']);
                $domItems = json_encode([
                    0 => $upload['id'],
                    1 => $upload['id'] . '2',
                ]);
                $deleteUpload = "deleteImagePasted({$domItems}, {$textTag}, {$getEditor})";
                $display .= '<button class="btn btn-icon btn-sm btn-link ti ti-circle-x" onclick="' . htmlescape($deleteUpload) . '"></span>';

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
     * @param array $columns  Array of column field name => column label
     * @param array $rows     Array of field name => array(
     *      'label' the label of the row
     *      'columns' an array of specific information regaring current row
     *                and given column indexed by column field_name
     *                 * a string if only have to display a string
     *                 * an array('value' => ???, 'readonly' => ???) that is used to Dropdown::showYesNo()
     * @param array $options Possible:
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

        if (count($options)) {
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
        foreach (array_keys($columns) as $col_name) {
            $nb_cb_per_col[$col_name] = [
                'total'   => 0,
                'checked' => 0,
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
                    'checked' => 0,
                ];

                foreach (array_keys($columns) as $col_name) {
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

        return (int) $param['rand'];
    }



    /**
     * This function provides a mechanism to send HTML form by ajax
     *
     * @param string $selector selector of a HTML form
     * @param string $success  JavaScript code of the success callback
     * @param string $error    JavaScript code of the error callback
     * @param string $complete JavaScript code of the complete callback
     *
     * @see https://api.jquery.com/jQuery.ajax/
     *
     * @since 9.1
     **/
    public static function ajaxForm($selector, $success = "console.log(html);", $error = "console.error(html)", $complete = '')
    {
        $selector = jsescape($selector);

        echo Html::scriptBlock(<<<JS
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
JS);
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
         caption = caption || '" . jsescape(_sn('Information', 'Information', 1)) . "';

         glpi_alert({
            title: caption,
            message: message,
         });
      };");
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
     * @param string $msg          Message to be shown
     * @param string $title        Title for dialog box
     * @param string $okCallback   Function that will be called when 'Ok' is pressed
     *                               (default null)
     **/
    public static function jsAlertCallback($msg, $title, $okCallback = null)
    {
        return "glpi_alert({
         title: '" . jsescape($title) . "',
         message: '" . jsescape($msg) . "',
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
                 . 'href="' . htmlescape($base_path . '/front/document.send.php?docid=' . $document_id . $more_link) . '" '
                 . 'target="_blank" '
                 . '>';
        }
        $out .= '<img ';
        if (isset($document->fields['tag'])) {
            $out .= 'alt="' . htmlescape($document->fields['tag']) . '" ';
        }
        $out .= 'width="' . ((int) $width) . '" '
              . 'src="' . htmlescape($base_path . '/front/document.send.php?docid=' . $document_id . $more_link) . '" '
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
            $message .= htmlescape(GLPI_VERSION) . " ";
        }
        $message .= "Copyright (C) 2015-" . htmlescape(GLPI_YEAR) . " Teclib' and contributors"
         . "</a>";
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
                $_SESSION['glpi_js_toload'][$name][] = 'lib/tinymce.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/FormTags.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/UserMention.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/ContentTemplatesParameters.js';
                break;
            case 'planning':
                $_SESSION['glpi_js_toload'][$name][] = 'js/planning.js';
                break;
            case 'flatpickr':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/flatpickr.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/flatpickr_buttons_plugin.js';
                if (isset($_SESSION['glpilanguage'])) {
                    $filename = "lib/flatpickr/l10n/"
                    . strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]) . ".js";
                    if (file_exists(GLPI_ROOT . '/public/' . $filename)) {
                        $_SESSION['glpi_js_toload'][$name][] = $filename;
                        break;
                    }
                }
                break;
            case 'fullcalendar':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/fullcalendar.js';
                if (isset($_SESSION['glpilanguage'])) {
                    foreach ([2, 3] as $loc) {
                        $filename = "lib/fullcalendar/core/locales/"
                         . strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][$loc]) . ".js";
                        if (file_exists(GLPI_ROOT . '/public/' . $filename)) {
                            $_SESSION['glpi_js_toload'][$name][] = $filename;
                            break;
                        }
                    }
                }
                break;
            case 'rateit':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/jquery.rateit.js';
                break;
            case 'fileupload':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/jquery-file-upload.js';
                $_SESSION['glpi_js_toload'][$name][] = 'js/fileupload.js';
                break;
            case 'charts':
                $_SESSION['glpi_js_toload']['charts'][] = 'lib/echarts.js';
                break;
            case 'notifications_ajax':
                $_SESSION['glpi_js_toload']['notifications_ajax'][] = 'js/notifications_ajax.js';
                break;
            case 'fuzzy':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/fuzzy.js';
                break;
            case 'marketplace':
                $_SESSION['glpi_js_toload'][$name][] = 'js/marketplace.js';
                break;
            case 'gridstack':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/gridstack.js';
                break;
            case 'masonry':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/masonry.js';
                break;
            case 'sortable':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/sortable.js';
                break;
            case 'rack':
                $_SESSION['glpi_js_toload'][$name][] = 'js/rack.js';
                break;
            case 'leaflet':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/leaflet.js';
                break;
            case 'log_filters':
                $_SESSION['glpi_js_toload'][$name][] = 'js/log_filters.js';
                break;
            case 'photoswipe':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/photoswipe.js';
                break;
            case 'reservations':
                $_SESSION['glpi_js_toload'][$name][] = 'js/reservations.js';
                break;
            case 'cable':
                $_SESSION['glpi_js_toload'][$name][] = 'js/cable.js';
                break;
            case 'altcha':
                $_SESSION['glpi_js_toload'][$name][] = 'lib/altcha.js';
                break;
            default:
                $found = false;
                if (isset($PLUGIN_HOOKS[Hooks::JAVASCRIPT][$name])) {
                    $found = true;
                    $jslibs = $PLUGIN_HOOKS[Hooks::JAVASCRIPT][$name];
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
        global $CFG_GLPI, $PLUGIN_HOOKS;

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
            $filename = "lib/select2/js/i18n/"
                     . $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2] . ".js";
            if (file_exists(GLPI_ROOT . '/public/' . $filename)) {
                echo Html::script($filename);
            }
        }

        // Some Javascript-Functions which we may need later
        self::redefineAlert();
        self::redefineConfirm();

        if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax'] && !Session::isImpersonateActive()) {
            $options = [
                'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ?: 5) * 1000,
                'sound'     => $CFG_GLPI['notifications_ajax_sound'] ?: false,
                'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
                'user_id'   => Session::getLoginUserID(),
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
                $version = Plugin::getPluginFilesVersion($plugin);
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    echo Html::script("/plugins/{$plugin}/{$file}", [
                        'version'   => $version,
                        'type'      => 'text/javascript',
                    ]);
                }
            }
        }

        if (isset($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_MODULE]) && count($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_MODULE])) {
            foreach ($PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_MODULE] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $version = Plugin::getPluginFilesVersion($plugin);
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    echo self::script("/plugins/{$plugin}/{$file}", [
                        'version'   => $version,
                        'type'      => 'module',
                    ]);
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
        global $CFG_GLPI;

        // prevent leak of data for non logged sessions
        $full = $full && (Session::getLoginUserID(true) !== false);

        $cfg_glpi = "var CFG_GLPI  = {
            'url_base': '" . jsescape((isset($CFG_GLPI['url_base']) ? $CFG_GLPI["url_base"] : '')) . "',
            'root_doc': '" . jsescape($CFG_GLPI["root_doc"]) . "',
        };";

        if ($full) {
            $cfg_glpi = "var CFG_GLPI  = " . json_encode(Config::getSafeConfig(true), JSON_PRETTY_PRINT) . ";";
        }

        $plugins_path = [];
        foreach (Plugin::getPlugins() as $key) {
            $plugins_path[$key] = "/plugins/{$key}";
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
         && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);

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
    final public static function getPrefixedUrl(string $url): string
    {
        global $CFG_GLPI;
        $prefix = $CFG_GLPI['root_doc'];
        if (!str_starts_with($url, '/')) {
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
        $timer = (int) $timer;

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
     * Get all options for the menu fuzzy search
     * @return array
     * @phpstan-return array<array{url: string, title: string}>
     * @since 11.0.0
     */
    public static function getMenuFuzzySearchList(): array
    {
        $fuzzy_entries = [];

        // retrieve menu
        foreach ($_SESSION['glpimenu'] as $firstlvl) {
            if (isset($firstlvl['default'])) {
                if (strlen($firstlvl['title']) > 0) {
                    $fuzzy_entries[] = [
                        'url'   => self::getPrefixedUrl($firstlvl['default']),
                        'title' => $firstlvl['title'],
                    ];
                }
            }

            if (isset($firstlvl['default_dashboard'])) {
                if (strlen($firstlvl['title']) > 0) {
                    $fuzzy_entries[] = [
                        'url'   => self::getPrefixedUrl($firstlvl['default_dashboard']),
                        'title' => $firstlvl['title'] . " > " . __('Dashboard'),
                    ];
                }
            }

            if (isset($firstlvl['content'])) {
                foreach ($firstlvl['content'] as $menu) {
                    if (isset($menu['title']) && strlen($menu['title']) > 0) {
                        $fuzzy_entries[] = [
                            'url'   => self::getPrefixedUrl($menu['page']),
                            'title' => $firstlvl['title'] . " > " . $menu['title'],
                        ];

                        if (isset($menu['options'])) {
                            foreach ($menu['options'] as $submenu) {
                                if (isset($submenu['title']) && strlen($submenu['title']) > 0) {
                                    $fuzzy_entries[] = [
                                        'url'   => self::getPrefixedUrl($submenu['page']),
                                        'title' => $firstlvl['title'] . " > "
                                            . $menu['title'] . " > "
                                            . $submenu['title'],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // return the entries to ajax call
        return $fuzzy_entries;
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
     * @param  boolean $sbw      default true, should we soft the black/white to a dark/light grey
     * @return string            the inverted color prefixed by #
     */
    public static function getInvertedColor($hexcolor = "", $bw = true, $sbw = true)
    {
        if (str_contains($hexcolor, '#')) {
            $hexcolor = trim($hexcolor, '#');
        }
        // convert 3-digit hex to 6-digits.
        if (strlen($hexcolor) === 3) {
            $hexcolor = $hexcolor[0] . $hexcolor[0]
                   . $hexcolor[1] . $hexcolor[1]
                   . $hexcolor[2] . $hexcolor[2];
        }
        if (strlen($hexcolor) != 6) {
            throw new Exception('Invalid HEX color.');
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
         . str_pad((string) $r, 2, '0', STR_PAD_LEFT)
         . str_pad((string) $g, 2, '0', STR_PAD_LEFT)
         . str_pad((string) $b, 2, '0', STR_PAD_LEFT);
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
        global $CFG_GLPI, $GLPI_CACHE;

        if (empty($args['file'])) {
            throw new InvalidArgumentException('"file" argument is required.');
        }

        $ckey = 'css_';
        $ckey .= $args['v'] ?? GLPI_VERSION;

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
        if (preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $file, $path_matches) === 1) {
            $plugin_key  = $path_matches['plugin_key'];
            $plugin_dir  = Plugin::getPhpDir($plugin_key) . '/public/'; // only expose public files
            $plugin_file = $path_matches['plugin_resource'];

            try {
                $path = realpath($plugin_dir . $plugin_file);
            } catch (FilesystemException $e) {
                global $PHPLOGGER;
                $PHPLOGGER->error(
                    sprintf('Cannot access the requested SCSS file `%s`, error was: `%s`.', $file, $e->getMessage()),
                    ['exception' => $e]
                );
                return '';
            }

            if (!str_starts_with($path, realpath($plugin_dir))) {
                trigger_error(
                    sprintf(
                        'Requested SCSS file `%s` is outside the plugin public directory tree.',
                        $file
                    ),
                    E_USER_WARNING
                );
                return '';
            }
        } else {
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
                && !str_starts_with($path, realpath(GLPI_THEMES_DIR)) // Allow files in THEMES dir
            ) {
                trigger_error('Requested file ' . $path . ' is outside GLPI file tree.', E_USER_WARNING);
                return '';
            }
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
                //Force bootstrap imports to be prefixed by ~
                if (str_starts_with($path, 'bootstrap/scss')) {
                    $path = '~' . $path;
                }

                $file_chunks = [];
                if (!preg_match('/^~@?(?<directory>.*)\/(?<file>[^\/]+?)(?:(\.(?<extension>s?css))?)$/', $path, $file_chunks)) {
                    return null;
                }

                $possible_extensions = array_key_exists('extension', $file_chunks) ? $file_chunks['extension'] : ['scss', 'css'];

                $possible_filenames  = [];
                foreach ($possible_extensions as $extension) {
                    $possible_filenames[] = sprintf('%s/css/lib/%s/%s.%s', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file'], $extension);
                    $possible_filenames[] = sprintf('%s/css/lib/%s/_%s.%s', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file'], $extension);
                }
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
            $start = microtime(true);
            $result = $scss->compileString($import, dirname($path));
            $css = $result->getCss();
            if (!isset($args['nocache'])) {
                $GLPI_CACHE->set($ckey, $css);
                $GLPI_CACHE->set($fckey, $file_hash);
            }
        } catch (Throwable $e) {
            ErrorHandler::logCaughtException($e);
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

            /** @var Application $application */
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
     * @return string
     *
     * @TODO Handle SCSS compiled directory in plugins.
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
        return $root_dir . '/public/css_compiled';
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
        if (is_string($ts) && !ctype_digit($ts)) {
            $ts = strtotime($ts);
        }
        $ts_date = new DateTime();
        $ts_date->setTimestamp($ts);

        $diff = strtotime($_SESSION['glpi_currenttime']) - $ts;
        $date = new DateTime(date('Y-m-d', $ts));
        $today = new DateTime(date('Y-m-d', strtotime($_SESSION['glpi_currenttime'])));
        if ($diff == 0) {
            return __('Now');
        } elseif ($diff > 0) {
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
        } else {
            $diff     = abs($diff);
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
        }

        return IntlDateFormatter::formatObject($ts_date, 'MMMM y', $_SESSION['glpilanguage'] ?? 'en_GB');
    }

    /**
     * Sanitize a input name to prevent XSS.
     *
     * @param string $name
     * @return string
     */
    public static function sanitizeInputName(string $name): string
    {
        return preg_replace('/[^a-z0-9_\[\]\-]+/i', '_', $name);
    }

    /**
     * Sanitize a DOM ID to prevent XSS.
     *
     * @param string $id
     * @return string
     */
    public static function sanitizeDomId(string $id): string
    {
        return preg_replace('/[^a-z0-9_-]+/i', '_', $id);
    }
}
