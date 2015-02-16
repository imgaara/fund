<?php

require_once(dirname(__FILE__) . '/../src/info.php');

class InfoTest extends PHPUnit_Framework_Testcase {
  public function testFetch() {
    $actual = Info::Fetch(dirname(__FILE__) . '/info.html');
    unset($actual['modified']);
    unset($actual['modified_str']);
    $expected = [
        'fund_name' => '大和住銀−日本株アルファ・カルテット（毎月分配型）',
        'fee' => 0,
        'category' => '国内株式-アクティブ・大型・一般',
        'compensation' => 0.01902,
        'reservation_fee' => 0.003,
        'refund_date' => '2019/04/04',
        'distribution' => '毎月',
        'id' => '22314144',
        'course' => '口数・金額',
        'settlement_schedule' => '約定日から4営業日後',
        'type' => '国内株式',
        'deadline' => '15:00',
        'trade_schedule' =>
            'ご注文日の翌営業日（国内・海外の休場により遅れる場合がございます）',
        'cancellation' => 'なし',
        'asset' => 53964000000,
        'policy' =>
            '新たな成長ステージに向かう日本経済に注目、日本の株式に投資します。' .
            '「高金利通貨戦略」を行うことで、為替取引によるプレミアム収益' .
            '（金利差相当分の収益）の確保を目指します。「株式カバードコール戦略」' .
            'および「通貨カバードコール戦略」を行うことで、オプションプレミアムの' .
            '確保を目指します。毎月の決算日に、原則として収益の分配を目指します。'];
    $this->assertEquals($expected, $actual);
  }
}
