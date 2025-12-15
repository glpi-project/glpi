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

/**
 * @since 0.84
 *
 */
abstract class HTMLTableBase
{
    /** @var array<string, array<string, HTMLTableHeader>> */
    private $headers = [];
    /** @var string[] */
    private $headers_order = [];
    /** @var array<string, string[]> */
    private $headers_sub_order = [];
    /** @var bool */
    private $super;

    /**
     * @param bool $super
     */
    public function __construct(bool $super)
    {
        $this->super = $super;
    }

    /**
     * @template T of HTMLTableHeader
     *
     * @param T $header_object
     * @param bool $allow_super_header    (false by default)
     *
     * @return T
     */
    public function appendHeader(HTMLTableHeader $header_object, bool $allow_super_header = false): T
    {
        $header_name    = '';
        $subHeader_name = '';
        $header_object->getHeaderAndSubHeaderName($header_name, $subHeader_name);
        if (
            $header_object->isSuperHeader()
            && (!$this->super)
            && (!$allow_super_header)
        ) {
            throw new Exception(sprintf(
                'Implementation error: invalid super header name "%s"',
                $header_name
            ));
        }
        if (
            !$header_object->isSuperHeader()
            && $this->super
        ) {
            throw new Exception(sprintf(
                'Implementation error: invalid super header name "%s"',
                $header_name
            ));
        }

        if (!isset($this->headers[$header_name])) {
            $this->headers[$header_name]           = [];
            $this->headers_order[]                 = $header_name;
            $this->headers_sub_order[$header_name] = [];
        }
        if (!isset($this->headers[$header_name][$subHeader_name])) {
            $this->headers_sub_order[$header_name][] = $subHeader_name;
        }
        $this->headers[$header_name][$subHeader_name] = $header_object;
        return $header_object;
    }

    /**
     * Internal test to see if we can add an header. For instance, we can only add a super header
     * to a table if there is no group defined. And we can only create a sub Header to a group if
     * it contains no row
     *
     * Does not actually add the header.
     * @return void
     **/
    abstract public function tryAddHeader(): void;

    /**
     * create a new HTMLTableHeader
     *
     * Depending of "$this" type, this head will be an HTMLTableSuperHeader of a HTMLTableSubHeader
     *
     * @param string               $name     The name that can be refered by getHeaderByName()
     * @param string|array         $content  The content (see HTMLTableEntity#content) of the header
     * @param ?HTMLTableSuperHeader $super    HTMLTableSuperHeader object:
     *                                       the header that contains this new header only used
     *                                       for HTMLTableSubHeader (default NULL)
     *                                       (ie: $this instanceof HTMLTableGroup)
     * @param ?HTMLTableHeader      $father   HTMLTableHeader object: the father of the current header
     *                                       (default NULL)
     *
     * @exception Exception                  If there is no super header while creating a sub
     *                                       header or a super header while creating a super one
     *
     * @return ($super is null ? HTMLTableSuperHeader : HTMLTableSubHeader) table header that have been created
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     */
    public function addHeader(
        string $name,
        string|array $content,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null
    ) {

        $this->tryAddHeader();
        if (is_null($super)) {
            if (!$this->super) {
                throw new Exception('A sub header requires a super header');
            }
            return $this->appendHeader(new HTMLTableSuperHeader(
                $this,
                $name,
                $content,
                $father
            ));
        }
        if ($this->super) {
            throw new Exception('Cannot attach a super header to another header');
        }
        return $this->appendHeader(new HTMLTableSubHeader($super, $name, $content, $father));
    }

    /**
     * @param string $name
     *
     * @return HTMLTableHeader
     */
    public function getSuperHeaderByName(string $name): HTMLTableHeader
    {
        return $this->getHeaderByName($name, '');
    }

    /**
     * @param string $name
     * @param ?string $sub_name (default NULL)
     *
     * @return HTMLTableHeader
     *
     * @throws HTMLTableUnknownHeader
     */
    public function getHeaderByName(string $name, ?string $sub_name = null): HTMLTableHeader
    {
        if (is_string($sub_name)) {
            if (isset($this->headers[$name][$sub_name])) {
                return $this->headers[$name][$sub_name];
            }
            throw new HTMLTableUnknownHeader($name . ':' . $sub_name);
        }

        foreach ($this->headers as $header) {
            if (isset($header[$name])) {
                return $header[$name];
            }
        }
        throw new HTMLTableUnknownHeader($name);
    }

    /**
     * @param string $header_name  (default '')
     *
     * @return ($header_name is '' ? array<string, array<string, HTMLTableHeader>> : array<string, HTMLTableHeader>)
     */
    public function getHeaders(string $header_name = '')
    {
        if (empty($header_name)) {
            return $this->headers;
        }
        if (isset($this->headers[$header_name])) {
            return $this->headers[$header_name];
        }
        throw new HTMLTableUnknownHeaders($header_name);
    }

    /**
     * @param string $header_name  (default '')
     *
     * @return string[]
     */
    public function getHeaderOrder(string $header_name = ''): array
    {
        if (empty($header_name)) {
            return $this->headers_order;
        }
        if (isset($this->headers_sub_order[$header_name])) {
            return $this->headers_sub_order[$header_name];
        }
        throw new HTMLTableUnknownHeadersOrder($header_name);
    }
}
