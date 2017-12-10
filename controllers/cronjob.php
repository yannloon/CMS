<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cronjob extends LC_Controller {

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
        
        $this->load->model('service_maintenance_model');
    }

    private function send_email($receiver, $sender, $senderName, $subject, $message) {
        $this->load->library('email');

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
        $this->email->send();
    }

    public function index() {
        $data['bills'] = $this->service_maintenance_model->cron_delete_expired_bills();
        $data['invoice'] = $this->service_maintenance_model->invoice_checking();
        $data['interest'] = $this->service_maintenance_model->interest_checking();
        $data['booking'] = $this->service_maintenance_model->cron_cancel_expired_booking();
        $data['auth'] = $this->service_maintenance_model->cron_update_authentication();

        if(count($data['invoice']['mailDetails']) > 0) {
            foreach($data['invoice']['mailDetails'] as $mail) {
                $thisSubject = 'New Invoice - ' . $mail->fldStatementNo . ' for unit number: ' . $mail->fldUnitNo;
                $thisMsg = 'Hi ' . $mail->fldFirstName . '<br/><br/>A new invoice has been generated. Please view the invoice details by clicking on the following link: <br/>' . base_url() . 'service_maintenance/view_invoice/' . $mail->fldID;
                $this->send_email($mail->fldEmail, $mail->SenderEmail, $mail->fldBuildingName, $thisSubject, $thisMsg);
            }
        }

        error_log(date("Y-m-d H:i:s")." | Cronjob executed");
        $fp = fopen('cronjob.txt', 'a');
        fwrite($fp, date("Y-m-d H:i:s")." | Cronjob executed.\n----------\n".json_encode($data)."\n\n");
        fclose($fp);
    }
}
/* End of file profile.php */
/* Location: ./application/controllers/profile.php */