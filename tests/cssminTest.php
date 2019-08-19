<?php
use hexydec\html\cssmin;

final class cssminTest extends \PHPUnit\Framework\TestCase {

	protected $config = Array(
		'removesemicolon' => false,
		'removezerounits' => false,
		'removeleadingzero' => false,
		'convertquotes' => false,
		'removequotes' => false,
		'shortenhex' => false,
		'lowerhex' => false,
		'sortselectors' => false,
		'mergeselectors' => false,
		'removeoverwrittenproperties' => false,
		'sortproperties' => false,
		'mergeblocks' => false,
		'report' => false,
		'output' => 'minify'
	);

	public function testCanMinifyCss() {
		$test = Array(
			Array(
				'input' => '#id {
					font-size: 3em;
				}',
				'output' => '#id{font-size:3em;}'
			),
			Array(
				'input' => '#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}',
				'output' => '#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			),
			Array(
				'input' => '#id {
					font-size: 3em;
				}

				#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}
				',
				'output' => '#id{font-size:3em;}#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			)
		);
		foreach ($test AS $item) {
			$this->assertEquals($item['output'], cssmin::minify($item['input'], $this->config));
		}
	}

	public function testCanRemoveLastSemicolon() {
		$input = '#id {
			font-size: 3em;
		}';
		$output = '#id{font-size:3em}';
		$config = $this->config;
		$config['removesemicolon'] = true;
		$this->assertEquals($output, cssmin::minify($input, $config));
	}

	public function testCanRemoveZeroUnits() {
		$input = '#id {
			margin: 0px 0% 20px 0em;
		}';
		$output = '#id{margin:0 0 20px 0;}';
		$config = $this->config;
		$config['removezerounits'] = true;
		$this->assertEquals($output, cssmin::minify($input, $config));
	}
}
