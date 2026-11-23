<?php
namespace GT\Config\Test;

use GT\Config\Config;
use GT\Config\ConfigSection;
use GT\Config\FileWriter;
use GT\Config\Test\Helper\Helper;

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

		$parsedData = parse_ini_file($tmpFilePath, true, INI_SCANNER_TYPED);
		foreach($sectionValues as $sectionName => $section) {
			foreach($section as $key => $value) {
				self::assertSame(
					$value,
					$parsedData[$sectionName][$key]
				);
			}
		}
	}

	public function testSelectiveQuotingPreservesValues():void {
		$values = [
			'host' => 'localhost',
			'path' => '/var/www/app',
			'words' => 'hello world',
			'url' => 'https://example.com:8080/path',
			'empty' => '',
			'spaces' => ' padded ',
			'comment' => 'before;after',
			'quotes' => 'say "hello"',
			'backslashes' => 'C:\\path\\',
			'environment' => '${HOME}',
			'newline' => "first\nsecond",
			'operators' => 'foo | bar & baz',
			'numeric' => '00123',
			'integerString' => '123',
			'floatString' => '1.5',
			'trueString' => 'true',
			'falseString' => 'false',
			'nullString' => 'null',
			'onString' => 'on',
			'offString' => 'off',
			'constant' => 'PHP_VERSION',
			'integer' => 123,
			'float' => 1.5,
			'wholeFloat' => 1.0,
			'enabled' => true,
			'disabled' => false,
			'nullValue' => null,
		];
		$config = new Config(new ConfigSection('example', $values));
		$path = Helper::getTmpDir('nested', 'output.ini');
		(new FileWriter($config))->writeIni($path);

		self::assertSame($values, parse_ini_file($path, true, INI_SCANNER_TYPED)['example']);
		$contents = file_get_contents($path);
		foreach(['host = localhost', 'path = /var/www/app', 'words = hello world',
			'url = https://example.com:8080/path'] as $line) {
			self::assertStringContainsString("$line\n", $contents);
		}
		self::assertStringContainsString('numeric = "00123"', $contents);
		self::assertStringContainsString('comment = "before;after"', $contents);
	}

	public function testExistingEntriesArePreserved():void {
		$path = Helper::getTmpDir('output.ini');
		mkdir(dirname($path), 0775, true);
		file_put_contents($path, "[existing]\nkeep = value\nchange = old\n"
			. "items[first] = one\nitems[second] = two\n\n[other]\nnumber = 42\n");
		$config = new Config(new ConfigSection('existing', ['change' => 'new']));
		(new FileWriter($config))->writeIni($path);

		self::assertSame([
			'existing' => [
				'keep' => 'value',
				'change' => 'new',
				'items' => ['first' => 'one', 'second' => 'two'],
			],
			'other' => ['number' => 42],
		], parse_ini_file($path, true, INI_SCANNER_TYPED));
	}

	public function testEmptyConfig():void {
		$path = Helper::getTmpDir('output.ini');
		(new FileWriter(new Config()))->writeIni($path);
		self::assertSame([], parse_ini_file($path, true, INI_SCANNER_TYPED));
	}
}
