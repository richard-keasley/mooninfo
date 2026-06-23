# mooninfo

Mooninfo is a wrapper class for 
[php-moon-phase](https://github.com/BitAndBlack/php-moon-phase)

```
spl_autoload_register(function($classname) {
	$target = 'basecamp\mooninfo';
	if($classname!==$target) return false;
	$suffix = ''; // maybe used later if I release versions
	$docroot = __DIR__; // set this to the root for your project
	$include = sprintf('%s/mooninfo%s/mooninfo.php', $docroot, $suffix);
	$success = is_file($include);
	if($success) require $include;	
	return $success;
});

echo \basecamp\mooninfo::example();
```
