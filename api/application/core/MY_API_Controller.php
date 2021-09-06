<?php


defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
class API_Controller extends REST_Controller {
    public $Return = array('ResponseCode' => 200, 'Message' => 'Success', 'Data' => array());
    public function __construct() {
        parent::__construct();
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $this->Post = $_POST = $this->post();
            $this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer|callback_validatePageNo');
            $this->form_validation->set_rules('PageSize', 'PageSize', 'trim|less_than['.PAGESIZE_MAX.']|callback_validatePageSize');
            $this->form_validation->validation($this);            
        }
    }
    
    
    function __destruct() {
        parent::__destruct();
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if(empty($this->Return['Data'])){
                exit;
            }
        }
        if(empty($this->Return['Data'])){
            $this->Return['Data'] = new stdClass();
        }
        $this->Common_model->addInputLog($this->Return);
        $this->response($this->Return);
    }
    
    
    function validateSession($V3yu531s2jlb) {
        if (empty($V3yu531s2jlb)) {
            return TRUE;
        }
        $this->SessionUserID = $this->Users_model->checkSession($V3yu531s2jlb);
        if ($this->SessionUserID) {
            $Vcnp1wz2dbzd = $this->Users_model->getUsers('UserTypeID', array('UserID' => $this->SessionUserID));
            $this->UserTypeID = $Vcnp1wz2dbzd['UserTypeID'];
            $this->UserFullName = $Vcnp1wz2dbzd['FullName'];
            return TRUE;
        }
        $this->Return['ResponseCode'] = 502;
        $this->form_validation->set_message('validateSession', 'Session disconnected, please login.');
        return FALSE;
    }
    
    
    function validateDeviceType($Vd2q5zgghd0r) {
        $this->DeviceTypeID = $this->Common_model->getDeviceTypeID($Vd2q5zgghd0r);
        if ($this->DeviceTypeID) {
            return TRUE;
        }
        $this->form_validation->set_message('validateDeviceType', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateSource($Vxnfj4ysdx4d) {
        $this->SourceID = $this->Common_model->getSourceID($Vxnfj4ysdx4d);
        if ($this->SourceID) {
            return TRUE;
        }
        $this->form_validation->set_message('validateSource', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateStatus($V3ymm21m1smy) {
        if (empty($V3ymm21m1smy)) {
            return TRUE;
        }
        $this->StatusID = $this->Common_model->getStatusID($V3ymm21m1smy);
        if ($this->StatusID) {
            return TRUE;
        }
        $this->form_validation->set_message('validateStatus', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateSourceGUID($Vxsak4i5btbn, $Vxnfj4ysdx4d) {
        if (empty($Vxsak4i5btbn)) {
            return TRUE;
        }
        $this->SourceID = $this->Common_model->getSourceID($Vxnfj4ysdx4d);
        $V5tqio5axdwt = $this->Users_model->getUsers('UserID', array('Password' => $Vxsak4i5btbn, 'SourceID' => $this->SourceID));
        if ($V5tqio5axdwt) {
            $this->form_validation->set_message('validateSourceGUID', 'You are already a registered member, please login to get access your account.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    function validateToken($Vsi2v0sbobbh, $V3fcjkxqutp2) {
        if (empty($Vsi2v0sbobbh)) {
            $this->form_validation->set_message('validateToken', '{field} is required.');
            return FALSE;
        }
        if (!$this->Recovery_model->verifyToken($Vsi2v0sbobbh, $V3fcjkxqutp2)) {
            $this->form_validation->set_message('validateToken', 'Sorry, but its an invalid OTP.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    function validatePhoneNumber($Vhj42lu13yl5, $V3yu531s2jlb = '') {
        if (empty($Vhj42lu13yl5) || !PHONE_NO_VERIFICATION) {
            return TRUE;
        }
        $Vznu5axcrqil = array('PhoneNumber' => $Vhj42lu13yl5);
        if ($V3yu531s2jlb != '') {
            $Ve50worpvxt4 = $this->Users_model->checkSession($V3yu531s2jlb);
            $Vznu5axcrqil = array_merge($Vznu5axcrqil, array('UserIDNot' => $Ve50worpvxt4));
        }
        $V5tqio5axdwt = $this->Users_model->getUsers('UserID', $Vznu5axcrqil);
        if ($V5tqio5axdwt) {
            $this->form_validation->set_message('validatePhoneNumber', 'Sorry, but this phone number is unavailable.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    function validateUsername($Vmvj1ead1fcv, $V3yu531s2jlb = '') {
        if (empty($Vmvj1ead1fcv)) {
            return TRUE;
        }
        $Vznu5axcrqil = array('Username' => $Vmvj1ead1fcv);
        if ($V3yu531s2jlb != '') {
            $Ve50worpvxt4 = $this->Users_model->checkSession($V3yu531s2jlb);
            $Vznu5axcrqil = array_merge($Vznu5axcrqil, array('UserIDNot' => $Ve50worpvxt4));
        }
        $V5tqio5axdwt = $this->Users_model->getUsers('UserID', $Vznu5axcrqil);
        if ($V5tqio5axdwt) {
            $this->form_validation->set_message('validateUsername', 'Sorry, but this username is unavailable.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    function validateEmail($Vuofpuuyygn4, $V3yu531s2jlb = '') {
        if (empty($Vuofpuuyygn4)) {
            return TRUE;
        }
        $Vznu5axcrqil = array('Email' => $Vuofpuuyygn4);
        if ($V3yu531s2jlb != '') {
            $Ve50worpvxt4 = $this->Users_model->checkSession($V3yu531s2jlb);
            $Vznu5axcrqil = array_merge($Vznu5axcrqil, array('UserIDNot' => $Ve50worpvxt4));
        }
        $V5tqio5axdwt = $this->Users_model->getUsers('UserID', $Vznu5axcrqil);
        if ($V5tqio5axdwt) {
            $this->form_validation->set_message('validateEmail', 'Sorry, but this email address is unavailable.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    function validateDate($Vrmu0ff11gig) {
        if ($Vrmu0ff11gig == '') {
            return TRUE;
        }
        if (!strtotime($Vrmu0ff11gig)) {
            $this->form_validation->set_message('validateDate', 'Invalid {field}.');
            return FALSE;
        }
        return TRUE;
    }
    
    
    function validateIP($Vlex4lzcrwze) {
        if ($this->input->valid_ip($Vlex4lzcrwze) || empty($Vlex4lzcrwze)) {
            return TRUE;
        }
        $this->form_validation->set_message('validateIP', 'Invalid {field}.');
        return FALSE;
    }

    
    
    function validateArray($Vbdxmvpx1kbt) {
        if ($Vbdxmvpx1kbt == '') {
            return TRUE;
        }
        if (!is_array($Vbdxmvpx1kbt)) {
            $this->form_validation->set_message('validateArray', 'Invalid format {field}.');
            return FALSE;
        }
        return TRUE;
    }
    
    
    function validateCategoryTypeName($Vqvkzvhixlac) {
        if (empty($Vqvkzvhixlac)) {
            return TRUE;
        }
        $this->CategoryTypeID = $this->Common_model->getCategoryTypeName($Vqvkzvhixlac);
        if ($this->CategoryTypeID) {
            return TRUE;
        }
        $this->form_validation->set_message('validateCategoryTypeName', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateEntityGUID($Vlvqkyaxwexq, $Vwuy01shq1dy) {
        if (empty($Vlvqkyaxwexq)) {
            return TRUE;
        }
        $Vwuy01shq1dy = array_filter(explode(',', $Vwuy01shq1dy));     
        $Vrg5lmsrtpmg = $this->Entity_model->getEntity('E.EntityID', array('EntityGUID' => $Vlvqkyaxwexq, 'EntityTypeName' => !empty($Vwuy01shq1dy[0]) ? $Vwuy01shq1dy[0] : ''));

        if ($Vrg5lmsrtpmg) {
            if(empty($Vwuy01shq1dy)){
                $Vk1h4lostho5 = 'EntityID';
            }
            if(!empty($Vwuy01shq1dy[1])){
                $Vk1h4lostho5 = $Vwuy01shq1dy[1];
            }
            if(!empty($Vwuy01shq1dy[0]) && empty($Vwuy01shq1dy[1])){
                $Vk1h4lostho5 = '';
            }
            if(!empty($Vk1h4lostho5)){
               $this->$Vk1h4lostho5 = $Vrg5lmsrtpmg['EntityID'];
           }
           return TRUE;
       }
       $this->form_validation->set_message('validateEntityGUID', 'Invalid {field}.');
       return FALSE;
   }







    
    
    function validateReferralCode($V33vfbcus3ra) {
        if (empty($V33vfbcus3ra)) {
            return TRUE;
        }
        $V33vfbcus3raData = $this->Common_model->getReferralCode($V33vfbcus3ra);
        if ($V33vfbcus3raData) {
            $this->Referral = $V33vfbcus3raData;
            return TRUE;
        }
        $this->form_validation->set_message('validateReferralCode', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateSection($Vslqn2d4li0p) {
        if (empty($Vslqn2d4li0p)) {
            return TRUE;
        }
        $Vuaywzgw2zqb = $this->Common_model->getSection($Vslqn2d4li0p);
        if ($Vuaywzgw2zqb) {
            $this->SectionData = $Vuaywzgw2zqb;
            return TRUE;
        }
        $this->form_validation->set_message('validateSection', 'Invalid {field}.');
        return FALSE;
    }
    
    
    function validateImage() {
        if (isset($_FILES['File']['name']) && $_FILES['File']['name'] != "") {
            if (in_array(get_mime_by_extension($_FILES['File']['name']), array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'))) {
                return TRUE;
            }
            $this->form_validation->set_message('validateImage', 'Please select only jpg/png File.');
            return TRUE;
        } else {
            $this->form_validation->set_message('validateImage', 'Please choose a File to upload.');
            return FALSE;
        }
    }
    
    
    function validatePageNo($Vcdli1got1yu) {
        $this->PageNo = (empty($Vcdli1got1yu)? '1':$Vcdli1got1yu);
        return TRUE;
    }
    
    
    function validatePageSize($Vupk2utugv0q) {
        $this->PageSize = (empty($Vupk2utugv0q)? PAGESIZE_DEFAULT:$Vupk2utugv0q);
        return TRUE;
    }
}


class API_Controller_Secure extends API_Controller {
    public function __construct() {
        parent::__construct();
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->validation($this);
    }
}
