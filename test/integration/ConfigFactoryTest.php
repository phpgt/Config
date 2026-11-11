<?php
namespace GT\Config\Test\Integration;

use GT\Config\ConfigFactory;
use GT\Config\EnvironmentConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigFactoryTest extends TestCase {
	private string $directory;
	private string|false $previousProduction;

	protected function setUp():void {
		$this->directory = sys_get_temp_dir() . "/config-environment-" . uniqid();
		mkdir($this->directory);
		$this->previousProduction = getenv("app_production");
		putenv("app_production");
	}

	protected function tearDown():void {
		putenv($this->previousProduction === false ? "app_production" : "app_production=$this->previousProduction");
		foreach(glob("$this->directory/*") as $file) {
			unlink($file);
		}
		rmdir($this->directory);
	}

	#[DataProvider("environments")]
	public function testCreateForProject_selectsEnvironmentAndPreservesOverrides(string $suffix, bool $production, bool $staging):void {
		file_put_contents("$this->directory/config.default.ini", "[database]\nhost=default\npassword=secret-default\n");
		file_put_contents("$this->directory/config.ini", "[database]\nhost=base\n");
		file_put_contents("$this->directory/config.$suffix.ini", "[database]\nhost=environment\n");

		$sut = ConfigFactory::createForProject($this->directory);

		self::assertSame($production, $sut->isProduction());
		self::assertSame(!$production, $sut->isDevelopment());
		self::assertSame($staging, $sut->isStaging());
		self::assertSame($production, $sut->getBool("app.production"));
		self::assertSame("environment", $sut->get("database.host"));
		self::assertSame("secret-default", $sut->get("database.password"));
	}

	public static function environments():iterable {
		yield ["dev", false, false];
		yield ["development", false, false];
		yield ["stag", true, true];
		yield ["staging", true, true];
		yield ["prod", true, false];
		yield ["production", true, false];
	}

	#[DataProvider("conflictingFiles")]
	public function testCreateForProject_rejectsEveryPairBeforeParsing(string $first, string $second):void {
		file_put_contents("$this->directory/config.$first.ini", "invalid INI [ secret-value");
		file_put_contents("$this->directory/config.$second.ini", "invalid INI [ another-secret");

		$this->expectException(EnvironmentConfigurationException::class);
		$this->expectExceptionMessageMatches(
			"/" . preg_quote("config.$first.ini", "/") . ".*"
			. preg_quote("config.$second.ini", "/") . "/"
		);
		ConfigFactory::createForProject($this->directory);
	}

	public static function conflictingFiles():iterable {
		$names = ["dev", "development", "stag", "staging", "prod", "production"];
		foreach($names as $index => $first) {
			foreach(array_slice($names, $index + 1) as $second) {
				yield "$first and $second" => [$first, $second];
			}
		}
	}

	#[DataProvider("flagConflicts")]
	public function testCreateForProject_rejectsContradictoryFlags(string $suffix, string $flag):void {
		file_put_contents("$this->directory/config.$suffix.ini", "[app]\nproduction=$flag\n");

		$this->expectException(EnvironmentConfigurationException::class);
		$this->expectExceptionMessage("contradicts");
		ConfigFactory::createForProject($this->directory);
	}

	public static function flagConflicts():iterable {
		yield ["development", "true"];
		yield ["staging", "false"];
		yield ["production", "false"];
	}

	public function testCreateForProject_environmentVariableCannotDisableStagingSafeguards():void {
		file_put_contents("$this->directory/config.staging.ini", "");
		putenv("app_production=false");

		$this->expectException(EnvironmentConfigurationException::class);
		ConfigFactory::createForProject($this->directory);
	}

	public function testCreateForProject_externalDefaultsRemainFallbackValues():void {
		file_put_contents("$this->directory/framework.ini", "[app]\nproduction=false\n[database]\nhost=framework\nport=3306\n");
		file_put_contents("$this->directory/config.ini", "[database]\nhost=project\n");

		$sut = ConfigFactory::createForProject($this->directory, "$this->directory/framework.ini");

		self::assertTrue($sut->isDevelopment());
		self::assertSame("project", $sut->get("database.host"));
		self::assertSame(3306, $sut->getInt("database.port"));
	}

	public function testCreateForProject_missingExternalDefaultsStillUsesProduction():void {
		$sut = ConfigFactory::createForProject($this->directory, "$this->directory/missing.ini");
		self::assertTrue($sut->isProduction());
	}

	public function testCreateForProject_noFilesDefaultsToProduction():void {
		$sut = ConfigFactory::createForProject($this->directory);
		self::assertTrue($sut->isProduction());
		self::assertFalse($sut->isDevelopment());
		self::assertFalse($sut->isStaging());
	}

	public function testCreateForProject_explicitFalseSelectsDevelopmentWithDeployOverrides():void {
		file_put_contents("$this->directory/config.ini", "[app]\nproduction=false\n[database]\nhost=base\n");
		file_put_contents("$this->directory/config.deploy.ini", "[database]\nhost=deployed\n");

		$sut = ConfigFactory::createForProject($this->directory);
		self::assertTrue($sut->isDevelopment());
		self::assertFalse($sut->getBool("app.production"));
		self::assertSame("deployed", $sut->get("database.host"));
	}
}
