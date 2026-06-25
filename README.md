# mooninfo

Mooninfo is a wrapper class for 
[php-moon-phase](https://github.com/BitAndBlack/php-moon-phase)

```
spl_autoload_register(function($classname) {
	if($classname==='basecamp\mooninfo') {
		$suffix = ''; // version suffix (e.g."-1.1.0")
		$docroot = __DIR__; // path to the project's dependencies
		$include = sprintf('%s/mooninfo%s/mooninfo.php', $docroot, $suffix);
		if(is_file($include)) {
			require $include;
			return true;
		}
	}
	return false;
});

echo \basecamp\mooninfo::example();
```
