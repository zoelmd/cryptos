<?php
class CoinController extends Controller {
    public function index() {
        app()->activeMenu = 'market';
        $coin = strtoupper(segment(1));
        $coin = get_coin($coin);
        $page = segment(2, 'overview');
        if (!$coin) return redirect(url('market'));
        $pageTitle = ($coin['seo_title']) ? $coin['seo_title'] : $coin['name'].' ('.$coin['symbol'].') '.lang('info-quotes-chart');
        $pageDesc = ($coin['seo_description']) ? $coin['seo_description'] : $coin['name'].' ('.$coin['symbol'].') '.lang('info-quotes-chart');

        $metas = "<meta property='og:url' content='".coin_link($coin)."'/>";
        $metas .= "<meta property='og:type' content='article'/>";
        $metas .= "<meta property='og:title' content='$pageTitle'/>";
        $metas .= "<meta property='og:description' content='$pageDesc'/>";
        $metas .= "<meta property='og:image' content='".url($coin['logo'])."'/>";

        Layout::getInstance()->addHeaderContent($metas);
        Layout::getInstance()->favicon = url($coin['logo_small']);
        $this->setTitle($pageTitle);
        switch($page) {
            default:
                $content = view('main::coin/overview', array('coin' => $coin));
                break;
            case 'forum':
                $content = view('main::coin/forum', array('coin' => $coin));
                break;
            case 'market':
                $content = view('main::coin/market', array('coin' => $coin));
                break;
            case 'trade':
                $content = view('main::coin/trade', array('coin' => $coin));
                break;
            case 'followers':
                $content = view('main::coin/followers', array('coin' => $coin));
                break;
        }
        return $this->render(view('main::coin/index', array('coin' => $coin,'content' => $content,'page' => $page)));
    }

    public function getHistory() {
        $symbol = input('symbol');
        return json_encode(get_coin_history($symbol));
    }

    public function switchCurrency() {
        $code = input('code');
        setcookie("base_currency", $code, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        redirect_back();
    }

    public function downloadCoins() {
        update_coins_lists();
    }

    public function convert() {
        $input  = input('input');
        $from = input('from');
        $to = input('to');
        $type = input('type');
        $currency = find_currency($to);
        $coin = get_coin($from);
        $value = $coin['price'] * $currency['rate'];
        $value = $value * $input;
        $price = formatNumber($value, config('decimal-point', 3));
        if ($type == 'left') {
            return '<strong>'.$price.'</strong> '.$to;
        } else {
            return '<strong>'.$price.'</strong> '.$from;
        }
    }
}