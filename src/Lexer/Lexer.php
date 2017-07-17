<?php

namespace clthck\SlimPHP\Lexer;

use clthck\SlimPHP\Exception\Exception;
use clthck\SlimPHP\Exception\ParseException;
use clthck\SlimPHP\Exception\UnknownTokenException;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SlimPHP Lexer. 
 */
class Lexer implements LexerInterface
{
    protected $input;
    protected $deferredObjects      = [];
    protected $lastIndents          = 0;
    protected $indentCache          = [0];
    protected $lineno               = 1;
    protected $stash                = [];

    protected $isInVerbatimBlock    = false;
    protected $verbatimBlockIndents = 0;        // verbatim wrapper's indents
    protected $verbatimLineIndents  = 0;        // current line indent in verbatim block

    protected $lastToken            = null;
    protected $placeHolderCounter   = 0;

    protected $options          = [
        'tabSize'           => 2,
    ];

    /**
     * Lexer constructor
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Set lexer input. 
     * 
     * @param   string  $input  input string
     */
    public function setInput($input)
    {
        $this->input                = trim(preg_replace(['/\r\n|\r/', '/\t/'], ["\n", str_repeat(' ', $this->options['tabSize'])], $input));
        $this->deferredObjects      = [];
        $this->lastIndents          = 0;
        $this->indentCache          = [0];
        $this->lineno               = 1;
        $this->stash                = [];
        $this->isInVerbatimBlock    = false;
        $this->verbatimBlockIndents = 0;
        $this->verbatimLineIndents  = 0;
        $this->lastToken            = null;
        $this->placeHolderCounter   = 0;
    }

    /**
     * Return next token or previously stashed one. 
     * 
     * @return  Object
     */
    public function getAdvancedToken()
    {
        if ($token = $this->getStashedToken()) {
            return $token;
        }

        return $this->getNextToken();
    }

    /**
     * Return current line number. 
     * 
     * @return  integer
     */
    public function getCurrentLine()
    {
        return $this->lineno;
    }

    /**
     * Defer token. 
     * 
     * @param   Object   $token  token to defer
     */
    public function deferToken(\stdClass $token)
    {
        $this->deferredObjects[] = $token;
    }

    /**
     * Predict for number of tokens. 
     * 
     * @param   integer     $number number of tokens to predict
     *
     * @return  Object              predicted token
     */
    public function predictToken($number = 1)
    {
        $fetch = $number - count($this->stash);

        while ($fetch-- > 0) {
            $this->stash[] = $this->getNextToken();
        }

        return $this->stash[--$number];
    }

    /**
     * Construct token with specified parameters. 
     * 
     * @param   string  $type   token type
     * @param   string  $value  token value
     *
     * @return  Object          new token object
     */
    public function takeToken($type, $value = null)
    {
        return (Object) [
            'type'  => $type
          , 'line'  => $this->lineno
          , 'value' => $value
        ];
    }

    /**
     * Return stashed token. 
     * 
     * @return  Object|boolean   token if has stashed, false otherways
     */
    protected function getStashedToken()
    {
        return count($this->stash) ? array_shift($this->stash) : null;
    }

    /**
     * Return deferred token. 
     * 
     * @return  Object|boolean   token if has deferred, false otherways
     */
    protected function getDeferredToken()
    {
        return count($this->deferredObjects) ? array_shift($this->deferredObjects) : null;
    }

    /**
     * Checks if token is a valid verbatim text wrapper or not.
     * 
     * @param   Object $token
     * @return  bool
     */
    public function isTokenVerbatimWrapper($token)
    {
        if ($token->type == 'filter' || $token->type == 'pipe') {
            return true;
        }
        if ($token->type == 'comment' && !$token->buffer) {
            return true;
        }
        if ($token->type == 'code' && !$token->buffer && preg_match("/\\\\$/", $token->value)) {
            return true;
        }
        return false;
    }

    /**
     * Return next token. 
     * 
     * @return  Object
     */
    protected function getNextToken()
    {
        $scanners = [
            'getDeferredToken'
          , 'scanEOS'
          , 'scanVerbatim'
          , 'scanDoctype'
          , 'scanTag'
          , 'scanPipe'
          , 'scanHtmlLikeStyle'
          , 'scanFilter'
          , 'scanCode'
          , 'scanComment'
          , 'scanId'
          , 'scanClass'
          , 'scanAttributes'
          , 'scanIndentation'
          , 'scanText'              // inline text node
        ];

        foreach ($scanners as $scan) {
            $token = $this->$scan();

            if (null !== $token && $token) {
                if ($this->isTokenVerbatimWrapper($token)) {
                    $this->isInVerbatimBlock = true;
                    $this->verbatimBlockIndents = $this->lastIndents;
                }
                $this->lastToken = $token;
                return $token;
            }
        }

        throw new UnknownTokenException($this->lineno);
    }

    /**
     * Consume input. 
     * 
     * @param   integer $length length of input to consume
     */
    protected function consumeInput($length)
    {
        $this->input = mb_substr($this->input, $length);
    }

    /**
     * Scan for token with specified regex. 
     * 
     * @param   string  $regex  regular expression
     * @param   string  $type   expected token type
     *
     * @return  Object|null
     */
    protected function scanInput($regex, $type)
    {
        $matches = [];
        if (preg_match($regex, $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));

            $value = null;
            if (count($matches) > 1) {
                $value = $matches[1];
            }
            if ($this->isInVerbatimBlock) {
                $value = str_repeat(' ', $this->verbatimLineIndents) . $value;
            }
            return $this->takeToken($type, $value);
        }
    }

    /**
     * Scan EOS from input & return it if found.
     * 
     * @return  Object|null
     */
    protected function scanEOS()
    {
        if (mb_strlen($this->input)) {
            return;
        }

        $token = $this->lastIndents > 0 ? $this->takeToken('outdent') : $this->takeToken('eos');
        array_pop($this->indentCache);
        $this->lastIndents = end($this->indentCache);
        return $token;
    }

    /**
     * Scan comment from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanComment()
    {
        $matches = [];

        if (preg_match('/^ *\/(\!)?([^\n]+)?/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('comment', isset($matches[2]) ? $matches[2] : '');
            $token->buffer = isset($matches[1]) && $matches[1] == '!';

            return $token;
        }
    }

    /**
     * Scan tag from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanTag()
    {
        return $this->scanInput('/^(\w[:\-\w]*)/', 'tag');
    }

    /**
     * Scan filter from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanFilter()
    {
        return $this->scanInput('/^:(\w+)/', 'filter');
    }

    /**
     * Scan pipe from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanPipe()
    {
        return $this->scanInput('/^\|/', 'pipe');
    }

    /**
     * Scan implicit pipe from input & return it if found. This is for allowing html like style.
     * 
     * @return  Object|null
     */
    protected function scanHtmlLikeStyle()
    {
        if (preg_match('/^</', $this->input)) {
            return $this->scanInput('/^(<[\w\/].*)/', 'text');
        }
        return null;
    }

    /**
     * Scan doctype from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanDoctype()
    {
        return $this->scanInput('/^doctype *([\w \.\-]+)?/', 'doctype');
    }

    /**
     * Scan id from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanId()
    {
        return $this->scanInput('/^#([\w\-]+)/', 'id');
    }

    /**
     * Scan class from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanClass()
    {
        return $this->scanInput('/^\.([\w\-]+)/', 'class');
    }

    /**
     * Scan text from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanText()
    {
        if ($this->lastToken->type == 'tag'
            || $this->lastToken->type == 'filter'
            || $this->lastToken->type == 'pipe'
            || $this->lastToken->type == 'attributes'
            || $this->lastToken->type == 'class'
            || $this->lastToken->type == 'id'
        ) {
            $token = $this->scanInput('/([^\n]+)/', 'text');
            $token->value = preg_replace("/ *\\\\\(/", '(', $token->value);
            return $token;
        }
        return null;
    }

    /**
     * Scan verbatim text from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanVerbatim()
    {
        if ($this->isInVerbatimBlock) {
            return $this->scanInput('/^([^\n]+)/', 'text');
        }
        return null;
    }

    /**
     * Scan code from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanCode()
    {
        $matches = [];

        if (preg_match('/^(!?=|-)([^\n]+)/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));

            $flags = $matches[1];
            $token = $this->takeToken('code', $matches[2]);
            $token->buffer = (isset($flags[0]) && '=' === $flags[0]) || (isset($flags[1]) && '=' === $flags[1]);

            return $token;
        }
    }

    /**
     * Scan attributes from input & return them if found. 
     * 
     * @return  Object|null
     */
    protected function scanAttributes()
    {
        if (preg_match('/^ *\(.+\)/', $this->input)) {
            $this->input = trim($this->input);
            $index      = $this->getDelimitersIndex('(', ')');
            $input      = preg_replace('/ *= */', '=', trim(mb_substr($this->input, 1, $index - 1)));
            $token      = $this->takeToken('attributes', $input);

            preg_match_all('/#{[^}]*}/', $input, $matches);
            $placeHolders = $matches[0];
            $this->placeHolderCounter = -1;
            $input = trim(preg_replace_callback('/#{[^}]*}/', function ($matches) {
                $this->placeHolderCounter++;
                return '{' . (int)$this->placeHolderCounter . '}';
            }, $input));

            preg_match_all('/"([^"]*)"/', $input, $matches);
            $doubleQuotedValues = $matches[1];
            $this->placeHolderCounter = -1;
            $input = trim(preg_replace_callback('/"([^"]*)"/', function ($matches) {
                $this->placeHolderCounter++;
                return '{<' . (int)$this->placeHolderCounter . '>}';
            }, $input));

            preg_match_all("/'([^']*)'/", $input, $matches);
            $singleQuotedValues = $matches[1];
            $this->placeHolderCounter = -1;
            $input = trim(preg_replace_callback("/'([^']*)'/", function ($matches) {
                $this->placeHolderCounter++;
                return '{<<' . (int)$this->placeHolderCounter . '>>}';
            }, $input));

            //$attributes = preg_split('/ *(?:,| ) *(?=[\'"\w\-]+ *[=]|[\w\-]+ *$)/', $token->value);
            $attributes = preg_split('/ *(?:,| ) */', $input);

            $this->consumeInput($index + 1);
            $token->attributes = [];

            foreach ($attributes as $pair) {
                $pair = preg_replace('/^ *| *$/', '', $pair);
                $equal = mb_strpos($pair, '=');

                $sbrac = mb_strpos($pair, '\'');
                $dbrac = mb_strpos($pair, '"');
                if ($sbrac < 1) {
                    $sbrac = false;
                }
                if ($dbrac < 1) {
                    $dbrac = false;
                }
                if ((false !== $sbrac && $equal > $sbrac) || (false !== $dbrac && $equal > $dbrac)) {
                    $equal = false;
                }

                if (false === $equal) {
                    $key    = $pair;
                    if (preg_match('/^{<<(\d+)>>}$/', $key, $matches)) {
                        $key = $singleQuotedValues[$matches[1]];
                    }
                    if (preg_match('/^{<(\d+)>}$/', $key, $matches)) {
                        $key = $doubleQuotedValues[$matches[1]];
                    }
                    if (preg_match('/(.*){(\d+)}(.*)/', $key, $matches)) {
                        $key = $matches[1] . $placeHolders[$matches[2]] . $matches[3];
                    }
                    $value  = true;
                } else {
                    $splitter = $equal;

                    $key    = mb_substr($pair, 0, $splitter);
                    $value  = preg_replace('/^ *[\'"]?|[\'"]? *$/', '', mb_substr($pair, ++$splitter, mb_strlen($pair)));

                    if ('true' === $value) {
                        $value = true;
                    } else if (empty($value) || 'null' === $value || 'false' === $value) {
                        $value = false;
                    }

                    if (preg_match('/^{<<(\d+)>>}$/', $value, $matches)) {
                        $value = $singleQuotedValues[$matches[1]];
                    }
                    if (preg_match('/^{<(\d+)>}$/', $value, $matches)) {
                        $value = $doubleQuotedValues[$matches[1]];
                    }
                    if (preg_match('/(.*){(\d+)}(.*)/', $value, $matches)) {
                        $value = $matches[1] . $placeHolders[$matches[2]] . $matches[3];
                    }
                }

                $token->attributes[preg_replace( ['/^ +| +$/', '/^[\'"]|[\'"]$/'], '', $key )] = $value;
            }

            return $token;
        }
    }

    /**
     * isValidIndent()
     * @param $indents
     * @return bool
     */
    protected function isValidIndent($indents)
    {
        return in_array($indents, $this->indentCache);
    }

    /**
     * Scan indentation from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanIndentation()
    {
        $matches = [];

        if (preg_match('/^\n( *)/', $this->input, $matches)) {
            $this->lineno++;
            $this->consumeInput(mb_strlen($matches[0]));

            $token      = $this->takeToken('indent', $matches[1]);
            $indents    = mb_strlen($token->value);

            if ($this->isInVerbatimBlock && mb_strlen($this->input) && "\n" === $this->input[0]) {
                return $token;
            }

            if ($this->isInVerbatimBlock && $indents <= $this->verbatimBlockIndents) {
                $this->isInVerbatimBlock = false;
            }

            if ($this->isInVerbatimBlock) {
                $this->verbatimLineIndents = max($indents - $this->verbatimBlockIndents - $this->options['tabSize'], 0);
                return $token;
            }

            if ($indents > $this->lastIndents) {
                if (!in_array($indents, $this->indentCache)) {
                    $this->indentCache = array_merge($this->indentCache, [$indents]);
                }
            }

            if (mb_strlen($this->input) && "\n" === $this->input[0]) {
                $token->type = 'newline';
                return $token;
            } elseif ($this->lastIndents === $indents) {
                $token->type = 'newline';
            } elseif ($this->lastIndents > $indents) {

                if ($this->isValidIndent($indents)) {
                    $count = array_search($this->lastIndents, $this->indentCache) - array_search($indents, $this->indentCache);
                    $token->type = 'outdent';
                    while (--$count) {
                        array_pop($this->indentCache);
                        $this->deferToken($this->takeToken('outdent'));
                    }
                }
                else {
                    throw new ParseException($this->lineno);
                }
            }

            $this->lastIndents = $indents;

            return $token;
        }
    }

    /**
     * Return the index of begin/end delimiters. 
     * 
     * @param   string  $begin  befin delimiter
     * @param   string  $end    end delimiter
     *
     * @return  integer         position index
     */
    protected function getDelimitersIndex($begin, $end)
    {
        $string     = $this->input;
        $nbegin     = 0;
        $nend       = 0;
        $position   = 0;

        $sbrac      = false;
        $dbrac      = false;

        for ($i = 0, $length = mb_strlen($string); $i < $length; ++$i) {
            if ('"' === $string[$i]) {
                $dbrac = !$dbrac;
            } elseif ('\'' === $string[$i]) {
                $sbrac = !$sbrac;
            }

            if (!$sbrac && !$dbrac && $begin === $string[$i]) {
                ++$nbegin;
            } elseif (!$sbrac && !$dbrac && $end === $string[$i]) {
                if (++$nend === $nbegin) {
                    $position = $i;
                    break;
                }
            }
        }

        return $position;
    }
}
