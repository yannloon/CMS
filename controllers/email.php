<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Email extends LC_Controller {

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
        
        $this->load->model('inbox_model');
        $this->data['perm'] = $this->set_permission(array("user_management"));
    }

    private function send_email($receiver, $sender, $senderName, $subject, $message, $files=array()) {
        $this->load->library('email');
        $data = array();

        $smtp_protocol = SMTP_PROTOCOL;
        $smtp_host = SMTP_HOST;
        $smtp_user = SMTP_USER;
        $smtp_pass = SMTP_PASS;
        $smtp_port = SMTP_PORT;

        $this->email->initialize(array(
            'protocol' => $smtp_protocol,
            'smtp_host' => $smtp_host,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_port' => $smtp_port,
            'crlf' => "\r\n",
            'newline' => "\r\n",
            'smtp_timeout' => '10',
            'mailtype' => 'html',
            'charset' => 'iso-8859-1'
        ));

        $this->email->from($smtp_user, $senderName);
        $this->email->to($receiver);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->reply_to($sender);
        if(count($files) > 0) {
            foreach($files as $f)
            {
                $this->email->attach($f['path'],'attachment',$f['name']);
            }
        }
        if($this->email->send())
        {
            $data['status'] = '1';
            $data['msg'] = 'Email Sent';
        }
        else
        {
            $data['status'] = '2';
            $data['msg'] = 'Failed to send.';
        }
        return $data;
    }

    public function index() {        
        $this->output('404');
    }

    public function new_email()
    {
        $targetID = $this->uri->segment(3);

        if ($targetID == "selected_users") {
            $message = $this->user['Message'];
            if ($message != null && $message != "") {
                $selectedUsers = array();
                $selectedUsers = explode(",", $message);
                
                foreach ($selectedUsers as $key => $su) {
                    $permission = $this->inbox_model->email_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $su);
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
            $permission = $this->inbox_model->email_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $targetID);
            if ($permission['status'] == 1) {
                $this->data['targetEmail'][0] = $this->inbox_model->get_user_email($this->user['Id'], $this->user['Email'], $targetID);
            }
        }

        $this->output('email/email');
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

    public function send_email_action() {
        $sentTo = $this->input->post('sentTo');
        $messageSubject = $this->input->post('messageSubject');
        $newMessage = $this->input->post('newMessage');
        $filesArray = $this->input->post('filesArray');
        $filesArray = $filesArray ? $filesArray : array();
        $data = $this->inbox_model->get_sent_users($this->user['Id'], $this->user['Email'], $sentTo);
        if(isset($data['users']))
        {
            if(isset($this->data['perm']['user_management']['edit'])) {
                $fromName = $this->user['BuildingName'] . " Management";
            } else {
                $fromName = $data['firstName'];
            }

            $emailResult = $this->send_email($data['users'], $data['selfEmail'], $fromName, $messageSubject, $newMessage, $filesArray);
            $this->set_data($emailResult);

            // $config = array(
            //   'protocol' => 'smtp',
            //   'smtp_host' => 'ssl://smtp.googlemail.com',
            //   'smtp_port' => 465,
            //   'smtp_user' => 'livincubeTest@gmail.com',
            //   'smtp_pass' => '123test123',
            //   'mailtype' => 'html',
            //   'charset' => 'iso-8859-1',
            //   'wordwrap' => TRUE
            // );

            // if(isset($this->data['perm']['user_management']['edit'])) {
            //     $fromName = $this->user['BuildingName'] . " Management";
            // } else {
            //     $fromName = $data['firstName'];
            // }

            // $this->load->library('email', $config);
            // $this->email->set_newline("\r\n");
            // $this->email->from($data['selfEmail'], $fromName);
            // $this->email->to($data['users']);
            // $this->email->subject($messageSubject);
            // $this->email->message($newMessage);
            // if($filesArray != null && !empty($filesArray))
            // {
            //     foreach($filesArray as $f)
            //     {
            //         $this->email->attach($f['path'],'attachment',$f['name']);
            //     }
            // }
            
            // if($this->email->send())
            // {
            //     $this->data['status'] = '1';
            //     $this->data['msg'] = 'Email Sent';
            // }
            // else
            // {
            //     $this->data['status'] = '2';
            //     $this->data['msg'] = 'Failed to send.';
            // }
        }
        else
        {
            $this->data['status'] = '2';
            $this->data['msg'] = 'Failed to send email';
        }
        $this->output();
    }
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */