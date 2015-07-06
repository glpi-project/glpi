<?php
/**
 * File containing the abstract ezcGraphMatrix class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */
/**
 * Provides a genereic matrix class with basic math operations
 * 
 * The matrix class is used for internal matrix calculations, and it should not
 * be required to be used by end users. It offers the common arithmetics
 * operations, and a __toString mechanism for debugging.
 * 
 * Beside this it implements more complex matrix algorithms to solve non linear
 * equatations using the Gauss-Newton algorithm and LR decomposition using the
 * Cholesky-Crout algorithm. These algorithms are required by the average
 * polynom calculation in the ezcGraphDataSetAveragePolynom class.
 *
 * @version //autogentag//
 * @package Graph
 * @access private
 */
class ezcGraphMatrix
{

    /**
     * Count of matrix rows
     * 
     * @var int
     */
    protected $rows;

    /**
     * Count of matrix columns
     * 
     * @var int
     */
    protected $columns;

    /**
     * Array containing matrix values.
     *
     * // Matrix
     *  array(
     *      // Rows
     *      array(
     *          // Column values
     *          (float)
     *      )
     *  )
     * 
     * @var array(array(float))
     */
    protected $matrix;

    /**
     * Constructor
     *
     * Creates a matrix with given dimensions. Optionally accepts an array to 
     * define the initial matrix values. If no array is given an identity 
     * matrix is created.
     * 
     * @param int $rows Number of rows
     * @param int $columns Number of columns
     * @param array $values Array with values
     * @return void
     */
    public function __construct( $rows = 3, $columns = 3, array $values = null )
    {
        $this->rows = max( 1, (int) $rows );
        $this->columns = max( 1, (int) $columns );

        if ( $values !== null )
        {
            $this->fromArray( $values );
        }
        else
        {
            $this->init();
        }
    }

    /**
     * Create matrix from array
     *
     * Use an array with float values to set matrix values.
     * 
     * @param array $values Array with values
     * @return ezcGraphMatrix Modified matrix
     */
    public function fromArray( array $values )
    {
        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] =
                    ( isset( $values[$i][$j] )
                    ? (float) $values[$i][$j]
                    : 0 );
            }
        }

        return $this;
    }

    /**
     * Init matrix
     *
     * Sets matrix to identity matrix.
     * 
     * @return ezcGraphMatrix Modified matrix
     */
    public function init()
    {
        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] = ( $i === $j ? 1 : 0 );
            }
        }

        return $this;
    }

    /**
     * Returns number of rows
     * 
     * @return int Number of rows
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Returns number of columns 
     * 
     * @return int Number of columns
     */
    public function columns()
    {
        return $this->columns;
    }

    /**
     * Get a single matrix value
     *
     * Returns the value of the matrix at the given position
     * 
     * @param int $i Column
     * @param int $j Row
     * @return float Matrix value
     */
    public function get( $i, $j )
    {
        if ( ( $i < 0 ) ||
             ( $i >= $this->rows ) ||
             ( $j < 0 ) ||
             ( $j >= $this->columns ) )
        {
            throw new ezcGraphMatrixOutOfBoundingsException( $this->rows, $this->columns, $i, $j );
        }

        return ( !isset( $this->matrix[$i][$j] ) ? .0 : $this->matrix[$i][$j] );
    }

    /**
     * Set a single matrix value
     *
     * Sets the value of the matrix at the given position.
     * 
     * @param int $i Column
     * @param int $j Row
     * @param float $value Value
     * @return ezcGraphMatrix Updated matrix
     */
    public function set( $i, $j, $value )
    {
        if ( ( $i < 0 ) ||
             ( $i >= $this->rows ) ||
             ( $j < 0 ) ||
             ( $j >= $this->columns ) )
        {
            throw new ezcGraphMatrixOutOfBoundingsException( $this->rows, $this->columns, $i, $j );
        }

        $this->matrix[$i][$j] = $value;

        return $this;
    }

    /**
     * Adds one matrix to the current one
     *
     * Calculate the sum of two matrices and returns the resulting matrix.
     * 
     * @param ezcGraphMatrix $matrix Matrix to sum with
     * @return ezcGraphMatrix Result matrix
     */
    public function add( ezcGraphMatrix $matrix )
    {
        if ( ( $this->rows !== $matrix->rows() ) ||
             ( $this->columns !== $matrix->columns() ) )
        {
            throw new ezcGraphMatrixInvalidDimensionsException( $this->rows, $this->columns, $matrix->rows(), $matrix->columns() );
        }

        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] += $matrix->get( $i, $j );
            }
        }

        return $this;
    }

    /**
     * Subtracts matrix from current one
     *
     * Calculate the diffenrence of two matices and returns the result matrix.
     * 
     * @param ezcGraphMatrix $matrix subtrahend
     * @return ezcGraphMatrix Result matrix
     */
    public function diff( ezcGraphMatrix $matrix )
    {
        if ( ( $this->rows !== $matrix->rows() ) ||
             ( $this->columns !== $matrix->columns() ) )
        {
            throw new ezcGraphMatrixInvalidDimensionsException( $this->rows, $this->columns, $matrix->rows(), $matrix->columns() );
        }

        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] -= $matrix->get( $i, $j );
            }
        }

        return $this;
    }

    /**
     * Scalar multiplication
     *
     * Multiplies matrix with the given scalar and returns the result matrix
     * 
     * @param float $scalar Scalar
     * @return ezcGraphMatrix Result matrix
     */
    public function scalar( $scalar )
    {
        $scalar = (float) $scalar;

        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] *= $scalar;
            }
        }
    }

    /**
     * Transpose matrix
     * 
     * @return ezcGraphMatrix Transposed matrix
     */
    public function transpose()
    {
        $matrix = clone $this;

        $this->rows = $matrix->columns();
        $this->columns = $matrix->rows();

        $this->matrix = array();

        for ( $i = 0; $i < $this->rows; ++$i )
        {
            for ( $j = 0; $j < $this->columns; ++$j )
            {
                $this->matrix[$i][$j] = $matrix->get( $j, $i );
            }
        }

        return $this;
    }

    /**
     * Multiplies two matrices
     *
     * Multiply current matrix with another matrix and returns the result 
     * matrix.
     *
     * @param ezcGraphMatrix $matrix Second factor
     * @return ezcGraphMatrix Result matrix
     */
    public function multiply( ezcGraphMatrix $matrix ) 
    {
        $mColumns = $matrix->columns();
        if ( $this->columns !== ( $mRows = $matrix->rows() ) ) 
        {
            throw new ezcGraphMatrixInvalidDimensionsException( $this->columns, $this->rows, $mColumns, $mRows );
        }

        $result = new ezcGraphMatrix( $this->rows, $mColumns );

        for ( $i = 0; $i < $this->rows; ++$i ) 
        {
            for ( $j = 0; $j < $mColumns; ++$j ) 
            {
                $sum = 0;
                for ( $k = 0; $k < $mRows; ++$k ) {
                    $sum += $this->matrix[$i][$k] * $matrix->get( $k, $j );
                }

                $result->set( $i, $j, $sum );
            }
        }

        return $result;
    }

    /**
     * Solve nonlinear equatation
     * 
     * Tries to solve equatation given by two matrices, with assumption, that:
     *      A * x = B
     * where $this is A, and the paramenter B. x is cosnidered as a vector
     * x = ( x^n, x^(n-1), ..., x^2, x, 1 )
     *
     * Will return a polynomial solution for x.
     *
     * See: http://en.wikipedia.org/wiki/Gauss-Newton_algorithm
     *
     * @param ezcGraphMatrix $matrix B
     * @return ezcGraphPolygon Solution of equatation
     */
    public function solveNonlinearEquatation( ezcGraphMatrix $matrix )
    {
        // Build complete equatation
        $equatation = new ezcGraphMatrix( $this->rows, $columns = ( $this->columns + 1 ) );

        for ( $i = 0; $i < $this->rows; ++$i ) 
        {
            for ( $j = 0; $j < $this->columns; ++$j ) 
            {
                $equatation->set( $i, $j, $this->matrix[$i][$j] );
            }
            $equatation->set( $i, $this->columns, $matrix->get( $i, 0 ) );
        }

        // Compute upper triangular matrix on left side of equatation
        for ( $i = 0; $i < ( $this->rows - 1 ); ++$i ) 
        {
            for ( $j = $i + 1; $j < $this->rows; ++$j ) 
            {
                if ( $equatation->get( $j, $i ) !== 0 )
                {
                    if ( $equatation->get( $j, $i ) == 0 )
                    {
                        continue;
                    }
                    else
                    {
                        $factor = -( $equatation->get( $i, $i ) / $equatation->get( $j, $i ) );
                    }

                    for ( $k = $i; $k < $columns; ++$k )
                    {
                        $equatation->set( $j, $k, $equatation->get( $i, $k ) + $factor * $equatation->get( $j, $k ) );
                    }
                }
            }
        }

        // Normalize values on left side matrix diagonale
        for ( $i = 0; $i < $this->rows; ++$i ) 
        {
            if ( ( ( $value = $equatation->get( $i, $i ) ) != 1 ) &&
                 ( $value != 0 ) )
            {
                $factor = 1 / $value;
                for ( $k = $i; $k < $columns; ++$k )
                {
                    $equatation->set( $i, $k, $equatation->get( $i, $k ) * $factor );
                }
            }
        }

        // Build up solving polynom
        $polynom = new ezcGraphPolynom();
        for ( $i = ( $this->rows - 1 ); $i >= 0; --$i )
        {
            for ( $j = $i + 1; $j < $this->columns; ++$j )
            {
                $equatation->set(
                    $i, 
                    $this->columns, 
                    $equatation->get( $i, $this->columns ) + ( -$equatation->get( $i, $j ) * $polynom->get( $j ) )
                );
                $equatation->set( $i, $j, 0 );
            }
            $polynom->set( $i, $equatation->get( $i, $this->columns ) );
        }

        return $polynom;
    }

    /**
     * Build LR decomposition from matrix
     *
     * Use Cholesky-Crout algorithm to get LR decomposition of the current 
     * matrix.
     *
     * Will return an array with two matrices:
     *  array(
     *      'l' => (ezcGraphMatrix) $left,
     *      'r' => (ezcGraphMatrix) $right,
     *  )
     * 
     * @return array( ezcGraphMatrix )
     */
    public function LRdecomposition()
    {
        /**
         * Use Cholesky-Crout algorithm to get LR decomposition
         *
         *  Input: Matrix A ($this)
         *  
         *  For i = 1 To n
         *      For j = i To n
         *          R(i,j)=A(i,j)             
         *          For k = 1 TO i-1               
         *              R(i,j)-=L(i,k)*R(k,j)
         *          end
         *      end    
         *      For j=i+1 To n
         *          L(j,i)= A(j,i)
         *          For k = 1 TO i-1
         *              L(j,i)-=L(j,k)*R(k,i)
         *          end
         *          L(j,i)/=R(i,i)
         *      end
         *  end
         *  
         *  Output: matrices L,R
         */
        $l = new ezcGraphMatrix( $this->columns, $this->rows );
        $r = new ezcGraphMatrix( $this->columns, $this->rows );

        for ( $i = 0; $i < $this->columns; ++$i ) 
        {
            for ( $j = $i; $j < $this->rows; ++$j ) 
            {
                $r->set( $i, $j, $this->matrix[$i][$j] );
                for ( $k = 0; $k <= ( $i - 1 ); ++$k )
                {
                    $r->set( $i, $j, $r->get( $i, $j ) - $l->get( $i, $k ) * $r->get( $k, $j ) );
                }
            }

            for ( $j = $i + 1; $j < $this->rows; ++$j ) 
            {
                $l->set( $j, $i, $this->matrix[$j][$i] );
                for ( $k = 0; $k <= ( $i - 1 ); ++$k )
                {
                    $l->set( $j, $i, $l->get( $j, $i ) - $l->get( $j, $k ) * $r->get( $k, $i ) );
                }
                $l->set( $j, $i, $l->get( $j, $i ) / $r->get( $i, $i ) );
            }
        }

        return array( 
            'l' => $l, 
            'r' => $r,
        );
    }

    /**
     * Returns a string representation of the matrix
     * 
     * @return string
     */
    public function __toString()
    {
        $string = sprintf( "%d x %d matrix:\n", $this->rows, $this->columns );

        for ( $i = 0; $i < $this->rows; ++$i ) 
        {
            $string .= '| ';
            for ( $j = 0; $j < $this->columns; ++$j ) 
            {
                $string .= sprintf( '%04.2F ', $this->get( $i, $j ) );
            }
            $string .= "|\n";
        }

        return $string;
    }
}
?>
