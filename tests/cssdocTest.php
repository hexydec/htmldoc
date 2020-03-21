<?php
use hexydec\css\cssdoc;

final class cssdocTest extends \PHPUnit\Framework\TestCase {

	protected $config = Array(
   		'removesemicolon' => false,
   		'removezerounits' => false,
   		'removeleadingzero' => false,
   		'convertquotes' => false,
   		'removequotes' => false,
   		'shortenhex' => false,
   		'lowerhex' => false,
   		'sortselectors' => false,
   		'email' => false,
		'maxline' => false,
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
			),
			Array(
				'input' => '@media screen {
					#id {
						font-size: 3em;
					}
				}

				#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}
				',
				'output' => '@media screen{#id{font-size:3em;}}#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			)
		);
		$config = $this->config;
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
		}
	}

	public function testCanMinifyMediaQueries() {
		$test = Array(
			Array(
				'input' => '@media screen {
					#id {
						font-size: 3em;
					}
				}

				#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}
				',
				'output' => '@media screen{#id{font-size:3em;}}#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			),
			Array(
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-size:3em;}}'
			),
			Array(
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-size:3em;}}'
			),
			Array(
				'input' => '@media screen, print and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen,print and (max-width:800px){#id{font-size:3em;}}'
			),
			Array(
				'input' => '@media ( color ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media (color){#id{font-size:3em;}}'
			)
		);
		$config = $this->config;
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
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
		$obj = new cssdoc();
		if ($obj->load($input)) {
			$obj->minify($config);
			$this->assertEquals($output, $obj->compile());
		}
	}

	public function testCanRemoveZeroUnits() {
		$input = '#id {
			margin: 0px 0% 20px 0em;
		}
		.class {
			transition: all 500ms;
		}';
		$output = '#id{margin:0 0 20px 0;}.class{transition:all 500ms;}';
		$config = $this->config;
		$config['removezerounits'] = true;
		$obj = new cssdoc();
		if ($obj->load($input)) {
			$obj->minify($config);
			$this->assertEquals($output, $obj->compile());
		}
	}

	public function testCanRemoveLeadingZeros() {
		$input = '#id {
			font-size: 0.9em;
		}';
		$output = '#id{font-size:.9em;}';
		$config = $this->config;
		$config['removeleadingzero'] = true;
		$obj = new cssdoc();
		if ($obj->load($input)) {
			$obj->minify($config);
			$this->assertEquals($output, $obj->compile());
		}
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
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
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
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
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
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
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
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
		}
	}

	public function testCanMinifyEmailCss() {
		$test = Array(
			Array(
				'input' => "#id {
					color: #000000;
				}",
				'output' => '#id{color:#000000}'
			),
			Array(
				'input' => "#id::before {
					color: #FFCCAA;
				}",
				'output' => '#id::before{color:#ffccaa}'
			),
			Array(
				'input' => "#id::before {
					color: #ffccaa;
				}",
				'output' => '#id::before{color:#ffccaa}'
			),
			Array(
				'input' => "#id::before {
					color: #ffccab;
				}",
				'output' => '#id::before{color:#ffccab}'
			)
		);
		$config = ['email' => true];
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
		}
	}
}
