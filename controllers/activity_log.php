<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Activity_log extends LC_Controller {

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
        /* LOAD MODEL */
        $this->load->model('activity_log_model');
        $this->set_title("Activity Log");

        $this->data['perm'] = $this->set_permission(array("building_management"));
    }

    public function index() {
        if(isset($this->data['perm']['building_management']['view'])) {
            $viewMultiple = 1;
        } else {
            $viewMultiple = 0;
        }

        $this->data['log'] = $this->activity_log_model->get_activity_log($this->user['Id'], $viewMultiple, $this->user['BuildingID'], 1, 20);
        $this->data['property'] = $this->activity_log_model->get_all_property();
        $this->data['controller'] = $this->activity_log_model->get_permitted_controller($this->user['Id'], $this->user['Role']);
        
        if($this->data['log']['status'] == 1) {
            $this->output('activity_log/index');
        } else {
            $this->output('404');
        }
    }

    public function reload_activity_log_listing() {
        $pageno = $this->input->post('pageno');
        $filterSearch = $this->input->post('filterSearch');
        $filterBuilding = $this->input->post('filterBuilding');
        $filterType = $this->input->post('filterType');
        $filterAction = $this->input->post('filterAction');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if(isset($this->data['perm']['building_management']['view'])) {
            $viewMultiple = 1;
        } else {
            $viewMultiple = 0;
        }
        
        $this->data['log'] = $this->activity_log_model->get_activity_log($this->user['Id'], $viewMultiple, $this->user['BuildingID'], $pageno, $filterRow, $filterSearch, $filterBuilding, $filterType, $filterAction);
        $this->data['Role'] = $this->user['Role'];
        
        if($this->data['log']['status'] == 1) {
            $this->output();
        } else {
            $this->output('404');
        }
    }

}
/* End of file activity_log.php */
/* Location: ./application/controllers/activity_log.php */