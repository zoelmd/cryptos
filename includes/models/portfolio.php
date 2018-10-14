<?php
function save_portfolio($name,$desc,$privacy,$id) {
    $slug = toAscii($name);
    if (!$slug) $slug = md5(time().$name);
    if (!$id) {
        db()->query("INSERT INTO portfolios (user_id,name,description,slug,privacy,date_created)VALUES(?,?,?,?,?,?)",
            get_userid(),$name,$desc,$slug,$privacy,time());
        $id = db()->lastInsertId();
    } else {
        $portfolio = get_portfolio($id);
        if ($portfolio['user_id'] == get_userid()) {
            db()->query("UPDATE portfolios SET name=?,description=?,privacy=? WHERE id=?", $name,$desc,$privacy,$id);
        }
    }
    $portfolio = get_portfolio($id);
    return portfolio_link($portfolio);
}

function save_portfolio_coin($id,$purchase,$sold,$amount,$rate,$date) {
    $portfolio = get_portfolio($id);
    if ($portfolio['user_id'] == get_userid()) {
        db()->query("INSERT INTO portfolio_transactions (portfolio_id,base,quote,amount,rate,date_created)VALUES(?,?,?,?,?,?)",
            $id,$purchase,$sold,$amount,$rate,$date);
        $portfolio = get_portfolio($id);

        $check = db()->query("SELECT id FROM portfolio_transactions WHERE base=? AND portfolio_id=?", $purchase,$id);
        if ($check->rowCount() < 2) {
            db()->query("UPDATE portfolios SET coins_count = coins_count + 1 WHERE id=?" , $portfolio['id']);
        }
        return portfolio_link($portfolio);
    }

}

function get_portfolio($id) {
    $query = db()->query("SELECT * FROM portfolios WHERE id=?", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function portfolio_link($portfolio) {
    return url('portfolio/'.$portfolio['id'].'/'.$portfolio['slug']);
}

function get_view_portfolio($page) {
    if ($page == 'home') {
        $query = db()->query("SELECT * FROM portfolios WHERE user_id=? ORDER BY id DESC", get_userid());
        return $query->fetch(PDO::FETCH_ASSOC);
    } else {
        return get_portfolio($page);
    }
}

function get_portfolios($privacy, $offset = 0, $order = 'id') {
    $sql = "SELECT * FROM portfolios WHERE id !='' ";
    $param = array();
    if ($privacy == 0) {
        $sql .= " AND (user_id=?) ";
        $param[] = get_userid();
    }

    if ($privacy == 1) {
        $sql .= " AND (privacy=?)  ";
        $param[] = 1;
    }

    $sql .= " ORDER BY $order DESC LIMIT 50 OFFSET $offset ";
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_portfolio_coins($id) {
    $query = db()->query("SELECT * FROM portfolio_transactions WHERE portfolio_id=? ORDER BY id DESC", $id);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_unique_portfolio_coins($id) {
    $query = db()->query("SELECT DISTINCT(base) FROM portfolio_transactions WHERE portfolio_id=? ", $id);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_portfolio_coin_detail($id, $coin) {
    $query = db()->query("SELECT count(id) as coin_count,sum(amount) as quantity,sum(rate) as total_rate FROM portfolio_transactions WHERE portfolio_id=? AND base=?", $id, $coin);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function delete_portfolio($id) {
    db()->query("DELETE FROM portfolio_transactions WHERE portfolio_id=? ", $id);
    db()->query("DELETE FROM portfolios WHERE id=? ", $id);
    return true;
}
