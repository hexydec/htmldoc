<?php

use hexydec\minify\htmlmin;

final class HtmlminTest extends \PHPUnit\Framework\TestCase {

	public function testCanStripWhitespace() {
		$html = file_get_contents(__DIR__.'/templates/whitespace.html');
		$minified = trim(file_get_contents(__DIR__.'/templates/whitespace-minified.html'));
		$output = htmlmin::minify($html, Array(
			'whitespace' => true, // remove whitespace
			'comments' => false, // remove comments
			'inlinestyles' => false, // minify inline CSS
			'urls' => false, // update internal URL's to be shorter
			'attributes' => false, // remove values from boolean attributes);
		));
		$this->assertEquals($minified, $output);
	}

	public function testCanStripComments() {

		// strip all comments
		$html = file_get_contents(__DIR__.'/templates/comments.html');
		$minified = file_get_contents(__DIR__.'/templates/comments-minified.html');
   		$output = htmlmin::minify($html, Array(
   			'whitespace' => false, // remove whitespace
   			'comments' => true, // remove comments
   			'inlinestyles' => false, // minify inline CSS
   			'urls' => false, // update internal URL's to be shorter
   			'attributes' => false, // remove values from boolean attributes);
   		), Array(
			'comments' => Array('ie' => false)
		));
   		$this->assertEquals($minified, $output);

		// test allowing IE conditional comments
		$minified = file_get_contents(__DIR__.'/templates/comments-minified-ie.html');
    	$output = htmlmin::minify($html, Array(
			'whitespace' => false, // remove whitespace
			'comments' => true, // remove comments
			'inlinestyles' => false, // minify inline CSS
			'urls' => false, // update internal URL's to be shorter
			'attributes' => false, // remove values from boolean attributes);
		));
    	$this->assertEquals($minified, $output);
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
