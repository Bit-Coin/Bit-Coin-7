# SlimPHP - template compiler for PHP 5.4+

*SlimPHP* is a high performance template compiler heavily influenced by [Slim](http://slim-lang.com), which is implemented for PHP 5.4 or greater.

## Features

  - high performance parser
  - great readability
  - contextual error reporting at compile &amp; run time
  - HTML5 mode (using the _doctype html_)
  - combine dynamic and static tag classes
  - no tag prefix
  - clear & beautiful HTML output
  - filters
    - :php
    - :cdata
    - :css
    - :javascript
  - you even can write & add own filters throught API

## Public API

    $dumper = new PHPDumper([
    	'tabSize' => 4					// Tab size for output. Default value: 2
	]);
    $dumper->registerVisitor('tag', new AutotagsVisitor());
    $dumper->registerFilter('javascript', new JavaScriptFilter());
    $dumper->registerFilter('cdata', new CDATAFilter());
    $dumper->registerFilter('php', new PHPFilter());
    $dumper->registerFilter('css', new CSSFilter());
    
    // Initialize parser & SlimPHP
    $parser = new Parser(new Lexer([
    	'tabSize' => 2					// Tab size for input. Default value: 2
	]));
    $slim   = new SlimPHP($parser, $dumper);
	
	// Parse a template (either filename or content string)
    echo $slim->render($template);

## Syntax

### Line Endings

**CRLF** and **CR** are converted to **LF** before parsing.

### Indentation

As it's meant to be, SlimPHP supports an _arbitrary length_ indent. Just keep the indent tree consistent throughout the slim template file.

### Tags

A tag is simply a leading word:

	html

for example is converted to `<html></html>`

tags can also have ids:

	div#container

which would render `<div id="container"></div>`

how about some classes?

	div.user-details

renders `<div class="user-details"></div>`

multiple classes? _and_ an id? sure:

	div#foo.bar.baz

renders `<div id="foo" class="bar baz"></div>`

div div div sure is annoying, how about:

	#foo
	.bar

which is syntactic sugar for what we have already been doing, and outputs:

	<div id="foo"></div><div class="bar"></div>

SlimPHP has a feature, called "autotags". It's just snippets for tags. Autotags will expand to basic tags with custom attributes. For example:

	input:text

will expand to `<input type="text" />` & it's the same as `input( type="text" )`, but shorter.
Another examples:

	input:submit( value="Send" )

will become `<input type="submit" value="Send" />`.

It also supports new HTML5 tags such as (`input:email` => `<input type="email"/>`).

### Tag Text

Simply place some content after the tag:

	p wahoo!

renders `<p>wahoo!</p>`.

well cool, but how about large bodies of text:

	p
	  | foo bar baz
	  	rawr rawr
	  	super cool
	  	go Slim go

renders `<p>foo bar baz rawr.....</p>`

Actually want `<?= $something ?>` for some reason? Use `#{}` instead:

	p #{$something}

now we have `<p><?= $something ?></p>`

What if you want to output `#{}` just as it is? You can escape the '#' character in this case:

	p \#{$notSoSpecial}

then we have `<p>#{$notSoSpecial}</p>`

### Verbatim Text

The pipe tells SlimPHP to just copy the line. It essentially escapes any processing. Each following line that is indented greater than the pipe is copied over.

	body
	  p
	    | This line is on the left margin.
	       This line will have one space in front of it.
	         This line will have two spaces in front of it.
	           And so on...

### Inline html <

You can write html tags directly in SlimPHP which allows you to write your templates in a more html like style with closing tags or mix html and Slim style. The leading < works like an implicit |:

	<html>
	  head
	    title Example
	  <body>
	    - if ($articles):
	    - else:
	      table
	        - foreach ($articles as $a):
	          <tr><td>#{$a->name}</td><td>#{$a->description}</td></tr>
	  </body>
	</html>

### Nesting

	ul
	  li one
	  li two
	  li three

### Attributes

SlimPHP currently supports '(' and ')' as attribute indicator and colon(,) or space as delimitor.

	a (href='/login', title='View login page' data-id="13") Login

We need to escape opening parenthesis if it comes to the very beginning character of text node, otherwise no need to escape:

	a (href='/login', title='View login page') \(Login)
	a (href='/login', title='View login page') Login (with Twitter)

Boolean attributes are also supported:

	input(type="checkbox", checked)

Boolean attributes with code will only output the attribute when `true`:

	input(type="checkbox", checked=someValue)

Another possibly awesome feature goes here:

	input:checkbox (#{$user->isAdmin() ? 'checked' : ''} name=is_admin)

Will render just as follows:

	<input <?= $user->isAdmin() ? 'checked' : '' ?> name="is_admin" type="checkbox" />

Note: Leading / trailing whitespace is _ignored_ for attr pairs.

### Doctypes

To add a doctype simply use `doctype` followed by an optional value:

	doctype

Will output the _transitional_ doctype, however:

`doctype html` (or simply `doctype 5`)

Will output HTML5's doctype. Below are the doctypes
defined by default, which can easily be extended:

	$doctypes = array(
		'xml'               => '<?xml version="1.0" encoding="utf-8" ?>',
		'xml ISO-8859-1'    => '<?xml version="1.0" encoding="iso-8859-1" ?>',
		'html'          => '<!DOCTYPE html>',
		'5'             => '<!DOCTYPE html>',
		'1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">',
		'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
		'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
	);

## Comments

### SlimPHP Comments

SlimPHP supports sharp comments (`/ COMMENT`). So SlimPHP block:

	/ SLIMPHP
	- $foo = "<script>";
	p
	  - switch ($foo):
	    - case 2:
	      p.foo= $foo
		/- case 'strong':
		  - strong#name= $foo * 2
	    - case 5:
	      p some text

will be compiled into:

	<?php $foo = "<script>"; ?>
	<p>
	    <?php switch ($foo): ?>
	        <?php case 2: ?>
	            <p class="foo"><?= $foo ?></p>
	        <?php break; ?>
	        <?php case 5: ?>
	            <p>some text</p>
	        <?php break; ?>
	    <?php endswitch; ?>
	</p>

### HTML Comments

SlimPHP supports HTML comments (`/! comment`). So block:

	peanutbutterjelly
	  /! This is the peanutbutterjelly element
	  | I like sandwiches!

will become:

	<peanutbutterjelly>
	  <!-- This is the peanutbutterjelly element -->
	  I like sandwiches!
	</peanutbutterjelly>

As with multiline comments:

	/!
	  p This doesn't render...
	  div
	    h1 Because it's commented out!

that compile to:

	<!--
	  <p>This doesn't render...</p>
	  <div>
	    <h1>Because it's commented out!</h1>
	  </div>
	-->

### IE Conditional Comments

Also, SlimPHP supports IE conditional comments, so:

	/! [if IE]
	  a( href = 'http://www.mozilla.com/en-US/firefox/' )
	    h1 Get Firefox

will be parsed to:

	<!--[if IE]>
	  <a href="http://www.mozilla.com/en-US/firefox/">
	    <h1>Get Firefox</h1>
	  </a>
	<![endif]-->

## Filters

Filters are prefixed with `:`, for example `:javascript` or `:cdata` and
pass the following block of text to an arbitrary function for processing. View the _features_
at the top of this document for available filters.

	body
	  :php
	    $data = 40;
	    $data /= 2;
	    echo $data;

Renders:

	<body>
	  <?php
	    $data = 40;
	    $data /= 2;
	    echo $data;
	  ?>
	</body>

## Code

### Buffered / Non-buffered output

SlimPHP currently supports two classifications of executable code. The first
is prefixed by `-`, and is not buffered:

	- var $foo = 'bar';

This can be used for conditionals, or iteration:

	- foreach ($items as $item):
	  p= $item

Due to SlimPHP's buffering techniques the following is valid as well:

	- if ($foo):
	  ul
	    li yay
	    li foo
	    li worked
	- else:
	  p hey! didnt work

Second is echoed code, which is used to
echo a return value, which is prefixed by `=`:

	- $foo = 'bar'
	= $foo
	h1= $foo

Which outputs

	<?php $foo = 'bar' ?>
	<?= $foo ?>
	<h1><?= $foo ?></h1>

### Code blocks

Also, SlimPHP has Code Blocks, that supports basic PHP template syntax:

	ul
	  - while (true):
	    li item

Will be rendered to:

	<ul>
	  <?php while (true): ?>
	    <li>item</li>
	  <?php endwhile; ?>
	</ul>

But don't forget about colons `:` after instructions start (`- if(true) :`).

There's bunch of default ones: `if`, `else`, `elseif`, `while`, `for`, `foreach`, `switch`, `case`.

Here's another convenient way to write multiline PHP code block:

	- $user = [ \
		'username' 		=> 'Bit-Coin',
		'first_name' 	=> 'Bit-Coin',
	  ];

This will be interpreted as:

	<?php
	    $user = [ 
	      'username'     => 'Bit-Coin',
	      'first_name'   => 'Bit-Coin',
	    ];
	?>