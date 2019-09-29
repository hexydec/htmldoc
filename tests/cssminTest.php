<?php
use hexydec\html\cssmin;

final class cssminTest extends \PHPUnit\Framework\TestCase {

	protected $config = Array(
		'removesemicolon' => false,
		'removezerounits' => false,
		'removeleadingzero' => false,
		'removequotes' => false,
		'convertquotes' => false,
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
			font-family: Arial, sans-serif;
			font-size: 3em;
		}';
		$output = '#id{font-family:Arial,sans-serif;font-size:3em}';
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

	public function testCanRemoveLeadingZeros() {
		$input = '#id {
			font-size: 0.9em;
		}';
		$output = '#id{font-size:.9em;}';
		$config = $this->config;
		$config['removeleadingzero'] = true;
		$this->assertEquals($output, cssmin::minify($input, $config));
	}

	public function testCanRemoveUnnecessaryQuotes() {
		$test = Array(
			Array(
				'input' => '#id {
					background: url("test.png");
				}',
				'output' => '#id{background:url(test.png);}'
			),
			Array(
				'input' => "#id {
					background: url('test.png');
				}",
				'output' => '#id{background:url(test.png);}'
			),
			Array(
				'input' => '#id::before {
					content: "Foo (bar)";
				}',
				'output' => '#id::before{content:"Foo (bar)";}'
			),
			Array(
				'input' => '#id::before {
					content: "Foo";
				}',
				'output' => '#id::before{content:"Foo";}'
			)
		);
		$config = $this->config;
		$config['removequotes'] = true;
		foreach ($test AS $item) {
			$this->assertEquals($item['output'], cssmin::minify($item['input'], $config));
		}
	}

	public function testCanConvertQuotes() {
		$test = Array(
			Array(
				'input' => "#id {
					background: url('test.png');
				}",
				'output' => '#id{background:url("test.png");}'
			),
			Array(
				'input' => "#id::before {
					content: 'Foo (bar)';
				}",
				'output' => '#id::before{content:"Foo (bar)";}'
			),
			Array(
				'input' => "#id::before {
					content: 'Foo';
				}",
				'output' => '#id::before{content:"Foo";}'
			)
		);
		$config = $this->config;
		$config['convertquotes'] = true;
		foreach ($test AS $item) {
			$this->assertEquals($item['output'], cssmin::minify($item['input'], $config));
		}
	}

	public function testCanShortenHexValues() {
		$test = Array(
			Array(
				'input' => "#id {
					color: #000000;
				}",
				'output' => '#id{color:#000;}'
			),
			Array(
				'input' => "#id::before {
					color: #FFCCAA;
				}",
				'output' => '#id::before{color:#FCA;}'
			),
			Array(
				'input' => "#id::before {
					color: #ffccaa;
				}",
				'output' => '#id::before{color:#fca;}'
			),
			Array(
				'input' => "#id::before {
					color: #ffccab;
				}",
				'output' => '#id::before{color:#ffccab;}'
			)
		);
		$config = $this->config;
		$config['shortenhex'] = true;
		foreach ($test AS $item) {
			$this->assertEquals($item['output'], cssmin::minify($item['input'], $config));
		}
	}

	public function testCanLowerHexValues() {
		$test = Array(
			Array(
				'input' => "#id::before {
					color: #FFCCAA;
				}",
				'output' => '#id::before{color:#ffccaa;}'
			),
			Array(
				'input' => "#id::before {
					color: #FcA;
				}",
				'output' => '#id::before{color:#fca;}'
			),
			Array(
				'input' => "#id::before {
					color: #FFCCAB;
				}",
				'output' => '#id::before{color:#ffccab;}'
			)
		);
		$config = $this->config;
		$config['lowerhex'] = true;
		foreach ($test AS $item) {
			$this->assertEquals($item['output'], cssmin::minify($item['input'], $config));
		}
	}
}
