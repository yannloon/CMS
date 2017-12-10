<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class NewsFeed extends LC_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    function __construct() {
        parent::__construct();
        
        $this->load->model('newsfeed_model');
        $this->data['perm'] = $this->set_permission(array('report_abuse'));
        $this->load->model('notification_model');
    }
    
    public function index($id = 0) {
        $this->data['newsfeed'] = $this->newsfeed_model->get_newsfeed($this->user['BuildingID'], 1, 5, $id);
        $count = 0;
        if (isset($this->data['newsfeed']['results'])) {
            foreach ($this->data['newsfeed']['results'] as $key => $list) {
                $this->data['user'][$count] = $this->newsfeed_model->get_user($list->fldUpdatedBy);
                if (!empty($this->data['user'][$count])) {
                    $this->data['image'][$count] = $this->data_model->get_image($this->data['user'][$count]->fldUserID, 1);
                }
                $this->data['like'][$count] = $this->newsfeed_model->get_likes($list->fldNewsFeedID);
                $this->data['selfLike'][$count] = $this->newsfeed_model->get_self_like($list->fldNewsFeedID, $this->user['Id']);
                $this->data['file'][$count] = $this->data_model->get_images($list->fldNewsFeedID, 4);
                $this->data['isImage'][$count] = false;
                if (isset($this->data['file'][$count][0]->fldThumbnailImage)) {
                    if (preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)$/', $this->data['file'][$count][0]->fldThumbnailImage)) {
                        $this->data['isImage'][$count] = true;
                    }
                } else {
                    $this->data['file'][$count] = $this->data_model->get_documents($list->fldNewsFeedID, 2);
                }
                $this->data['time'][$count] = $this->time_elapsed_string($list->fldCreatedDate);
                $count++;
            }
        }
        
        $count2 = 0;
        if (isset($this->data['newsfeed']['results'])) {
            foreach ($this->data['newsfeed']['results'] as $key => $list) {
                $this->data['comments'][$count2] = $this->newsfeed_model->get_comments($list->fldNewsFeedID, 1, 2);
                $count3 = 0;
                if (isset($this->data['comments'][$count2]['comment'])) {
                    foreach ($this->data['comments'][$count2]['comment'] as $key2 => $list2) {
                        $this->data['commentuser'][$count2][$count3] = $this->newsfeed_model->get_user($list2->fldUserID);
                        if (!empty($this->data['commentuser'][$count2][$count3])) {
                            $this->data['commentTime'][$count2][$count3] = $this->time_elapsed_string($list2->fldLastUpdDate);
                            if (!empty($this->data['commentuser'][$count2][$count3])) {
                                $this->data['commentImage'][$count2][$count3] = $this->data_model->get_image($this->data['commentuser'][$count2][$count3]->fldUserID, 1);
                            }
                        }
                        $count3++;
                    }
                }
                if (isset($this->data['comments'][$count2]['total'])) {
                    $this->data['totalComments'][$count2] = $this->data['comments'][$count2]['total'];
                }
                $count2++;
            }
        }
        
        $this->data['single'] = empty($id) ? FALSE : TRUE;
        
        $this->output("newsfeed/newsfeed");
    }
    
    public function reloadListing() {
        $pageno = $this->input->post('pageno');
        
        $this->data['newsfeed'] = $this->newsfeed_model->get_newsfeed($this->user['BuildingID'], $pageno, 5);
        $count = 0;
        if (isset($this->data['newsfeed']['results'])) {
            foreach ($this->data['newsfeed']['results'] as $key => $list) {
                $this->data['user'][$count] = $this->newsfeed_model->get_user($list->fldUpdatedBy);
                if (!empty($this->data['user'][$count])) {
                    $this->data['image'][$count] = $this->data_model->get_image($this->data['user'][$count]->fldUserID, 1);
                }
                $this->data['like'][$count] = $this->newsfeed_model->get_likes($list->fldNewsFeedID);
                $this->data['selfLike'][$count] = $this->newsfeed_model->get_self_like($list->fldNewsFeedID, $this->user['Id']);
                $this->data['file'][$count] = $this->data_model->get_images($list->fldNewsFeedID, 4);
                $this->data['isImage'][$count] = false;
                if (isset($this->data['file'][$count][0]->fldThumbnailImage)) {
                    if (preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)$/', $this->data['file'][$count][0]->fldThumbnailImage)) {
                        $this->data['isImage'][$count] = true;
                    }
                } else {
                    $this->data['file'][$count] = $this->data_model->get_documents($list->fldNewsFeedID, 2);
                }
                $this->data['time'][$count] = $this->time_elapsed_string($list->fldCreatedDate);
                $count++;
            }
        }
        
        $count2 = 0;
        if (isset($this->data['newsfeed']['results'])) {
            foreach ($this->data['newsfeed']['results'] as $key => $list) {
                $this->data['comments'][$count2] = $this->newsfeed_model->get_comments($list->fldNewsFeedID, 1, 2);
                $count3 = 0;
                if (isset($this->data['comments'][$count2]['comment'])) {
                    foreach ($this->data['comments'][$count2]['comment'] as $key2 => $list2) {
                        $this->data['commentuser'][$count2][$count3] = $this->newsfeed_model->get_user($list2->fldUserID);
                        if (!empty($this->data['commentuser'][$count2][$count3])) {
                            $this->data['commentTime'][$count2][$count3] = $this->time_elapsed_string($list2->fldLastUpdDate);

                            if (!empty($this->data['commentuser'][$count2][$count3])) {
                                $this->data['commentImage'][$count2][$count3] = $this->data_model->get_image($this->data['commentuser'][$count2][$count3]->fldUserID, 1);
                            }
                        }
                        $count3++;
                    }
                }
                if (isset($this->data['comments'][$count2]['total'])) {
                    $this->data['totalComments'][$count2] = $this->data['comments'][$count2]['total'];
                }
                $count2++;
            }
        }
        $this->output();
    }
    
    public function postNewsFeed() {
        $newsfeedInput = $this->input->post('newsfeedInput');
        
        $data = $this->newsfeed_model->post_newsfeed($this->user['BuildingID'], $this->user['Id'], $newsfeedInput);
        $this->set_data($data);
        $this->data['time'] = $this->time_elapsed_string($this->data['newsfeed']->fldCreatedDate);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblNewsFeed", $this->user['BuildingID'], "Post New Chat");
        }
        
        $this->output();
    }

    public function postImage() {
        $newName = $this->input->post('image');
        $oriName = $this->input->post('ori_image');
        $uploaded = $this->input->post('uploaded');
        $newsfeedID = $this->input->post('newsfeedID');

        if ($uploaded == "IMAGE") {
            $data = $this->data_model->add_new_image_allowduplicate($this->user['Id'], $oriName, $newName, $newsfeedID, 4);
        } else if ($uploaded == "DOCUMENT") {
            $data = $this->data_model->add_new_document($this->user['Id'], $oriName, $newName, $newsfeedID, 2);
        }
        $this->set_data($data);
        
        $this->output();
    }

    public function postComment() {
        $commentInput = $this->input->post('commentInput');
        $newsfeedID = $this->input->post('newsfeedID');
        
        $data = $this->newsfeed_model->post_comment($this->user['Id'], $commentInput, $newsfeedID);
        $this->set_data($data);
        $this->data['commentTime'] = $this->time_elapsed_string($this->data['fldCreatedDate']);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComments", $this->user['BuildingID'], "Post New Comment");

            $newsfeedData = $this->newsfeed_model->get_newsfeed_user_id($newsfeedID);
            if($newsfeedData['status'] == 1 && !in_array($this->user['Id'], $newsfeedData['user']) ) {
                $this->notification_model->insert_notification($newsfeedData['user'], $this->user['BuildingID'], "New Comment - " . $newsfeedData['title'], $commentInput, "/newsfeed/post/$newsfeedID", "comment-o");
            }
        }

        $this->output();
    }

    public function postUpdateComment() {
        $commentID = $this->input->post('commentID');
        $commentInput = $this->input->post('commentInput');
        
        $data = $this->newsfeed_model->post_update_comment($commentID, $this->user['Id'], $commentInput);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new'])) {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblComments", $this->user['BuildingID'], "Edit Comment");

            $newsfeedID = $this->newsfeed_model->get_newsfeed_id($commentID);
            $newsfeedData = $this->newsfeed_model->get_newsfeed_user_id($newsfeedID);
            if($newsfeedData['status'] == 1) {
                $this->notification_model->insert_notification($newsfeedData['user'], $this->user['BuildingID'], "Comment Edited - " . $newsfeedData['title'], $commentInput, "/newsfeed", "comment-o");
            }
        }
        
        $this->output();
    }

    public function removeComment() {
        $commentID = $this->input->post('commentID');
        $newsfeedID = $this->input->post('newsfeedID');
        
        $data = $this->newsfeed_model->remove_comment($commentID, $this->user['Id'], $newsfeedID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblComments", $this->user['BuildingID'], "Delete Comment");
        }
        
        $this->output();
    }

    public function postLike() {
        $newsfeedID = $this->input->post('newsfeedID');
        
        $data = $this->newsfeed_model->post_like($this->user['Id'], $newsfeedID);
        $this->set_data($data);
        
        $this->output();
    }

    public function showMoreComment() {
        $newsfeedID = $this->input->post('newsfeedID');
        $pageno = $this->input->post('pageno');
        $count2 = 0;
        
        $this->data['comments'][$count2] = $this->newsfeed_model->get_all_comments($newsfeedID, $pageno, 2);

        $count3 = 0;
        if (isset($this->data['comments'][$count2]['comment'])) {
            foreach ($this->data['comments'][$count2]['comment'] as $key2 => $list2) {
                $this->data['commentuser'][$count2][$count3] = $this->newsfeed_model->get_user($list2->fldUserID);
                if (!empty($this->data['commentuser'][$count2][$count3])) {
                    $this->data['commentTime'][$count2][$count3] = $this->time_elapsed_string($this->data['commentuser'][$count2][$count3]->fldLastUpdDate);
                    if (!empty($this->data['commentuser'][$count2][$count3])) {
                        $this->data['commentImage'][$count2][$count3] = $this->data_model->get_image($this->data['commentuser'][$count2][$count3]->fldUserID, 1);
                    }
                }
                $count3++;
            }
        }
        $this->output();
    }

    public function thumbnailupload() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $this->load->helper('upload');
                $f = stripslashes($_FILES['file']['name']);
                $extension = get_image_extension($f);
                if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'gif') {
                    $type = "IMAGE";
                    $dir = $this->data_model->getDir($type, 4);
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 2);
                }
                if ($dir != "") {
                    if ($type == "IMAGE") {
                        $returndata = uploadImage($dir);
                        $this->data['uploaded'] = 'IMAGE';
                    } else if ($type == "DOCUMENT") {
                        $returndata = uploadFile($dir);
                        $this->data['uploaded'] = 'DOCUMENT';
                    }

                    if ($returndata['result'] == '1') {
                        $this->data['status'] = '1';
                        $this->data['ori_image_name'] = $_FILES['file']['name'];
                        $this->data['new_image_name'] = $returndata['random_filename'];
                        $this->data['stored_pathname'] = $returndata['stored_pathname'];
                    } else {
                        $this->data['msg'] = $returndata['msg'];
                    }
                } else {
                    $this->data['msg'] = "Directory not found";
                }
            } else {
                $this->data['msg'] = 'Filename not found';
            }
        }
        
        $this->output();
    }

    public function thumbnailuploadTemp() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $this->load->helper('upload');
                $f = stripslashes($_FILES['file']['name']);
                $extension = get_image_extension($f);
                if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'gif') {
                    $type = "IMAGE";
                    $dir = $this->data_model->getDir($type, 4) . "/temp";
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 2) . "/temp";
                }
                if ($dir != "") {
                    if ($type == "IMAGE") {
                        $returndata = uploadTempImage($dir);
                        $this->data['uploaded'] = 'IMAGE';
                    } else if ($type == "DOCUMENT") {
                        $returndata = uploadFile($dir);
                        $this->data['uploaded'] = 'DOCUMENT';
                    }

                    if ($returndata['result'] == '1') {
                        $this->data['status'] = '1';
                        $this->data['ori_image_name'] = $_FILES['file']['name'];
                        $this->data['new_image_name'] = $returndata['random_filename'];
                        $this->data['stored_pathname'] = $returndata['stored_pathname'];
                    } else {
                        $this->data['msg'] = $returndata['msg'];
                        $this->data['ori_image_name'] = $_FILES['file']['name'];
                    }
                    $this->data['dir'] = $dir;
                }
            }
        }
        
        $this->output();
    }
    
    public function removeNewsfeed() {
        $newsfeedID = $this->input->post('newsfeedID');
        
        $data = $this->newsfeed_model->remove_newsfeed($this->user['Id'], $this->user['Email'], $newsfeedID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblNewsFeed", $this->user['BuildingID'], "Delete Chat");
        }
        
        $this->output();
    }
    
    public function edit_newsfeed_action() {
        $newsfeedID = $this->input->post('newsfeedID');
        $content = $this->input->post('content');
        
        if(strlen(trim($content)) === 0) {
            $this->data['msg'] = "Post content cannot be blank!";
        } else {
            $data = $this->newsfeed_model->edit_newsfeed_action($this->user['Id'], $this->user['Email'], $newsfeedID, $content);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new'])) {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblNewsFeed", $this->user['BuildingID'], "Edit Chat");
            }
        }
        
        $this->output();
    }
    
    /* PRIVATE FUNCTIONS */
    public function time_elapsed_string($datetime, $full = false) {
        $timezone = 'Asia/Kuala_Lumpur';
        $nowTime = new DateTime(date("Y-m-d H:i:s"));
        $nowTimezone = $nowTime->setTimezone(new DateTimeZone($timezone));
        $now = new DateTime($nowTimezone->format('Y-m-d H:i:s'));
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }
        if ($now->format('Y-m-d') == $ago->format('Y-m-d')) {
            return $string ? implode(', ', $string) . ' ago' : 'just now';
        } else {
            return $ago->format('Y-m-d h:i A');
        }
    }

    public function reupdate_images_action() {
        $newsfeedID = $this->input->post('newsfeedID');
        $type = $this->input->post('type');
        $fileArray = $this->input->post('fileArray');

        $data = $this->newsfeed_model->reupdate_images_action($this->user['Id'], $newsfeedID, $type, $fileArray);
        $this->set_data($data);
        $this->output();
    }
    
    public function post() {
        $id = $this->uri->segment(3);
        if($id) {
            $this->index($id);
        } else {
            $this->index();
        }
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
