<?php
namespace GT\Config;

class IniParser {
	public function __construct(
		protected string $pathName
	) {}

	public function parse():Config {
		$data = parse_ini_file(
			$this->pathName,
			true,
			INI_SCANNER_TYPED
		);

		if(isset($data["app"]["production"])
		&& is_bool($data["app"]["production"])) {
			$data["app"]["production"] = $data["app"]["production"] ? "1" : "0";
		}

		$configSectionList = [];
		foreach($data as $sectionName => $sectionData) {
			$configSectionList []= new ConfigSection(
				$sectionName,
				$sectionData
			);
		}

		return new Config(...$configSectionList);
	}
}
