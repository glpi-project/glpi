<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/** GLPIUploadHandler class
 *
 * @since 9.2
 **/
class GLPIUploadHandler extends UploadHandler
{
    protected function get_error_message($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return __('The uploaded file exceeds the upload_max_filesize directive in php.ini');
            break;

            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
            break;

            case UPLOAD_ERR_PARTIAL:
                return __('The uploaded file was only partially uploaded');
            break;

            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded');
            break;

            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder');
            break;

            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk');
            break;

            case UPLOAD_ERR_EXTENSION:
                return __('A PHP extension stopped the file upload');
            break;

            case 'post_max_size':
                return __('The uploaded file exceeds the post_max_size directive in php.ini');
            break;

            case 'max_file_size':
                return __('File is too big');
            break;

            case 'min_file_size':
                return __('File is too small');
            break;

            case 'max_number_of_files':
                return __('Maximum number of files exceeded');
            break;

            case 'max_width':
                return __('Image exceeds maximum width');
            break;

            case 'min_width':
                return __('Image requires a minimum width');
            break;

            case 'max_height':
                return __('Image exceeds maximum height');
            break;

            case 'min_height':
                return __('Image requires a minimum height');
            break;

            case 'accept_file_types':
                return __('Filetype not allowed');
            break;

            case 'abort':
                return __('File upload aborted');
            break;

            case 'image_resize':
                return __('Failed to resize image');
            break;
        }

        return false;
    }

    public static function uploadFiles($params = [])
    {

        global $CFG_GLPI, $DB;

        $default_params = [
            'name'           => '',
            'showfilesize'   => false,
            'print_response' => true
        ];
        $params = array_merge($default_params, $params);

        $pname = $params['name'];

        $upload_dir     = GLPI_TMP_DIR . '/';
        $upload_handler = new self(
            [
                'accept_file_types'         => DocumentType::getUploadableFilePattern(),
                'image_versions'            => [],
                'param_name'                => $pname,
                'replace_dots_in_filenames' => false,
                'upload_dir'                => $upload_dir,
                'max_file_size'             => $CFG_GLPI['document_max_size'] * 1024 * 1024,
            ],
            false
        );
        $response       = $upload_handler->post(false);

       // clean compute display filesize
        if (isset($response[$pname]) && is_array($response[$pname])) {
            foreach ($response[$pname] as &$val) {
                if (isset($val->error) && file_exists($upload_dir . $val->name)) {
                    unlink($upload_dir . $val->name);
                } else {
                    if (isset($val->name)) {
                        $val->prefix = substr($val->name, 0, 23);
                        $val->display = str_replace($val->prefix, '', $val->name);
                    }
                    if (isset($val->size)) {
                        $val->filesize = Toolbox::getSize($val->size);
                        if (isset($params['showfilesize']) && $params['showfilesize']) {
                            $val->display = sprintf('%1$s %2$s', $val->display, $val->filesize);
                        }
                    }
                }
                $val->id = 'doc' . $params['name'] . mt_rand();
            }
        }

       // send answer
        return $upload_handler->generate_response($response, $params['print_response']);
    }

    /**
     * Overrided method to prevent deprecation when variable does not exist.
     * @see UploadHandler::get_upload_data()
     */
    protected function get_upload_data($id)
    {
        return array_key_exists($id, $_FILES) ? $_FILES[$id] : '';
    }

    /**
     * Overrided method to prevent deprecation when variable does not exist.
     * @see UploadHandler::get_post_param()
     */
    protected function get_post_param($id)
    {
        return array_key_exists($id, $_POST) ? $_POST[$id] : '';
    }

    /**
     * Overrided method to prevent deprecation when variable does not exist.
     * @see UploadHandler::get_query_param()
     */
    protected function get_query_param($id)
    {
        return array_key_exists($id, $_GET) ? $_GET[$id] : '';
    }

    /**
     * Overrided method to prevent deprecation when variable does not exist.
     * @see UploadHandler::get_server_var()
     */
    protected function get_server_var($id)
    {
        return array_key_exists($id, $_SERVER) ? $_SERVER[$id] : '';
    }

    /**
     * Overrided method to prevent deprecation when using default `$suffix` value.
     * @see UploadHandler::basename()
     */
    protected function basename($filepath, $suffix = '')
    {
        return parent::basename($filepath, $suffix);
    }
}
