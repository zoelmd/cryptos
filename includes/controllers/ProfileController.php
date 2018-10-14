<?php
class ProfileController extends Controller {
    public function index() {
        $user = get_user();
        return $this->render(view('main::profile/index'));
    }

    public function follow() {
        return $this->render(view('main::profile/follow'));
    }
    public function favourites() {
        return $this->render(view('main::profile/favourites'));
    }

    public function processFollow() {
        $type = input('type');
        $id = input('id');
        $entityType = input('entity_type');
        if ($type == 'follow') {
            follow($id, $entityType);
        } else {
            unFollow($id,null, $entityType);
        }
        $user = get_coin($id);
        return ($type == 'follow') ? lang('you-have-started-following', array('name' => $user['name'])) : lang('you-have-unfollow', array('name' => $user['name']));
    }

    public function render($content) {
        return parent::render(view('main::profile/layout', array('content' => $content)));
    }
}