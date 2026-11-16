<?php
namespace GT\Config;

class FileWriter {
	public function __construct(
		private Config $config
	) {
	}

	public function writeIni(string $filePath):void {
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), 0775, true);
		}

		$data = [];
		if(is_file($filePath)) {
			$data = parse_ini_file($filePath, true, INI_SCANNER_TYPED);
			if($data === false) {
				throw new ConfigException("Unable to parse file: $filePath");
			}
		}

		foreach($this->config->getSectionNames() as $sectionName) {
			$data[$sectionName] = array_replace_recursive(
				$data[$sectionName] ?? [],
				$this->config->getSection($sectionName)->asArray()
			);
		}

		$contents = [];
		foreach($data as $sectionName => $section) {
			$contents []= $this->encodeSection($sectionName, $section);
		}

		if(file_put_contents($filePath, implode("\n\n", $contents) . "\n") === false) {
			throw new ConfigException("Unable to write to file: $filePath");
		}
	}


	/** @param array<string, mixed> $section */
	private function encodeSection(string $name, array $section):string {
		$lines = ["[$name]"];
		foreach($section as $key => $value) {
			if(is_array($value)) {
				foreach($value as $index => $item) {
					$lines []= "$key" . "[$index] = " . $this->encode($item);
				}
			}
			else {
				$lines []= "$key = " . $this->encode($value);
			}
		}
		return implode("\n", $lines);
	}

	private function encode(mixed $value):string {
		if(!is_string($value)) {
			return json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
		}

		// Only leave a string bare if the INI parser preserves its value and type.
		if($value === trim($value)
		&& preg_match('/\A[\pL\pN_ .\/:@,\-]+\z/u', $value)
		&& (parse_ini_string("value = $value", false, INI_SCANNER_TYPED)["value"] ?? null)
			=== $value) {
			return $value;
		}

		return '"' . strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$']) . '"';
	}
}
