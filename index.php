<?php

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

echo \hexydec\minify\cssmin::minify($css);
