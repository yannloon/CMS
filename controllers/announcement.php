<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Announcement extends LC_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *      http://example.com/index.php/welcome
     *  - or -
     *      http://example.com/index.php/welcome/index
     *  - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    function __construct() {
        parent::__construct();
        /* LOAD MODEL */
        $this->load->model('announcement_model');
        $this->set_title("Announcement");
        $this->load->model('notification_model');
    }

    private function send_email($receiver, $sender, $senderName, $subject, $message, $bcc, $images=array(), $files=array()) {
        $this->load->library('email');
        //testing code
        //testing code 2

        $img_dir = $this->data_model->getDir("IMAGE", IMG_TYPE_ANNOUNCEMENT);
        $doc_dir = $this->data_model->getDir("DOCUMENT", DOC_TYPE_ANNOUNCEMENT);

        $smtp_protocol = SMTP_PROTOCOL;
        $smtp_host = SMTP_HOST;
        $smtp_user = SMTP_USER;
        $smtp_pass = SMTP_PASS;
        $smtp_port = SMTP_PORT;
        $smtp_no_reply = SMTP_NO_REPLY;

        $this->email->initialize(array(
            'protocol' => $smtp_protocol,
            'smtp_host' => $smtp_host,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_port' => $smtp_port,
            'crlf' => "\r\n",
            'newline' => "\r\n",
            'smtp_timeout' => '10',
            'mailtype' => 'html'
        ));

        $this->email->from($smtp_user, $senderName);
        $this->email->to($receiver);
        $this->email->bcc($bcc);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->reply_to($smtp_no_reply);
        if(count($images) > 0) {
            for($i = 0; $i < count($images); $i++)
            {
                $this_file = str_replace("//", "/", $img_dir.$images[$i]['image']);
                $this->email->attach($this_file,'attachment',$images[$i]['name']);
            }
        }
        if(count($files) > 0) {
            for($i = 0; $i < count($files); $i++)
            {
                $doc_file = str_replace("//", "/", $doc_dir.$files[$i]['file']);
                $this->email->attach($doc_file,'attachment',$files[$i]['name']);
            }
        }
        $this->email->send();
    }

    public function index() {
        if($this->input->get("old")) {
            $this->data['announcement'] = $this->announcement_model->get_announcement($this->user['Role'], $this->user['BuildingID'], 1, 5);
            $count = 0;
            if (isset($this->data['announcement']['results'])) {
                foreach ($this->data['announcement']['results'] as $key => $list) {
                    $this->data['user'][$count] = $this->announcement_model->get_user($list->fldUpdatedBy);
                    if (!empty($this->data['user'][$count])) {
                        $this->data['image'][$count] = $this->data_model->get_image($this->data['user'][$count]->fldUserID, 1);
                    }
                    $this->data['file'][$count] = $this->data_model->get_images($list->fldAnnouncementID, 5);
                    $this->data['isImage'][$count] = false;
                    if (isset($this->data['file'][$count][0]->fldThumbnailImage)) {
                        if (preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)$/', $this->data['file'][$count][0]->fldThumbnailImage)) {
                            $this->data['isImage'][$count] = true;
                        }
                    } else {
                        $this->data['file'][$count] = $this->data_model->get_documents($list->fldAnnouncementID, 1);
                    }
                    $this->data['time'][$count] = $this->time_elapsed_string($list->fldCreatedDate);
                    $count++;
                }
            }
            
            $this->output('announcement/announcement');
        } else {
            $this->data['announcement'] = $this->announcement_model->get_announcement_new($this->user['Role'], $this->user['BuildingID']);
            if (isset($this->data['announcement']['results'])) {
                foreach($this->data['announcement']['results'] as $k => $v) {
                    $this->data['announcement']['results'][$k]->posted_time = $this->time_elapsed_string($v->fldCreatedDate);
                }
            }
            $this->data['img_dir'] = $this->data_model->getDir("IMAGE", IMG_TYPE_ANNOUNCEMENT);
            $this->data['doc_dir'] = $this->data_model->getDir("DOCUMENT", DOC_TYPE_ANNOUNCEMENT);
            
            $this->output('announcement/announcement_new');
        }
    }

    public function reloadListing() {
        $pageno = $this->input->post('pageno');
        $buildingID = $this->user['BuildingID'];
        
        $this->data['announcement'] = $this->announcement_model->get_announcement($this->user['Role'], $buildingID, $pageno, 5);
        $count = 0;
        if (isset($this->data['announcement']['results'])) {
            foreach ($this->data['announcement']['results'] as $key => $list) {
                $this->data['user'][$count] = $this->announcement_model->get_user($list->fldUpdatedBy);
                if (!empty($this->data['user'][$count])) {
                    $this->data['image'][$count] = $this->data_model->get_image($this->data['user'][$count]->fldUserID, 1);
                }
                $this->data['file'][$count] = $this->data_model->get_images($list->fldAnnouncementID, IMG_TYPE_ANNOUNCEMENT);
                $this->data['isImage'][$count] = false;
                if (isset($this->data['file'][$count][0]->fldThumbnailImage)) {
                    if (preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)$/', $this->data['file'][$count][0]->fldThumbnailImage)) {
                        $this->data['isImage'][$count] = true;
                    }
                } else {
                    $this->data['file'][$count] = $this->data_model->get_documents($list->fldAnnouncementID, DOC_TYPE_ANNOUNCEMENT);
                }
                $this->data['time'][$count] = $this->time_elapsed_string($list->fldCreatedDate);
                $this->data['role'] = $this->user['Role'];
                $count++;
            }
        }
        $this->output();
    }

    public function postAnnouncement() {
        $title   = $this->input->post('announcement_title');
        $content = $this->input->post('announcement_content');
        $data = $this->announcement_model->post_announcement($this->user['BuildingID'], $this->user['Id'], $title, $content);
        $this->set_data($data);
        
        $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblAnnouncement", $this->user['BuildingID'], "");
        
        if(isset($this->data['announcement'])) {
            $this->data['time'] = $this->time_elapsed_string($this->data['announcement']->fldCreatedDate);
        }

        if($this->data['status'] == 1) {
            $usersData = $this->permission_model->get_building_users($this->user['BuildingID'], $this->__controller);
            $this->notification_model->insert_notification($usersData, $this->user['BuildingID'], "Announcement", $title, "/announcement", "bullhorn");

            $bccUsers = $this->announcement_model->get_users_email($usersData);
            $fromEmail = $this->announcement_model->get_user_email($this->user['id']);
            $thisSubject = 'New Announcement - ' . $title;
            $this->data['bcc'] = $bccUsers;
            $this->data['email'] = $this->send_email($fromEmail, $fromEmail, $this->user['BuildingName'], $thisSubject, $content, $bccUsers);
        }

        $this->output();
    }

    public function postImage() {
        $newName = $this->input->post('image');
        $oriName = $this->input->post('ori_image');
        $uploaded = $this->input->post('uploaded');
        $announcementID = $this->input->post('announcementID');
        
        if ($uploaded == "IMAGE") {
            $data = $this->data_model->add_new_image_allowduplicate($this->user['Id'], $oriName, $newName, $announcementID, 5);
        } else if ($uploaded == "DOCUMENT") {
            $data = $this->data_model->add_new_document($this->user['Id'], $oriName, $newName, $announcementID, 1);
        }
        $this->set_data($data);
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
                    $dir = $this->data_model->getDir($type, 5);
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 1);
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

    public function removeAnnouncement() {
        $announcementID = $this->input->post('announcementID');
        
        $data = $this->announcement_model->remove_announcement($this->user['Id'], $this->user['Email'], $announcementID);
        $this->set_data($data);

        $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblAnnouncement", $this->user['BuildingID'], "");
        
        $this->output();
    }

    public function edit_announcement_action() {
        $announcementID = $this->input->post('announcementID');
        $content = $this->input->post('content');
        
        $data = $this->announcement_model->edit_announcement_action($this->user['Id'], $this->user['Email'], $announcementID, $content);
        $this->set_data($data);

        $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblAnnouncement", $this->user['BuildingID'], "");
        
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
                    $dir = $this->data_model->getDir($type, 5) . "/temp";
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 1) . "/temp";
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
                        $this->data['stored_path'] = $returndata['stored_pathname'];
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

    public function reupdate_images_action() {
        $announcementID = $this->input->post('announcementID');
        $type = $this->input->post('type');
        $fileArray = $this->input->post('fileArray');

        $data = $this->announcement_model->reupdate_images_action($this->user['Id'], $announcementID, $type, $fileArray);
        $this->set_data($data);
        $this->output();
    }
    
    public function upload_files() {
        $this->load->helper('upload');
        $this->is_ajax = TRUE;
        
        $result = array();
        
        if(isset($_FILES['files']) && isset($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
            for($i = 0; $i < count($_FILES['files']['name']); $i++) {
                $result[$i] = array();
                $result[$i]['status'] = FALSE;
                $result[$i]['ori_name'] = stripslashes($_FILES['files']['name'][$i]);
                $result[$i]['ext'] = get_image_extension($result[$i]['ori_name']);
                $result[$i]['datepath'] = date("Y/m/d/");
                if(in_array($result[$i]['ext'], array('jpg', 'jpeg', 'png', 'gif'))) {
                    $result[$i]['type'] = "IMAGE";
                    $result[$i]['dir'] = $this->data_model->getDir($result[$i]['type'], IMG_TYPE_ANNOUNCEMENT) . "/temp/".$result[$i]['datepath'];
                } else if(in_array($result[$i]['ext'], array('doc', 'docx', 'log', 'txt', 'pdf'))) {
                    $result[$i]['type'] = "DOCUMENT";
                    $result[$i]['dir'] = $this->data_model->getDir($result[$i]['type'], DOC_TYPE_ANNOUNCEMENT) . "/temp/".$result[$i]['datepath'];
                } else {
                    $result[$i]['msg'] = "File <strong>".$result[$i]['ori_name']."</strong> was not uploaded because file extension <strong>".$result[$i]['ext']."</strong> was not supported.";
                }
                
                // UPLOAD THE FILE
                if(isset($result[$i]['dir'])) {
                    $root = FCPATH;
                    if(!file_exists($root.$result[$i]['dir'])) {
                        mkdir($root.$result[$i]['dir'], 0755, TRUE);
                    }
                    $result[$i]['new_name'] = random_name(7, 0).".".$result[$i]['ext'];
                    if(move_uploaded_file($_FILES['files']['tmp_name'][$i], $root.$result[$i]['dir'].$result[$i]['new_name'])) {
                        if($result[$i]['type'] === "IMAGE") {
                            $result[$i]['thumb_name'] = "thumb_".$result[$i]['new_name'];
                            $result[$i]['fullthumb'] = $result[$i]['dir'].$result[$i]['thumb_name'];
                            create_thumbnail($root.$result[$i]['dir'].$result[$i]['new_name'], $root.$result[$i]['dir'].$result[$i]['thumb_name'], 200, 200);
                        }
                        $result[$i]['status'] = TRUE;
                        $result[$i]['msg'] = "File <strong>".$result[$i]['ori_name']."</strong> uploaded successfully.";
                        $result[$i]['fullname'] = $result[$i]['dir'].$result[$i]['new_name'];
                    } else {
                        $result[$i]['msg'] = "Error while uploading.";
                    }
                }
            }
        }
        $this->data['result'] = $result;
        
        $this->output();
    }
    
    
    /* V2 */
    public function reloadListing_new() {
        $pageno = $this->input->post('pageno');
        
        $this->data['announcement'] = $this->announcement_model->get_announcement_new($this->user['Role'], $this->user['BuildingID'], $pageno);
        if (isset($this->data['announcement']['results'])) {
            foreach ($this->data['announcement']['results'] as $k => $v) {
                $this->data['announcement']['results'][$k]->posted_time = $this->time_elapsed_string($v->fldCreatedDate);
            }
        }
        $this->output();
    }
    
    public function postAnnouncement_new() {
        $title   = $this->input->post('title');
        $message = $this->input->post('message');
        // ATTACHMENTS
        $att_imgs       = array_filter(explode(";", $this->input->post('att_imgs')), 'strlen');
        $att_thumbs     = array_filter(explode(";", $this->input->post('att_thumbs')), 'strlen');
        $att_imgnames   = array_filter(explode(";", $this->input->post('att_imgnames')), 'strlen');
        $att_files      = array_filter(explode(";", $this->input->post('att_files')), 'strlen');
        $att_filenames  = array_filter(explode(";", $this->input->post('att_filenames')), 'strlen');
        
        if(empty($title) || empty($message)) {
            $data['msg'] = "Announcement title &amp; contents cannot be blank.";
        } else {
            // MOVE FILES
            $root = FCPATH;
            $img_dir = $this->data_model->getDir("IMAGE", IMG_TYPE_ANNOUNCEMENT);
            $doc_dir = $this->data_model->getDir("DOCUMENT", DOC_TYPE_ANNOUNCEMENT);
            $images = $files = array();
            for($i = 0; $i < count($att_imgs); $i++) {
                $new_img = str_replace($img_dir."/temp", "/large", $att_imgs[$i]);
                $new_thumb = str_replace($img_dir."/temp", "/thumbnail", $att_thumbs[$i]);
                $new_img = str_replace("//", "/", $new_img);
                $new_thumb = str_replace("//", "/", $new_thumb);
                $this->mkdir_ondemand($root.$img_dir.$new_img);
                $this->mkdir_ondemand($root.$img_dir.$new_thumb);
                if(rename($root.$att_imgs[$i], $root.$img_dir.$new_img) && rename($root.$att_thumbs[$i], $root.$img_dir.$new_thumb)) {
                    $images[] = array(
                        "name"  => $att_imgnames[$i],
                        "image" => $new_img,
                        "thumb" => $new_thumb
                    );
                }
            }
            for($i = 0; $i < count($att_files); $i++) {
                $new_file = str_replace($doc_dir."/temp", "", $att_files[$i]);
                $new_file = str_replace("//", "/", $new_file);
                $this->mkdir_ondemand($root.$doc_dir.$new_file);
                if(rename($root.$att_files[$i], $root.$doc_dir.$new_file)) {
                    $files[] = array(
                        "name"  => $att_filenames[$i],
                        "file"  => $new_file
                    );
                }
            }

            $data = $this->announcement_model->post_announcement_new($this->user['BuildingID'], $this->user['Id'], $title, $message, $images, $files);
            
            $this->data_model->log_activity($this->user['Id'], $this->__controller, ACTIVITY_ADD, $data, "tblAnnouncement", $this->user['BuildingID'], "");
        }
        $this->set_data($data);
        
        if(isset($this->data['data'])) {
            $this->data['data']->posted_time = $this->time_elapsed_string($this->data['data']->fldCreatedDate);
        }

        if($this->data['stat'] == TRUE) {
            $usersData = $this->permission_model->get_building_users($this->user['BuildingID'], $this->__controller);
            $this->notification_model->insert_notification($usersData, $this->user['BuildingID'], "Announcement", $title, "/announcement", "bullhorn");

            $bccUsers = $this->announcement_model->get_users_email($usersData);
            $fromEmail = $this->announcement_model->get_user_email($this->user['Id']);
            $thisSubject = 'New Announcement - ' . $title;
            $this->data['bcc'] = $bccUsers;
            $this->data['mail'] = $this->send_email($fromEmail, $fromEmail, $this->user['BuildingName'], $thisSubject, $message, $bccUsers, $images, $files);
        }

        $this->output();
    }
    
    public function edit_announcement_action_new() {
        $aid     = $this->input->post('aid');
        $title   = $this->input->post('title');
        $message = $this->input->post('message');
        // ATTACHMENTS
        $att_imgs       = array_filter(explode(";", $this->input->post('att_imgs')), 'strlen');
        $att_thumbs     = array_filter(explode(";", $this->input->post('att_thumbs')), 'strlen');
        $att_imgnames   = array_filter(explode(";", $this->input->post('att_imgnames')), 'strlen');
        $att_files      = array_filter(explode(";", $this->input->post('att_files')), 'strlen');
        $att_filenames  = array_filter(explode(";", $this->input->post('att_filenames')), 'strlen');
        
        if(!$aid) {
            $data['msg'] = "Invalid parameters!";
        } else if(empty($title) || empty($message)) {
            $data['msg'] = "Announcement title &amp; contents cannot be blank.";
        } else {
            // MOVE FILES - ONLY FOR NEW FILES
            $root = FCPATH;
            $img_dir = $this->data_model->getDir("IMAGE", IMG_TYPE_ANNOUNCEMENT);
            $doc_dir = $this->data_model->getDir("DOCUMENT", DOC_TYPE_ANNOUNCEMENT);
            $images = $files = array();
            for($i = 0; $i < count($att_imgs); $i++) {
                // IDENTIFY EXISTING OR NEW IMAGE
                if($this->is_new_file($att_imgs[$i], $img_dir)) {
                    $new_img = str_replace($img_dir."/temp", "/large", $att_imgs[$i]);
                    $new_thumb = str_replace($img_dir."/temp", "/thumbnail", $att_thumbs[$i]);
                    $this->mkdir_ondemand($root.$img_dir.$new_img);
                    $this->mkdir_ondemand($root.$img_dir.$new_thumb);
                    if(rename($root.$att_imgs[$i], $root.$img_dir.$new_img) && rename($root.$att_thumbs[$i], $root.$img_dir.$new_thumb)) {
                        $images[] = array(
                            "name"  => $att_imgnames[$i],
                            "image" => $new_img,
                            "thumb" => $new_thumb
                        );
                    }
                } else {
                    // REMOVE DIR
                    $new_img = str_replace($img_dir, "", $att_imgs[$i]);
                    $new_thumb = str_replace($img_dir, "", $att_thumbs[$i]);
                    $images[] = array(
                        "name"  => $att_imgnames[$i],
                        "image" => $new_img,
                        "thumb" => $new_thumb
                    );
                }
            }
            for($i = 0; $i < count($att_files); $i++) {
                // IDENTIFY EXISTING OR NEW FILE
                if($this->is_new_file($att_files[$i], $doc_dir)) {
                    $new_file = str_replace($doc_dir."/temp", "", $att_files[$i]);
                    $this->mkdir_ondemand($root.$doc_dir.$new_file);
                    if(rename($root.$att_files[$i], $root.$doc_dir.$new_file)) {
                        $files[] = array(
                            "name"  => $att_filenames[$i],
                            "file"  => $new_file
                        );
                    }
                } else {
                    // REMOVE DIR
                    $new_file = str_replace($doc_dir, "", $att_files[$i]);
                    $files[] = array(
                        "name"  => $att_filenames[$i],
                        "file"  => $new_file
                    );
                }
            }
            
            $data = $this->announcement_model->edit_announcement_new($this->user['BuildingID'], $this->user['Id'], $aid, $title, $message, $images, $files);
            
            $this->data_model->log_activity($this->user['Id'], $this->__controller, ACTIVITY_EDIT, $data, "tblAnnouncement", $this->user['BuildingID'], "");
        }
        $this->set_data($data);
        
        if(isset($this->data['data'])) {
            $this->data['data']->posted_time = $this->time_elapsed_string($this->data['data']->fldCreatedDate);
        }

        $this->output();
    }
    
    
    /* PRIVATE FUNCTION */
    private function time_elapsed_string($datetime, $full = false) {
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
    
    private function mkdir_ondemand($filename) {
        $dir = dirname($filename);
        if(!file_exists($dir)) {
            mkdir($dir, DIR_READ_MODE, TRUE);
        }
    }
    
    private function is_new_file($filename, $dir) {
        return $this->startsWith($filename, $dir."/temp");
    }
    
    private function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */