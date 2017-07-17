<?php

namespace clthck\SlimPHP\Exception;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SlimPHP exception. 
 */
class ParseException extends Exception
{
	protected $line;

	public function __construct($line)
	{
		$this->line = $line;
	}

	public function getLineNumber()
	{
		return $this->line;
	}
}
