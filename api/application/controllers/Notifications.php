<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
	}

	/*
	Name: 			notifications
	Description: 	Use to get notifications.
	URL: 			/api/notifications
	*/
	public function index_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('NotificationPatternGUID', 'NotificationPatternGUID', 'trim|callback_validateNotificationPatternGUID');
		$this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
		$this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		// $this->Notification_model->markAllRead($this->SessionUserID); /*mark all notifiction to read.*/
		$NotificationData = $this->Notification_model->getNotifications($this->SessionUserID, array_merge(array("NotificationPatternID"=>@$this->NotificationPatternID), $this->Post),@$this->Post['PageNo'], @$this->Post['PageSize']);
		if($NotificationData){
			$this->Return['Data'] = $NotificationData['Data'];
		}
	}



	/*
	Name: 			getNotificationCount
	Description: 	Use to get count of notifications.
	URL: 			/api/notifications/getNotificationCount
	*/
	public function getNotificationCount_post()
	{
		$NotificationData = $this->Notification_model->getNotificationCount('COUNT(NotificationID) TotalUnread',
			array("UserID"=>$this->SessionUserID, "StatusID"=>1));
		$this->Return['Data'] = array("TotalUnread"=>$NotificationData['TotalUnread']);
	}



	/*
	Name: 			markAllRead
	Description: 	Use to mark all notifiction to read.
	URL: 			/api/notifications/markAllRead
	*/
	public function markAllRead_post()
	{
		$this->Notification_model->markAllRead($this->SessionUserID);
	}


	/*
	Name: 			mark Read
	Description: 	Use to mark single notifiction to read.
	URL: 			/api/notifications/markRead
	*/
	public function markRead_post()
	{
		$this->form_validation->set_rules('NotificationID', 'NotificationID', 'trim|required');
		$this->form_validation->validation($this);  /* Run validation */		
		
		$this->Notification_model->markRead($this->SessionUserID,$this->input->post('NotificationID'));
	}

	/*Common Validations*/
	/*------------------------------*/
	/*------------------------------*/	
	function validateNotificationPatternGUID($NotificationPatternGUID)
	{		
		if(empty($NotificationPatternGUID)){
			return TRUE;
		}

		$NotificationPattern = $this->Notification_model->getNotificationPattern($NotificationPatternGUID);
		if($NotificationPattern){
			$this->NotificationPatternID = $NotificationPattern['NotificationPatternID'];
			return TRUE;
		}
		$this->form_validation->set_message('validateNotificationPatternGUID', 'Invalid {field}.');  
		return FALSE;
	}



}
