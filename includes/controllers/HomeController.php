<?php
class HomeController extends Controller {
    public function index() {
        $segment = segment(0, config('default-home', 'market'));
        switch($segment) {
            case 'market':
                $this->setTitle(lang('live-cryptocurrecies-title'));
                app()->activeMenu = 'market';
                $limit = config('coin.list.limit', input('limit', 50));
                $order = config('coin.order', input('order', 'market_cap'));
                $orderType = input('order_type', 'DESC');
                $offset = input('offset', 0);
                $coins = get_coins($order,$orderType,$limit,$offset);
                $content = view('main::home/index', array(
                    'coins' => $coins,
                    'limit' => $limit,
                    'order' => $order,
                    'orderType' => $orderType,
                    'offset' => $offset));
                break;
            case 'forum':
                app()->activeMenu = 'forum';
                $page = segment(1, 'coin');
                $content = view('main::home/forum', array('page' => $page));
                break;
        }
        return $this->render($content);
    }


    public function login() {
        $username = input('username');
        $password = input('password');
        $result = array(
            'status' => 0,
            'message' => lang('failed-to-login')
        );

        if (empty($username) or empty($password)) return json_encode($result);
        if (login_user($username, $password)) {
            $result['status'] = 1;
            $result['message'] = lang('login-success');
            $result['url'] = url('');
            return json_encode($result);
        } else {
            $result['message'] = lang('invalid-login-details');
            return json_encode($result);
        }
    }

    public function signup() {
        $name = input('name');
        $username = input('username');
        $password = input('password');
        $email = input('email');
        $result = array(
            'status' => 0,
            'message' => ''
        );

        if (!config('enable-signup', true))  return json_encode($result);

        if (empty($name)) {
            $result['message'] = lang('provide-full-name');
            return json_encode($result);
        }

        if (empty($username)) {
            $result['message'] = lang('provide-username');
            return json_encode($result);
        }

        if (empty($password)) {
            $result['message'] = lang('choose-a-password');
            return json_encode($result);
        }

        if (empty($email)) {
            $result['message'] = lang('provide-email-address');
            return json_encode($result);
        }

        if (check_username($username)) {
            $result['message'] = lang('username-already-exist');
            return json_encode($result);
        }

        if (username_is_bad($username)) {
            $result['message'] = lang('username-is-bad');
            return json_encode($result);
        }

        if (!is_email($email) or check_email($email)) {
            $result['message'] = lang('email-already-exist');
            return json_encode($result);
        }

        if (config('enable-recaptcha', false) and !validate_recaptcha()){
            $result['message'] = lang('security-check-failed');
            return json_encode($result);
        }

        $userid = register_member($username,$name,$email,$password);

        if (config('send-welcome-mail', true)) {
            $message  = lang('welcome-mail');
            try{
                Email::getInstance()->setAddress($email)
                    ->setSubject(lang('welcome-to-site'))
                    ->setMessage($message)->send();
            } catch(Exception $e){}
        }


        if (config('enable-account-activation', false)) {
            $user = get_user($userid);
            if ($user) {
                //send the reset link
                send_activation_link($user);

                return json_encode(array(
                    'status' => 1,
                    'message' => lang('registration-success'),
                    'url' => url('activate/success?id='.$userid)
                ));
            }
        }

        return json_encode(array(
            'status' => 1,
            'message' => lang('registration-success'),
            'url' => url('')
        ));
    }

    public function logout() {
        logout_user();
        redirect(url());
    }



    public function info() {
        $segment = segment(0);

        switch($segment) {
            case 'privacy':
                $title = lang('privacy');
                $content = config('privacy');
                break;
            case 'about':
                $title = lang('about');
                $content = config('about');
                break;
            default:
                $title = lang('terms-and-condition');
                $content = config('terms-and-condition');
                break;
        }
        return $this->render(view('main::home/info', array('content' => $content,'title' => $title)));
    }

    public function setLanguage() {
        session_put('language', input('id'));
         redirect_back();
    }

    public function forgot() {
        $this->setTitle(lang('forgot-password'));
        $error = null;
        $success = null;
        if (request_is_post()) {
            $email = input('email');
            $user = get_user($email);
            if ($user) {
                //send the reset link
                $hash = md5('djsdfsjkfsd1234233'.time());
                db()->query("UPDATE members SET hash=? WHERE id=?", $hash, $user['id']);
                $link = "<a href='".url('reset/password?hash='.$hash)."'>".url('reset/password?hash='.$hash).'</a>';
                $success = lang('reset-link-sent');
                $message  = lang('reset-password-message', array('link' => $link));
                Email::getInstance()->setAddress($user['email'])
                    ->setSubject(lang('reset-password-link'))
                    ->setMessage($message)->send();
            } else {
                $error = lang('email-address-does-not-exist');
            }
        }
        return $this->render(view('main::home/forgot', array('error' => $error, 'success' => $success)));
    }

    public function reset() {
        $this->setTitle(lang('reset-password'));
        $error = null;
        $success = null;
        $hash = input('hash');
        $query = db()->query("SELECT * FROM members WHERE hash=?", $hash);
        if ($query->rowCount() < 1) redirect(url());
        if (request_is_post()) {
            $password = input('password');
            $confirm = input('confirm');
            if ($password != $confirm) {
                $error = lang('password-deos-not-match');
            } else {
                $password = hash_make($password);
                $user = $query->fetch(PDO::FETCH_ASSOC);
                db()->query("UPDATE members SET password=?,hash=? WHERE hash=?", $password, $hash,$hash);
                login_user($user['username'], $confirm);
                return redirect(url());
            }
        }
        return $this->render(view('main::home/reset', array('error' => $error, 'success' => $success)));
    }

    public function turnLight() {
        $type = input('t', 'on');
        setcookie("turn-light", $type, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        redirect_back();
    }

    public function translator() {

        $lang_id = input('lang', 'fr');

        $strings = include(path('languages/en.php'));
        $content = "";
        foreach($strings as $id => $value) {
            $key = "AIzaSyDTTljwhwwqeRjzl7vbR9xeKfQYjcKHc7M";
            $text = $value;
            $url = 'https://www.googleapis.com/language/translate/v2?key=' . $key . '&q=' . rawurlencode($text) . '&source=en&target='.$lang_id;

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($handle);
            $responseDecoded = json_decode($response, true);
            curl_close($handle);
            $value = $responseDecoded['data']['translations'][0]['translatedText'];
            $content .= "'$id' => '$value', ".PHP_EOL;
        }
        try{
            @mkdir(path('files/uploads/translated/'), 0777, true);
            $file = @fopen(path('files/uploads/translated/'.$lang_id.'.php'), 'x+');
            if ($file) fclose($file);;
            $fullContent = "<?php ".PHP_EOL;
            $fullContent .= " return array( ".PHP_EOL;
            $fullContent .= $content.PHP_EOL;
            $fullContent .= ")";
            file_put_contents(path('files/uploads/translated/'.$lang_id.'.php'), $fullContent);

            download_file(path('files/uploads/translated/'.$lang_id.'.php'), $lang_id.'.php');
        } catch(Exception $e){};
        echo $content;
        exit;
    }

    public function saveColumns() {
        $columns = input('columns');
        if (count($columns) < 2) return false;
        $selected = array();
        foreach($columns as $column => $value) {
            $selected[] = $column;
        }
        $selected = implode(',', $selected);
        setcookie("table-columns", $selected, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        return true;
    }

    public function exchanges() {
        $this->setTitle(lang('exchanges'));
        app()->activeMenu = 'exchanges';
        $coin = input('coin', 'BTC');
        return $this->render(view('main::home/exchanges', array('coin' => $coin)));
    }

    public function activateSuccess() {
        if (input('resend')) {
            $user = get_user(input('id'));
            send_activation_link($user);
        }
        return $this->render(view('main::home/activate'));
    }

    public function activate() {
        $hash = input('hash');
        $query = db()->query("SELECT * FROM members WHERE hash=? LIMIT 1", $hash);

        if ($query->rowCount() > 0) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            db()->query("UPDATE members SET active=?,activated=? WHERE id=? ", 1,1, $user['id']);
            login_with_user($user);
            return redirect(url(""));
        } else {
            exit("You have follow a bad link");
        }
    }

    public function sitemap() {
        $output = segment(1, 'list');
        $links = array(
            array(
                'loc' => url(),
                'priority' => '1.0',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('blog'),
                'priority' => '0.9',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('forum'),
                'priority' => '0.5',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('exchanges'),
                'priority' => '0.9',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('terms'),
                'priority' => '0.1',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('privacy'),
                'priority' => '0.1',
                'lastmod' => date( 'c', time() - 360)
            ),
            array(
                'loc' => url('about'),
                'priority' => '0.1',
                'lastmod' => date( 'c', time() - 360)
            )
        );

        $coins = get_all_coins();
        foreach($coins as $coin) {
            $links[] = array(
                'loc' => coin_link($coin),
                'priority' => '0.5',
                'lastmod' => date( 'c', time())
            );
        }

        switch($output) {
            default:
                return view('main::home/sitemap', array('links' => $links));
                break;
            case 'xml':
                $content = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

                foreach($links as $link) {
                    $content .= ' <url>
            <loc>'.$link['loc'].'</loc>
            		<priority>'.$link['priority'].'</priority>
		<lastmod>'.$link['lastmod'].'</lastmod>
        </url>';
                }
                $content .= '</urlset>';
                return $content;
                break;
            case 'txt':
                $content = "";
                foreach($links as $link) {
                    $content .= $link['loc'].PHP_EOL;
                }
                return nl2br($content);
                break;
        }
    }

    public function pages() {
        $slug = segment(1);
        $page = get_page($slug);
        if(!$page) return redirect(url(''));
        $this->setTitle(lang($page['title']));
        $pageDesc = $page['description'];
        $pageTitle = lang($page['title']);

        $metas = "<meta property='og:url' content='".page_link($page)."'/>";
        $metas .= "<meta property='og:type' content='article'/>";
        $metas .= "<meta property='og:title' content='$pageTitle'/>";
        $metas .= "<meta property='og:description' content='$pageDesc'/>";

        Layout::getInstance()->addHeaderContent($metas);

        return $this->render(view('main::home/page', array('page' => $page)));
    }
}