<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Utility_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /*
      Description: 	Use to get country list
     */

    function getCountries() {
        /* Define section  */
        $Return = array('Data' => array('Records' => array()));
        /* Define variables - ends */
        $Query = $this->db->query("SELECT CountryCode,CountryName,phonecode   FROM `set_location_country` ORDER BY CountryName ASC
		");
        //echo $this->db->last_query();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: 	Use to get banner list
     */

    function bannerList($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,	M.MediaCaption', array("SectionID" => 'Banner'), TRUE);

        if ($MediaData) {
            $Return = ($MediaData ? $MediaData : new StdClass());
            return $Return;
        }

        return false;
    }

    /*
      Description: 	Use to add ReferralCode
     */

    function generateReferralCode($UserID = '') {
        $ReferralCode = random_string('alnum', 6);
        $this->db->insert('tbl_referral_codes', array_filter(array('UserID' => $UserID, 'ReferralCode' => $ReferralCode)));
        return $ReferralCode;
    }

    /*
      Description: Use to manage cron logs
     */

    function insertCronLogs($CronType) {
        $InsertData = array(
            'CronType' => $CronType,
            'EntryDate' => date('Y-m-d H:i:s')
        );
        $this->db->insert('log_cron', $InsertData);
        return $this->db->insert_id();
    }

    /*
      Description: Use to manage cron logs
     */

    function updateCronLogs($CronID) {
        $UpdateData = array(
            'CompletionDate' => date('Y-m-d H:i:s'),
            'CronStatus' => 'Completed'
        );
        $this->db->where('CronID', $CronID);
        $this->db->limit(1);
        $this->db->update('log_cron', $UpdateData);
    }

    /*
      Description: Use to get site config.
     */

    function getConfigs($Where = array()) {
        $this->db->select('ConfigTypeGUID,ConfigTypeDescprition,ConfigTypeValue, (CASE WHEN StatusID = 2 THEN "Active" WHEN StatusID = 6 THEN "Inactive" ELSE "Unknown" END) AS Status');
        $this->db->from('set_site_config');
        if (!empty($Where['ConfigTypeGUID'])) {
            $this->db->where("ConfigTypeGUID", $Where['ConfigTypeGUID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        $this->db->order_by("Sort", 'ASC');
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

        /*
      Description: Use to get site config.
     */

    function getGameType($Where = array()) {
        $this->db->select('*');
        $this->db->from('game_type');
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to update config.
     */

    function updateConfig($ConfigTypeGUID, $Input = array()) {
        if (!empty($Input)) {

            /* Update Config */
            $UpdateData = array(
                'ConfigTypeValue' => $Input['ConfigTypeValue'],
                'StatusID' => $Input['StatusID']
            );
            $this->db->where('ConfigTypeGUID', $ConfigTypeGUID);
            $this->db->limit(1);
            $this->db->update('set_site_config', $UpdateData);
            // $this->db->cache_delete('admin', 'config'); //Delete Cache
        }
    }

    /*
      Description : To add banner
     */

    function addBanner($UserID, $Input = array(), $StatusID) {
        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add to entity table and get ID. */
        $BannerID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 14, "UserID" => $UserID, "StatusID" => $StatusID));

        $this->db->trans_complete($this->SessionUserID, array_merge($this->Post), $this->StatusID);
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return array('BannerID' => $BannerID, 'BannerGUID' => $EntityGUID);
    }

    /*
      Description: Use to send sms on mobile
     */

    function sendMobileSMS($SMSArray) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?country=91&sender=".MSG91_SENDER_ID."&route=4&mobiles=".$SMSArray['PhoneNumber']."&authkey=".MSG91_AUTH_KEY."&message=".$SMSArray['Text'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
      Description: Use to send emails
     */

    function sendMails($MailArray) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://control.msg91.com/api/sendmail.php?body=" . $MailArray['emailMessage'] . "&subject=" . $MailArray['emailSubject'] . "&to=" . $MailArray['emailTo'] . "&from=" . MSG91_FROM_EMAIL . "&authkey=" . MSG91_AUTH_KEY,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
      Description: 	Use to get state list
     */

    function getStates($where = array()) {
        /* Define section  */
        $Return = array('Data' => array('Records' => array()));
        /* Define variables - ends */

        $this->db->select('StateName,CountryCode');
        $this->db->from('set_location_state');
        if (!empty($Where['CountryCode'])) {
            $this->db->where("CountryCode", $Where['CountryCode']);
        }
        $this->db->where("Status",2);
        $this->db->order_by("StateName", 'ASC');

        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();

        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;

        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

     /*
      Description: 	Use to get app version details
     */

    function getAppVersionDetails() {
        $Query = $this->db->query("SELECT ConfigTypeGUID,ConfigTypeDescprition,ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID IN ('AndridAppUrl','AndroidAppVersion','IsAndroidAppUpdateMandatory')");
        if ($Query->num_rows() > 0) {
            $VersionData = array();
            foreach ($Query->result_array() as $Value) {
                $VersionData[$Value['ConfigTypeGUID']] = $Value['ConfigTypeValue'];
            }
            return $VersionData;
        }
        return FALSE;
    }

}
