<?php
use hexydec\html\htmldoc;

final class minifyHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanMinifyDoctype() : void {
		$tests = Array(
			'<!DOCTYPE html>' => '<!DOCTYPE html>',
			'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">' => '<!DOCTYPE html public "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
			'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">' => '<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' => '<!DOCTYPE html public "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
			'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
	"http://www.w3.org/TR/html4/frameset.dtd">' => '<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">' => '<!DOCTYPE html public "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">'
		);
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$doc->minify();
				$this->assertEquals($output, $doc->html());
			}
		}
	}

	public function testCanLowercaseTagsAndAttributes() : void {
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
			$this->assertEquals($minified, $doc->html(), 'Case of tags now match in opening and closing');
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
			$this->assertEquals($minified, $doc->html(), 'Can lowercase tag and attribute names');
		}
	}

	public function testCanStripWhitespace() : void {
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
			$this->assertEquals($minified, $doc->html(), 'Can strip whitepace');
		}

		// visible spaces around inline elements are kept, while spaces at the edges of inline block content and next to line breaks are not
		$tests = [
			'<p><label>Name</label> <input name="a"> <button> A </button> <button>B</button></p>' => '<p><label>Name</label> <input name=a> <button>A</button> <button>B</button>',
			"<p><svg>\n\t<rect/>\n</svg> Icon label</p>" => '<p><svg><rect/></svg> Icon label',
			'<p>A <img src="x"> B <mark>m</mark> <q>q</q> <tt>t</tt> <span>s</span></p>' => '<p>A <img src=x> B <mark>m</mark> <q>q</q> <tt>t</tt> <span>s</span>',
			'<p>Line<br> next</p>' => '<p>Line<br>next',
			'<video><p>Fallback</p></video>' => '<video><p>Fallback</p></video>'
		];
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$doc->minify();
			$this->assertEquals($output, $doc->html());
		}
	}

	public function testCanStripComments() : void {

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
			$this->assertEquals($minified, $doc->html(), 'Can strip all comments');
		}

		// test allowing IE conditional comments
		if ($doc->open(__DIR__.'/templates/comments.html')) {
			$doc->minify(Array(
				'whitespace' => false, // remove whitespace
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
	   			'quotes' => false, // minify attribute quotes
				'close' => false, // don't write close tags where possible
				'comments' => [
					'remove' => true,
					'ie' => true
				],
				'singleton' => false
			));
			$minified = file_get_contents(__DIR__.'/templates/comments-minified-ie.html');
			$this->assertEquals($minified, $doc->html(), 'Can strip comments but leave IE comments intact');
		}
	}

	public function testCanMinifyUrls() : void {
		$html = Array(
			'https://test.com/' => Array(
				'<a href="https://test.com">Root</a>' => '<a href="">Root</a>',
				'<a href="https://test.com/">Root</a>' => '<a href="">Root</a>',
				'<a href="https://test.com/test">Own Host</a>' => '<a href="test">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="url/test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="url/test.php">Own Host under folder no scheme</a>',
				'<a href="/url/test.php">Own Host under folder no host</a>' => '<a href="url/test.php">Own Host under folder no host</a>',
				'<a href="/">Same URL, no query string</a>' => '<a href="">Same URL, no query string</a>',
				'<a href="/?var=value">Same URL including query string</a>' => '<a href="?var=value">Same URL including query string</a>',
				'<a href="http://test.com/test">Different scheme</a>' => '<a href="http://test.com/test">Different scheme</a>',
				'<a href="https://tester.com/test">Different Host</a>' => '<a href="//tester.com/test">Different Host</a>',
				'<video src="https://test.com/assets/video.mp4" poster="https://test.com/assets/video.jpg"></video>' => '<video src="assets/video.mp4" poster="assets/video.jpg"></video>',
				'<link rel="stylesheet" href="https://test.com/alotof/css/build/file.css?_12345">' => '<link rel="stylesheet" href="alotof/css/build/file.css?_12345">',
				'<link itemtype="url" href="https://test.com/">' => '<link itemtype="url" href="https://test.com/">',
				'<a href="//test.com?var=value">Host with query string</a>' => '<a href="?var=value">Host with query string</a>',
				'<a href="/test?url=/other/path">Slash in query string</a>' => '<a href="test?url=/other/path">Slash in query string</a>'
			),
			'http://test.com/url/' => Array(
				'<a href="http://test.com">Root</a>' => '<a href="/">Root</a>',
				'<a href="http://test.com/">Root</a>' => '<a href="/">Root</a>',
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
				'<a href="https://test.com">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="https://test.com/url">Own Host</a>' => '<a href="/url">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="test.php">Own Host under folder no scheme</a>',
				'<a href="http://test.com/test">Different scheme</a>' => '<a href="http://test.com/test">Different scheme</a>',
				'<a href="http://tester.com/test">Different Host</a>' => '<a href="http://tester.com/test">Different Host</a>',
			),
			'https://test.com/url' => Array(
				'<a href="https://test.com">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/test">Own Host</a>' => '<a href="test">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="url/test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="url/test.php">Own Host under folder no scheme</a>',
				'<a href="https://test.com/url">Same page</a>' => '<a href="">Same page</a>',
				'<a href="https://test.com/url?page=2">Same page with query string</a>' => '<a href="?page=2">Same page with query string</a>',
				'<script src="//cdn.other.com/script.js"></script>' => '<script src="//cdn.other.com/script.js"></script>',
				'<a href="http://test.com/test">Different scheme</a>' => '<a href="http://test.com/test">Different scheme</a>',
				'<a href="http://tester.com/test">Different Host</a>' => '<a href="http://tester.com/test">Different Host</a>',
			),
			'https://test.com/url/?var=value' => Array(
				'<a href="https://test.com">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/test">Own Host</a>' => '<a href="/test">Own Host</a>',
				'<a href="https://test.com/url/test.php">Own Host under folder</a>' => '<a href="test.php">Own Host under folder</a>',
				'<a href="//test.com/url/test.php">Own Host under folder no scheme</a>' => '<a href="test.php">Own Host under folder no scheme</a>',
				'<a href="https://test.com/url">Same URL with no querystring or slash</a>' => '<a href="/url">Same URL with no querystring or slash</a>',
				'<a href="https://test.com/url/">Same URL with no querystring</a>' => '<a href="./">Same URL with no querystring</a>',
				'<a href="https://test.com/url/?var=other">Same URL different querystring</a>' => '<a href="?var=other">Same URL different querystring</a>',
				'<a href="https://test.com/url/#top">Same URL with fragment</a>' => '<a href="./#top">Same URL with fragment</a>',
			),
			'https://test.com/dir/page.php' => Array(
				'<a href="/dir/?var=value">Folder with query string</a>' => '<a href="./?var=value">Folder with query string</a>',
				'<a href="/dir/#top">Folder with fragment</a>' => '<a href="./#top">Folder with fragment</a>',
				'<a href="/dir/">Folder</a>' => '<a href="./">Folder</a>',
				'<a href="/dir/page.php">Same page</a>' => '<a href="">Same page</a>',
				'<a href="/dir/page.php?var=value">Same page with query string</a>' => '<a href="?var=value">Same page with query string</a>',
				'<a href="/dir/page.php#top">Same page with fragment</a>' => '<a href="#top">Same page with fragment</a>',
			),
			'https://test.com/dir/page.php?var=value' => Array(
				'<a href="/dir/page.php">Same page without query string</a>' => '<a href="page.php">Same page without query string</a>',
				'<a href="/dir/page.php#top">Same page without query string with fragment</a>' => '<a href="page.php#top">Same page without query string with fragment</a>',
				'<a href="/dir/page.php?var=other">Same page different query string</a>' => '<a href="?var=other">Same page different query string</a>',
			),
			'https://test.com/deep/lot/of/folders/' => Array(
				'<a href="https://test.com">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/">Root</a>' => '<a href="/">Root</a>',
				'<a href="https://test.com/different/folders/">Different Folders</a>' => '<a href="/different/folders/">Different Folders</a>',
				'<a href="https://test.com/deep/lot/test">Back two</a>' => '<a href="../../test">Back two</a>',
				'<a href="https://test.com/deep/lot/test/">Back two keep slash</a>' => '<a href="../../test/">Back two keep slash</a>',
				'<a href="https://test.com/deep/lot/test/this/and/this.php">Back two</a>' => '<a href="../../test/this/and/this.php">Back two</a>',
				'<link rel="stylesheet" href="/deep/css/build/file.css?_12345">' => '<link rel="stylesheet" href="/deep/css/build/file.css?_12345">', // shorter to stay as is
			),
			'https://test.com/alotof/of/folders/' => Array(
				'<link rel="stylesheet" href="/alotof/css/build/file.css?_12345">' => '<link rel="stylesheet" href="../../css/build/file.css?_12345">',
				'<a href="https://nottest.com/alotof/of/test/test.php">Different Domain</a>' => '<a href="//nottest.com/alotof/of/test/test.php">Different Domain</a>',
			)
		);
		$doc = new htmldoc();
		foreach ($html AS $url => $items) {
			$_SERVER['HTTPS'] = parse_url($url, PHP_URL_SCHEME) == 'https' ? 'on' : '';
			$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST);
			$query = parse_url($url, PHP_URL_QUERY);
			$_SERVER['REQUEST_URI'] = parse_url($url, PHP_URL_PATH).($query === null ? '' : '?'.$query);
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
					$this->assertEquals($output, $doc->html(), 'Can minify URLs');
				}
			}
		}

		// the host is removed when the scheme is kept
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['REQUEST_URI'] = '/dir/';
		$doc->load('<a href="https://test.com/dir/test.php">Own host</a><a href="http://test.com/dir/test.php">Different scheme</a>');
		$doc->minify(['urls' => ['scheme' => false]]);
		$this->assertEquals('<a href=test.php>Own host</a><a href=http://test.com/dir/test.php>Different scheme</a>', $doc->html());

		// URLs are left as they are when a base URL changes how they resolve
		$doc->load('<base href="https://other.com/"><a href="https://test.com/dir/test.php">Own host</a>');
		$doc->minify();
		$this->assertEquals('<base href=https://other.com/><a href=https://test.com/dir/test.php>Own host</a>', $doc->html());
	}

	public function testCanMinifyAttributes() : void {
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
			$this->assertEquals($minified, $doc->html(), 'Can minify attributes');
		}

		// keyword values are kept, and attributes whose empty value has a meaning are not removed
		$tests = [
			'<div hidden="until-found">Findable</div>' => '<div hidden=until-found>Findable</div>',
			'<div hidden="hidden">Hidden</div><div hidden="">Hidden</div>' => '<div hidden>Hidden</div><div hidden>Hidden</div>',
			'<p title="Tip"><span title="">No tip</span></p>' => '<p title=Tip><span title="">No tip</span>',
			'<p lang="">Unknown language</p>' => '<p lang="">Unknown language'
		];
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$doc->minify();
			$this->assertEquals($output, $doc->html());
		}
	}

	public function testCanMinifySingletons() : void {
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
			$this->assertEquals($minified, $doc->html(), 'Can minify singletons');
		}
	}

	public function testCanMinifyQuotes() : void {
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
			$this->assertEquals($minified, $doc->html(), 'Can minify attribute quotes');
		}

		// unquoted values are encoded the same as quoted ones
		$tests = [
			'<a title="&amp;lt;b&amp;gt;">x</a>' => '<a title=&amp;lt;b&amp;gt;>x</a>',
			'<img alt="R&amp;D">' => '<img alt=R&amp;D>',
			'<img alt="a&lt;b">' => '<img alt=a&lt;b>',
			"<img alt=\"form\ffeed\">" => "<img alt=\"form\ffeed\">",
			'<img alt="\'&quot;">' => '<img alt=\'&#39;"\'>'
		];
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$doc->minify();
			$this->assertEquals($output, $doc->html());
		}
	}

	public function testCanMinifyCloseTags() : void {
		$html = Array(
			'<div><p>Test</p></div>' => '<div><p>Test</div>',
			'<div><p>Test</p><p>Test 2</p></div>' => '<div><p>Test<p>Test 2</div>',
			'<a href="#"><div><p>Test</p></div><p>Don\'t close when followed by inline element</p></a>' => '<a href="#"><div><p>Test</div><p>Don\'t close when followed by inline element</p></a>',
			'<div><p>Test</p> followed by text</div>' => '<div><p>Test</p> followed by text</div>',
			'<div><p>Test</p>0<p>Test 2</p></div>' => '<div><p>Test</p>0<p>Test 2</div>',
			'<div><p>Test</p> <p>Test 2</p></div>' => '<div><p>Test <p>Test 2</div>',
			'<p>Top level</p> followed by text' => '<p>Top level</p> followed by text',
			'<p>Top level</p><p>Test 2</p>' => '<p>Top level<p>Test 2',
		);
		$doc = new htmldoc();
		foreach ($html AS $input => $output) {
			if ($doc->load($input, mb_internal_encoding())) {
				$doc->minify(Array(
					'whitespace' => false, // remove whitespace
					'comments' => false, // remove comments
					'urls' => false, // update internal URL's to be shorter
					'attributes' => false, // remove values from boolean attributes);
					'quotes' => false, // minify attribute quotes
					//'close' => false // don't write close tags where possible
				));
				$this->assertEquals($output, $doc->html(), 'Can minify URLs');
			}
		}
	}

	public function testCanMinifySvg() : void {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/svg.html')) {
			$doc->minify(['attributes' => ['sort' => false]]);
			$minified = file_get_contents(__DIR__.'/templates/svg-minified.html');
			$this->assertEquals(trim($minified), $doc->html(), 'Can minify SVGs');
		}

		// elements keep their children, and self-close when they have none
		$tests = [
			'<svg><rect width="1"><animate attributeName="x"/></rect></svg>' => '<svg><rect width="1"><animate attributeName="x"/></rect></svg>',
			'<svg><circle r="2"><title>Tooltip</title></circle></svg>' => '<svg><circle r="2"><title>Tooltip</title></circle></svg>',
			'<svg><circle r="2"></circle><path d="M0"></path></svg>' => '<svg><circle r="2"/><path d="M0"/></svg>',
			'<div></div><svg><g></g></svg>' => '<div></div><svg><g/></svg>'
		];
		foreach ($tests AS $input => $output) {
			$doc->load($input);
			$doc->minify();
			$this->assertEquals($output, $doc->html());
		}
	}

	public function testCanMinifyCssAndJs() : void {

		// this basic test is to test that the HTMLdoc object can invoke the minifiers, not how good the minifiers themselves are
		// There are tests within the minifiers' respective projects for that
		$tests = [
			'<style type="text/css">
				.test {
					display: block;
					font-weight: bold;
				}
			</style>' => '<style>.test{display:block;font-weight:700}</style>',
			'<style type="text/css">
				<![CDATA[
					.test {
						display: block;
						font-weight: bold;
					}
				]]>
			</style>' => '<style>.test{display:block;font-weight:700}</style>',
			'<svg>
				<style type="text/css">
					.test {
						display: block;
						font-weight: bold;
					}
				</style>
			</svg>' => '<svg><style>.test{display:block;font-weight:700}</style></svg>',
			'<svg>
				<style type="text/css">
					<![CDATA[
						.test {
							display: block;
							font-weight: bold;
						}
					]]>
				</style>
			</svg>' => '<svg><style>.test{display:block;font-weight:700}</style></svg>',
			'<script type="text/javascript" async="async">
				(function () {
					console.log("Test");
				}());
			</script>' => '<script async>(function(){console.log("Test")}())</script>'
		];
		foreach ($tests AS $key => $item) {
			$doc = new htmldoc();
			$doc->load($key);

			// minify the code
			$doc->minify();
			$this->assertEquals($item, $doc->html(), 'Can minify CSS and Javascript');
		}

		foreach ($tests AS $key => $item) {
			// minify and cache
			$doc = new htmldoc([
				'custom' => [
					'style' => [
						'cache' => __DIR__.'/cache/%s.css'
					],
					'script' => [
						'cache' => __DIR__.'/cache/%s.js'
					]
				]
			]);
			$doc->load($key);
			$doc->minify();
			$this->assertEquals($item, $doc->html(), 'Can minify and cache CSS and Javascript');

			// do it again to pull from cache
			$doc->load($key);
			$doc->minify();
			$this->assertEquals($item, $doc->html(), 'Can load minified CSS and Javascript from cache');
		}
	}

	public function testCanReplaceMinifiedOutputOptions() : void {
		$doc = new htmldoc();
		$doc->load('<p class="a">Text</p>');

		// minified quotes apply to the document and its collections, but explicit options take precedence
		$doc->minify();
		$this->assertEquals('<p class=a>Text', $doc->html());
		$this->assertEquals('<p class=a>Text', $doc->find('p')->html());
		$this->assertEquals('<p class="a">Text', $doc->html(['quotestyle' => 'double']));

		// minifying again without quotes replaces the previous minification, and the config is unchanged
		$doc->minify(['quotes' => false]);
		$this->assertEquals('<p class="a">Text', $doc->html());
		$this->assertEquals('double', $doc->config['output']['quotestyle']);
	}

	public function testCanCacheMinifiedCode() : void {
		$dir = \sys_get_temp_dir().'/htmldoc-cache-'.\bin2hex(\random_bytes(4));
		$doc = new htmldoc(['custom' => ['style' => ['cache' => $dir.'/%s.css']]]);
		$html = '<style>p { color: #ff0000; }</style>';

		try {

			// different options are cached separately
			$doc->load($html);
			$doc->minify();
			$this->assertEquals('<style>p{color:red}</style>', $doc->html());
			$doc->load($html);
			$doc->minify(['style' => ['colors' => false]]);
			$this->assertEquals('<style>p{color:#ff0000}</style>', $doc->html());
			$this->assertCount(2, \glob($dir.'/*.css'));

			// the same options are read from the cache
			$doc->load($html);
			$doc->minify();
			$this->assertEquals('<style>p{color:red}</style>', $doc->html());
			$this->assertCount(2, \glob($dir.'/*.css'));

		// clean up even when an assertion fails
		} finally {
			\array_map('\\unlink', \glob($dir.'/*.css'));
			if (\is_dir($dir)) {
				\rmdir($dir);
			}
		}
	}

	public function testCanMinifyDifficultHtml() : void {
		$tests = [
			'<a href="الشركاء/شركاء-الصناعة/">هنا</a>' => '<a href=الشركاء/شركاء-الصناعة/>هنا</a>'
		];
		$doc = new htmldoc();
		foreach ($tests AS $input => $output) {
			if ($doc->load($input, \mb_internal_encoding())) {
				$doc->minify();
				$this->assertEquals($output, $doc->html());
			}
		}
	}
}
