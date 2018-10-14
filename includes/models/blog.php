<?php
function get_blog_list($offset, $term) {
    $sql = "SELECT * FROM blogs WHERE id!='' ";
    if ($term) {
        $sql .= " AND (name LIKE '%$term%' OR symbol LIKE '%$term%') ";
    }
    $sql .= " ORDER BY id DESC LIMIT 50 OFFSET $offset ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function save_blog($id = null) {
    $title = input('title');
    $desc = input('description');
    $content = input('content');
    $image = '';
    if ($file = input_file('image')) {
        $uploader = new Uploader($file);
        $uploader->setPath("blogs/");
        if ($uploader->passed()) {
            $image = $uploader->resize(900)->result();
        }
    }

    if ($id) {
        db()->query("UPDATE blogs SET title=?,description=?,content=?,edited_date=? WHERE id=? ", $title,$desc,$content,time(),$id);
        if ($image) {
            db()->query("UPDATE blogs SET image=? WHERE id=? ", $image, $id);
        }
    } else {
        $slug = toAscii($title);
        $slug = ($slug) ? $slug : md5(time());
        db()->query("INSERT INTO blogs (slug,title,description,content,image,date_created)VALUES(?,?,?,?,?,?)",$slug,$title,$desc,$content,$image,time());
    }

    return true;
}

function blog_url($blog) {
    return url('blog/'.$blog['id'].'/'.$blog['slug']);
}

function get_blog($id) {
    $query = db()->query("SELECT * FROM blogs WHERE id=? ", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function delete_blog($id) {
    $blog = get_blog($$id);
    if ($blog['image']) delete_file(path($blog['image']));
    db()->query("DELETE FROM blogs WHERE id=?", $id);
    return true;
}
function blog_logo($blog) {
    return url($blog['image']);
}
function unique_views($type,$typeId) {
    $query = db()->query("SELECT ip FROM unique_views WHERE view_type=? AND type_id=? ", $type, $typeId);
    return $query->rowCount();
}

function add_unique_views($type, $typeId, $func = null) {
    $ip = get_ip();
    $query = db()->query("SELECT ip FROM unique_views WHERE view_type=? AND type_id=? AND ip=? ", $type, $typeId, $ip);
    if (!$query->rowCount()) {
        db()->query("INSERT INTO unique_views (ip,view_type,type_id)VALUES(?,?,?)",$ip,$type,$typeId);
        if ($func) call_user_func($func);
    }
    return true;
}