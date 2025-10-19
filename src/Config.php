<?php
namespace Gt\Config;

use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use Gt\TypeSafeGetter\TypeSafeGetter;

class Config implements TypeSafeGetter {
	use NullableTypeSafeGetter;

	/** @var ConfigSection[] */
	protected array $sectionList = [];

	public function __construct(ConfigSection...$sectionList) {
		foreach($sectionList as $section) {
			$this->sectionList[$section->getName()] = $section;
		}
	}

	public function get(string $name):?string {
		$env = getenv(str_replace(".", "_", $name));
		if($env) {
			return $env;
		}

		return $this->getSectionValue($name);
	}

	public function getSection(string $sectionName):?ConfigSection {
		return $this->sectionList[$sectionName] ?? null;
	}

	protected function getSectionValue(string $name):?string {
		$parts = explode(".", $name, 2);
		$section = $this->getSection($parts[0]);

		if(is_null($section)
		|| empty($parts[1])) {
			return null;
		}

		return $section->get($parts[1]);
	}

	/** @return array<string> */
	public function getSectionNames():array {
		$names = [];

		foreach($this->sectionList as $section) {
			$names []= $section->getName();
		}

		return $names;
	}

	/**
	 * Merge another Config into this instance immutably, returning a new Config.
	 * Existing values are preserved; only missing keys/sections are filled from the overriding config.
	 */
	public function withMerge(Config $configToOverride): self {
		// Start with current sections
		$mergedSectionList = $this->sectionList;

		foreach($configToOverride->getSectionNames() as $sectionName) {
			$overrideSection = $configToOverride->getSection($sectionName);
			if(isset($mergedSectionList[$sectionName])) {
				// Fill only missing keys from override into existing section
				foreach($overrideSection as $key => $value) {
					if(empty($mergedSectionList[$sectionName][$key])) {
						$mergedSectionList[$sectionName] = $mergedSectionList[$sectionName]->with($key, $value);
					}
				}
			}
			else {
				// Add whole section if it doesn't exist
				$mergedSectionList[$sectionName] = $overrideSection;
			}
		}

		return new self(...array_values($mergedSectionList));
	}

	/**
	 * @deprecated Use withMerge() for an immutable alternative. This method will be removed in a future major release.
	 */
	public function merge(Config $configToOverride):void {
		@trigger_error("Config::merge() is deprecated. Use Config::withMerge() for an immutable merge.", E_USER_DEPRECATED);
		$new = $this->withMerge($configToOverride);
		$this->sectionList = $new->sectionList;
	}
}
