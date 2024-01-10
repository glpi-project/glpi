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

/**
 * DocumentType Class
 **/
class DocumentType extends CommonDropdown
{
    public static $rightname      = 'typedoc';


    public function getAdditionalFields()
    {

        return [['name'  => 'icon',
            'label' => __('Icon'),
            'type'  => 'icon'
        ],
            ['name'  => 'is_uploadable',
                'label' => __('Authorized upload'),
                'type'  => 'bool'
            ],
            ['name'    => 'ext',
                'label'   => __('Extension'),
                'type'    => 'text',
                'comment' => __('May be a regular expression')
            ],
            ['name'  => 'mime',
                'label' => __('MIME type'),
                'type'  => 'text'
            ]
        ];
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Document type', 'Document types', $nb);
    }


    /**
     * Get search function for the class
     *
     * @return array of search option
     **/
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'ext',
            'name'               => __('Extension'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'icon',
            'name'               => __('Icon'),
            'massiveaction'      => false,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'mime',
            'name'               => __('MIME type'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'is_uploadable',
            'name'               => __('Authorized upload'),
            'datatype'           => 'bool'
        ];

        return $tab;
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'icon':
                if (!empty($values[$field])) {
                    return "&nbsp;<img style='vertical-align:middle;' alt='' src='" .
                      $CFG_GLPI["typedoc_icon_dir"] . "/" . $values[$field] . "'>";
                }
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'icon':
                return Dropdown::dropdownIcons(
                    $name,
                    $values[$field],
                    GLPI_ROOT . "/pics/icones",
                    false
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * @since 0.85
     *
     * @param array $options list of options with theses possible keys:
     *                        - bool 'display', echo the generated html or return it
     **/
    public static function showAvailableTypesLink($options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'display' => true,
            'rand'    => mt_rand(),
        ];

       //merge default options with options parameter
        $p = array_merge($p, $options);

        $display = "&nbsp;";
        $display .= "<a href='#' data-bs-toggle='modal' data-bs-target='#documenttypelist_{$p['rand']}' class='fa fa-info pointer' title='" . __s('Help') . "' >";
        $display .= "<span class='sr-only'>" . __s('Help') . "></span>";
        $display .= "</a>";
        $display .= Ajax::createIframeModalWindow(
            "documenttypelist_{$p['rand']}",
            $CFG_GLPI["root_doc"] . "/front/documenttype.list.php",
            ['title'   => static::getTypeName(Session::getPluralNumber()),
                'display' => false
            ]
        );

        if ($p['display']) {
            echo $display;
        } else {
            return $display;
        }
    }

    /**
     * Return pattern that can be used to validate that name of an uploaded file matches accepted extensions.
     *
     * @return string
     */
    public static function getUploadableFilePattern(): string
    {
        /** @var \DBmysql $DB */
        global $DB;

        $valid_type_iterator = $DB->request([
            'FROM'   => 'glpi_documenttypes',
            'WHERE'  => [
                'is_uploadable'   => 1
            ]
        ]);

        $valid_ext_patterns = [];
        foreach ($valid_type_iterator as $valid_type) {
            $valid_ext = $valid_type['ext'];
            if (preg_match('/\/.+\//', $valid_ext)) {
                // Filename matches pattern
                // Remove surrounding '/' as it will be included in a larger pattern
                // and protect by surrounding parenthesis to prevent conflict with other patterns
                $valid_ext_patterns[] = '(' . substr($valid_ext, 1, -1) . ')';
            } else {
               // Filename ends with allowed ext
                $valid_ext_patterns[] = '\.' . preg_quote($valid_type['ext'], '/') . '$';
            }
        }

        return '/(' . implode('|', $valid_ext_patterns) . ')/i';
    }

    public static function getIcon()
    {
        return "far fa-file";
    }
}
