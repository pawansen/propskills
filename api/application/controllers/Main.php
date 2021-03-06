<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends MAIN_Controller {

    public function index() {
        echo "This is a sample page.";
    }

    public function showEmailTemplate() {
      
      sendMail(array(
          'emailTo' => 'gauravsahu.mobiwebtech@gmail.com',
          'emailSubject' => 'Thank you for registering at ' . SITE_NAME,
          'emailMessage' => emailTemplate($this->load->view('emailer/signup', array("Name" => 'GAURAV', 'Token' => '456487', 'DeviceTypeID' => '1'), TRUE))
          )
      );
      // $data = ['Name' => 'USER', 'GOOGLE_PLUS_URL'  => 'fake_url', 'Token' => 'Token'];
      // $this->load->view('emailer/includes/header');
      // $this->load->view('emailer/signup', $data);
      // $this->load->view('emailer/includes/footer');
    }

    public function logs() {
        $this->load->library('logviewer');
        $this->load->view('logs', $this->logviewer->get_logs());
    }

    public function upload() {
        $this->load->view('upload');
    }

    public function paypalResponse() {
        $this->load->model('Users_model');
        $Input = file_get_contents("php://input");
        $PayResponse = json_decode($Input, 1);

        $InsertData = array_filter(array(
            "PageGUID" => "Paypal",
            "Title" => "Test",
            "Content" => $Input
        ));
        $this->db->insert('set_pages', $InsertData);
    }

    public function paytmResponse() {
        $this->load->model('Users_model');

        /* Get User ID */
        $UserID = $this->db->query('SELECT `UserID` FROM `tbl_users_wallet` WHERE `WalletID` = ' . $_POST["ORDERID"] . ' LIMIT 1')->row()->UserID;
        $PaymentResponse = array();
        $PaymentResponse['WalletID'] = $_POST["ORDERID"];
        $PaymentResponse['PaymentGatewayResponse'] = json_encode($_POST);
        if ($_POST["STATUS"] == "TXN_FAILURE") {

            /* Update Transaction */
            $PaymentResponse['PaymentGatewayStatus'] = 'Failed';
            $this->Users_model->confirm($PaymentResponse, $UserID);
            redirect(SITE_HOST . ROOT_FOLDER . 'myAccount?status=failed');
        } else {

            /* Verify Transaction */
            $IsValidCheckSum = $this->Users_model->verifychecksum_e($_POST, PAYTM_MERCHANT_KEY, $_POST['CHECKSUMHASH']);
            if ($IsValidCheckSum == "TRUE" && $_POST["STATUS"] == "TXN_SUCCESS") {

                /* Update Transaction */
                $PaymentResponse['PaymentGatewayStatus'] = 'Success';
                $PaymentResponse['Amount'] = $_POST['TXNAMOUNT'];
                $this->Users_model->confirm($PaymentResponse, $UserID);
                redirect(SITE_HOST . ROOT_FOLDER . 'myAccount?status=success');
            } else {

                /* Update Transaction */
                $PaymentResponse['PaymentGatewayStatus'] = 'Failed';
                $this->Users_model->confirm($PaymentResponse, $UserID);
                redirect(SITE_HOST . ROOT_FOLDER . 'myAccount?status=failed');
            }
        }
    }

}
