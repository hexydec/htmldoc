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
		<form action="something/" method="get">
			<h1 class="test__heading">Test</h1>
			<p style=" font-weight: bold; color : blue; ">Lorem ipsum blah blah blah</p>
			<a href="/minify/test/"><img src="test.png" alt="" /></a>
			<a href="http://test.com/"><img src="test.png" alt="" /></a>
			<select name="select">
				<option value="test">test</option>
				<option value="test2">This is also a test</option>
			</select>
		</form>
		<script type="text/javascript" nomodule="nomodule">
			alert("hi");
		</script>
	</body>
</html>';
$doc = new \hexydec\minify\htmldoc();
$doc->load($html);
$doc->minify();
echo $doc->save();
// echo \hexydec\minify\htmlmin::minify($html);
