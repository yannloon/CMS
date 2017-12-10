<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Landing extends LC_Controller {
    
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
    }
    
    public function index() {
        $this->output('landing/index');
    }
    
    public function form_submit() {
        $referer = $this->input->server('HTTP_REFERER');
        $forms = array("Contact Us");
        
        $is_ajax = intval($this->input->post('is_ajax')) === 1 ? TRUE : FALSE;
        $this->data['msg'] = array();
        $this->data['reload'] = FALSE;
        $this->data['redirect'] = FALSE;
        $this->data['url'] = "";
        
        $from_name = $this->input->post('from_name');
        $recipient = explode(";", $this->input->post('recipient'));
        $fid = $this->input->post('fid');
        $fields = $this->input->post('fields');
        $field_names_tmp = explode(";", $this->input->post('field_names'));
        $field_names = array();
        $compulsory = explode(";", $this->input->post('compulsory'));
        $recaptcha_response = $this->input->post('g-recaptcha-response');

        /* RECATPCHA */
        //$url = "https://www.google.com/recaptcha/api/siteverify";
        //$privatekey = "6LfpzgwTAAAAAGCqcRVCSqcIDvAu3A4b6quIL8ib";
        //$response = file_get_contents($url."?secret=".$privatekey."&response=".$recaptcha_response."&remoteip=".$_SERVER['REMOTE_ADDR']);
        //$data = json_decode($response);
        //if(!isset($data->success) || $data->success !== TRUE) {
        //    $err[] = "Verification failed!";
        //}

        /* FILES UPLOAD */
        //$supported_extension = array("jpg", "jpeg", "png", "gif", "pdf");
        //$file_names = explode(";", $this->input->post('file_names'));
        //$compulsory_files = explode(";", $this->input->post('compulsory_files'));
        $attachments = array();

        if(count($recipient) == 0) {
            $this->data['msg'][] = "Invalid parameter";
        } else {
            $tmp = array_keys($fields);
            for($i = 0; $i < count($field_names_tmp); $i++) {
                $field_names[$tmp[$i]] = $field_names_tmp[$i];
            }

            foreach($fields as $key => $field) {
                if($key == "agree_tnc" && $field == 0) {
                    $this->data['msg'][] = "You must agree to our <a href=\"javascript:window.open('https://www.livincube.com');\">Terms &amp; Condition</a> before proceeding.";
                } else if($key == "captcha" && strcmp($field, $_SESSION['_captcha'][$fid]['code']) !== 0) {
                    $this->data['msg'][] = "Incorrect verification captcha provided. Please return and try again.";
                } else if(in_array($key, $compulsory) && $field == "") {
                    $this->data['msg'][] = "<strong>".$field_names[$key]."</strong> must not be blank.";
                }
            }
            /*
            for($i = 0; $i < count($file_names); $i++) {
                $file = isset($_FILES["file_$i"]) ? $_FILES["file_$i"] : NULL;
                if(((bool)$compulsory_files && in_array($i, $compulsory_files)) && (!$file || empty($file['name']))) {
                    $this->data['msg'][] = "<strong>".$file_names[$i]."</strong> must be provided.";
                }

                if($file && !empty($file['name'])) {
                    $ext = get_file_extension($file['name']);
                    //if(($file["type"] != "image/gif") && ($file["type"] != "image/jpeg") && ($file["type"] != "image/pjpeg") && ($file["type"] != "image/png") && ($file["type"] != "application/pdf")) {
                    if(!in_array($ext, $supported_extension)) {
                        $this->data['msg'][] = "Unsupported format (GIF/JPG/PNG/PDF only).";
                    } else if($file["size"] > 2000000) {
                        $this->data['msg'][] = "File size exceeded limit (2MB).";
                    } else if($file["error"] > 0) {
                        $this->data['msg'][] = "Error occurred while uploading file. ERROR : ".$file["error"];
                    }
                    $file['name'] = $file_names[$i].".".$ext;
                    $attachments[] = $file;
                }
            }
            */
        }

        if(count($this->data['msg']) == 0) {
            /* GENERATE DATA TABLE */
            if(!file_exists($this->config->item('absolute_path')."/assets/tpl/tpl_email_plain.php")) {
                $this->data['msg'][] = "Failed to submit form. Required system file not found.";
            } else {
                $email_msg = file_get_contents($this->config->item('absolute_path')."/assets/tpl/tpl_email_plain.php");
                $content = "<br>\n";
                foreach($field_names as $key => $field) {
                    $content .= "$field<br>\n<strong>".nl2br($fields[$key])."</strong><br>\n<br>\n";
                }
                $email_msg = preg_replace('/{var:title}/', $forms[$fid], $email_msg);
                $email_msg = preg_replace('/{var:content}/', $content, $email_msg);

                $reply_email = isset($fields['email']) ? $fields['email'] : "";
                $reply_name = !empty($reply_email) && isset($fields['name']) ? $fields['name'] : "";

                $subject = $forms[$fid].(isset($fields['name']) ? " - ".$fields['name'] : "");

                $result = $this->send_mail_smtp($recipient, $subject, $email_msg, $reply_email, $reply_name, $from_name, count($attachments) > 0 ? TRUE : FALSE, $attachments);
                if($result !== true) {
                    $this->data['err'] = $result;
                } else {
                    $this->data['stat'] = TRUE;
                    $this->data['msg'] = "<i class=\"fa fa-check-circle fa-fw green\"></i> Your enquiry is submitted successfully. We will get back to you soon.";
                }
            }
        }
        
        $this->output();
    }
    
    public function send_mail_smtp($to, $subject, $msg, $reply_to = "", $reply_name = "", $from_name = "", $attach = FALSE, $files = array()) {
        include_once("assets/plugins/phpmailer/class.phpmailer.php");
        $mail = new PHPMailer();

        $mail->IsSMTP();                            // Set mailer to use SMTP
        $mail->Host     = "localhost";              // Specify main and backup server
        $mail->Username = "enquiry@livincube.com";  // SMTP username
        $mail->Password = "vCxBSMmD(e[}";           // SMTP password
        $mail->Port     = 587;                      // Port
        $mail->SMTPAuth = TRUE;                     // Turn on SMTP authentication
        $mail->CharSet  = "UTF-8";                  // Character Set

        //$mail->From     = $reply_to;
        //$mail->From     = $to[0];
        //$mail->FromName = empty($from_name) ? "Unknown" : $from_name;
        $mail->SetFrom("enquiry@livincube.com", empty($from_name) ? "Unknown" : $from_name);
        if(is_array($to)) {
            foreach($to as $email) {
                $mail->AddAddress($email);
            }
        } else {
            $mail->AddAddress($to);
        }
        $mail->AddReplyTo($reply_to, $reply_name);
        if($attach) {
            if(is_array($files)) {
                foreach($files as $file) {
                    $mail->AddAttachment($file['tmp_name'], $file['name']);
                }
            } else {
                $mail->AddAttachment($files['tmp_name'], $files['name']);
            }
        }

        $mail->WordWrap = 200;    // set word wrap to 50 characters
        $mail->IsHTML(TRUE);         // set email format to HTML

        $mail->Subject = $subject;
        $mail->MsgHTML($msg);

        $fp = fopen($this->config->item('absolute_path')."/email-log.txt", "a");
        if($mail->Send()) {
            if($fp) {
                fwrite($fp, "[".date("Y-m-d H:i:s")."] FORM : \"$subject\" | EMAIL : \"$reply_to\" | STATUS : SUCCESS\n");
                fclose($fp);
            }
            return TRUE;
        } else {
            if($fp) {
                fwrite($fp, "[".date("Y-m-d H:i:s")."] FORM : \"$subject\" | EMAIL : \"$reply_to\" | STATUS : ".$mail->ErrorInfo."\n");
                fclose($fp);
            }
            return "Failed to send email. | ERROR : ".$mail->ErrorInfo;
        }
    }
    
    public function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function get_file_extension($file_name) {
        return strtolower(substr(strrchr(strtolower($file_name), '.'), 1));
    }

    public function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        $start = $length * -1; //negative
        return (substr($haystack, $start) === $needle);
    }
}

/* End of file landing.php */
/* Location: ./application/controllers/landing/landing.php */