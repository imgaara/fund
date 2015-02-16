<?php

require_once(dirname(__FILE__) . '/catalog.php');
require_once(dirname(__FILE__) . '/info.php');

date_default_timezone_set('UTC');

$catalog = Catalog::Get();
foreach ($catalog['funds'] as $fund_id) {
  if (is_readable(Info::GetFileName($fund_id))) {
    $info = Info::Get($fund_id);
    echo "{$info['id']}\t{$info['fee']}\t{$info['trade_schedule']}\t{$info['type']}\n";
  }
}
