<?php
namespace GT\Config;

class ConfigFactory {
	const EXTENSION = "ini";
	const FILE_DEFAULT = "default";
	const FILE_OVERRIDE_ORDER = [
		"dev", "development", "deploy", "stag", "staging", "prod", "production",
	];
	const ENVIRONMENT_FILES = [
		"dev" => Environment::DEVELOPMENT,
		"development" => Environment::DEVELOPMENT,
		"stag" => Environment::STAGING,
		"staging" => Environment::STAGING,
		"prod" => Environment::PRODUCTION,
		"production" => Environment::PRODUCTION,
	];

	public static function createForProject(
		string $projectRoot,
		?string $defaultConfigPathName = null
	):Config {
		$environmentFiles = self::findEnvironmentFiles($projectRoot);
		$order = array_merge([self::FILE_DEFAULT, ""], self::FILE_OVERRIDE_ORDER);
		$config = $defaultConfigPathName === null
			? new Config()
			: self::createFromPathName($defaultConfigPathName) ?? new Config();

		foreach($order as $file) {
			$path = $projectRoot . DIRECTORY_SEPARATOR . self::fileName($file);
			$override = self::createFromPathName($path);
			if($override !== null) {
				$config = $override->withMerge($config);
			}
		}

		foreach($environmentFiles as $environment) {
			return $config->withEnvironment($environment);
		}

		$config->isProduction();
		return $config;
	}

	public static function createFromPathName(string $pathName):?Config {
		if(!is_file($pathName)) {
			return null;
		}

		$parser = new IniParser($pathName);
		return $parser->parse();
	}

	/** @return array<string, Environment> */
	private static function findEnvironmentFiles(string $projectRoot):array {
		$found = [];
		foreach(self::ENVIRONMENT_FILES as $file => $environment) {
			$fileName = self::fileName($file);
			if(is_file($projectRoot . DIRECTORY_SEPARATOR . $fileName)) {
				$found[$fileName] = $environment;
			}
		}

		if(count($found) > 1) {
			throw new EnvironmentConfigurationException(
				"Only one environment configuration file is allowed: "
				. implode(", ", array_keys($found)) . "."
			);
		}

		return $found;
	}

	private static function fileName(string $suffix):string {
		return "config" . ($suffix === "" ? "" : ".$suffix") . "." . self::EXTENSION;
	}
}
