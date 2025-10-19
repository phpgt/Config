<?php
namespace Gt\Config\Test;

use Gt\Config\Test\Helper\Helper;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class ConfigTestCase extends PHPUnitTestCase {
	protected $tmp;

	protected function setUp():void {
		Helper::removeTmpDir();
		$this->tmp = Helper::getTmpDir();
		mkdir($this->tmp, 0775, true);
		chdir($this->tmp);
	}

	protected function tearDown():void {
		Helper::removeTmpDir();
	}
}
