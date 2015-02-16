<?php

require_once(dirname(__FILE__) . '/catalog.php');

date_default_timezone_set('UTC');

class Info {
  static public function GetDirectoryName($fund_id) {
    $fund_prefix = substr($fund_id, 0, 2);
    return dirname(__FILE__) . "/../data/$fund_prefix/$fund_id";
  }

  static public function GetFileName($fund_id) {
    return self::GetDirectoryName($fund_id) . '/info.json';
  }

  static public function Get($fund_id) {
    if (!is_readable(self::GetFileName($fund_id))) {
      fwrite(STDERR, 'No such file: ' . self::GetFileName($fund_id) . "\n");
      exit(1);
    }
    return json_decode(file_get_contents(self::GetFileName($fund_id)), TRUE);
  }

  static public function Fetch($url) {
    fwrite(STDERR, "Fetching info from $url...\n");
    $data = [];
    $html = mb_convert_encoding(
        file_get_contents($url), 'UTF-8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS');
    $html = preg_replace('%\\<!--.*--\\>%Usu', '', $html);
    $html = preg_replace('%<script(|\\s+[^>]*)>.*</script>%Usui', '', $html);
    $html = preg_replace('%\\s+%u', ' ', $html);
    $html = preg_replace('%<(div|p)(|\\s+[^>]*)>|</(div|p)>%iu', "\n", $html);
    $html = preg_replace('%<([\w]+)(?:|\\s+[^>]*)>|<(/[\w]+)>%iu',
                         '<\\1\\2>',
                         $html);
    $html = preg_replace('%\s*\n\s*%u', "\n", $html);
    $html = str_replace('&nbsp;', ' ', $html);
    if (!preg_match('%<h3>(.*)</h3>%', $html, $match)) {
      fwrite(STDERR, "Failed to fetch $url.");
      exit(1);
    }
    $data['fund_name'] = $match[1];
    preg_match_all('%<tr>(.*)</tr>%Usi', $html, $matches);
    foreach ($matches[1] as $match) {
      preg_match_all('%<(th|td)>(.*)</(th|td)>%Usi', $match, $submatches);
      $tags = array_map('strtolower', $submatches[1]);
      $values = array_map('trim', $submatches[2]);
      if (strpos($values[0], '口数：') !== FALSE) {
        if (strpos($values[0], 'なし')) {
          $data['fee'] = 0.0;
        } else if (preg_match('/(\d(?:|\.\d+))%/', $values[0], $value)) {
          $data['fee'] = floatval($value[1]) / 100;
        }
      }
      if ($tags[0] == 'th') {
        $info[$values[0]] = $values[1];
      }
      if ($tags[2] == 'th') {
        $info[$values[2]] = $values[3];
      }
    }
    foreach ([
        'SBIカテゴリ' => 'category',
        '信託報酬 (税込)/年' => 'compensation',
        '信託財産留保額' => 'reservation_fee',
        '償還日' => 'refund_date',
        '分配金' => 'distribution',
        '協会コード' => 'id',
        '取引コース' => 'course',
        '受渡日' => 'transaction_schedule',
        '商品分類' => 'type',
        '当社締切時間' => 'deadline',
        '約定日' => 'contract_schedule',
        '解約手数料 (税込)' => 'cancellation',
        '純資産' => 'asset',
        '運用方針' => 'policy',
        ] as $sbi_key => $internal_key) {
      if (isset($info[$sbi_key])) {
        $data[$internal_key] = $info[$sbi_key];
      }
    }
    if (isset($data['asset']) &&
        preg_match('%^(\d+(?:,\d+)+)百万円$%u', $data['asset'], $match)) {
      $data['asset'] = intval(str_replace(',', '', $match[1])) * 1000000;
    }
    foreach (['compensation', 'reservation_fee'] as $key) {
      if (isset($data[$key])) {
        if (preg_match('/(\d(?:|\.\d+))%/', $data[$key], $value)) {
          $data[$key] = floatval($value[1]) / 100;
        }
      }
    }
    $data['modified'] = time();
    $data['modified_str'] = gmdate('Y-m-d H:i:s', $data['modified']);
    ksort($data);
    return $data;
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
    if (!is_dir(self::GetDirectoryName($fund_id))) {
      mkdir(self::GetDirectoryName($fund_id), 0777, TRUE);
    }
    $data = self::Fetch(
        'https://www.sbisec.co.jp/ETGate/WPLETfiR001Control/' .
        'WPLETfiR001Idtl11/DefaultAID?getFlg=on&fund_sec_code=' .
        $sbi_fund_id);
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
    }
    foreach ($sbi_fund_ids as $sbi_fund_id) {
      self::Update($sbi_fund_id);
    }
  }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  Info::Main($argv);
}
