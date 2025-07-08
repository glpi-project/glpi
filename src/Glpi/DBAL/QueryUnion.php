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

namespace Glpi\DBAL;

use AbstractQuery;
use DBmysql;

/**
 * UNION query class
 **/
class QueryUnion extends AbstractQuery
{
    private $queries = [];
    private $distinct = false;

    /**
     * Create a sub query
     *
     * @param array $queries An array of queries to union. Either SubQuery objects
     *                          or an array of criteria to build them.
     *                          You can also add later using @param boolean $distinct Include duplicates or not. Turning on may has
     *                          huge cost on queries performances.
     * @param string $alias Union ALIAS. Defaults to null.
     * @see addQuery
     */
    public function __construct(array $queries = [], $distinct = false, $alias = null)
    {
        parent::__construct($alias);
        $this->distinct = $distinct;

        foreach ($queries as $query) {
            $this->addQuery($query);
        }
    }

    /**
     * Add a query
     *
     * @param QuerySubQuery|array $query Either a SubQuery object
     *                                   or an array of criteria to build it.
     */
    public function addQuery($query)
    {
        if (!$query instanceof QuerySubQuery) {
            $query = new QuerySubQuery($query);
        }
        $this->queries[] = $query;
    }


    /**
     * Get queries
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     *
     * Get SQL query
     *
     * @return string
     */
    public function getQuery()
    {
        $union_queries = $this->getQueries();
        if (
            empty($union_queries)
        ) {
            throw new \RuntimeException('Cannot build an empty union query');
        }

        $queries = [];
        foreach ($union_queries as $uquery) {
            $queries[] = $uquery->getQuery();
        }

        $keyword = 'UNION';
        if (!$this->distinct) {
            $keyword .= ' ALL';
        }
        $query = '(' . implode(" $keyword ", $queries) . ')';

        $alias = $this->alias !== null
            ? $this->alias
            : 'union_' . md5($query);
        $query .= ' AS ' . DBmysql::quoteName($alias);

        return $query;
    }
}
