<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Notification extends LC_Controller {

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
        $this->load->model('notification_model');
        $this->set_title("Notification");
    }

    public function index() {
        $this->data['notification'] = $this->notification_model->get_user_notification($this->user['Id'], 1, 20);
        
        $this->output('notification/index');
    }

    public function reload_notification() {
        $pageno = $this->input->post('pageno');
        $filterRow = $this->input->post('filterRow');
        $filterRow = $filterRow ? $filterRow : 10;
        
        $this->data['notification'] = $this->notification_model->get_user_notification($this->user['Id'], $pageno, $filterRow);
        $notification = $this->data['notification'];

        $newListing = '';
        if (isset($notification['results'])) {
            foreach ($notification['results'] as $row) {
                $newListing .= '<div class="notification-row" onclick="browseTo(\''.base_url().ltrim($row->fldUrl, "/").'\')">';
                $newListing .= '<a href="'.base_url().ltrim($row->fldUrl, "/").'">';
                $newListing .= '<i class="fa fa-'.$row->fldIcon.' font-35 m-r-10"></i>';
                $newListing .= '<strong class="vertical-super">'.$row->fldTitle.'</strong>';
                if($row->fldDescription != "") {
                    $description = str_replace("<p>","",$row->fldDescription);
                    $description = str_replace("</p>","",$description);
                    $newListing .= '<div class="display-inline-block vertical-super"> - ' . $description . '</div>';
                }
                $newListing .= '</a>';
                $newListing .= '</div>';
            }
            if (isset($notification['next']) && $notification['next'] == 1) {
                $newListing .= '<div id="loadMore" class="notification-row text-center" onclick="loadMore()">';
                $newListing .= '<a>Load More</a>';
                $newListing .= '</div>';
            }
        }
        $this->data['newListing'] = $newListing;

        $this->output();
    }

}
/* End of file notification.php */
/* Location: ./application/controllers/notification.php */