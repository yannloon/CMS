<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Company_Management extends LC_Controller {

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
        
        $this->load->model('company_management_model');
    }

    public function Index() {
        $this->data['company'] = $this->company_management_model->get_company_listing($this->user['Id'], $this->user['Email'], 1, 20);
        
        $this->output('company_management/company_listing');
    }

    public function reload_company_listing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        
        $this->data['company'] = $this->company_management_model->get_company_listing($this->user['Id'], $this->user['Email'], $pageno, $filterRow);
        
        $this->output();
    }

    public function register_company() {
        //$checking = $this->company_management_model->get_company_listing($this->user['Id'], $this->user['Email'], 1, 20);
        
        $this->data['building'] = $this->company_management_model->get_building_listing();
        $this->output('company_management/register_company');
    }

    public function register_company_action() {
        $companyName = $this->input->post('companyName');
        $companyDescription = $this->input->post('companyDescription');
        $companyAddress = $this->input->post('companyAddress');
        $remark = $this->input->post('remark');
        $personName = $this->input->post('personName');
        $personContact = $this->input->post('personContact');
        
        $data = $this->company_management_model->register_company_action($this->user['Id'], $this->user['Email'], $companyName, $companyDescription, $companyAddress, $remark, $personName, $personContact);
        $this->set_data($data);

        if($data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblCompany", 0, "");
        }
        
        $this->output();
    }

    public function edit_company() {
        $companyID = $this->uri->segment(3);
        
        if ($companyID == null || $companyID == "") {
            $this->output('404');
        }
        
        $this->data['company'] = $this->company_management_model->get_company_detail($this->user['Id'], $this->user['Email'], $companyID);
        if($this->data['company']['status'] == 1) {
            $this->data['building'] = $this->company_management_model->get_building_listing();
            $this->data['selfBuilding'] = $this->company_management_model->get_company_building($companyID);
            
            $this->output('company_management/edit_company');
        } else {
            $this->output('404');
        }
    }

    public function edit_company_action() {
        $companyName = $this->input->post('companyName');
        $companyDescription = $this->input->post('companyDescription');
        $companyAddress = $this->input->post('companyAddress');
        $remark = $this->input->post('remark');
        $personName = $this->input->post('personName');
        $personContact = $this->input->post('personContact');
        $companyID = $this->input->post('companyID');
        
        $data = $this->company_management_model->edit_company_action($this->user['Id'], $this->user['Email'], $companyName, $companyDescription, $companyAddress, $remark, $personName, $personContact, $companyID);
        $this->set_data($data);

        if($data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblCompany", 0, "");
        }
        
        $this->output();
    }

    public function remove_company() {
        $companyID = $this->input->post('companyID');
        
        $data = $this->company_management_model->remove_company($this->user['Id'], $this->user['Email'], $companyID);
        $this->set_data($data);

        if($data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblCompany", 0, "");
        }
        
        $this->output();
    }

    public function view_company_users() {
        $companyID = $this->uri->segment(3);
        
        if ($companyID == null || $companyID == "") {
            $this->output('404');
        }
        
        $this->data['company'] = $this->company_management_model->get_company_users($this->user['Id'], $this->user['Email'], $companyID, 1, 20);
        
        if($this->data['company']['status'] == 1 || $this->data['company']['status'] == 3) {
            $this->output('company_management/view_company_users');
        } else {
            $this->output('404');
        }
    }

    public function reload_company_users_listing() {
        $this->load->library('session');
        $this->load->helper('url');
        $userID = $this->session->userdata('Id');
        $user_email = $this->session->userdata('Email');
        $companyID = $this->input->post('companyID');
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if ($companyID == null || $companyID == "") {
            $this->load->view('404');
        }

        $this->load->model('company_management_model');
        $this->data['company'] = $this->company_management_model->get_company_users($userID, $user_email, $companyID, $pageno, $filterRow);

        if ($this->data['company']['status'] == 1) {
            $this->output();
        } else {
            $this->load->view('404');
        }
    }

}
/* End of file company_management.php */
/* Location: ./application/controllers/company_management.php */