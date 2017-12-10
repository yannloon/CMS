<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends LC_Controller {

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
        
        $this->load->model('profile_model');
    }

    public function index() {
        $property = explode(",", $this->user['Property']);
        $this->data['user_profile'] = $this->profile_model->get_user_details($this->user['Id']);
        if (!empty($this->data['user_profile'])) {
            $this->data['building'] = $this->profile_model->get_building($property);
            $this->data['group'] = $this->profile_model->get_group($this->user['Id']);
            $this->data['units'] = $this->profile_model->get_units($this->user['Id'], $this->user['BuildingID']);

            //$this->data['edit_permission'] = $this->permission_model->check_permission($this->user['Id'], $this->user['Role'], $this->__controller, "editProfileDetails");
            if(count($property) == 1 && $this->user['AdminRole'] == 0) {
                $this->data['property_info'] = FALSE;
            } else {
                $this->data['property_info'] = TRUE;
            }
            
            $this->output('profile/profile');
        }
    }

    public function editProfileDetails() {
        $firstname = $this->input->post('firstname');
        $lastname = $this->input->post('lastname');
        $ic = $this->input->post('ic');
        $gender = $this->input->post('gender');
        $race = $this->input->post('race');
        $religion = $this->input->post('religion');
        $occupation = $this->input->post('occupation');
        
        $nickname = $this->input->post('nickname');
        
        $address1 = $this->input->post('address1');
        $address2 = $this->input->post('address2');
        $city = $this->input->post('city');
        $postcode = $this->input->post('postcode');
        $state = $this->input->post('state');
        $country = $this->input->post('country');
        
        $phone1 = $this->input->post('phone1');
        $phone2 = $this->input->post('phone2');
        $handphone1 = $this->input->post('handphone1');
        $handphone2 = $this->input->post('handphone2');
        $officephone = $this->input->post('officephone');
        
        $defaultCondo = $this->input->post('defaultCondo');
        
        $data = $this->profile_model->edit_profile_details($this->user['Id'], $this->user['Email'], $firstname, $lastname, $ic, $gender, $race, $religion, $occupation, $nickname, $address1, $address2, $city, $postcode, $state, $country, $phone1, $phone2, $handphone1, $handphone2, $officephone, $defaultCondo);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['Id'], "");
        }
        $this->user['Nickname'] = $nickname;
        $this->session->set_userdata("user", $this->user);
        
        $this->output();
    }

    public function editDefaultDetails() {
        $editNickName = $this->input->post('editNickName');
        
        $data = $this->profile_model->edit_default_profile_details($this->user['Id'], $editNickName);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblUser", $this->user['Id'], "");
        }
        $this->user['Nickname'] = $editNickName;
        $this->session->set_userdata("user", $this->user);
        
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

    public function postChangePhoto() {
        $newName = $this->input->post('image');
        $oriName = $this->input->post('ori_image');
        
        $data = $this->data_model->add_new_image($this->user['Id'], $oriName, $newName, $this->user['Id'], 1);
        $this->set_data($data);
        
        if($data['status'] == 1) {
            $imageDetail = $this->data_model->get_image_detail($data['id']);

            $imageDate = explode(" ", $imageDetail->fldCreatedDate)[0];
            $imageFolder = str_replace('-', '/', $imageDate);
            $this->user['Thumbnail'] = $imageFolder . '/' . $imageDetail->fldThumbnailImage;
            $this->data['imagePath'] = $imageFolder . '/' . $imageDetail->fldThumbnailImage;
            $this->session->set_userdata("user", $this->user);
        }
        
        $this->output();
    }

    public function view_profile() {
        $targetID = $this->uri->segment(3);
        
        if ($targetID == null || $targetID == "" || $targetID == $this->user['Id']) {
            redirect('/profile');
        }
        $this->data['profile_user'] = $this->profile_model->get_other_user_details($this->user['Id'], $targetID);
        
        if (!empty($this->data['profile_user'])) {
            $propertyArray = array();
            $propertyOutput = $this->data_model->get_property($targetID);
            foreach ($propertyOutput as $list) {
                array_push($propertyArray, $list->fldBuildingID);
            }
            $this->data['building'] = $this->profile_model->get_building($propertyArray);
            $this->data['group'] = $this->profile_model->get_group($targetID);
            $this->data['permission'] = $this->profile_model->message_permission($this->user['Id'], $this->user['Role'], $this->user['AdminRole'], $this->user['BuildingID'], $targetID);
            $this->data['image'] = $this->data_model->get_image($targetID, 1);
            $this->data['units'] = $this->profile_model->get_units($targetID, $this->user['BuildingID']);
            
            $this->output('profile/view_profile');
        } else {
            $this->output('404');
        }
    }
    
    public function change_password() {
        $this->output('profile/change_password');
    }

    public function change_password_action() {
        $currentPassword = $this->input->post('currentPassword');
        $newPassword = $this->input->post('newPassword');
        
        $data = $this->profile_model->change_password_action($this->user['Id'], $this->user['Email'], $currentPassword, $newPassword);
        $this->set_data($data);
        
        $this->output();
    }
}
/* End of file profile.php */
/* Location: ./application/controllers/profile.php */