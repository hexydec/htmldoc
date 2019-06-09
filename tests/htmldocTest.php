<?php

use hexydec\minify\htmldoc;

final class HtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanStripWhitespace() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/whitespace.html')) {
			$doc->minify(Array(
				//'whitespace' => true, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
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
			));
			$minified = trim(file_get_contents(__DIR__.'/templates/comments-minified-ie.html'));
			$this->assertEquals($minified, $doc->save());
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
