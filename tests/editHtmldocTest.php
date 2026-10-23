<?php
use hexydec\html\htmldoc;

final class editHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanAppendHtml() : void {
		$obj = new htmldoc();
		$obj->load('Data to <span>append</span> here');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'append' => 'Data to <span>append</span> here',
				'output' => '<!DOCTYPE html><html><body>Data to <span>append</span> here</body></html>',
			],
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'append' => $obj,
				'output' => '<!DOCTYPE html><html><body>Data to <span>append</span> here</body></html>',
			],
			[
				'input' => '<ul><li></li><li></li><li></li><li></li></ul>',
				'find' => 'li',
				'append' => '<h3>Appended</h3>',
				'output' => '<ul><li><h3>Appended</h3></li><li><h3>Appended</h3></li><li><h3>Appended</h3></li><li><h3>Appended</h3></li></ul>',
			],
			[
				'input' => '<ul><li></li><li></li><li></li><li></li></ul>',
				'find' => 'li',
				'append' => '<h3>Appended</h3><p>Test <span>this</span></p>',
				'output' => '<ul><li><h3>Appended</h3><p>Test <span>this</span></p></li><li><h3>Appended</h3><p>Test <span>this</span></p></li><li><h3>Appended</h3><p>Test <span>this</span></p></li><li><h3>Appended</h3><p>Test <span>this</span></p></li></ul>',
			],
			[
				'input' => '<div><div></div></div>',
				'find' => 'div',
				'append' => '<h3>Appended</h3><p>Test <span>this</span></p>',
				'output' => '<div><div><h3>Appended</h3><p>Test <span>this</span></p></div><h3>Appended</h3><p>Test <span>this</span></p></div>',
			],
		];

		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->append($item['append']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanPrependHtml() : void {
		$obj = new htmldoc();
		$obj->load('Data to <span>prepend</span> here');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'prepend' => 'Data to <span>prepend</span> here',
				'output' => '<!DOCTYPE html><html><body>Data to <span>prepend</span> here</body></html>',
			],
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'prepend' => $obj,
				'output' => '<!DOCTYPE html><html><body>Data to <span>prepend</span> here</body></html>',
			],
			[
				'input' => '<ul><li></li><li></li><li></li><li></li></ul>',
				'find' => 'li',
				'prepend' => '<h3>Prepended</h3>',
				'output' => '<ul><li><h3>Prepended</h3></li><li><h3>Prepended</h3></li><li><h3>Prepended</h3></li><li><h3>Prepended</h3></li></ul>',
			],
			[
				'input' => '<ul><li></li><li></li><li></li><li></li></ul>',
				'find' => 'li',
				'prepend' => '<h3>Prepended</h3><p>Test <span>this</span></p>',
				'output' => '<ul><li><h3>Prepended</h3><p>Test <span>this</span></p></li><li><h3>Prepended</h3><p>Test <span>this</span></p></li><li><h3>Prepended</h3><p>Test <span>this</span></p></li><li><h3>Prepended</h3><p>Test <span>this</span></p></li></ul>',
			],
			[
				'input' => '<div><div></div></div>',
				'find' => 'div',
				'prepend' => '<h3>Prepended</h3><p>Test <span>this</span></p>',
				'output' => '<div><h3>Prepended</h3><p>Test <span>this</span></p><div><h3>Prepended</h3><p>Test <span>this</span></p></div></div>',
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->prepend($item['prepend']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanInsertHtmlBefore() : void {
		$obj = new htmldoc();
		$obj->load('Data to <span>insert</span> before');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body><p>Test</p></body></html>',
				'find' => 'p',
				'before' => 'Data to <span>insert</span> before',
				'output' => '<!DOCTYPE html><html><body>Data to <span>insert</span> before<p>Test</p></body></html>',
			],
			[
				'input' => '<!DOCTYPE html><html><body><p>Test</p></body></html>',
				'find' => 'p',
				'before' => $obj,
				'output' => '<!DOCTYPE html><html><body>Data to <span>insert</span> before<p>Test</p></body></html>',
			],
			[
				'input' => '<div><p>Test</p><p>Test</p><p>Test</p></div>',
				'find' => 'p',
				'before' => '<h3>Before</h3>',
				'output' => '<div><h3>Before</h3><p>Test</p><h3>Before</h3><p>Test</p><h3>Before</h3><p>Test</p></div>',
			],
			[
				'input' => '<div><p>Test</p><p>Test</p><p>Test</p></div>',
				'find' => 'p',
				'before' => '<h3>Before</h3><p>Test <span>this</span></p>',
				'output' => '<div><h3>Before</h3><p>Test <span>this</span></p><p>Test</p><h3>Before</h3><p>Test <span>this</span></p><p>Test</p><h3>Before</h3><p>Test <span>this</span></p><p>Test</p></div>',
			],
			[
				'input' => '<body><div><div></div></div></body>',
				'find' => 'div',
				'before' => '<h3>Before</h3><p>Test <span>this</span></p>',
				'output' => '<body><h3>Before</h3><p>Test <span>this</span></p><div><h3>Before</h3><p>Test <span>this</span></p><div></div></div></body>',
			],
			[ // top level nodes
				'input' => '<p>Test</p><p>Test</p>',
				'find' => 'p',
				'before' => '<hr>',
				'output' => '<hr><p>Test</p><hr><p>Test</p>',
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->before($item['before']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanInsertHtmlAfter() : void {
		$obj = new htmldoc();
		$obj->load('Data to <span>insert</span> after');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body><p>Test</p></body></html>',
				'find' => 'p',
				'after' => 'Data to <span>insert</span> after',
				'output' => '<!DOCTYPE html><html><body><p>Test</p>Data to <span>insert</span> after</body></html>',
			],
			[
				'input' => '<!DOCTYPE html><html><body><p>Test</p></body></html>',
				'find' => 'p',
				'after' => $obj,
				'output' => '<!DOCTYPE html><html><body><p>Test</p>Data to <span>insert</span> after</body></html>',
			],
			[
				'input' => '<div><p>Test</p><p>Test</p><p>Test</p></div>',
				'find' => 'p',
				'after' => '<h3>After</h3>',
				'output' => '<div><p>Test</p><h3>After</h3><p>Test</p><h3>After</h3><p>Test</p><h3>After</h3></div>',
			],
			[
				'input' => '<div><p>Test</p><p>Test</p><p>Test</p></div>',
				'find' => 'p',
				'after' => '<h3>After</h3><p>Test <span>this</span></p>',
				'output' => '<div><p>Test</p><h3>After</h3><p>Test <span>this</span></p><p>Test</p><h3>After</h3><p>Test <span>this</span></p><p>Test</p><h3>After</h3><p>Test <span>this</span></p></div>',
			],
			[
				'input' => '<body><div><div></div></div></body>',
				'find' => 'div',
				'after' => '<h3>After</h3><p>Test <span>this</span></p>',
				'output' => '<body><div><div></div><h3>After</h3><p>Test <span>this</span></p></div><h3>After</h3><p>Test <span>this</span></p></body>',
			],
			[ // test with text nodes
				'input' => '<body>
						<div>
							<div>Text Node</div>
						</div>
					</body>',
				'find' => 'div',
				'after' => '<h3>After</h3><p>Test <span>this</span></p>',
				'output' => '<body>
						<div>
							<div>Text Node</div><h3>After</h3><p>Test <span>this</span></p>
						</div><h3>After</h3><p>Test <span>this</span></p>
					</body>',
			],
			[ // top level nodes
				'input' => '<div></div>',
				'find' => 'div',
				'after' => '<p>Test</p>',
				'output' => '<div></div><p>Test</p>',
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->after($item['after']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanEditAppendedNodes() : void {
		$doc = new htmldoc();

		// insert next to a node nested inside appended content
		$doc->load('<div id="target"></div>');
		$doc->find('#target')->append('<section><span></span></section>');
		$doc->find('span')->after('<b></b>');
		$this->assertEquals('<div id="target"><section><span></span><b></b></section></div>', $doc->html());

		// remove nodes nested inside content appended to multiple nodes
		$doc->load('<ul><li></li><li></li></ul>');
		$doc->find('li')->append('<section><span></span></section>');
		$doc->remove('span');
		$this->assertEquals('<ul><li><section></section></li><li><section></section></li></ul>', $doc->html());

		// every node type accepts a parent without raising a deprecation
		$deprecations = [];
		\set_error_handler(function (int $type, string $msg) use (&$deprecations) : bool {
			$deprecations[] = $msg;
			return true;
		}, E_DEPRECATED);
		$doc->load('<div></div>');
		$doc->find('div')->append('<!-- comment --><div><script>var a = 1;</script><!-- nested --></div>');
		\restore_error_handler();
		$this->assertEquals([], $deprecations);
		$this->assertEquals('<div><!-- comment --><div><script>var a = 1;</script><!-- nested --></div></div>', $doc->html());
	}

	public function testCanRemoveNodes() : void {
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body><h1>Remove</h1></body></html>',
				'remove' => 'h1',
				'output' => '<!DOCTYPE html><html><body></body></html>',
			],
			[
				'input' => '<ul><li><h3>Remove</h3></li><li><h3>Remove</h3></li><li><h3>Remove</h3></li><li><h3>Remove</h3></li></ul>',
				'remove' => 'h3',
				'output' => '<ul><li></li><li></li><li></li><li></li></ul>',
			],
			[
				'input' => '<ul><li><h3>Remove</h3><p>Test <span>this</span></p></li><li><h3>Remove</h3><p>Test <span>this</span></p></li><li><h3>Remove</h3><p>Test <span>this</span></p></li><li><h3>Remove</h3><p>Test <span>this</span></p></li></ul>',
				'remove' => 'h3, p',
				'output' => '<ul><li></li><li></li><li></li><li></li></ul>',
			],
			[
				'input' => '<div><h3>Remove</h3><p>Test <span>this</span></p><div><h3>Remove</h3><p>Test <span>this</span></p></div></div>',
				'remove' => 'h3, p',
				'output' => '<div><div></div></div>',
			],
			[ // top level nodes
				'input' => '<div></div><p>Remove</p>',
				'remove' => 'p',
				'output' => '<div></div>',
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->remove($item['remove']);
			$this->assertEquals($item['output'], $doc->html());
		}

		// on a collection, the selector matches the collection's own tags as well as their contents
		$doc->load('<div class="a"></div><div class="b"><p class="a"></p></div><div class="c"></div>');
		$doc->find('div')->remove('.a');
		$this->assertEquals('<div class="b"></div><div class="c"></div>', $doc->html());
	}

	public function testCanWrapNodes() : void {
		$tests = [
			[ // each tag gets its own copy of the wrapper
				'input' => '<ul><li>1</li><li>2</li></ul>',
				'find' => 'li',
				'wrap' => '<b class="a"></b>',
				'output' => '<ul><b class="a"><li>1</li></b><b class="a"><li>2</li></b></ul>'
			],
			[ // the tag goes inside the innermost first tag, after its contents
				'input' => '<p>a</p>',
				'find' => 'p',
				'wrap' => '<div><section><span>b</span></section><i></i></div>',
				'output' => '<div><section><span>b<p>a</p></span></section><i></i></div>'
			],
			[ // only the first tag is used as the wrapper
				'input' => '<p>a</p>',
				'find' => 'p',
				'wrap' => 'text <div></div><section></section>',
				'output' => '<div><p>a</p></div>'
			],
			[ // there is nothing to wrap with
				'input' => '<p>a</p>',
				'find' => 'p',
				'wrap' => 'text',
				'output' => '<p>a</p>'
			],
			[
				'input' => '<p>a</p>',
				'find' => 'p',
				'wrap' => '',
				'output' => '<p>a</p>'
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input']);
			$doc->find($item['find'])->wrap($item['wrap']);
			$this->assertEquals($item['output'], $doc->html());
		}

		// a document can be the wrapper, which is copied, and the wrapped tags can still be edited
		$wrapper = new htmldoc();
		$wrapper->load('<div></div>');
		$doc->load('<p>a</p>');
		$doc->find('p')->wrap($wrapper)->attr('class', 'b');
		$this->assertEquals('<div><p class="b">a</p></div>', $doc->html());
		$this->assertEquals('<div></div>', $wrapper->html());

		// unwrapping reverses it
		$doc->find('p')->unwrap();
		$this->assertEquals('<p class="b">a</p>', $doc->html());
	}

	public function testCanUnwrapNodes() : void {
		$tests = [
			[ // the parent's other children are kept too
				'input' => '<p><span>a</span> b <i>c</i></p>',
				'find' => 'span',
				'selector' => null,
				'output' => '<span>a</span> b <i>c</i>'
			],
			[ // siblings share a parent, which is only removed once
				'input' => '<div><p><b>a</b><b>b</b></p></div>',
				'find' => 'b',
				'selector' => null,
				'output' => '<div><b>a</b><b>b</b></div>'
			],
			[ // nested parents are each removed
				'input' => '<div><p><span>a</span></p></div>',
				'find' => 'p, span',
				'selector' => null,
				'output' => '<span>a</span>'
			],
			[ // the parent must match the selector
				'input' => '<section><p><b>a</b></p></section><div><b>b</b></div>',
				'find' => 'b',
				'selector' => 'div',
				'output' => '<section><p><b>a</b></p></section><b>b</b>'
			],
			[
				'input' => '<section><p><b>a</b></p></section><div><b>b</b></div>',
				'find' => 'b',
				'selector' => 'p, div',
				'output' => '<section><b>a</b></section><b>b</b>'
			],
			[
				'input' => '<p><b>a</b></p>',
				'find' => 'b',
				'selector' => '#none',
				'output' => '<p><b>a</b></p>'
			],
			[ // top level tags have no parent tag to remove
				'input' => '<p>a</p>',
				'find' => 'p',
				'selector' => null,
				'output' => '<p>a</p>'
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input']);
			$doc->find($item['find'])->unwrap($item['selector']);
			$this->assertEquals($item['output'], $doc->html());
		}

		// the unwrapped tags are moved rather than copied, so can still be edited
		$doc->load('<div><span>a</span></div>');
		$doc->find('span')->unwrap()->attr('class', 'b');
		$this->assertEquals('<span class="b">a</span>', $doc->html());
		$this->assertEquals(0, $doc->find('span')->parent()->length);
	}
}
