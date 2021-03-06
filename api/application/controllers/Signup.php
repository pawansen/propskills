<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Recovery_model');
        $this->load->model('Utility_model');
    }

    /*
      Name: 			Signup
      Description: 	Use to register user to system.
      URL: 			/api/signup/
     */

    public function index_post() {
        /* Validation section */
        $this->form_validation->set_rules('Email', 'Email', 'trim' . (empty($this->Post['Source']) || $this->Post['Source'] == 'Direct' ? '|required' : '') . '|valid_email|callback_validateEmail');
        $this->form_validation->set_rules('Username', 'Username', 'trim|alpha_dash|callback_validateUsername');
        $this->form_validation->set_rules('Password', 'Password', 'trim' . (empty($this->Post['Source']) || $this->Post['Source'] == 'Direct' ? '|required' : ''));
        $this->form_validation->set_rules('FirstName', 'FirstName', 'trim');
        $this->form_validation->set_rules('MiddleName', 'MiddleName', 'trim');
        $this->form_validation->set_rules('LastName', 'LastName', 'trim');
        $this->form_validation->set_rules('UserTypeID', 'UserTypeID', 'trim|required|in_list[2,3]');
        $this->form_validation->set_rules('Gender', 'Gender', 'trim|in_list[Male,Female,Other]');
        $this->form_validation->set_rules('BirthDate', 'BirthDate', 'trim|callback_validateDate');
        $this->form_validation->set_rules('Age', 'Age', 'trim|integer');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|callback_validatePhoneNumber|is_unique[tbl_users.PhoneNumber]');
        $this->form_validation->set_rules('Source', 'Source', 'trim|required|callback_validateSource');
        $this->form_validation->set_rules('SourceGUID', 'SourceGUID', 'trim' . (empty($this->Post['Source']) || $this->Post['Source'] != 'Direct' ? '|required' : '') . '|callback_validateSourceGUID[' . @$this->Post['Source'] . ']');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
        $this->form_validation->set_rules('IPAddress', 'IPAddress', 'trim|callback_validateIP');
        $this->form_validation->set_rules('ReferralCode', 'ReferralCode', 'trim|callback_validateReferralCode');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */

        
        if(!empty($this->Post['BirthDate'])){

            // $birthday can be UNIX_TIMESTAMP or just a string-date.
            if(is_string($this->Post['BirthDate'])) {
                $birthday = strtotime($this->Post['BirthDate']);
            }
            if(time() - $birthday < 18 * 31536000)  {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "User is under 18 years of age.'";
                exit;
            }
        }

        $UserID = $this->Users_model->addUser(array_merge($this->Post, array(
            "Referral" => @$this->Referral
                )), $this->Post['UserTypeID'], $this->SourceID, ($this->Post['Source'] != 'Direct' ? '2' : '1') /* if source is not Direct Account treated as Verified */);
        if (!$UserID) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            if (!empty($this->Post['Email'])) {
                /* Send welcome Email to User with Token. (only if source is Direct) */
                $Token = ($this->Post['Source'] == 'Direct' ? $this->Recovery_model->generateToken($UserID, 2) : '');
                 sendMail(array(
                  'emailTo' => $this->Post['Email'],
                  'emailSubject' => "Thank you for registering at " . SITE_NAME,
                  'emailMessage' => emailTemplate($this->load->view('emailer/signup', array(
                  "Name" => $this->Post['FirstName'],
                  'Token' => $Token,
                  'DeviceTypeID' => $this->DeviceTypeID
                  ), TRUE))
                  )); 
                // send_mail(array(
                //     'emailTo' => $this->Post['Email'],
                //     'template_id' => 'd-6be85e7ae1504e419a88c6bef2bd3ed0',
                //     'Subject' => 'Thank you for registering at ' . SITE_NAME,
                //     "Name" => $this->Post['FirstName'],
                //     'Token' => $Token,
                //     'DeviceTypeID' => $this->DeviceTypeID
                // ));
            }

            /* for update phone number */

            /* if (!empty($this->Post['PhoneNumber']) && PHONE_NO_VERIFICATION) {
              $this->load->model('Recovery_model');
              $Token = $this->Recovery_model->generateToken($UserID, 3);
              $this->Utility_model->sendMobileSMS(array(
              'PhoneNumber' => $this->Post['PhoneNumber'],
              'Text' => SITE_NAME . ", OTP for Verify Mobile No. is: $Token"
              ));
              } */

            /* referal code generate */
            $this->Utility_model->generateReferralCode($UserID);
            /* Send welcome notification */
            /*$this->Notification_model->addNotification('welcome', 'Hi and welcome to ' . SITE_NAME . '!', $UserID, $UserID);*/
            /* get user data */
            $UserData = $this->Users_model->getUsers('FirstName,MiddleName,LastName,Email,ProfilePic,UserTypeID,UserTypeName', array(
                'UserID' => $UserID
            ));
            /* create session only if source is not Direct and account treated as Verified. */
            $UserData['SessionKey'] = '';

            $UserData['SessionKey'] = $this->Users_model->createSession($UserID, array(
                "IPAddress" => @$this->Post['IPAddress'],
                "SourceID" => $this->SourceID,
                "DeviceTypeID" => $this->DeviceTypeID,
                "DeviceGUID" => @$this->Post['DeviceGUID'],
                "DeviceToken" => @$this->Post['DeviceToken']
            ));

            $this->Return['Data'] = $UserData;
        }
    }

    /*
      Name: 			resendverify
      Description: 	Use to resend OTP for Email address verification.
      URL: 			/api/signup/resendverify
     */

    public function resendverify_post() {
        /* Validation section */
        $this->form_validation->set_rules('Keyword', 'Keyword', 'trim|required|valid_email');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */
        $UserData = $this->Users_model->getUsers('UserID, FirstName, StatusID', array(
            'Email' => $this->Post['Keyword']
        ));

        if (empty($UserData)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "If your account is registered here you will receive an email from us.";
        } elseif ($UserData && $UserData['StatusID'] == 2) {
            $this->Return['Message'] = "Your account is already verified.";
        } elseif ($UserData && $UserData['StatusID'] == 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Your account has been deleted. Please contact the Admin for more info.";
        } elseif ($UserData && $UserData['StatusID'] == 4) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Your account has been blocked. Please contact the Admin for more info.";
        } elseif ($UserData && $UserData['StatusID'] == 6) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You have deactivated your account, please contact the Admin to reactivate.";
        } else {
            /* Re-Send welcome Email to User with Token. */
             sendMail(array(
              'emailTo' => $this->Post['Keyword'],
              'emailSubject' => "Verify your account " . SITE_NAME,
              'emailMessage' => emailTemplate($this->load->view('emailer/signup', array(
              "Name" => $UserData['FirstName'],
              'Token' => $this->Recovery_model->generateToken($UserData['UserID'], 2),
              'DeviceTypeID' => $this->DeviceTypeID
              ), TRUE))
              )); 
            // send_mail(array(
            //     'emailTo' => $this->Post['Keyword'],
            //     'template_id' => 'd-6be85e7ae1504e419a88c6bef2bd3ed0',
            //     'Subject' => 'Thank you for registering at ' . SITE_NAME,
            //     "Name" => $UserData['FirstName'],
            //     'Token' => $this->Recovery_model->generateToken($UserData['UserID'], 2),
            //     'DeviceTypeID' => $this->DeviceTypeID
            // ));
            $this->Return['Message'] = "Please check your email for instructions.";
        }
    }

    /*
      Name:           SendVerifyEmail
      Description:    Use to send verify Email.
      URL:            /api/signup/resendVerification
     */

    function resendVerification_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Type', 'Type', 'trim|required|in_list[Email,Phone]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $UserData = $this->Users_model->getUsers('FirstName,LastName,Email,EmailForChange,PhoneNumber,UserTypeID,UserTypeName', array("UserID" => $this->SessionUserID));

        if ($this->input->post('Type') == 'Email') {
            /* Send welcome Email to User with Token. */
            $Token = $this->Recovery_model->generateToken($this->SessionUserID, 2);

             sendMail(array(
              'emailTo' => $UserData['Email'],
              'emailSubject' => "Verify your account " . SITE_NAME,
              'emailMessage' => emailTemplate($this->load->view('emailer/signup', array(
              "Name" => $UserData['FirstName'],
              'Token' => $this->Recovery_model->generateToken($this->SessionUserID, 2)
              ), TRUE))
              )); 
            /*send_mail(array(
                'emailTo' => $UserData['EmailForChange'],
                'template_id' => 'd-6be85e7ae1504e419a88c6bef2bd3ed0',
                'Subject' => 'Verify your Email ' . SITE_NAME,
                "Name" => $UserData['FirstName'],
                'Token' => $Token
            ));*/
        } else {
            $Token = $this->Recovery_model->generateToken($this->SessionUserID, 3);
            /* Send change phonenumber SMS to User with Token. */
            $this->Utility_model->sendMobileSMS(array(
                'PhoneNumber' => $UserData['PhoneNumber'],
                'Text' => SITE_NAME . ", OTP to verify Mobile no. is:" . $Token,
            ));
        }
    }

    /*
      Name: 			verifyEmail
      Description: 	Use to verify Email address and activate account by OTP.
      URL: 			/api/signup/verifyEmail
     */

    public
            function verifyEmail_post() {
        /* Validation section */
        $this->form_validation->set_rules('OTP', 'OTP', 'trim|required|callback_validateToken[2]');
        $this->form_validation->set_rules('Source', 'Source', 'trim|required|callback_validateSource');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
        $this->form_validation->set_rules('DeviceGUID', 'DeviceGUID', 'trim');
        $this->form_validation->set_rules('DeviceToken', 'DeviceToken', 'trim');
        $this->form_validation->set_rules('IPAddress', 'IPAddress', 'trim|callback_validateIP');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */
        $UserID = $this->Recovery_model->verifyToken($this->Post['OTP'], 2);
        /* check for email update */
        $UserData = $this->Users_model->getUsers('UserTypeID,FirstName,LastName,Email,EmailForChange,StatusID,ProfilePic', array(
            'UserID' => $UserID
        ));
        if (!empty($UserData['EmailForChange'])) {
            if ($this->Users_model->updateEmail($UserID, $UserData['EmailForChange'])) {
                sendMail(array(
                    'emailTo' => $UserData['Email'],
                    'emailSubject' => "Your " . SITE_NAME . " email has been updated!",
                    'emailMessage' => emailTemplate($this->load->view('emailer/change_email_confirmed', array(
                                "Name" => $UserData['FirstName']
                                    ), TRUE))
                ));
            }
        } else {
            /* change entity status to activate */
            $this->Entity_model->updateEntityInfo($UserID, array(
                "StatusID" => 2
            ));
            /* Create Session */
            $UserData['SessionKey'] = $this->Users_model->createSession($UserID, array(
                "IPAddress" => @$this->Post['IPAddress'],
                "SourceID" => $this->SourceID,
                "DeviceTypeID" => $this->DeviceTypeID,
                "DeviceGUID" => @$this->Post['DeviceGUID'],
                "DeviceToken" => @$this->Post['DeviceToken'],
                "Latitude" => @$this->Post['Latitude'],
                "Longitude" => @$this->Post['Longitude']
            ));
            $this->Return['Data'] = $UserData;
            $this->Return['Message'] = "Your account has been successfully verified, please login to get access your account.";
        }

        $this->Recovery_model->deleteToken($this->Post['OTP'], 2); /* delete token in any case */
    }

    /*
      Name: 			verifyEmail
      Description: 	Use to verify Email address and activate account by OTP.
      URL: 			/api/signup/verifyEmail
     */

    public
            function verifyPhoneNumber_post() {
        /* Validation section */
        $this->form_validation->set_rules('OTP', 'OTP', 'trim|required|callback_validateToken[3]');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */
        $UserID = $this->Recovery_model->verifyToken($this->Post['OTP'], 3);
        /* check for PhoneNo. update */
        $UserData = $this->Users_model->getUsers('PhoneNumberForChange', array(
            'UserID' => $UserID
        ));

        if (!empty($UserData['PhoneNumberForChange'])) {
            if (!$this->Users_model->updatePhoneNumber($UserID, $UserData['PhoneNumberForChange'])) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred. Please contact the Admin for more info.";
            }
        } else {
            $this->Return['Message'] = "Successfully verified.";
        }
        $this->Recovery_model->deleteToken($this->Post['OTP'], 3); /* delete token in any case */
    }

    public function verify_get() {

        $OTP = @$this->input->get('otp');
        $UserData = array();
        $Msg = '';


        $this->load->model('Recovery_model');
        $UserID = $this->Recovery_model->verifyToken($OTP, 2);
        if (!$UserID) {
            $Msg = "Sorry, but this is an invalid link, or you have already verified your account.";
        } else {
            $UserData = $this->Users_model->getUsers('UserTypeID,FirstName,LastName,Email,EmailForChange,StatusID,ProfilePic', array('UserID' => $UserID));

            /* change entity status to activate */
            $this->Entity_model->updateEntityInfo($UserID, array("StatusID" => 2));

            $this->Recovery_model->deleteToken($OTP, 2); /* delete token in any case */
        }

        echo emailTemplate($this->load->view('emailer/email_verify', array('Error' => $Msg, 'UserData' => $UserData), true));
    }

    function randomString($length = 6) {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

}
