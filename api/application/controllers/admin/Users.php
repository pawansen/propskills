<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Users extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
	}


	/*
      Description: 	Use to broadcast message.
      URL: 			/api_admin/users/broadcast/
     */

    public function broadcast_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Title', 'Title', 'trim|required');
        $this->form_validation->set_rules('Message', 'Message', 'trim');
        $this->form_validation->set_rules('selectedUser[]', 'Users', 'trim'.($this->Post['UserType'] == "Selected" ? '|required': ''));
        $this->form_validation->set_rules('MediaGUIDs', 'MediaGUIDs', 'trim'); /* Media GUIDs */
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        /* check for media present - associate media with this Post - ends */
        if ($this->Post['UserType'] == 'Selected') {
            $UsersData=$this->Users_model->getUsers('
                UserID, 
                Username,   
                Email,
                PhoneNumber         
                ',array('AdminUsers' =>'No','UserTypeID' => 2,'UserArray' => $this->Post['selectedUser']), TRUE, 1, 1000000);
        }else {
            $UsersData=$this->Users_model->getUsers('
                UserID, 
                Username,   
                Email,
                PhoneNumber         
                ',array('AdminUsers' =>'No','UserTypeID' => 2), TRUE, 1, 1000000);
        }
        if($UsersData){
            if (!empty($this->Post['Email']) && $this->Post['Email'] == 1) {
                /* Send Email to User */
                send_mail(array(
                    'emailTo'       => implode(',', array_column($UsersData['Data']['Records'], 'Email')),
                    'template_id'   => 'd-a653fd170a47432abd0520f5ce209acf',
                    'Subject'       => SITE_NAME . "-" . $this->Post['Title'],
                    'Message'       => $this->Post['EmailMessage']
                ));
                $this->Return['Message'] = 'Email broadcasted.';

            }elseif(!empty($this->Post['Notification']) && $this->Post['Notification'] == 3) {
                foreach ($UsersData['Data']['Records'] as $Value) {
                    $InsertData[] = array_filter(array(
                        "NotificationPatternID" => 2,
                        "UserID" => $this->SessionUserID,
                        "ToUserID" => $Value['UserID'],
                        "RefrenceID" => "",
                        "NotificationText" => $this->Post['Title'],
                        "NotificationMessage" => $this->Post['Message'],
                        "MediaID" => "",
                        "EntryDate" => date("Y-m-d H:i:s")
                    ));
                }
                if(!empty($InsertData)){
                  $this->db->insert_batch('tbl_notifications', $InsertData);   
                }
                sendPushMessage($this->Post['Title'],$this->Post['Message']);
                $this->Return['Message'] = 'Notification broadcasted.';
            }else{
                $this->Return['Message'] = 'Please Select broadcast Type.';
            }
        }
    }

	/*
	Name: 			getUsers
	Description: 	Use to get users list.
	URL: 			/api_admin/users/getProfile
	*/
	public function index_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('StoreGUID', 'StoreGUID','trim'.($this->UserTypeID==4 ? '|required' : '').'|callback_validateEntityGUID[Store,StoreID]');
		$this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
		$this->form_validation->set_rules('AdminUsers', 'AdminUsers', 'trim');
		$this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
		$this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		/*$UsersData=$this->Users_model->getUsers('RegisteredOn,LastLoginDate,UserTypeName, FullName, Email, Username, ProfilePic, Gender, BirthDate, PhoneNumber, Status, StatusID',$this->Post, TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);*/
		$UsersData=$this->Users_model->getUsers((!empty($this->Post['Params']) ? $this->Post['Params']:''),array_merge($this->Post,array("StatusID" =>@$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		// echo $this->db->last_query();die;
		if($UsersData){
			$this->Return['Data'] = $UsersData['Data'];
		}
	}

		/*
	Name: 			getUsers
	Description: 	Use to get users list.
	URL: 			/api_admin/users/getProfile
	*/
	public function getListAccountReports_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
		$this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
		$this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
		$this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		/*$UsersData=$this->Users_model->getUsers('RegisteredOn,LastLoginDate,UserTypeName, FullName, Email, Username, ProfilePic, Gender, BirthDate, PhoneNumber, Status, StatusID',$this->Post, TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);*/
		
		$UsersData=$this->Users_model->getListAccountReports((!empty($this->Post['Params']) ? $this->Post['Params']:''),array_merge($this->Post,array("StatusID" =>@$this->StatusID,'SeriesID'=>@$this->SeriesID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if($UsersData){
			$this->Return['Data'] = $UsersData['Data'];
		}
	}


	/*
	Description: 	Use to update user profile info.
	URL: 			/api_admin/entity/changeStatus/	
	*/
	public function changeStatus_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->set_rules('Username', 'Username', 'trim|callback_validateUsername');

		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		$this->Entity_model->updateEntityInfo($this->UserID, array("StatusID"=>$this->StatusID));
		$this->Users_model->updateUserInfo($this->UserID, $this->Post);
                $this->Return['Data']=$this->Users_model->getUsers('FirstName,LastName,Email,ProfilePic,Status',array("UserID" => $this->UserID));
		$this->Return['Message']      	=	"Status has been changed.";
	}


	/*
	Description: 	Use to update user details as pan and bank details.
	URL: 			/api_admin/entity/changeVerificationStatus/	
	*/
	public function changeVerificationStatus_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('VetificationType', 'VetificationType', 'trim|required');
		if($this->Post['VetificationType']=='PAN'){
			$this->form_validation->set_rules('PanStatus', 'PanStatus', 'trim|required|callback_validateStatus');	
		}
		if($this->Post['VetificationType']=='BANK'){
			$this->form_validation->set_rules('BankStatus', 'BankStatus', 'trim|required|callback_validateStatus');
		}
		$this->form_validation->validation($this);  /* Run validation */		

		/* Validation - ends */
		if($this->Post['VetificationType']=='PAN' && !empty($this->Post['PanStatus'])){
			$UpdateData = array("PanStatus"=>$this->StatusID);
		}
		if($this->Post['VetificationType']=='BANK' && !empty($this->Post['BankStatus'])){
			$UpdateData = array("BankStatus"=>$this->StatusID);
		}
		$this->Users_model->updateUserInfo($this->UserID, $UpdateData);

		/* Get User Data */
		$UserData = $this->Users_model->getUsers('FirstName,LastName,Email,ProfilePic,Status,PanStatus,BankStatus,PhoneNumber',array("UserID" => $this->UserID));

		/* Manage Verification Bonus */
		if($UserData['PanStatus'] == 'Verified' && $UserData['BankStatus'] == 'Verified' && !empty($UserData['PhoneNumber'])){
			$BonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "VerificationBonus" LIMIT 1');
		    if($BonusData->row()->StatusID == 2){
		    	$WalletData = array(
	                        "Amount"          => $BonusData->row()->ConfigTypeValue,
	                        "CashBonus"       => $BonusData->row()->ConfigTypeValue,
	                        "TransactionType" => 'Cr',
	                        "Narration"       => 'Verification Bonus',
	                        "EntryDate"       => date("Y-m-d H:i:s")
	                    );
	    		$this->Users_model->addToWallet($WalletData,$this->UserID,5);
		    }
		}
		$this->Return['Data'] = $UserData;
		$this->Return['Message'] =	"Status has been changed.";
	}




	/*
	Name: 			updateUserInfo
	Description: 	Use to update user profile info.
	URL: 			/api_admin/updateUserInfo/	
	*/
	public function updateUserInfo_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->set_rules('Username', 'Username', 'trim|callback_validateUsername');
		$this->form_validation->set_rules('UserTypeID', 'User Type', 'trim|in_list[3,4]');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$this->Users_model->updateUserInfo($this->UserID, array_merge($this->Post, array("StatusID"=>@$this->StatusID, "SkipPhoneNoVerification"=>true)));
		$this->Return['Data']=$this->Users_model->getUsers('StatusID,Status,ProfilePic,Email,Username,Gender,BirthDate,PhoneNumber,UserTypeName,RegisteredOn,LastLoginDate',array("UserID" => $this->UserID));
		$this->Return['Message']      	=	"Successfully updated."; 	
	}




	/*
	Name: 			add
	Description: 	Use to register user to system.
	URL: 			/api_admin/users/add/
	*/
	public function add_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('Email', 'Email','trim|required|valid_email|callback_validateEmail');
		$this->form_validation->set_rules('Password', 'Password', 'trim'.(empty($this->Post['Source']) || $this->Post['Source']=='Direct' ? '|required' : ''));
		$this->form_validation->set_rules('FirstName', 'FirstName', 'trim|required');
		$this->form_validation->set_rules('LastName', 'LastName', 'trim');
		$this->form_validation->set_rules('UserTypeID', 'UserTypeID', 'trim|required|in_list[1,2,3,4]');
		$this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|callback_validatePhoneNumber');
		$this->form_validation->set_rules('Source', 'Source', 'trim|required|callback_validateSource');	
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');

		$this->form_validation->set_rules('StoreGUID', 'StoreGUID','trim|callback_validateEntityGUID[Store,StoreID]');

		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$UserID = $this->Users_model->addUser($this->Post, $this->Post['UserTypeID'], $this->SourceID, $this->StatusID); 
		if(!$UserID){
			$this->Return['ResponseCode'] 	=	500;
			$this->Return['Message']      	=	"An error occurred, please try again later.";  
		}else{
			/* Send welcome Email to User with login details */
			/*sendMail(array(
				'emailTo' 		=> $this->Post['Email'],			
				'emailSubject'	=> "Your Login Credentials - ".SITE_NAME,
				'emailMessage'	=> emailTemplate($this->load->view('emailer/adduser',array("Name" =>  $this->Post['FirstName'], 'Password' => $this->Post['Password']),TRUE)) 
			));*/
			send_mail(array(
                    'emailTo'       =>  $this->Post['Email'],
                    'template_id'   =>  'd-18fbdb0526ac43e4b7942fc08d8ebcd3',
                    'Subject'       =>  SITE_NAME ."- Your Login Credentials",           
                    "Name"          =>  $this->Post['FirstName'],
                    "Password"  =>  $this->Post['Password']
                ));
			return true;
		}
	}

	/*
	Name: 			getWallet
	Description: 	To get wallet data
	URL: 			/users/getWallet/	
	*/
	public function getWallet_post()
	{
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('TransactionMode', 'TransactionMode', 'trim|required|in_list[All,WalletAmount,WinningAmount,CashBonus]');
		$this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
		$this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
		$this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
		$this->form_validation->validation($this);  /* Run validation */	

		/* Get Wallet Data */
		$WalletDetails = $this->Users_model->getWallet(@$this->Post['Params'],array_merge($this->Post, array('UserID' => $this->UserID)),TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if(!empty($WalletDetails)){
			$this->Return['Data'] = $WalletDetails['Data'];
		}
	}

	/*
	Name: 			getWithdrawals
	Description: 	To get all Withdrawal requests
	URL: 			/users/getWithdrawals/	
	*/
	public function getWithdrawals_post()
	{
		$this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
		$this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
		$this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */	

		/* Get Withdrawal Data */
		$WithdrawalsData = $this->Users_model->getWithdrawals(@$this->Post['Params'],array_merge($this->Post,array("StatusID" =>@$this->StatusID)),TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if(!empty($WithdrawalsData)){
			$this->Return['Data'] = $WithdrawalsData['Data'];
		}
	}

	/*
	Name: 			getWithdrawal
	Description: 	To get Withdrawal data
	URL: 			/users/getWithdrawals/	
	*/
	public function getWithdrawal_post()
	{
		$this->form_validation->set_rules('WithdrawalID', 'WithdrawalID', 'trim|required');
		$this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
		$this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
		$this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
		$this->form_validation->validation($this);  /* Run validation */	

		/* Get Withdrawal Data */
		$WithdrawalsData = $this->Users_model->getWithdrawals(@$this->Post['Params'],array_merge($this->Post,array('WithdrawalID'=> @$this->Post['WithdrawalID'])),TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if(!empty($WithdrawalsData)){
			$this->Return['Data'] = $WithdrawalsData['Data'];
		}
	}


	/*
	Description: 	Use to update user profile info.
	URL: 			/api_admin/entity/changeStatus/	
	*/
	public function changeWithdrawalStatus_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('WithdrawalID', 'WithdrawalID', 'trim|required');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		$this->Users_model->updateWithdrawal(@$this->Post['WithdrawalID'], array("StatusID"=>$this->StatusID));
		$this->Return['Data']=$this->Users_model->getWithdrawals(@$this->Post['Params'],array("WithdrawalID" => @$this->Post['WithdrawalID']));
		$this->Return['Message']      	=	"Status has been changed.";
	}

	/*
		Description : To add cash bonus to user

	*/
	public function addCashBonus_post(){
		// addToWallet
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
		$this->form_validation->set_rules('Amount', 'Amount', 'trim|required|numeric');
		$this->form_validation->set_rules('Narration', 'Narration', 'trim|required');
		$this->form_validation->validation($this);  /* Run validation */
		$this->Users_model->addToWallet($this->Post,$this->UserID,$this->StatusID);
		$this->Return['Data']=$this->Users_model->getUsers('StatusID,Status,ProfilePic,Email,Username,Gender,BirthDate,PhoneNumber,UserTypeName,RegisteredOn,LastLoginDate',array("UserID" => $this->UserID));
		$this->Return['Message']      	=	"Cash bonus added Successfully."; 	
	}

	/*
	Name: 			getReferredUsers
	Description: 	To get all referred users
	URL: 			/users/getReferredUsers/	
	*/
	public function getReferredUsers_post()
	{
		$this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
		$this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
		$this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
		$this->form_validation->validation($this);  /* Run validation */	

		/* Get Referred Users Data */
		$ReferredUsersData = $this->Users_model->getUsers(@$this->Post['Params'],array('ReferredByUserID' => $this->UserID,'OrderBy' => @$this->Post['OrderBy'],'Sequence' => @$this->Post['Sequence']),TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if(!empty($ReferredUsersData)){
			$this->Return['Data'] = $ReferredUsersData['Data'];
		}
	}
}
