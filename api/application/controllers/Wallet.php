<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wallet extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Users_model');
    }

    /*
      Name: 			add
      Description: 	Use to add wallet cash
      URL: 			/wallet/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('RequestSource', 'RequestSource', 'trim|required|in_list[Web,Mobile]');
        $this->form_validation->set_rules('CouponGUID', 'CouponGUID', 'trim|callback_validateEntityGUID[Coupon,CouponID]');
        $this->form_validation->set_rules('PaymentGateway', 'PaymentGateway', 'trim|required|in_list[PayUmoney,Paytm,Razorpay,Paypal]');
        $this->form_validation->set_rules('Amount', 'Amount', 'trim|required|numeric|callback_validateMinimumDepositAmount');
        $this->form_validation->set_rules('FirstName', 'FirstName', 'trim|required');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|numeric');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $WalletAmount = $this->db->query('SELECT sum(Amount) as TotalAmount FROM tbl_users_wallet WHERE UserID = ' . $this->SessionUserID . ' AND Narration="Join Contest" AND MONTH(EntryDate)='.date('m').' LIMIT 1')->row()->TotalAmount;
        $WalletAmount = $WalletAmount + $this->Post['Amount'];

        $MaximumDepositLimitPerMonth = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MaximumDepositLimitPerMonth" LIMIT 1')->row()->ConfigTypeValue;
        if ($WalletAmount > $MaximumDepositLimitPerMonth) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Deposit amount limit exceeded $".$MaximumDepositLimitPerMonth.". in this month.";
        }

        /*$WalletAmount = $this->db->query('SELECT WalletAmount FROM tbl_users WHERE UserID = ' . $this->SessionUserID . ' LIMIT 1')->row()->WalletAmount;
        $WalletAmount = $WalletAmount + $this->Post['Amount'];
        if ($WalletAmount > 500) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Wallet or Deposit amount can not be more than $500.";
        } else {*/

            $PaymentResponse = $this->Users_model->add($this->Post, $this->SessionUserID, @$this->CouponID);
            if (empty($PaymentResponse)) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Data'] = $PaymentResponse;
                $this->Return['Message'] = "Success.";
            }
        /*}*/
    }

    /*
      Name: 			confirm
      Description: 	Use to update payment gateway response
      URL: 			/wallet/confirm/
     */

    public function confirm_post() {
        /* Validation section */
        $this->form_validation->set_rules('PaymentGateway', 'PaymentGateway', 'trim|required|in_list[PayUmoney,Paytm,Razorpay,Paypal]');
        $this->form_validation->set_rules('PaymentGatewayStatus', 'PaymentGatewayStatus', 'trim|required|in_list[Success,Failed,Cancelled]');
        $this->form_validation->set_rules('WalletID', 'WalletID', 'trim|required|numeric|callback_validateWalletID');
        $this->form_validation->set_rules('PaymentGatewayResponse', 'PaymentGatewayResponse', 'trim');
        $this->form_validation->set_rules('PaymentNonce', 'PaymentNonce', 'trim' . $this->Post['PaymentGateway'] == 'Paypal' ? "|required" : "");
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $WalletData = $this->Users_model->confirm($this->Post, $this->SessionUserID);
        if (!$WalletData) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data'] = $WalletData;
            $this->Return['Message'] = "Successfully payment added.";
        }
    }

    /*
      Name: 			getWallet
      Description: 	To get wallet data
      URL: 			/wallet/getWallet/
     */

    public function getWallet_post() {
        $this->form_validation->set_rules('TransactionMode', 'TransactionMode', 'trim|required|in_list[All,WalletAmount,WinningAmount,CashBonus]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Wallet Data */
        $WalletDetails = $this->Users_model->getWallet(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($WalletDetails)) {
            $this->Return['Data'] = $WalletDetails['Data'];
        }
    }

    /*
      Name: 			withdrawal
      Description: 	Use to withdrawal winning amount
      URL: 			/wallet/withdrawal/
     */

    public function withdrawal_post() {
        /* Validation section */
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateAccountStatus');
        $this->form_validation->set_rules('PaymentGateway', 'PaymentGateway', 'trim|required|in_list[Paytm,Bank]');
        //$this->form_validation->set_rules('PaytmPhoneNumber', 'PaytmPhoneNumber', 'trim' . (!empty($this->Post['PaymentGateway']) && $this->Post['PaymentGateway'] == 'Paytm' ? '|required' : ''));
        $this->form_validation->set_rules('Amount', 'Amount', 'trim|required|numeric|callback_validateWithdrawalAmount');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $WalletData = $this->Users_model->withdrawal($this->Post, $this->SessionUserID);
        if (empty($WalletData)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data'] = $WalletData;
            $this->Return['Message'] = "Success.";
        }
    }

    /*
      Name: 			getWithdrawals
      Description: 	To get Withdrawal data
      URL: 			/wallet/getWithdrawals/
     */

    public function getWithdrawals_post() {
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Withdrawal Data */
        $WithdrawalsData = $this->Users_model->getWithdrawals(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($WithdrawalsData)) {
            $this->Return['Data'] = $WithdrawalsData['Data'];
        }
    }

    /**
     * Function Name: validateMinimumDepositAmount
     * Description:   To validate minimum deposit amount
     */
    public function validateMinimumDepositAmount($Amount) {
        /* Get Minimum Deposit Limit */
        $MinimumDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MinimumDepositLimit" LIMIT 1')->row()->ConfigTypeValue;
        if ($Amount < $MinimumDepositLimit) {
            $this->form_validation->set_message('validateMinimumDepositAmount', 'Minimum deposit amount limit is ' . DEFAULT_CURRENCY . $MinimumDepositLimit);
            return FALSE;
        }
        $MaximumDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MaximumDepositLimit" LIMIT 1')->row()->ConfigTypeValue;
        if ($Amount > $MaximumDepositLimit) {
            $this->form_validation->set_message('validateMinimumDepositAmount', 'Maximun deposit amount limit is ' . DEFAULT_CURRENCY . $MaximumDepositLimit);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: validateWalletID
     * Description:   To validate wallet ID
     */
    public function validateWalletID($WalletID) {
        $WalletData = $this->Users_model->getWallet('Amount,TransactionID,CouponDetails', array('UserID' => $this->SessionUserID, 'WalletID' => $WalletID));
        if (!$WalletData) {
            $this->form_validation->set_message('validateWalletID', 'Invalid {field}.');
            return FALSE;
        } else {
            $this->Post['Amount'] = round($WalletData['Amount'], 1);
            $this->Post['TransactionID'] = $WalletData['TransactionID'];
            $this->Post['CouponDetails'] = $WalletData['CouponDetails'];
            return TRUE;
        }
    }

    /**
     * Function Name: validateWithdrawalAmount
     * Description:   To validate withdrawal amount
     */
    public function validateWithdrawalAmount($Amount) {
        $PrivateContestFee = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MinimumWithdrawalLimitBank" LIMIT 1');
        $MinimumWithdrawalLimitBank = $PrivateContestFee->row()->ConfigTypeValue;
        if ($Amount < $MinimumWithdrawalLimitBank) {
            $this->form_validation->set_message('validateWithdrawalAmount', 'Minimum withdrawal amount limit is ' . DEFAULT_CURRENCY . '' . $MinimumWithdrawalLimitBank . ' ');
            return FALSE;
        }

        /* Validate Winning Amount */
        $UserData = $this->Users_model->getUsers('WinningAmount', array('UserID' => $this->SessionUserID));
        if ($Amount > $UserData['WinningAmount']) {
            $this->form_validation->set_message('validateWithdrawalAmount', 'Withdrawal amount can not greater than to winning amount.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: validateAccountStatus
     * Description:   To validate user account status
     */
    public function validateAccountStatus($UserGUID) {
        /* Validate account status */
        /* $userData = $this->Users_model->getUsers('PanStatus,BankStatus', array('UserID' => $this->SessionUserID));
          if ($userData['BankStatus'] != 'Verified') {
          $this->form_validation->set_message('validateAccountStatus', 'Bank Account details not verified.');
          return FALSE;
          }
          if ($userData['PanStatus'] != 'Verified') {
          $this->form_validation->set_message('validateAccountStatus', 'Pan Card details not verified.');
          return FALSE;
          } */

        /* Validate Pending Withdrawal Request */
        if ($this->db->query('SELECT COUNT(*) AS TotalRecords FROM `tbl_users_withdrawal` WHERE `UserID` = ' . $this->SessionUserID . ' AND `StatusID` = 1')->row()->TotalRecords > 0) {
            $this->form_validation->set_message('validateAccountStatus', 'Your withdrawal request already in pending mode.');
            return FALSE;
        }
        return TRUE;
    }

}
