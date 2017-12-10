<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Login extends LC_Controller {
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
        $this->load->model('notification_model');
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
        if(!$this->user['Logged']) {
            $this->session->sess_destroy();
            $this->output('login_view');
        } else {
            redirect("/announcement");
        }
    }

    public function user_data_submit_1() {
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $salt = "l0gins3ssions@lt";
        
        $data = $this->data_model->get_user_login($email, $password, $_SERVER['REMOTE_ADDR']);
        
        if($data["status"] == 1) {
            // CREATE USER OBJECT
            $user = array(
                "Logged" => TRUE,
                "Id"     => $data['fldUserID'],
                "Role"   => $data['fldRoleID'],
                "AdminRole"   => $data['roleAdmin'],
                "Email"  => do_hash($salt . $data['fldEmail'])
            );
            if (isset($data["fldNickName"]) && $data["fldNickName"] != null && $data["fldNickName"] != '') {
                $user['Nickname'] = $data["fldNickName"];
            } else {
                $user['Nickname'] = $data["fldFirstName"] . " " . $data["fldLastName"];
            }

            if (isset($data["fldThumbnailImage"]) && $data["fldThumbnailImage"] != null && $data["fldThumbnailImage"] != '') {
                $imageDate = explode(" ", $data["fldCreatedDate"])[0];
                $imageFolder = str_replace('-', '/', $imageDate);
                $user['Thumbnail'] = $imageFolder . '/' . $data["fldThumbnailImage"];
            } else {
                $user['Thumbnail'] = "UserSmall.png";
            }
            $roleArray = array();
            $buildingNameArray = array();
            $getProperty = $this->data_model->get_property($data["fldUserID"]);
            foreach ($getProperty as $key => $list) {
                array_push($roleArray, $list->fldBuildingID);
                array_push($buildingNameArray, $list->fldBuildingName);
            }
            if (!empty($roleArray)) {
                $user['Property'] = implode(",", $roleArray);

                $preferenceID = $this->data_model->get_property_preference($data["fldUserID"]);
                if ($preferenceID['preferenceID'] == 0 || $preferenceID['preferenceID'] == null) {
                    $user['BuildingID'] = $roleArray[0];
                    $user['BuildingName'] = $buildingNameArray[0];
                } else {
                    $user['BuildingID'] = $preferenceID['building']->fldBuildingID;
                    $user['BuildingName'] = $preferenceID['building']->fldBuildingName;
                }
            } else {
                $user['Property'] = "";
            }
            $user['Message'] = "";
            
            $this->session->set_userdata("user", $user);

            $returnData['status'] = 1;
            $returnData['message'] = "Login Success";
        } else if($data["status"] == 3) {
            $returnData['status'] = 0;
            $returnData['message'] = $data["msg"];
        } else {
            $returnData['status'] = 0;
            $returnData['message'] = "Login Failed.";
        }
        echo json_encode($returnData);
    }
    
    public function user_data_submit() {
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $access_ip = $this->input->server('REMOTE_ADDR');
        $salt = "l0gins3ssions@lt";
        
        $data = $this->data_model->get_user_login($email, $password, $access_ip);
        
        if($data["status"] == 1) {
            // CREATE USER OBJECT
            $user = array(
                "Logged"        => TRUE,
                "Id"            => $data['fldUserID'],
                "Role"          => $data['fldRoleID'],
                "Level"         => $data['fldLevel'],
                "Email"         => do_hash($salt . $data['fldEmail']),
                "BuildingID"    => $data['BuildingID'],
                "BuildingName"  => $data['BuildingName'],
                "AdminRole"         => $data['roleAdmin'],
                "Property"      => $data['Property']
            );
            if (isset($data["fldNickName"]) && $data["fldNickName"] != null && $data["fldNickName"] != '') {
                $user['Nickname'] = $data["fldNickName"];
            } else {
                $user['Nickname'] = $data["fldFirstName"] . " " . $data["fldLastName"];
            }

            if (isset($data["fldThumbnailImage"]) && $data["fldThumbnailImage"] != null && $data["fldThumbnailImage"] != '') {
                $imageDate = explode(" ", $data["fldCreatedDate"])[0];
                $imageFolder = str_replace('-', '/', $imageDate);
                $user['Thumbnail'] = $imageFolder . '/' . $data["fldThumbnailImage"];
            } else {
                $user['Thumbnail'] = "UserSmall.png";
            }
            $user['Message'] = "";
            
            $this->session->set_userdata("user", $user);

            $returnData['status'] = 1;
            $returnData['message'] = "Login Success";
        } else if($data["status"] == 3) {
            $returnData['status'] = 0;
            $returnData['message'] = $data["msg"];
        } else {
            $returnData['status'] = 0;
            $returnData['message'] = "Login Failed.";
        }
        echo json_encode($returnData);
    }



    public function forgot_password() {
        $this->set_title("Forgot Password");
        $this->output('forgot_password');
    }

    public function getBuildingListing() {
        // $this->is_ajax = TRUE;
        
        $searchName = $this->input->post('searchName');
        
        $data = $this->data_model->get_building_listing($searchName);
        $this->set_data($data);
        
        $this->output();
    }

    public function sendMail($link, $to) {
        // $config = array(
        //     'protocol' => 'smtp',
        //     'smtp_host' => 'ssl://smtp.googlemail.com',
        //     'smtp_port' => 465,
        //     'smtp_user' => 'livincubeTest@gmail.com',
        //     'smtp_pass' => '123test123',
        //     'mailtype' => 'html',
        //     'charset' => 'iso-8859-1',
        //     'wordwrap' => TRUE
        // );

        // $this->load->library('email', $config);
        // $this->email->set_newline("\r\n");
        // $this->email->from('livincubeTest@gmail.com');
        // $this->email->to($to);
        // $this->email->subject('LivinCube Password Reset');
        // $this->email->message('Your request for password reset had been confirmed. Please click this link to reset your password: ' . $link);
        // if ($this->email->send()) {
        //     return true;
        // } else {
        //     return false;
        // }

        $subject = 'LivinCube Password Reset';
        $message = 'Your request for password reset had been confirmed. Please click this link to reset your password: ' . $link;

        $emailResult = $this->send_email($to, SMTP_USER, "Livincube", $subject, $message);
        if($emailResult['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function forgot_password_action() {
        $email = $this->input->post('email');
        $data = $this->data_model->forgot_password_action($email);
        if ($data['status'] == 1) {
            $link = base_url() . 'login/reset_password/' . $data['token'];
            if ($this->sendMail($link, $email) == true) {
                $data['status'] = 3;
                $data['msg'] = 'An email had been sent to your email address. Please reset your password from the email you received.';
            } else {
                $data['status'] = 4;
                $data['msg'] = 'Email unable to sent. Please try again later.';
            }
        }

        echo json_encode($data);
    }

    public function reset_password() {
        $segs = $this->uri->segment_array();
        unset($segs[1]); //unset /login
        unset($segs[2]); //unset /reset_password
        $token = implode('/', $segs);
        
        if (isset($token)) {
            $data = $this->data_model->reset_password($token);
            if ($data['status'] == 1) {
                $this->output('reset_password');
            } else {
                $this->load->view('404');
            }
        } else {
            $this->load->view('404');
        }
    }

    public function reset_password_action() {
        $token = $this->input->post('token');
        $newpassword = $this->input->post('newpassword');

        if (isset($token)) {
            $data = $this->data_model->reset_password_action($token, $newpassword);

            echo json_encode($data);
        } else {
            $this->load->view('404');
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        $this->session->set_userdata("user", array());
        redirect('/login');
    }



    public function dropdown_checking() {
        $this->data['dropdown_profile'] = $this->data['edit_permission'] = $this->permission_model->check_permission($this->user['Id'], $this->user['Role'], "Profile", "index");

        $this->output();
    }



    public function checkSession() {

        if($this->user['Id'] != null && $this->user['Email'] != null && $this->user['BuildingID'] != null && $this->user['Nickname'] != null && $this->user['Thumbnail'] != null) {

            $data = $this->data_model->validate_user_login($this->user['Id'], $this->user['Email']);

            $this->set_data($data);

        } else {

            $this->data['status'] = 2;

        }

        $this->output();

    }

    public function getBuildingID() {
        $buildingID = $this->input->post('buildingID');
        
        $data = $this->data_model->get_building_name($buildingID);
        $this->set_data($data);
        
        $this->user['BuildingID'] = $buildingID;
        $this->user['BuildingName'] = $data['buildingName'];
        $this->user['Role'] = $this->data_model->load_user_role($this->user['Id'], $buildingID);
        // RESET SESSION USER OBJECT
        $this->session->set_userdata('user', $this->user);
        
        $this->data['stat'] = TRUE;
        
        $this->output();
    }

    public function getLatestNotification() {
        $data = $this->notification_model->get_latest_notification($this->user['Id']);

        $newList = '';
        $unread = 0;
        foreach($data as $noti) {
            $newList .= '<a href="'.base_url().ltrim($noti->fldUrl, "/").'">';
            if($noti->fldRead==0) {
                $unread++;
                $newList .= '<div class="notification-messages info">';
            } else {
                $newList .= '<div class="notification-messages">';
            }
            $newList .= '<div class="user-profile">';
            $newList .= '<i class="fa fa-'.$noti->fldIcon.' font-35"></i>';
            $newList .= '</div>';
            $newList .= '<div class="message-wrapper">';
            $newList .= '<div class="heading">';
            $newList .= $noti->fldTitle;
            $newList .= '</div>';
            $newList .= '<div class="description">';
            $description = str_replace("<p>","",$noti->fldDescription);
            $description = str_replace("</p>","",$description);
            $newList .= $description;
            $newList .= '</div>';
            $newList .= '<div class="date pull-left">';
            $newList .= $this->time_elapsed_string($noti->fldCreatedDate);
            $newList .= '</div>';
            $newList .= '</div>';
            $newList .= '<div class="clearfix"></div>';
            $newList .= '</div>';
            $newList .= '</a>';
        }
        if($newList == '') {
            $newList = '<div class="notification-messages info text-center">0 notification</div>';
        }
        $this->data['newList'] = $newList;
        $this->data['newNotification'] = $unread;

        $this->output();
    }

    public function readLatestNotification() {
        $data = $this->notification_model->read_latest_notification($this->user['Id']);
        $this->set_data($data);

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

}



/* End of file welcome.php */

/* Location: ./application/controllers/welcome.php */