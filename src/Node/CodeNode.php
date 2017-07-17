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
 * Code Node. 
 */
class CodeNode extends Node
{
    protected $code;
    protected $buffering = false;
    protected $block;
    protected $isVerbatimMode = false;

    /**
     * Initialize code node. 
     * 
     * @param   string  $code       code string
     * @param   boolean $buffering  turn on buffering
     * @param   integer $line       source line
     */
    public function __construct($code, $buffering = false, $line, $isVerbatimMode = false)
    {
        parent::__construct($line);

        $this->code             = $code;
        $this->buffering        = $buffering;
        $this->isVerbatimMode   = $isVerbatimMode;
    }

    /**
     * Return code string. 
     * 
     * @return  string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Return true if code buffered. 
     * 
     * @return  boolean
     */
    public function isBuffered()
    {
        return $this->buffering;
    }

    /**
     * Set block node. 
     * 
     * @param   BlockNode   $node   child node
     */
    public function setBlock(BlockNode $node)
    {
        $this->block = $node;
    }

    /**
     * Return block node. 
     * 
     * @return  BlockNode
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Set verbatim mode. 
     * 
     * @param   bool   $isVerbatimMode
     */
    public function setVerbatimMode($isVerbatimMode)
    {
        $this->isVerbatimMode = $isVerbatimMode;
    }

    /**
     * Get verbatim mode. 
     * 
     * @return   bool
     */
    public function getVerbatimMode()
    {
        return $this->isVerbatimMode;
    }

    /**
     * Get code alternate syntax for control structures. 
     * 
     * @return   string
     */
    public function getPhpAlternateControlStructure()
    {
        if (preg_match('/^ *if[ \(]+.*\: *$/', $this->code)) {
            return 'if';
        }
        if (preg_match('/^ *else *\: *$/', $this->code)) {
            return 'else';
        }
        if (preg_match('/^ *else *if[ \(]+.*\: *$/', $this->code)) {
            return 'else if';
        }
        if (preg_match('/^ *while *.*\: *$/', $this->code)) {
            return 'while';
        }
        if (preg_match('/^ *for[ \(]+.*\: *$/', $this->code)) {
            return 'for';
        }
        if (preg_match('/^ *foreach[ \(]+.*\: *$/', $this->code)) {
            return 'foreach';
        }
        if (preg_match('/^ *switch[ \(]+.*\: *$/', $this->code)) {
            return 'switch';
        }
        if (preg_match('/^ *case *.* *\: *$/', $this->code)) {
            return 'case';
        }
        return '';
    }
}
