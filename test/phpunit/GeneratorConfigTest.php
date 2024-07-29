<?php
namespace Gt\Config\Test;

use Gt\Config\Generator;
use Gt\Config\InvalidArgumentException;

class GeneratorConfigTest extends ConfigTestCase {
	public function testGenerateEmptyArgs() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Not enough arguments supplied");
		new Generator([]);
	}

	public function testGenerateOnlyOneArgs() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Not enough arguments supplied");
		new Generator(["config-generate"]);
	}

	public function testGenerateOnlyTwoArgs() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Not enough arguments supplied");
		new Generator(["config-generate", "meow"]);
	}

	public function testGenerateDisallowedSuffix() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Invalid config suffix: meow");
		new Generator(["config-generate", "meow", "test"]);
	}

	public function testGenerateNoKVP() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Invalid key-value pair: test");
		new Generator(["config-generate", "dev", "test"]);
	}

	public function testGenerateNoSection() {
		self::expectException(InvalidArgumentException::class);
		self::expectExceptionMessage("Invalid key-value pair: testkey");
		$sut = new Generator(["config-generate", "dev", "testkey=testvalue"]);
	}

	public function testGenerate() {
		$sut = new Generator(["config-generate", "dev", "testsection.testkey=testvalue"]);
		$sut->generate();
		$filePath = $this->tmp . "/config.dev.ini";
		self::assertFileExists($filePath);
		$contents = file_get_contents($filePath);
		self::assertStringContainsString("[testsection]", $contents);
		self::assertStringContainsString("testkey = \"testvalue\"", $contents);
	}
}
