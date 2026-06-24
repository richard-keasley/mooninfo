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
 * SVG image for given moonphase
 * @param phase: float for moon phase (0-1) 
 * @return string containing SVG image 
 */
static $mask_id = 0;
static function image(float $phase) : string {
	// in case phase >= 1.0
	$whole = floor($phase);
	$phase = $phase - $whole;
	$qtr = (int) ($phase * 4);
	/*
	0: waxing crescent
	1: waxing gibbous
	2: waning gibbous
	3: waning crescent
	
	shadow (left/right)
	ellipse fill (shadow/light)
	ellipse factor (0 - 0.25)
	*/
	$matrix = [
		[000, '#bbb', 0.25 - $phase,], // left,  shadow
		[000, 'black', $phase - 0.25,], // left,  light
		[200, 'black', 0.75 - $phase,], // right, light  
		[200, '#bbb', $phase - 0.75,], // right, shadow
	];
	$recx = $matrix[$qtr][0];   // shadow position
	$ellf = $matrix[$qtr][1];   // ellipse fill
	$factor = $matrix[$qtr][2]; // ellipse factor
	$radians = 2 * pi() * $factor; // ellipse angle
	$ellx = sin($radians); // ellipse width
	
	// scale
	$ellx = $ellx * 200;
	// unique ID for this image mask
	self::$mask_id++; 
			
ob_start(); ?>
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
<defs><mask id="mmask-<?php echo self::$mask_id;?>">
<rect x="<?php echo $recx;?>" y="0" width="200" height="400" fill="#bbb"/>
<ellipse cx="200" cy="200" rx="<?php echo $ellx;?>" ry="200" fill="<?php echo $ellf;?>"/>
</mask></defs>
<circle cx="200" cy="200" r="200" fill="#eee"/>
<circle cx="200" cy="200" r="200" fill="#000" mask="url(#mmask-<?php echo self::$mask_id;?>)"/>
</svg>
<?php return ob_get_clean();
}
 
static function example($htm_page=true) : string {
	ob_start();
	$include = __DIR__ . '/example.php';
	include $include;
	return ob_get_clean();
}
 
}
