<?php
namespace Gt\Config\Test;

use Gt\Config\Config;
use Gt\Config\ConfigSection;
use Gt\Config\FileWriter;
use Gt\Config\Test\Helper\Helper;

class FileWriterConfigTest extends ConfigTestCase {
	public function testWrite():void {
		$sectionValues = [
			"one" => [
				"firstKeyOfOne" => "value",
				"secondKeyOfOne" => "another-value",
			],
			"two" => [
				"firstKeyOfTwo" => 12345,
				"secondKeyOfTwo" => true,
			],
			"three" => [
				"fk-of-3" => "this?has!weird charac~'#ters",
				"sk-of-3" => false,
			]
		];
		$sectionOne = new ConfigSection("one", $sectionValues["one"]);
		$sectionTwo = new ConfigSection("two", $sectionValues["two"]);
		$sectionThree = new ConfigSection("three", $sectionValues["three"]);

		$config = self::createMock(Config::class);
		$config->method("getSectionNames")
			->willReturn(array_keys($sectionValues));
		$config->method("getSection")
			->willReturn(
				$sectionOne,
				$sectionTwo,
				$sectionThree
			);

		$writer = new FileWriter($config);
		$tmpFilePath = Helper::getTmpDir("output.ini");
		$writer->writeIni($tmpFilePath);

		$parsedData = parse_ini_file($tmpFilePath, true);
		foreach($sectionValues as $sectionName => $section) {
			foreach($section as $key => $value) {
				self::assertEquals(
					$value,
					$parsedData[$sectionName][$key]
				);
			}
		}
	}
}
