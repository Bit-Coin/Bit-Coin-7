<?php

namespace clthck\SlimPHP;

use clthck\SlimPHP\Exception\Exception;
use clthck\SlimPHP\Exception\ParseException;
use clthck\SlimPHP\Exception\UnknownTokenException;

use clthck\SlimPHP\Lexer\LexerInterface;

use clthck\SlimPHP\Node\BlockNode;
use clthck\SlimPHP\Node\CodeNode;
use clthck\SlimPHP\Node\CommentNode;
use clthck\SlimPHP\Node\DoctypeNode;
use clthck\SlimPHP\Node\FilterNode;
use clthck\SlimPHP\Node\TagNode;
use clthck\SlimPHP\Node\TextNode;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SlimPHP Parser. 
 */
class Parser
{
    protected $lexer;

    /**
     * Initialize Parser. 
     * 
     * @param   LexerInterface  $lexer  lexer object
     */
    public function __construct(LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parse input returning block node. 
     * 
     * @param   string          $input  slim document
     *
     * @return  BlockNode
     */
    public function parse($input)
    {
        $this->lexer->setInput($input);

        $node = new BlockNode($this->lexer->getCurrentLine());

        while ('eos' !== $this->lexer->predictToken()->type) {
            if ('newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                if ($child = $this->parseExpression()) {
                    $node->addChild($child);
                }
            }
        }

        return $node;
    }

    /**
     * Expect given type or throw Exception. 
     * 
     * @param   string  $type   type
     */
    protected function expectTokenType($type)
    {
        if ($type === $this->lexer->predictToken()->type) {
            return $this->lexer->getAdvancedToken();
        } else {
            throw new Exception(sprintf('Expected %s, but got %s', $type, $this->lexer->predictToken()->type));
        }
    }
    
    /**
     * Accept given type. 
     * 
     * @param   string  $type   type
     */
    protected function acceptTokenType($type)
    {
        if ($type === $this->lexer->predictToken()->type) {
            return $this->lexer->getAdvancedToken();
        }
    }

    /**
     * Parse current expression & return Node. 
     * 
     * @return  Node
     */
    protected function parseExpression()
    {
        switch ($this->lexer->predictToken()->type) {
            case 'tag':
                return $this->parseTag();
            case 'doctype':
                return $this->parseDoctype();
            case 'filter':
                return $this->parseFilter();
            case 'pipe':
                return $this->parsePipe();
            case 'comment':
                return $this->parseComment();
            case 'text':
                return $this->parseText();
            case 'code':
                return $this->parseCode();
            case 'id':
            case 'class':
                $token = $this->lexer->getAdvancedToken();
                $this->lexer->deferToken($this->lexer->takeToken('tag', 'div'));
                $this->lexer->deferToken($token);

                return $this->parseExpression();
            case 'outdent':
            case 'indent':
            case 'eos':
                $this->lexer->getAdvancedToken();
                return null;
            default:
                throw new UnknownTokenException($this->lexer->getCurrentLine());
                return null;
        }
    }

    /**
     * Parse next text token. 
     * 
     * @return  TextNode
     */
    protected function parseText($trim = false)
    {
        $token = $this->expectTokenType('text');
        $value = $trim ? preg_replace('/^ +/', '', $token->value) : $token->value;

        return new TextNode($value, $this->lexer->getCurrentLine());
    }

    /**
     * Parse next code token. 
     * 
     * @return  CodeNode
     */
    protected function parseCode()
    {
        $token  = $this->expectTokenType('code');
        $node   = new CodeNode($token->value, $token->buffer, $this->lexer->getCurrentLine());

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        if ($this->lexer->isTokenVerbatimWrapper($token)) {
            $block = new BlockNode($this->lexer->getCurrentLine());
            $block->addChild($this->parseTextBlock());
            $node->setBlock($block);
            $node->setVerbatimMode(true);
        }
        else if ('indent' === $this->lexer->predictToken()->type) {
            $node->setBlock($this->parseBlock());
        }

        return $node;
    }

    /**
     * Parse next commend token. 
     * 
     * @return  CommentNode
     */
    protected function parseComment()
    {
        $token  = $this->expectTokenType('comment');
        $node   = new CommentNode(preg_replace('/^ +| +$/', '', $token->value), $token->buffer, $this->lexer->getCurrentLine());

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        if ('indent' === $this->lexer->predictToken()->type) {
            if ($token->buffer) {
                $node->setBlock($this->parseBlock());
            }
            else {
                $block = new BlockNode($this->lexer->getCurrentLine());
                $block->addChild($this->parseTextBlock());
                $node->setBlock($block);
            }
        }

        return $node;
    }

    /**
     * Parse next doctype token. 
     * 
     * @return  DoctypeNode
     */
    protected function parseDoctype()
    {
        $token = $this->expectTokenType('doctype');

        return new DoctypeNode($token->value, $this->lexer->getCurrentLine());
    }

    /**
     * Parse next filter token. 
     * 
     * @return  FilterNode
     */
    protected function parseFilter()
    {
        $block      = null;
        $token      = $this->expectTokenType('filter');
        $attributes = $this->acceptTokenType('attributes');
        
        $block = $this->parseTextBlock();

        $node = new FilterNode(
            $token->value, null !== $attributes ? $attributes->attributes : [], $this->lexer->getCurrentLine()
        );
        $node->setBlock($block);

        return $node;
    }

    /**
     * Parse next indented? text token. 
     * 
     * @return  TextNode
     */
    protected function parseTextBlock()
    {
        $node = new TextNode(null, $this->lexer->getCurrentLine());

        if ($this->lexer->predictToken()->type === 'text') {
            $node->addLine(trim($this->lexer->getAdvancedToken()->value));
        } else {
            $this->expectTokenType('indent');
        }
        while ('text' === $this->lexer->predictToken()->type || 'indent' === $this->lexer->predictToken()->type) {
            if ('indent' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                $node->addLine($this->lexer->getAdvancedToken()->value);
            }
        }
        //$this->expectTokenType('outdent');

        return $node;
    }

    /**
     * Parse next pipe token. 
     * 
     * @return  TextNode
     */
    protected function parsePipe()
    {
        $this->expectTokenType('pipe');
        return $this->parseTextBlock();
    }

    /**
     * Parse indented block token. 
     * 
     * @return  BlockNode
     */
    protected function parseBlock()
    {
        $node = new BlockNode($this->lexer->getCurrentLine());

        $this->expectTokenType('indent');
        while ('outdent' !== $this->lexer->predictToken()->type && 'eos' !== $this->lexer->predictToken()->type) {
            if ('newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                if ($child = $this->parseExpression()) {
                    $node->addChild($child);
                }
            }
        }
        //$this->expectTokenType('outdent');
        $this->lexer->getAdvancedToken();

        return $node;
    }

    /**
     * Parse tag token. 
     * 
     * @return  TagNode
     */
    protected function parseTag()
    {
        $name = $this->lexer->getAdvancedToken()->value;
        $node = new TagNode($name, $this->lexer->getCurrentLine());

        // Parse id, class, attributes token
        while (true) {
            switch ($this->lexer->predictToken()->type) {
                case 'id':
                case 'class':
                    $token = $this->lexer->getAdvancedToken();
                    $node->setAttribute($token->type, $token->value);
                    continue;
                case 'attributes':
                    foreach ($this->lexer->getAdvancedToken()->attributes as $name => $value) {
                        $node->setAttribute($name, $value);
                    }
                    continue;
                default:
                    break(2);
            }
        }

        // Parse text/code token
        switch ($this->lexer->predictToken()->type) {
            case 'text':
                $node->setText($this->parseText(true));
                break;
            case 'code':
                $node->setCode($this->parseCode());
                break;
        }

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        // Tag text on newline
        if ('text' === $this->lexer->predictToken()->type) {
            if ($text = $node->getText()) {
                $text->addLine('');
            } else {
                $node->setText(new TextNode('', $this->lexer->getCurrentLine()));
            }
        }

        // Parse block indentation
        if ('indent' === $this->lexer->predictToken()->type) {
            $node->addChild($this->parseBlock());
        }

        return $node;
    }
}
