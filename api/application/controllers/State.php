<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class State extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('State_model');
    }

        /*
    Description:    Use to add new category
    URL:            /api_admin/category/add 
    */
    public function add_post()
    {
        /* Validation section */
        $this->form_validation->set_rules('StateName', 'StateName', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */        
        /* Validation - ends */

        $this->db->select('*');
        $this->db->from('set_location_state');
        $this->db->where("StateName",$this->Post['StateName']);
        $Query = $this->db->get();
        if($Query->num_rows()>0){
                   $CategoryData = $this->State_model->addState($this->Post);
        /* check for media present - associate media with this Post - ends */
        $this->Return['Message']        =   "This state already exists.";
        $this->Return['ResponseCode']   =500;
        }else{
                   $CategoryData = $this->State_model->addState($this->Post);
        /* check for media present - associate media with this Post - ends */
        $this->Return['Message']        =   "New state added successfully.";  
        }


        
    }

    /*
      Description: 	Use to get Get Attributes.
      URL: 			/api/state/getState
      Input (Sample JSON):
     */

    public function getState_post() {
        $StateData = $this->State_model->getState(TRUE, 1, 50);
        if (!empty($StateData)) {
            $this->Return['Data'] = $StateData['Data'];
        }
    }

        /*
      Description:  Use to get Get Attributes.
      URL:          /api/state/getState
      Input (Sample JSON):
     */

    public function getCountries_post() {
        $StateData = $this->State_model->getCountries(TRUE, 1, 50);
        if (!empty($StateData)) {
            $this->Return['Data'] = $StateData['Data'];
        }
    }

    public function deleteState_post() {

        $this->form_validation->set_rules('StateName', 'StateName', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        $StateData = $this->State_model->deleteState($this->Post['StateName'],$this->Post['CountryCode']);
        if (!empty($StateData)) {
            $this->Return['Message'] = "Successfully deleted";
            $this->Return['Data'] = $StateData;
        }
    }


    public function getStateByName_post() {

        $this->form_validation->set_rules('StateName', 'StateName', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        $StateData = $this->State_model->getStateByName($this->Post['StateName']);
        if (!empty($StateData)) {
            $this->Return['Data'] = $StateData;
        }
    }

    public function editStateByName_post() {

        $this->form_validation->set_rules('StateName', 'StateName', 'trim|required');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required');

        $this->form_validation->validation($this);  /* Run validation */
        $StateData = $this->State_model->editState($this->Post['StateName'],$this->Post['Status']);
        if (!empty($StateData)) {
            $this->Return['Data'] = $StateData;
        }
    }
}