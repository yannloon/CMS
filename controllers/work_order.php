<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Work_order extends LC_Controller {

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
        
        $this->load->model('work_order_model');
        $this->load->model('notification_model');

        $this->data['perm'] = $this->set_permission(array("building_management"));
    }

    //For permission purposes
    public function view_all_request()
    {
        return true;
    }

    public function request()
    {
        return true;
    }

    public function complaint()
    {
        return true;
    }

    public function index()
    {
        $property = explode(",",$this->user['Property']);

        if(isset($this->data['perm']['work_order']['view_all']) || isset($this->data['perm']['schedule_job']['view_all'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }
        if(isset($this->data['perm']['work_order']['request']) || isset($this->data['perm']['schedule_job']['request'])) {
            $requestPerm = 1;
        } else {
            $requestPerm = 0;
        }
        if(isset($this->data['perm']['work_order']['complaint']) || isset($this->data['perm']['schedule_job']['complaint'])) {
            $complaintPerm = 1;
        } else {
            $complaintPerm = 0;
        }

        $this->data['complaint'] = $this->work_order_model->get_complaint_listing($this->user['Id'], $buildingAll, $viewAll, $this->user['BuildingID'], 1, 20, "", 1);
        $this->data['property'] = $this->work_order_model->get_user_property($property);
        $this->data['category'] = $this->work_order_model->get_complaint_category($buildingAll, $this->user['BuildingID'], $requestPerm, $complaintPerm);
        
        if ($this->data['complaint']['status'] == 1) {
            $this->output('work_order/request_listing.php');
        } else {
            $this->output('404');
        }
    }

    public function reload_request_listing() {
        $pageno = $this->input->post('pageno');
        $filterBuilding = $this->input->post('filterBuilding');
        $filterStatus = $this->input->post('filterStatus');
        $filterCategory = $this->input->post('filterCategory');
        $filterType = $this->input->post('filterType');
        $filterRow = $this->input->post('filterRow');
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');
        $filterSearch = $this->input->post('filterSearch');

        if(isset($this->data['perm']['work_order']['view_all']) || isset($this->data['perm']['schedule_job']['view_all'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }
        
        $this->data['complaint'] = $this->work_order_model->get_complaint_listing($this->user['Id'], $buildingAll, $viewAll, $this->user['BuildingID'], $pageno, $filterRow, $filterBuilding, $filterStatus, $filterCategory, $filterType, $formStartDate, $formEndDate, $filterSearch);
        $this->data['user']['Role'] = $this->user['Role'];
        
        $this->output();
    }

    public function make_request() {
        if(isset($this->data['perm']['work_order']['view_all']) || isset($this->data['perm']['schedule_job']['view_all'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }
        if(isset($this->data['perm']['work_order']['request']) || isset($this->data['perm']['schedule_job']['request'])) {
            $requestPerm = 1;
        } else {
            $requestPerm = 0;
        }
        if(isset($this->data['perm']['work_order']['complaint']) || isset($this->data['perm']['schedule_job']['complaint'])) {
            $complaintPerm = 1;
        } else {
            $complaintPerm = 0;
        }

        $this->data['complaint'] = $this->work_order_model->get_complaint_category($buildingAll, $this->user['BuildingID'], $requestPerm, $complaintPerm);

        $this->data['unit'] = $this->work_order_model->get_owner_units($this->user['Id'], $buildingAll, $viewAll, $this->user['BuildingID']);
        
        $this->output('work_order/make_request.php');
    }

    public function make_complaint_action() {
        $formCategory = $this->input->post('formCategory');
        $formUnit = $this->input->post('formUnit');
        $formComplaint = $this->input->post('formComplaint');
        $complaintType = $this->input->post('complaintType');
        $formComplaintTitle = $this->input->post('formComplaintTitle');
        
        $data = $this->work_order_model->make_complaint_action($this->user['Id'], $formCategory, $formUnit, $formComplaint, $complaintType, $formComplaintTitle);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComplaint", $this->user['BuildingID'], "Make Request");
        }
        
        $this->output();
    }

    public function view_request() {
        $complaintID = $this->uri->segment(3);
        
        if ($complaintID == null || $complaintID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['work_order']['edit']) || isset($this->data['perm']['schedule_job']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }
        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }
        
        $this->data['complaint'] = $this->work_order_model->get_complaint_details($this->user['Id'], $buildingAll, $manage, $this->user['BuildingID'], $complaintID);
        if($this->data['complaint']['status'] == 1) {
            $this->output('work_order/view_request');
        } else {
            $this->output('404');
        }
    }

    public function update_complaint_status() {
        $formStatus = $this->input->post('formStatus');
        $formRemarks = $this->input->post('formRemarks');
        $formAssign = $this->input->post('formAssign');
        $formPriority = $this->input->post('formPriority');
        $complaintID = $this->input->post('complaintID');
        $sendEmail = $this->input->post('sendEmail');
        
        $data = $this->work_order_model->update_complaint_status($this->user['Id'], $this->user['Role'], $formStatus, $formRemarks, $formAssign, $formPriority, $complaintID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComplaint", $this->user['BuildingID'], "Update Request Status");

            $userData = $this->work_order_model->get_complaint_user_id($complaintID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], "Status Updated - " . $userData['title'], $formRemarks, "/work_order/view_request/".$userData['complaintID'], "th-list");
            }

            if($formStatus == 4 && $sendEmail == 1)
            {
                $config = array(
                  'protocol' => 'smtp',
                  'smtp_host' => 'ssl://smtp.googlemail.com',
                  'smtp_port' => 465,
                  'smtp_user' => 'livincubeTest@gmail.com',
                  'smtp_pass' => '123test123',
                  'mailtype' => 'html',
                  'charset' => 'iso-8859-1',
                  'wordwrap' => TRUE
                );

                $message = "Your work request has been completed. Action: " . $formRemarks ." Thank you.";

                $this->load->library('email', $config);
                $this->email->set_newline("\r\n");
                $this->email->from($data['updatedBy']->fldEmail, $data['updatedBy']->fldFirstName);
                $this->email->to($data['sendTo']->fldEmail);
                $this->email->subject("Completed - " . $data['sendTo']->fldComplaintTitle);
                $this->email->message($message);
                
                if($this->email->send())
                {
                    $this->data['status'] = '1';
                    $this->data['msg'] = 'Status Updated';
                }
            }
        }
        
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
                    $dir = $this->data_model->getDir($type, 8);
                }
                if ($dir != "") {
                    if ($type == "IMAGE") {
                        $returndata = uploadImage($dir);
                        $this->data['uploaded'] = 'IMAGE';
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

    public function thumbnailupload_status() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $this->load->helper('upload');
                $f = stripslashes($_FILES['file']['name']);
                $extension = get_image_extension($f);
                if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'gif') {
                    $type = "IMAGE";
                    $dir = $this->data_model->getDir($type, 9);
                }
                if ($dir != "") {
                    if ($type == "IMAGE") {
                        $returndata = uploadImage($dir);
                        $this->data['uploaded'] = 'IMAGE';
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

    public function postImage() {
        $newName = $this->input->post('image');
        $oriName = $this->input->post('ori_image');
        $uploaded = $this->input->post('uploaded');
        $complaintID = $this->input->post('complaintID');
        $imageType = $this->input->post('imageType');
        
        if ($uploaded == "IMAGE") {
            $data = $this->data_model->add_new_image_allowduplicate($this->user['Id'], $oriName, $newName, $complaintID, $imageType);
        }
        $this->set_data($data);
        $this->output();
    }

    public function request_category() {
        $type = 1;

        $property = explode(",",$this->user['Property']);
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }

        $this->data['complaint'] = $this->work_order_model->get_complaint_category_list($buildingAll, $this->user['BuildingID'], $pageno, $filterRow, $type);
        $this->data['property'] = $this->work_order_model->get_user_property($property);
        $this->data['AdminRole'] = $this->user['AdminRole'];
        $this->data['type'] = $type;
        $this->output('work_order/category');
    }

    public function complaint_category() {
        $type = 2;

        $property = explode(",",$this->user['Property']);
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }

        $this->data['complaint'] = $this->work_order_model->get_complaint_category_list($buildingAll, $this->user['BuildingID'], $pageno, $filterRow, $type);
        $this->data['property'] = $this->work_order_model->get_user_property($property);
        $this->data['AdminRole'] = $this->user['AdminRole'];
        $this->data['type'] = $type;
        $this->output('work_order/category');
    }

    public function add_request_category_action() {
        $formCategoryName = $this->input->post('formCategoryName');
        $formBuilding = $this->input->post('formBuilding');
        $formBuilding = $formBuilding ? $formBuilding : $this->user['BuildingID'];
        $type = $this->input->post('type');

        $data = $this->work_order_model->add_category_action($this->user['Id'], $formBuilding, $formCategoryName, $type);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComplaint", $data['new']->fldBuildingID, "Add Category");
        }        
        $this->output();
    }

    public function add_complaint_category_action() {
        $formCategoryName = $this->input->post('formCategoryName');
        $formBuilding = $this->input->post('formBuilding');
        $formBuilding = $formBuilding ? $formBuilding : $this->user['BuildingID'];
        $type = $this->input->post('type');

        $data = $this->work_order_model->add_category_action($this->user['Id'], $formBuilding, $formCategoryName, $type);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComplaint", $data['new']->fldBuildingID, "Add Category");
        }        
        $this->output();
    }

    public function delete_request_category_action() {
        $categoryID = $this->input->post('categoryID');

        $data = $this->work_order_model->delete_category_action($this->user['Id'], $categoryID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblComplaint", $data['old']->fldBuildingID, "Delete Category");
        } 
        $this->output();
    }

    public function delete_complaint_category_action() {
        $categoryID = $this->input->post('categoryID');

        $data = $this->work_order_model->delete_category_action($this->user['Id'], $categoryID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblComplaint", $data['old']->fldBuildingID, "Delete Category");
        } 
        $this->output();
    }

    public function edit_request_category_action() {
        $categoryID = $this->input->post('categoryID');
        $categoryName = $this->input->post('categoryName');

        $data = $this->work_order_model->edit_category_action($this->user['Id'], $categoryID, $categoryName);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblComplaint", $data['new']->fldBuildingID, "Edit Category");
        } 
        $this->output();
    }

    public function edit_complaint_category_action() {
        $categoryID = $this->input->post('categoryID');
        $categoryName = $this->input->post('categoryName');

        $data = $this->work_order_model->edit_category_action($this->user['Id'], $categoryID, $categoryName);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblComplaint", $data['new']->fldBuildingID, "Edit Category");
        } 
        $this->output();
    }

    public function roles() {
        $property = explode(",",$this->user['Property']);
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        if(isset($this->data['perm']['building_management']['view'])) {
            $buildingAll = 1;
        } else {
            $buildingAll = 0;
        }

        $this->data['complaint'] = $this->work_order_model->get_complaint_roles_list($buildingAll, $this->user['BuildingID'], $pageno, $filterRow);
        $this->data['property'] = $this->work_order_model->get_user_property($property);
        $this->data['role'] = $this->user['Role'];
        $this->output('work_order/roles');
    }

    public function add_roles_action() {
        $formBuilding = $this->input->post('formBuilding');
        $formBuilding = $formBuilding ? $formBuilding : $this->user['BuildingID'];
        $formRoleName = $this->input->post('formRoleName');
        $formContactName = $this->input->post('formContactName');
        $formCompany = $this->input->post('formCompany');
        $formContactNumber = $this->input->post('formContactNumber');

        $data = $this->work_order_model->add_roles_action($this->user['Id'], $formBuilding, $formRoleName, $formContactName, $formCompany, $formContactNumber);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblComplaintContact", $formBuilding, "Add Work Order Role");
        }
        $this->output();
    }

    public function edit_roles_action() {
        $roleID = $this->input->post('roleID');
        $formRoleName = $this->input->post('formRoleName');
        $formContactName = $this->input->post('formContactName');
        $formCompany = $this->input->post('formCompany');
        $formContactNumber = $this->input->post('formContactNumber');

        $data = $this->work_order_model->edit_roles_action($this->user['Id'], $roleID, $formRoleName, $formContactName, $formCompany, $formContactNumber);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblComplaintContact", $data['buildingID'], "Edit Work Order Role");
        } 
        $this->output();
    }

    public function delete_roles_action() {
        $roleID = $this->input->post('roleID');

        $data = $this->work_order_model->delete_roles_action($this->user['Id'], $roleID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblComplaintContact", $data['buildingID'], "Delete Work Order Role");
        } 
        $this->output();
    }
}
/* End of file profile.php */
/* Location: ./application/controllers/profile.php */