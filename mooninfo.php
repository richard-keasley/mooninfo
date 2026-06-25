<?php
namespace basecamp;

use \DateTime;

require_once __DIR__ . '/moonphase.php';
	
class mooninfo extends \Solaris\MoonPhase {
	
private $_data = [];
		
function __construct(?DateTime $date=null) {
	if(!$date) $date = new \DateTime;	
	parent::__construct($date);

	$keys = [
		'phase', 'illumination',
		'age', 'timestamp', 
		'distance', 'diameter',
	];		
	foreach($keys as $key) {
		$this->_data[$key] = $this->{$key};
	}
	
	$this->_data['date'] = $date->format('Y-m-d');
}
	
function __get($key) {
	return match($key) {
		'data' => $this->_data,
		'image' => $this->_image(),
		'name' => $this->_name(),
		'blue' => $this->_blue(),
		'phase_name' => $this->getPhaseName(),
		default => $this->_data[$key] ?? null
	};
}

private function _blue() : string {
	$blue = [];
	$val = $this->blue_month(); if($val) $blue[] = 'month';
	$val = $this->blue_seasonal(); if($val) $blue[] = 'seasonal';
	return implode(', ', $blue);
}

/**
 * Is this is a monthly blue moon 
 * second full moon in a calendar month
 * @return bool
 */
private function blue_month() : bool {
	// look for previous full moon this month
	if($this->phase_name != 'Full Moon') return false;
	
	// this full moon
	$ts1 = (int) $this->getPhaseFullMoon();
	$moon1 = new \DateTime;
	$moon1->setTimestamp($ts1);
	
	// previous full moon
	$moon0 = static::modify($moon1, -1);
	
	// was this full moon in same calendar month as previous?
	$format = 'Y-m';
	$cmp0 = $moon0->format($format);
	$cmp1 = $moon1->format($format);
	return $cmp0==$cmp1;
}

/**
 * Is this is an astronomical blue moon 
 * third full moon in a season with 4 full moons
 * @return bool
 */
private function blue_seasonal() : bool {
	if($this->phase_name != 'Full Moon') return false;
	
	// this full moon
	$ts1 = (int) $this->getPhaseFullMoon();
	$moon3 = new \DateTime;
	$moon3->setTimestamp($ts1);
	// 2 full moons ago
	$moon1 = static::modify($moon3, -2);
	// next full moon
	$moon4 = static::modify($moon3, +1);
	// season for this full moon
	$season = static::season($moon3);
	
	if($moon1<$season[0]) return false; // 2 full moons ago was last season
	if($moon4>=$season[1]) return false; // next full moon is next season
	return true;     
}

/**
 * Traditional names for full moons by month
 */
private function _name() : string {
	if($this->phase_name != 'Full Moon') return '';
	
	$dt = new \DateTime($this->date);
	$month = (int) $dt->format('n');
	return match($month) {
		1 => 'Wolf',
		2 => 'Snow',
		3 => 'Worm',
		4 => 'Pink',
		5 => 'Flower',
		6 => 'Strawberry',
		7 => 'Buck',
		8 => 'Sturgeon',
		9 => 'Corn',
		10 => "Hunter's",
		11 => 'Beaver',
		12 => 'Cold',
		default => 'Unknown'
	};
}

/**
 * SVG image for this moon
 * uses static::$image_id to ensure all images have unique ID
 * @param phase: float for moon phase (0-1) 
 * @return string containing SVG image 
 */
static $image_id = 0;
private function _image() : string {
	// in case phase >= 1.0
	$phase = $this->phase;
	$whole = floor($phase);
	$phase = $phase - $whole;
	$qtr = (int) ($phase * 4);
	/*
	0: waxing crescent
	1: waxing gibbous
	2: waning gibbous
	3: waning crescent
	
	shadow position (left/right)
	ellipse fill (shadow/light)
	*/
	$mx = match($qtr) {
		0 => [000, '#bbb'],  // left,  shadow
		1 => [000, 'black'], // left,  light
		2 => [200, 'black'], // right, light  
		3 => [200, '#bbb'],  // right, shadow
	};

	/* 
	ellipse radius (0 - 1)	https://astronomy.stackexchange.com/questions/51714/the-shape-of-the-moon-limb-crescent-terminator-line
	b = (2n−1) R
	*/
	$elrx = abs(2 * $this->illumination - 1);
	
	// scale
	$elrx = round($elrx * 200);
	// unique ID for this image
	static::$image_id++; 
			
ob_start(); ?>
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
<defs><mask id="mmask-<?php echo static::$image_id;?>">
<rect x="<?php echo $mx[0];?>" y="0" width="200" height="400" fill="#bbb"/>
<ellipse cx="200" cy="200" rx="<?php echo $elrx;?>" ry="200" fill="<?php echo $mx[1];?>"/>
</mask></defs>
<circle cx="200" cy="200" r="200" fill="#eee"/>
<circle cx="200" cy="200" r="200" fill="#000" mask="url(#mmask-<?php echo static::$image_id;?>)"/>
</svg>
<?php return ob_get_clean();
}

/**
 * Adjust a given DateTime by the specified number of lunar months
 * @return DateTime
 */
static function modify(?DateTime $dt=null, float $diff=0) : DateTime {
	if(!$dt) $dt = new \DateTime;
	$ts = $dt->getTimestamp();
	
	// seconds per lunar month
	// $this->synmonth * 86400
	$lunar_seconds = 2551442.861952;
	$ts = $ts + ($diff * $lunar_seconds);
	
	$retval = new \DateTime;
	$retval->setTimestamp((int) $ts);
	return $retval;
}

/**
 * the season for given DateTime 
 * @return array [season start, season end] 
 */
static function season(?DateTime $dt=null) : array {
	if(!$dt) $dt = new \DateTime;
	
	// start month for the season this month belongs to
	$this_month = (int) $dt->format('n');
	$month0 = match($this_month) {
		12,  1,  2 => 12, // winter
		 3,  4,  5 =>  3, // spring
		 6,  7,  8 =>  6, // summer
		 9, 10, 11 =>  9, // autumn
	};
	// start year for this season
	$year0 = (int) $dt->format('Y');
	if($month0==12 && $this_month!=12) $year0--;
	// start of season
	$strval = sprintf('%04d-%02d-01', $year0, $month0);
	
	return [
		new DateTime($strval),
		(new DateTime($strval))->modify('+3 month') 
	];
}
 
static function example($htm_page=true) : string {
	ob_start();
	$include = __DIR__ . '/example.php';
	include $include;
	return ob_get_clean();
}
 
}
