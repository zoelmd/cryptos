<?php
class BlogController extends Controller {
    public function index() {
        app()->activeMenu = 'blog';
        $offset = input('offset', 0);
        $term = input('term');
        $this->setTitle(lang('blog'));
        $blogs = get_blog_list($offset, $term);
        return $this->render(view('main::blog/index', array('blogs' => $blogs)));
    }

    public function page() {
        app()->activeMenu = 'blog';
        $blog = get_blog(segment(1));
        db()->query("UPDATE blogs SET views = views + 1 WHERE id=? ", $blog['id']);
        add_unique_views('blog', $blog['id']);
        $pageTitle = $blog['title'];
        $pageDesc = $blog['description'];

        $metas = "<meta property='og:url' content='".blog_url($blog)."'/>";
        $metas .= "<meta property='og:type' content='article'/>";
        $metas .= "<meta property='og:title' content='$pageTitle'/>";
        $metas .= "<meta property='og:description' content='$pageDesc'/>";
        $metas .= "<meta property='og:image' content='".blog_url($blog)."'/>";

        Layout::getInstance()->addHeaderContent($metas);

        $this->setTitle($blog['title']);
        return $this->render(view('main::blog/page', array('blog' => $blog)));
    }
}