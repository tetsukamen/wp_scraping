<?php
/**
 * The template for displaying single posts and pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */

get_header();

define("USER_AGENT_TEXT", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36");

require_once "simple_html_dom.php";

// APIを呼び出し、結果を受け取る処理（URLにアクセスしその結果を取得する処理）
// $url         ：APIの URI（アクセスする URL）
// $responseType：受け取る結果のタイプ（header、html、json）
function getApiDataCurl($url, $responseType = "html")
{
    if ($responseType == "header") {
        $option = [
        CURLOPT_RETURNTRANSFER => true,   // 文字列として返す
        CURLOPT_TIMEOUT        => 3000,   // タイムアウト時間
        CURLOPT_HEADER         => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_SSL_VERIFYPEER => false,  // サーバ証明書の検証をしない
    ];
    } else {
        $option = [
        CURLOPT_RETURNTRANSFER => true,   // 文字列として返す
        CURLOPT_TIMEOUT        => 3000,   // タイムアウト時間
        CURLOPT_SSL_VERIFYPEER => false,  // サーバ証明書の検証をしない
        CURLOPT_USERAGENT      => USER_AGENT_TEXT,  // UserAgentを指定
    ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, $option);

    $body     = curl_exec($ch);
    $info     = curl_getinfo($ch);
    $errorNo  = curl_errno($ch);
    $errorMsg = curl_error($ch);

    // 「CURLE_OK」以外はエラーなのでエラー情報を返す
    if ($errorNo !== CURLE_OK) {
        // 詳しくエラーハンドリングしたい場合はerrorNoで確認
        // タイムアウトの場合はCURLE_OPERATION_TIMEDOUT
        return $errorNo . " : " . $errorMsg;
    }

    // 200以外のステータスコードは失敗なのでそのステータスコードを返す
    if ($info['http_code'] !== 200) {
        return $info['http_code'];
    }

    // headerのみ取得
    if ($responseType == "header") {
        $responseArray = explode("\n", $body);                   // 行に分割
    $responseArray = array_map('trim', $responseArray);      // 各行にtrim()をかける
    $responseArray = array_filter($responseArray, 'strlen'); // 文字数が0の行を取り除く
    $responseArray = array_values($responseArray);           // キーを連番に振りなおす

    // HTMLの本体を取得
    } elseif ($responseType == "html") {
        $responseArray = $body;

    // JSONで取得した情報を配列に変換して取得
    } else {
        $responseArray = json_decode($body, true);               // JSON を配列に変換
    }

    return $responseArray;
}
?>

<main id="site-content" role="main">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php

$indcator_arr = [
    'US_NMI' => [
        'name' => 'アメリカ・ISM非製造業景気指数',
        'url' => 'https://fx.minkabu.jp/indicators/US-NMI'
    ],
    'US_MIN' => [
        'name' => 'アメリカ・FOMC議事録',
        'url' => 'https://fx.minkabu.jp/indicators/US-MIN'
    ],
    'US_NFP' => [
        'name' => 'アメリカ・雇用統計',
        'url' => 'https://fx.minkabu.jp/indicators/US-NFP'
    ],
    'EU_ECBR' => [
        'name' => 'ユーロ・ECB政策金利',
        'url' => 'https://fx.minkabu.jp/indicators/EU-ECBR'
    ],
    'US_FOMC' => [
        'name' => 'アメリカ・FRB政策金利',
        'url' => 'https://fx.minkabu.jp/indicators/US-FOMC'
    ],
    'US_GDPA' => [
        'name' => 'アメリカ・実質ＧＤＰ（速報値）',
        'url' => 'https://fx.minkabu.jp/indicators/US-GDPA'
    ],
    'US_GDPS' => [
        'name' => 'アメリカ・実質ＧＤＰ（改定値）',
        'url' => 'https://fx.minkabu.jp/indicators/US-GDPS'
    ]
];

?>

<table id="indicator">
    <thead>
        <th>指標名</th>
        <th>発表日時</th>
        <th>前回</th>
        <th>結果</th>
    </thead>
    <tbody>
        <?php
        foreach ($indcator_arr as $value):
            $url = $value['url'];
            $htmlSource = getApiDataCurl($url, "html");
            $html = str_get_html($htmlSource);
            // テキストを抽出
            $time = $html->find("time", 0)->plaintext;
            $prev = $html->find("dl[id^=js-indicator_announcement-]", 0)->find('div', 0)->find("dd", 2)->find("span", 1)->plaintext;
            $result = $html->find("dl[id^=js-indicator_announcement-]", 0)->find('div', 0)->find("dd", 1)->find("span", 1)->plaintext;
        ?>
        <tr>
            <td><?php print_r($value['name']) ?></td>
            <td><?php print_r($time) ?></td>
            <td><?php print_r($prev) ?></td>
            <td><?php print_r($result) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php endwhile; endif; ?>

</main><!-- #site-content -->

<?php get_template_part('template-parts/footer-menus-widgets'); ?>

<?php get_footer(); ?>