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
	$this->_data['phase_name'] = $this->getPhaseName();
	$this->_data['image'] = static::image($this->phase);
	$this->_data['name'] = static::moon_name($date);
	
	$blue = [];
	$val = $this->blue_month(); if($val) $blue[] = 'month';
	$val = $this->blue_seasonal(); if($val) $blue[] = 'seasonal';
	/*
	if($blue) {
		$ts = (int) $this->getPhaseFullMoon();
		$dt = new \DateTime;
		$dt->setTimestamp($ts);
		array_unshift($blue, $dt->format('Y-m-d'));
	}
	*/
	$this->_data['blue'] = implode(', ', $blue);
}
	
function __get($key) {
	if($key=='data') return $this->_data;
	return $this->_data[$key] ?? null ;
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

/**
 * Traditional names for full moons by month
 */
static function moon_name(?DateTime $dt=null): string {
	if(!$dt) $dt = new \DateTime;
	
	$month = (int) $dt->format('n');
	return match($month) {
		1 => 'Wolf Moon',
		2 => 'Snow Moon',
		3 => 'Worm Moon',
		4 => 'Pink Moon',
		5 => 'Flower Moon',
		6 => 'Strawberry Moon',
		7 => 'Buck Moon',
		8 => 'Sturgeon Moon',
		9 => 'Corn Moon',
		10 => "Hunter's Moon",
		11 => 'Beaver Moon',
		12 => 'Cold Moon',
		default => 'Unknown'
	};
}

/**
 * SVG image for given moonphase
 * @param phase: float for moon phase (0-1) 
 * @return string containing SVG image 
 */

static function image(float $phase) : string {
	$count = 8; // 8 images
	$key = round($phase * $count) % $count;
		
	$mask = match($key) {
		// new moon
        0 => '<rect width="100" height="100" fill="black"/>',
		
		1 => '<rect width="100" height="100" fill="white"/>
		<circle cx="10" cy="50" r="65" fill="black"/>',
				 
        2 => '<rect x="50" y="0" width="50" height="100" fill="white"/>',
		
		3 => '<rect width="100" height="100" fill="black"/>
		<circle cx="90" cy="50" r="65" fill="white"/>',
	
		4 => '<rect width="100" height="100" fill="white"/>',
		
		5 => '<rect width="100" height="100" fill="black"/>
		<circle cx="10" cy="50" r="65" fill="white"/>',
		
		6 => '<rect x="0" y="0" width="50" height="100" fill="white"/>',
		
		7 => '<rect width="100" height="100" fill="white"/>
		<circle cx="90" cy="50" r="65" fill="black"/>',
		
	};
	/*
	shadow (#303038) in 1st layer
	colour (#f0f0f5) rendered on top with a mask to obscure the hidden parts 
	*/
	$format = '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
		<defs><mask id="mm%2$u">%s</mask></defs>
		<circle cx="50" cy="50" r="50" fill="#303038"/>
		<circle cx="50" cy="50" r="50" fill="#f0f0f5" mask="url(#mm%2$u)"/>
		</svg>';	 
	return sprintf($format, $mask, $key);
}
 
static function example($htm_page=true) : string {
	ob_start();
	$include = __DIR__ . '/example.php';
	include $include;
	return ob_get_clean();
}
 
}
