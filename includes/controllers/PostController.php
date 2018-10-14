<?php
class PostController extends Controller {
    public function add() {
        $result = array(
            'status' => 0,
            'message' => lang('post-empty-error'),
            'post' => ''
        );
        $image = input_file('photo');

        $content = input('content');


        if (!$image and !$content) return json_encode($result);
        $photos = array();
        if ($image) {
            $uploader = new Uploader($image);
            $uploader->setPath('posts/photo/');
            if ($uploader->passed()) {
                $photos[] = $uploader->resize(700)->result();
            } else {
                $result['message'] = $uploader->getError();
                return json_encode($result);
            }
        }


        $url = '';
        $urlDetails = array();
        if ($url) {
           try{
               include_once path('includes/libraries/embed/autoloader.php');
               $info = \Embed\Embed::create($url);
               //var_dump($info->code);

           } catch(Exception $e) {
                //its suppose to be done silently
           }
        }


        $id = post_add($photos,$content,$urlDetails);
        $post = get_post($id);
        $result['status']= 1;
        $result['message'] = lang('posted-success');
        $result['post'] = view('main::post/each', array('post' => $post,'scope' => false));
        return json_encode($result);
    }

    public function ffmpegExists()  {
        if(!function_exists('shell_exec')) return false;
        $ffmpeg = trim(shell_exec('which ffmpeg'));
        if(empty($ffmpeg)) return false;
        return true;
    }

    public function like() {
        like(input('id'));
        return json_encode(array('count' => count_likes(input('id'))));
    }

    public function favourite() {
        $result = favourite(input('id'));
        return json_encode(array('message' => ($result) ? lang('add-to-favourite-list') : lang('remove-favourite-list')));
    }

    public function addComment() {
        $id = input('id');
        $comment = input('comment');
        $commentId = add_comment($id, input('type'),$comment);
        return view('main::post/comment', array('comment' => get_comment($commentId)));
    }

    public function removeComment() {
        remove_comment(input('id'));
    }

    public function moreComment() {
        $offset = input('offset') + config('comment-per-page', 5);
        $comments = get_comments(input('id'),input('type', 'post'), $offset);
        $comments = array_reverse($comments);
        $content = '';
        foreach($comments as $comment) {
            $content .= view('main::post/comment', array('comment' => $comment));
        }
        $result = array(
            'offset' => $offset,
            'content' => $content
        );
        return json_encode($result);
    }

    public function page() {
        $post = get_post(segment(1));
        if (!$post) redirect(url());
        if ($post['content']) {
            $this->setTitle($post['content']);
        } else {
            $user = get_user($post['user_id']);
            $this->setTitle($user['full_name']);
        }
        $prev = false;
        $next = false;
        return $this->render(view('main::post/page', array('post' => $post , 'prev' => $prev, 'next' => $next )));
    }



    public function savePost() {
        $caption = input('caption');
        $tags = input('tags');
        save_post(input('id'), $caption);
        return lang('post-saved-success');
    }

    public function deletePost() {
        delete_post(input('id'));
        return lang('post-deleted-success');
    }

    public function morePost() {
        $userid = input('userid');
        $page = input('page');
        $type = input('type');
        $offset = input('offset');
        $newOffset = $offset + config('post-per-page', 10);
        $posts = get_posts($newOffset, $userid, $page);

        $result = array(
            'offset' => $newOffset,
            'content' => ''
        );
        foreach($posts as $post){
            $result['content'] .=  view('main::post/each', array('post' => $post)) ;
        }
        return json_encode($result);
    }


}