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
			// var_dump($doc->find('title'));
			$this->assertEquals('<title>Find</title>', $doc->find('title')->html());
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p></div>', $doc->find('.find')->html());
			$this->assertEquals('<div class="find"><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p></div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('[class^=find]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('[class*=__]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('[class$=heading]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('h1[class$=heading]')->html());
			$this->assertEquals('<h1 class="find__heading">Heading</h1>', $doc->find('html h1[class$=heading]')->html());
			$this->assertEquals('<div class="first">First</div>', $doc->find('div:first-child')->html());
			$this->assertEquals('<div class="last">Last</div>', $doc->find('div:last-child')->html());
			$this->assertEquals('<div class="first">First</div><h1 class="find__heading">Heading</h1><p class="find__paragraph" title="This is a paragraph">Paragraph</p>', $doc->find('.first, .find__heading, .find__paragraph')->html());
		}
	}

	public function testCanReadAttributes() {
		$doc = new htmldoc();
		if ($doc->open(__DIR__.'/templates/find.html')) {
			$this->assertEquals('find', $doc->find('.find')->attr('class'));
			$this->assertEquals('This is a paragraph', $doc->find('.find__paragraph')->attr('title'));
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
