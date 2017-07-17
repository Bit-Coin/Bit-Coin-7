<?php

namespace clthck\SlimPHP\Node;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node. 
 */
abstract class Node
{
    protected $line;

    /**
     * Initialize node. 
     * 
     * @param   integer $line   source line
     */
    public function __construct($line)
    {
        $this->line = $line;
    }

    /**
     * Return node source line. 
     * 
     * @return  integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
