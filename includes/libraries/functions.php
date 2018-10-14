<?php
/**
 * Function to get the full url
 */
function getFullUrl($queryStr = false)
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];
    $isSecure = (isset($request['HTTPS']) and $request['HTTPS'] == "on") ? true : false;
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $queryString = (isset($_SERVER['QUERY_STRING']) and $_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : null;
    $scheme = (app()->getConfig('https')) ? "https://" : "http://";
    $fullUrl = $scheme . $host . $uri;
    return $fullUrl = ($queryStr) ? $fullUrl . $queryString : $fullUrl;
}

function isSecure()
{
    return $isSecure = (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == "on") ? true : false;
}


function is_email($value) {
    if (filter_var($value, FILTER_VALIDATE_EMAIL) == false) return false;
    return true;
}

function view($view, $param = array()) {
    return View::find($view, $param);
}

function get_theme(){
    return Layout::getInstance()->theme;
}

function getScheme()
{
    return (app()->getConfig('https')) ? 'https' : 'http';
}

function getHost()
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];

    //remove unwanted characters
    $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
    //prevent Dos attack
    if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
        die();
    }

    return $host;
}

function server($name, $default = null)
{
    if (isset($_SERVER[$name])) return $_SERVER[$name];
    return $default;
}

function getRoot()
{
    $base = getBase();

    return getScheme() . '://' . getHost() . $base;
}

function getBase()
{
    $filename = basename(server('SCRIPT_FILENAME'));
    if (basename(server('SCRIPT_NAME')) == $filename) {
        $baseUrl = server('SCRIPT_NAME');
    } elseif (basename(server('PHP_SELF')) == $filename) {
        $baseUrl = server('PHP_SELF');
    } elseif (basename(server('ORIG_SCRIPT_NAME')) == $filename) {
        $baseUrl = server('ORIG_SCRIPT_NAME');
    } else {
        $baseUrl = server('SCRIPT_NAME');
    }

    $baseUrl = str_replace('index.php', '', $baseUrl);

    return $baseUrl;
}

/**
 * Function to get the request method
 * @return string
 */
function get_request_method()
{
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

/**
 * Method to get path
 */
function path($path = "")
{
    $base = APP_BASE_PATH ;
    return $base . $path;
}

/**
 * Function to get app instance
 */
function app()
{
    return Application::getInstance();
}

function config($key, $default = null)
{
    return app()->getConfig($key, $default);
}

function lang($name, $replace = array(), $default = null)
{
    return Translation::getInstance()->translate($name, $replace, $default);
}

function _lang($name, $replace = array(), $default = null)
{
    echo lang($name, $replace, $default);
}

function input_has($name)
{
    return input($name);
}
function session_put($name, $value = "")
{
    $_SESSION[$name] = $value;
    return true;
}
function session_get($name, $default = false)
{
    if (!isset($_SESSION[$name])) return $default;
    return $_SESSION[$name];
}
function session_forget($name)
{
    if (isset($_SESSION[$name])) unset($_SESSION[$name]);
    return true;
}

function redirect($url, $flash = array())
{
    @session_write_close();
    @session_regenerate_id(true);
    header("Location:" . $url);
    exit;
}
function redirect_back($flash = array())
{
    $back = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
    if (empty($back) and !preg_match("#" . config("base_url") . "#", $back)) redirect(url());
    redirect($back);
}

function url($url = "") {
    return Url::get($url);
}


function request_is_post() {
    return (get_request_method() == "POST") ? true : false;
}

function input_file($name)
{
    if (isset($_FILES[$name])) {
        if (is_array($_FILES[$name]['name'])) {
            $files = array();
            $index = 0;
            foreach ($_FILES[$name]['name'] as $n) {
                if ($_FILES[$name]['name'] != 0) {
                    $files[] = array(
                        'name' => $n,
                        'type' => $_FILES[$name]['type'][$index],
                        'tmp_name' => $_FILES[$name]['tmp_name'][$index],
                        'error' => $_FILES[$name]['error'][$index],
                        'size' => $_FILES[$name]['size'][$index]
                    );
                }
                $index++;
            }

            if (empty($files)) return false;
            return $files;
        } else {
            if ($_FILES[$name]['size'] == 0) return false;
            return $_FILES[$name];
        }
    }
    return false;
}

function segment($index, $default = null) {
    return Url::segment($index, $default);
}

function db() {
    return Database::getInstance();
}

function input($name, $default = "", $escape = true)
{
    //if (!isset($_POST[$name]) and !isset($_GET[$name])) return $default;
    //for all admin lets escape be off
    //if (segment(0) == 'admincp') $escape = false;
    if ($name == "val" and get_request_method() != "POST") return false;
    $index = "";
    if (preg_match("#\.#", $name)) list($name, $index) = explode(".", $name);

    $result = (isset($_GET[$name])) ? $_GET[$name] : $default;
    $result = (isset($_POST[$name])) ? $_POST[$name] : $result;

    if (is_array($result)) {
        if (empty($index)) {
            $nR = array();
            foreach ($result as $k => $v) {
                if (is_array($v)) {
                    $newResult = array();
                    foreach ($v as $n => $a) {
                        $newResult[$n] = ((!is_array($a) and $escape === true) || (is_array($escape) && !in_array($k, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($a)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                    }
                    $nR[$k] = $newResult;
                } else {
                    $nR[$k] = ($escape === true || (is_array($escape) && !in_array($k, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($v)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                }
            }
            $result = $nR;
        } else {
            if (!isset($result[$index])) return $default;
            if (is_array($result[$index])) {
                $newResult = array();
                foreach ($result[$index] as $n => $v) {
                    $newResult[$n] = ((!is_array($v) and $escape === true) || (is_array($escape) && !in_array($index, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($v)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                }
                $result = $newResult;
            } else {
                $result = ((!is_array($result[$index]) and $escape === true) || (is_array($escape) && !in_array($index, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($result[$index])))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $result[$index]));
            }

        }
    } else {
        $result = ((!is_array($result) and $escape === true) || (is_array($escape) && !in_array($name, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($result)))) : str_replace("'", '&#39;', $result);
    }

    return $result;
}

function set_cache($key, $value, $time = null)
{
    $cache = Cache::getInstance();
    $cache->set($key, $value, $time);
}

function get_cache($key, $default = null)
{
    $cache = Cache::getInstance();
    return $cache->get($key, $default);
}

function set_cacheForever($key, $value)
{
    $cache = Cache::getInstance();
    $cache->setForever($key, $value);
}

function forget_cache($key)
{
    $cache = Cache::getInstance();
    $cache->forget($key);
}

function flush_cache()
{
    $cache = Cache::getInstance();
    $cache->flush();
}
function cache_exists($key)
{
    $cache =
        Cache::getInstance();
    return $cache->keyexists($key);
}

function hash_make($content)
{
    if (extension_loaded('mcrypt')) {
        require_once path("includes/libraries/password.php");
        return password_hash($content, PASSWORD_BCRYPT, array('cost' => 10));
    } else {
        return md5($content);
    }
}
function hash_check($content, $hash)
{
    if (extension_loaded('mcrypt')) {
        require_once path("includes/libraries/password.php");
        return password_verify($content, $hash);
    } else {
        return (md5($content) == $hash);
    }
}

function get_timezones_list()
{
    return array(
        'EUROPE'=>DateTimeZone::listIdentifiers(DateTimeZone::EUROPE),
        'AMERICA'=>DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
        'INDIAN'=>DateTimeZone::listIdentifiers(DateTimeZone::INDIAN),
        'AUSTRALIA'=>DateTimeZone::listIdentifiers(DateTimeZone::AUSTRALIA),
        'ASIA'=>DateTimeZone::listIdentifiers(DateTimeZone::ASIA),
        'AFRICA'=>DateTimeZone::listIdentifiers(DateTimeZone::AFRICA),
        'ANTARCTICA'=>DateTimeZone::listIdentifiers(DateTimeZone::ANTARCTICA),
        'ARCTIC'=>DateTimeZone::listIdentifiers(DateTimeZone::ARCTIC),
        'ATLANTIC'=>DateTimeZone::listIdentifiers(DateTimeZone::ATLANTIC),
        'PACIFIC'=>DateTimeZone::listIdentifiers(DateTimeZone::PACIFIC),
        'UTC'=>DateTimeZone::listIdentifiers(DateTimeZone::UTC),
    );
}

function get_ip()
{
    //Just get the headers if we can or else use the SERVER global
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = $_SERVER;
    }

    //Get the forwarded IP if it exists
    if (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['X-Forwarded-For'];
    } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
    ) {
        $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
        $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    return $the_ip;
}


function is_ajax()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest") {
        return true;
    }
    return false;
}

function delete_file($path)
{
    $basePath = path();
    $basePath2 = $basePath . '/';

    if ($path == $basePath or $path == $basePath2) return false;
    if (is_dir($path) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if (in_array($file->getBasename(), array('.', '..')) !== true) {
                if ($file->isDir() === true) {
                    rmdir($file->getPathName());
                } else if (($file->isFile() === true) || ($file->isLink() === true)) {
                    unlink($file->getPathname());
                }
            }
        }

        return rmdir($path);
    } else if ((is_file($path) === true) || (is_link($path) === true)) {
        return unlink($path);
    }

    return false;
}

function convertToAscii($str, $replace = array(), $delimiter = '-', $charset = 'ISO-8859-1')
{


    $str = str_replace(
        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
        array("'", "'", '"', '"', '-', '--', '...'),
        $str); // by mordomiamil
    try {
        $str = iconv($charset, 'UTF-8', $str); // by lelebart
        if (!empty($replace)) {
            $str = str_replace((array)$replace, ' ', $str);
        }
        $clean = $str;
    } catch (Exception $e) {
    }

    try {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    } catch (Exception $e) {

    }

    $str = preg_replace('/[^\x{0600}-\x{06FF}A-Za-z !@#$%^&*()]/u', '', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = strtolower(trim($clean, '-'));
    return $clean;
}

if (!function_exists('perfectSerialize')) {
    function perfectSerialize($string)
    {
        return base64_encode(serialize($string));
    }
}

if (!function_exists('perfectUnserialize')) {
    function perfectUnserialize($string)
    {

        if (base64_decode($string, true) == true) {

            return @unserialize(base64_decode($string));
        } else {
            return @unserialize($string);
        }
    }
}

function str_limit($text, $length, $ad = '...')
{
    /**
     * @var $ending
     * @var $exact
     * @var $html
     */
    $ad = is_string($ad) ? array('ending' => $ad) : $ad;
    $default = array('ending' => '...', 'exact' => true, 'html' => false);
    $options = array_merge($default, $ad);
    extract($options);

    if ($html) {
        if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        $totalLength = mb_strlen(strip_tags($ending));
        $openTags = array();
        $truncate = '';

        preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
        foreach ($tags as $tag) {
            if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                    array_unshift($openTags, $tag[2]);
                } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                    $pos = array_search($closeTag[1], $openTags);
                    if ($pos !== false) {
                        array_splice($openTags, $pos, 1);
                    }
                }
            }
            $truncate .= $tag[1];

            $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
            if ($contentLength + $totalLength > $length) {
                $left = $length - $totalLength;
                $entitiesLength = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entitiesLength <= $left) {
                            $left--;
                            $entitiesLength += mb_strlen($entity[0]);
                        } else {
                            break;
                        }
                    }
                }

                $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                break;
            } else {
                $truncate .= $tag[3];
                $totalLength += $contentLength;
            }
            if ($totalLength >= $length) {
                break;
            }
        }
    } else {
        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
        }
    }
    if (!$exact) {
        $spacepos = mb_strrpos($truncate, ' ');
        if (isset($spacepos)) {
            if ($html) {
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    foreach ($droppedTags as $closingTag) {
                        if (!in_array($closingTag[1], $openTags)) {
                            array_unshift($openTags, $closingTag[1]);
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
    }
    $truncate .= $ending;

    if ($html) {
        foreach ($openTags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
    }

    return $truncate;
}

function is_rtl($string)
{
    return false;
    $rtl_chars_pattern = '/[\x{0590}-\x{05ff}\x{0600}-\x{06ff}]/u';
    return preg_match($rtl_chars_pattern, $string);
}

function selected_is_rtl() {
    return is_rtl(lang('site-title'));
}

function isEnglish($string)
{
    if (strlen($string) != mb_strlen($string, 'utf-8')) return false;
    return true;
}

function format_bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
{
// Format string
    $format = ($format === NULL) ? '%01.2f %s' : (string)$format;

    // IEC prefixes (binary)
    if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE) {
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $mod = 1024;
    } // SI prefixes (decimal)
    else {
        $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $mod = 1000;
    }

    // Determine unit to use
    if (($power = array_search((string)$force_unit, $units)) === FALSE) {
        $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
    }

    return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
}


function sanitizeText($string, $limit = false, $output = false)
{
    if (!is_string($string)) return $string;
    $lawed_config = array(
        'safe' => 1,
        'deny_attribute' => 'id, style, class'
    );
    $string = lawedContent($string, $lawed_config);//great one
    $string = trim($string);
    if ($limit) {
        $string = substr($string, 0, $limit);
    }
    return $string;
}

function lawedContent($t, $C=1, $S=array()) {
    if (file_exists(path('includes/libraries/htmLawed.php'))) {
        require_once path('includes/libraries/htmLawed.php');
        return htmLawed($t, $C, $S);
    }

    return $t;
}

function format_output_text($content) {

    $content = str_replace("\r\n", '<br />',$content);
    $content = str_replace("\n", '<br />',$content);
    $content = str_replace("\r", '<br />',$content);
    $content = stripslashes($content);
    $content = autoLinkUrls($content);
    $content = html_entity_decode($content);
    $lawed_config = array(
        'safe' => 1,
        'deny_attribute' => 'id, style, class'
    );
    $content = lawedContent($content, $lawed_config);
    //replace bad words
    $badWords = config('ban_filters_words', '');
    if ($badWords) {
        $badWords = explode(',', $badWords);
        foreach($badWords as $word) {
            $content = str_replace($word, '***', $content);
        }
    }
    $content = format_mention($content);
    return $content;
}

function output_text($content, $options = array('html' => true, 'length' => 500, 'more' => true)) {
    /**
     * @var $html
     * @var $length
     * @var $more
     */

    extract($options);
    $content = format_output_text($content);
    $tContent = $content;
    $original = $content;


    if (is_rtl($content)) {
        $content = "<span style='direction: rtl;text-align: right;display: block'>{$content}</span>";
    }
    //too much text solution
    $id = md5($tContent.time());
    $result = "<span id='{$id}' style='font-weight: normal !important'>";
    if ($more === true) {
        if (strlen(preg_replace('/\s+/', ' ', strip_tags($tContent, '<br>'))) > $length) {
            $result .= "<span class='text-full' style='display: none;font-weight: normal'>{$content}</span>";
            $tContent = format_output_text(str_limit($tContent, $length, array('ending' => '...', 'html' => $html)));
            if (is_rtl($tContent)) $tContent = "<span style='direction: rtl;text-align: right;display:block'>{$tContent}</span>";
            $result .= "<span style='font-weight: normal !important'>".$tContent."</span>";
            $result .= '<a href="" onclick=\'return read_more(this, "'.$id.'")\'>'.lang('read-more').'</a>';
        } else {
            $result .= $content;
        }
    } elseif($more) {
        $result .= "<span class='text-full' style='display: none;font-weight: normal'>{$content}</span>";
        $tContent = format_output_text(str_limit($tContent, $length, array('ending' => '...', 'html' => $html)));
        if (is_rtl($tContent)) $tContent = "<span style='direction: rtl;text-align: right;display:block'>{$tContent}</span>";
        $result .= "<span style='font-weight: normal !important'>".$tContent."</span>";
        $result .= '<a href="'.$more.'" ajax="true">'.lang('read-more').'</a>';
    } else {
        $result .= $content;
    }

    $result .= "</span>";
    return $result;
}



function autoLinkUrls($text, $popup = true)
{
    $target = false;
    $str = $text;
    if ($target) {
        $target = ' target="' . $target . '"';
    } else {
        $target = '';
    }
    // find and replace link
    $str = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', "<a onclick=\"return window.open('http://$1')\" nofollow='nofollow' href='javascript::void(0)' {$target}>$1</a>", $str);
    // add "http://" if not set
    $str = preg_replace('/<a\s[^>]*href\s*=\s*"((?!https?:\/\/)[^"]*)"[^>]*>/i', "<a onclick=\"return window.open('$1')\" nofollow='nofollow' href='javascript::void(0)' {$target}>$1</a>", $str);
    //return $str;
    $regexB = '(?:[^-\\/"\':!=a-z0-9_@＠]|^|\\:)';
    $regexUrl = '(?:[^\\p{P}\\p{Lo}\\s][\\.-](?=[^\\p{P}\\p{Lo}\\s])|[^\\p{P}\\p{Lo}\\s])+\\.[a-z]{2,}(?::[0-9]+)?';
    $regexUrlChars = '(?:(?:\\([a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\))|@[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\/|[\\.\\,]?(?:[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_~]|,(?!\s)))';
    $regexURLPath = '[a-z0-9=#\\/]';
    $regexQuery = '[a-z0-9!\\*\'\\(\\);:&=\\+\\$\\/%#\\[\\]\\-_\\.,~]';
    $regexQueryEnd = '[a-z0-9_&=#\\/]';

    $regex = '/(?:' # $1 Complete match (preg_match already matches everything.)
        . '(' . $regexB . ')' # $2 Preceding character
        . '(' # $3 Complete URL
        . '((?:https?:\\/\\/|www\\.)?)' # $4 Protocol (or www)
        . '(' . $regexUrl . ')' # $5 Domain(s) (and port)
        . '(\\/' . $regexUrlChars . '*' # $6 URL Path
        . $regexURLPath . '?)?'
        . '(\\?' . $regexQuery . '*' # $7 Query String
        . $regexQueryEnd . ')?'
        . ')'
        . ')/iux';
//    return $text;
    return preg_replace_callback($regex, function ($matches) {

        list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
        $href = ((!$protocol || strtolower($protocol) === 'www.') ? 'http://' . $url : $url);
        //if (!$protocol && !preg_match('/\\.(?:com|net|org|gov|edu)$/iu' , $domain)) return $all;
        return $before . "<a onclick=\"return window.open('" . $href . "')\" nofollow='nofollow' href='javascript:void(0)' >" . $url . "</a>";
    }, $text);
}

function curl_get_file_size($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);

    $data = curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    curl_close($ch);
    return $size;
}

function curl_get_content($url, $javascript_loop = 0, $timeout = 100)
{
    $url = str_replace("&amp;", "&", urldecode(trim($url)));

    $cookie = tempnam("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    //curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $content = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);

    return $content;
}

function addHook($event,$callback)
{
    $hook = Hook::getInstance();
    $hook->attachOrFire($event,$values = null,$callback);
}

function runHook($event,$values = null, $param = array())
{
    $hook = Hook::getInstance();
    return $hook->attachOrFire($event,$values,$callback = null, $param);
}

function validate_recaptcha() {
    $response = input('g-recaptcha-response');
    $secret = config('captcha-secret-key');
    try{
        $response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=".$response."&remoteip=".$_SERVER['REMOTE_ADDR']), true);
        //print_r($response);
        if ($response['success']==false){
            return false;
        } else {
            return true;
        }
    } catch(Exception $e) {
        exit($e->getMessage());
    }
    return false;
}

function format_time($time) {
    $time_ago     = $time;
    $cur_time     = time();
    $time_elapsed = $cur_time - $time_ago;
    $seconds      = $time_elapsed;
    $minutes      = round($time_elapsed / 60);
    $hours        = round($time_elapsed / 3600);
    $days         = round($time_elapsed / 86400);
    $weeks        = round($time_elapsed / 604800);
    $months       = round($time_elapsed / 2600640);
    $years        = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return strtoupper(lang('time_ago_just_now'));
    }
    //Minutes
    else if ($minutes <= 60) {
        if ($minutes == 1) {
            return strtoupper(lang('time_ago_minute'));
        } else {
            return strtoupper(lang('time_ago_minutes', array('number' => $minutes)));
        }
    }
    //Hours
    else if ($hours <= 24) {
        if ($hours == 1) {
            return strtoupper(lang('time_ago_hour'));
        } else {
            return strtoupper(lang('time_ago_hours', array('number' => $hours)));
        }
    }
    //Days
    else if ($days <= 7) {
        if ($days == 1) {
            return strtoupper(lang('time_ago_yesterday'));
        } else {
            return strtoupper(lang('time_ago_days', array('number' => $days)));
        }
    }
    //Weeks
    else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return lang('time_ago_week');
        } else {
            return strtoupper(lang('time_ago_weeks', array('number' => $weeks)));
        }
    }
    //Months
    else if ($months <= 12) {
        if ($months == 1) {
            return lang('time_ago_month');
        } else {
            return strtoupper(lang('time_ago_months', array('number' => $months)));
        }
    }
    //Years
    else {
        if ($years == 1) {
            return strtoupper(lang('time_ago_year'));
        } else {
            return strtoupper(lang('time_ago_years', array('number' => $years)));
        }
    }
}

function get_file_extension($path) {
    return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}
function is_gif($path) {
    return (get_file_extension($path) == 'gif');
}

function is_image($ext) {
    return in_array(strtolower($ext), array('jpg','jpeg','gif','png'));
}

function toAscii($str, $replace=array(), $delimiter='-', $charset='ISO-8859-1') {


    $str = str_replace(
        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
        array("'", "'", '"', '"', '-', '--', '...'),
        $str); // by mordomiamil
    try{
        $str = iconv($charset, 'UTF-8', $str); // by lelebart
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }
        $clean = $str;
    } catch(Exception $e) {}

    try {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    } catch( Exception $e) {

    }

    $str = preg_replace('/[^\x{0600}-\x{06FF}A-Za-z !@#$%^&*()]/u','', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = strtolower(trim($clean, '-'));
    return $clean;
}

function request_url($url) {
    $ch = curl_init($url);
    curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, tempnam ("/tmp", "CURLCOOKIE") );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 100 );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 100 );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    $result = curl_exec($ch);
    //var_dump($result);
    $session = @json_decode($result, true);
    return $session;
}

function timeStamp($time = 'now', $offset = NULL, $inputTimezone = NULL, $outputTimezone = NULL, $format = 'Y-m-d H:i:s') {
    $offset = intval($offset);
    $datetime = $inputTimezone ? new \DateTime($time, new \DateTimeZone($inputTimezone)) : new \DateTime($time);
    // apply offset in seconds
    if ($offset>0) {
        $datetime->add(new \DateInterval('PT'.$offset.'S'));
    } elseif ($offset<0) {
        $datetime->sub(new \DateInterval('PT'.abs($offset).'S'));
    }
    if ($outputTimezone) {
        $outTz = new \DateTimeZone($outputTimezone);
        $datetime->setTimezone($outTz);
    }
    return $datetime->format($format);
}


function formatNumber($input, $decimals = 'auto', $prefix = '', $suffix = '') {
    $input = floatval($input);
    $absInput = abs($input);
    if ($decimals === 'auto') {
        if ($absInput >= 0.01) {
            $decimals = 2;
        } elseif (0.0001 <= $absInput && $absInput < 0.01) {
            $decimals = 4;
        } elseif (0.000001 <= $absInput && $absInput < 0.0001) {
            $decimals = 6;
        } elseif ($absInput < 0.000001) {
            $decimals = 8;
        }
    }
    return ($prefix ? $prefix : '') . number_format($input, $decimals, config('decimal-separator','.'), config('thousand-separator', ',')) . ($suffix ? $suffix : '');
}


function download_file($path, $baseName = null, $speed = null) {
    if (!$path) return false;
    if (!preg_match("#storage#", $path)) return false;
    if (is_file($path) === true or preg_match("#http://#", $path) or preg_match("#https://#", $path))
    {
        @set_time_limit(0);
        while (ob_get_level() > 0)
        {
            ob_end_clean();
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $baseName = ($baseName) ? $baseName : md5(basename($path).time().time());
        $basename = $baseName.'.'.$ext;
        $size =  sprintf('%u', filesize($path));
        $speed = (is_null($speed) === true) ? $size : intval($speed) * 1024;

        header('Expires: 0');
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/octet-stream');
        header('Content-Length: '.$size);
        header('Content-Disposition: attachment; filename="'.$basename.'"');
        header('Content-Transfer-Encoding: binary');

        for ($i = 0; $i <= $size; $i = $i + $speed)
        {
            echo file_get_contents($path, false, null, $i, $speed);

            while (ob_get_level() > 0)
            {
                ob_end_clean();
            }

            flush();
            sleep(1);
        }

        //exit();
    }
}
