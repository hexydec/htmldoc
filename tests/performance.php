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

	// test the performance
	foreach ($urls AS $item) {
		var_dump($item);
		set_time_limit(30);
		$time1 = microtime(true);
		if (($input = fetch($item)) !== false) {
			$time2 = microtime(true);
			$results[$item] = [
				'load' => $time2 - $time1
			];
			$obj = new \hexydec\html\htmldoc();
			if ($obj->load($input)) {
				$start = $time = microtime(true);
				$results[$item]['parse'] = $time - $time2;
				$obj->minify();
				$time2 = microtime(true);
				$results[$item]['minify'] = $time2 - $time;
				$output = $obj->save();
				$time = microtime(true);
				$results[$item]['compile'] = $time - $time2;
				$results[$item]['total'] = $time - $start;
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
							<td><?= htmlspecialchars(number_format((100 / $item['input']) * $item['output'])); ?>%</td>
							<td><?= htmlspecialchars(number_format($item['inputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['outputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format($item['inputgz'] - $item['outputgz'])); ?></td>
							<td><?= htmlspecialchars(number_format((100 / $item['inputgz']) * $item['outputgz'])); ?>%</td>
							<td><?= htmlspecialchars(number_format($item['load'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['parse'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['minify'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['compile'], 4)); ?>s</td>
							<td><?= htmlspecialchars(number_format($item['total'], 4)); ?>s</td>
						</tr>
					<?php }
					$count = count($results);
					$input = array_sum(array_column($results, 'input')) / $count;
					$output = array_sum(array_column($results, 'output')) / $count;
					$inputgz = array_sum(array_column($results, 'inputgz')) / $count;
					$outputgz = array_sum(array_column($results, 'outputgz')) / $count;
					$load = array_sum(array_column($results, 'load')) / $count;
					$parse = array_sum(array_column($results, 'parse')) / $count;
					$minify = array_sum(array_column($results, 'minify')) / $count;
					$compile = array_sum(array_column($results, 'compile')) / $count;
					$total = array_sum(array_column($results, 'total')) / $count; ?>
					<tr style="font-weight:bold">
						<td colspan="2">Total</td>
						<td><?= htmlspecialchars(number_format($input)); ?></td>
						<td><?= htmlspecialchars(number_format($output)); ?></td>
						<td><?= htmlspecialchars(number_format($input - $output)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $input) * $output)); ?>%</td>
						<td><?= htmlspecialchars(number_format($inputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format($inputgz - $outputgz)); ?></td>
						<td><?= htmlspecialchars(number_format((100 / $inputgz) * $outputgz)); ?>%</td>
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
