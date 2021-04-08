<?php
use hexydec\html\htmldoc;

final class htmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanOpenDocument() {
		$doc = new htmldoc();
		$this->assertTrue($doc->open('https://google.co.uk/') !== false, 'Can open external URL');
		$this->assertTrue($doc->load('<html><meta charset="utf-8"></html>') !== false, 'Can open document from string');
		$this->assertTrue($doc->load('<html><meta http-equiv="Content-Type" content="charset=utf-8"></html>') !== false, 'Can open document from string');
	}

	public function testCanParseDocument() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/document.html')) {
			$html = file_get_contents(__DIR__.'/templates/document.html');
			$this->assertEquals($html, $doc->save(), 'Parsed document successfully');
		}
	}

	public function testCanFailCorrectly() {
		$url = __DIR__.'/does-not-exists.html';
		$doc = new htmldoc();
		$this->assertEquals(false, $doc->open($url, null, $error), 'Correctly failed to open file that doesn\'t exist');
		$this->assertEquals('Could not open file "'.$url.'"', $error, 'Correct error message generated');
	}

	public function testCanHandleDifficultHtml() {
		$tests = Array(
			'<!-->' => '<!---->',
			'<!--->' => '<!---->',
			'<!doctype html>' => '<!DOCTYPE html>',
			'<a href="#"">Extra quote</a>' => '<a href="#">Extra quote</a>',
			'<p title="</p>">Closing tag in title</p>' => '<p title="&lt;/p&gt;">Closing tag in title</p>',
			'<p title=" <!-- hello world --> ">Comment in title</p>' => '<p title=" &lt;!-- hello world --&gt; ">Comment in title</p>',
			'<p title="<![CDATA[ hello world ]]>">Comment in title</p>' => '<p title="&lt;![CDATA[ hello world ]]&gt;">Comment in title</p>',
			'<section><div><h1>Wrong closing tag order</div></h1></section>' => '<section><div><h1>Wrong closing tag order</h1></div></section>',
			'<p class=test data-test=tester>Unquoted attributes</p>' => '<p class="test" data-test="tester">Unquoted attributes</p>',
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
			"<div\n\r\n\t   >Test</div\n\r\n\t    >" => '<div>Test</div>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}
	}

	public function testCanEncodeAttributes() {
		$tests = Array(
			'<p title="test single quotes \'"></p>' => '<p title="test single quotes \'"></p>',
			'<p title="test single quotes &apos;"></p>' => '<p title="test single quotes \'"></p>',
			'<p title="test single quotes &#39;"></p>' => '<p title="test single quotes \'"></p>',
			"<p title='test single quotes &#39;'></p>" => '<p title="test single quotes \'"></p>',
			"<p disabled></p>" => '<p disabled></p>',
			'<p disabled title="test double attribute"></p>' => '<p disabled title="test double attribute"></p>',
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}
		if ($doc->load('<p title="test single quotes &quot; \'"></p>', mb_internal_encoding())) {
			$this->assertEquals("<p title='test single quotes &quot; &apos;'></p>", $doc->html(Array('quotestyle' => 'single')));
		}
	}

	public function testCanEncodeTextNodes() {
		$tests = Array(
			'<p>"Test&"</p>' => '<p>"Test&amp;"</p>',
			'<p>&#128512; &#128513; &#128514; &#129315;</p>' => '<p>😀 😁 😂 🤣</p>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->html());
			}
		}
	}

	public function testCanConvertEncodings() {
		$doc = new htmldoc();

		// test input encoding conversion
		$input = mb_convert_encoding('<p title="Héllo ÿ &#128512;">Héllo ÿ &#129315;</p>', 'iso-8859-1');
		$output = '<p title="Héllo ÿ 😀">Héllo ÿ 🤣</p>';
		if ($doc->load($input, 'iso-8859-1')) {
			$this->assertEquals($output, $doc->html());
			$this->assertEquals($input, $doc->save(null, Array('charset' => 'iso-8859-1')));
		}
	}

	public function testCanProduceXhtml() {
		$tests = Array(
			"<p disabled></p>" => '<p disabled=""></p>',
			"<p title='disabled'></p>" => '<p title="disabled"></p>',
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

	public function testCanSaveDocument() {
		$doc = new htmldoc();
		if ($doc->load('<div>Hello world</div>')) {
			$file = dirname(__DIR__).'/save.html';
			$this->assertEquals(true, $doc->save($file), 'Can save document');
			unlink($file);
		}
	}
}
