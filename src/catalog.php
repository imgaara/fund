<?php

date_default_timezone_set('UTC');

class Catalog {
  static public function GetFileName() {
    return dirname(__FILE__) . '/../data/catalog.json';
  }

  static public function Get() {
    if (!is_readable(self::GetFileName())) {
      fwrite(STDERR, 'No such file: ' . self::GetFileName() . "\n");
      exit(1);
    }
    return json_decode(file_get_contents(self::GetFileName()), TRUE);
  }

  static public function Fetch($url) {
    fwrite(STDERR, "Fetching catalog from $url...\n");
    $data = [];
    $html = file_get_contents($url);
    preg_match_all('%&fund_sec_code=([\w\d]+)%', $html, $matches);
    $funds = $matches[1];
    sort($funds);
    $data['funds'] = $funds;
    $data['modified'] = time();
    $data['modified_str'] = gmdate('Y-m-d H:i:s', $data['modified']);
    ksort($data);
    return $data;
  }

  static public function Main() {
    if (is_readable(self::GetFileName())) {
      $data = self::Get();
      if ($data['modified'] > time() - 24 * 60 * 60) {
        fwrite(STDERR, "Cache is still alive.\n");
        return;
      }
    }
    $data = self::Fetch(
        'https://site1.sbisec.co.jp/ETGate/WPLETmgR001Control?' .
        'getFlg=on&burl=search_fund&cat1=fund&cat2=lineup&' .
        'dir=edeliv&file=fund_edeliv_01.html');
    file_put_contents(
        self::GetFileName(),
        json_encode($data, JSON_PRETTY_PRINT |
                           JSON_UNESCAPED_UNICODE));
    fwrite(STDERR, "Finished.\n");
  }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  Catalog::Main();
}
