<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Inbox extends LC_Controller {

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
        
        $this->load->model('inbox_model');
        $this->load->model('notification_model');
    }

    public function send_to_all_owners() {
        return true;
    }

    public function send_to_all_tenants() {
        return true;
    }

    public function index() {
        if(isset($this->data['perm']['inbox']['all_view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['mail'] = $this->inbox_model->get_email_main($this->user['Id'], $viewAll, 1, 20);
        $count = 0;
        if (isset($this->data['mail']['mail']) && !empty($this->data['mail']['mail']) && isset($this->data['mail']['mail']) != null) {
            foreach ($this->data['mail']['mail'] as $mails) {
                $this->data['date'][$count] = date('d/m/Y h:i A', strtotime($mails['fldLastUpdDate']));
                $count++;
            }
        }
        
        if (isset($this->data['mail']['detail']) && !empty($this->data['mail']['detail']) && isset($this->data['mail']['detail']) != null) {
            $counts = 0;
            foreach ($this->data['mail']['detail'] as $d) {
                $this->data['mail']['detail'][$counts]['fldMessage'] = strip_tags($d['fldMessage']);
                $counts++;
            }
        }
        
        $this->output('inbox/inbox');
    }

    public function reloadListing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        if(isset($this->data['perm']['inbox']['all_view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        
        $this->data['mail'] = $this->inbox_model->get_email_main($this->user['Id'], $viewAll, $pageno, $filterRow);
        if (isset($this->data['mail']['mail']) && !empty($this->data['mail']['mail']) && isset($this->data['mail']['mail']) != null) {
            $count = 0;
            foreach ($this->data['mail']['mail'] as $mails) {
                $this->data['date'][$count] = date('d/m/Y h:i A', strtotime($mails['fldLastUpdDate']));
                $count++;
            }
        }
        if (isset($this->data['mail']['detail']) && !empty($this->data['mail']['detail']) && isset($this->data['mail']['detail']) != null) {
            $counts = 0;
            foreach ($this->data['mail']['detail'] as $d) {
                $this->data['mail']['detail'][$counts]['fldMessage'] = strip_tags($d['fldMessage']);
                $counts++;
            }
        }
        $this->data['selfRole'] = $this->user['Role'];
        
        $this->output();
    }

    public function readDetail() {
        $mailID = $this->input->post('mailID');
        
        $data = $this->inbox_model->get_email_details($this->user['Id'], $mailID);
        $this->set_data($data);
        if (isset($this->data['detail'])) {
            $count = 0;
            foreach ($this->data['detail'] as $mails) {
                $this->data['date'][$count] = date('F d Y h.iA', strtotime($mails['fldCreatedDate']));
                $count++;
            }
            $counts = 0;
            foreach ($this->data['detail'] as $d) {
                $this->data['detail'][$counts]['fldMessage'] = stripslashes($d['fldMessage']);
                $counts++;
            }
        }
        $this->data['selfID'] = $this->user['Id'];
        
        $this->output();
    }

    public function reply_message_action() {
        $mailID = $this->input->post('mailID');
        $replyMessage = $this->input->post('replyMessage');
        
        $data = $this->inbox_model->reply_message_action($this->user['Id'], $this->user['Email'], $mailID, $replyMessage);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblInboxDetail", $this->user['BuildingID'], "Reply");

            $userData = $this->inbox_model->get_message_user_id($mailID, $this->user['Id']);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], "You have a new message", $replyMessage, "/inbox", "envelope");
            }
        }
        
        $this->output();
    }

    public function new_message() {
        $targetID = $this->uri->segment(3);
        
        if ($targetID == "selected_users") {
            $message = $this->user['Message'];
            if ($message != null && $message != "") {
                $selectedUsers = array();
                $selectedUsers = explode(",", $message);
                
                foreach ($selectedUsers as $key => $su) {
                    $permission = $this->inbox_model->message_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['BuildingID'], $su);
                    if ($permission['status'] != 1) {
                        unset($selectedUsers[$key]);
                    }
                }
                $userCounter = 0;
                foreach ($selectedUsers as $su) {
                    $this->data['targetEmail'][$userCounter] = $this->inbox_model->get_user_email($this->user['Id'], $this->user['Email'], $su);
                    $userCounter++;
                }
            }            
        } else {
            $permission = $this->inbox_model->message_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['BuildingID'], $targetID);
            if ($permission['status'] == 1) {
                $this->data['targetEmail'][0] = $this->inbox_model->get_user_email($this->user['Id'], $this->user['Email'], $targetID);
            }
        }

        //eliminate selected user from being selected from query again
        $selectedID = array();
        if(!empty($this->data['targetEmail'])) {
            foreach ($this->data['targetEmail'] as $te) {
                array_push($selectedID, $te->fldUserID);
            }
        }

        // $this->data['managementGroup'] = $this->inbox_model->get_property_management($this->user['Id'], $this->user['Email']);
        if(in_array($this->user['Role'], array(OWNER_ROLE_ID, TENANT_ROLE_ID))) {
            $this->data['receiverGroup'] = $this->inbox_model->get_tenant_owner($this->user['Id'], $this->user['Role'], $selectedID);
        }
        if(isset($this->data['perm']['inbox']['send_to_all_owners'])) {
            $this->data['sendToAllOwner'] = 1;
        }
        if(isset($this->data['perm']['inbox']['send_to_all_tenants'])) {
            $this->data['sendToAllTenant'] = 1;
        }
        $this->data['receiverGroup'] = $this->inbox_model->get_all_user($this->user['BuildingID'], $selectedID);
        
        $this->output('inbox/new_message');
    }

    public function send_message_action() {
        $sentTo = $this->input->post('sentTo');
        $messageSubject = $this->input->post('messageSubject');
        $newMessage = $this->input->post('newMessage');
        $data = $this->inbox_model->send_message_action($this->user['Id'], $this->user['BuildingID'], $sentTo, $messageSubject, $newMessage);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, array("tblInboxMain","tblInboxDetail"), $this->user['BuildingID'], "New");

            if(sizeof($data['usersArray']) > 0) {
                $this->notification_model->insert_notification($data['usersArray'], $this->user['BuildingID'], "You have a new message", $messageSubject, "/inbox", "envelope");
            }
        }
        
        $this->output();
    }
    
    public function sent_mail() {
        if(isset($this->data['perm']['inbox']['all_view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['mail'] = $this->inbox_model->get_email_sent($this->user['Id'], $viewAll, 1, 20);
        $count = 0;
        if (isset($this->data['mail']['mail']) && !empty($this->data['mail']['mail']) && isset($this->data['mail']['mail']) != null) {
            foreach ($this->data['mail']['mail'] as $mails) {
                $this->data['date'][$count] = date('d/m/Y h:i A', strtotime($mails['fldLastUpdDate']));
                $count++;
            }
        }
        if (isset($this->data['mail']['detail']) && !empty($this->data['mail']['detail']) && isset($this->data['mail']['detail']) != null) {
            $counts = 0;
            foreach ($this->data['mail']['detail'] as $d) {
                $this->data['mail']['detail'][$counts]['fldMessage'] = strip_tags($d['fldMessage']);
                $counts++;
            }
        }
        
        $this->output('inbox/sent');
    }
    
    public function sent_mail_reloadListing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if(isset($this->data['perm']['inbox']['all_view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        
        $this->data['mail'] = $this->inbox_model->get_email_sent($this->user['Id'], $viewAll, $pageno, $filterRow);
        
        if (isset($this->data['mail']['mail']) && !empty($this->data['mail']['mail']) && isset($this->data['mail']['mail']) != null) {
            $count = 0;
            foreach ($this->data['mail']['mail'] as $mails) {
                $this->data['date'][$count] = date('d/m/Y h:i A', strtotime($mails['fldLastUpdDate']));
                $count++;
            }
        }
        if (isset($this->data['mail']['detail']) && !empty($this->data['mail']['detail']) && isset($this->data['mail']['detail']) != null) {
            $counts = 0;
            foreach ($this->data['mail']['detail'] as $d) {
                $this->data['mail']['detail'][$counts]['fldMessage'] = strip_tags($d['fldMessage']);
                $counts++;
            }
        }
        
        $this->output();
    }
    
    public function postImage() {
        $newName = $this->input->post('image');
        $oriName = $this->input->post('ori_image');
        $uploaded = $this->input->post('uploaded');
        $inboxDetailID = $this->input->post('inboxDetailID');
        
        if ($uploaded == "IMAGE") {
            $data = $this->data_model->add_new_image_allowduplicate($this->user['Id'], $oriName, $newName, $inboxDetailID, 7);
        } else if ($uploaded == "DOCUMENT") {
            $data = $this->data_model->add_new_document($this->user['Id'], $oriName, $newName, $inboxDetailID, 3);
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
                    $dir = $this->data_model->getDir($type, 7);
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 3);
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
                    $dir = $this->data_model->getDir($type, 7) . "/temp";
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 3) . "/temp";
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
    
    public function delete_message_main_action() {
        $mailSelected = json_decode($this->input->post('mailSelectedJson'));
        
        $data = $this->inbox_model->delete_message_main_action($this->user['Id'], $this->user['Email'], $mailSelected);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblInboxMain", $this->user['BuildingID'], "Delete Main Message");
        }
        
        $this->output();
    }
    
    public function delete_message_detail_action() {
        $msgID = json_decode($this->input->post('msgID'));
        
        $data = $this->inbox_model->delete_message_detail_action($this->user['Id'], $this->user['Email'], $msgID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblInboxDetail", $this->user['BuildingID'], "Delete Sub Message");
        }
        
        $this->output();
    }
    
    public function time_elapsed_string($datetime, $full = false) {
        $timezone = 'Asia/Kuala_Lumpur';
        $nowTime = new DateTime(date("Y-m-d H:i:s"));
        $nowTimezone = $nowTime->setTimezone(new DateTimeZone($timezone));
        $now = new DateTime($nowTimezone->format('Y-m-d H:i:s'));
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->d > 1) {
            return $datetime;
        } else {
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

            if (!$full)
                $string = array_slice($string, 0, 1);
            return $string ? implode(', ', $string) . ' ago' : 'just now';
        }
    }
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */