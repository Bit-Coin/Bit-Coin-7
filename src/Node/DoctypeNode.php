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
 * Doctype Node. 
 */
class DoctypeNode extends Node
{
    protected $version;

    /**
     * Initialize doctype node. 
     * 
     * @param   string  $version    doctype version
     * @param   integer $line       source line
     */
    public function __construct($version, $line)
    {
        parent::__construct($line);

        $this->version = $version;
    }

    /**
     * Return doctype version. 
     * 
     * @return  string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
