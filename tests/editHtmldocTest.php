<?php
use hexydec\html\htmldoc;

final class editHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanAppendHtml() {
		$obj = new htmldoc();
		$obj->load('<h1>Appended</h1>');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'append' => '<h1>Appended</h1>',
				'output' => '<!DOCTYPE html><html><body><h1>Appended</h1></body></html>',
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
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'append' => $obj,
				'output' => '<!DOCTYPE html><html><body><h1>Appended</h1></body></html>',
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
		$obj->load('<h1>Prepended</h1>');
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'prepend' => '<h1>Prepended</h1>',
				'output' => '<!DOCTYPE html><html><body><h1>Prepended</h1></body></html>',
			],
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'prepend' => $obj,
				'output' => '<!DOCTYPE html><html><body><h1>Prepended</h1></body></html>',
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
