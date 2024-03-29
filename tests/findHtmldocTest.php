<?php
use hexydec\html\htmldoc;

final class findHtmldocTest extends \PHPUnit\Framework\TestCase {

	public function testCanFindElements() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$doc->minify(Array(
				'css' => false, // minify css
				'js' => false, // minify javascript
				//'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
				'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));
			$this->assertEquals($doc->length, 4, 'Can count elements');
			// var_dump($doc->find('title'));
			$tests = [

				// basic selectors
				'title' => '<title>Find</title>',
				'.find' => '<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>',
				'#first' => '<div id="first" class="first">First</div>',
				'.first, .find__heading, .find__paragraph' => '<div id="first" class="first">First</div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p>',

				// combination selectors
				'body .find__paragraph' => '<p class="find__paragraph" title="This is a paragraph">Paragraph</p>',
				'body > .find__paragraph' => null,
				'.find > .find__paragraph' => '<p class="find__paragraph" title="This is a paragraph">Paragraph</p>',
				'.find__paragraph + a' => '<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'div[data-attr] ~ div' => '<div data-attr="">attr</div><div data-attr="attr">attr</div><div data-attr="attr-value1">attr</div><div data-attr="attr-value2">attr</div><div data-word="one two three four">attr</div>',
				'.find h1 ~ a' => '<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'.attributes div ~ div' => '<div data-attr="">attr</div><div data-attr="attr">attr</div><div data-attr="attr-value1">attr</div><div data-attr="attr-value2">attr</div><div data-word="one two three four">attr</div>',

				// attribute selectors
				'#first[class]' => '<div id="first" class="first">First</div>',
				'[class=first]' => '<div id="first" class="first">First</div>',
				'[class^=find]' => '<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'[class*=__]' => '<h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'[class$=heading]' => '<h1 class="find__heading">Heading</h1>',
				'h1[class$=heading]' => '<h1 class="find__heading">Heading</h1>',
				'html h1[class$=heading]' => '<h1 class="find__heading">Heading</h1>',
				'a[href]' => '<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'a[href$="://github.com/hexydec/htmldoc/"]' => '<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'a[href$="://github.com/hexydec/htmldoc"]' => null,
				'a[href$="://github.com/Hexydec/Htmldoc/"]' => null,
				'a[href$="://github.com/Hexydec/Htmldoc/" i]' => '<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>',
				'[data-attr]' => '<div data-attr>attr</div><div data-attr="">attr</div><div data-attr="attr">attr</div><div data-attr="attr-value1">attr</div><div data-attr="attr-value2">attr</div>',
				'[data-attr|=attr]' => '<div data-attr="attr">attr</div><div data-attr="attr-value1">attr</div><div data-attr="attr-value2">attr</div>',
				'[data-word~=three]' => '<div data-word="one two three four">attr</div>',

				// pseudo selectors
				'.positions div:first-child' => '<div id="first" class="first">First</div>',
				'.positions div:last-child' => '<div class="last">Last</div>',
				'title:not([class])' => '<title>Find</title>',
				'.positions div:not(.find)' => '<div id="first" class="first">First</div><div class="last">Last</div>',
				'body section:not(:first-child) div:last-child' => '<div data-word="one two three four">attr</div>',
			];
			foreach ($tests AS $key => $item) {
				$this->assertEquals($item, $doc->find($key)->html());
			}
		}
	}

	public function testCanTraverseElements() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$doc->minify(Array(
				'css' => false, // minify css
				'js' => false, // minify javascript
				//'whitespace' => false, // remove whitespace
				'comments' => false, // remove comments
				'urls' => false, // update internal URL's to be shorter
				'attributes' => false, // remove values from boolean attributes);
				'quotes' => false, // minify attribute quotes
				'close' => false // don't write close tags where possible
			));

			$this->assertEquals('<div id="first" class="first">First</div>', $doc->find('.positions > *')->first()->html(), 'Can find first element');
			$this->assertEquals('<div class="last">Last</div>', $doc->find('.positions > *')->last()->html(), 'Can find last element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('.positions > *')->eq(1)->html(), 'Can find specific element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('.positions > *')->eq(-2)->html(), 'Can find specific element');
			$this->assertEquals('<h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>', $doc->find('.find')->children()->html(), 'Can extract children of an element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('.find__heading')->parent()->html(), 'Can specific element');

			$this->assertEquals(3, \count($doc->find('.positions > *')->get()));
			$this->assertEquals('<div class="last">Last</div>', $doc->find('.positions > *')->get(2)->html());
			$this->assertEquals('<div class="last">Last</div>', $doc->find('.positions > *')->get(-1)->html());

			$cls = ['first', 'find', 'last'];
			$divs = $doc->find('.positions > *');
			$this->assertTrue(isset($divs[0]), true);
			$this->assertEquals($cls[0], $divs[0]->attr('class'));
			foreach ($divs AS $key => $item) {
				$this->assertEquals($cls[$key], $item->attr('class'));
			}

			unset($divs[2]);
			$this->assertEquals(isset($divs[2]), false);
		}
	}

	public function testCanReadTagNames() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$this->assertEquals('div', $doc->find('.find')->tag());
			$this->assertEquals('p', $doc->find('.find__paragraph')->tag());
			$this->assertEquals('h1', $doc->find('.find__heading')->tag());
			$this->assertEquals(null, $doc->find('.find__nothing')->tag());
		}
	}

	public function testCanReadAttributes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$this->assertEquals('find', $doc->find('.find')->attr('class'));
			$this->assertEquals('This is a paragraph', $doc->find('.find__paragraph')->attr('title'));
			$this->assertEquals(null, $doc->find('.find__paragraph')->attr('data-nothing'));
			$this->assertEquals(null, $doc->find('.find__nothing')->attr('data-nothing'));
		}
	}

	public function testCanSetAttributes() {
		$tests = [
			[
				'input' => '<div>Test</div><div>Test 2</div>',
				'find' => 'div',
				'attr' => 'class',
				'value' => 'test',
				'output' => '<div class="test">Test</div><div class="test">Test 2</div>'
			],
			[
				'input' => '<div>
						<img src="test.png" alt="Test" />
					</div>
					<div class="main">
						<p>
							<img src="test.png" alt="Test" width="800" height="450" />
						</p>
					</div>',
				'find' => 'img',
				'attr' => 'loading',
				'value' => 'lazy',
				'output' => '<div>
						<img src="test.png" alt="Test" loading="lazy" />
					</div>
					<div class="main">
						<p>
							<img src="test.png" alt="Test" width="800" height="450" loading="lazy" />
						</p>
					</div>'
			],
			[
				'input' => '<div class="test">Test</div><div class="test">Test 2</div>',
				'find' => 'div',
				'attr' => 'class',
				'value' => 'test test--add-attr',
				'output' => '<div class="test test--add-attr">Test</div><div class="test test--add-attr">Test 2</div>'
			]
		];
		$doc = new htmldoc();
		foreach ($tests AS $item) {
			$doc->load($item['input'], \mb_internal_encoding());
			$doc->find($item['find'])->attr($item['attr'], $item['value']);
			$this->assertEquals($item['output'], $doc->html());
		}
	}

	public function testCanReadTextNodes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$this->assertEquals('Heading', $doc->find('.find__heading')->text());
			$this->assertEquals('Paragraph', $doc->find('.find__paragraph')->text());
			$this->assertEquals('First Heading Paragraph', $doc->find('.first, .find__heading, .find__paragraph')->text());
		}
	}
}
