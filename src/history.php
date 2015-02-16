<?php

require_once(dirname(__FILE__) . '/catalog.php');

date_default_timezone_set('UTC');

class History {
  static public function GetDirectoryName($fund_id) {
    $fund_id = substr($fund_id, -8);
    $fund_prefix = substr($fund_id, 0, 2);
    return dirname(__FILE__) . "/../data/$fund_prefix/$fund_id";
  }

  static public function GetFileName($fund_id) {
    return self::GetDirectoryName($fund_id) . '/history.json';
  }

  static public function Get($fund_id) {
    if (!is_readable(self::GetFileName($fund_id))) {
      fwrite(STDERR, 'No such file: ' . self::GetFileName($fund_id) . "\n");
      exit(1);
    }
    $data = json_decode(file_get_contents(self::GetFileName($fund_id)), TRUE);
    $prices = [];
    foreach (glob(self::GetDirectoryName($fund_id) . '/prices-*.json')
             as $file) {
      $prices = array_merge($prices,
                            json_decode(file_get_contents($file), TRUE));
    }
    $data['prices'] = $prices;
    if (is_readable(self::GetDirectoryName($fund_id) . '/distributions.json')) {
      $data['distributions'] = json_decode(file_get_contents(
          self::GetDirectoryName($fund_id) . '/distributions.json'), TRUE);
    }
    return $data;
  }

  static public function Put($fund_id, $data) {
    if (isset($data['prices'])) {
      foreach (glob(self::GetDirectoryName($fund_id) . '/prices-*.json')
               as $file) {
        unlink($file);
      }
      $prices = [];
      foreach ($data['prices'] as $date => $price) {
        $prices[substr($date, 0, 7)][$date] = $price;
      }
      ksort($prices);
      foreach ($prices as $month => $monthly_prices) {
        ksort($monthly_prices);
        file_put_contents(
            self::GetDirectoryName($fund_id) . "/prices-$month.json",
            json_encode($monthly_prices, JSON_PRETTY_PRINT |
                                         JSON_UNESCAPED_UNICODE));
      }
    }
    if (isset($data['distributions'])) {
      ksort($data['distributions']);
      file_put_contents(
          self::GetDirectoryName($fund_id) . '/distributions.json',
          json_encode($data['distributions'], JSON_PRETTY_PRINT |
                                              JSON_UNESCAPED_UNICODE));
    }
  }

  static public function FetchPrices($url) {
    fwrite(STDERR, "Fetching prices from $url...\n");
    $parameters = [
        'in_term_from_yyyy' => '2001',
        'in_term_from_mm' => '01',
        'in_term_from_dd' => '01',
        'in_term_to_yyyy' => date('Y'),
        'in_term_to_mm' => date('m'),
        'in_term_to_dd' => date('d')];
    $content = http_build_query($parameters, '', '&');
    $header = [
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($content)];
    $source = mb_convert_encoding(file_get_contents(
        $url, FALSE,
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $header),
                'content' => $content]])), 'UTF-8', 'UTF-8,SJIS');
    $source = trim(str_replace("\r", '', $source));
    if ($source == '' || strpos($source, '<html') !== FALSE) {
      fwrite(STDERR, "Failed to fetch $url.\n");
      exit(1);
    }
    $data = [];
    foreach (explode("\n", $source) as $line) {
      $data[] = str_getcsv($line);
    }
    if ($data[0][0] != '基準価額一覧' &&
        $data[0][0] != '分配金実績') {
      fwrite(STDERR, "Unexpected data.\n");
      exit(1);
    }
    $prices = [];
    for ($i = 8; $i < count($data); $i++) {
      $prices[str_replace('/', '-', $data[$i][0])] = intval($data[$i][1]);
    }
    ksort($prices);
    return $prices;
  }

  static public function Update($sbi_fund_id) {
    fwrite(STDERR, "Updateing $sbi_fund_id...\n");
    $fund_id = substr($sbi_fund_id, -8);
    if (is_readable(self::GetFileName($fund_id))) {
      $data = self::Get($fund_id);
      if ($data['modified'] > time() - 24 * 60 * 60) {
        fwrite(STDERR, "Cache is still alive.\n");
        return;
      }
    }
    $data['prices'] = self::FetchPrices(
        "https://site0.sbisec.co.jp/marble/fund/history/" .
        "standardprice/standardPriceHistoryCsvAction.do?" .
        "fund_sec_code={$fund_id}");
    $data['distributions'] = self::FetchPrices(
        "https://site0.sbisec.co.jp/marble/fund/history/" .
        "distribution/distributionHistoryCsvAction.do?" .
        "fund_sec_code={$fund_id}");
    self::Put($fund_id, $data);
    $data = [];
    $data['modified'] = time();
    $data['modified_str'] = gmdate('Y-m-d H:i:s', $data['modified']);
    ksort($data);
    file_put_contents(
        self::GetFileName($fund_id),
        json_encode($data, JSON_PRETTY_PRINT |
                           JSON_UNESCAPED_UNICODE));
    fwrite(STDERR, "Finished.\n");
  }

  static public function Main($argv) {
    if (count($argv) > 1) {
      array_shift($argv);
      $sbi_fund_ids = $argv;
    } else {
      $catalog = Catalog::Get();
      $sbi_fund_ids = $catalog['funds'];
      shuffle($sbi_fund_ids);
    }
    foreach ($sbi_fund_ids as $sbi_fund_id) {
      self::Update($sbi_fund_id);
    }
  }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  History::Main($argv);
}
