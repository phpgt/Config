<?php
namespace Gt\Config\Test;

use Gt\Config\ConfigFactory;
use Gt\Config\Test\Helper\Helper;

class ConfigFactoryTest extends ConfigTestCase {
	public function testCreateForProject():void {
		$filePath = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.ini",
		]);
		$filePathDefault = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.default.ini",
		]);
		$filePathDev = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.dev.ini",
		]);
		$filePathProduction = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.production.ini",
		]);

		file_put_contents($filePathDefault, Helper::INI_DEFAULT);
		file_put_contents($filePath, Helper::INI_SIMPLE);
		file_put_contents($filePathDev, Helper::INI_OVERRIDE_DEV);
		file_put_contents($filePathProduction, Helper::INI_OVERRIDE_PROD);

		$config = ConfigFactory::createForProject($this->tmp);
		self::assertEquals("ExampleApp", $config->get("app.namespace"));
		self::assertEquals("dev789override", $config->get("block1.value.nested"));
		self::assertEquals("this appears by default", $config->get("block1.value.existsByDefault"));
		self::assertEquals("my.production.database", $config->get("database.host"));
		self::assertEquals("example", $config->get("database.schema"));
	}

	public function testCreateFromPathName():void {
		$filePath = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.ini",
		]);

		file_put_contents($filePath, Helper::INI_SIMPLE);

		$config = ConfigFactory::createFromPathName($filePath);

		$sectionNames = $config->getSectionNames();
		self::assertContains("app", $sectionNames);
		self::assertContains("block1", $sectionNames);
		self::assertContains("database", $sectionNames);
		self::assertNotContains("extra", $sectionNames);
	}

	public function testCreateForProjectPreservesFalseyOverrides():void {
		$filePath = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.ini",
		]);
		$filePathDefault = implode(DIRECTORY_SEPARATOR, [
			$this->tmp,
			"config.default.ini",
		]);

		file_put_contents($filePathDefault, implode(PHP_EOL, [
			"[session]",
			"use_cookies=true",
			"max_age=60",
			'label="default"',
			"",
		]));
		file_put_contents($filePath, implode(PHP_EOL, [
			"[session]",
			"use_cookies=false",
			"max_age=0",
			'label=""',
			"",
		]));

		$config = ConfigFactory::createForProject($this->tmp);
		self::assertFalse($config->getBool("session.use_cookies"));
		self::assertSame(0, $config->getInt("session.max_age"));
		self::assertSame("", $config->getString("session.label"));
	}
}
