<?php 
use basecamp\mooninfo;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="author" content="Richard Keasley">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Demonstration of Mooninfo">
<title>Mooninfo</title>
</head>
<body style="font-size:12pt;font-family:sans-serif;">

<h1>Moon info</h1>

<section>
<p>Mooninfo is a wrapper class for <a href="https://github.com/BitAndBlack/php-moon-phase">php-moon-phase</a>.</p>
<p>This example page shows how to get moon information (name, blue, icon) for a variety of dates.</p>
</section>

<section>
<h2>Moon images</h2>
<div style="display:flex;flex-wrap:wrap;gap:0;background:#202030"><?php
$int = 1 / 8;
$style = 'width:5em;padding:0.5em;margin:0;';
for($i=0; $i<=1.5; $i=$i+$int) {
	echo html::div(mooninfo::image($i), $style);
}
?></div>
</section>

<section><?php
$moontime = new \DateTime('2026-01-03');
$count = 24;
echo "<h2>Quarters for {$count} lunar months from {$moontime->format('j F Y')}</h2>";

$mooninfo = new mooninfo($moontime);

$img_style = 'width:1em;';
$format0 = '<div>%u</div>';
$format1 = '<div>%s</div>';
$datetime = new \DateTime;
	
?>
<div style="display:grid; width:24em; grid-template-columns: 25% 25% 25% 25%;"><?php
for($i=0; $i<=$count; $i++) {
	if(!$i) {
		$format = '<div>%s</div>';
		$labels = [
			html::div(mooninfo::image(0.00), $img_style),
			html::div(mooninfo::image(0.25), $img_style),
			html::div(mooninfo::image(0.50), $img_style),
			html::div(mooninfo::image(0.75), $img_style),
		];
		foreach($labels as $label) printf($format, $label);
	}
	
	$timestamps = [
		$mooninfo->getPhaseNewMoon(),
		$mooninfo->getPhaseFirstQuarter(),
		$mooninfo->getPhaseFullMoon(),
		$mooninfo->getPhaseLastQuarter(),
	];
		
	foreach($timestamps as $timestamp) {
		$datetime->setTimestamp((int) $timestamp);
		printf($format1, $datetime->format("j M y"));
	}
	
	$moontime = mooninfo::modify($moontime, 1);
	$mooninfo = new mooninfo($moontime);
	$full_ts = $mooninfo->getPhaseFullMoon();
	$full_dt = (new \DateTime)->setTimestamp((int) $full_ts);
	$mooninfo = new mooninfo($full_dt);
}
?></div>
</section>

<section>
<?php
$moontime = new \DateTime('2026-01-03');
$count = 400;
$interval = new  \DateInterval('P1D');
echo "<h2>{$count} days from {$moontime->format('j F Y')}</h2>";

$mooninfo = new mooninfo($moontime);

$timestamps = [
	'this_new'  => $mooninfo->getPhaseNewMoon(),
	'this_full' => $mooninfo->getPhaseFullMoon(),
	'next_new'  => $mooninfo->getPhaseNextNewMoon(),
	'next_full' => $mooninfo->getPhaseNextFullMoon(),
];
asort($timestamps);
$datetime = new \DateTime;
$items = [];
foreach($timestamps as $key=>$timestamp) {
	if(count($items)>1) continue;
	$datetime->setTimestamp((int) $timestamp);
	if($datetime>=$moontime) {
		$arr = explode('_', $key);
		$label = "Next {$arr[1]} moon";
		$items[$label] = $datetime->format('j M Y H:i');
	}
}
$ul_style = 'list-style:none;padding:0.2em;margin:1em 0;';
echo html::ul($items, $ul_style);

?>
<div style="display:flex;flex-wrap:wrap;gap:1em;padding:0.1em;"><?php
$remember = null;
$img_style = 'width:2em;padding:0.5em;margin:0;display:inline-block;vertical-align:middle;';
$ul_style = 'width:20em;overflow:hidden;list-style:none;padding:0.2em;margin:0;background:#f8f8f8;';
for($i=0; $i<=$count; $i++) {
	if($mooninfo->phase_name!==$remember) {
		// skip forward until we get a new moon phase 
		$remember = $mooninfo->phase_name;
		
		$info = $mooninfo->data;
		foreach($info as $key=>$val) {
			$info[$key] = match($key) {
				'image' => html::div($info[$key], $img_style),
				
				'date' => (new \DateTime($info[$key]))->format('j M Y'),
			
				'phase', 
				'illumination', 
				'age', 
				'distance', 
				'diameter' => round($info[$key], 3),
				
				default => $val
			};
		}
				
		echo html::ul($info, $ul_style);
	}
	
	$moontime->add($interval);
	$mooninfo = new mooninfo($moontime);
}
?></div>
</section>

</body>
</html>
<?php


class html {

static function ul($list, $style='') {
	$op = sprintf('<ul style="%s">', $style);
	foreach($list as $key=>$val) {
		$op .= "<li><strong>{$key}:</strong> {$val}</li>";
	}
	$op .= '</ul>';
	return $op;
}

static function div($content, $style='') {
	$format = '<div style="%s">%s</div>';
	return sprintf($format, $style, $content);
}

}