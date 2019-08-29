<?php
use hexydec\html\htmldoc;

final class minifyHtmldocTest extends \PHPUnit\Framework\TestCase {

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
			$minified = file_get_contents(__DIR__.'/templates/lowercase-recycle.html');
			$this->assertEquals($minified, $doc->save(), 'Case of tags now match in opening and closing');
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
			$minified = file_get_contents(__DIR__.'/templates/lowercase-minified.html');
			$this->assertEquals($minified, $doc->save(), 'Can lowercase tag and attribute names');
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
			$this->assertEquals($minified, $doc->save(), 'Can strip whitepace');
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
			$minified = file_get_contents(__DIR__.'/templates/comments-minified.html');
			$this->assertEquals($minified, $doc->save(), 'Can strip all comments');
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
			$minified = file_get_contents(__DIR__.'/templates/comments-minified-ie.html');
			$this->assertEquals($minified, $doc->save(), 'Can strip comments but leave IE comments intact');
		}
	}

	public function testCanMinifyUrls() {
		$html = Array(
			'http://test.com/url/' => Array(
				'<a href="http://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="http://test.com/url/test.php">Own Host under folder</a>' => '<a href="test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="test.php">Own Host under folder no scheme</a>',
				'<a href="/url/test.php">Own Host under folder no host</a>' => '<a href="test.php">Own Host under folder no host</a>',
				'<a href="/url/">Same URL, no query string</a>' => '<a href="">Same URL, no query string</a>',
				'<a href="/url/?var=value">Same URL including query string</a>' => '<a href="?var=value">Same URL including query string</a>',
				'<a href="https://test.com/test">Different scheme</a>' => '<a href="https://test.com/test">Different scheme</a>',
				'<a href="http://tester.com/test">Different Host</a>' => '<a href="//tester.com/test">Different Host</a>',
				'<video src="http://test.com/assets/video.mp4" poster="http://test.com/assets/video.jpg"></video>' => '<video src="/assets/video.mp4" poster="/assets/video.jpg"></video>'
			),
			'https://test.com/url/' => Array(
				'<a href="https://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="https://test.com/url">Own Host</a>' => '<a href="/url">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="test.php">Own Host under folder no scheme</a>',
				'<a href="http://test.com/test">Different scheme</a>' => '<a href="http://test.com/test">Different scheme</a>',
				'<a href="http://tester.com/test">Different Host</a>' => '<a href="http://tester.com/test">Different Host</a>',
			),
			'https://test.com/url' => Array(
				'<a href="https://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="/url/test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="/url/test.php">Own Host under folder no scheme</a>',
				'<a href="http://test.com/test">Different scheme</a>' => '<a href="http://test.com/test">Different scheme</a>',
				'<a href="http://tester.com/test">Different Host</a>' => '<a href="http://tester.com/test">Different Host</a>',
			),
			'https://test.com/url/?var=value' => Array(
				'<a href="https://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="test.php">Own Host under folder no scheme</a>',
				'<a href="https://test.com/url">Same URL with no querystring or slash</a>' => '<a href="/url">Same URL with no querystring or slash</a>',
				'<a href="https://test.com/url/">Same URL with no querystring</a>' => '<a href="./">Same URL with no querystring</a>',
			),
			'https://test.com/deep/lot/of/folders/' => Array(
				'<a href="https://test.com/">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/different/folders/">Different Folders</a>' => '<a href="/different/folders/">Different Folders</a>',
				'<a href="https://test.com/deep/lot/test">Back two</a>' => '<a href="../../test">Back two</a>',
				'<a href="https://test.com/deep/lot/test/this/and/this.php">Back two</a>' => '<a href="../../test/this/and/this.php">Back two</a>',
				'<link rel="stylesheet" href="/deep/css/build/file.css?_12345">' => '<link rel="stylesheet" href="/deep/css/build/file.css?_12345">', // shorter to stay as is
			),
			'https://test.com/alotof/of/folders/' => Array(
				'<link rel="stylesheet" href="/alotof/css/build/file.css?_12345">' => '<link rel="stylesheet" href="../../css/build/file.css?_12345">',
			)
		);
		$doc = new htmldoc();
		foreach ($html AS $url => $items) {
			$_SERVER['HTTPS'] = parse_url($url, PHP_URL_SCHEME) == 'https' ? 'on' : '';
			$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST);
			$_SERVER['REQUEST_URI'] = parse_url($url, PHP_URL_PATH).parse_url($url, PHP_URL_QUERY);
			foreach ($items AS $input => $output) {
				if ($doc->load($input, mb_internal_encoding())) {
					$doc->minify(Array(
						'whitespace' => false, // remove whitespace
						'comments' => false, // remove comments
						//'urls' => false, // update internal URL's to be shorter
						'attributes' => false, // remove values from boolean attributes);
			   			'quotes' => false, // minify attribute quotes
						'close' => false // don't write close tags where possible
					));
					$this->assertEquals($output, $doc->save(), 'Can minify URLs');
				}
			}
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
			$minified = file_get_contents(__DIR__.'/templates/attributes-minified.html');
			$this->assertEquals($minified, $doc->save(), 'Can minify attributes');
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
			$minified = file_get_contents(__DIR__.'/templates/singleton-minified.html');
			$this->assertEquals($minified, $doc->save(), 'Can minify singletons');
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
			$minified = file_get_contents(__DIR__.'/templates/quotes-minified.html');
			$this->assertEquals($minified, $doc->save(), 'Can minify attribute quotes');
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
