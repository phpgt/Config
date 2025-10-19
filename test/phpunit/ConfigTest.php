<?php
namespace Gt\Config\Test;

use DateTimeImmutable;
use Gt\Config\Config;
use Gt\Config\ConfigSection;

class ConfigTest extends ConfigTestCase {
	public function testNotPresentByDefault() {
		$config = new Config();
		$this->assertNull($config->get(uniqid()));
	}

	public function testEnvVarPresentWithEnv() {
		putenv("my-env-var=example");

		$config = new Config();
		$this->assertNotNull($config->get("my-env-var"));
	}

	public function testGet() {
		$key = uniqid();
		$value = uniqid();
		putenv("$key=$value");

		$config = new Config();
		self::assertEquals($value, $config->get($key));
	}

	public function testLoadSection() {
		$section = self::createMock(ConfigSection::class);
		$section->method("getName")
			->willReturn("test");
		$section->method("get")
			->willReturn("value123");

		$config = new Config($section);
		self::assertEquals("value123", $config->get("test.example"));
	}

	public function testEnvOverride() {
		putenv("app_namespace=ExampleAppChanged");
		putenv("app_nothing=Something");

		$section = self::createMock(ConfigSection::class);
		$section->method("getName")
			->willReturn("app");
		$section->method("get")
			->willReturn("exampleApp");

		$config = new Config($section);
		self::assertEquals("ExampleAppChanged", $config->get("app.namespace"));
		self::assertEquals("Something", $config->get("app.nothing"));
	}

	public function testTypeSafeGetter() {
		putenv("my-int=123");
		putenv("my-float=123.456");
		putenv("my-birthday=576264065");
		$sut = new Config();
		self::assertSame(123, $sut->getInt("my-int"));
		self::assertSame(123, $sut->getInt("my-float"));
		self::assertSame(123.456, $sut->getFloat("my-float"));
		$dateTime = $sut->getDateTime("my-birthday");
		self::assertInstanceOf(DateTimeImmutable::class, $dateTime);
		self::assertEquals("April 5th 1988", $dateTime->format("F jS Y"));
		self::assertNull($sut->getFloat("nothing-here"));
	}

	public function testWithMergeReturnsNewAndDoesNotMutateOriginal():void {
		$original = new Config(
			new ConfigSection("app", [
				"namespace" => "ExampleAppOriginal",
			]),
			new ConfigSection("db", [
				"host" => "localhost",
			])
		);

		$override = new Config(
			new ConfigSection("app", [
				"namespace" => "Override",
				"extra" => "value",
			]),
			new ConfigSection("cache", [
				"enabled" => "1",
			])
		);

		$merged = $original->withMerge($override);

		// Ensure new instance returned
		self::assertNotSame($original, $merged);

		// Original remains unchanged (use section access to avoid env var interference)
		self::assertSame("ExampleAppOriginal", $original->getSection("app")->get("namespace"));
		self::assertNull($original->getSection("app")->get("extra"));
		self::assertNull($original->getSection("cache"));

		// Merged config has expected values
		self::assertSame("ExampleAppOriginal", $merged->getSection("app")->get("namespace")); // existing preserved
		self::assertSame("value", $merged->getSection("app")->get("extra")); // new key added
		self::assertSame("1", $merged->getSection("cache")->get("enabled")); // new section added
	}

	public function testMergeEmitsDeprecationAndMutates():void {
		$original = new Config(
			new ConfigSection("app", [
				"namespace" => "ExampleAppOriginal",
			])
		);
		$override = new Config(
			new ConfigSection("app", [
				"extra" => "value",
			])
		);

		$deprecationCount = 0;
		set_error_handler(function(int $errno, string $errstr) use (&$deprecationCount) {
			if($errno === E_USER_DEPRECATED) {
				$deprecationCount++;
			}
			return true; // prevent PHPUnit from handling
		});

		try {
			$original->merge($override);
		}
		finally {
			restore_error_handler();
		}

		self::assertSame(1, $deprecationCount);
		self::assertSame("value", $original->getSection("app")->get("extra")); // mutated
	}
}
