<?php
function post_add($photos,$content, $urlDetails) {
    $time = time();
    $coinId = input('coin_id');
    $postType = input('post_type', 'coin');

    db()->query("INSERT INTO posts (user_id,coin_id,images,content,url_details,date_created,post_type)VALUES(?,?,?,?,?,?,?)",
        get_userid(),$coinId,perfectSerialize($photos),$content,perfectSerialize($urlDetails), $time,$postType);
    $id = db()->lastInsertId();
    process_mention($content, $id);
    process_point('add-post-point');
    db()->query("UPDATE coins SET post_count = post_count +1 WHERE id=?", $coinId);
    return $id;
}

function get_top_coins() {
    $query = db()->query("SELECT * FROM coins ORDER BY post_count DESC LIMIT 5");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}


function save_post($id, $caption) {
    if (!can_edit_post(get_post($id))) return false;
    return db()->query("UPDATE posts SET content=? WHERE id=?", $caption, $id);
}

function get_post($id) {
    $query = db()->query("SELECT * FROM posts WHERE id =? ", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function get_posts($offset = 0, $userid = null, $page = null, $limit  = null) {
    $limit = (!$limit) ? config('post-per-page', 10) : $limit;
    $sql = "SELECT * FROM posts WHERE id != '' ";

    $param = array();
    if ($userid) {
        $sql .= " AND user_id=? ";
        $param[] = $userid;
    }

    if ($page ) {
        list($type, $typeId) = explode('|',$page);
        $sql .= " AND coin_id=? AND post_type=?";
        $param[] = $typeId;
        $param[] = $type;
    }

    $sql .= " ORDER by id DESC LIMIT {$limit} OFFSET $offset ";

    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);

}


function can_edit_post($post) {
    if (get_userid() == $post['user_id']) return true;
    if (is_admin() or is_moderator()) return true;
    return false;
}

function delete_post($id) {
    $post = get_post($id);

    if ($post['images']) {
        $images = perfectUnserialize($post['images']);
        if($images) {
            foreach($images as $image) {
                delete_file(path($image));
            }
        }
    }
    db()->query("UPDATE coins SET post_count = post_count -1 WHERE id=?", $post['coin_id']);
    db()->query("DELETE FROM posts WHERE id=?", $id);
    db()->query("DELETE FROM comments WHERE post_id=?", $id);
    db()->query("DELETE FROM likes WHERE post_id=?", $id);
    process_point('like-post-point', true,$post['user_id']);
    return true;
}

function count_posts($userid) {
    $query = db()->query("SELECT id FROM posts WHERE  user_id=?",  $userid);
    return $query->rowCount();
}

function can_edit_comment($comment) {
    if (get_userid() == $comment['user_id']) return true;
    if (is_admin() or is_moderator()) return true;
    return false;
}

function has_liked($postId) {
    $query = db()->query("SELECT id FROM likes WHERE user_id=? AND post_id=?", get_userid(), $postId);
    return $query->rowCount();
}

function count_likes($postId) {
    $query = db()->query("SELECT id FROM likes WHERE  post_id=?",  $postId);
    return $query->rowCount();
}

function like($postId) {
    if (!has_liked($postId)) {
        db()->query("INSERT INTO likes (post_id,user_id)VALUES(?,?)", $postId, get_userid());
        $post = get_post($postId);
        add_notification($post['user_id'], 'like-post', $postId);
        process_point('like-post-point');
    } else{
        db()->query("DELETE FROM likes WHERE post_id=? AND user_id=?", $postId, get_userid());
        process_point('like-post-point', true);
    }
}

function add_comment($id,$type, $comment) {
    db()->query("INSERT INTO comments (post_id,comment_type,user_id,comment,date_created)VALUES(?,?,?,?,?)", $id,$type,get_userid(),$comment,time());
    $postId = $id;
    $id = db()->lastInsertId();
    if ($type == 'post') {
        $post = get_post($postId);
        $members = array();
        $usersQuery = db()->query("SELECT DISTINCT(user_id) as user_id FROM comments WHERE post_id=? ", $postId);
        while($fetch = $usersQuery->fetch(PDO::FETCH_ASSOC)) {
            $members[] = $fetch['user_id'];
        }

        if ($post['user_id'] != get_userid()) {
            add_notification($post['user_id'], 'comment-post-your', $postId);
        }
        process_point('add-comment-point');
        foreach($members as $member) {
            if ($member != get_userid() and $member != $post['user_id']) {
                add_notification($member, 'comment-post', $postId);
            }
        }
        process_mention($comment,$postId, 'comment');
    }


    return $id;
}
function get_comment($id) {

    $query = db()->query("SELECT * FROM comments WHERE id =? ", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}
function get_comments($id,$type = 'post', $offset = 0) {
    $limit = config('comment-per-page', 5);
    $sql = "SELECT * FROM comments WHERE post_id=? AND comment_type=? ";

    $sql .= " ORDER by id DESC LIMIT {$limit} OFFSET $offset ";
    $param = array($id,$type);
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}
function count_comments($id, $type = 'post') {
    $query = db()->query("SELECT id FROM comments WHERE post_id=? AND comment_type=?", $id,$type);
    return $query->rowCount();
}
function remove_comment($id) {
    $comment = get_comment($id);
    if(!can_edit_comment($comment)) return false;
    process_point('like-post-point', true, $comment['user_id']);
   return db()->query("DELETE FROM comments WHERE id=?", $id);
}

function count_all_posts() {
    $query = db()->query("SELECT * FROM posts");
    return $query->rowCount();
}

function count_all_comments() {
    $query = db()->query("SELECT * FROM comments");
    return $query->rowCount();
}

function count_all_likes() {
    $query = db()->query("SELECT * FROM likes");
    return $query->rowCount();
}

function process_mention($caption, $id, $type = 'post') {
    preg_match_all('/(^|\s)(@\w+)/', $caption, $matches);
    $mentions = array_map('trim', $matches[0]);
    foreach($mentions as $mention) {
        $username = str_replace('@', '', $mention);
        $user = get_user($username);
        if ($user and $user['id'] != get_userid()) {
            //send notification
            $key = 'mention-you';
            if ($type == 'comment')  $key = 'mention-you-comment';
            add_notification($user['id'], $key, $id);
        }
    }
    return true;
}

function format_mention($text) {
    preg_match_all('/(^|\s)(@\w+)/', $text, $matches);
    $mentions = array_map('trim', $matches[0]);
    foreach($mentions as $mention) {
        $username = str_replace('@', '', $mention);
        $user = get_user($username);
        if ($user) {
            $link = profile_link($user);
            $text = str_replace($mention, "<a href='$link' class='ajax-link'>$mention</a>", $text);
        }
    }

    preg_match_all("/(#\w+)/u", $text, $matches);
    if ($matches) {
        $hashtagsArray = array_count_values($matches[0]);
        $hashtags = array_keys($hashtagsArray);
        foreach($hashtags as $hashtag) {
            $tag = str_replace('#','', $hashtag);
            if (!empty($tag)) {
                $link = url('explore/tag?t='.$tag);
                $text = str_replace($hashtag, "<a href='$link' class='ajax-link'>$hashtag</a>", $text);
            }
        }
    }


    return $text;
}
