<?php 
use basecamp\mooninfo;

if($htm_page) { ?>
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
<?php } ?>

<section style="background:#002;color:#eee;display:flex;padding:0.5em;">
<h1>Moon info</h1>

<div style="width:7em;padding:1em;"><?php
$moontime = new \DateTime;
$mooninfo = new mooninfo($moontime);
$ts_start = (int) $mooninfo->getPhaseNewMoon();
$ts_stop = (int) $mooninfo->getPhaseNextNewMoon();
$ts_period = $ts_stop - $ts_start;

$format = '<div style="display:none;" class="slide">%s</div>';

$steps = 64;
$interval = round($ts_period / $steps);
for($step=0; $step<$steps; $step++) {
	$ts = $ts_start + ($step * $interval);
	$moontime->setTimestamp($ts);
	$mooninfo = new mooninfo($moontime);
	printf($format, $mooninfo->image);
}

?>
</div>
<script>
let currentIndex = 0;

const nextSlide = (inc) => {
	const slides = document.getElementsByClassName("slide");
	for (let i = 0; i < slides.length; i++) {
		slides[i].style.display = "none";
	}
	currentIndex=(currentIndex+slides.length+inc)%slides.length;
	slides[currentIndex].style.display = "block";
}

setInterval(function() { nextSlide(1); }, 100); 

</script>
</section>

<p>Mooninfo is a wrapper class for <a href="https://github.com/BitAndBlack/php-moon-phase">php-moon-phase</a>.</p>
<p>This example page shows how to get moon information (moon name, blue moon, icon) for any given date.</p>

<section>
<div style="display:flex;flex-wrap:wrap;gap:0;background:#002"><?php

$style = 'width:3em;padding:0.5em;margin:0;';

$steps = 12;
$interval = round($ts_period / $steps);
for($step=0; $step<=$steps; $step++) {
	$ts = $ts_start + ($step * $interval);
	$moontime->setTimestamp($ts);
	$mooninfo = new mooninfo($moontime);
	echo html::div($mooninfo->image, $style);
}
?></div>

</section>

<section>
<h2>Current info</h2>
<div style="display:flex;gap:1.5em;background:#f0f0f3;">
<?php
$mooninfo = new mooninfo;
$img_style = "width:5em;background:#002;padding:0.5em;";
$ul_style = 'list-style:none;padding:0.5em 0;margin:0;';

$items = [
	'Phase' => sprintf('%s (%u%%)', $mooninfo->phase_name, round($mooninfo->illumination * 100)),
];

$timestamps = [
	'this_new'  => $mooninfo->getPhaseNewMoon(),
	'this_full' => $mooninfo->getPhaseFullMoon(),
	'next_new'  => $mooninfo->getPhaseNextNewMoon(),
	'next_full' => $mooninfo->getPhaseNextFullMoon(),
];
asort($timestamps);
$datetime = new \DateTime;
// future lunar events
$count = 0;
foreach($timestamps as $key=>$timestamp) {
	if($timestamp<=$mooninfo->timestamp) continue; // in the past
	$arr = explode('_', $key);
	$label = "Next {$arr[1]} moon";
	$datetime->setTimestamp((int) $timestamp);
	$items[$label] = $datetime->format('j M Y H:i');
	$count++;
	if($count==2) break; // all done
}

echo html::div($mooninfo->image, $img_style);
echo html::ul($items, $ul_style);
?>
</div>
</section>

<section>
<?php 
$rows = [];
$moontime = new \DateTime('2026-01-01');
$mooninfo = new mooninfo($moontime);
$moontime = new \DateTime;
$mooninfo = new mooninfo($moontime);
$ts_start = (int) $mooninfo->getPhaseNewMoon();
$moontime->setTimestamp($ts_start);
$mooninfo = new mooninfo($moontime);

$img_style = 'width:1em;margin:0 auto;';
$format0 = '<div>%s</div>';
$format1 = '<div style="text-align:center">%s</div>';
$datetime = new \DateTime;

$count = 24;
echo "<h2>Quarters for {$count} lunar months, starting {$moontime->format('j F Y')}</h2>";
?>
<div style="display:grid; width:30em; gap:0.3em; grid-template-columns:25% 25% 25% 25%;"><?php
for($i=0; $i<=$count; $i++) {
	$timestamps = [
		$mooninfo->getPhaseNewMoon(),
		$mooninfo->getPhaseFirstQuarter(),
		$mooninfo->getPhaseFullMoon(),
		$mooninfo->getPhaseLastQuarter(),
	];
	
	if(!$i) {
		// header row
		foreach($timestamps as $timestamp) {
			$datetime->setTimestamp((int) $timestamp);
			$mooninfo = new mooninfo($datetime);
			echo html::div($mooninfo->image, $img_style);
		}
	}
		
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
?>
</div>
</section>

<section>
<?php
$moontime = new \DateTime('2026-01-01');
$count = 400;
$interval = new  \DateInterval('P1D');
echo "<h2>Moon info for {$count} days, starting {$moontime->format('j F Y')}</h2>";
?>
<p>
A Synodic month (the period taken for the moon to go through a complete cycle) is 29.53 days  (stored as <code>moonphase->synmonth</code>).
Each phase lasts 1/8 of a Synodic month (3.69 days).
A <em>total</em> full moon (phase 0.5, age 14.77) occurs a day or so after the <em>start</em> of the "full moon" phase (phase 0.4375, age 12.92).</p>

<div style="display:flex;flex-wrap:wrap;gap:1em;padding:0.1em;"><?php
$mooninfo = new mooninfo($moontime);
$remember = null;
$ul_style = 'width:16em;overflow:hidden;list-style:none;padding:0.2em;margin:0;background:#eef;';
for($i=0; $i<=$count; $i++) {
	if($mooninfo->phase_name!==$remember) {
		// skip forward until we get a new moon phase 
		# $remember = $mooninfo->phase_name;
		
		$data = $mooninfo->data;
		$keys = ['phase_name', 'name', 'blue', 'image'];
		foreach($keys as $key) $data[$key] = $mooninfo->{$key};
		
		$list = [];
		foreach($data as $key=>$val) {
			if($val==='') continue;
			
			$format = 'width:%fem;padding:0.5em;margin:0;display:inline-block;vertical-align:middle;';
			$img_style = sprintf($format, 8 * $mooninfo->diameter);

			$list[$key] = match($key) {
				'image' => html::div($val, $img_style),
				
				'date' => (new \DateTime($val))->format('j M Y'),
			
				'phase', 
				'illumination', 
				'age', 
				'distance', 
				'diameter' => round($val, 3),
						
				default => $val
			};
		}
				
		echo html::ul($list, $ul_style);
	}
	
	$moontime->add($interval);
	$mooninfo = new mooninfo($moontime);
}
?></div>
</section>

<?php if($htm_page) { ?>
</body>
</html>
<?php }

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