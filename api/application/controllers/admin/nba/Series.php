<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Series extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('nba/Sports_model');
	}

	/*
	Description: To get series data
	*/
	public function getSeries_post()
	{
		$SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'],array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()),TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		
		if(!empty($SeriesData)){
			$this->Return['Data'] = $SeriesData['Data'];
		}
	}

	/*
	Description: 	use to get list of filters
	URL: 			/api_admin/entity/getFilterData	
	*/
	public function getFilterData_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesGUID]');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */


		$CategoryTypes = $this->Category_model->getCategoryTypes('',array("ParentCategoryID"=>@$this->ParentCategoryID),true,1,250);
		if($CategoryTypes){
			$Return['CategoryTypes'] = $CategoryTypes['Data']['Records'];			
		}
		$this->Return['Data'] = $Return;

		$SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'],array());
		
		if(!empty($SeriesData)){
			$Return['SeiresData'] = $SeriesData['Data']['Records']; 
		}
		$this->Return['Data'] = $Return;

	}

		/*
	Description: 	Use to update series status.
	URL: 			/api_admin/entity/changeStatus/	
	*/
	public function changeStatus_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
		$this->form_validation->set_rules('AuctionDraftIsPlayed', 'AuctionDraftIsPlayed', 'trim|required');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		$this->Entity_model->updateEntityInfo($this->SeriesID, array("StatusID"=>$this->StatusID,"AuctionDraftIsPlayed"=>$this->AuctionDraftIsPlayed));
		$this->Sports_model->updateAuctionPlayStatus($this->SeriesID, array("AuctionDraftIsPlayed"=>$this->Post['AuctionDraftIsPlayed']));
		$this->Return['Data']=$this->Sports_model->getSeries('SeriesName,SeriesGUID,StatusID,Status,SeriesStartDate,SeriesEndDate',array('SeriesID' => $this->SeriesID),FALSE,0);
		$this->Return['Message']      	=	"Status has been changed.";
	}
	/*
	Description : use to get series details
	URL 		: /api_admin/series/getSeriesDetails		  
	*/

	public function getSeriesDetails_post(){
		$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
		$this->form_validation->validation($this);  /* Run validation */	

		/* Get Contests Data */
		$SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'],array_merge($this->Post, array('SeriesID' => $this->SeriesID)),FALSE,0);
		if(!empty($SeriesData)){
			$this->Return['Data'] = $SeriesData;
		}
	}
}

?>
