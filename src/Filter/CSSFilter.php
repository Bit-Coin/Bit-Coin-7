<?php

namespace clthck\SlimPHP\Filter;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * CSS style tag filter. 
 */
class CSSFilter implements FilterInterface
{
    /**
     * Filter text. 
     * 
     * @param   string  $text       text to filter
     * @param   array   $attributes filter options from template
     * @param   string  $indent     indentation string
     *
     * @return  string              filtered text
     */
    public function filter($text, array $attributes, $indent)
    {
        $html  = $indent . '<style type="text/css">' . "\n";
        $html .= $text;
        $html .= "\n" . $indent . '</style>';

        return $html;
    }
}
