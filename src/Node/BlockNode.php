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
 * Block Node. 
 */
class BlockNode extends Node
{
    protected $childs = [];

    /**
     * Add child node. 
     * 
     * @param   Node    $node   child node
     */
    public function addChild(Node $node)
    {
        $this->childs[] = $node;
    }

    /**
     * Return child nodes. 
     * 
     * @return  array           array of Node's
     */
    public function getChilds()
    {
        return $this->childs;
    }
}
