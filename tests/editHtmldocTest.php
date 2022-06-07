<?php
use hexydec\html\htmldoc;

final class editHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanAppendHtml() {
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

	public function testCanPrependHtml() {
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

	public function testCanInserHtmlBefore() {
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
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->before($item['before']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanInserHtmlAfter() {
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
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->after($item['after']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanRemoveNodes() {
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
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->remove($item['remove']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}
}
