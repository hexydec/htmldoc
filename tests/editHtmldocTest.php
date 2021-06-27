<?php
use hexydec\html\htmldoc;

final class editHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanAppendHtml() {
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
			]
		];
		$this->testHtml($tests);
	}

	public function testCanPrependHtml() {
		$tests = [
			[
				'input' => '<!DOCTYPE html><html><body></body></html>',
				'find' => 'body',
				'prepend' => '<h1>Prepended</h1>',
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
		$this->testHtml($tests);
	}

	protected function testHtml(array $tests) {
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$find = $doc->find($item['find']);
			if (isset($item['append'])) {
				$find->append($item['append']);
			} elseif (isset($item['prepend'])) {
				$find->prepend($item['prepend']);
			}
			$this->assertEquals($item['output'], $doc->html());
		}
	}
}
