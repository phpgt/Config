<?php
namespace Gt\Config;

class Generator {
	const ALLOWED_SUFFIXES = [
		"dev", "deploy", "prod",
	];

	/** @var array<int, ConfigSection> */
	protected array $sectionData;
	protected string $filePath;

	/** @param array<int, string> $argv */
	public function __construct(array $argv) {
		$this->checkArgs($argv);

		array_shift($argv);
		$suffix = array_shift($argv);
		$this->filePath = "config.$suffix.ini";
		$kvp = $this->splitKvp($argv);
		$sectionList = $this->splitDotNotation($kvp);
		$this->sectionData = $this->getSectionData($sectionList);
	}

	public function generate():void {
		$config = new Config(...$this->sectionData);
		$writer = new FileWriter($config);
		$writer->writeIni($this->filePath);
	}

	/** @param array<int, string> $args */
	protected function checkArgs(array $args):void {
		if(count($args) <=2) {
			throw new InvalidArgumentException(
				"Not enough arguments supplied"
			);
		}

		if(!in_array($args[1], self::ALLOWED_SUFFIXES)) {
			throw new InvalidArgumentException(
				"Invalid config suffix: {$args[1]}"
			);
		}

		for($i = 2, $len = count($args); $i < $len; $i++) {
			if(strlen($args[$i]) > 2
			&& 1 === preg_match("/.+\..+=.+/", $args[$i])) {
				continue;
			}

			throw new InvalidArgumentException(
				"Invalid key-value pair: {$args[$i]}"
			);
		}
	}

	/**
	 * @param array<int, string> $argv
	 * @return array<string, string>
	 */
	protected function splitKvp(array $argv):array {
		$kvp = [];

		foreach($argv as $arg) {
			list($key, $value) = explode("=", $arg);
			if(empty($key) || empty($value)) {
				continue;
			}

			$kvp[$key] = $value;
		}

		return $kvp;
	}

	/**
	 * @param array<string, string> $data
	 * @return array<string, array<string, string>>
	 */
	protected function splitDotNotation(array $data):array {
		$result = [];

		foreach($data as $key => $value) {
			list($sectionName, $subKey) = explode(".", $key);

			if(empty($sectionName) || empty($subKey)) {
				continue;
			}

			if(!isset($result[$sectionName])) {
				$result[$sectionName] = [];
			}

			$result[$sectionName][$subKey] = $value;
		}

		return $result;
	}

	/**
	 * @param array<string, array<string, string>> $sectionList
	 * @return array<int, ConfigSection>
	 */
	protected function getSectionData(array $sectionList):array {
		$result = [];

		foreach($sectionList as $sectionName => $data) {
			array_push(
				$result,
				new ConfigSection($sectionName, $data)
			);
		}

		return $result;
	}
}
