<?php
namespace clthck\SlimPHP\Test;

use clthck\SlimPHP\SlimPHP;
use clthck\SlimPHP\Parser;
use clthck\SlimPHP\Lexer\Lexer;
use clthck\SlimPHP\Dumper\PHPDumper;
use clthck\SlimPHP\Visitor\AutotagsVisitor;

use clthck\SlimPHP\Filter\JavaScriptFilter;
use clthck\SlimPHP\Filter\CDATAFilter;
use clthck\SlimPHP\Filter\PHPFilter;
use clthck\SlimPHP\Filter\CSSFilter;

/*
 * This file is part of the SlimPHP package.
 * (c) 2015 clthck <joey.corleone92@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters test 
 */
class FiltersTest extends \PHPUnit_Framework_TestCase
{
    protected $slim;

    public function __construct()
    {
        $parser = new Parser(new Lexer());
        $dumper = new PHPDumper();
        $dumper->registerVisitor('tag', new AutotagsVisitor());
        $dumper->registerFilter('javascript', new JavaScriptFilter());
        $dumper->registerFilter('cdata', new CDATAFilter());
        $dumper->registerFilter('php', new PHPFilter());
        $dumper->registerFilter('style', new CSSFilter());

        $this->slim = new SlimPHP($parser, $dumper);
    }

    protected function parse($value)
    {
        return $this->slim->render($value);
    }

    public function testFilterCodeInsertion()
    {
        $this->assertEquals(
            "<script type=\"text/javascript\">\n  var name = \"<?= \$name ?>\";\n</script>",
            $this->parse(<<<SlimPHP
:javascript
  | var name = "{{\$name}}";
SlimPHP
            )
        );
    }

    public function testCDATAFilter()
    {
        $this->assertEquals(
            "<![CDATA[\n  foo\n]]>",
            $this->parse(<<<SlimPHP
:cdata
  | foo
SlimPHP
            )
        );
        $this->assertEquals(
            "<![CDATA[\n  foo\n   bar\n]]>",
            $this->parse(<<<SlimPHP
:cdata
  | foo
  |  bar
SlimPHP
            )
        );
        $this->assertEquals(
            "<![CDATA[\n  foo\n  bar\n]]>\n<p>something else</p>",
            $this->parse(<<<SlimPHP
:cdata
  | foo
  | bar
p something else
SlimPHP
            )
        );
    }

    public function testJavaScriptFilter()
    {
        $this->assertEquals(
            "<script type=\"text/javascript\">\n  alert('foo')\n</script>",
            $this->parse(<<<SlimPHP
:javascript
  | alert('foo')
SlimPHP
            )
        );
    }

    public function testCSSFilter()
    {
        $this->assertEquals(
            "<style type=\"text/css\">\n  body {\n    color:#000;\n  }\n</style>",
            $this->parse(<<<SlimPHP
:style
  | body {
  |   color:#000;
  | }
SlimPHP
            )
        );
        $this->assertEquals(
            "<style type=\"text/css\">\n  body {color:#000;}\n</style>",
            $this->parse(<<<SlimPHP
:style
  | body {color:#000;}
SlimPHP
            )
        );

        $slim = <<<SlimPHP
body
  p
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }
SlimPHP;
        $html = <<<HTML
<body>
  <p>
    <link type="text/css" src="/css/ie6.css" rel="stylesheet" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  </p>
</body>
HTML;
        $this->assertEquals($html, $this->parse($slim));

        $slim = <<<SlimPHP
body
  p
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }
  p
    script:js( src="/js/html5.js" )
SlimPHP;
        $html = <<<HTML
<body>
  <p>
    <link type="text/css" src="/css/ie6.css" rel="stylesheet" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  </p>
  <p>
    <script src="/js/html5.js" type="text/javascript"></script>
  </p>
</body>
HTML;
        $this->assertEquals($html, $this->parse($slim));

        $slim = <<<SlimPHP
head
  // [if lt IE 7]
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }

  // [if lt IE 9]
    script:js( src="/js/html5.js" )
SlimPHP;
        $html = <<<HTML
<head>
  <!--[if lt IE 7]>
    <link type="text/css" src="/css/ie6.css" rel="stylesheet" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  <![endif]-->
  <!--[if lt IE 9]>
    <script src="/js/html5.js" type="text/javascript"></script>
  <![endif]-->
</head>
HTML;
        $this->assertEquals($html, $this->parse($slim));
    }

    public function testPHPFilter()
    {
        $this->assertEquals(
            "<?php\n  \$bar = 10;\n  \$bar++;\n  echo \$bar;\n?>",
            $this->parse(<<<SlimPHP
:php
  | \$bar = 10;
  | \$bar++;
  | echo \$bar;
SlimPHP
            )
        );
    }
}
