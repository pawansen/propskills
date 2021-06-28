<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Signin extends API_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Admin_model');
	}

	/*
	Description: 	Verify login and activate session
	URL: 			/api_admin/signin/
	*/
	public function index_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('Username', 'Username', 'trim'.(empty($this->Post['Source']) || $this->Post['Source']=='Direct' ? '|required' : ''));
		$this->form_validation->set_rules('Password', 'Password', 'trim|required');
		$this->form_validation->set_rules('Source', 'Source', 'trim|required|callback_validateSource');		
		$this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
		$this->form_validation->set_rules('IPAddress', 'IPAddress', 'trim|callback_validateIP');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$UserData=$this->Users_model->getUsers('UserTypeID,UserID,IsAdmin,FirstName,LastName,Email,StatusID,ProfilePic',
			array('LoginKeyword'=>@$this->Post['Username'], 'Password'=>$this->Post['Password'], 'SourceID'=>$this->SourceID));	

		if(!$UserData){
			$this->Return['ResponseCode'] 	=	500;
			$this->Return['Message']      	=	"Invalid login credentials.";
		}elseif($UserData && $UserData['StatusID']==1){
			$this->Return['ResponseCode'] 	=	501;	
			$this->Return['Message']      	=	"You have not activated your account yet, please verify your email address first.";
		}elseif($UserData && $UserData['StatusID']==3){
			$this->Return['ResponseCode'] 	=	500;	
			$this->Return['Message']      	=	"Your account has been deleted. Please contact the Admin for more info.";
		}elseif($UserData && $UserData['StatusID']==4){
			$this->Return['ResponseCode'] 	=	500;	
			$this->Return['Message']      	=	"Your account has been blocked. Please contact the Admin for more info.";
		}elseif($UserData && $UserData['StatusID']==5){
			$this->Return['ResponseCode'] 	=	500;	
			$this->Return['Message']      	=	"You have deactivated your account, please contact the Admin to reactivate.";
		}elseif($UserData && $UserData['IsAdmin']=='No'){
			$this->Return['ResponseCode'] 	=	500;	
			$this->Return['Message']      	=	"Access restricted.";
		}else{

			/*Create Session*/
			$UserData['SessionKey']	= $this->Users_model->createSession($UserData['UserID'], array(
				//"IPAddress"	=>	@$this->Post['IPAddress'],
				"SourceID"		=>	$this->SourceID,
				"DeviceTypeID"	=>	$this->DeviceTypeID,
				//"DeviceGUID"	=>	(empty($this->Post['DeviceGUID']) ? '' : $this->Post['DeviceGUID']),
				//"DeviceToken"	=>	@$this->Post['DeviceToken'],
				//"Latitude"	=>	@$this->Post['Latitude'],
				//"Longitude"	=>	@$this->Post['Longitude']
			));

			/*Get Permitted Modules*/
			$UserData['PermittedModules']		= 	$this->Admin_model->getPermittedModules($UserData['UserTypeID']);
			$UserData['Menu']					= 	$this->Admin_model->getMenu($UserData['UserTypeID']);
			$this->Return['Data']      			=	$UserData;
		}
	}


}
