<?php

require_once(dirname(__FILE__) . '/../src/catalog.php');

class CatalogTest extends PHPUnit_Framework_Testcase {
  public function testFetch() {
    $actual = Catalog::Fetch(dirname(__FILE__) . '/catalog.html');
    unset($actual['modified']);
    unset($actual['modified_str']);
    $expected = [
        'funds' => [
            '101311002',
            '101311007']];
    $this->assertEquals($expected, $actual);
  }
}
