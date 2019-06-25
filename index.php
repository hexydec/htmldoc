<?php
require(__DIR__.'/htmldoc.php');
require(__DIR__.'/cssmin.php');
require(__DIR__.'/tokenise.php');


$css = '
h1:not(.cls) {
	font-size: 1.8em;
	margin: 5px 0px 50% 10px;
	padding: 0%;
	background: #FFFFFF url("../graphics/image.png") no-repeat 50% 50%;
	text-shadow: 1px 1px 5px #FFEEAA, -1px -1px 5px #FFF;
}

@media only screen and (max-width: 600px) {
	body {
		background-color: lightblue;
	}
}

h1, h2, h3 {
	color: #FFF;
}
/* comment */
li a, li a:hover li a:hover > span {
	color: blue; /* The pen is blue */
}

@font-face {
	font-family: metropolis;
	src: url(../metropolis.woff) format("woff");
	font-weight: normal;
	font-style: normal;
}

p {
	font-size: 0.9em;
}
';

//echo \hexydec\minify\cssmin::minify($css);

$html = '
<!DOCTYPE html>
<html>
	<head>
		<title>Test HTML Parser</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style type="text/css">
			h1 {
				font-size: 2em;
			}
		</style>
	</head>
	<body>
		<!-- this is a comment -->
		<!--[if IE 8]><span>IE8</span><![endif]-->
		<!--[if gt IE 8]><!--><span>Greater than IE8</span><!--<![endif]-->
		<form action="something/" method="get" class="form form--state">
			<h1 class="test__heading">Test</h1>
			<p style=" font-weight: bold; color : blue; " class="paragraph">Lorem ipsum blah blah blah</p>
			<a href="/minify/test/"><img src="test.png" alt="" class="image" /></a>
			<a href="http://test.com/"><img src="test.png" class="image" alt="" /></a>
			<div>
				<h2>Heading tthat isn\'t closed
				<p>Paragraph that isn\'t closed
			</div>
			<select name="select">
				<option value="test">test</option>
				<option value="test2">This is also a test</option>
			</select>
			<input type="text" class=" " value="" />
		</form>
		<script type="text/javascript" nomodule="nomodule">
			alert("hi");
		</script>
	</body>
</html>';
// $html = '<div>
// 	<h2>Heading tthat isn\'t closed
// 	<p>Paragraph that isn\'t closed
// </div>
// <select name="select"></select>';
$time = microtime(true);
$doc = new \hexydec\html\htmldoc();
$doc->load($html);
// $doc->open('https://www.php.net/manual/en/function.strcspn.php');
$doc->minify();
exit($doc->save());
// echo "\n\n".number_format(microtime(true) - $time, 8)."\n\n";


// require(__DIR__.'/workspace/htmlmin.class.php');
// $time = microtime(true);
// echo \hexydec\minify\htmlmin::minify($html);
// echo "\n\n".number_format(microtime(true) - $time, 8)."\n\n";


$source = '';
?>
<!DOCTYPE html>
<html>
	<head>

	</head>
	<body>
		<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" accept-charset="<?= mb_internal_encoding(); ?>">
			<h1>HTML Minifier</h1>
			<textarea name="source"><?= htmlspecialchars($source); ?></textarea>
			<div></div>
		</form>
	</body>
</html>
