<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceStatic.class.php';

$t = new lime_test(9, new lime_output_color());

$t->diag('::getCpuLoad()');

  $cpu = IceSystemStats::getCpuLoad();

  if (count($cpu) == 4)
  {
    $t->is(array_key_exists('user', $cpu), true);
    $t->is(array_key_exists('nice', $cpu), true);
    $t->is(array_key_exists('system', $cpu), true);
    $t->is(array_key_exists('idle', $cpu), true);
  }
  else
  {
    $t->fail('The CPU stats array must be exactly 4 elements');
  }

$t->diag('::getLoadAvg()');

  $load = IceSystemStats::getLoadAvg();

  if (count($load) == 3)
  {
    $t->is(array_key_exists('1-minute', $load), true);
    $t->is(array_key_exists('5-minute', $load), true);
    $t->is(array_key_exists('15-minute', $load), true);
  }
  else
  {
    $t->fail('The LOAD stats array must be exaclty 3 elements');
  }

$t->diag('::getMemory()');

  $memory = IceSystemStats::getMemory();

  if (count($memory) == 2)
  {
    $t->is(array_key_exists('current', $memory), true);
    $t->is(array_key_exists('peak', $memory), true);
  }
  else
  {
    $t->fail('The MEMORY stats array must be exaclty 2 elements');
  }