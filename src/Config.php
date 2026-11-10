<?php
namespace GT\Config;

use GT\TypeSafeGetter\NullableTypeSafeGetter;
use GT\TypeSafeGetter\TypeSafeGetter;

class Config implements TypeSafeGetter {
	use NullableTypeSafeGetter {
		getBool as private getGenericBool;
	}

	private ?Environment $environment = null;

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

	/** @SuppressWarnings("PHPMD.BooleanGetMethodName") */
	public function getBool(string $name):?bool {
		if($name === "app.production") {
			return $this->isProduction();
		}

		return $this->getGenericBool($name);
	}

	public function isProduction():bool {
		$production = $this->isProductionConfigured();
		if($this->environment === null) {
			return $production ?? true;
		}

		$expected = $this->environment !== Environment::DEVELOPMENT;
		if($production !== null && $production !== $expected) {
			throw new EnvironmentConfigurationException(
				"app.production contradicts the selected "
				. $this->environment->value . " environment."
			);
		}

		return $expected;
	}

	public function isDevelopment():bool {
		return !$this->isProduction();
	}

	public function isStaging():bool {
		$this->isProduction();
		return $this->environment === Environment::STAGING;
	}

	public function withEnvironment(Environment $environment):self {
		$clone = clone $this;
		$clone->environment = $environment;
		$clone->isProduction();
		return $clone;
	}

	private function isProductionConfigured():?bool {
		$value = getenv("app_production");
		if($value === false) {
			$value = $this->getSectionValue("app.production");
		}

		if($value === null) {
			return null;
		}

		return match(strtolower(trim($value))) {
			"1", "true", "yes", "on" => true,
			"0", "false", "no", "off" => false,
			default => throw new EnvironmentConfigurationException(
				"app.production must be a valid boolean."
			),
		};
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
	 * Existing values are preserved.
	 * Only missing keys and sections are filled from the overriding config.
	 */
	public function withMerge(Config $configToOverride): self {
		// Start with current sections
		$mergedSectionList = $this->sectionList;

		foreach($configToOverride->getSectionNames() as $sectionName) {
			$overrideSection = $configToOverride->getSection($sectionName);
			if(isset($mergedSectionList[$sectionName])) {
				// Fill only missing keys from override into existing section
				foreach($overrideSection as $key => $value) {
					if(!$mergedSectionList[$sectionName]->contains($key)) {
						$mergedSectionList[$sectionName] = $mergedSectionList[$sectionName]->with($key, $value);
					}
				}
			}
			else {
				// Add whole section if it doesn't exist
				$mergedSectionList[$sectionName] = $overrideSection;
			}
		}

		$merged = clone $this;
		$merged->sectionList = $mergedSectionList;
		$merged->environment ??= $configToOverride->environment;
		$merged->isProduction();
		return $merged;
	}

	/**
	 * @deprecated Use withMerge() for an immutable alternative.
	 * This method will be removed in a future major release.
	 */
	public function merge(Config $configToOverride):void {
		trigger_error(
			"Config::merge() is deprecated. "
			. "Use Config::withMerge() for an immutable merge.",
			E_USER_DEPRECATED
		);
		$new = $this->withMerge($configToOverride);
		$this->sectionList = $new->sectionList;
		$this->environment = $new->environment;
	}
}
