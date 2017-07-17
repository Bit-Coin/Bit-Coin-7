<?php

namespace clthck\SlimPHP\Dumper;

use clthck\SlimPHP\Exception\Exception;

use clthck\SlimPHP\Visitor\VisitorInterface;
use clthck\SlimPHP\Filter\FilterInterface;

use clthck\SlimPHP\Node\Node;
use clthck\SlimPHP\Node\BlockNode;
use clthck\SlimPHP\Node\DoctypeNode;
use clthck\SlimPHP\Node\TagNode;
use clthck\SlimPHP\Node\TextNode;
use clthck\SlimPHP\Node\FilterNode;
use clthck\SlimPHP\Node\CommentNode;
use clthck\SlimPHP\Node\CodeNode;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SlimPHP -> PHP template dumper. 
 */
class PHPDumper implements DumperInterface
{
    protected $doctypes = [
        'xml'               => '<?xml version="1.0" encoding="utf-8" ?>',
        'xml ISO-8859-1'    => '<?xml version="1.0" encoding="iso-8859-1" ?>',
        'html'          => '<!DOCTYPE html>',
        '5'             => '<!DOCTYPE html>',
        '1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">',
        'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
    ];
    protected $selfClosing = ['meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'base'];
    protected $codes = [
        "/^ *if[ \(]+.*\: *$/"        => 'endif',
        "/^ *else *\: *$/"            => 'endif',
        "/^ *else *if[ \(]+.*\: *$/"  => 'endif',
        "/^ *while *.*\: *$/"         => 'endwhile',
        "/^ *for[ \(]+.*\: *$/"       => 'endfor',
        "/^ *foreach[ \(]+.*\: *$/"   => 'endforeach',
        "/^ *switch[ \(]+.*\: *$/"    => 'endswitch',
        "/^ *case *.* *\: *$/"        => 'break'
    ];
    protected $nextIsIf = [];
    protected $visitors = [
        'code'      => []
      , 'comment'   => []
      , 'doctype'   => []
      , 'filter'    => []
      , 'tag'       => []
      , 'text'      => []
    ];
    protected $filters = [];

    protected $options = [
        'tabSize'           => 2,
    ];

    /**
     * PHPDumper constructor
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Dump node to string.
     * 
     * @param   BlockNode   $node   root node
     *
     * @return  string
     */
    public function dump(BlockNode $node)
    {
        return $this->dumpNode($node);
    }

    /**
     * Register visitee extension. 
     * 
     * @param   string              $name       name of the visitable node (code, comment, doctype, filter, tag, text)
     * @param   VisitorInterface    $visitor    visitor object
     */
    public function registerVisitor($name, VisitorInterface $visitor)
    {
        $names = array_keys($this->visitors);

        if (!in_array($name, $names)) {
            throw new \InvalidArgumentException(sprintf('Unsupported node type given "%s". Use %s.',
                $name, implode(', ', $names)
            ));
        }

        $this->visitors[$name][] = $visitor;
    }

    /**
     * Register filter on dumper. 
     * 
     * @param   string          $alias  filter alias (:javascript for example)
     * @param   FilterInterface $filter filter
     */
    public function registerFilter($alias, FilterInterface $filter)
    {
        if (isset($this->filters[$alias])) {
            throw new \InvalidArgumentException(sprintf('Filter with alias %s is already registered', $alias));
        }

        $this->filters[$alias] = $filter;
    }

    /**
     * Dump node to string. 
     * 
     * @param   Node    $node   node to dump
     * @param   integer $level  indentation level
     *
     * @return  string
     */
    protected function dumpNode(Node $node, $level = 0)
    {
        $dumper = 'dump' . basename(str_replace('\\', '/', get_class($node)), 'Node');

        return $this->$dumper($node, $level);
    }

    /**
     * Dump block node to string. 
     * 
     * @param   BlockNode   $node   block node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpBlock(BlockNode $node, $level = 0)
    {
        $html = '';
        $last = '';

        $childs = $node->getChilds();
        foreach ($childs as $i => $child) {
            if (!empty($html) && !empty($last)) {
                $html .= "\n";
            }

            $this->nextIsIf[$level] = isset($childs[$i + 1]) && ($childs[$i + 1] instanceof CodeNode) 
                && (($childs[$i + 1]->getPhpAlternateControlStructure() == 'else') || ($childs[$i + 1]->getPhpAlternateControlStructure() == 'else if'));
            $last  = $this->dumpNode($child, $level);
            $html .= $last;
        }

        return $html;
    }

    /**
     * Dump doctype node. 
     * 
     * @param   DoctypeNode $node   doctype node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpDoctype(DoctypeNode $node, $level = 0)
    {
        foreach ($this->visitors['doctype'] as $visitor) {
            $visitor->visit($node);
        }

        $doctypeVersion = $node->getVersion();
        if (empty(trim($doctypeVersion))) {
            $doctypeVersion = 'transitional';
        }
        if (!isset($this->doctypes[$doctypeVersion])) {
            throw new Exception(sprintf('Unknown doctype %s', $doctypeVersion));
        }

        return $this->doctypes[$doctypeVersion];
    }

    /**
     * Dump tag node. 
     * 
     * @param   TagNode $node   tag node
     * @param   integer $level  indentation level
     *
     * @return  string
     */
    protected function dumpTag(TagNode $node, $level = 0)
    {
        $tabSize = $this->options['tabSize'];
        $html = str_repeat(' ', $level * $tabSize);

        foreach ($this->visitors['tag'] as $visitor) {
            $visitor->visit($node);
        }

        if (in_array($node->getName(), $this->selfClosing)) {
            $html .= '<' . $node->getName();
            $html .= $this->dumpAttributes($node->getAttributes());
            $html .= ' />';

            return $html;
        } else {
            if (count($node->getAttributes())) {
                $html .= '<' . $node->getName();
                $html .= $this->dumpAttributes($node->getAttributes());
                $html .= '>';
            } else {
                $html .= '<' . $node->getName() . '>';
            }

            if ($node->getCode()) {
                if (count($node->getChilds())) {
                    $html .= "\n" . str_repeat(' ', $tabSize * ($level + 1)) . $this->dumpCode($node->getCode());
                } else {
                    $html .= $this->dumpCode($node->getCode());
                }
            }
            if ($node->getText() && count($node->getText()->getLines())) {
                if (count($node->getChilds())) {
                    $html .= "\n" . str_repeat(' ', ($level + 1) * $tabSize) . $this->dumpText($node->getText());
                } else {
                    $html .= $this->dumpText($node->getText());
                }
            }

            if (count($node->getChilds())) {
                $html .= "\n";
                $childs = $node->getChilds();
                foreach ($childs as $i => $child) {
                    $this->nextIsIf[$level + 1] = isset($childs[$i + 1]) && ($childs[$i + 1] instanceof CodeNode);
                    $html .= $this->dumpNode($child, $level + 1);
                }
                $html .= "\n" . str_repeat(' ', $level * $tabSize);
            }

            return $html . '</' . $node->getName() . '>';
        }
    }

    /**
     * Dump text node. 
     * 
     * @param   TextNode    $node   text node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpText(TextNode $node, $level = 0)
    {
        $indent = str_repeat(' ', $level * $this->options['tabSize']);

        foreach ($this->visitors['text'] as $visitor) {
            $visitor->visit($node);
        }

        return $indent . $this->replaceHolders(implode("\n" . $indent, $node->getLines()));
    }

    /**
     * Dump comment node. 
     * 
     * @param   CommentNode $node   comment node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpComment(CommentNode $node, $level = 0)
    {
        foreach ($this->visitors['comment'] as $visitor) {
            $visitor->visit($node);
        }

        $tabSize = $this->options['tabSize'];

        if ($node->isBuffered()) {
            $html = str_repeat(' ', $level * $tabSize);

            if ($node->getBlock()) {
                $string = $node->getString();
                $beg    = "<!--\n";
                $end    = "\n" . str_repeat(' ', $level * $tabSize) . '-->';

                if (preg_match('/^\[ *if/', $string)) {
                    $beg = '<!--' . $string . ">\n";
                    $end = "\n" . str_repeat(' ', $level * $tabSize) . '<![endif]-->';
                    $string = '';
                }

                $html .= $beg;
                if ('' !== $string) {
                    $html .= str_repeat(' ', ($level + 1) * $tabSize) . $string . "\n";
                }
                $html .= $this->dumpBlock($node->getBlock(), $level + 1);
                $html .= $end;
            } else {
                $html = str_repeat(' ', $level * $tabSize) . '<!-- ' . $node->getString() . ' -->';
            }

            return $html;
        } else {
            return '';
        }
    }

    /**
     * Dump code node. 
     * 
     * @param   CodeNode    $node   code node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpCode(CodeNode $node, $level = 0)
    {
        $tabSize = $this->options['tabSize'];
        $html = str_repeat(' ', $level * $tabSize);

        foreach ($this->visitors['code'] as $visitor) {
            $visitor->visit($node);
        }

        $block = $node->getBlock();

        if ($block) {

            if ($node->getVerbatimMode()) {
                $codeText = preg_replace("/\\\\$/", '', $node->getCode());
                $begin = "<?php\n" . str_repeat(' ', ($level + 1) * $tabSize) . preg_replace('/^ +/', '', $codeText) . "\n";
                $end = "\n" . str_repeat(' ', $level * $tabSize) . '?>';
            }
            else {
                if ($node->isBuffered()) {
                    $begin = '<?= ' . preg_replace('/^ +/', '', $node->getCode()) . " { ?>\n";
                } else {
                    $begin = '<?php ' . preg_replace('/^ +/', '', $node->getCode()) . " { ?>\n";
                }
                $end = "\n" . str_repeat(' ', $level * $tabSize) . '<?php } ?>';

                foreach ($this->codes as $regex => $ending) {
                    if (preg_match($regex, $node->getCode())) {
                        $begin  = '<?php ' . preg_replace('/^ +| +$/', '', $node->getCode()) . " ?>\n";
                        $end    = "\n" . str_repeat(' ', $level * $tabSize) . '<?php ' . $ending . '; ?>';
                        if ('endif' === $ending && isset($this->nextIsIf[$level]) && $this->nextIsIf[$level]) {
                            $end = '';
                        }
                        break;
                    }
                }
            }

            $html .= $begin;
            $html .= $this->dumpNode($block, $level + 1);
            $html .= $end;
        } else {
            if ($node->isBuffered()) {
                $html .= '<?= ' . preg_replace('/^ +/', '', $node->getCode()) . ' ?>';
            } else {
                $html .= '<?php ' . preg_replace('/^ +/', '', $node->getCode()) . ' ?>';
            }
        }

        return $html;
    }

    /**
     * Dump filter node. 
     * 
     * @param   FilterNode  $node   filter node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpFilter(FilterNode $node, $level = 0)
    {
        if (!isset($this->filters[$node->getName()])) {
            throw new Exception(sprintf('Filter with alias "%s" is not registered.', $node->getName()));
        }

        $text = '';
        if ($node->getBlock()) {
            $text = $this->dumpNode($node->getBlock(), $level + 1);
        }

        return $this->filters[$node->getName()]->filter($text, $node->getAttributes(), str_repeat(' ', $level * $this->options['tabSize']));
    }

    /**
     * Dump attributes. 
     * 
     * @param   array   $attributes attributes associative array
     * 
     * @return  string
     */
    protected function dumpAttributes(array $attributes)
    {
        $items = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $valueText = implode(' ', $value);
                if (preg_match("/#{([^}]*)}/", $valueText)) {
                    $valueText = preg_replace("/'/", '"', $valueText);
                    $items[] = $key . "='" . $this->replaceHolders(htmlspecialchars($valueText), true) . "'";
                } else {
                    $items[] = $key . '="' . $this->replaceHolders(htmlspecialchars($valueText), true) . '"';
                }
            } elseif (true === $value) {
                if (preg_match("/#{([^}]*)}/", $key)) {
                    $items[] = $this->replaceHolders($key);
                } else {
                    $items[] = $key . '="' . $key . '"';
                }
            } elseif (false !== $value) {
                $items[] = $key . '="' . $this->replaceHolders(htmlspecialchars($value), true) . '"';
            }
        }

        return count($items) ? ' ' . implode(' ', $items) : '';
    }

    /**
     * Replace tokenized PHP string in text. 
     * 
     * @param   string  $string text
     * @param   boolean $decode decode HTML entitied
     *
     * @return  string
     */
    protected function replaceHolders($string, $decode = false)
    {
        $ret = preg_replace_callback("/^#{([^}]*)}/", function($matches) use($decode) {
            return sprintf('<?= %s ?>', $decode ? html_entity_decode($matches[1]) : $matches[1]);
        }, $string);

        $ret = preg_replace_callback("/([^\\\\])#{([^}]*)}/", function($matches) use($decode) {
            return sprintf('%s<?= %s ?>', $matches[1], $decode ? html_entity_decode($matches[2]) : $matches[2]);
        }, $ret);

        return preg_replace("/\\\\(#{[^}]*})/", "$1", $ret);
    }
}
