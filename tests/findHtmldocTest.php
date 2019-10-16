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
			$this->assertEquals('<title>Find</title>', $doc->find('title')->html());
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('.find')->html());
			$this->assertEquals('<div id="first" class="first">First</div>', $doc->find('#first')->html());
			$this->assertEquals('<div id="first" class="first">First</div>', $doc->find('[class=first]')->html());
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>', $doc->find('[class^=find]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>', $doc->find('[class*=__]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('[class$=heading]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('h1[class$=heading]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('html h1[class$=heading]')->html());
			$this->assertEquals('<a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a>', $doc->find('a[href$="://github.com/hexydec/htmldoc/"]')->html());
			$this->assertEquals(null, $doc->find('a[href$="://github.com/hexydec/htmldoc"]')->html());
			$this->assertEquals('<div id="first" class="first">First</div>', $doc->find('div:first-child')->html());
			$this->assertEquals('<div class="last">Last</div>', $doc->find('div:last-child')->html());
			$this->assertEquals('<div id="first" class="first">First</div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('.first, .find__heading, .find__paragraph')->html());
			$this->assertEquals('<p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('body .find__paragraph')->html());
			$this->assertEquals('<p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('.find > .find__paragraph')->html());
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

			$this->assertEquals('<div id="first" class="first">First</div>', $doc->find('body > *')->first()->html(), 'Can find first element');
			$this->assertEquals('<div class="last">Last</div>', $doc->find('body > *')->last()->html(), 'Can find last element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('body > *')->eq(1)->html(), 'Can specific element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('body > *')->eq(-2)->html(), 'Can specific element');
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p><a class="find__anchor" href="https://github.com/hexydec/htmldoc/">Anchor</a></div>', $doc->find('.find')->children()->html(), 'Can specific element');

			$this->assertEquals(3, count($doc->find('body > *')->get()));
			$this->assertEquals('<div class="last">Last</div>', $doc->find('body > *')->get(2)->html());
			$this->assertEquals('<div class="last">Last</div>', $doc->find('body > *')->get(-1)->html());
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

	public function testCanReadTextNodes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$this->assertEquals('Heading', $doc->find('.find__heading')->text());
			$this->assertEquals('Paragraph', $doc->find('.find__paragraph')->text());
			$this->assertEquals('First Heading Paragraph', $doc->find('.first, .find__heading, .find__paragraph')->text());
		}
	}
}
