<?php
ini_set('memory_limit', '256M');
$file = dirname(__DIR__).'/vendor/autoload.php';
require(file_exists($file) ? $file : dirname(__DIR__).'/src/autoload.php');

function fetch($url) {
	$cache = __DIR__.'/cache/'.preg_replace('/[^0-9a-z]++/i', '-', $url).'.cache';
	if (file_exists($cache)) {
		$url = $cache;
	}
	$context = stream_context_create([
		'http' => [
			'headers' => [
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Encoding: none',
				'Accept-Language: en-GB,en;q=0.5',
				'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:87.0) Gecko/20100101 Firefox/87.0'
			]
		]
	]);
	$html = file_get_contents($url, false, $context);
	if ($url != $cache) {
		$dir = dirname($cache);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		file_put_contents($cache, $html);
	}
	return $html ? $html : false;
}

$results = [];
$obj = new \hexydec\html\htmldoc();
if (($html = fetch('https://kinsta.com/blog/wordpress-site-examples/')) !== false && $obj->load($html)) {

	// get the URLs
	$urls = [];
	foreach ($obj->find('h3 > a') AS $item) {
		if (($href = $item->attr('href')) !== null) {
			$urls[] = $href;
		}
		// if (count($urls) == 90) {
		// 	break;
		// }
	}

	// cache css and js
	$config = [
		'custom' => [
			'style' => [
				'cache' =>  __DIR__.'/cache/%s.css'
			],
			'script' => [
				'cache' =>  __DIR__.'/cache/%s.js'
			]
		]
	];

	// test the performance
	foreach ($urls AS $item) {
		set_time_limit(30);
		$start = microtime(true);
		if (($input = fetch($item)) !== false) {

			// Setup the environment
			$_SERVER['HTTP_HOST'] = parse_url($item, PHP_URL_HOST);
			$_SERVER['REQUEST_URI'] = parse_url($item, PHP_URL_PATH);
			$_SERVER['HTTPS'] = mb_strpos($item, 'https://') === 0 ? 'on' : '';

			// setup timing
			$fetch = microtime(true);
			$results[$item] = [
				'load' => $fetch - $start
			];

			// create the object
			$obj = new \hexydec\html\htmldoc($config);
			if ($obj->load($input)) {
				$load = microtime(true);
				$results[$item]['parse'] = $load - $fetch;

				// minify
				$obj->minify();
				$minify = microtime(true);
				$results[$item]['minify'] = $minify - $load;

				// output
				$output = $obj->html();
				$save = microtime(true);

				// compile timings
				$results[$item]['compile'] = $save - $minify;
				$results[$item]['total'] = $save - $fetch;
				$results[$item]['input'] = strlen($input);
				$results[$item]['inputgz'] = strlen(gzencode($input));
				$results[$item]['output'] = strlen($output);
				$results[$item]['outputgz'] = strlen(gzencode($output));
			}
		} else {
			unset($results[$item]);
		}
	}
} ?>
<!DOCTYPE html>
<html>
	<head>
		<title>HTMLdoc Performance Tests</title>
		<style>
			html, body {
				margin: 0;
				font-family: Segoe UI;
			}
			h3 {
				margin: 0;
			}
			.minify__table {
				margin: 10px;
				font-size: 0.9em;
			}
			.minify__table th, .minify__table td {
				padding: 5px;
				text-align: center;
				border-bottom: 1px solid #CCC;
			}
			.minify__table td:first-child {
				text-align: left;
				font-weight: bold;
			}
		</style>
	</head>
	<body>
		<?php if ($results) { ?>
			<table class="minify__table">
				<thead>
					<tr>
						<th></th>
						<th>Website</th>
						<th>Input (bytes)</th>
						<th>Output (bytes)</th>
						<th>Diff (bytes)</th>
						<th>% of Original</th>
						<th>Input (Gzipped)</th>
						<th>Output (Gzipped)</th>
						<th>Diff (bytes)</th>
						<th>% of Original</th>
						<th>Load (secs)</th>
						<th>Parse (secs)</th>
						<th>Minify (secs)</th>
						<th>Output (secs)</th>
						<th>Total (secs)</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					 foreach ($results AS $key => $item) { ?>
						<tr>
							<td><?= $i++; ?></td>
							<td><h3><a href="<?= htmlspecialchars($key); ?>" target="_blank"><?= htmlspecialchars($key); ?></td>
							<td><?= htmlspecialchars(number_format($item['input'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['output'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['input'] - $item['output'])); ?></td>
							<td><?= htmlspecialchars(number_format((100 / $item['input']) * $item['output'], 2)); ?>%</td>
							<td><?= htmlspecialchars(number_format($item['inputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['outputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['inputgz'] - $item['outputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format((100 / $item['inputgz']) * $item['outputgz'], 2)); ?>%</td>
							<td><?= htmlspecialchars(number_format($item['load'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['parse'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['minify'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['compile'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['total'], 4)); ?>s</td>
						</tr>
					<?php }
					$count = count($results);
					$input = array_sum(array_column($results, 'input'));
					$output = array_sum(array_column($results, 'output'));
					$inputgz = array_sum(array_column($results, 'inputgz'));
					$outputgz = array_sum(array_column($results, 'outputgz'));
					$load = array_sum(array_column($results, 'load'));
					$parse = array_sum(array_column($results, 'parse'));
					$minify = array_sum(array_column($results, 'minify'));
					$compile = array_sum(array_column($results, 'compile'));
					$total = array_sum(array_column($results, 'total')); ?>
					<tr style="font-weight:bold">
						<td colspan="2">Total</td>
						<td><?= htmlspecialchars(number_format($input)); ?></td>
						<td><?= htmlspecialchars(number_format($output)); ?></td>
						<td><?= htmlspecialchars(number_format($input - $output)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $input) * $output, 2)); ?>%</td>
						<td><?= htmlspecialchars(number_format($inputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($inputgz - $outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $inputgz) * $outputgz, 2)); ?>%</td>
						<td><?= htmlspecialchars(number_format($load, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($parse, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($minify, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($compile, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($total, 4)); ?>s</td>
					</tr>
					<?php
					$count = count($results);
					$input /= $count;
					$output /= $count;
					$inputgz /= $count;
					$outputgz /= $count;
					$load /= $count;
					$parse /= $count;
					$minify /= $count;
					$compile /= $count;
					$total /= $count;
					?>
					<tr style="font-weight:bold">
						<td colspan="2">Average</td>
						<td><?= htmlspecialchars(number_format($input)); ?></td>
						<td><?= htmlspecialchars(number_format($output)); ?></td>
						<td><?= htmlspecialchars(number_format($input - $output)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $input) * $output, 2)); ?>%</td>
						<td><?= htmlspecialchars(number_format($inputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($inputgz - $outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $inputgz) * $outputgz, 2)); ?>%</td>
						<td><?= htmlspecialchars(number_format($load, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($parse, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($minify, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($compile, 4)); ?>s</td>
						<td><?= htmlspecialchars(number_format($total, 4)); ?>s</td>
					</tr>
				</tbody>
			</table>
		<?php } ?>
	</body>
</html>
