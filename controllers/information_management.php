<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Information_management extends LC_Controller {

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
        
        $this->load->model('information_management_model');
        $this->load->model('notification_model');
        $this->data['perm'] = $this->set_permission(array("building_management"));
    }
    
    /* DIRECTORY LISTING BEGIN */
    public function index() {
        $this->data['directory'] = $this->information_management_model->get_directory_view_listing($this->user['Id'], $this->user['Email'], $this->user['BuildingID'], 1, 20);
        
        if ($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->output('information_management/view_directory');
        } else {
            $this->output('404');
        }
    }

    public function reload_directory_listing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        
        $this->data['directory'] = $this->information_management_model->get_directory_view_listing($this->user['Id'], $this->user['Email'], $this->user['BuildingID'], $pageno, $filterRow);
        
        if($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->output();
        } else {
            $this->output('404');
        }
    }
    
    public function add_directory() {
        $this->data['directory'] = $this->information_management_model->get_directory_category($this->user['Id'], $this->user['Email']);
        
        if ($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->data['building'] = $this->information_management_model->get_admin_property_listing($this->user['Id'], $this->user['Email']);
            
            $this->output('information_management/add_directory');
        } else {
            $this->output('404');
        }
    }
    
    public function add_directory_action() {
        $category = $this->input->post('category');
        $directory = $this->input->post('directory');
        $hotline = $this->input->post('hotline');
        $description = $this->input->post('description');
        $property = $this->input->post('property');
        
        if ($property == "undefined" || $property == null) {
            $building = $this->user['BuildingName'];
        } else {
            $building = $property;
        }
        
        $data = $this->information_management_model->add_directory_action($this->user['Id'], $this->user['Email'], $building, $category, $directory, $hotline, $description);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblDirectory", $data['new']->fldBuildingID, "");

            $usersData = $this->permission_model->get_building_users($building, $this->__controller);
            $this->notification_model->insert_notification($usersData, $building, "New Information Added", $directory, "/information_management/directories", "folder");
        }
        
        $this->output();
    }
    
    public function edit_directory() {
        $directoryID = $this->uri->segment(3);
        if($directoryID == null || $directoryID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['building_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        
        $this->data['directory'] = $this->information_management_model->get_directory_info($this->user['Id'], $viewAll, $directoryID, $this->user['BuildingID']);
        
        if ($this->data['directory']['status'] == 1) {
            $this->output('information_management/edit_directory');
        } else {
            redirect('/information_management');
        }
    }
    
    public function edit_directory_action() {
        $directory = $this->input->post('directory');
        $hotline = $this->input->post('hotline');
        $description = $this->input->post('description');
        $directoryID = $this->input->post('directoryID');
        
        $data = $this->information_management_model->edit_directory_action($this->user['Id'], $this->user['Email'], $directoryID, $directory, $hotline, $description);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblDirectory", $data['new']->fldBuildingID, "");
        }
        
        $this->output();
    }
    
    public function delete_directory_action() {
        $directoryID = $this->input->post('directoryID');
        
        $data = $this->information_management_model->delete_directory_action($this->user['Id'], $this->user['Email'], $directoryID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblDirectory", $this->user['BuildingID'], "");
        }
        
        $this->output();
    }
    /* DIRECTORY LISTING END */
    
    /* DIRECTORY CATEGORY BEGIN */
    public function directory_categories() {
        $this->data['directory'] = $this->information_management_model->get_directory_type_listing($this->user['Id'], $this->user['Email'], 1, 20);
        
        if($this->data['directory']['status'] == 1) {
            $this->output('information_management/category_listing');
        } else {
            $this->output('404');
        }
    }
    
    public function reload_category_listing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        
        $this->data['directory'] = $this->information_management_model->get_directory_type_listing($this->user['Id'], $this->user['Email'], $pageno, $filterRow);
        
        $this->output();
    }
    
    public function add_category() {
        $this->data['building'] = $this->information_management_model->get_admin_property_listing($this->user['Id'], $this->user['Email']);
        
        if (!empty($this->data['building'])) {
            $this->output('information_management/add_category');
        } else {
            $this->output('404');
        }
    }
    
    public function add_category_action() {
        $category = $this->input->post('category');
        $description = $this->input->post('description');
        
        $data = $this->information_management_model->add_category_action($this->user['Id'], $this->user['Email'], $category, $description);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "information_category", 1, $data, "tblDirectoryType", 0, "Add Category");
        }
        
        $this->output();
    }
    
    public function edit_category() {
        $categoryID = $this->uri->segment(3);
        if($categoryID == null || $categoryID == "") {
            $this->output('404');
        }
        $this->data['directory'] = $this->information_management_model->get_category_detail($this->user['Id'], $this->user['Email'], $categoryID);
        
        if ($this->data['directory']['status'] == 1) {
            $this->output('information_management/edit_category');
        } else {
            $this->load->view('404');
        }
    }
    
    public function edit_category_action() {
        $category = $this->input->post('category');
        $description = $this->input->post('description');
        $categoryID = $this->input->post('categoryID');
        
        $data = $this->information_management_model->edit_category_action($this->user['Id'], $this->user['Email'], $categoryID, $category, $description);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "information_category", 2, $data, "tblDirectoryType", 0, "Edit Category");
        }
        
        $this->output();
    }
    
    public function delete_category_action() {
        $categoryID = $this->input->post('categoryID');
        
        $data = $this->information_management_model->delete_category_action($this->user['Id'], $this->user['Email'], $categoryID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], "information_category", 3, $data, "tblDirectoryType", 0, "Delete Category");
        }
        
        $this->output();
    }
    /* DIRECTORY CATEGORY END */
    
    /* DIRECTORIES BEGIN */
    public function directories() {
        $this->data['directory'] = $this->information_management_model->get_directories($this->user['BuildingID']);
        
        if ($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->output('information_management/directory_display');
        } else {
            $this->output('404');
        }
    }
    /* DIRECTORIES END */

    /* MANAGE_FILE BEGIN */
    public function shared_files() {
        $this->data['directory'] = $this->information_management_model->get_shared_files($this->user['Id'], $this->user['BuildingID'], 1, 20);
        
        if ($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->output('information_management/shared_files');
        } else {
            $this->output('404');
        }
    }

    public function manage_file() {
        $this->data['directory'] = $this->information_management_model->get_shared_files($this->user['Id'], $this->user['BuildingID'], 1, 20);
        
        if ($this->data['directory']['status'] == 1 || $this->data['directory']['status'] == 3) {
            $this->output('information_management/manage_file');
        } else {
            $this->output('404');
        }
    }

    public function reload_manage_file() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $viewType = $this->input->post('view');
        $viewType = $viewType ? $viewType : 0;
        
        $this->data['directory'] = $this->information_management_model->get_shared_files($this->user['Id'], $this->user['BuildingID'], $pageno, $filterRow);
        $directory = $this->data['directory'];
        $i = intval($directory['startId']) + 1;
        $resultReturn = '';
        if(isset($directory['results'])){
            foreach ($directory['results'] as $row) {
                $resultReturn .= '<tr id="directory'.$row->fldID.'" class="odd">';
                $resultReturn .= '<td>'.$i.'</td>';
                $resultReturn .= '<td>'.$row->fldFilename.'</td>';
                $resultReturn .= '<td>'.$row->fldDescription.'</td>';
                $resultReturn .= '<td>'.($row->fldSize/1000).'</td>';
                $resultReturn .= '<td>'.$row->fldCreatedDate.'</td>';
                $resultReturn .= '<td class="text-center">';
                if(isset($row->file)) {
                    $f = $row->file;
                    $imageDate = explode(" ", $f->file_date)[0];
                    $imageFolder = str_replace('-', '/', $imageDate);
                    if(isset($f->fldThumbnailImage)) {
                        $imageFolder = 'large/' . $imageFolder . '/large_';
                    } else {
                        $imageFolder = $imageFolder . '/';
                    }
                    $resultReturn .= '<a class="btn btn-success btn-xs btn-mini" href="'. $f->file_dir .'/' . $imageFolder . $f->new_name . '" target="_blank" download="' . $f->ori_name . '"><i class="fa fa-download"></i> Download</a>&nbsp;&nbsp';
                }
                if($viewType != 1) {
                    if(isset($this->data['perm']['information_shared_files']['remove'])) {
                        $resultReturn .= '<button onclick="setDelete('.$row->fldID.')" data-toggle="modal" data-target="#removeModal" class="btn btn-danger btn-xs btn-mini"><i class="fa fa-trash"></i> Delete</button>';
                    }
                }
                $resultReturn .= '</td>';
                $resultReturn .= '</tr>';
                $i++;
            }
            $this->data['resultNumber'] = $i - 1;
        }
        $this->data['resultReturn'] = $resultReturn;
        
        $this->output();
    }

    public function add_file() {
        $this->output('information_management/add_file');
    }

    public function add_file_action() {
        $this->is_ajax = TRUE;
        $description = $this->input->post('description');
        $data['status'] = 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $this->load->helper('upload');
                $f = stripslashes($_FILES['file']['name']);
                $extension = get_image_extension($f);
                if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'gif') {
                    $type = "IMAGE";
                    $dir = $this->data_model->getDir($type, 10);
                } else if ($extension == 'doc' || $extension == 'docx' || $extension == 'log' || $extension == 'txt' || $extension == 'pdf') {
                    $type = "DOCUMENT";
                    $dir = $this->data_model->getDir($type, 4);
                }
                if ($dir != "") {
                    if ($type == "IMAGE") {
                        $returndata = uploadImage($dir);
                    } else if ($type == "DOCUMENT") {
                        $returndata = uploadFile($dir);
                    }

                    if ($returndata['result'] == '1') {
                        $ori_image_name = $_FILES['file']['name'];
                        $new_image_name = $returndata['random_filename'];
                        $stored_pathname = $returndata['stored_pathname'];
                        $file_size = $_FILES['file']['size'];

                        $data = $this->information_management_model->add_file_action($this->user['Id'], $this->user['BuildingID'], $ori_image_name, $description, $file_size);
                        if($data['status'] == 1 && isset($data['new']))
                        {
                            $this->data_model->log_activity($this->user['Id'], "information_shared_files", 1, $data, "tblSharedFiles", $this->user['BuildingID'], "Upload File");

                            if ($type == "IMAGE") {
                                $this->data_model->add_new_image_allowduplicate($this->user['Id'], $ori_image_name, $new_image_name, $data['insert_id'], 10);
                            } else if ($type == "DOCUMENT") {
                                $this->data_model->add_new_document($this->user['Id'], $ori_image_name, $new_image_name, $data['insert_id'], 4);
                            }
                        }
                    } else {
                        $data['msg'] = $returndata['msg'];
                    }
                } else {
                    $data['msg'] = "File type not supported";
                }
            } else {
                $data['msg'] = 'Filename not found';
            }
        }
        $this->set_data($data);
        
        $this->output();
    }

    public function delete_file_action() {
        $fileID = $this->input->post('fileID');
        
        $data = $this->information_management_model->delete_file_action($this->user['Id'], $this->user['BuildingID'], $fileID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], "information_shared_files", 3, $data, "tblSharedFiles", $this->user['BuildingID'], "Delete File");
        }
        
        $this->output();
    }
    /* MANAGE_FILE END */
}
/* End of file information_management.php */
/* Location: ./application/controllers/information_management.php */