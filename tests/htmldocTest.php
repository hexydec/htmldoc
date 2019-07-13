<?php

use hexydec\html\htmldoc;

final class HtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanParseDocument() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/document.html')) {
			$html = trim(file_get_contents(__DIR__.'/templates/document.html'));
			$this->assertEquals($html, $doc->save());
		}
	}

	public function testCanLowercaseTagsAndAttributes() {
		$doc = new htmldoc();

		if ($doc->open(__DIR__.'/templates/lowercase.html')) {
			$doc->minify(Array(
				'lowercase' => false, // locase attribute and tags names
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/lowercase-recycle.html'));
			$this->assertEquals($minified, $doc->save());
		}

		if ($doc->open(__DIR__.'/templates/lowercase.html')) {
			$doc->minify(Array(
				'lowercase' => true, // locase attribute and tags names
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/lowercase-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanStripWhitespace() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/whitespace.html')) {
			$doc->minify(Array(
				//'whitespace' => true, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/whitespace-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanStripComments() {

		// strip all comments
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/comments.html')) {
			$doc->minify(Array(
	   			'whitespace' => false, // remove whitespace
	   			'comments' => Array('ie' => false), // remove comments
	   			'urls' => false, // update internal URL's to be shorter
	   			'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/comments-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}

		// test allowing IE conditional comments
		if ($doc->open(__DIR__.'/templates/comments.html')) {
			$doc->minify(Array(
				'whitespace' => false, // remove whitespace
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/comments-minified-ie.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanMinifyUrls() {
		$_SERVER['HTTPS'] = '';
		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['REQUEST_URI'] = '/url/';
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/urls.html')) {
			$doc->minify(Array(
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				//'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/urls-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanMinifyAttributes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/attributes.html')) {
			$doc->minify(Array(
				'css' => false, // minify css
				'js' => false, // minify javascript
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				//'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/attributes-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanMinifySingletons() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/singleton.html')) {
			$doc->minify(Array(
				'css' => false, // minify css
				'js' => false, // minify javascript
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				//'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/singleton-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanMinifyQuotes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/quotes.html')) {
			$doc->minify(Array(
				'css' => false, // minify css
				'js' => false, // minify javascript
				'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			//'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/quotes-minified.html'));
			$this->assertEquals($minified, $doc->save());
		}
	}

	public function testCanHandleBrokenHtml() {
		$tests = Array(
			'<a href="#"">Extra quote</a>' => '<a href="#">Extra quote</a>',
			'<p title="</p>">Closing tag in titke</p>' => '<p title="&lt;/p&gt;">Closing tag in titke</p>',
			'<p title=" <!-- hello world --> ">Comment in title</p>' => '<p title=" &lt;!-- hello world --&gt; ">Comment in title</p>',
			'<section><div><h1>Wrong closing tag order</div></h1></section>' => '<section><div><h1>Wrong closing tag order</h1></div></section>'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$this->assertEquals($output, $doc->save());
			}
		}
	}

	/*public function testCanMinifyCss() {
		$html = file_get_contents(__DIR__.'/templates/css.html');
		$minified = file_get_contents(__DIR__.'/templates/css-minified.html');
		$output = htmlmin::minify($html, Array(
			'whitespace' => false, // remove whitespace
			'comments' => false, // remove comments
			'inlinestyles' => false, // minify inline CSS
			'urls' => false, // update internal URL's to be shorter
			'attributes' => false, // remove values from boolean attributes);
		));
		$this->assertEquals($minified, $output);
	}*/
}
