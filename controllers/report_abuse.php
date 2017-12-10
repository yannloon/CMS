<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report_abuse extends LC_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */

    function __construct()
    {
        parent::__construct();
        /* LOAD MODEL */
        $this->load->model('report_abuse_model');
    }

    public function index()
    {
        $page = intval($this->input->post('pageno'));
        $page = $page ? $page : 1;
        $filterRow = intval($this->input->post('filterRow'));
        $filterRow = $filterRow ? $filterRow : 20;

        $filterType = intval($this->input->post('filterType'));
        $filterStatus = intval($this->input->post('filterStatus'));
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');

        $data = $this->report_abuse_model->get_report_list($page, $filterRow, $filterType, $filterStatus, $formStartDate, $formEndDate);
        $this->set_data($data);
        
        $this->output('report_abuse/index');
    }
    
    public function report_act() {
        $itemId   = $this->input->post("itemId");
        $itemType = $this->input->post("type");
        $ra_type  = $this->input->post("ra_type");
        $ra_msg   = $this->input->post("ra_msg");
        
        $data = $this->report_abuse_model->report_abuse($this->user['Id'], $itemId, $itemType, $ra_type, $ra_msg);
        $this->set_data($data);
        if($data['stat'] == true && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblReportAbuse", $this->user['BuildingID'], "Report");
        }
        
        $this->output();
    }
    
    public function view() {
        $id = $this->uri->segment(3);
        
        $this->data['abuse'] = $this->report_abuse_model->get_abuse($id);
        
        $this->output('report_abuse/view');
    }
    
    public function do_action() {
        $act        = $this->input->post('act');
        $abuse_id   = $this->input->post('abuse_id');
        $remarks    = $this->input->post('remarks');
        
        if(!in_array($act, array("remove", "keep")) || !$abuse_id) {
            $this->data['msg'] = "Invalid parameters.";
            $this->output();
        }
        
        $data = $this->report_abuse_model->process_report($abuse_id, $act, $remarks, $this->user['Id']);
        $this->set_data($data);
        if($data['stat'] == true && isset($data['old']))
        {
            if($act == "remove") {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblReportAbuse", $this->user['BuildingID'], "Delete Report");
            }
            else {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblReportAbuse", $this->user['BuildingID'], "Update Report");
            }
        }

        $this->output();
    }
}