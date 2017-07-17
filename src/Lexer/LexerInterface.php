<?php

namespace clthck\SlimPHP\Lexer;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SlimPHP Lexer Interface. 
 */
interface LexerInterface
{
    /**
     * Set lexer input. 
     * 
     * @param   string  $input  input string
     */
    public function setInput($input);

    /**
     * Return next token or previously stashed one. 
     * 
     * @return  Object
     */
    public function getAdvancedToken();

    /**
     * Return current line number. 
     * 
     * @return  integer
     */
    public function getCurrentLine();

    /**
     * Defer token. 
     * 
     * @param   Object   $token  token to defer
     */
    public function deferToken(\stdClass $token);

    /**
     * Predict for number of tokens. 
     * 
     * @param   integer     $number number of tokens to predict
     *
     * @return  Object              predicted token
     */
    public function predictToken($number = 1);

    /**
     * Construct token with specified parameters. 
     * 
     * @param   string  $type   token type
     * @param   string  $value  token value
     *
     * @return  Object          new token object
     */
    public function takeToken($type, $value = null);
}
