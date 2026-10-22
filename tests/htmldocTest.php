<?php
use hexydec\html\htmldoc;

final class htmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanOpenDocument() : void {
		$doc = new htmldoc();
		$this->assertTrue($doc->open('https://google.co.uk/') !== false, 'Can open external URL');
		$this->assertTrue($doc->load('<html><meta charset="utf-8"></html>') !== false, 'Can open document from string');
		$this->assertTrue($doc->load('<html><meta http-equiv="Content-Type" content="charset=utf-8"></html>') !== false, 'Can open document from string');
	}

	public function testCanAccessNodes() : void {
		$warnings = [];
		\set_error_handler(function (int $type, string $msg) use (&$warnings) : bool {
			$warnings[] = $msg;
			return true;
		});

		// tag properties are read from the first tag, and are null without a warning when there is none
		$doc = new htmldoc();
		$this->assertNull($doc->tagName);
		$doc->load('Text only');
		$this->assertNull($doc->tagName);
		$doc->load('Text <p>Paragraph</p>');
		$this->assertEquals('p', $doc->tagName);
		\restore_error_handler();
		$this->assertEquals([], $warnings);

		// positions count tags only, skipping the doctype, text, comments, and CDATA
		$doc->load('<!DOCTYPE html>Text<p>First</p><!-- comment --><![CDATA[x]]><b>Last</b>');
		$this->assertEquals('<p>First</p>', $doc->first()->html());
		$this->assertEquals('<b>Last</b>', $doc->last()->html());
		$this->assertEquals('<b>Last</b>', $doc->eq(1)->html());
		$this->assertEquals(0, $doc->eq(2)->length);

		// nodes can be appended and replaced, but only nodes at integer positions
		$other = new htmldoc();
		$other->load('<i>Appended</i><u>Replaced</u>');
		$doc->load('<p>Paragraph</p>');
		$doc[] = $other->get(0);
		$doc[0] = $other->get(1);
		$this->assertEquals('<u>Replaced</u><i>Appended</i>', $doc->html());
		foreach ([fn () => $doc[] = 'text', fn () => $doc['key'] = $other->get(0)] AS $item) {
			try {
				$item();
				$this->fail('A TypeError was not thrown');
			} catch (\TypeError $e) {
				$this->assertInstanceOf(\TypeError::class, $e);
			}
		}

		// positions past the end return null
		$doc->load('<p>Paragraph</p>');
		$this->assertNull($doc[1]);
		$doc->next();
		$this->assertNull($doc->current());

		// the attribute counts used to sort attributes are reset for each document
		$cache = new \ReflectionProperty(htmldoc::class, 'cache');
		$doc->load('<p class="a" id="b"></p>');
		$first = $cache->getValue($doc);
		$doc->load('<p class="a" id="b"></p>');
		$this->assertEquals($first, $cache->getValue($doc));
	}

	public function testCanReadHeaderValues() : void {
		$doc = new class extends htmldoc {
			public function header(array $headers, string $name, ?string $parameter = null) : ?string {
				return $this->getHeaderValue($headers, $name, $parameter);
			}
		};
		$redirect = ['HTTP/1.1 301 Moved Permanently', 'Location: /page', 'Content-Type: text/html; charset=iso-8859-1'];
		$tests = [
			[[...$redirect, 'HTTP/1.1 200 OK', 'Content-Type: text/html; charset=UTF-8'], 'UTF-8'],
			[[...$redirect, 'HTTP/1.1 200 OK', 'Content-Type: text/html'], null],
			[['HTTP/1.1 200 OK', 'content-type: text/html; Charset="utf-8"'], 'utf-8'],
			[['HTTP/1.1 200 OK', 'Content-Type: text/html; charset=utf-8; foo=bar'], 'utf-8'],
			[['HTTP/1.1 200 OK', 'Content-Type: text/html'], null],
			[[], null]
		];
		foreach ($tests AS [$headers, $charset]) {
			$this->assertEquals($charset, $doc->header($headers, 'Content-Type', 'charset'));
		}
		$this->assertEquals('text/html; charset=UTF-8', $doc->header($tests[0][0], 'Content-Type'));
	}

	public function testCanParseDocument() : void {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/document.html')) {
			$html = file_get_contents(__DIR__.'/templates/document.html');
			$this->assertEquals($html, $doc->save(), 'Parsed document successfully');
		}
	}

	public function testCanFailCorrectly() : void {
		$url = __DIR__.'/does-not-exists.html';
		$doc = new htmldoc();
		$this->assertEquals(false, $doc->open($url, null, $error), 'Correctly failed to open file that doesn\'t exist');
		$this->assertEquals('Could not open file "'.$url.'"', $error, 'Correct error message generated');
	}

	public function testCanHandleDifficultHtml() : void {
		$tests = Array(
			'<!-->' => '<!---->',
			'<!--->' => '<!---->',
			'<!doctype html>' => '<!DOCTYPE html>',
			'<a href="#"">Extra quote</a>' => '<a href="#">Extra quote</a>',
			'<p title="</p>">Closing tag in title</p>' => '<p title="&lt;/p>">Closing tag in title</p>',
			'<p title=" <!-- hello world --> ">Comment in title</p>' => '<p title=" &lt;!-- hello world --> ">Comment in title</p>',
			'<p title="<![CDATA[ hello world ]]>">Comment in title</p>' => '<p title="&lt;![CDATA[ hello world ]]>">Comment in title</p>',
			'<section><div><h1>Wrong closing tag order</div></h1></section>' => '<section><div><h1>Wrong closing tag order</h1></div></section>',
			'<section><div><h1>Wrong closing tag order</div></h1></section><p>after</p>' => '<section><div><h1>Wrong closing tag order</h1></div></section><p>after</p>',
			'<p class=test data-test=tester>Unquoted attributes</p>' => '<p class="test" data-test="tester">Unquoted attributes</p>',
			'<div>Stray</span> closing tag</div>' => '<div>Stray closing tag</div>',
			// '<script>let test = "</script><div>Test</div>";</script>' => '<script>let test = "</script><div>Test</div>";</script>',
			'<li>Something with a &nbsp; or one at the end like this &nbsp;</li>' => '<li>Something with a '.mb_convert_encoding('&nbsp;', 'UTF-8', 'HTML-ENTITIES').' or one at the end like this '.mb_convert_encoding('&nbsp;', 'UTF-8', 'HTML-ENTITIES').'</li>',
			'<a
    href="https://github.com/hexydec"
    class = \'test\'
    title=test
    >
        Test
</a>' => '<a href="https://github.com/hexydec" class="test" title="test">
        Test
</a>',
			'<div><p><p>test</p></p></div>' => '<div><p><p>test</p></div>',
			'<div><p>test</a></p></div>' => '<div><p>test</p></div>',
			'<div><p><p>test</p></p><p>test 2</p></div>' => '<div><p><p>test</p><p>test 2</p></div>',
			'<img src="test.png"></img>' => '<img src="test.png">',
			'<div class="test" />' => '<div class="test"></div>',
			"<div\n\r\n\t   >Test</div\n\r\n\t    >" => '<div>Test</div>',
			'<div><table><tbody><tr><td>Cell 1<td>cell 2</table></div><p>hi' => '<div><table><tbody><tr><td>Cell 1<td>cell 2</table></div><p>hi',
			'<div>hi</p></div>' => '<div>hi</div>',
			'<p><div>hi</p></div>' => '<p><div>hi</div>',
			'<script>
				<!--
					alert("Hey there");
				//-->
			</script>' => '<script>
					alert("Hey there");
				//-->
			</script>',
			'<p data-attr="attribute"data-another="too close">No space between attributes</p>' => '<p data-attr="attribute" data-another="too close">No space between attributes</p>',
			'<p class="test""><span class="something">Extra Quote</span></p>' => '<p class="test"><span class="something">Extra Quote</span></p>',
			'<a href="الشركاء/شركاء-الصناعة/">هنا</a>' => '<a href="الشركاء/شركاء-الصناعة/">هنا</a>',
			'<p>a < b and 1<2</p>' => '<p>a &lt; b and 1&lt;2</p>',
			'<p>< leading less than</p>' => '<p>&lt; leading less than</p>',
			'Top level <3 text' => 'Top level &lt;3 text',
			'<p>Trailing less than <</p>' => '<p>Trailing less than &lt;</p>',
			'<?xml version="1.0"?><p>Processing instruction</p>' => '<p>Processing instruction</p>',
			'<a href=?q=abc==>Trailing equals</a>' => '<a href="?q=abc==">Trailing equals</a>',
			'<i data-x==abc>Leading equals</i>' => '<i data-x="=abc">Leading equals</i>',
			'<img src=data:image/png;base64,QUJD=>' => '<img src="data:image/png;base64,QUJD=">',
			'<p class = "test" title =  \'spaced\'>Spaced separator</p>' => '<p class="test" title="spaced">Spaced separator</p>',
			'<p class="first" class="second" title title="second">Duplicate attributes</p>' => '<p class="first" title>Duplicate attributes</p>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}
	}

	public function testCanParseTagsCaseInsensitively() : void {
		$tests = [
			'<div><DIV>Nested</DIV>Outer</div>' => '<div><DIV>Nested</DIV>Outer</div>',
			'<UL><LI>One<LI>Two</UL>' => '<UL><LI>One</LI><LI>Two</LI></UL>',
			'<P>One<P>Two' => '<P>One</P><P>Two</P>',
			'<p>Text<IMG src="x">More<BR>text</p>' => '<p>Text<IMG src="x">More<BR>text</p>',
			'<SCRIPT>if (a<b) x();</SCRIPT>' => '<SCRIPT>if (a<b) x();</SCRIPT>',
			'<svg><linearGradient id="g"><stop offset="0"/></linearGradient><feBlend in="a"/></svg>' => '<svg><linearGradient id="g"><stop offset="0"/></linearGradient><feBlend in="a"/></svg>'
		];
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$this->assertEquals($output, $doc->html(['closetags' => true]));
		}

		// selectors match regardless of case
		$doc->load('<DIV><linearGradient></linearGradient></DIV>');
		$this->assertEquals(1, $doc->find('div')->length);
		$this->assertEquals(1, $doc->find('LINEARGRADIENT')->length);

		// inline elements keep their surrounding whitespace without lowercasing
		$doc->load('<p>Some <B>bold</B> text</p>');
		$doc->minify(['lowercase' => false]);
		$this->assertEquals('<p>Some <B>bold</B> text', $doc->html());
	}

	public function testCanParseOmittedClosingTags() : void {
		$tests = [
			'<html><head><title>Test</title><body><p>Text</p></html>' => '<html><head><title>Test</title></head><body><p>Text</p></body></html>',
			'<dl><dt>Term<dd>Definition<dt>Term 2</dl>' => '<dl><dt>Term</dt><dd>Definition</dd><dt>Term 2</dt></dl>',
			'<table><tr><td>A<td>B<tr><td>C</table>' => '<table><tr><td>A</td><td>B</td></tr><tr><td>C</td></tr></table>',
			'<table><thead><tr><th>Head<tbody><tr><td>Body<tfoot><tr><td>Foot</table>' => '<table><thead><tr><th>Head</th></tr></thead><tbody><tr><td>Body</td></tr></tbody><tfoot><tr><td>Foot</td></tr></tfoot></table>',
			'<table><tr><td>Cell<th>Header</table>' => '<table><tr><td>Cell</td><th>Header</th></tr></table>',
			'<select><option>A<optgroup label="G"><option>B<optgroup label="H"><option>C</select>' => '<select><option>A</option><optgroup label="G"><option>B</option></optgroup><optgroup label="H"><option>C</option></optgroup></select>',
			'<p>Paragraph<div>Block</div><p>Paragraph<ul><li>Item</ul>' => '<p>Paragraph</p><div>Block</div><p>Paragraph</p><ul><li>Item</li></ul>',
			'<p>Paragraph<table><tr><td>Cell</table>' => '<p>Paragraph<table><tr><td>Cell</td></tr></table></p>'
		];
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$this->assertEquals($output, $doc->html(['closetags' => true]));
		}

		// the tree matches the document structure
		$doc->load('<html><head><title>Test</title><body><p>Text</p></html>');
		$this->assertEquals(0, $doc->find('head > body')->length);
		$this->assertEquals(1, $doc->find('html > body')->length);

		// minified output parses back to the same tree
		$html = '<html><head><title>Test</title></head><body><dl><dt>Term</dt><dd>Definition</dd></dl><p>Text</p><div>Block</div><table><tr><td>A</td><td>B</td></tr></table></body></html>';
		$doc->load($html);
		$doc->minify();
		$minified = $doc->html();
		$doc->load($minified);
		$this->assertEquals($html, $doc->html(['closetags' => true]));
	}

	public function testCanHandleCdata() : void {
		$tests = [
			[
				'input' => '<svg><text><![CDATA[x<y]]></text></svg>',
				'html' => '<svg><text><![CDATA[x<y]]></text></svg>',
				'xml' => '<svg><text><![CDATA[x<y]]></text></svg>',
				'text' => 'x<y'
			],
			[
				'input' => '<div><![CDATA[Content]]></div>',
				'html' => '<div><![CDATA[Content]]></div>',
				'xml' => '<div><![CDATA[Content]]></div>',
				'text' => 'Content'
			],
			[
				'input' => '<svg><style><![CDATA[a{color:red}]]></style></svg>',
				'html' => '<svg><style><![CDATA[a{color:red}]]></style></svg>',
				'xml' => '<svg><style><![CDATA[a{color:red}]]></style></svg>',
				'text' => ''
			],
			[
				'input' => "<script>//<![CDATA[\nvar a = 1;\n//]]></script>",
				'html' => "<script>//<![CDATA[\nvar a = 1;\n//]]></script>",
				'xml' => "<script><![CDATA[//<![CDATA[\nvar a = 1;\n//]]]]><![CDATA[>]]></script>",
				'text' => ''
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input']);
			$this->assertEquals($item['html'], $doc->html());
			$this->assertEquals($item['xml'], $doc->html(['xml' => true]));
			$this->assertEquals($item['text'], $doc->text());
		}

		// the minifier receives the code without the CDATA wrapper, which is only restored where the content needs it
		$doc->load("<style>\n<![CDATA[\np { color: red; }\n]]>\n</style>");
		$doc->minify();
		$this->assertEquals('<style>p{color:red}</style>', $doc->html());
		$this->assertEquals('<style>p{color:red}</style>', $doc->html(['xml' => true]));
	}

	public function testCanMinifyCdata() : void {
		$tests = [

			// empty content and content that can be written as text are not wrapped
			[
				'input' => '<script src="a.js"></script><script><![CDATA[var a = 1;]]></script>',
				'minify' => [],
				'html' => '<script src=a.js></script><script>var a=1</script>',
				'xml' => '<script src="a.js"></script><script>var a=1</script>'
			],

			// XML wraps content containing < & or ]]>
			[
				'input' => '<script>if (a < b) {}</script><style>p:after{content:"&"}</style><script>var a = "]]>";</script>',
				'minify' => [],
				'html' => '<script>if(a<b){}</script><style>p:after{content:"&"}</style><script>var a="]]>"</script>',
				'xml' => '<script><![CDATA[if(a<b){}]]></script><style><![CDATA[p:after{content:"&"}]]></style><script><![CDATA[var a="]]]]><![CDATA[>"]]></script>'
			],

			// foreign content is parsed as markup, so is wrapped where needed in HTML too
			[
				'input' => '<svg><script><![CDATA[if (a < b) {}]]></script><style><![CDATA[a{color:red}]]></style></svg>',
				'minify' => [],
				'html' => '<svg><script><![CDATA[if(a<b){}]]></script><style>a{color:red}</style></svg>',
				'xml' => '<svg><script><![CDATA[if(a<b){}]]></script><style>a{color:red}</style></svg>'
			],

			// CDATA sections outside custom content are output as they are
			[
				'input' => '<svg><text><![CDATA[x<y]]></text></svg>',
				'minify' => [],
				'html' => '<svg><text><![CDATA[x<y]]></text></svg>',
				'xml' => '<svg><text><![CDATA[x<y]]></text></svg>'
			],

			// the source wrappers are kept when the option is off
			[
				'input' => '<script><![CDATA[var a = 1;]]></script><style>p { color: red; }</style>',
				'minify' => ['cdata' => false],
				'html' => '<script><![CDATA[var a=1]]></script><style>p{color:red}</style>',
				'xml' => '<script><![CDATA[var a=1]]></script><style>p{color:red}</style>'
			]
		];
		foreach ($tests AS $item) {
			$doc = new htmldoc();
			$doc->load($item['input']);
			$doc->minify($item['minify']);
			$this->assertEquals($item['html'], $doc->html());
			$this->assertEquals($item['xml'], $doc->html(['xml' => true]));
		}
	}

	public function testCanEncodeAttributes() : void {
		$tests = Array(
			'<p title="Test characters are encoded & <> \'"></p>' => '<p title="Test characters are encoded &amp; &lt;> \'"></p>',
			'<p title="test single quotes \'"></p>' => '<p title="test single quotes \'"></p>',
			'<p title="test single quotes &apos;"></p>' => '<p title="test single quotes \'"></p>',
			'<p title="test single quotes &#39;"></p>' => '<p title="test single quotes \'"></p>',
			"<p title='test single quotes &#39;'></p>" => '<p title="test single quotes \'"></p>',
			"<p disabled></p>" => '<p disabled></p>',
			'<p disabled title="test double attribute"></p>' => '<p disabled title="test double attribute"></p>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}

		// single quotes
		if ($doc->load('<p title="test single quotes &quot; \'"></p>', mb_internal_encoding())) {
			$this->assertEquals("<p title='test single quotes \" &#39;'></p>", $doc->html(Array('quotestyle' => 'single')));
		}

		// minimal - swap quote style
		if ($doc->load('<p data-json="{&quot;foo&quot;: &quot;bar&quot;}"></p>', mb_internal_encoding())) {
			$this->assertEquals('<p data-json=\'{"foo": "bar"}\'></p>', $doc->html(['quotestyle' => 'minimal']));
		}
	}

	public function testCanEncodeTextNodes() : void {
		$tests = Array(
			'<p>"Test&"</p>' => '<p>"Test&amp;"</p>',
			'<p>&#128512; &#128513; &#128514; &#129315;</p>' => '<p>😀 😁 😂 🤣</p>',
			'<p>It&apos;s &check; &lt;b&gt;</p>' => '<p>It\'s ✓ &lt;b&gt;</p>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}

		// HTML5 entities are decoded in text the same as in attributes
		$doc->load('<p title="It&apos;s">It&apos;s</p>');
		$this->assertEquals($doc->attr('title'), $doc->text());
	}

	public function testCanConvertEncodings() : void {
		$doc = new htmldoc();

		// test input encoding conversion
		$input = \mb_convert_encoding('<p title="Héllo ÿ &#128512;">Héllo ÿ &#129315;</p>', 'iso-8859-1');
		$output = '<p title="Héllo ÿ 😀">Héllo ÿ 🤣</p>';
		if ($doc->load($input, 'iso-8859-1')) {
			$this->assertEquals($output, $doc->html());
			$this->assertEquals(\mb_convert_encoding('<p title="Héllo ÿ &#x1F600;">Héllo ÿ &#x1F923;</p>', 'iso-8859-1'), $doc->save(null, Array('charset' => 'iso-8859-1')));
		}
	}

	public function testCanProduceXhtml() : void {
		$tests = Array(
			"<p disabled></p>" => '<p disabled=""/>',
			"<p title=unquoted></p>" => '<p title="unquoted"/>',
			"<p title='disabled'></p>" => '<p title="disabled"/>',
			'<p class="para__first">Test<p class="para__second">Test 2' => '<p class="para__first">Test</p><p class="para__second">Test 2</p>',
			"<img src='test.png' alt=''>" => '<img src="test.png" alt=""/>',
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html(Array('xml' => true)));
			}
		}
	}

	public function testCanSaveDocument() : void {
		$doc = new htmldoc();
		if ($doc->load('<div>Hello world</div>')) {
			$file = dirname(__DIR__).'/save.html';
			if (file_exists($file)) {
				unlink($file);
			}
			$this->assertEquals($doc->html(), $doc->save($file), 'Can save document');
			$this->assertEquals(true, file_exists($file), 'Saved document ecists');
			$this->assertEquals('<div>Hello world</div>', file_get_contents($file), 'Saved document has the correct content');
			unlink($file);
		}

		// characters the charset cannot represent become entities, using the configured charset or the one passed in
		$substitute = \mb_substitute_character();
		$expected = '<p>&#x20AC;5 &#x2014; caf'."\xE9".' &#x1F600;</p>';
		$doc = new htmldoc(['output' => ['charset' => 'ISO-8859-1']]);
		$doc->load('<p>€5 — café 😀</p>');
		$this->assertEquals($expected, $doc->save());
		$doc = new htmldoc();
		$doc->load('<p>€5 — café 😀</p>');
		$this->assertEquals($expected, $doc->save(null, ['charset' => 'ISO-8859-1']));
		$this->assertEquals('<p>€5 — café 😀</p>', $doc->save(null, ['charset' => 'utf-8']));
		$this->assertEquals($substitute, \mb_substitute_character());
	}
}
