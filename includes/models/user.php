<?php
function is_loggedin() {
    return app()->authId;
}

function get_userid() {
    return app()->authId;
}

function get_user($id = null) {
    $user = app()->authUser;
    if ($id) {
        $query = db()->query("SELECT * FROM members WHERE id=? OR username=? OR email=?", $id,$id,$id);
        if ($query) $user = $query->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}


function check_username($username, $id = null) {
    $sql = "SELECT id FROM members WHERE username=? ";
    $param = array($username);
    if ($id) {
        $sql .= " AND id != ?";
        $param[] = $id;
    }

    $query = db()->query($sql, $param);
    return $query->rowCount();
}

function username_is_bad($username) {
    $bads = array('admin', 'administrator','explore','tags','people','messages','profile');
    if (is_numeric($username)) return true;
    $valid = preg_match('/^[\pL\pN_-]+$/u', $username);
    $slug = toAscii($username);

    if(!$valid or empty($slug)  or strlen($username) != strlen($slug)) return true;
    return in_array($username, $bads);
}


function check_email($email, $id = null) {
    $sql = "SELECT id FROM members WHERE email=? ";
    $param = array($email);
    if ($id) {
        $sql .= " AND id != ?";
        $param[] = $id;
    }

    $query = db()->query($sql, $param);
    return $query->rowCount();
}

function register_member($username,$name,$email,$pass) {
    $password = hash_make($pass);
    $time = time();
    $ip = get_ip();
    $active = (config('enable-account-activation')) ? 0 : 1;
    db()->query("INSERT INTO members (full_name,username,email,password,ip_address,date_created,banned,active,activated,hash)VALUES(?,?,?,?,?,?,?,?,?,?)",
        $name,$username,$email,$password, $ip, $time,0,$active,$active,'dsd');
    //login user
    $userid = db()->lastInsertId();
    if (!config('enable-account-activation', false)) {
        login_user($username, $pass, false);

        if (config('auto-follow', '')) {
            $users = explode(',', config('auto-follow'));
            foreach($users as $user){
                $coin = get_coin($user);
                follow($coin['id'], 'coin');
            }
        }
    }
    return $userid;
}

function process_login() {
    $username = "";
    $password = "";
    if (isset($_COOKIE['username']) and isset($_COOKIE['user_token'])) {
        $username = $_COOKIE['username'];
        $password = $_COOKIE['user_token'];
    }
    if (isset($_SESSION['username']) and isset($_SESSION['user_token'])) {
        $username = $_SESSION['username'];
        $password = $_SESSION['user_token'];
    }

    $query = db()->query("SELECT * FROM members WHERE id = ? AND password = ? ", $username, $password);
    $result = $query->fetch(\PDO::FETCH_ASSOC);

    if (!$result) return false;

    //@TODO - Other processes for specific auth types
    app()->authId = $result['id'];
    app()->authUser = $result;


    save_login_data($result['id'], $result['password']);
    return true;
}
process_login();
function login_user($username, $password, $ban = true) {
    $query = db()->query("SELECT * FROM members WHERE email = ?  OR username = ?", $username, $username);
    $result = $query->fetch(\PDO::FETCH_ASSOC);

    if (!$result) return false;
    if (!hash_check($password, $result['password'])) return false;
    if ($ban and ($result['banned'] or !$result['active'] or !$result['activated'])) return false;
    app()->authId = $result['id'];
    app()->authUser = $result;
    save_login_data($result['id'], $result['password']);
    return true;
}

function login_with_user($result) {
    app()->authId = $result['id'];
    app()->authUser = $result;
    save_login_data($result['id'], $result['password']);
    return true;
}

function save_login_data($id, $password) {
    session_put("username", $id);
    session_put("user_token", $password);
    setcookie("username", $id, time() + 30 * 24 * 60 * 60, config('cookie_path'));
    setcookie("user_token", $password, time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
}

function logout_user() {
    unset($_SESSION['username']);
    unset($_SESSION['user_token']);
    unset($_COOKIE['username']);
    unset($_COOKIE['user_token']);
    setcookie("username", "", 1, config('cookie_path'));
    setcookie("user_token", "", 1, config('cookie_path'));
}

function send_activation_link($user) {
    $hash = md5('djsdfsjkfsd1234233'.time());
    db()->query("UPDATE members SET hash=? WHERE id=?", $hash, $user['id']);
    $link = "<a href='".url('activate/account?hash='.$hash)."'>".url('activate/account?hash='.$hash).'</a>';

    $message  = lang('account-activate-message', array('link' => $link));
    Email::getInstance()->setAddress($user['email'])
        ->setSubject(lang('account-activation'))
        ->setMessage($message)->send();
}

function update_user_avatar($avatar) {
    return db()->query("UPDATE members SET avatar=? WHERE id=?", $avatar, get_userid());
}

function update_user_password($new) {
    $password = hash_make($new);
    session_put("user_token", $password);
    setcookie("user_token", $password, time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
    return db()->query("UPDATE members SET password=? WHERE id=?", $password, get_userid());
}

function update_user_details($fullname,$website,$bio,$username,$email) {
    return db()->query("UPDATE members SET full_name=?,website=?,bio=?,username=?,email=? WHERE id=?", $fullname,$website,$bio,$username,$email, get_userid());
}



function search_user($term) {
    $term = '%'.$term.'%';
    $query = db()->query("SELECT * FROM members WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?  LIMIT 100 ", $term,$term,$term);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}



function get_latest_members($limit, $offset =  0) {
    $sql = "SELECT * FROM members ";


    $sql .= " ORDER BY id DESC LIMIT {$limit} OFFSET {$offset} ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}



function get_avatar($size = 75, $user = null) {
    $user = ($user) ? $user : get_user();
    //return url('assets/images/avatar.png');
    if (!$user['avatar']) return url('assets/images/avatar.png');
    return url(str_replace('%w', $size, $user['avatar']));
}

function profile_link($user = null, $s = null) {
    $user = ($user) ? $user : get_user();
    $url = $user['username'];
    if ($s) $url .= "/".$s;
    return url($url);
}

function profile_owner() {
    if (!is_loggedin()) return false;
    if (app()->profileId == get_userid()) return true;
    return false;
}

function count_follow($id, $following = true,$type) {
    if ($following) {
        $query = db()->query("SELECT id FROM follow WHERE following_id=? AND entity_type=?", $id,$type);
    } else {
        $query = db()->query("SELECT id FROM follow WHERE follow_id=? AND entity_type=?", $id,$type);
    }
    return $query->rowCount();
}
function get_following($id , $idsOnly = false,$type ) {
    $query = db()->query("SELECT follow_id FROM follow WHERE following_id=? AND entity_type=? ", $id, $type);
    $ids = array();
    while($f = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $f['follow_id'];
    }
    if (!$ids) return array();
    if ($idsOnly) return $ids;
    $ids = implode(',', $ids);
    $query = db()->query("SELECT * FROM members WHERE id IN ($ids)");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_followers($id , $type, $limit = null) {
    $sql = "SELECT following_id FROM follow WHERE follow_id=? AND entity_type=? ";

    if ($limit)  $sql .= " LIMIT $limit ";
    $query = db()->query($sql, $id, $type);
    $ids = array();
    while($f = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $f['following_id'];
    }
    if (!$ids) return array();
    $ids = implode(',', $ids);
    $query = db()->query("SELECT * FROM members WHERE id IN ($ids)");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function is_following($id, $type) {
    $query = db()->query("SELECT id FROM follow WHERE follow_id=? AND following_id=? AND entity_type=?", $id, get_userid(), $type);
    return $query->rowCount();
}

function follow($id, $type) {
    if ($type == 'user') add_notification($id, 'follow-you','');
    process_point('add-follow-point');
    db()->query("INSERT INTO follow (follow_id,following_id,entity_type)VALUES(?,?,?)", $id, get_userid(),$type);
    return true;
}
function unFollow($id, $userid = null, $type) {
    $userid = ($userid) ? $userid : get_userid();
    process_point('add-follow-point', true);
    db()->query("DELETE FROM follow WHERE follow_id=? AND following_id=? AND entity_type=?", $id, $userid,$type);
    return true;
}

function get_notifications($offset = 0, $limit = 10) {
    $sql = "SELECT * FROM notifications WHERE user_id=?  ";
    $param = array(get_userid());

    $sql .= "ORDER BY id desc LIMIT {$limit} OFFSET {$offset}";
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function count_unread_notifications() {
    $query = db()->query("SELECT id FROM notifications WHERE user_id=? AND seen=?", get_userid(), 0);
    return $query->rowCount();
}

function add_notification($userid,$key,$postId = null) {
    return db()->query("INSERT INTO notifications (user_id,from_userid,post_id,notification_key,date_created)VALUES(?,?,?,?,?)",
        $userid, get_userid(),$postId,$key,time());
}

function mark_notification($id) {
    return db()->query("UPDATE notifications SET seen=? WHERE id=?", 1, $id);
}

function report_content($type,$id, $text) {
    return db()->query("INSERT INTO reports (user_id,type,type_id,message,date_created)VALUES(?,?,?,?,?)", get_userid(),$type,$id,$text,time());
}

function get_reports() {
    return db()->query("SELECT * FROM reports ORDER BY date_created DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function user_has_pending_request() {
    $query = db()->query("SELECT id FROM verification_request WHERE user_id=?", get_userid());
    return $query->rowCount();
}

function get_verification_requests() {
    $query = db()->query("SELECT * FROM verification_request WHERE processed=? ORDER BY id DESC",0);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function add_verification_request($name, $message, $photo1, $photo2) {
    return db()->query("INSERT INTO verification_request (name,message,photo_1,photo_2,user_id)VALUES(?,?,?,?,?)",
        $name,$message,$photo1,$photo2,get_userid());
}

function process_verification_request($type, $id) {
    $query = db()->query("SELECT * FROM verification_request WHERE id=?", $id);
    $request = $query->fetch(PDO::FETCH_ASSOC);
    if ($type == 'accept') {
        db()->query("UPDATE members SET verified=? WHERE id=? ", 1, $request['user_id']);
    }
    db()->query("UPDATE verification_request SET processed=? WHERE id=?",1, $id);
    return true;
}

function count_users() {
    $query = db()->query("SELECT * FROM members");
    return $query->rowCount();
}

function delete_user($id) {
    $user = get_user($id);
    if($user['avatar']) @delete_file(path($user['avatar']));
    $query = db()->query("SELECT id FROM posts WHERE user_id=?", $id);
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        delete_post($fetch['id']);
    }
    //db()->query("DELETE FROM messages WHERE user_id=?", $id);
    //db()->query("DELETE FROM conversations WHERE userid_1=?  OR userid_2=?", $id,$id);
    db()->query("DELETE FROM reports WHERE user_id=?", $id);
    db()->query("DELETE FROM user_badges WHERE user_id=?", $id);
    db()->query("DELETE FROM notifications WHERE user_id=? OR from_userid=?", $id,$id);
    db()->query("DELETE FROM follow WHERE follow_id=? ", $id);
    //db()->query("DELETE FROM favourites WHERE user_id=?", $id);
    db()->query("DELETE FROM members WHERE id=?", $id);
    return true;
}

function get_top_members() {
    $limit = config('top-members-limit', 10);
    $query = db()->query("SELECT * FROM members ORDER BY point DESC LIMIT $limit ");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_users_list($offset, $term) {
    $sql = "SELECT * FROM members WHERE id!='' ";
    if ($term) {
        $sql .= " AND (full_name LIKE '%$term%' OR username LIKE '%$term%') ";
    }
    $sql .= " ORDER BY id DESC LIMIT 50 OFFSET $offset ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function is_light_off() {
    $type = (isset($_COOKIE['turn-light'])) ? $_COOKIE['turn-light'] : 'on';
    return ($type == 'off') ? true : false;
}