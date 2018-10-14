<?php

function getCurrency() {
    $currency = config('base_currency', 'USD');
    if (isset($_COOKIE['base_currency'])) {
        $currency = $_COOKIE['base_currency'];
        return $currency;
    }
    if (app()->countryCurrency) $currency = app()->countryCurrency;
    return $currency;
}

function getCurrencySymbol() {
    if (app()->currencySymbol) return app()->currencySymbol;
    $currency = getCurrency();
    $c = get_coin_fiat_currency_detail($currency);
    if ($c) return $c['symbol'];
    $query = db()->query("SELECT symbol FROM currencies WHERE code=?", $currency);
    $symbol =  $query->fetch(PDO::FETCH_ASSOC)['symbol'];
    app()->currencySymbol = $symbol;
    return $symbol;
}

function find_currency($code) {
    $c = get_coin_fiat_currency_detail($code);
    if ($c) return $c;
    $query = db()->query("SELECT * FROM currencies WHERE code=?", $code);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function getCurrencyDetail() {
    if (app()->currencyDetail) return app()->currencyDetail;
    $currency = getCurrency();
    $c = get_coin_fiat_currency_detail($currency);
    if ($c) return $c;
    $query = db()->query("SELECT * FROM currencies WHERE code=?", $currency);
    $detail =  $query->fetch(PDO::FETCH_ASSOC);
    $detail['rate'] = (!$detail['rate']) ? 1 : $detail['rate'];
    app()->currencyDetail = $detail;
    return $detail;
}

function getAllSymbolCodes() {
    $query = db()->query("SELECT symbol FROM coins");
    $codes = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $codes[] = $fetch['symbol'];
    }
    return $codes;
}

function getAllCurrencies($main = false){
    $query = db()->query("SELECT * FROM currencies");
    $currencies = $query->fetchAll(PDO::FETCH_ASSOC);
    if(!$main){
        $currencies = array_merge(get_coin_currencies(), $currencies);
    }
    return $currencies;
}

function get_coin_currencies() {
    if (isset(app()->fiatCoins)) return app()->fiatCoins;
    $coins = explode(',', config('coin-fiat-currency', 'BTC,ETH,LTC'));
    $result = array();
    //return $result;
    foreach($coins as $coin) {
        $dcoin = get_coin($coin);
        $detail = array(
            'name' => $coin,
            'code' => $coin,
            'rate' => 1,
            'symbol' => ''
        );
        if ($coin == 'BTC') $detail['symbol']  = 'Ƀ';
        if ($coin == 'ETH') $detail['symbol']  = 'Ξ';
        if ($coin == 'LTC') $detail['symbol']  = 'Ł';
        $result[] = $detail;
    }
    app()->fiatCoins = $result;
    return $result;
}

function get_coin_fiat_currency_detail($currency) {
    foreach(get_coin_currencies() as $c) {
        if ($c['name'] == $currency) return $c;
    }
    return false;
}

function is_fiat_currency_coin() {
    $currency = getCurrency();
    $c = get_coin_fiat_currency_detail($currency);
    if ($c) return true;
    return false;
}


function update_coins_lists() {

    if (!config('auto-download-coin', true)) return false;
    $request = request_url(app()->COINS_LIST_URL);

    foreach($request['Data'] as $coin) {

        $query = db()->query("SELECT id FROM coins WHERE symbol=? ", $coin['Symbol']);
        if ($query->rowCount() < 1) {
            $time = time();
            $logo = ($coin['ImageUrl']) ? 'https://www.cryptocompare.com/'.$coin['ImageUrl'] : '';
            try{
                $supply = $coin['TotalCoinSupply'];
                $supply = ($supply == 'N/A') ? 0 : $supply;
                db()->query("INSERT INTO coins (last_time,symbol,name,logo,logo_small,proof_type,total_supply)
            VALUES(?,?,?,?,?,?,?)", $time,$coin['Symbol'],$coin['CoinName'], $logo, $logo,$coin['ProofType'], $supply);
            } catch(Exception $e) {
                //exit($e->getMessage());
            }
        }
    }
    //exit('am here');
}

function update_rates_data() {

    if (config('rate_app_id', '')) {
        @mkdir(path('files/uploads/'), 0777, true);
        $file = @fopen(path('files/uploads/rates.txt'), 'x+');
        if ($file) fclose($file);;
        $content = file_get_contents(path('files/uploads/rates.txt'));
        if (!$content or $content < (time() - 3600)) {
            $appID = config('rate_app_id', '');
            $currency = config('base_currency','USD');
            $url = sprintf(app()->CURRENCIES_MARKET_DATA_URL, $appID, $currency);
            try {
                $request = request_url($url);
                if ($request and isset($request['rates'])) {
                    foreach($request['rates'] as $code => $rate) {
                        db()->query("UPDATE currencies SET rate=? WHERE code=?", $rate, $code);
                    }
                }
                file_put_contents(path('files/uploads/rates.txt'), time());
            } catch(Exception $e) {}
        }

    }
}

function update_coins_data() {
    $currencies = getAllSymbolCodes();
    $currency = config('base_currency','USD');
    foreach(array_chunk($currencies, 60) as $chunk_currencies) {
        $url = sprintf(app()->COINS_MARKET_DATA_URL, implode(',',$chunk_currencies), $currency);
        $request = request_url($url);
        if ($request and isset($request['RAW'])) {
            foreach($request['RAW'] as $symbol => $coin) {
                $details = $coin[$currency];
                try{
                    db()->query("UPDATE coins SET change_value=?,change_ptc=?,open=?,low=?,high=?,supply=?,market_cap=?,volume=?,volume_ccy=?,price=? WHERE symbol=?",
                        $details['CHANGE24HOUR'],$details['CHANGEPCT24HOUR'],$details['OPEN24HOUR'],$details['LOW24HOUR'],$details['HIGH24HOUR'],
                        $details['SUPPLY'], $details['MKTCAP'],$details['VOLUME24HOUR'],$details['VOLUME24HOURTO'],$details['PRICE'], $symbol);
                    update_coin_performances($symbol, $details['PRICE']);
                } catch(Exception $e) {}
            }
        }

    }
    return true;
}

function update_coin_performances($symbol, $price) {

    if(!file_exists(path('files/history/coins/'.$symbol.'.txt'))) {
        @mkdir(path('files/history/coins/'), 0777, true);
        $file = @fopen(path('files/history/coins/'.$symbol.'.txt'), 'x+');
        fclose($file);
    }

    $content = file_get_contents(path('files/history/coins/'.$symbol.'.txt'));
    $content .= ($content) ? ','.$price : $price;
    $explode = explode(',', $content);

    if (count($explode) > 288) {
        array_shift($explode);
    };
    $content = implode(',', $explode);
    //exit($content.$symbol);
    return file_put_contents(path('files/history/coins/'.$symbol.'.txt'), $content);
}

function get_coin_line_data($symbol) {
    $file = path('files/history/coins/'.$symbol.'.txt');
    if (!file_exists($file)) return 0;
    $explode = explode(',', file_get_contents($file));
    $result = array();
    $currency = getCurrencyDetail();
    $rate = $currency['rate'];

    foreach($explode as $price) {

        $dprice = $price * $rate;

        $result[] = number_format($dprice, config('decimal-point', 3), '.', '');
    }
    return implode(',', $result);
}

function get_coins($order = 'market_cap', $orderType = 'ASC',  $limit = 100, $offset = 0) {
    $hiddens = get_sql_hidden_coins();
    $sql = "SELECT * FROM coins WHERE symbol NOT IN ($hiddens) ";

    $sql .= " ORDER BY $order $orderType LIMIT $limit OFFSET $offset";

    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}
function get_hidden_coins() {
    return explode(',', config('hide-coins', 'WBTC,ARENA,AMIS,BITCNY,XUC,VERI,ABC'));
}

function get_sql_hidden_coins() {
    $result = "'twlo'";
    $hidden = get_hidden_coins();
    foreach($hidden as $hide) {
        $result .= ",'$hide'";
    }
    return $result;
}
function search_coins($term) {
    $hiddens = get_sql_hidden_coins();
    $query = db()->query("SELECT * FROM coins WHERE (symbol LIKE '%$term%' OR name LIKE '%$term%') AND symbol NOT IN ($hiddens) LIMIT 50");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_featured_coins() {
    $query = db()->query("SELECT * FROM coins WHERE featured=?", 1);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}
function get_all_coins() {
    $query = db()->query("SELECT id,name,symbol FROM coins");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_coins_list($offset, $term) {
    $hiddens = get_sql_hidden_coins();
    $sql = "SELECT * FROM coins WHERE symbol NOT IN ($hiddens) ";
    if ($term) {
        $sql .= " AND (name LIKE '%$term%' OR symbol LIKE '%$term%') ";
    }


    $sql .= " ORDER BY price DESC LIMIT 50 OFFSET $offset ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_coin($coin) {
    $query = db()->query("SELECT * FROM coins WHERE symbol=? OR id=? ", $coin, $coin);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function get_coin_stats() {
    $currencyRate = getCurrencyDetail();
    $displayCurrencyRate = $currencyRate['rate'];
    $hiddens = get_sql_hidden_coins();
    $result = db()->query("SELECT count(symbol) AS total_coins, sum(volume * price) AS total_volume, sum(market_cap) AS total_market_cap FROM coins WHERE active = 1 AND symbol NOT IN ($hiddens)")->fetch(PDO::FETCH_ASSOC);
    return [
        'total_coins' => $result['total_coins'],
        'total_volume' => $result['total_volume'] * $displayCurrencyRate,
        'total_market_cap' => $result['total_market_cap'] * $displayCurrencyRate,
    ];
}

function get_coin_market_share($coin) {
    $stats = get_coin_stats();
    return round((100 * $coin['market_cap']) / $stats['total_market_cap'], 2);
}

function format_coin_price($price, $symbol = true, $format = true, $decimal = null) {
    $currency = getCurrencyDetail();
    $price = $price * $currency['rate'];
    $decimal = ($decimal) ? 0 : config('decimal-point', 3);
    if ($format) $price = formatNumber($price, $decimal);
    $out = ($symbol) ? getCurrencySymbol() : '';
    return $out.$price;
}



function get_coin_history($symbol, $type = 'histoday', $maxDataPoints = 2000, $aggregatePoints = 1, $allData = FALSE) {
    $currency = getCurrencyDetail();
    $rate = $currency['rate'];
    $requestUrl = sprintf(app()->COINS_HISTORICAL_MARKET_DATA_URL, $type, $symbol, config('base_currency','USD'), $maxDataPoints, $aggregatePoints, ($allData?'true':'false'));
    $response = request_url($requestUrl);
    $result = array(

    );
    if ($response and isset($response['Data'])) {
        foreach($response['Data'] as $item) {
            $result[] = [
                'date' => $item['time'] * 1000,
                'date_fmt' => timeStamp('@'.$item['time'], NULL, NULL, NULL, 'Y-m-d'),
                'value' => floatval($item['close']) * $rate,
                'value_fmt' => formatNumber(floatval($item['close']) * $rate, 2),
                'volume' => floatval($item['volumeto']) * $rate,
                'volume_fmt' => formatNumber(floatval($item['volumeto']) * $rate),
            ];
        }
    }
    return $result;
}

function count_all_coins() {
    $query = db()->query("SELECT id FROM coins");
    return $query->rowCount();
}

function get_risers() {
    $hiddens = get_sql_hidden_coins();
    $query = db()->query("SELECT symbol, name, change_ptc FROM coins WHERE active = 1 AND change_ptc IS NOT NULL AND symbol NOT IN ($hiddens) ORDER BY change_ptc DESC LIMIT 10");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_fallers() {
    $hiddens = get_sql_hidden_coins();
    $query = db()->query("SELECT symbol, name, change_ptc FROM coins WHERE active = 1 AND change_ptc IS NOT NULL AND symbol NOT IN ($hiddens) ORDER BY change_ptc ASC LIMIT 10");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function coin_link($coin, $slug = '') {
    return url(config('coin-link', 'coin').'/'.strtolower($coin['symbol']).'/'.$slug);
}
function get_coin_forums($type) {
    $limit = 50;
    $offset = input('offset', 0);
    try{
        $query = db()->query("SELECT DISTINCT(coin_id) as coin_id FROM posts WHERE post_type=? LIMIT $limit OFFSET $offset", $type);
        $result = array();
    } catch(Exception $e) {
        exit($e->getMessage());
    }
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $detail = array(
            'item' => array(),
            'last' => array(),
            'posts' => 0,
            'comments' => 0
        );
        if ($type == 'coin') {
            $detail['item'] = get_coin($fetch['coin_id']);
            $detail['item']['link'] = coin_link($detail['item'], 'forum');
        }
        $detail['last'] = get_last_post($type,$fetch['coin_id']);
        $detail['posts'] = count_single_forum_posts($type, $fetch['coin_id']);
        $detail['comments'] = count_forum_comments($type,$fetch['coin_id']);
        $result[] = $detail;
    }
    return $result;
}

function get_last_post($type,$id) {
    $query = db()->query("SELECT user_id,date_created FROM posts WHERE post_type=? AND coin_id=? ORDER BY id DESC LIMIT 1", $type,$id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function count_single_forum_posts($type, $typeId) {
    $query = db()->query("SELECT user_id,date_created FROM posts WHERE post_type=? AND coin_id=?", $type,$typeId);
    return $query->rowCount();
}
function count_forum_posts($type) {
    $query = db()->query("SELECT DISTINCT(coin_id) FROM posts WHERE post_type=?", $type);
    return $query->rowCount();
}

function count_forum_comments($type,$typeId = '') {
    $postIds = array(0);
    $query = db()->query("SELECT id FROM posts WHERE coin_id=? AND post_type=?", $typeId, $type);
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $postIds[] = $fetch['id'];
    }
    $postIds = implode(',', $postIds);
    $query = db()->query("SELECT id FROM comments WHERE post_id IN ($postIds)");
    return $query->rowCount();
}

function get_highest_coins() {
    $coins = array();
    $selected = explode(',', config('trending-coin', 'BTC,ETH,XRP,BCH,ADA'));
    foreach($selected as $se) {
        $coins[] = get_coin($se);
    }
    return $coins;
}

function available_table_columns() {
    return array(
        'name',
        'price',
        'change_ptc',
        'supply',
        'volume',
        'market_cap',
        '24h_performance',
        'action'
    );
}


function my_table_columns() {
    $columns = array(
        'name',
        'price',
        'change_ptc',
        'supply',
        'volume',
        'market_cap',
        '24h_performance',
        'action'
    );

    if (isset($_COOKIE['table-columns'])) {
        $columns = explode(',', $_COOKIE['table-columns']);
    }
    return $columns;
}


function get_default_market() {
    return "Cryptsy,trustdex,Exx,bisq,BTCChina,kucoin,Bitstamp,BTER,OKCoin,Coinbase,Poloniex,Cexio,BTCE,BitTrex,Kraken,Bitfinex,Yacuna,LocalBitcoins,Yunbi,itBit,HitBTC,btcXchange,BTC38,Coinfloor,Huobi,CCCAGG,LakeBTC,ANXBTC,Bit2C,Coinsetter,CCEX,Coinse,MonetaGo,Gatecoin,Gemini,CCEDK,Cryptopia,Exmo,Yobit,Korbit,BitBay,BTCMarkets,Coincheck,QuadrigaCX,BitSquare,Vaultoro,MercadoBitcoin,Bitso,Unocoin,BTCXIndia,Paymium,TheRockTrading,bitFlyer,Quoine,Luno,EtherDelta,bitFlyerFX,TuxExchange,CryptoX,Liqui,MtGox,BitMarket,LiveCoin,Coinone,Tidex,Bleutrade,EthexIndia,Bithumb,CHBTC,ViaBTC,Jubi,Zaif,Novaexchange,WavesDEX,Binance,Lykke,Remitano,Coinroom,Abucoins,BXinth,Gateio,HuobiPro,OKEX";
}

function count_available_exchanges() {
    return count(explode(',', get_default_market()));
}

function get_available_exchanges() {

    return array(
        "Cryptsy" => '',
        "BTCChina" => '',
        "Bitstamp"=>'https://www.bitstamp.net/',
        "BTER"=>'https://bter.com/',
        "OKCoin"=> '',
        "Coinbase"=>'',
        "Poloniex" => 'https://poloniex.com/',
        "Cexio"=> 'https://cex.io/r/0/up110861111/0/',
        "BTCE"=> 'https://btc-e.com/',
        "BitTrex" => 'https://bittrex.com/',
        "Kraken"=> 'https://www.kraken.com/',
        "Bitfinex" => 'https://www.bitfinex.com/',
        "Yacuna"=>'https://yacuna.com/',
        "LocalBitcoins"=> '',
        "Yunbi"=>'https://yunbi.com/',
        "itBit" => '',
        "HitBTC" => 'https://hitbtc.com/',
        "btcXchange" => '',
        "BTC38"=> 'http://www.btc38.com/',
        "Coinfloor" => 'https://coinfloor.co.uk/',
        "Huobi" => '',
        "CCCAGG" => '',
        "LakeBTC" => '',
        "ANXBTC" => '',
        "Bit2C" => '',
        "Coinsetter" => '',
        "CCEX" => '',
        "Coinse" => '',
        "MonetaGo" => '',
        "Gatecoin" => 'https://gatecoin.com/',
        "Gemini" => '',
        "CCEDK" => 'https://www.ccedk.com/',
        "Cryptopia" => 'https://www.cryptopia.co.nz/Exchange',
        "Exmo" => 'https://exmo.com/',
        "Yobit" => 'https://yobit.io/',
        "Korbit" => '',
        "BitBay" => 'https://affiliate.bitbay.net/11543',
        "BTCMarkets" => 'https://www.btcmarkets.net/',
        "Coincheck" => 'https://coincheck.com/',
        "QuadrigaCX" => '',
        "BitSquare" => '',
        "Vaultoro" => '',
        "MercadoBitcoin" => '',
        "Bitso" => '',

        "Unocoin" => '',
        "BTCXIndia" => '',
        "Paymium" => '',
        "TheRockTrading" => 'https://www.therocktrading.com/referral/450',
        "bitFlyer"=> '',
        "Quoine" => '',
        "Luno" => '',
        "EtherDelta" => 'https://etherdelta.github.io/',
        "bitFlyerFX" => '',
        "TuxExchange"=>'https://tuxexchange.com/',
        "CryptoX" => 'https://cryptox.pl/',
        "Liqui" => 'https://liqui.io/',
        "MtGox" => '',
        "BitMarket" => 'https://www.bitmarket.net/',
        "LiveCoin" => 'https://livecoin.net/',
        "Coinone" => '',
        "Tidex" => 'https://tidex.com/',
        "Bleutrade" => 'https://bleutrade.com/',
        "EthexIndia" => '',
        "Bithumb" => '',
        "CHBTC" => '',
        "ViaBTC" => '',
        "Jubi" => 'https://www.jubi.com/',
        "Zaif" => 'https://zaif.jp/',
        "Novaexchange" => 'https://novaexchange.com/',
        "WavesDEX" => 'https://wavesplatform.com/',
        "Binance" => 'https://www.binance.com/',
        "Lykke" => 'https://www.lykke.com/',
        "Remitano" => 'https://remitano.com/',
        "Coinroom" => 'https://coinroom.com/',
        "Abucoins" => 'https://abucoins.com/',
        "BXinth" => 'https://bx.in.th/',
        "Gateio" => 'https://gate.io/',
        "HuobiPro" => '',
        "OKEX" => 'https://www.okex.com/',
        'trustdex' => 'https://trustdex.io/',
        'exx' => 'https://www.exx.com/',
        'kucoin' => 'https://www.kucoin.com/',
        'bisq' => 'https://bisq.network/',
    );
}
