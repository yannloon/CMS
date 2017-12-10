<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Building_management extends LC_Controller {

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
        $this->load->model('building_management_model');
    }

    public function index()
    {
        $page = intval($this->input->post('pageno'));
        $page = $page ? $page : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $data = $this->building_management_model->get_building_list($page, $filterRow);
        $this->set_data($data);
        
        $this->output('building_management/index');
    }
    
    /* CONDO MANAGEMENT BEGIN */
    public function manage() {
        $building_id = $this->uri->segment(3);
        if(!empty($building_id) && !is_numeric($building_id)) {
            $this->go_home();
        }
        $data = array(
            'act' => empty($building_id) ? "add" : "edit",
            'building' => $this->building_management_model->get_building($building_id),
            'buildingTenure' => $this->building_management_model->get_building_tenure(),
            'buildingType' => $this->building_management_model->get_building_type(),
            'developer_list' => $this->building_management_model->get_developer_list()
        );
        $this->set_data($data);
        if(!$this->data['building']) {
            $this->go_home();
        }
        
        $this->output('building_management/manage');
    }

    public function add_building_action() {
        if(!$this->is_ajax) {
            $this->go_home();
        }
        $userID = $this->user['Id'];
        $new_building = $this->input->post("building");
        $new_building['fldUpdatedBy'] = $userID;
        $this->data = $this->building_management_model->add_building($new_building);

        if($this->data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data, "tblBuilding", 0, "");

            $roleArray = array();
            $getProperty = $this->data_model->get_property($this->user['Id']);
            foreach ($getProperty as $key => $list) {
                array_push($roleArray, $list->fldBuildingID);
            }
            if (!empty($roleArray)) {
                $this->user['Property'] = implode(",", $roleArray);
                $this->session->set_userdata("user", $this->user);
            }
        }
        
        $this->output();
    }

    public function edit_building_action() {
        if(!$this->is_ajax) {
            $this->go_home();
        }
        $userID = $this->user['Id'];
        $sessionBuildingID = $this->user['BuildingID'];
        $building_id = $this->input->post("building_id");
        $building = $this->input->post("building");
        $building['fldLastUpdDate'] = date("Y-m-d H:i:s");
        $building['fldUpdatedBy'] = $userID;
        $this->data = $this->building_management_model->edit_building($building, $building_id);
        if($this->data['status'] == 1 && $building_id == $sessionBuildingID) {
            $this->session->set_userdata(array(
                'BuildingID' => $building_id,
                'BuildingName' => $building['fldBuildingName']
            ));
        }
        if($this->data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $this->data, "tblBuilding", 0, "");
        }
        
        $this->output();
    }

    public function remove_building_action() {
        if(!$this->is_ajax) {
            $this->go_home();
        }

        $building_id = $this->input->post("building_id");
        $this->data = $this->building_management_model->remove_building($building_id);
        if($this->data['status'] == 1)
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $this->data, "tblBuilding", 0, "");

            $roleArray = array();
            $getProperty = $this->data_model->get_property($this->user['Id']);
            foreach ($getProperty as $key => $list) {
                array_push($roleArray, $list->fldBuildingID);
            }
            if (!empty($roleArray)) {
                $this->user['Property'] = implode(",", $roleArray);
                $this->session->set_userdata("user", $this->user);
            }
        }
        
        $this->output();
    }
    /* CONDO MANAGEMENT END */
    
    
    /* UNIT MANAGEMENT BEGIN */
    public function unit_listing()
    {
        if($this->uri->segment(3) != null)
        {
            $buildingID = $this->uri->segment(3);
        }

        if($this->input->post('buildingID'))
        {
            $buildingID = $this->input->post('buildingID');
        }

        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }

        if (!isset($buildingID) || $buildingID == null || $buildingID == "") {
            $this->output('404');
        }

        $page = intval($this->input->post('pageno'));
        $page = $page ? $page : 1;
        $filterUnit = $this->input->post('filterUnit');
        $filterOwner = $this->input->post('filterOwner');
        $filterParking = $this->input->post('filterParking');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $this->data['unit'] = $this->building_management_model->get_unit_list($buildingID, $page, $filterUnit, $filterOwner, $filterParking, $filterRow);
        $this->data['buildingID'] = $buildingID;

        $this->data['SF_SU'] = SF_SU;
        $getConstantName = $this->building_management_model->get_constant_name($buildingID);
        if($getConstantName['status'] == 1) {
            $this->data['SF_SU'] = $getConstantName['name'];
        }
        
        $this->output('building_management/unit_listing');
    }

    public function create_unit()
    {
        if($this->uri->segment(3) != null)
        {
            $buildingID = $this->uri->segment(3);
        }

        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }

        if (!isset($buildingID) || $buildingID == null || $buildingID == "") {
            $this->output('404');
        }
        
        $this->data['check'] = $this->building_management_model->check_building_unit($buildingID);
        $this->data['building'] = $this->building_management_model->get_building_details($buildingID);
        if($this->data['check']['status'] == 1) {
            $this->output('building_management/create_unit');
        } else {
            $this->output('404');
        }
    }

    public function create_unit_action()
    {
        $formBlockArray = $this->input->post('formBlockArray');
        $formFloorArray = $this->input->post('formFloorArray');
        $formNumberArray = $this->input->post('formNumberArray');
        $formFormat = $this->input->post('formFormat');
        $replaceFlag = $this->input->post('replaceFlag');
        $buildingID = $this->input->post('buildingID');
        if ($this->user['AdminRole'] != 1) {
            $buildingID = $this->user['BuildingID'];
        }
        $buildingUnitFormat = strtolower($formFormat) . ";" . $replaceFlag;
        
        $maxCounter = 0;
        $sumArray = array();
        $floorArray = array();
        foreach($formNumberArray as $number)
        {
            $currentArray = array();
            $currentFloor = "";
            for( $generatedNumber=1; $generatedNumber<=$number; $generatedNumber++ )
            {
                $stringGeneratedNumber = "$generatedNumber";
                if($replaceFlag == 1)
                {
                    if(strlen($stringGeneratedNumber) == 1)
                    {
                        $stringGeneratedNumber = str_replace("4","3A",$stringGeneratedNumber);
                    }
                    else if(strlen($stringGeneratedNumber) == 2 && $stringGeneratedNumber[1] == "4")
                    {
                        $stringGeneratedNumber = $stringGeneratedNumber[0] . "3A";
                    }
                }
                $currentUnit = strtolower($formFormat);                

                //change floor
                //add leading zero
                $thisFloor = "$formFloorArray[$maxCounter]";
                if($replaceFlag == 1)
                {
                    if(strlen($thisFloor) == 1)
                    {
                        $thisFloor = str_replace("4","3A",$thisFloor);
                    }
                    else if(strlen($thisFloor) == 2 && $thisFloor[1] == "4")
                    {
                        $thisFloor = $thisFloor[0] . "3A";
                    }
                }
                $currentFloor = $thisFloor;
                if(strlen($thisFloor) == 1)
                {
                    if (strpos($currentUnit, '0f') !== false) {
                        $thisFloor = "0" . $thisFloor;
                        $currentUnit = str_replace("0f",$thisFloor,$currentUnit);
                    }
                    else
                    {
                        $currentUnit = str_replace("f",$thisFloor,$currentUnit);
                    }
                }
                else
                {
                    if (strpos($currentUnit, '0f') !== false) {
                        $currentUnit = str_replace("0f",$thisFloor,$currentUnit);
                    }
                    else
                    {
                        $currentUnit = str_replace("f",$thisFloor,$currentUnit);
                    }                    
                }

                //change number
                if(strlen($stringGeneratedNumber) == 1)
                {
                    if (strpos($currentUnit, '0n') !== false) {
                        $stringGeneratedNumber = "0" . $stringGeneratedNumber;
                        $currentUnit = str_replace("0n",$stringGeneratedNumber,$currentUnit);
                    }
                    else
                    {
                        $currentUnit = str_replace("n",$stringGeneratedNumber,$currentUnit);
                    }
                }
                else
                {
                    if (strpos($currentUnit, '0n') !== false) {
                        $currentUnit = str_replace("0n",$stringGeneratedNumber,$currentUnit);
                    }
                    else
                    {
                        $currentUnit = str_replace("n",$stringGeneratedNumber,$currentUnit);
                    }
                }

                //change block (optional)
                $currentUnit = str_replace("b",$formBlockArray[$maxCounter],$currentUnit);

                array_push($currentArray, $currentUnit);
            }
            $sumArray[$maxCounter] = $currentArray;
            $floorArray[$maxCounter] = $currentFloor;
            $maxCounter++;
        }
        $maxCounter = $maxCounter - 1;
        $data = $this->building_management_model->create_unit_action($this->user['Id'], $buildingID, $sumArray, $maxCounter, $floorArray);
        $this->set_data($data);
        $this->building_management_model->update_building_unit_format($this->user['Id'], $buildingID, $buildingUnitFormat);

        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "unit_management", 1, $data, "tblUnit", $buildingID, "Create Unit");
        }
        
        $this->output();
    }

    public function edit_unit()
    {
        $unitID = $this->uri->segment(3);
        
        if ($unitID == null || $unitID == "") {
            $this->output('404');
        }
        
        $this->data['unit'] = $this->building_management_model->get_unit_details($unitID);
        if($this->data['unit']['status'] == 1) {
            if($this->user['AdminRole'] != 1 && $this->data['unit']['results']->fldBuildingID != $this->user['BuildingID']) {
                $this->output('404');
            } else {
                $this->data['SF_SU'] = SF_SU;
                $getConstantName = $this->building_management_model->get_constant_name($this->data['unit']['results']->fldBuildingID);
                if($getConstantName['status'] == 1) {
                    $this->data['SF_SU'] = $getConstantName['name'];
                }
                $this->output('building_management/edit_unit');
            }
        } else {
            $this->output('404');
        }
    }

    public function edit_unit_action()
    {
        $buildingID = $this->input->post('buildingID');
        $unitID = $this->input->post('unitID');
        $formUnit = $this->input->post('formUnit');
        $formParking = $this->input->post('formParking');
        $formSquareFt = $this->input->post('formSquareFt');
        $formDesc = $this->input->post('formDesc');
        $formFloor = $this->input->post('formFloor');

        $data = $this->building_management_model->edit_unit_action($this->user['Id'], $buildingID, $unitID, $formUnit, $formParking, $formSquareFt, $formDesc, $formFloor);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "unit_management", 2, $data, "tblUnit", $buildingID, "");
        }
        
        $this->output();
    }

    public function add_unit()
    {
        $buildingID = $this->uri->segment(3);
        if (!isset($buildingID) || $buildingID == null || $buildingID == "") {
            $this->output('404');
        }
        $this->data['SF_SU'] = SF_SU;
        $getConstantName = $this->building_management_model->get_constant_name($buildingID);
        if($getConstantName['status'] == 1) {
            $this->data['SF_SU'] = $getConstantName['name'];
        }
        
        $this->output('building_management/add_unit');
    }

    public function add_unit_action()
    {
        $buildingID = $this->input->post('buildingID');
        $formBlock = $this->input->post('formBlock');
        $formParking = $this->input->post('formParking');
        $formSquareFt = $this->input->post('formSquareFt');
        $formNumber = $this->input->post('formNumber');
        $formFloor = $this->input->post('formFloor');
        $formAccountCode = $this->input->post('formAccountCode');
        if ($this->user['AdminRole'] != 1) {
            $buildingID = $this->user['BuildingID'];
        }

        $data = $this->building_management_model->add_unit_action($this->user['Id'], $buildingID, $formBlock, $formSquareFt, $formFloor, $formNumber, $formAccountCode, $formParking);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "unit_management", 1, $data, "tblUnit", $buildingID, "");
        }
        
        $this->output();
    }

    public function delete_unit_action()
    {
        $unitID = $this->input->post('unitID');

        $data = $this->building_management_model->delete_unit_action($this->user['Id'], $this->user['Role'], $this->user['BuildingID'], $unitID);
        $this->set_data($data);
        if(isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], "unit_management", 3, $data, "tblUnit", $data['buildingID'], "");
        }
        
        $this->output();
    }

    public function unit_configuration()
    {
        if($this->uri->segment(3) != null)
        {
            $buildingID = $this->uri->segment(3);
        }

        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }

        if (!isset($buildingID) || $buildingID == null || $buildingID == "") {
            $this->output('404');
        }

        $this->data['SF_SU'] = SF_SU;
        $getConstantName = $this->building_management_model->get_constant_name($buildingID);
        if($getConstantName['status'] == 1) {
            $this->data['SF_SU'] = $getConstantName['name'];
        }

        $this->data['unit'] = $this->building_management_model->get_unit_configuration($buildingID);
        $this->data['buildingID'] = $buildingID;
        
        $this->output('building_management/unit_configuration');
    }

    public function update_unit_configuration()
    {
        if($this->input->post('buildingID'))
        {
            $buildingID = $this->input->post('buildingID');
        }

        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }
        $field = $this->input->post('field');
        
        $data = $this->building_management_model->update_unit_configuration($this->user['Id'], $buildingID, $field);
        $this->set_data($data);
        
        $this->output();
    }

    public function import_units() {
        if($this->uri->segment(3) != null)
        {
            $buildingID = $this->uri->segment(3);
        }

        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }

        if (!isset($buildingID) || $buildingID == null || $buildingID == "") {
            $this->output('404');
        } else {
            $this->data['check'] = $this->building_management_model->check_building_unit($buildingID);
            $this->data['building'] = $this->building_management_model->get_building_details($buildingID);
            if($this->data['check']['status'] == 1) {
                $this->output('building_management/import_units');
            } else {
                $this->output('404');
            }
        }
    }

    public function import_units_action() {
        $this->is_ajax = TRUE;

        $formFormat = $this->input->post('formFormat');
        $replace4 = $this->input->post('replace4');
        $buildingID = $this->input->post('buildingID');
        if($this->user['AdminRole'] != 1)
        {
            $buildingID = $this->user['BuildingID'];
        }
        
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
                    $unitStack = array();
                    $codeStack = array();
                    foreach ($csv_array as $row) {
                        $insert_data = array(
                            'number'=>$row['NUMBER'],
                            'unitNo'=>$row['UNIT_NUMBER'],
                            'accountCode'=>$row['ACCOUNT_CODE'],
                            'floorNo'=>$row['FLOOR_NUMBER'],
                            'squareFt'=>$row['SQUARE_FT'],
                            'parkingNo'=>$row['PARKING_LOT_NUMBER'],
                        );
                        $validate = $this->building_management_model->validate_insert_row($this->user['BuildingID'], $insert_data);
                        if($validate['status'] == 2) {
                            $data['pass'] = false;
                            array_push($data['row'], $validate['errorRow']);
                        }

                        //check for duplicate unit name in imported file
                        if(in_array(strtolower($row['UNIT_NUMBER']), $unitStack)) {
                            $importErrorMsg[0] = $row['NUMBER'] . " - UNIT NUMBER - Duplicate unit number found (" . $row['UNIT_NUMBER'] . ")";
                            array_push($data['row'], $importErrorMsg);
                            $data['pass'] = false;
                        } else {
                            array_push($unitStack, strtolower($row['UNIT_NUMBER']));
                        }

                        //check for duplicate account code in imported file
                        if(in_array(strtolower($row['ACCOUNT_CODE']), $codeStack) && $row['ACCOUNT_CODE'] != "") {
                            $importErrorMsg[0] = $row['NUMBER'] . " - ACCOUNT CODE - Duplicate account code found (" . $row['ACCOUNT_CODE'] . ")";
                            array_push($data['row'], $importErrorMsg);
                            $data['pass'] = false;
                        } else {
                            array_push($codeStack, strtolower($row['ACCOUNT_CODE']));
                        }
                    }
                    if($data['pass'] == true) {
                        $rowInserted = 0;
                        $failedInsert = array();
                        foreach ($csv_array as $row) {
                            $insert_data = array(
                                'number'=>$row['NUMBER'],
                                'unitNo'=>$row['UNIT_NUMBER'],
                                'accountCode'=>$row['ACCOUNT_CODE'],
                                'floorNo'=>$row['FLOOR_NUMBER'],
                                'squareFt'=>$row['SQUARE_FT'],
                                'parkingNo'=>$row['PARKING_LOT_NUMBER'],
                            );
                            $insertRow = $this->building_management_model->insert_unit_row($this->user['Id'], $buildingID, $insert_data);
                            $buildingUnitFormat = strtolower($formFormat) . ";" . $replace4;
                            $this->building_management_model->update_building_unit_format($this->user['Id'], $buildingID, $buildingUnitFormat);
                            if($insertRow['status'] == 1) {
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
    /* UNIT MANAGEMENT END */
}
