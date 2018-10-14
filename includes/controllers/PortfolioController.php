<?php
class PortfolioController extends Controller {

    public function index() {
        $this->setTitle(lang('portfolio'));
        app()->activeMenu = 'portfolio';
        $page = segment(1, 'home');
        switch($page) {
            case 'me':
                $content = view('main::portfolio/list', array('type' => 'mine'));
                break;
            case 'public':
                $content = view('main::portfolio/list', array('type' => 'public'));
                break;
            default:
                $portfolio = get_view_portfolio($page);
                $canView = true;
                if ($portfolio) {
                    if($portfolio['privacy'] == 0 and $portfolio['user_id'] != get_userid()) $canView = false;
                }
                if ($portfolio and $canView) {
                    add_unique_views('portfolio', $portfolio['id'], function() use($portfolio){
                        db()->query("UPDATE portfolios SET views = views + 1 WHERE id=?" , $portfolio['id']);
                    });
                    if ($transaction = input('transaction')) {
                        db()->query("DELETE FROM portfolio_transactions WHERE id=? ", $transaction);
                    }

                    if ($delete = input('delete')) {
                        delete_portfolio($portfolio['id']);
                        return redirect(url('portfolio'));
                    }
                    $content = view('main::portfolio/page', array('portfolio' => $portfolio));
                } else {
                    $content = view('main::portfolio/list', array('type' => 'mine'));
                }
                $page = 'me';
                break;
        }
        return $this->render(view('main::portfolio/index', array('content' => $content, 'page' => $page)));
    }

    public function save() {
        $id = input('id');
        $name = input('name');
        $desc = input('desc');
        $privacy = input('privacy', 0);
        return save_portfolio($name,$desc,$privacy,$id);
    }

    public function saveCoin() {
        $id = input('id');
        $purchase = input('purchase');
        $sold = config('base_currency', 'USD');
        $amount = input('amount');
        $rate = input('rate');
        $date = input('time');
        return save_portfolio_coin($id,$purchase,$sold,$amount,$rate,$date);
    }
}