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
 * Text Node. 
 */
class TextNode extends Node
{
    protected $lines = [];

    /**
     * Initialize text node with string. 
     * 
     * @param   string|null $string text
     * @param   integer     $line   source line
     */
    public function __construct($string = null, $line)
    {
        parent::__construct($line);

        if (!empty($string)) {
            $this->lines = explode("\n", $string);
        }
    }

    /**
     * Add text line to node. 
     * 
     * @param   string  $line   string line
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * Return text lines. 
     * 
     * @return  array           array of strings
     */
    public function getLines()
    {
        return $this->lines;
    }
}
