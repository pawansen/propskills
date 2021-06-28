<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Common_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}	
	
	/*
	Description: Use to Save POST input to DB
	*/
	function addInputLog($Response){
		if(!API_SAVE_LOG){
			return TRUE;
		}
		@$this->db->insert('log_api', array(
			'URL' 		=> current_url(),
			'RawData'	=> @file_get_contents("php://input"),
			'DataJ'		=> json_encode(array_merge(array("API" => $this->classFirstSegment = $this->uri->segment(2)), $this->Post, $_FILES)),
			'Response'	=> json_encode($Response)
		));
	}


	/*
	Description: 	Use to get EntityTypeID by EntityTypeName
	*/
	function getEntityTypeID($EntityTypeName){
		if(empty($EntityTypeName)){return FALSE;}
		$this->db->select('EntityTypeID');
		$this->db->from('tbl_entity_type');
		$this->db->where('EntityTypeName',$EntityTypeName);
		$this->db->limit(1);
		$Query = $this->db->get();		
		if($Query->num_rows()>0){
			return $Query->row()->EntityTypeID;
		}else{
			return FALSE;
		}
	}


	/*
	Description: 	Use to get SectionID by SectionID
	*/
	function getSection($SectionID){
		if(empty($SectionID)){return FALSE;}
		$this->db->select('*');
		$this->db->from('tbl_media_sections');
		$this->db->where('SectionID',$SectionID);
		$this->db->limit(1);
		$Query = $this->db->get();		
		if($Query->num_rows()>0){
			return $Query->row_array();
		}else{
			return FALSE;
		}
	}


	/*
	Description: 	Use to get DeviceTypeID by DeviceTypeName
	*/
	function getDeviceTypeID($DeviceTypeName){
		if(empty($DeviceTypeName)){return FALSE;}
		$this->db->select('DeviceTypeID');
		$this->db->from('set_device_type');
		$this->db->where('DeviceTypeName',$DeviceTypeName);
		$this->db->limit(1);
		$Query = $this->db->get();	
		if($Query->num_rows()>0){
			return $Query->row()->DeviceTypeID;
		}else{
			return FALSE;
		}
	}
	/*
	Description: 	Use to get SourceID by SourceName
	*/
	function getSourceID($SourceName){
		if(empty($SourceName)){return FALSE;}
		$this->db->select('SourceID');
		$this->db->from('set_source');
		$this->db->where('SourceName',$SourceName);
		$this->db->limit(1);
		$Query = $this->db->get();		
		if($Query->num_rows()>0){
			return $Query->row()->SourceID;
		}else{
			return FALSE;
		}
	}

	/*
	Description: 	Use to get SourceID by SourceName
	*/
	function getStatusID($Status){
		if(empty($Status)){return FALSE;}
		$Query = $this->db->query("SELECT `StatusID` FROM `set_status` WHERE FIND_IN_SET('".$Status."',StatusName) LIMIT 1");	
		if($Query->num_rows()>0){
			return $Query->row()->StatusID;
		}else{
			return FALSE;
		}
	}



     /*
	Description: 	Use to get ReferralCode
	*/
	function getReferralCode($ReferralCode){
		if(empty($ReferralCode)){return FALSE;}
		$this->db->select('ReferralCodeID, UserID');
		$this->db->from('tbl_referral_codes');
		$this->db->where('ReferralCode',$ReferralCode);
		$this->db->limit(1);
		$Query = $this->db->get();		
		if($Query->num_rows()>0){
			return $Query->row();
		}else{
			return FALSE;
		}
	}


     /*
	Description: 	Use to get EntityID by MenuGUID
	*/
	function getCategoryTypeName($CategoryTypeName){
		if(empty($CategoryTypeName)){return FALSE;}
		$this->db->select('CategoryTypeID');
		$this->db->from('set_categories_type');
		$this->db->where('CategoryTypeName',$CategoryTypeName);
		$this->db->limit(1);
		$Query = $this->db->get();		
		if($Query->num_rows()>0){
			return $Query->row()->CategoryTypeID;
		}else{
			return FALSE;
		}
	}


}


