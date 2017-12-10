<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_matrix extends LC_Controller {

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
        
        $this->load->model('user_matrix_model');
    }

    public function index_old()
    {
        $this->data['permission'] = $this->user_matrix_model->get_permission_table();
        $this->data['default'] = $this->user_matrix_model->get_default_role_permission();
        $this->output('user_matrix/default_matrix.php');
    }

    public function update_default_matrix()
    {
        $selected = $this->input->post('selected');        

        foreach($selected as $s)
        {
            $thisRole = explode("-",$s)[0];
            $thisPermission = explode("-",$s)[1];
            if(!isset($insertPermission[$thisRole]))
            {
                $insertPermission[$thisRole] = array();
            }
            array_push($insertPermission[$thisRole], $thisPermission);
        }

        foreach($insertPermission as $key => $ip)
        {
            $thisString = implode(";",$ip);
            if(!isset($permissionString[$key]))
            {
                $permissionString[$key] = array();
            }
            array_push($permissionString[$key], $thisString);
        }

        $data = $this->user_matrix_model->update_default_matrix($this->user['Id'], $permissionString);
        $this->set_data($data);

        $this->output();
    }

    public function index()
    {
        $page = intval($this->input->post('pageno'));
        $page = $page ? $page : 1;
        $data = $this->user_matrix_model->get_building_list($page);
        $this->set_data($data);
        
        $this->output('user_matrix/condo_listing');
    }

    public function customize_matrix()
    {
        $buildingID = $this->uri->segment(3);
        
        if ($buildingID == null || $buildingID == "") {
            $this->output('404');
        }

        $this->data['permission'] = $this->user_matrix_model->get_permission_table();
        $this->data['custom'] = $this->user_matrix_model->get_custom_role_permission($buildingID);
        $this->data['default'] = $this->user_matrix_model->get_default_role_permission();
        $this->data['building'] = $this->user_matrix_model->get_building_info($buildingID);
        $this->output('user_matrix/custom_matrix.php');
    }
    
    public function configure_matrix() {
        $buildingID = $this->uri->segment(3);
        $roleID = $this->uri->segment(4);
        
        $this->data['matrix'] = $this->user_matrix_model->get_matrix($buildingID, $roleID);
        $this->output('user_matrix/configure_matrix');
    }
    
    public function save_matrix() {
        $buildingID = $this->input->post('buildingID');
        $roleID     = $this->input->post('roleID');
        $perms      = $this->input->post('perms');
        
        if(empty($buildingID) || empty($roleID) || !is_array($perms)) {
            $this->data['msg'] = "Invalid parameters!";
        } else {
            $result = $this->user_matrix_model->save_matrix($buildingID, $roleID, $perms);
            $this->set_data($result);
        }
        
        $this->output();
    }

    public function update_custom_matrix()
    {
        $selected = $this->input->post('selected');
        $buildingID = $this->input->post('buildingID');

        foreach($selected as $s)
        {
            $thisRole = explode("-",$s)[0];
            $thisPermission = explode("-",$s)[1];
            if(!isset($insertPermission[$thisRole]))
            {
                $insertPermission[$thisRole] = array();
            }
            array_push($insertPermission[$thisRole], $thisPermission);
            sort($insertPermission[$thisRole]);
        }

        foreach($insertPermission as $key => $ip)
        {
            $thisString = implode(";",$ip);
            if(!isset($permissionString[$key]))
            {
                $permissionString[$key] = array();
            }
            array_push($permissionString[$key], $thisString);
        }

        $data = $this->user_matrix_model->update_custom_matrix($this->user['Id'], $permissionString, $buildingID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblGroup", $buildingID, "Edit User Matrix");
        }

        $this->output();
    }

    public function update_to_default_matrix()
    {
        $buildingID = $this->input->post('buildingID');

        $data = $this->user_matrix_model->update_to_default_matrix($this->user['Id'], $buildingID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblGroup", $buildingID, "Edit User Matrix");
        }

        $this->output();
    }
    
    public function condo_listing() {
        redirect('/user_matrix');
    }
    
    public function manage_roles()
    {
        $roleID = $this->uri->segment(3);
        
        if(empty($roleID)) {
            $data = $this->user_matrix_model->get_all_roles();
            $data['act'] = 'list';
        } else if($roleID === "add") {
            $data['role'] = new stdClass();
            $data['role']->fldRoleName = "";
            $data['role']->fldRoleDesc = "";
            $data['role']->fldLevel = 3;
            
            $data['act'] = 'add';
        } else {
            $data = $this->user_matrix_model->get_role($roleID);
            if($data['role']->fldRoleID <= 5) {
                $this->go_home();
            }
            $data['act'] = 'edit';
        }
        $this->set_data($data);
        $this->data['building'] = $this->user_matrix_model->get_building_listing();
        $this->output('user_matrix/manage_roles');
    }

    public function add_role_action() {
        $this->is_ajax = true;
        $userID = $this->user['Id'];
        $new_role = $this->input->post("role");
        $building = $this->input->post("building");
        $new_role['fldUpdatedBy'] = $userID;

        $this->data = $this->user_matrix_model->add_role_action($new_role, $building);

        if($this->data['status'] == 1) {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data, "tblRole", 0, "");
        }
        
        $this->output();
    }

    public function edit_role_action() {
        if(!$this->is_ajax) {
            $this->go_home();
        }
        $userID = $this->user['Id'];
        $roleID = $this->input->post("role_id");
        $new_role = $this->input->post("role");
        $building = $this->input->post("building");
        $new_role['fldUpdatedBy'] = $userID;

        if($roleID > 5) {
            $this->data = $this->user_matrix_model->edit_role_action($roleID, $new_role, $building);

            if($this->data['status'] == 1)
            {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $this->data, "tblRole", 0, "");
            }
        }
        
        
        $this->output();
    }
}
/* End of file profile.php */
/* Location: ./application/controllers/profile.php */