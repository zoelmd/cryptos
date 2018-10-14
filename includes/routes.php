<?php
Router::addFilter('home', function() {

    return true;
});
Router::addFilter('auth', function() {
    if (!is_loggedin()) {
        if (is_ajax()) exit('login-is-needed');
        return redirect(url('?login=true'));
    }
    return true;
});

Router::addFilter('profile', function() {
    $user = get_user(segment(0));
    if (!$user) return false;
    app()->profileUser = $user;
    app()->profileId = $user['id'];
    return true;
});

Router::addFilter('admincp', function() {
    if (!is_loggedin()) {
        if (is_ajax()) exit(json_encode(array('status' => 0, 'message' => 'Please login')));
        return redirect(url('?login=true'));
    }
    if (!is_admin()) return redirect(url());
    Layout::getInstance()->pageType = "backend";
    return true;
});
Router::get("/", array('as' => 'home', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("forum", array('as' => 'home-feed', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("forum/exchange", array('as' => 'exchange-form', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("forum/latest", array('as' => 'latest--forum', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("exchanges", array('as' => 'exchanges', 'uses' => 'HomeController@exchanges'))->addFilter('home');
Router::get("market", array('as' => 'home-market', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("terms", array( 'uses' => 'HomeController@info'));
Router::get("privacy", array( 'uses' => 'HomeController@info'));
Router::get("about", array( 'uses' => 'HomeController@info'));

Router::post("signup", array( 'uses' => 'HomeController@signup'));

Router::get("translator", array( 'uses' => 'HomeController@translator'));
Router::post("login", array( 'uses' => 'HomeController@login'));
Router::any("forgot-password", array( 'uses' => 'HomeController@forgot'));
Router::any("reset/password", array( 'uses' => 'HomeController@reset'));

Router::any("activate/success", array( 'uses' => 'HomeController@activateSuccess'));
Router::any("activate/account", array( 'uses' => 'HomeController@activate'));

Router::any("sitemap", array( 'uses' => 'HomeController@sitemap'));
Router::any("sitemap/txt", array( 'uses' => 'HomeController@sitemap'));
Router::any("sitemap/xml", array( 'uses' => 'HomeController@sitemap'));

Router::get("logout", array( 'uses' => 'HomeController@logout'));
Router::any("getstarted", array( 'uses' => 'HomeController@getstarted'))->addFilter('auth');

Router::post("save/columns", array( 'uses' => 'HomeController@saveColumns'));

Router::any("set/language", array( 'uses' => 'HomeController@setLanguage'));
Router::any("turn/light", array( 'uses' => 'HomeController@turnLight'));

Router::any("page/{slug}", array( 'uses' => 'HomeController@pages'))->where(array('slug' => '[a-zA-Z0-9\-\_]+'));


Router::any("social/auth/{id}", array( 'uses' => 'SocialController@index'))->where(array('id' => '[a-zA-Z0-9\-\_]+'));



Router::any("settings", array( 'uses' => 'UserController@settings'))->addFilter('auth');
Router::any("notification/load", array( 'uses' => 'UserController@loadNotification'))->addFilter('auth');
Router::any("notifications", array( 'uses' => 'UserController@notifications'))->addFilter('auth');
Router::any("check/events", array( 'uses' => 'UserController@checkEvents'))->addFilter('auth');

Router::any("report/content", array( 'uses' => 'UserController@report'))->addFilter('auth');

Router::any("search", array( 'uses' => 'UserController@search'));

Router::any("post/add", array( 'uses' => 'PostController@add'))->addFilter('auth');
Router::any("post/load/more", array( 'uses' => 'PostController@morePost'));
Router::any("post/{id}", array( 'uses' => 'PostController@page'))->where(array('id' => '[0-9]+'));
Router::any("post/save", array( 'uses' => 'PostController@savePost'))->addFilter('auth');
Router::any("post/delete", array( 'uses' => 'PostController@deletePost'))->addFilter('auth');
Router::any("like/process", array( 'uses' => 'PostController@like'))->addFilter('auth');
Router::any("comment/add", array( 'uses' => 'PostController@addComment'))->addFilter('auth');
Router::any("comment/remove", array( 'uses' => 'PostController@removeComment'))->addFilter('auth');
Router::any("comment/more", array( 'uses' => 'PostController@moreComment'))->addFilter('auth');



Router::get('admincp', array('uses' => 'AdminController@index' ))->addFilter('admincp');
Router::any('admincp/settings', array('uses' => 'AdminController@settings' ))->addFilter('admincp');
Router::any('admincp/system/update', array('uses' => 'AdminController@updateSystem' ))->addFilter('admincp');

Router::any('admincp/appearance', array('uses' => 'AdminController@appearance' ))->addFilter('admincp');
Router::any('admincp/users', array('uses' => 'AdminController@users' ))->addFilter('admincp');
Router::any('admincp/coins', array('uses' => 'AdminController@coins' ))->addFilter('admincp');
Router::any('admincp/user/edit', array('uses' => 'AdminController@editUser' ))->addFilter('admincp');
Router::any('admincp/requests', array('uses' => 'AdminController@requests' ))->addFilter('admincp');
Router::any('admincp/adverts', array('uses' => 'AdminController@adverts' ))->addFilter('admincp');
Router::any('admincp/pages', array('uses' => 'AdminController@pages' ))->addFilter('admincp');
Router::any('admincp/badges', array('uses' => 'AdminController@badges' ))->addFilter('admincp');
Router::any('admincp/badge', array('uses' => 'AdminController@badge' ))->addFilter('admincp');
Router::any('admincp/blog', array('uses' => 'AdminController@blog' ))->addFilter('admincp');
Router::any('admincp/menu', array('uses' => 'AdminController@menu' ))->addFilter('admincp');

Router::any("follow/process", array( 'uses' => 'ProfileController@processFollow'))->addFilter('auth');

Router::any("cron/run", array( 'uses' => 'CronController@run'));

Router::any("coin/{username}", array( 'uses' => 'CoinController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'));
Router::any("download/coins", array( 'uses' => 'CoinController@downloadCoins'));
Router::any("convert", array( 'uses' => 'CoinController@convert'));
Router::any("coin/{username}/forum", array( 'uses' => 'CoinController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'));
Router::any("coin/{username}/market", array( 'uses' => 'CoinController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'));
Router::any("coin/{username}/trade", array( 'uses' => 'CoinController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'));
Router::any("coin/{username}/followers", array( 'uses' => 'CoinController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'));


Router::any("blog", array( 'uses' => 'BlogController@index'));
Router::any("blog/{id}/{slug}", array( 'uses' => 'BlogController@page'))->where(array('slug' => '[a-zA-Z0-9\-\_]+', 'id' => '[0-9]+'));

Router::any("portfolio", array( 'uses' => 'PortfolioController@index'))->addFilter('auth');
Router::any("portfolio/me", array( 'uses' => 'PortfolioController@index'))->addFilter('auth');
Router::any("portfolio/public", array( 'uses' => 'PortfolioController@index'))->addFilter('auth');
Router::any("portfolio/{id}/{slug}", array( 'uses' => 'PortfolioController@index'))->addFilter('auth')->where(array('id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-\_]+'));
Router::any("portfolio/save", array( 'uses' => 'PortfolioController@save'))->addFilter('auth');
Router::any("portfolio/coin/save", array( 'uses' => 'PortfolioController@saveCoin'))->addFilter('auth');

Router::any("load/history", array( 'uses' => 'CoinController@getHistory'));
Router::any("currency/switch", array( 'uses' => 'CoinController@switchCurrency'));

Router::any("{username}", array( 'uses' => 'ProfileController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/followers", array( 'uses' => 'ProfileController@follow'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/following", array( 'uses' => 'ProfileController@follow'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/favourites", array( 'uses' => 'ProfileController@favourites'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');