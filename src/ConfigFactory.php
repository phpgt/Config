<?php
namespace GT\Config;

class ConfigFactory {
	const EXTENSION = "ini";
	const FILE_DEFAULT = "default";
	const FILE_OVERRIDE_ORDER = [
		"dev",
		"deploy",
		"production",
	];

	public static function createForProject(
		string $projectRoot,
		?string $defaultConfigPathName = null
	):Config {
		$order = array_merge(
			[self::FILE_DEFAULT, ""],
			self::FILE_OVERRIDE_ORDER
		);

		$previousConfig = null;

		if(!is_null($defaultConfigPathName)) {
			$previousConfig = ConfigFactory::createFromPathName(
				$defaultConfigPathName
			);
		}

		foreach($order as $file) {
			$fileName = "config";
			$fileName .= ".";

			if(!empty($file)) {
				$fileName .= $file;
				$fileName .= ".";
			}

				$fileName .= self::EXTENSION;
				$config = self::createFromPathName(
					implode(DIRECTORY_SEPARATOR, [
						$projectRoot,
						$fileName,
					])
			);
			if(is_null($config)) {
				continue;
			}

			if($previousConfig) {
				$config = $config->withMerge($previousConfig);
			}

			if($file === "production") {
				$config = self::withProductionFlag($config);
			}

			$previousConfig = $config;
		}

		return $config ?? $previousConfig;
	}

	public static function createFromPathName(string $pathName):?Config {
		if(!is_file($pathName)) {
			return null;
		}

		$parser = new IniParser($pathName);
		return $parser->parse();
	}

	protected static function withProductionFlag(Config $config):Config {
		$appSection = $config->getSection("app");
		if($appSection && $appSection->contains("production")) {
			return $config;
		}

		return $config->withMerge(new Config(
			new ConfigSection("app", [
				"production" => "true",
			])
		));
	}
}
