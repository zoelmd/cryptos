<?php
class CronController extends Controller {
    public function run() {

        set_time_limit(36000);
        update_coins_lists();
        update_coins_data();
        update_rates_data();
        if (input('install')) return redirect(url());
        exit('done');
    }
}