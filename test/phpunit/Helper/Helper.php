<?php
namespace Gt\Config\Test\Helper;

class Helper {
	const INI_SIMPLE = <<<INI
[app]
namespace=ExampleApp

[block1]
; This is a comment
value = 123
; Another comment
valueNoSpaces=456
value.nested = 789
value.nested.again = 0123456789

[database]
host=localhost
schema=example
port=3306
INI;

	const INI_DEFAULT = <<<INI
[app]
namespace=App

[block1]
value.nested = default789
value.existsByDefault = this appears by default
INI;


	const INI_OVERRIDE_DEV = <<<INI
[block1]
value.nested = dev789override

[database]
host=dev.database
INI;

	const INI_OVERRIDE_PROD = <<<INI
[database]
host=my.production.database

[exampleapi]
key=secret-key-only-on-production
INI;


	public static function getBaseTmpDir() {
		return implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"config"
		]);
	}

	public static function getTmpDir(string...$parts) {
		$parts = array_merge([
			self::getBaseTmpDir(),
			uniqid(),
		], $parts);

		return implode(DIRECTORY_SEPARATOR, $parts);
	}

	public static function removeTmpDir() {
		self::recursiveRemove(self::getBaseTmpDir());
	}

	public static function recursiveRemove(string $dir) {
		if(!file_exists($dir)) {
			return;
		}

		$scanDir = array_diff(scandir($dir), array('.', '..'));

		foreach($scanDir as $file) {
			if(is_dir("$dir/$file")) {
				self::recursiveRemove("$dir/$file");
			}
			else {
				unlink("$dir/$file");
			}
		}

		rmdir($dir);
	}
}
