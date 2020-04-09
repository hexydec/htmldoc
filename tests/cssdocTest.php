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
		'lowerproperties' => false,
		'lowervalues' => false,
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
				'input' => '
					#id {
						font-size: 3em !important;
					}
				',
				'output' => '#id{font-size:3em!important;}'
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

	public function testCanMinifyDirectives() {
		$test = Array(
			Array(
				'input' => '@charset   "utf-8"   ;',
				'output' => '@charset "utf-8";'
			),
			Array(
				'input' => '@font-face {
					font-family: "gotham";
					src: url(".../css/gotham-medium.woff2") format("woff2"),
						url("../css/gotham/gotham-medium.woff") format("woff");
					font-display: block;
				}
				',
				'output' => '@font-face{font-family:"gotham";src:url(".../css/gotham-medium.woff2") format("woff2"),url("../css/gotham/gotham-medium.woff") format("woff");font-display:block;}'
			),
			Array(
				'input' => '@import url("fineprint.css") print;
					@import url("bluish.css") speech;
					@import \'custom.css\';
					@import url("chrome://communicator/skin/");
					@import "common.css" screen;
					@import url(\'landscape.css\') screen and (orientation: landscape);
				',
				'output' => '@import url("fineprint.css") print;@import url("bluish.css") speech;@import \'custom.css\';@import url("chrome://communicator/skin/");@import "common.css" screen;@import url(\'landscape.css\') screen and (orientation:landscape);'
			),
			Array(
				'input' => '@page {
						margin: 1cm;
					}

					@page :first {
						margin: 2cm;
					}
				',
				'output' => '@page{margin:1cm;}@page :first{margin:2cm;}'
			),
			Array(
				'input' => '@keyframes slidein {
					from {
				    	transform: translateX(0%);
					}

				  	to {
					  	transform: translateX(100%);
					}
				}
				',
				'output' => '@keyframes slidein{from{transform:translateX(0%);}to{transform:translateX(100%);}}'
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
			),
			Array(
				'input' => '@font-face {
					font-family: "gotham";
					src: url(".../css/gotham-medium.woff2") format("woff2"),
						url("../css/gotham/gotham-medium.woff") format("woff");
					font-display: block;
				}
				',
				'output' => '@font-face{font-family:gotham;src:url(.../css/gotham-medium.woff2) format("woff2"),url(../css/gotham/gotham-medium.woff) format("woff");font-display:block;}'
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

	public function testCanLowerValues() {
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
			),
			Array(
				'input' => '#id::before {
					background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
				}',
				'output' => '#id::before{background:#ffccab url("TEST.PNG") no-repeat 50% top;}'
			),
			Array(
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						FONT-WEIGHT: BOLD;
						background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{FONT-WEIGHT:bold;background:#ffccab url("TEST.PNG") no-repeat 50% top;}}'
			)
		);
		$config = $this->config;
		$config['lowervalues'] = true;
		$obj = new cssdoc();
		foreach ($test AS $item) {
			if ($obj->load($item['input'])) {
				$obj->minify($config);
				$this->assertEquals($item['output'], $obj->compile());
			}
		}
	}

	public function testCanLowerProperties() {
		$test = Array(
			Array(
				'input' => "#id {
					COLOR: #FFCCAA;
				}",
				'output' => '#id{color:#FFCCAA;}'
			),
			Array(
				'input' => ".camelClass {
					COLOR: #FcA;
					Font-Weight: BOLD;
					Font-STYLE: Italic;
				}",
				'output' => '.camelClass{color:#FcA;font-weight:BOLD;font-style:Italic;}'
			),
			Array(
				'input' => "@font-face {
					FONT-FAMILY: GOTHAM;
				}",
				'output' => '@font-face{font-family:GOTHAM;}'
			),
			Array(
				'input' => '#id::before {
					background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
				}',
				'output' => '#id::before{background:#FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;}'
			),
			Array(
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						FONT-WEIGHT: BOLD;
						BACKGROUND: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-weight:BOLD;background:#FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;}}'
			)
		);
		$config = $this->config;
		$config['lowerproperties'] = true;
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
