<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class User_management extends LC_Controller {

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
        $this->load->model('user_management_model');
        $this->set_title(isset($this->data['perm']['user_management']['view']) ? 'User Management' : 'My Tenant');
        
        $this->data['perm'] = $this->set_permission(array("building_management"));
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
        $property = explode(",",$this->user['Property']);
        
        $this->data['all_users'] = $this->user_management_model->get_user_listing($this->user['Id'], $this->user['AdminRole'], $this->user['Level'], $this->user['Role'], $property, 1, 20);
        $this->data['filter_roles'] = $this->user_management_model->get_filter_roles($this->user['Level']);
        $this->data['property'] = $this->user_management_model->get_user_property($property);
        
        if($this->data['all_users']['status'] == 1) {
            $this->output('user_management/user_management');
        } else {
            $this->output('404');
        }
    }

    public function reloadListing() {
        $property = explode(",", $this->user['Property']);
        
        $pageno = $this->input->post('pageno');
        
        $filterRole = $this->input->post('filterRole');
        $filterStatus = $this->input->post('filterStatus');
        $filterSearch = $this->input->post('filterSearch');
        $filterUnitOrder = $this->input->post('filterUnitOrder');
        $filterBuilding = $this->input->post('filterBuilding');
        $filterRow = $this->input->post('filterRow');
        $filterName = $this->input->post('filterName');
        $filterEmail = $this->input->post('filterEmail');
        $filterUnit = $this->input->post('filterUnit');
        
        $this->data['all_users'] = $this->user_management_model->get_user_listing($this->user['Id'], $this->user['AdminRole'], $this->user['Level'], $this->user['Role'], $property, $pageno, $filterRow, $filterRole, $filterStatus, $filterSearch, $filterUnitOrder, $filterBuilding, $filterName, $filterEmail, $filterUnit);
        
        $this->output();
    }

    // public function get_user()
    // {
    // 	$userID = $this->input->post('userID');
    //
	// 	$this->load->helper('url');
    // 	$this->load->model('user_management_model');
    // 	$data['user'] = $this->user_management_model->get_user($userID);
    //
	// 	$data['role'] = $this->user_management_model->get_role($userID);
    // }

    public function add_user() {
        if(isset($this->data['perm']['building_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['company'] = $this->user_management_model->get_company();
        $this->data['empty_unit'] = $this->user_management_model->get_empty_unit($this->user['Id'], $this->user['Role'], $viewAll, $this->user['BuildingID']);
        $this->data['occupied_unit'] = $this->user_management_model->get_occupied_unit($this->user['Id'], $this->user['Role'], $viewAll, $this->user['BuildingID']);
        $this->data['filter_roles'] = $this->user_management_model->get_filter_roles($this->user['Level']);

        if ($this->user['Role'] == TENANT_ROLE_ID) {
            $this->output('404');
        } else {
            $this->data['building'] = $this->user_management_model->get_building_listing();
            
            $this->output('user_management/add_user');
        }
    }

    public function add_user_action() {
        $email          = $this->input->post('email');
        $role           = $this->input->post('role');
        $firstname      = $this->input->post('firstname');
        $lastname       = $this->input->post('lastname');
        $nickname       = $this->input->post('nickname');
        $ic             = $this->input->post('ic');
        $gender         = $this->input->post('gender');
        $address        = $this->input->post('address');
        $address2       = $this->input->post('address2');
        $city           = $this->input->post('city');
        $postcode       = $this->input->post('postcode');
        $state          = $this->input->post('state');
        $country        = $this->input->post('country');
        $phone1         = $this->input->post('phone');
        $handphone1     = $this->input->post('handphone');
        $officephone    = $this->input->post('office');
        $race           = $this->input->post('race');
        $religion       = $this->input->post('religion');
        $occupation     = $this->input->post('occupation');
        $newName        = $this->input->post('image');
        $oriName        = $this->input->post('ori_image');
        $unit           = $this->input->post('unit');
        
        $data = $this->user_management_model->add_user($this->user['Id'], $this->user['Email'], $email, $role, $firstname, $lastname, $nickname, $ic, $gender, $address, $address2, $city, $postcode, $state, $country, $phone1, $handphone1, $officephone, $race, $religion, $occupation, $unit);
        $this->set_data($data);
        
        if($this->data['status'] == '1' && $newName != null && $newName != "") {
            $send = $this->data_model->add_new_image($this->user['Id'], $oriName, $newName, $this->data['user_id'], 1);
        }
        if($data['status'] == 1 && isset($data['new']))
        {
            $message = "Hi,<br/><br/>Your account has been created. Please login to Livincube (". base_url() . "login) using the following login:<br/><br/>Username: " . $email . " <br/><br/>Password: " . $this->data['password'];
            if($this->user['AdminRole'] == 1) {
                $this->send_email($email, 'enquiry@livincube.com', 'Livincube', 'Livincube - verify account', $message);
            } else {
                $this->send_email($email, 'enquiry@livincube.com', $this->user['BuildingName'], 'Livincube - verify account', $message);
            }
            
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblUser", $this->user['BuildingID'], "");
        }
        
        $this->output();
    }

    public function add_group() {
        $newID      = $this->input->post('new_id');
        $buildingID = $this->input->post('buildingID');
        $roleID     = $this->input->post('role_id');
        $groupName  = $this->input->post('group_name');
        $company    = $this->input->post('company');
        if($roleID != ADMIN_ROLE_ID) {
            $buildingID = $this->user['BuildingID'];
        }
        
        $data = $this->user_management_model->add_group($this->user['Id'], $newID, $buildingID, $roleID, $groupName, $company);
        $this->set_data($data);
        
        $this->output();
    }
    
    public function thumbnailupload() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $dir = $this->data_model->getDir("IMAGE", 1);
                if ($dir != "") {
                    $this->load->helper('upload');
                    $returndata = uploadImage($dir);

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

    public function activate_user() {
        $targetID = $this->input->post('targetID');
        
        $this->load->helper('url');
        if($this->user['Id'] != $targetID) {
            $data = $this->user_management_model->activate_user($this->user['Id'], $this->user['Role'], $this->user['Level'], $targetID, $this->user['BuildingID'], $this->user['AdminRole']);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                if($this->user['AdminRole'] == 1)
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", 0, "Activate User");
                }
                else
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['BuildingID'], "Activate User");
                }
            }
        } else {
            $this->data['status'] = 2;
            $this->data['msg'] = "Unable to remove your own account.";
        }
        
        $this->output();
    }

    public function suspend_user() {
        $targetID = $this->input->post('targetID');
        
        if($this->user['Id'] != $targetID) {
            $data = $this->user_management_model->suspend_user($this->user['Id'], $this->user['Role'], $this->user['Level'], $targetID, $this->user['BuildingID'], $this->user['AdminRole']);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                if($this->user['AdminRole'] == 1)
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", 0, "Suspend User");
                }
                else
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['BuildingID'], "Suspend User");
                }
            }
        } else {
            $this->data['status'] = 2;
            $this->data['msg'] = "Unable to remove your own account.";
        }

        $this->output();
    }

    public function remove_user() {
        $targetID = $this->input->post('targetID');
        
        if ($this->user['Id'] != $targetID) {
            $data = $this->user_management_model->remove_user($this->user['Id'], $this->user['Role'], $this->user['Level'], $targetID, $this->user['BuildingID'], $this->user['AdminRole']);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                if($this->user['AdminRole'] == 1)
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", 0, "Close Account");
                }
                else
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['BuildingID'], "Close Account");
                }
            }
        } else {
            $this->data['status'] = 2;
            $this->data['msg'] = "Unable to remove your own account.";
        }
        
        $this->output();
    }

    public function edit_user() {
        $targetID = $this->uri->segment(3);
        $property = explode(",", $this->user['Property']);
        
        $checking = $this->user_management_model->check_target($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $property);
        
        $isTarget = 0;
        foreach ($checking as $row) {
            if ($row->fldUserID == $targetID) {
                $isTarget = 1;
            }
        }
        
        $permission = $this->user_management_model->check_role_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $targetID, $this->user['BuildingID']);
        if ($isTarget == 1 && $permission['status'] == 1) {
            $this->data['edit_user'] = $this->user_management_model->get_user($targetID);
            $this->data['role'] = $this->user_management_model->get_role($targetID);
            $this->data['selfRole'] = $this->user_management_model->get_role($this->user['Id']);
            $this->data['building'] = $this->user_management_model->get_building_listing();
            $this->data['company'] = $this->user_management_model->get_company();
            $this->data['filter_roles'] = $this->user_management_model->get_filter_roles($this->user['Level']);
            
            if ($this->data['role']['roleID'] == PROPERTY_MANAGEMENT_ROLE_ID) {
                $this->data['targetCompany'] = $this->user_management_model->get_current_company($targetID);
            }

            if(isset($this->data['perm']['building_management']['view'])) {
                $viewAll = 1;
            } else {
                $viewAll = 0;
            }
            
            $this->data['empty_unit'] = $this->user_management_model->get_empty_unit($this->user['Id'], $this->user['Role'], $viewAll, $this->user['BuildingID']);
            $this->data['occupied_unit'] = $this->user_management_model->get_occupied_unit($this->user['Id'], $this->user['Role'], $viewAll, $this->user['BuildingID']);
            
            if ($this->data['role']['roleID'] == TENANT_ROLE_ID) {
                $this->data['target_unit'] = $this->user_management_model->get_target_unit($targetID);
            } else if ($this->data['role']['roleID'] == OWNER_ROLE_ID) {
                $this->data['target_unit_owner'] = $this->user_management_model->get_target_owner_unit($targetID);
            }
            $this->output('user_management/edit_user');
        } else {
            $this->output('404');
        }
    }

    public function edit_user_info() {
        $email          = $this->input->post('email');
        $firstname      = $this->input->post('firstname');
        $lastname       = $this->input->post('lastname');
        $nickname       = $this->input->post('nickname');
        $ic             = $this->input->post('ic');
        $gender         = $this->input->post('gender');
        $role           = $this->input->post('role');
        $address1       = $this->input->post('address1');
        $address2       = $this->input->post('address2');
        $city           = $this->input->post('city');
        $postcode       = $this->input->post('postcode');
        $state          = $this->input->post('state');
        $country        = $this->input->post('country');
        $phone1         = $this->input->post('phone1');
        $phone2         = $this->input->post('phone2');
        $handphone1     = $this->input->post('handphone1');
        $handphone2     = $this->input->post('handphone2');
        $officephone    = $this->input->post('officephone');
        $race           = $this->input->post('race');
        $religion       = $this->input->post('religion');
        $occupation     = $this->input->post('occupation');
        $newpassword    = $this->input->post('newpassword2');
        $targetID       = $this->input->post('targetID');
        $unit           = $this->input->post('unit');
        
        $data = $this->user_management_model->edit_user_info($this->user['Id'], $this->user['AdminRole'], $targetID, $email, $firstname, $lastname, $nickname, $ic, $gender, $role, $address1, $address2, $city, $postcode, $state, $country, $phone1, $phone2, $handphone1, $handphone2, $officephone, $race, $religion, $occupation, $newpassword, $unit);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            if($this->user['AdminRole'] == 1)
            {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", 0, "");
            }
            else
            {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['BuildingID'], "");
            }
        }
        
        $this->output();
    }

    public function edit_group() {
        $buildingID = $this->input->post('buildingID');
        $roleID       = $this->input->post('role');
        $groupName  = $this->input->post('group_name');
        $remove     = $this->input->post('remove');
        $targetID   = $this->input->post('targetID');
        $company    = $this->input->post('company');
        if($this->user['AdminRole'] != 1) {
            $buildingID = $this->user['BuildingID'];
        }
        
        $output = $this->user_management_model->remove_group($this->user['Id'], $targetID, $buildingID, $roleID, $remove);
        if ($output['status'] == '1') {
            $data = $this->user_management_model->add_group($this->user['Id'], $targetID, $buildingID, $roleID, $groupName, $company);
            $this->set_data($data);
            
            $this->output();
        }
    }

    public function change_user_password() {
        $targetID = $this->uri->segment(3);
        if ($targetID == null || $targetID == "") {
            $this->load->view('404');
        }
        $property = explode(",", $this->user['Property']);

        $checking = $this->user_management_model->check_target($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $property);

        $isTarget = 0;
        foreach ($checking as $row) {
            if($row->fldUserID == $targetID) {
                $isTarget = 1;
            }
        }

        $permission = $this->user_management_model->check_role_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $targetID, $this->user['BuildingID']);
        if ($isTarget == 1 && $permission['status'] == 1) {
            $this->data['target_user'] = $this->user_management_model->get_user($targetID);
            $this->output('user_management/change_user_password');
        } else {
            $this->output('404');
        }
    }

    public function change_user_password_action() {
        $property = explode(",", $this->user['Property']);
        
        $newPassword = $this->input->post('newPassword');
        $targetID = $this->input->post('targetID');
        
        $checking = $this->user_management_model->check_target($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $property);
        $isTarget = 0;
        foreach ($checking as $row) {
            if ($row->fldUserID == $targetID) {
                $isTarget = 1;
            }
        }
        
        if ($isTarget == 1) {
            $data = $this->user_management_model->change_user_password_action($this->user['Id'], $targetID, $newPassword);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                if($this->user['AdminRole'] == 1)
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", 0, "Change User Password");
                }
                else
                {
                    $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['BuildingID'], "Change User Password");
                }
            }
        }
        $this->output();
    }

    public function check_role_permission() {
        $targetID = $this->input->post('targetID');
        
        $data = $this->user_management_model->check_role_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['Level'], $targetID, $this->user['BuildingID']);
        $this->set_data($data);
        
        $this->output();
    }

    public function set_message_users() {
        $users = $this->input->post('users');
        
        $this->user['Message'] = implode(",", $users);
        // RESET SESSION USER OBJECT
        $this->session->set_userdata('user', $this->user);

        if(isset($users) && is_array($users)) {
            $this->data['message_users'] = implode(",", $users);
        }
        
        if (isset($this->data['message_users']) && $this->data['message_users'] != "") {
            $this->data['status'] = 1;
        } else {
            $this->data['msg'] = "Failed to proceed.";
        }
        $this->output();
    }

    public function get_all_selected_user() {
        $property = explode(",", $this->user['Property']);
        
        $filterRole = $this->input->post('filterRole');
        $filterStatus = $this->input->post('filterStatus');
        $filterSearch = $this->input->post('filterSearch');
        $filterUnitOrder = $this->input->post('filterUnitOrder');
        $filterBuilding = $this->input->post('filterBuilding');
        
        $this->data['all_users'] = $this->user_management_model->get_all_selected_user($this->user['Id'], $this->user['AdminRole'], $this->user['Level'], $this->user['Role'], $property, $filterRole, $filterStatus, $filterSearch, $filterUnitOrder, $filterBuilding);
        
        $this->output();
    }
    
    public function check_email() {
        $email = $this->input->post('email');
        
        $this->data['stat'] = TRUE;
        $this->data['found'] = $this->user_management_model->check_email($email);
        
        $this->output();
    }

    public function import_file() {
        $this->output('user_management/import_file');
    }

    public function import_file_action() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $this->load->helper('upload');
                $this->load->library('csvimport');
                $f = stripslashes($_FILES['file']['name']);
                $extension = get_image_extension($f);
                if ($extension == 'csv') {
                    $uploadData = uploadCSV();
                    $file_path = $uploadData['stored_pathname'].$uploadData['random_filename'];
                    $csv_array = $this->csvimport->get_array($file_path);
                    $data['pass'] = true;
                    $data['row'] = array();
                    $emailStack = array();
                    $ownerUnitStack = array();
                    foreach ($csv_array as $row) {
                        $insert_data = array(
                            'number'=>$row['NUMBER'],
                            'email'=>$row['EMAIL'],
                            'firstname'=>$row['FIRSTNAME'],
                            'lastname'=>$row['LASTNAME'],
                            'nickname'=>$row['NICKNAME'],
                            'nric'=>$row['NRIC'],
                            'role'=>$row['ROLE'],
                            'company'=>$row['COMPANY'],
                            'units'=>$row['UNITS'],
                            'gender'=>$row['GENDER'],
                            'race'=>$row['RACE'],
                            'religion'=>$row['RELIGION'],
                            'occupation'=>$row['OCCUPATION'],
                            'phone'=>$row['PHONE'],
                            'handphone'=>$row['HANDPHONE'],
                            'officephone'=>$row['OFFICEPHONE'],
                            'address1'=>$row['ADDRESS1'],
                            'address2'=>$row['ADDRESS2'],
                            'city'=>$row['CITY'],
                            'postcode'=>$row['POSTCODE'],
                            'state'=>$row['STATE'],
                            'country'=>$row['COUNTRY'],
                        );
                        $validate = $this->user_management_model->validate_insert_row($this->user['BuildingID'], $insert_data);
                        if($validate['status'] == 2) {
                            $data['pass'] = false;
                            array_push($data['row'], $validate['errorRow']);
                        }

                        //check for duplicate email in imported file
                        if(in_array($row['EMAIL'], $emailStack)) {
                            $importErrorMsg[0] = $row['NUMBER'] . " - EMAIL - Duplicate email address found (" . $row['EMAIL'] . ")";
                            array_push($data['row'], $importErrorMsg);
                            $data['pass'] = false;
                        } else {
                            array_push($emailStack, $row['EMAIL']);
                        }

                        //check for duplicate unit for owner.
                        if(strtolower($row['ROLE']) == "owner") {
                            $unitsArray = explode(";", $row['UNITS']);
                            foreach($unitsArray as $ua) {
                                if(in_array($ua, $ownerUnitStack)) {
                                    $importErrorMsg[0] = $row['NUMBER'] . " - UNITS - Duplicate unit found (" . $ua . ")";
                                    array_push($data['row'], $importErrorMsg);
                                    $data['pass'] = false;
                                } else {
                                    array_push($ownerUnitStack, $ua);
                                }
                            }
                        }
                    }
                    if($data['pass'] == true) {
                        $rowInserted = 0;
                        $failedInsert = array();
                        foreach ($csv_array as $row) {
                            $insert_data = array(
                                'number'=>$row['NUMBER'],
                                'email'=>$row['EMAIL'],
                                'firstname'=>$row['FIRSTNAME'],
                                'lastname'=>$row['LASTNAME'],
                                'nickname'=>$row['NICKNAME'],
                                'nric'=>$row['NRIC'],
                                'role'=>$row['ROLE'],
                                'company'=>$row['COMPANY'],
                                'units'=>$row['UNITS'],
                                'gender'=>$row['GENDER'],
                                'race'=>$row['RACE'],
                                'religion'=>$row['RELIGION'],
                                'occupation'=>$row['OCCUPATION'],
                                'phone'=>$row['PHONE'],
                                'handphone'=>$row['HANDPHONE'],
                                'officephone'=>$row['OFFICEPHONE'],
                                'address1'=>$row['ADDRESS1'],
                                'address2'=>$row['ADDRESS2'],
                                'city'=>$row['CITY'],
                                'postcode'=>$row['POSTCODE'],
                                'state'=>$row['STATE'],
                                'country'=>$row['COUNTRY'],
                            );
                            $insertRow = $this->user_management_model->insert_user_row($this->user['Id'], $this->user['BuildingID'], $insert_data);
                            if($insertRow['status'] == 1) {
                                $message = "Hi,<br/><br/>Your account has been created. Please login to Livincube (". base_url() . "login) using the following login:<br/><br/>Username: " . $row['EMAIL'] . " <br/><br/>Password: " . $insertRow['password'];
                                $this->send_email($row['EMAIL'], 'enquiry@livincube.com', $this->user['BuildingName'], 'Livincube - verify account', $message);
                                $rowInserted++;
                            } else {
                                array_push($failedInsert, $insertRow['msg']);
                            }
                        }
                        if(count($failedInsert) > 0) {
                            $data['status'] = 2;
                            $data['msg'] = "Error occured while importing file. Please reimport the row specified.";
                            $data['failedInsert'] = $failedInsert;
                        } else {
                            $data['status'] = 1;
                            $data['msg'] = $rowInserted . " Row(s) Imported Successfully";
                        }
                    }
                } else {
                    $data['msg'] = 'File type not supported';
                }
            } else {
                $data['msg'] = 'Filename not found';
            }
        }
        $this->set_data($data);
        $this->output();
    }
}
/* End of file user_management.php */
/* Location: ./application/controllers/user_management.php */