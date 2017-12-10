<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_maintenance extends LC_Controller {

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
        
        $this->load->model('service_maintenance_model');
        $this->load->library('billplz');

        $this->data['perm'] = $this->set_permission(array("building_management"));
        $this->data['SF_SU'] = SF_SU;
        $getConstantName = $this->service_maintenance_model->get_constant_name($this->user['BuildingID']);
        if($getConstantName['status'] == 1) {
            $this->data['SF_SU'] = $getConstantName['name'];
        }
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

        $this->data['unit'] = $this->service_maintenance_model->get_maintenance_listing($this->user['BuildingID'], 1, 20);

        $this->output('service_maintenance/maintenance_management');
    }

    public function reload_maintenance_listing() {
        $pageno = $this->input->post('pageno');
        $filterSearch = $this->input->post('filterSearch');
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');
        $filterStatus = $this->input->post('filterStatus');
        $filterRow = $this->input->post('filterRow');

        $filterRow = $filterRow ? $filterRow : 20;

        $this->data['unit'] = $this->service_maintenance_model->get_maintenance_listing($this->user['BuildingID'], $pageno, $filterRow, $filterSearch, $formStartDate, $formEndDate, $filterStatus);
        $unit = $this->data['unit'];
        $i = 0;
        $resultReturn = '';
        $resultHeader = '';
        $resultHeader .= '<tr>';
        $resultHeader .= '<th>Unit</th>';
        if (isset($unit['itemType'])) {
            foreach($unit['itemType'] as $t)
            {
                $resultHeader .= '<th>'.$t->fldTitle.'</th>';
            }
        }
        $resultHeader .= '<th>Total</th>';
        $resultHeader .= '<th>Balance</th>';
        $resultHeader .= '<th>Latest Balance</th>';
        $resultHeader .= '<th class="text-center">Status</th>';
        $resultHeader .= '<th class="text-center">Action</th>';
        $resultHeader .= '</tr>';
        if (isset($unit['results'])) {
            foreach($unit['results'] as $row)
            {
                // $totalAmount = 0;
                $resultReturn .= '<tr>';
                $resultReturn .= '<td><a href="'.base_url().'service_maintenance/view_transaction/'.$row->fldUnitID.'" class="booking-href">'.$row->fldUnitNo.'</a></td>';
                if (isset($unit['itemType'])) {
                    foreach($unit['itemType'] as $type)
                    {
                        if (isset($unit['item'][$row->fldUnitID][$type->fldID])) {
                            $resultReturn .= '<td>'.number_format((float)$unit['item'][$row->fldUnitID][$type->fldID], 2, '.', '').'</td>';
                            // $totalAmount += $unit['item'][$row->fldUnitID][$type->fldTitle];
                        }
                        else
                        {
                            $resultReturn .= '<td></td>';
                        }
                    }
                }
                if (isset($unit['statement'][$row->fldUnitID])) {
                    $resultReturn .= '<td>'.number_format((float)$unit['statement'][$row->fldUnitID]->fldTotalAmount, 2, '.', '').'</td>';
                    $currentTotalDue = (float)$unit['statement'][$row->fldUnitID]->fldTotalDue;
                    if($currentTotalDue < 0) {
                        $displayTotalDue = '+(' . number_format(-($currentTotalDue), 2, '.', '') . ')';
                    } else {
                        $displayTotalDue = number_format($currentTotalDue, 2, '.', '');
                    }
                    $resultReturn .= '<td>'.$displayTotalDue.'</td>';
                    $latestTotalDue = (float)$unit['latestStatement'][$row->fldUnitID]->latestTotalDue;
                    if($latestTotalDue < 0) {
                        $displayLatestDue = '+(' . number_format(-($latestTotalDue), 2, '.', '') . ')';
                    } else {
                        $displayLatestDue = number_format($latestTotalDue, 2, '.', '');
                    }
                    $resultReturn .= '<td>'.$displayLatestDue.'</td>';
                    if($currentTotalDue > 0){
                        $resultReturn .= '<td class="text-center"><span class="label label-warning">Pending</span></td>';
                    } else {
                        $resultReturn .= '<td class="text-center"><span class="label label-success">Paid</span></td>';
                    }
                }
                else
                {
                    $resultReturn .= '<td></td>';
                    $resultReturn .= '<td></td>';
                    $resultReturn .= '<td></td>';
                    $resultReturn .= '<td></td>';
                }
                $resultReturn .= '<td class="text-center">';
                if(isset($this->data['perm']['fee_management']['view'])) {
                    $resultReturn .= '<a href="'.base_url().'service_maintenance/unit_invoice/'.$row->fldUnitID.'" class="btn btn-success btn-xs btn-mini"> View Invoices </a>&nbsp;&nbsp;';
                    $resultReturn .= '<a href="'.base_url().'service_maintenance/unit_receipt/'.$row->fldUnitID.'" class="btn btn-success btn-xs btn-mini"> View Receipts </a>';
                }
                $resultReturn .= '</td>';
                $resultReturn .= '</tr>';
                $i++;
            }
            $this->data['resultNumber'] = $i + $unit['startId'];
        }
        else
        {
            $resultReturn .= '<tr><td colspan="8" class="text-center">No data found in selected date range</td></tr>';
            $this->data['resultNumber'] = 0;
        }
        $this->data['resultReturn'] = $resultReturn;
        $this->data['resultHeader'] = $resultHeader;

        $this->output();
    }

    public function permanent_fee_setup() {
        $this->data['unit'] = $this->service_maintenance_model->get_fixed_fee($this->user['BuildingID']);

        $this->output('service_maintenance/permanent_fee_setup');
    }

    public function field_setup() {
        $this->data['field'] = $this->service_maintenance_model->get_statement_type($this->user['BuildingID']);

        $this->output('service_maintenance/field_setup');
    }

    public function payment() {
        $thisUrl = $this->uri->segment(3);
        if($thisUrl == null || $thisUrl == "") {
            $this->output('404');
        }
        $statementNos = explode('_', $thisUrl);

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $amountInRM = 0.0;
        $passValidation = true;
        foreach($statementNos as $statementNo) {
            $validate = $this->service_maintenance_model->get_payment_validation($this->user['Id'], $manage, $this->user['BuildingID'], $statementNo);
            if($validate['status'] != 1) {
                $passValidation = false;
            } else {
                $statementDetails = $this->service_maintenance_model->get_statement_details_payment($statementNo);
                if($statementDetails['status'] == 1) {
                    $amountInRM += floatval($statementDetails['statement']->fldTotalDue);
                }
            }
        }
        $amount = ($amountInRM * 100) + 20; // in cent + transaction fee RM0.20

        if($passValidation == true && $amountInRM <= MAX_PAY_AMOUNT && $amountInRM >= 0) {
            $statementString = implode(', ', $statementNos);
            // $this->data['statementNo'] = $statementNo;

            $userEmail = $this->service_maintenance_model->get_user_email($this->user['Id']);
            $billplzSettings = $this->service_maintenance_model->get_billplz_settings($this->user['BuildingID']);

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
                    'amount' => $amount,
                    'description' => 'Livincube Payment for Invoice No: '. $statementString,
                    'callback_url' => base_url()."service_maintenance/get_payment_callback",
                    'redirect_url' => base_url()."service_maintenance/get_payment_object",
                    'reference_1_label' => 'invoice',
                    'reference_1' => $statementString,
                    'reference_2_label' => 'building',
                    'reference_2' => $this->user['BuildingID']
                ));
                $results = $bplz->create_bill();
                $resultObj = json_decode($results);
                if (isset($resultObj->id)) {
                    $billing = $this->service_maintenance_model->create_billing($this->user['Id'], $statementString, $resultObj);
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
        } else if($passValidation == true){
            $this->data['status'] = 2;
            $this->data['msg'] = "Amount must be between 0 to " . MAX_PAY_AMOUNT;
        }
        $this->output('service_maintenance/payment');
    }

    public function get_payment_callback() {
        $billObj = $_POST;
        $billObj['id'] = $billObj['id'] ? $billObj['id'] : '';
        $billID = $billObj['id'];
        // $this->load->library('user_agent');
        // error_log("Callback Logged: ".json_encode($this->agent->referrer()));
        // error_log("SERVER: ".json_encode($_SERVER));

        $billDetails = $this->service_maintenance_model->get_bill_details($billObj['id']);
        if($billDetails['status'] == 1) {
            $userBuildingID = $billDetails['details']->fldBuildingID;
            $userUserID = $billDetails['details']->fldUserID;

            $billplzSettings = $this->service_maintenance_model->get_billplz_settings($userBuildingID);
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
                        $statementNo = $results->reference_1;
                        $updateBill = $this->service_maintenance_model->update_bill($results);
                        if($updateBill['status'] == 1) {
                            $amountInRM = (floatval($results->paid_amount) - 20.0) / 100.0;
                            $payment = $this->service_maintenance_model->make_payment($userUserID, $statementNo, $amountInRM, $billID);
                            if($payment['status'] == 1 && isset($payment['new'])) {
                                $this->service_maintenance_model->update_bill_receipt($results->id, $payment['receiptNo']);
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

        $billDetails = $this->service_maintenance_model->get_bill_details($billObj['id']);
        if($billDetails['status'] == 1) {
            $userBuildingID = $billDetails['details']->fldBuildingID;
            $userUserID = $billDetails['details']->fldUserID;

            $billplzSettings = $this->service_maintenance_model->get_billplz_settings($userBuildingID);
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
                        $statementNo = $this->data['results']->reference_1;
                        $updateBill = $this->service_maintenance_model->update_bill($this->data['results']);
                        if($updateBill['status'] == 1) {
                            $amountInRM = (floatval($this->data['results']->paid_amount) - 20.0) / 100;
                            $this->data['payment'] = $this->service_maintenance_model->make_payment($userUserID, $statementNo, $amountInRM, $billID);
                            if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new'])) {
                                $this->data['paymentStatus'] = 1;
                                $this->data['paymentMsg'] = "Payment Success";
                                $this->service_maintenance_model->update_bill_receipt($this->data['results']->id, $this->data['payment']['receiptNo']);
                                $this->data_model->log_activity($userUserID, $this->__controller, 1, $this->data['payment'], "tblReceipts", $userBuildingID, "Online Payment");
                            } else {
                                $this->data['paymentMsg'] = "Update Payment Failed.";
                            }
                        } else {
                            $this->data['paymentMsg'] = "Payment Failed. Unable to update bill.";
                        }
                    } else if($billDetails['status'] == 1 && $billDetails['details']->fldPaid == 1) {
                        $this->data['payment'] = $this->service_maintenance_model->get_receipt_id($this->data['results']->id);
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
            redirect("/service_maintenance/view_receipt/".$this->data['payment']['receiptID']);
        } else {
            $this->output('service_maintenance/payment_completion');
        }
    }

    public function payment_management() {
        $thisUrl = $this->uri->segment(3);
        if($thisUrl == null || $thisUrl == "") {
            $this->output('404');
        }
        $statementNos = explode('_', $thisUrl);
        // $split = explode('-', $thisUrl);
        // $statementNo = $split[0];
        // $amount = $split[1];

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $passValidation = true;
        foreach($statementNos as $statementNo) {
            $validate = $this->service_maintenance_model->get_payment_validation($this->user['Id'], $manage, $this->user['BuildingID'], $statementNo);
            if($validate['status'] != 1) {
                $passValidation = false;
            }
        }

        if($passValidation == true && $manage == 1) {
            $this->data['statementNo'] = implode(', ', $statementNos);
            $amount = 0.0;
            foreach($statementNos as $statementNo) {
                $this->data['statement'] = $this->service_maintenance_model->get_statement_details_payment($statementNo);
                if($this->data['statement']['status'] == 1) {
                    $amount += floatval($this->data['statement']['statement']->fldTotalDue);
                }
            }
            $this->data['amount'] = $amount;
            if($this->data['statement']['status'] == 1) {
                $this->output('service_maintenance/payment_management');
            } else {
                $this->output('404');
            }
        } else {
            $this->output('404');
        }
    }

    // public function make_payment() {
    //     $statementNo = $this->input->post('statementNo');
    //     $amount = $this->input->post('amount');

    //     if(isset($this->data['perm']['fee_management']['edit'])) {
    //         $manage = 1;
    //     } else {
    //         $manage = 0;
    //     }

    //     $validate = $this->service_maintenance_model->get_payment_validation($this->user['Id'], $manage, $this->user['BuildingID'], $statementNo);
    //     if($validate['status'] == 1 && $amount <= MAX_PAY_AMOUNT && $amount >= 0) {
    //         $this->data['payment'] = $this->service_maintenance_model->make_payment($this->user['Id'], $statementNo, $amount);
    //         if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new']))
    //         {
    //             $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data['payment'], "tblReceipts", $this->user['BuildingID'], "Online Payment");
    //         }
    //     }

    //     $this->output();
    // }

    public function cash_payment() {
        $statementWhole = $this->input->post('statementNo');
        $amount = $this->input->post('amount');

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }
        $statementNos = explode(', ', $statementWhole);

        $passValidation = true;
        foreach($statementNos as $statementNo) {
            $validate = $this->service_maintenance_model->get_payment_validation($this->user['Id'], $manage, $this->user['BuildingID'], $statementNo);
            if($validate['status'] != 1) {
                $passValidation = false;
            }
        }

        if($passValidation == true && $manage == 1 && $amount <= MAX_PAY_AMOUNT && $amount >= 0) {
            $this->data['payment'] = $this->service_maintenance_model->cash_payment($this->user['Id'], $statementNos, $amount);
            if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new']))
            {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data['payment'], "tblReceipts", $this->user['BuildingID'], "Cash Payment");
            }
        } else if($passValidation == true){
            $this->data['payment']['status'] = 2;
            $this->data['payment']['msg'] = "Amount must be between 0 to " . MAX_PAY_AMOUNT;
        }

        $this->output();
    }

    public function receipt() {
        $this->data['receipt'] = $this->service_maintenance_model->get_unit_receipt($this->user['Id'], $this->user['BuildingID'], 1, 20);

        $this->output('service_maintenance/receipt');
    }

    public function reload_receipt() {
        $unitID = $this->input->post('unitID');
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;

        if(isset($this->data['perm']['fee_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['receipt'] = $this->service_maintenance_model->get_selected_unit_receipt($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], $unitID, $pageno, 20);
        $receipt = $this->data['receipt'];
        $i = 0;
        $resultReturn = '';
        $this->data['resultNumber'] = 0;

        if (isset($receipt['receipt'])) {
            foreach($receipt['receipt'] as $row)
            {
                $resultReturn .= '<tr id="row'.$row->fldID.'">';
                $resultReturn .= '<td class="ticketColumn"><a class="booking-href" href="'.base_url().'service_maintenance/view_receipt/'.$row->fldID.'">'.$row->fldReceiptNo.'</a></td>';
                $resultReturn .= '<td class="text-center">'.explode(" ",$row->fldDatetime)[0].'</td>';
                $resultReturn .= '<td class="text-center">'.$row->fldAmount.'</td>';
                $resultReturn .= '<td class="text-center">';
                if($row->fldTransactionID == 0) {
                    $resultReturn .= '-';
                } else {
                    $resultReturn .= $row->fldTransactionID;
                }
                $resultReturn .= '</td>';
                if($row->fldStatus != 1)
                {
                    $status = '<span class="label label-danger">Failed</span>';
                }
                else
                {
                    $status = '<span class="label label-success">Success</span>';
                }
                $resultReturn .= '<td class="text-center">'.$status.'</td>';
                $resultReturn .= '<td class="text-center"><button onclick="downloadReceipt('.$row->fldID.')" class="btn btn-success btn-xs btn-mini"><i class="fa fa-download"></i> Download</button></td>';
                $resultReturn .= '</tr>';
                $i++;
            }
            $this->data['resultNumber'] = $i + $receipt['startId'];
        }

        $this->data['resultReturn'] = $resultReturn;
        

        $this->output();
    }

    public function unit_receipt() {
        $unitID = $this->uri->segment(3);
        if($unitID == null || $unitID == "") {
            $this->output('404');
        }

        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;

        if(isset($this->data['perm']['fee_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['receipt'] = $this->service_maintenance_model->get_selected_unit_receipt($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], $unitID, $pageno, 20);

        $this->output('service_maintenance/unit_receipt');
    }

    public function invoice() {
        $this->data['statement'] = $this->service_maintenance_model->get_unit_statement($this->user['Id'], $this->user['BuildingID'], 1, 20);

        $this->output('service_maintenance/invoice');
    }

    public function reload_invoice() {
        $unitID = $this->input->post('unitID');
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $postLogo = $this->input->post('postLogo');
        $postLogo = $postLogo ? $postLogo : 0;

        if(isset($this->data['perm']['fee_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['statement'] = $this->service_maintenance_model->get_selected_unit_statement($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], $unitID, $pageno, 20);
        $statement = $this->data['statement'];
        $i = 0;
        $resultReturn = '';
        $statementTable = '';
        $this->data['resultNumber'] = 0;

        if(isset($statement['latestStatement'])) {
            $statementTable .= '<tr>';
            $statementTable .= '<td>';
            $statementTable .= 'Invoice No.:';
            $statementTable .= '</td>';
            $statementTable .= '<td id="invoice-selected">';
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
            $statementTable .= '<tr>';
            $statementTable .= '<td>';
            $statementTable .= 'Unit No:';
            $statementTable .= '</td>';
            $statementTable .= '<td>';
            $statementTable .= $statement['selectedUnit']->fldUnitNo;
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
            $statementTable .= '<tr>';
            $statementTable .= '<td>';
            $statementTable .= 'Name:';
            $statementTable .= '</td>';
            $statementTable .= '<td>';
            if($statement['selectedUnit']->fldFirstName != NULL) {
                $statementTable .= $statement['selectedUnit']->fldFirstName;
            }
            else {
                $statementTable .= '-';
            }
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
            $statementTable .= '<tr>';
            $statementTable .= '<td>';
            $statementTable .= 'Outstanding Balance:';
            $statementTable .= '</td>';
            $statementTable .= '<td>';
            if(isset($statement['latestStatement']->latestTotalDue)) {
                $statementTable .= $statement['latestStatement']->latestTotalDue;
            } else {
                $statementTable .= "0.00";
            }
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
            $statementTable .= '<tr>';
            $statementTable .= '<td>';
            $statementTable .= 'Amount to pay:';
            $statementTable .= '</td>';
            $statementTable .= '<td class="td-no-padding">';
            $statementTable .= '<input id="payAmount" name="payAmount" type="number" disabled>';
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
            $statementTable .= '<tr>';
            $statementTable .= '<td colspan="2" class="text-center">';
            if($postLogo == 1) {
                $statementTable .= '<div class="payment-by-logo"><img src="'. base_url() . BILLPLZ_IMAGE .'"></div>';
                $statementTable .= '<div class="m-b-10">Note: A transaction fee of RM0.20 will be charged for each payment</div>';
            }
            $statementTable .= '<button onclick="makePayment()" class="btn btn-primary btn-xs">Make Payment</button>';
            $statementTable .= '</td>';
            $statementTable .= '</tr>';
        }

        if (isset($statement['statement'])) {
            foreach($statement['statement'] as $row)
            {
                $currentTotalDue = (float)$row->fldTotalDue;
                if($currentTotalDue < 0) {
                    $displayTotalDue = '+(' . number_format(-($currentTotalDue), 2, '.', '') . ')';
                } else {
                    $displayTotalDue = number_format($currentTotalDue, 2, '.', '');
                }
                $resultReturn .= '<tr id="row'.$row->fldID.'">';
                if($currentTotalDue > 0) {
                    $resultReturn .= '<td class="small-cell v-align-middle text-center selectedcheckbox" style="width:5%">';
                    $resultReturn .= '<div class="checkbox check-success">';
                    $resultReturn .= '<input id="checkbox'.$row->fldID.'" type="checkbox" value="0">';
                    $resultReturn .= '<label class="vertical-super" for="checkbox'.$row->fldID.'"></label>';
                    $resultReturn .= '</div>';
                    $resultReturn .= '</td>';
                } else {
                    $resultReturn .= '<td></td>';
                }
                $resultReturn .= '<td class="ticketColumn"><a class="booking-href" href="'.base_url().'service_maintenance/view_invoice/'.$row->fldID.'">'.$row->fldStatementNo.'</a></td>';
                $resultReturn .= '<td class="text-center">'.explode(" ",$row->fldCreatedDate)[0].'</td>';
                $resultReturn .= '<td class="text-center">'.$row->fldTotalAmount.'</td>';
                $resultReturn .= '<td class="text-center totalDue">'.$displayTotalDue.'</td>';
                if($row->fldTotalDue > 0)
                {
                    $status = '<span class="label label-danger">Unpaid</span>';
                }
                else
                {
                    $status = '<span class="label label-success">Paid</span>';
                }
                $resultReturn .= '<td class="text-center">'.$status.'</td>';
                $resultReturn .= '<td class="text-center">';
                $resultReturn .= '<button onclick="downloadStatement('.$row->fldID.')" class="btn btn-success btn-xs btn-mini"><i class="fa fa-download"></i> Download</button>';
                if(isset($this->data['perm']['fee_management']['edit_invoice'])) {
                    $resultReturn .= '&nbsp;&nbsp;<button onclick="createCredit('.$row->fldID.')" class="btn btn-primary btn-xs btn-mini"><i class="fa fa-plus"></i> Waive</button>';
                }
                $resultReturn .= '</td>';
                $resultReturn .= '</tr>';
                $i++;
            }
            $this->data['resultNumber'] = $i + $statement['startId'];
        }
        $this->data['statementTable'] = $statementTable;
        $this->data['resultReturn'] = $resultReturn;

        $this->output();
    }

    public function unit_invoice() {
        $unitID = $this->uri->segment(3);
        if($unitID == null || $unitID == "") {
            $this->output('404');
        }

        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;

        if(isset($this->data['perm']['fee_management']['view'])) {
            $viewAll = 1;
        } else {
            $viewAll = 0;
        }

        $this->data['statement'] = $this->service_maintenance_model->get_selected_unit_statement($this->user['Id'], $this->user['Role'], $viewAll, $this->user['AdminRole'], $this->user['BuildingID'], $unitID, $pageno, 20);

        $this->output('service_maintenance/unit_invoice');
    }

    public function monthly_fee() {
        $this->data['unit'] = $this->service_maintenance_model->get_monthly_fee($this->user['BuildingID']);

        $this->output('service_maintenance/monthly_fee');
    }

    public function filter_monthly_fee() {
        $filterDate = $this->input->post('filterDate');
        $this->data['unit'] = $this->service_maintenance_model->get_monthly_fee($this->user['BuildingID'], $filterDate);

        $unit = $this->data['unit'];
        $resultReturn = '';
        $fieldEditable = 0;
        if (isset($unit['results'])) {
            $counter = 0;
            foreach ($unit['results'] as $row) {
                $resultReturn .= '<tr class="odd" id="tableRow'.$row->fldUnitID.'">';
                $resultReturn .= '<td class="text-center">' .$row->fldUnitNo . '</td>';
                if (isset($unit['statement'])) {
                    foreach ($unit['statement'] as $s) {
                        $found = 0;
                        if(isset($unit['fee'][$row->fldUnitID]))
                        {
                            foreach($unit['fee'][$row->fldUnitID] as $fee)
                            {
                                if($fee->statementID == $s->fldID)
                                {
                                    if($s->fldMeterReading == 1) {
                                        if(isset($fee->meterReading)) {
                                            if($fee->meterCharged == 0) {
                                                $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-'.$fee->fldID.'-'.$s->fldID.'-'.$fee->meterID.'" name="'.$row->fldUnitID.'-'.$fee->fldID.'-'.$s->fldID.'-'.$fee->meterID.'" type="number" value="'.$fee->meterReading.'" class="text-center meterReading" step="0.01"></td>';
                                            } else {
                                                $resultReturn .= '<td class="no-padding text-center">'.$fee->meterReading.'</td>';
                                            }
                                        } else {
                                            $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" name="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" type="number" class="text-center meterReading" step="0.01"></td>';
                                            $fieldEditable = 1;
                                        }
                                    }
                                    if($fee->fldCharged == 0)
                                    {
                                        $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-'.$fee->fldID.'-'.$s->fldID.'" name="'.$row->fldUnitID.'-'.$fee->fldID.'-'.$s->fldID.'" type="number" value="'.$fee->fldAmount.'" class="text-center" step="0.01"></td>';
                                        $fieldEditable = 1;
                                    }
                                    else
                                    {
                                        $resultReturn .= '<td class="no-padding text-center">'.$fee->fldAmount.'</td>';
                                    }
                                    $counter++;
                                    $found = 1;
                                }
                            }
                            if($found == 0)
                            {
                                if($s->fldMeterReading == 1) {
                                    $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" name="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" type="number" class="text-center meterReading" step="0.01"></td>';
                                }
                                $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-0-'.$s->fldID.'" name="'.$row->fldUnitID.'-0-'.$s->fldID.'" type="number" class="text-center" step="0.01"></td>';
                                $counter++;
                                $fieldEditable = 1;
                            }
                        }
                        else
                        {
                            if($s->fldMeterReading == 1) {
                                $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" name="'.$row->fldUnitID.'-0-'.$s->fldID.'-0" type="number" class="text-center meterReading" step="0.01"></td>';
                            }
                            $resultReturn .= '<td class="no-padding"><input id="'.$row->fldUnitID.'-0-'.$s->fldID.'" name="'.$row->fldUnitID.'-0-'.$s->fldID.'" type="number" class="text-center" step="0.01"></td>';
                            $counter++;
                            $fieldEditable = 1;
                        }
                    }
                }
                $resultReturn .= '</tr>';
            }
        }
        $this->data['resultReturn'] = $resultReturn;
        $this->data['fieldEditable'] = $fieldEditable;

        $this->output();
    }

    public function view_invoice() {
        $statementID = $this->uri->segment(3);
        if($statementID == null || $statementID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $this->data['statement'] = $this->service_maintenance_model->get_statement_details($this->user['Id'], $this->user['Role'], $manage, $this->user['AdminRole'], $this->user['BuildingID'], $statementID);
        if(isset($this->data['perm']['building_management']['view'])) {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration_by_statement($statementID);
        }
        else {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);
        }

        if($this->data['statement']['status'] == 1) {
            $this->output('service_maintenance/view_invoice');
        }
        else {
            $this->output('404');
        }
    }

    public function preview_invoice() {
        $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);

        $this->output('service_maintenance/preview_invoice');
    }

    public function view_receipt() {
        $receiptID = $this->uri->segment(3);
        if($receiptID == null || $receiptID == "") {
            $this->output('404');
        }

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

    public function print_invoice() {
        $statementID = $this->uri->segment(3);
        if($statementID == null || $statementID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $this->data['statement'] = $this->service_maintenance_model->get_statement_details($this->user['Id'], $this->user['Role'], $manage, $this->user['AdminRole'], $this->user['BuildingID'], $statementID);
        if(isset($this->data['perm']['building_management']['view'])) {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration_by_statement($statementID);
        }
        else {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);
        }

        if($this->data['statement']['status'] == 1) {
            $this->load->view('service_maintenance/print_invoice', $this->data);
        }
        else {
            $this->output('404');
        }
    }

    public function print_receipt() {
        $receiptID = $this->uri->segment(3);
        if($receiptID == null || $receiptID == "") {
            $this->output('404');
        }

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
            $this->load->view('service_maintenance/print_receipt', $this->data);
        }
        else {
            $this->output('404');
        }
    }

    public function download_statement() {
        $statementID = $this->uri->segment(3);
        if($statementID == null || $statementID == "") {
            $this->output('404');
        }

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $this->data['statement'] = $this->service_maintenance_model->get_statement_details($this->user['Id'], $this->user['Role'], $manage, $this->user['AdminRole'], $this->user['BuildingID'], $statementID);
        if(isset($this->data['perm']['building_management']['view'])) {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration_by_statement($statementID);
        }
        else {
            $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);
        }

        if($this->data['statement']['status'] == 1) {
            $this->load->view('service_maintenance/download_statement', $this->data);
        }
        else {
            $this->output('404');
        }
    }

    public function download_receipt() {
        $receiptID = $this->uri->segment(3);
        if($receiptID == null || $receiptID == "") {
            $this->output('404');
        }

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
            $this->load->view('service_maintenance/download_receipt', $this->data);
        }
        else {
            $this->output('404');
        }
    }

    public function statement_setup() {
        $this->data['config'] = $this->service_maintenance_model->get_statement_configuration($this->user['BuildingID']);

        $this->output('service_maintenance/statement_setup');
    }

    public function edit_statement_configuration() {
        $modalname = $this->input->post('modalname');
        $modalemail = $this->input->post('modalemail');
        $modalcontactperson = $this->input->post('modalcontactperson');
        $modaladdress = $this->input->post('modaladdress');
        $modalphone = $this->input->post('modalphone');
        $modalfax = $this->input->post('modalfax');
        $modaldisclaimer = $this->input->post('modaldisclaimer');
        $modalremarks = $this->input->post('modalremarks');
        $modaldate = $this->input->post('modaldate');
        $modalduedate = $this->input->post('modalduedate');
        $modalinterest = $this->input->post('modalinterest');
        $modalbillplzapi = $this->input->post('modalbillplzapi');
        $modalbillplzcollectionid = $this->input->post('modalbillplzcollectionid');
        $modalbillplzxsignature = $this->input->post('modalbillplzxsignature');

        $data = $this->service_maintenance_model->edit_statement_configuration($this->user['Id'], $this->user['BuildingID'], $modalname, $modalemail, $modalcontactperson, $modaladdress, $modalphone, $modalfax, $modaldisclaimer, $modalremarks, $modaldate, $modalduedate, $modalinterest, $modalbillplzapi, $modalbillplzcollectionid, $modalbillplzxsignature);
        $this->set_data($data);

        if($data['status'] == 1 && isset($data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, $data['log'], $data, "tblStatementConfig", $this->user['BuildingID'], "");
        }

        $this->output();
    }

    public function update_field_setup() {
        $field = $this->input->post('field');

        $data = $this->service_maintenance_model->update_field_setup($this->user['BuildingID'], $field);
        $this->set_data($data);

        $this->output();
    }

    public function update_permanent_fee() {
        $field = $this->input->post('field');

        $data = $this->service_maintenance_model->update_permanent_fee($this->user['BuildingID'], $field);
        $this->set_data($data);

        $this->output();
    }

    public function update_monthly_fee() {
        $field = $this->input->post('field');
        $filterDate = $this->input->post('filterDate');

        $data = $this->service_maintenance_model->update_monthly_fee($this->user['BuildingID'], $field, $filterDate);
        $this->set_data($data);

        $this->output();
    }

    public function manual_generate_invoice() {
        //start of test
        // $jsonData = json_encode(array("username"=>TREEZSOFT_USERNAME, "password"=>TREEZSOFT_PASS, "rememberMe"=>true));
        // $auth = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJhcGlkZW1vQGoyYWUiLCJhdXRoIjoiUk9MRV9BUElfVVNFUiIsImV4cCI6MTUxMTA1OTk1OH0.2lKW9UXd5fkF-qoeJFyK2fpiU58qjRInVoJNa0I5sP6Kda8Tx29GNeC9LlimiviQ3znpu3JIifAPlGNCVXG-wA";

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, TREEZSOFT_GET_AUTH);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/json')
        // );
        // // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        // //     'Content-Type: application/json',
        // //     'Authorization: Bearer ' . $auth)
        // // );
        // $this->data['curl'] = curl_exec($ch);
        // $this->data['httpcode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);
        //end of test

        $data = $this->service_maintenance_model->generate_invoice($this->user['BuildingID']);
        $this->set_data($data);
        if($this->data['status'] == 1) {
            foreach($this->data['mailDetails'] as $mail) {
                $thisSubject = 'New Invoice - ' . $mail->fldStatementNo . ' for unit number: ' . $mail->fldUnitNo;
                $thisMsg = 'Hi ' . $mail->fldFirstName . '<br/><br/>A new invoice has been generated. Please view the invoice details by clicking on the following link: <br/>' . base_url() . 'service_maintenance/view_invoice/' . $mail->fldID;
                $this->send_email($mail->fldEmail, $mail->SenderEmail, $mail->fldBuildingName, $thisSubject, $thisMsg);
            }
        }

        $this->output();
    }

    public function transaction() {
        $this->data['transaction'] = $this->service_maintenance_model->get_unit_transaction($this->user['Id'], $this->user['BuildingID'], 1, 20);

        $this->output('service_maintenance/transaction');
    }

    public function reload_transaction() {
        $unitID = $this->input->post('unitID');
        $filterDateRange = $this->input->post('filterDateRange');
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        $this->data['transaction'] = $this->service_maintenance_model->get_unit_transaction($this->user['Id'], $this->user['BuildingID'], $pageno, $filterRow, $unitID, $filterDateRange);
        $transaction = $this->data['transaction'];
        $i = 0;
        $resultReturn = '';
        $resultFooter = '';
        $this->data['resultNumber'] = 0;

        if (isset($transaction['transaction'])) {
            $append = '';
            $balance = $transaction['previousBalance'];
            $displayBalance = 0;
            foreach($transaction['transaction'] as $row)
            {
                if($row->fldDebit != 0 || $row->fldCredit != 0) {
                    $append .= '<tr>';
                    $append .= '<td>'.explode(" ",$row->fldDatetime)[0].'</td>';
                    $append .= '<td>'.$row->fldStatementNo.'</td>';
                    $append .= '<td>'.$row->fldDescription.'</td>';
                    if($row->fldDebit != 0) {
                        $append .= '<td class="text-right">'.$row->fldDebit.'</td>';
                        $balance = $balance - $row->fldDebit;
                    } else {
                        $append .= '<td class="text-right"></td>';
                    }
                    if($row->fldCredit != 0) {
                        $append .= '<td class="text-right">'.$row->fldCredit.'</td>';
                        $balance = $balance + $row->fldCredit;
                    } else {
                        $append .= '<td class="text-right"></td>';
                    }
                    if($balance < 0) {
                        $displayBalance = '+(' . number_format(-($balance), 2, '.', '') . ')';
                    } else {
                        $displayBalance = number_format($balance, 2, '.', '');
                    }
                    $append .= '<td class="text-right">'.$displayBalance.'</td>';
                    $append .= '</tr>';
                }
                $i++;
            }
            $resultReturn .= $append;
            $this->data['resultNumber'] = $i + $transaction['startId'];

            $resultFooter .= '<tr>';
            if($transaction['balance'] < 0) {
                $endingBalance = '+(' . number_format(-($transaction['balance']), 2, '.', '') . ')';
            } else {
                $endingBalance = number_format($transaction['balance'], 2, '.', '');
            }
            $resultFooter .= '<td colspan="5">Total Balance Outstanding</td>';
            $resultFooter .= '<td class="text-right">'.$endingBalance.'</td>';
            $resultFooter .= '<tr>';
        }
        else {
             $resultReturn .= '<tr><td colspan="6" class="text-center">No statement found</td></tr>';
        }

        $this->data['resultReturn'] = $resultReturn;
        $this->data['resultFooter'] = $resultFooter;
        $this->output();
    }

    public function view_transaction() {
        $unitID = $this->uri->segment(3);
        if($unitID == null || $unitID == "") {
            $this->output('404');
        }

        $this->data['transaction'] = $this->service_maintenance_model->view_unit_transaction($unitID, $this->user['BuildingID'], 1, 20);
        $this->data['unit'] = $unitID;

        $this->output('service_maintenance/view_transaction');
    }

    public function reload_view_transaction() {
        $unitID = $this->input->post('unitID');
        $filterDateRange = $this->input->post('filterDateRange');
        $pageno = $this->input->post('pageno');
        $pageno = $pageno ? $pageno : 1;
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;

        $this->data['transaction'] = $this->service_maintenance_model->view_unit_transaction($unitID, $this->user['BuildingID'], $pageno, $filterRow, $filterDateRange);
        $transaction = $this->data['transaction'];
        $i = 0;
        $resultReturn = '';
        $resultFooter = '';
        $this->data['resultNumber'] = 0;

        if (isset($transaction['transaction'])) {
            $append = '';
            $balance = $transaction['previousBalance'];
            $displayBalance = 0;
            foreach($transaction['transaction'] as $row)
            {
                if($row->fldDebit != 0 || $row->fldCredit != 0) {
                    $append .= '<tr>';
                    $append .= '<td>'.explode(" ",$row->fldDatetime)[0].'</td>';
                    $append .= '<td>'.$row->fldStatementNo.'</td>';
                    $append .= '<td>'.$row->fldDescription.'</td>';
                    if($row->fldDebit != 0) {
                        $append .= '<td class="text-right">'.$row->fldDebit.'</td>';
                        $balance = $balance - $row->fldDebit;
                    } else {
                        $append .= '<td class="text-right"></td>';
                    }
                    if($row->fldCredit != 0) {
                        $append .= '<td class="text-right">'.$row->fldCredit.'</td>';
                        $balance = $balance + $row->fldCredit;
                    } else {
                        $append .= '<td class="text-right"></td>';
                    }
                    if($balance < 0) {
                        $displayBalance = '+(' . number_format(-($balance), 2, '.', '') . ')';
                    } else {
                        $displayBalance = number_format($balance, 2, '.', '');
                    }
                    $append .= '<td class="text-right">'.$displayBalance.'</td>';
                    $append .= '</tr>';
                }
                $i++;
            }
            $resultReturn .= $append;
            $this->data['resultNumber'] = $i + $transaction['startId'];

            $resultFooter .= '<tr>';
            if($transaction['balance'] < 0) {
                $endingBalance = '+(' . number_format(-($transaction['balance']), 2, '.', '') . ')';
            } else {
                $endingBalance = number_format($transaction['balance'], 2, '.', '');
            }
            $resultFooter .= '<td colspan="5">Total Balance Outstanding</td>';
            $resultFooter .= '<td class="text-right">'.$endingBalance.'</td>';
            $resultFooter .= '<tr>';
        }
        else {
            $resultReturn .= '<tr><td colspan="6" class="text-center">No statement found</td></tr>';
        }

        $this->data['resultReturn'] = $resultReturn;
        $this->data['resultFooter'] = $resultFooter;
        $this->output();
    }

    public function thumbnailupload() {
        $this->is_ajax = TRUE;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_FILES['file']['name'] == "" && !$_FILES['file']['name'] == null) {
                $dir = $this->data_model->getDir("IMAGE", 2);
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

    public function add_logo_image() {
        $buildingID = $this->user['BuildingID'];
        $oriName = $this->input->post('oriName');
        $newName = $this->input->post('newName');
        
        $data = $this->data_model->add_new_image($this->user['Id'], $oriName, $newName, $buildingID, 2);
        $this->set_data($data);
        
        $this->output();
    }

    public function cheque_payment() {
        $statementWhole = $this->input->post('statementNo');
        $amount = $this->input->post('amount');
        $chequeNo = $this->input->post('chequeNo');
        $chequeDate = $this->input->post('chequeDate');
        $chequeUsername = $this->input->post('chequeUsername');
        $chequeBankName = $this->input->post('chequeBankName');
        $chequeRemarks = $this->input->post('chequeRemarks');

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }
        $statementNos = explode(', ', $statementWhole);

        $passValidation = true;
        foreach($statementNos as $statementNo) {
            $validate = $this->service_maintenance_model->get_payment_validation($this->user['Id'], $manage, $this->user['BuildingID'], $statementNo);
            if($validate['status'] != 1) {
                $passValidation = false;
            }
        }

        if($passValidation == true && $manage == 1 && $amount <= MAX_PAY_AMOUNT && $amount >= 0) {
            $this->data['payment'] = $this->service_maintenance_model->cheque_payment($this->user['Id'], $statementNos, $amount, $chequeNo, $chequeDate, $chequeUsername, $chequeBankName, $chequeRemarks);
            if($this->data['payment']['status'] == 1 && isset($this->data['payment']['new']))
            {
                $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data['payment'], "tblReceipts", $this->user['BuildingID'], "Cash Payment");
            }
        }

        $this->output();
    }

    public function cheque() {
        $this->data['cheque'] = $this->service_maintenance_model->get_cheque_list($this->user['BuildingID'], 1, 20);

        $this->output('service_maintenance/cheque');
    }

    public function reload_cheque() {
        $pageno = $this->input->post('pageno');
        $unit = $this->input->post('unit');
        $formStartDate = $this->input->post('formStartDate');
        $formEndDate = $this->input->post('formEndDate');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 20;
        $filterApproved = $this->input->post('filterApproved');

        $this->data['cheque'] = $this->service_maintenance_model->get_cheque_list($this->user['BuildingID'], $pageno, $filterRow, $unit, $formStartDate, $formEndDate, $filterApproved);
        $cheque = $this->data['cheque'];
        $resultReturn = '';
        $this->data['resultNumber'] = 0;

        if (isset($cheque['results'])) {
            $append = '';
            $i = $cheque['startId'];
            foreach($cheque['results'] as $row)
            {
                $append .= '<tr>';
                $append .= '<td>'.($i+1).'</td>';
                $append .= '<td>'.$row->fldUnitNo.'</td>';
                $append .= '<td>'.$row->fldChequeNo.'</td>';
                $append .= '<td>'.explode(" ",$row->fldCreatedDate)[0].'</td>';
                $append .= '<td>'.$row->fldAmount.'</td>';
                $append .= '<td class="text-center">';
                if($row->fldStatus == 1) {
                    $append .= '<span class="label label-success">Active</span>';
                }
                else if($row->fldStatus == 2) {
                    $append .= '<span class="label label-danger">Rejected</span>';
                }
                $append .= '</td>';
                $append .= '<td class="text-center">';
                if(isset($this->data['perm']['fee_management']['edit'])) {
                    $createdName = $row->fldFirstName . " " . $row->fldLastName;
                    $append .= '<button onclick="setView(\''.$row->fldUnitNo.'\',\''.$row->fldChequeNo.'\',\''.explode(" ",$row->fldCreatedDate)[0].'\',\''.explode(" ",$row->fldChequeDate)[0].'\',\''.$row->fldUsername.'\',\''.$row->fldBankName.'\',\''.$row->fldRemark.'\',\''.$row->fldAmount.'\',\''.$createdName.'\')" class="btn btn-success btn-xs btn-mini"><i class="fa fa-eye"></i> View</button>&nbsp;';
                    if($row->fldStatus == 1 && $row->fldApproved == 0) {
                        $append .= '<button onclick="approve('.$row->fldID.',\''.$row->fldChequeNo.'\',\''.$row->fldAmount.'\')" class="btn btn-primary btn-xs btn-mini"><i class="fa fa-check"></i> Approve</button>&nbsp;';
                        $append .= '<button onclick="reject('.$row->fldID.',\''.$row->fldChequeNo.'\',\''.$row->fldAmount.'\')" class="btn btn-danger btn-xs btn-mini"><i class="fa fa-times"></i> Reject</button>';
                    }
                }
                $append .= '</td>';
                $append .= '</tr>';
                $i++;
            }
            $resultReturn .= $append;
            $this->data['resultNumber'] = $i;
        }
        else {
            $resultReturn .= '<tr><td colspan="7" class="text-center">No data found</td></tr>';
        }

        $this->data['resultReturn'] = $resultReturn;

        $this->output();
    }

    public function reject_cheque() {
        $rejectID = $this->input->post('rejectID');

        $data = $this->service_maintenance_model->reject_cheque($this->user['Id'], $this->user['BuildingID'], $rejectID);
        $this->set_data($data);
        if($this->data['status'] == 1 && isset($this->data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data, "tblCheque", $this->user['BuildingID'], "Reject Cheque");
        }

        $this->output();
    }

    public function approve_cheque() {
        $approveID = $this->input->post('approveID');

        $data = $this->service_maintenance_model->approve_cheque($this->user['Id'], $this->user['BuildingID'], $approveID);
        $this->set_data($data);
        if($this->data['status'] == 1 && isset($this->data['new']))
        {
            $this->data_model->log_activity($this->user['Id'], $this->__controller, 1, $this->data, "tblCheque", $this->user['BuildingID'], "Approve Cheque");
        }

        $this->output();
    }

    public function import_file_action() {
        $this->is_ajax = TRUE;
        // $field  = $this->input->post('field');

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
                    // $data['row'] = array();
                    $rowCounter = 0;
                    $storeAllValue = array();
                    foreach ($csv_array as $row) {
                        $key = array_keys($row);
                        $columnCounter = 0;
                        foreach($key as $columnName) {
                            $storeAllValue[$rowCounter][$columnCounter] = $row[$columnName];
                            $columnCounter++;
                        }
                        $rowCounter++;
                    }
                    $data['results'] = $storeAllValue;
                    $data['status'] = 1;
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

    public function get_statement_details() {
        $statementID = $this->input->post('statement');

        if(isset($this->data['perm']['fee_management']['edit'])) {
            $manage = 1;
        } else {
            $manage = 0;
        }

        $data = $this->service_maintenance_model->get_statement_details($this->user['Id'], $this->user['Role'], $manage, $this->user['AdminRole'], $this->user['BuildingID'], $statementID);
        $this->set_data($data);
        $this->output();
    }

    public function add_credit_note() {
        $waiveDesc = $this->input->post('waiveDesc');
        $waiveAmount = $this->input->post('waiveAmount');
        $statementNo = $this->input->post('statementNo');

        $data['credit'] = $this->service_maintenance_model->add_credit_note($this->user['Id'], $this->user['BuildingID'], $statementNo, $waiveDesc, $waiveAmount);
        $this->set_data($data);
        if($this->data['credit']['status'] == 1 && isset($this->data['credit']['new'])) {
            $this->data_model->log_activity($this->user['Id'], "fee_management", 1, $this->data['credit'], "tblCreditNote", $this->user['BuildingID'], "Waive Item");
        }

        $this->output();
    }
}
/* End of file profile.php */
/* Location: ./application/controllers/profile.php */