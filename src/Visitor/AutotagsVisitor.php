<?php

namespace clthck\SlimPHP\Visitor;

use clthck\SlimPHP\Node\Node;
use clthck\SlimPHP\Node\TagNode;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autotags Replacer. 
 */
class AutotagsVisitor implements VisitorInterface
{
    protected $autotags = [
        'a:void'                => ['tag' => 'a',      'attrs' => ['href' => 'javascript:;']],
        'form:post'             => ['tag' => 'form',   'attrs' => ['method' => 'POST']],
        'link:css'              => ['tag' => 'link',   'attrs' => ['rel' => 'stylesheet', 'type'  => 'text/css']],
        'script:js'             => ['tag' => 'script', 'attrs' => ['type'  => 'text/javascript']],
        'input:button'          => ['tag' => 'input',  'attrs' => ['type' => 'button']],
        'input:checkbox'        => ['tag' => 'input',  'attrs' => ['type' => 'checkbox']],
        'input:file'            => ['tag' => 'input',  'attrs' => ['type' => 'file']],
        'input:hidden'          => ['tag' => 'input',  'attrs' => ['type' => 'hidden']],
        'input:image'           => ['tag' => 'input',  'attrs' => ['type' => 'image']],
        'input:password'        => ['tag' => 'input',  'attrs' => ['type' => 'password']],
        'input:radio'           => ['tag' => 'input',  'attrs' => ['type' => 'radio']],
        'input:reset'           => ['tag' => 'input',  'attrs' => ['type' => 'reset']],
        'input:submit'          => ['tag' => 'input',  'attrs' => ['type' => 'submit']],
        'input:text'            => ['tag' => 'input',  'attrs' => ['type' => 'text']],
        'input:search'          => ['tag' => 'input',  'attrs' => ['type' => 'search']],
        'input:tel'             => ['tag' => 'input',  'attrs' => ['type' => 'tel']],
        'input:url'             => ['tag' => 'input',  'attrs' => ['type' => 'url']],
        'input:email'           => ['tag' => 'input',  'attrs' => ['type' => 'email']],
        'input:datetime'        => ['tag' => 'input',  'attrs' => ['type' => 'datetime']],
        'input:date'            => ['tag' => 'input',  'attrs' => ['type' => 'date']],
        'input:month'           => ['tag' => 'input',  'attrs' => ['type' => 'month']],
        'input:week'            => ['tag' => 'input',  'attrs' => ['type' => 'week']],
        'input:time'            => ['tag' => 'input',  'attrs' => ['type' => 'time']],
        'input:number'          => ['tag' => 'input',  'attrs' => ['type' => 'number']],
        'input:range'           => ['tag' => 'input',  'attrs' => ['type' => 'range']],
        'input:color'           => ['tag' => 'input',  'attrs' => ['type' => 'color']],
        'input:datetime-local'  => ['tag' => 'input',  'attrs' => ['type'  => 'datetime-local']]
    ];

    /**
     * Visit node. 
     * 
     * @param   Node    $node   node to visit
     */
    public function visit(Node $node)
    {
        if (!($node instanceof TagNode)) {
            throw new \InvalidArgumentException(sprintf('Autotags filter may only work with tag nodes, but %s given', get_class($node)));
        }

        if (isset($this->autotags[$node->getName()])) {
            foreach ($this->autotags[$node->getName()]['attrs'] as $key => $value) {
                $node->setAttribute($key, $value);
            }

            $node->setName($this->autotags[$node->getName()]['tag']);
        }
    }
}
