<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Facility_management extends LC_Controller {

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
        
        $this->load->model('facility_management_model');
        $this->load->model('notification_model');
        $this->load->library('billplz');

        $this->data['perm'] = $this->set_permission(array("building_management", "fee_management"));
    }

    public function Index() {
        $this->data['facility'] = $this->facility_management_model->get_facility_listing($this->user['Id'], $this->user['BuildingID'], 1, 20);
        
        if ($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
            $this->output('facility_management/facility_listing');
        } else {
            $this->output('404');
        }
    }
    
    public function reload_facility_listing() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        
        $this->data['facility'] = $this->facility_management_model->get_facility_listing($this->user['Id'], $this->user['BuildingID'], $pageno, $filterRow);
        
        if($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
            $this->output();
        } else {
            $this->output('404');
        }
    }
    
    public function add_facility() {
        $this->data['building'] = $this->facility_management_model->get_admin_property_listing($this->user['Id'], $this->user['Email']);
        
        if (!empty($this->data['building'])) {
            $this->output('facility_management/add_facility');
        } else {
            $this->output('404');
        }
    }
    
    public function add_facility_action() {
        $property = $this->input->post('property');
        $operatingHour = $this->input->post('operatingHour');
        $facility = $this->input->post('facility');
        $description = $this->input->post('description');
        $timeslot = $this->input->post('timeslot');
        $slotPerDay = $this->input->post('slotPerDay');
        $advanceBookingDay = $this->input->post('advanceBookingDay');
        $approval = $this->input->post('approval');
        $deposit = $this->input->post('deposit');
        $depositAmount = $this->input->post('depositAmount');
        $rental = $this->input->post('rental');
        $rentalAmount = $this->input->post('rentalAmount');
        
        if($property == "undefined" || $property == null) {
            $building = $this->user['BuildingName'];
        } else {
            $building = $property;
        }
        
        $data = $this->facility_management_model->add_facility_action($this->user['Id'], $this->user['Email'], $building, $property, $operatingHour, $facility, $description, $timeslot, $slotPerDay, $advanceBookingDay, $approval, $deposit, $depositAmount, $rental, $rentalAmount);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $data, "tblFacility", $data['new']->fldBuildingID, "");

            $usersData = $this->permission_model->get_building_users($this->user['BuildingID'], $this->__controller, 'booking_management');
            $this->notification_model->insert_notification($usersData, $this->user['BuildingID'], "New Facility Added", $facility, "/facility_management/facility_booking", "book");
        }
        
        $this->output();
    }
    
    public function edit_facility() {
        $facilityID = $this->uri->segment(3);
        if($facilityID == null || $facilityID == "") {
            $this->output('404');
        }
        
        $this->data['facility'] = $this->facility_management_model->get_facility_details($this->user['Id'], $this->user['AdminRole'], $facilityID, $this->user['BuildingID']);
        
        if ($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
            $this->output('facility_management/edit_facility');
        } else {
            redirect('/facility_management/facility_booking');
        }
    }
    
    public function edit_facility_action() {
        $operatingHour = $this->input->post('operatingHour');
        $facility = $this->input->post('facility');
        $description = $this->input->post('description');
        $facilityID = $this->input->post('facilityID');
        $timeslot = $this->input->post('timeslot');
        $slotPerDay = $this->input->post('slotPerDay');
        $advanceBookingDay = $this->input->post('advanceBookingDay');
        $approval = $this->input->post('approval');
        $formStatus = $this->input->post('formStatus');
        $deposit = $this->input->post('deposit');
        $depositAmount = $this->input->post('depositAmount');
        $rental = $this->input->post('rental');
        $rentalAmount = $this->input->post('rentalAmount');
        
        $data = $this->facility_management_model->edit_facility_action($this->user['Id'], $this->user['Email'], $facilityID, $operatingHour, $facility, $description, $timeslot, $slotPerDay, $advanceBookingDay, $approval, $formStatus, $deposit, $depositAmount, $rental, $rentalAmount);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 2, $data, "tblFacility", $data['new']->fldBuildingID, "");
        }
        
        $this->output();
    }

    public function delete_facility_action() {
        $facilityID = $this->input->post('facilityID');
        $status = 0;
        
        $data = $this->facility_management_model->change_facility_status_action($this->user['Id'], $facilityID, $status);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 3, $data, "tblFacility", $this->user['BuildingID'], "");
        }
        
        $this->output();
    }
    
    public function facility_booking() {
        $this->data['facility'] = $this->facility_management_model->get_facility_booking_listing($this->user['Id'], $this->user['BuildingID']);
        
        if ($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
            $this->output('facility_management/facility_booking');
        } else {
            $this->output('404');
        }
    }
    
    // public function reload_facility_booking_listing() {
    //     $pageno = $this->input->post('pageno');
        
    //     $this->data['facility'] = $this->facility_management_model->get_facility_booking_listing($this->user['Id'], $this->user['Email'], $this->user['BuildingID'], $pageno, 10);
        
    //     if ($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
    //         $this->output();
    //     } else {
    //         $this->output('404');
    //     }
    // }
    
    public function facility_new_booking() {
        $facilityID = $this->uri->segment(3);
        if($facilityID == null || $facilityID == "") {
            $this->output('404');
        }
        
        if(isset($this->data['perm']['booking_management']['access_all_bookings'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        $this->data['facility'] = $this->facility_management_model->get_facility_booking_details($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $facilityID, $this->user['BuildingID']);
        
        if ($this->data['facility']['status'] == 1 || $this->data['facility']['status'] == 3) {
            $this->output('facility_management/facility_new_booking');
        } else {
            redirect('/facility_management/facility_booking');
        }
    }

    public function access_all_bookings() {
        return true;
    }

    public function book_facility_action() {
        $unit = $this->input->post('unit');
        $remark = $this->input->post('remark');
        $bookingdatetime = $this->input->post('bookingdatetime');
        $facilityID = $this->input->post('facilityID');
        $totalValue = $this->input->post('totalValue');
        
        if($unit != null || $unit != "" || $unit != 0) {
            $data = $this->facility_management_model->book_facility_action($this->user['Id'], $this->user['Role'], $facilityID, $unit, $remark, $bookingdatetime, $totalValue);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                $this->data_model->log_activity($this->user['Id'], "booking_management", 1, $data, array("tblFacilityBooking", "tblBookingTimeSlot"), $this->user['BuildingID'], "");
            }
        } else {
            $data['status'] = 2;
            $data['msg'] = "Unit Number is required";
            $this->set_data($data);
        }
        
        $this->output();
    }
    
    public function my_facility_booking() {
        if(isset($this->data['perm']['booking_management']['access_all_bookings'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['booking'] = $this->facility_management_model->get_all_booking($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], 1, 20);
        $this->data['Role'] = $this->user['Role'];
        
        if ($this->data['booking']['status'] == 1 || $this->data['booking']['status'] == 3) {
            $this->output('facility_management/my_facility_booking');
        } else {
            $this->output('404');
        }
    }
    
    public function get_filteredBooking() {
        $pageno = $this->input->post('pageno');
        $filterProperty = $this->input->post('filterProperty');
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');
        $filterApproved = $this->input->post('filterApproved');
        $filterSearch = $this->input->post('filterSearch');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $filterDateType = $this->input->post('filterDateType');

        if(isset($this->data['perm']['booking_management']['access_all_bookings'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        
        $this->data['booking'] = $this->facility_management_model->get_filtered_booking($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], $pageno, $filterRow, $filterProperty, $formStartDate, $formEndDate, $filterApproved, $filterSearch, $filterDateType);
        
        $this->output();
    }
    
    public function delete_booking_action() {
        $bookingID = $this->input->post('bookingID');

        if(isset($this->data['perm']['booking_management']['manage'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }
        
        $data = $this->facility_management_model->delete_booking_action($this->user['Id'], $manage, $bookingID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['old']))
        {
            $this->data_model->log_activity($this->user['Id'], "booking_management", 3, $data, array("tblFacilityBooking", "tblBookingTimeSlot"), $this->user['BuildingID'], "Cancel Booking");
        }
        
        $this->output();
    }
    
    public function thumbnailupload() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $dir = $this->data_model->getDir("IMAGE", 3);
                if ($dir != "") {
                    $this->load->helper('upload');
                    $returndata = uploadImage($dir);

                    if ($returndata['result'] == '1') {
                        $this->data['status'] = '1';
                        $this->data['ori_image_name'] = $_FILES['file']['name'];
                        $this->data['new_image_name'] = $returndata['random_filename'];
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
    
    public function add_facility_image() {
        $facilityID = $this->input->post('facilityID');
        $oriName = $this->input->post('oriName');
        $newName = $this->input->post('newName');
        
        $data = $this->data_model->add_new_image($this->user['Id'], $oriName, $newName, $facilityID, 3);
        $this->set_data($data);
        
        $this->output();
    }
    
    public function edit_booking() {
        $bookingID = $this->uri->segment(3);
        if ($bookingID == null || $bookingID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['booking_management']['access_all_bookings'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }
        
        $this->data['facility'] = $this->facility_management_model->get_booking_details($this->user['Id'], $viewAll, $this->user['AdminRole'], $this->user['Role'], $bookingID);
        
        if ($this->data['facility']['status'] == 1) {
            $this->output('facility_management/my_facility_booking_edit');
        } else {
            redirect('/facility_management/my_facility_booking');
        }
    }
    
    public function edit_booking_action() {
        $unit = $this->input->post('unit');
        $remark = $this->input->post('remark');
        $bookingdatetime = $this->input->post('bookingdatetime');
        $bookingID = $this->input->post('bookingID');

        if(isset($this->data['perm']['booking_management']['access_all_bookings'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        if($unit != null || $unit != "" || $unit != 0) {
            $data = $this->facility_management_model->edit_booking_action($this->user['Id'], $viewAll, $bookingID, $unit, $remark, $bookingdatetime);
            $this->set_data($data);
            if($data['status'] == 1 && isset($data['new']))
            {
                $this->data_model->log_activity($this->user['Id'], "booking_management", 2, $data, array("tblFacilityBooking", "tblBookingTimeSlot"), $this->user['BuildingID'], "");
            }
        } else {
            $data['status'] = 2;
            $data['msg'] = "Unit Number is required";
            $this->set_data($data);
        }
        $this->data['roleID'] = $this->user['Role'];
        
        $this->output();
    }
    
    public function set_pending_booking_action() {
        $bookingID = $this->input->post('bookingID');
        
        $data = $this->facility_management_model->set_pending_booking_action($this->user['Id'], $bookingID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "booking_management", 2, $data, "tblFacilityBooking", $this->user['BuildingID'], "Set to Pending");

            $userData = $this->facility_management_model->get_booking_user_id($bookingID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], $userData['title'], "Your booking has been set to pending", "/facility_management/my_facility_booking", "book");
            }
        }
        
        $this->output();
    }

    public function approve_booking_action() {
        $bookingID = $this->input->post('bookingID');
        
        $data = $this->facility_management_model->approve_booking_action($this->user['Id'], $bookingID);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "booking_management", 2, $data, "tblFacilityBooking", $this->user['BuildingID'], "Approve Booking");

            $userData = $this->facility_management_model->get_booking_user_id($bookingID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], $userData['title'], "Your booking has been approved", "/facility_management/my_facility_booking", "book");
            }
        }
        
        $this->output();
    }

    public function reject_booking_action() {
        $bookingID = $this->input->post('bookingID');
        $rejectReason = $this->input->post('rejectReason');
        
        $data = $this->facility_management_model->reject_booking_action($this->user['Id'], $bookingID, $rejectReason);
        $this->set_data($data);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "booking_management", 2, $data, "tblFacilityBooking", $this->user['BuildingID'], "Reject Booking");

            $userData = $this->facility_management_model->get_booking_user_id($bookingID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], $userData['title'], "Your booking has been rejected", "/facility_management/my_facility_booking", "book");
            }
        }
        
        $this->output();
    }

    public function facility_payment() {
        $bookingNo = $this->uri->segment(3);
        if($bookingNo == null || $bookingNo == "") {
            $this->output('404');
        }
        
        $this->data['booking'] = $this->facility_management_model->get_booking_deposit_details($this->user['Id'], $bookingNo);
        $this->data['bookingNo'] = $bookingNo;
        
        if ($this->data['booking']['status'] == 1) {
            $this->output('facility_management/facility_payment');
        } else {
            $this->output('404');
        }
    }

    public function facility_payment_page() {
        $bookingNo = $this->uri->segment(3);
        if($bookingNo == null || $bookingNo == "") {
            $this->output('404');
        }
        
        $booking = $this->facility_management_model->get_booking_deposit_details($this->user['Id'], $bookingNo);

        //create bill start
        $userEmail = $this->facility_management_model->get_user_email($this->user['Id']);
        $billplzSettings = $this->facility_management_model->get_billplz_settings($this->user['BuildingID']);
        $amountinCent = ($booking['amountToPay'] * 100) + 20; // in cent + transaction fee RM0.20
        $this->data['status'] = 1;
        $this->data['msg'] = "";
        if($billplzSettings['status'] == 1) {
            $today = date('Y-m-d');
            $bplz = new Billplz(array('api_key' => $billplzSettings['billplz']->fldBillplzApiKey));
            $bplz->set_data(array(
                'collection_id' => $billplzSettings['billplz']->fldBillplzCollectionID,
                'email' => $userEmail,
                'name' => $this->user['Nickname'],
                'due_at' => $today,
                'amount' => $amountinCent,
                'description' => 'Livincube Payment for Facility Booking No: '. $bookingNo,
                'callback_url' => base_url()."facility_management/get_payment_callback",
                'redirect_url' => base_url()."facility_management/get_payment_object",
                'reference_1_label' => 'booking',
                'reference_1' => $bookingNo,
                'reference_2_label' => 'building',
                'reference_2' => $this->user['BuildingID']
            ));
            $results = $bplz->create_bill();
            $resultObj = json_decode($results);
            if (isset($resultObj->id)) {
                $billing = $this->facility_management_model->create_billing($this->user['Id'], $bookingNo, $resultObj);
                if($billing['status'] == 1) {
                    $this->data['url'] = $resultObj->url;
                } else {
                    $this->data['status'] = 2;
                    $this->data['msg'] = "Failed to create bill.";
                }
            } else {
                $this->data['status'] = 2;
                $this->data['msg'] = "Failed to create bill on Billplz.";
            }
        } else {
            $this->data['status'] = 2;
            $this->data['msg'] = "Please contact your administrator to set up the payment settings.";
        }
        //create bill end
        
        if ($booking['status'] == 1) {
            $this->output('facility_management/payment');
        } else {
            $this->output('404');
        }
    }

    public function get_payment_callback() {
        $billObj = $_POST;
        $billObj['id'] = $billObj['id'] ? $billObj['id'] : '';
        $billID = $billObj['id'];

        $billDetails = $this->facility_management_model->get_bill_details($billObj['id']);
        if($billDetails['status'] == 1) {
            $userBuildingID = $billDetails['details']->fldBuildingID;
            $userUserID = $billDetails['details']->fldUserID;

            $billplzSettings = $this->facility_management_model->get_billplz_settings($userBuildingID);
            if($billplzSettings['status'] == 1) {
                $bplz = new Billplz(array('api_key' => $billplzSettings['billplz']->fldBillplzApiKey));
                $signkey = $billplzSettings['billplz']->fldBillplzXSignature;

                $checkingData = [
                    'amount' => isset($_POST['amount']) ? $_POST['amount'] : exit('Amount is not supplied'),
                    'collection_id' => isset($_POST['collection_id']) ? $_POST['collection_id'] : exit('Collection ID is not supplied'),
                    'due_at' => isset($_POST['due_at']) ? $_POST['due_at'] : '',
                    'email' => isset($_POST['email']) ? $_POST['email'] : '',
                    'id' => isset($_POST['id']) ? $_POST['id'] : exit('Billplz ID is not supplied'),
                    'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : '',
                    'name' => isset($_POST['name']) ? $_POST['name'] : exit('Payer Name is not supplied'),
                    'paid_amount' => isset($_POST['paid_amount']) ? $_POST['paid_amount'] : '',
                    'paid_at' => isset($_POST['paid_at']) ? $_POST['paid_at'] : '',
                    'paid' => isset($_POST['paid']) ? $_POST['paid'] : exit('Paid status is not supplied'),
                    'state' => isset($_POST['state']) ? $_POST['state'] : exit('State is not supplied'),
                    'url' => isset($_POST['url']) ? $_POST['url'] : exit('URL is not supplied'),
                    'x_signature' => isset($_POST['x_signature']) ? $_POST['x_signature'] : exit('X Signature is not enabled'),
                ];
                $preparedString = "";
                foreach ($checkingData as $key => $value) {
                    $preparedString .= $key.$value;
                    if ($key === 'url') {
                        break;
                    } else {
                        $preparedString .= "|";
                    }
                }

                $generatedSHA = hash_hmac('sha256', $preparedString, $signkey);
                $billObj['paid'] = $billObj['paid'] === 'true' ? true : false;

                $results = json_decode($bplz->get_bill($billID));
                if($billObj['paid'] === true && $checkingData['x_signature'] == $generatedSHA) {
                    if($billDetails['status'] == 1 && $billDetails['details']->fldPaid == 0) {
                        $bookingNo = $results->reference_1;
                        $updateBill = $this->facility_management_model->update_bill($results);
                        if($updateBill['status'] == 1) {
                            $amountInRM = (floatval($results->paid_amount) - 20.0) / 100;
                            $payment = $this->facility_management_model->make_payment($userUserID, $bookingNo, $amountInRM, $billObj['id']);
                            if($payment['status'] == 1 && isset($payment['new'])) {
                                $this->facility_management_model->update_bill_receipt($results->id, $payment['receiptNo']);
                                $this->data_model->log_activity($userUserID, $this->__controller, 1, $payment, "tblReceipts", $userBuildingID, "Online Payment");
                            }
                        }
                    }
                }
            }
        }
    }

    public function get_payment_object() {
        $billObj = $this->input->get('billplz');
        $this->data['paymentStatus'] = 2;
        $this->data['paymentMsg'] = "Payment Failed";
        $billObj['id'] = $billObj['id'] ? $billObj['id'] : '';
        $billID = $billObj['id'];

        $billDetails = $this->facility_management_model->get_bill_details($billObj['id']);
        if($billDetails['status'] == 1) {
            $userBuildingID = $billDetails['details']->fldBuildingID;
            $userUserID = $billDetails['details']->fldUserID;

            $billplzSettings = $this->facility_management_model->get_billplz_settings($userBuildingID);
            if($billplzSettings['status'] == 1) {
                $bplz = new Billplz(array('api_key' => $billplzSettings['billplz']->fldBillplzApiKey));
                $signkey = $billplzSettings['billplz']->fldBillplzXSignature;

                $checkingData = [
                    'id' => isset($billObj['id']) ? $billObj['id'] : exit('Billplz ID is not supplied'),
                    'paid_at' => isset($billObj['paid_at']) ? $billObj['paid_at'] : exit('Please enable Billplz XSignature Payment Completion'),
                    'paid' => isset($billObj['paid']) ? $billObj['paid'] : exit('Please enable Billplz XSignature Payment Completion'),
                    'x_signature' => isset($billObj['x_signature']) ? $billObj['x_signature'] : exit('Please enable Billplz XSignature Payment Completion'),
                ];
                $preparedString = "";
                foreach ($checkingData as $key => $value) {
                    $preparedString .= "billplz".$key.$value;
                    if ($key === 'paid') {
                        break;
                    } else {
                        $preparedString .= "|";
                    }
                }

                $generatedSHA = hash_hmac('sha256', $preparedString, $signkey);
                $billObj['paid'] = $billObj['paid'] === 'true' ? true : false;

                $this->data['results'] = json_decode($bplz->get_bill($billID));
                if($billObj['paid'] === true && isset($this->data['results']->id) && $billObj['paid'] == $this->data['results']->paid && $checkingData['x_signature'] == $generatedSHA) {
                    if($billDetails['status'] == 1 && $billDetails['details']->fldPaid == 0) {
                        $bookingNo = $this->data['results']->reference_1;
                        $updateBill = $this->facility_management_model->update_bill($this->data['results']);
                        if($updateBill['status'] == 1) {
                            $amountInRM = (floatval($this->data['results']->paid_amount) - 20.0) / 100;
                            $this->data['payment'] = $this->facility_management_model->make_payment($userUserID, $bookingNo, $amountInRM, $billObj['id']);
                            if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new'])) {
                                $this->data['paymentStatus'] = 1;
                                $this->data['paymentMsg'] = "Payment Success";
                                $this->facility_management_model->update_bill_receipt($this->data['results']->id, $this->data['payment']['receiptNo']);
                                $this->data_model->log_activity($userUserID, $this->__controller, 1, $this->data['payment'], "tblReceipts", $userBuildingID, "Online Payment");
                            } else {
                                $this->data['paymentMsg'] = "Update Payment Failed.";
                            }
                        } else {
                            $this->data['paymentMsg'] = "Payment Failed. Unable to update bill.";
                        }
                    } else if($billDetails['status'] == 1 && $billDetails['details']->fldPaid == 1) {
                        $this->data['payment'] = $this->facility_management_model->get_receipt_id($this->data['results']->id);
                        $this->data['paymentStatus'] = 1;
                        $this->data['paymentMsg'] = "Payment Success!";
                    } else {
                        $this->data['paymentMsg'] = "Payment Failed. Bill not found.";
                    }
                } else if($billObj['paid'] === true && isset($this->data['results']->id)) {
                    $this->data['paymentMsg'] = "Payment fail due to altered data";
                }
            } else {
                $this->data['paymentMsg'] = "Payment Failed. Unable to retrieve payment settings";
            }
        } else {
            $this->data['paymentMsg'] = "Payment Failed. Unable to retrieve billing information";
        }

        if($this->data['paymentStatus'] == 1 && $this->user['Logged']) {
            redirect("/facility_management/view_receipt/".$this->data['payment']['receiptID']);
        } else {
            $this->output('facility_management/payment_completion');
        }
    }

    public function view_receipt() {
        $receiptID = $this->uri->segment(3);
        if($receiptID == null || $receiptID == "") {
            $this->output('404');
        }
        $this->load->model('service_maintenance_model');

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $this->data['receipt'] = $this->service_maintenance_model->get_receipt_details($this->user['Id'], $this->user['Role'], $manage, $this->user['AdminRole'], $this->user['BuildingID'], $receiptID);
        if(isset($this->data['perm']['building_management']['view'])) {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration_by_receipt($receiptID);
        }
        else {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);
        }

        if($this->data['receipt']['status'] == 1) {
            $this->output('service_maintenance/view_receipt');
        }
        else {
            $this->output('404');
        }
    }

    // public function make_payment() {
    //     $bookingNo = $this->input->post('bookingNo');
    //     $amount = $this->input->post('amount');

    //     $validate = $this->facility_management_model->get_booking_deposit_details($this->user['Id'], $bookingNo);
    //     if($validate['status'] == 1 && $amount <= MAX_PAY_AMOUNT) {
    //         $this->data['payment'] = $this->facility_management_model->make_payment($this->user['Id'], $bookingNo, $amount);
    //         if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new']))
    //         {
    //             $this->data_model->log_activity($this->user['Id'], "booking_management", 1, $this->data['payment'], "tblFacilityBooking", $this->data['payment']['new']->fldBuildingID, "Make Payment");
    //         }
    //     }

    //     $this->output();
    // }

    public function manual_payment() {
        $bookingID = $this->input->post('bookingID');
        $amount = $this->input->post('payment');

        $this->data['payment'] = $this->facility_management_model->set_payment_success($this->user['Id'], $this->user['Role'], $bookingID, $amount);
        if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "booking_management", 1, $this->data['payment'], "tblFacilityBooking", $this->data['payment']['new']->fldBuildingID, "Make Payment (By Management)");
        }

        $this->output();
    }

    public function refunds() {
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $filterRefunded = 0;

        if(isset($this->data['perm']['building_management']['view'])) {
            $allBuilding = 1;
        } else {
            $allBuilding = 0;
        }

        $data['refunds'] = $this->facility_management_model->get_refunds_list($allBuilding, $this->user['BuildingID'], $pageno, $filterRow, $filterRefunded);
        $this->set_data($data);
        $this->output('facility_management/refund_list');
    }

    public function reload_refunds() {
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $filterRefunded = $this->input->post('filterRefunded');
        $filterProperty = $this->input->post('filterProperty');
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');
        $filterApproved = $this->input->post('filterApproved');
        $filterSearch = $this->input->post('filterSearch');
        $filterSearchUnit = $this->input->post('filterSearchUnit');

        if(isset($this->data['perm']['building_management']['view'])) {
            $allBuilding = 1;
        } else {
            $allBuilding = 0;
        }

        $this->data['refunds'] = $this->facility_management_model->get_refunds_list($allBuilding, $this->user['BuildingID'], $pageno, $filterRow, $filterRefunded, $filterProperty, $formStartDate, $formEndDate, $filterApproved, $filterSearch, $filterSearchUnit);
        $refunds = $this->data['refunds'];
        $i = 0;
        $resultReturn = '';
        if (isset($refunds['results'])) {
            $tz = 'Asia/Singapore';
            $timestamp = time();
            $dt = new DateTime("now", new DateTimeZone($tz));
            $dt->setTimestamp($timestamp);
            $datetimenow = $dt->format('Y-m-d H:i');
            foreach($refunds['results'] as $row)
            {
                $resultReturn .= '<tr id="tableRow'.$row->fldTicketID.'" class="odd">';
                $resultReturn .= '<td>'.$row->fldTicketID.'</td>';
                if(isset($this->data['perm']['building_management']['view']))
                {
                    $resultReturn .= '<td>' . $row->fldBuildingName . '</td>';
                }
                $resultReturn .= '<td>'.$row->fldFacilityName.'</td>';
                $resultReturn .= '<td class="text-center">'.$row->fldUnitNo.'</td>';
                $resultReturn .= '<td class="text-center">';
                $bookingDateTime = new DateTime($row->fldBookingDate);
                $bookingDateTime = $bookingDateTime->format('Y-m-d');
                $resultReturn .= $bookingDateTime;
                $resultReturn .= '</td>';

                //checking able to refund or not
                $bookingTime = explode("-", $row->fldTimeSlot)[0];
                $fullBookingDateTime = $bookingDateTime . " " . $bookingTime;
                $bookingDateTimeFormat = date_create_from_format('Y-m-d H:i', $fullBookingDateTime);
                $stringBookingDateTime = date_format($bookingDateTimeFormat,"Y-m-d H:i");

                $refundStatus = 0;
                if(strtotime($stringBookingDateTime) <= strtotime($datetimenow)) {
                    $refundStatus = 1;
                } else if(strtotime($stringBookingDateTime) > strtotime($datetimenow)) {
                    if($row->fldApproved == 2 || $row->fldApproved == 3) {
                        $refundStatus = 1;
                    }
                }
                //end of checking

                $resultReturn .= '<td class="text-center">'.$row->fldTimeSlot.'</td>';
                if($refundStatus == 1) {
                    $resultReturn .= '<td class="text-center">'.$row->fldTotalRefundable.'</td>';
                } else {
                    $resultReturn .= '<td class="text-center">'.$row->fldPaidAmount.'</td>';
                }
                
                $resultReturn .= '<td class="text-center approve-column">';
                if($row->fldApproved == 3) {
                    $resultReturn .= '<span class="label label-default">Cancelled</span>';
                }
                else if ($row->fldApproved == 1) {
                    $resultReturn .= '<span class="label label-success">Approved</span>';
                }
                else if ($row->fldApproved == 2) {
                    $resultReturn .= '<span class="label label-danger">Rejected</span>';
                } else {
                    $resultReturn .= '<span class="label label-warning">Pending</span>';
                }
                $resultReturn .= '</td>';
                $resultReturn .= '<td class="text-center refund-column">';
                if($row->fldRefunded == 0) {
                    $resultReturn .= '<span class="label label-warning">Pending</span>';
                }
                else if ($row->fldRefunded == 1) {
                    $resultReturn .= '<span class="label label-info">Refunded</span>';
                }
                $resultReturn .= '</td>';
                $resultReturn .= '<td class="text-center action-column">';
                if($row->fldRefunded == 0 && isset($this->data['perm']['facility_refunds']['edit']))
                {
                    $resultReturn .= '<button onclick="makeRefund(\'' . $row->fldTicketID . '\')" class="btn btn-success btn-xs btn-mini">Refund</button>&nbsp;';
                    if($row->foundInvoice == 1) {
                        $resultReturn .= '<button onclick="makePayment(\'' . $row->fldTicketID . '\')" class="btn btn-primary btn-xs btn-mini">Transfer</button>&nbsp;';
                    }
                }
                $resultReturn .= '</td>';
                $resultReturn .= '</tr>';
                $i++;
            }
            $this->data['resultNumber'] = $i + $refunds['startId'];
        }
        else
        {
            if(isset($this->data['perm']['building_management']['view'])) {
                $resultReturn .= '<tr><td colspan="10" class="text-center">No data found in selected date range</td></tr>';
            } else {
                $resultReturn .= '<tr><td colspan="9" class="text-center">No data found in selected date range</td></tr>';
            }
        }
        $this->data['resultReturn'] = $resultReturn;

        $this->output();
    }

    public function confirm_refund() {
        $ticketID = $this->input->post('ticketID');

        if(isset($this->data['perm']['building_management']['view'])) {
            $allBuilding = 1;
        } else {
            $allBuilding = 0;
        }

        $this->data['refund'] = $this->facility_management_model->get_refunds_details($allBuilding, $this->user['BuildingID'], $ticketID);
        $invoice = $this->facility_management_model->get_unit_invoice($ticketID);
        $resultReturn = '';
        if($this->data['refund']['status'] == 1) {
            $refund = $this->data['refund']['refund'];
            $resultReturn .= '<table class="refund-table m-t-10">';

            $resultReturn .= '<tr>';
            $resultReturn .= '<th style="width:30%"></th>';
            $resultReturn .= '<th style="width:50%"></th>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Ticket ID</td>';
            $resultReturn .= '<td>'.$ticketID.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Facility Name</td>';
            $resultReturn .= '<td>'.$refund->fldFacilityName.'</td>';
            $resultReturn .= '</tr>';
            $bookingDate = new DateTime($refund->fldBookingDate);
            $bookingDateTime = $bookingDate->format('Y-m-d');

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Booking Date</td>';
            $resultReturn .= '<td>'.$bookingDateTime.' '.$refund->fldTimeSlot.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Unit No</td>';
            $resultReturn .= '<td>'.$refund->fldUnitNo.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Name</td>';
            $resultReturn .= '<td>'.$refund->fldFirstName.' '.$refund->fldLastName.'</td>';
            $resultReturn .= '</tr>';

            if($refund->fldApproved == 3) {
                $status = '<span class="label label-default">Cancelled</span>';
            } else if ($refund->fldApproved == 1) {
                $status = '<span class="label label-success">Approved</span>';
            } else if ($refund->fldApproved == 2) {
                $status = '<span class="label label-danger">Rejected</span>';
            } else {
                $status = '<span class="label label-warning">Pending</span>';
            }

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Booking Status</td>';
            $resultReturn .= '<td>'.$status.'</td>';
            $resultReturn .= '</tr>';

            //checking able to refund or not
            $tz = 'Asia/Singapore';
            $timestamp = time();
            $dt = new DateTime("now", new DateTimeZone($tz));
            $dt->setTimestamp($timestamp);
            $datetimenow = $dt->format('Y-m-d H:i');
            $bookingTime = explode("-", $refund->fldTimeSlot)[0];
            $fullBookingDateTime = $bookingDateTime . " " . $bookingTime;
            $bookingDateTimeFormat = date_create_from_format('Y-m-d H:i', $fullBookingDateTime);
            $stringBookingDateTime = date_format($bookingDateTimeFormat,"Y-m-d H:i");

            $this->data['refundStatus'] = 0;
            if(strtotime($stringBookingDateTime) <= strtotime($datetimenow)) {
                $this->data['refundStatus'] = 1;
            } else if(strtotime($stringBookingDateTime) > strtotime($datetimenow)) {
                if($refund->fldApproved == 2 || $refund->fldApproved == 3) {
                    $this->data['refundStatus'] = 1;
                }
            }
            //end of checking

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Refundable Amount</td>';
            if($this->data['refundStatus'] == 1) {
                $resultReturn .= '<td>'.$refund->fldTotalRefundable.'</td>';
            } else {
                $resultReturn .= '<td>'.$refund->fldPaidAmount.'</td>';
            }
            $resultReturn .= '</tr>';
            
            $resultReturn .= '</table>';
            $resultReturn .= '<div class="m-b-10" id="confirm_description">';
            $resultReturn .= '</div>';

            $resultReturn .= '<input type="text" id="ticketID" hidden>';
            $resultReturn .= '<input type="text" id="statusID" hidden>';
        }

        $this->data['resultReturn'] = $resultReturn;

        $this->output();
    }

    public function confirm_payment_refund() {
        $ticketID = $this->input->post('ticketID');

        if(isset($this->data['perm']['building_management']['view'])) {
            $allBuilding = 1;
        } else {
            $allBuilding = 0;
        }

        $this->data['refund'] = $this->facility_management_model->get_refunds_details($allBuilding, $this->user['BuildingID'], $ticketID);
        $invoice = $this->facility_management_model->get_unit_invoice($ticketID);
        $resultReturn = '';
        if($this->data['refund']['status'] == 1) {
            $refund = $this->data['refund']['refund'];

            $resultReturn .= '<label class="form-label display-block semi-bold">Please select one of the invoice to proceed.</label>';
            $resultReturn .= '<table id="invoice-table" class="table log-table">';
            $resultReturn .= '<thead>';
            $resultReturn .= '<tr>';
            $resultReturn .= '<th style="width:10%"></th>';
            $resultReturn .= '<th>Invoice Number</th>';
            $resultReturn .= '<th>Amount Due</th>';
            $resultReturn .= '</tr>';
            $resultReturn .= '</thead>';
            $resultReturn .= '<tbody>';
            if($invoice['status'] == 1) {
                foreach($invoice['invoice'] as $inv) {
                    $resultReturn .= '<tr>';
                    $resultReturn .= '<td class="text-center"><input type="radio" name="invoice" value="'.$inv->fldStatementNo.'"></td>';
                    $resultReturn .= '<td>'.$inv->fldStatementNo.'</td>';
                    $resultReturn .= '<td>'.$inv->fldTotalDue.'</td>';
                    $resultReturn .= '</tr>';
                }
            }
            $resultReturn .= '</tbody>';
            $resultReturn .= '</table>';

            $resultReturn .= '<table class="refund-table m-t-10">';

            $resultReturn .= '<tr>';
            $resultReturn .= '<th style="width:30%"></th>';
            $resultReturn .= '<th style="width:50%"></th>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Ticket ID</td>';
            $resultReturn .= '<td>'.$ticketID.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Facility Name</td>';
            $resultReturn .= '<td>'.$refund->fldFacilityName.'</td>';
            $resultReturn .= '</tr>';
            $bookingDate = new DateTime($refund->fldBookingDate);
            $bookingDateTime = $bookingDate->format('Y-m-d');

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Booking Date</td>';
            $resultReturn .= '<td>'.$bookingDateTime.' '.$refund->fldTimeSlot.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Unit No</td>';
            $resultReturn .= '<td>'.$refund->fldUnitNo.'</td>';
            $resultReturn .= '</tr>';

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Name</td>';
            $resultReturn .= '<td>'.$refund->fldFirstName.' '.$refund->fldLastName.'</td>';
            $resultReturn .= '</tr>';

            if($refund->fldApproved == 3) {
                $status = '<span class="label label-default">Cancelled</span>';
            } else if ($refund->fldApproved == 1) {
                $status = '<span class="label label-success">Approved</span>';
            } else if ($refund->fldApproved == 2) {
                $status = '<span class="label label-danger">Rejected</span>';
            } else {
                $status = '<span class="label label-warning">Pending</span>';
            }

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Booking Status</td>';
            $resultReturn .= '<td>'.$status.'</td>';
            $resultReturn .= '</tr>';

            //checking able to refund or not
            $tz = 'Asia/Singapore';
            $timestamp = time();
            $dt = new DateTime("now", new DateTimeZone($tz));
            $dt->setTimestamp($timestamp);
            $datetimenow = $dt->format('Y-m-d H:i');
            $bookingTime = explode("-", $refund->fldTimeSlot)[0];
            $fullBookingDateTime = $bookingDateTime . " " . $bookingTime;
            $bookingDateTimeFormat = date_create_from_format('Y-m-d H:i', $fullBookingDateTime);
            $stringBookingDateTime = date_format($bookingDateTimeFormat,"Y-m-d H:i");

            $this->data['refundStatus'] = 0;
            if(strtotime($stringBookingDateTime) <= strtotime($datetimenow)) {
                $this->data['refundStatus'] = 1;
            } else if(strtotime($stringBookingDateTime) > strtotime($datetimenow)) {
                if($refund->fldApproved == 2 || $refund->fldApproved == 3) {
                    $this->data['refundStatus'] = 1;
                }
            }
            //end of checking

            $resultReturn .= '<tr>';
            $resultReturn .= '<td>Refundable Amount</td>';
            if($this->data['refundStatus'] == 1) {
                $resultReturn .= '<td>'.$refund->fldTotalRefundable.'</td>';
            } else {
                $resultReturn .= '<td>'.$refund->fldPaidAmount.'</td>';
            }
            $resultReturn .= '</tr>';
            
            $resultReturn .= '</table>';
            $resultReturn .= '<div class="m-b-10" id="confirm_description">';
            $resultReturn .= '</div>';

            $resultReturn .= '<input type="text" id="ticketID" hidden>';
            $resultReturn .= '<input type="text" id="statusID" hidden>';
        }

        $this->data['resultReturn'] = $resultReturn;

        $this->output();
    }

    public function confirm_refund_action() {
        $ticketID = $this->input->post('ticketID');
        $statusID = $this->input->post('statusID');
        $insertJournal = 1;

        $data = $this->facility_management_model->confirm_refund_action($this->user['Id'], $this->user['Role'], $this->user['BuildingID'], $ticketID, $statusID);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], "facility_refunds", 1, $data, "tblFacilityBooking", $data['new']->fldBuildingID, "Refund");

            $userData = $this->facility_management_model->get_booking_user_id($ticketID, $ticketID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], $userData['title'], "Your deposit has been refunded", "/facility_management/my_facility_booking", "book");
            }
        }
        $this->set_data($data);
        $this->output();
    }

    public function confirm_refund_payment_action() {
        $ticketID = $this->input->post('ticketID');
        $statusID = $this->input->post('statusID');
        $invoiceNo = $this->input->post('invoiceNo');

        $data = $this->facility_management_model->confirm_refund_action($this->user['Id'], $this->user['Role'], $this->user['BuildingID'], $ticketID, $statusID);
        if($data['status'] == 1 && isset($data['new']))
        {
            $this->facility_management_model->refund_payment($this->user['Id'], $this->user['Role'], $ticketID, $invoiceNo);

            $this->data_model->log_activity($this->user['Id'], "facility_refunds", 1, $data, "tblFacilityBooking", $data['new']->fldBuildingID, "Refund");

            $userData = $this->facility_management_model->get_booking_user_id($ticketID, $ticketID);
            if($userData['status'] == 1) {
                $this->notification_model->insert_notification($userData['user'], $this->user['BuildingID'], $userData['title'], "Your deposit has been transferred to your account", "/facility_management/my_facility_booking", "book");
            }
        }
        $this->set_data($data);
        $this->output();
    }
}
/* End of file facility_management.php */
/* Location: ./application/controllers/facility_management.php */