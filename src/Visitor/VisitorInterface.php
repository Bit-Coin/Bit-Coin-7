<?php

namespace clthck\SlimPHP\Visitor;

use clthck\SlimPHP\Node\Node;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node Visitor Interface. 
 */
interface VisitorInterface
{
    /**
     * Visit node. 
     * 
     * @param   Node    $node   node to visit
     */
    public function visit(Node $node);
}
