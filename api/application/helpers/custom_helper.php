<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

function send_mail($emailData = array()) {
    require 'send/vendor/autoload.php';
    $request_body = json_decode('{
            "personalizations": [
            {
                "to": [
                {
                    "email": "' . $emailData['emailTo'] . '"
                }],
                "dynamic_template_data":{
                    "site_url"              :   "' . SITE_HOST . '",
                    "BASE_URL"              :   "' . BASE_URL . '",
                    "ASSET_BASE_URL"        :   "' . ASSET_BASE_URL . '",
                    "SITE_NAME"             :   "' . SITE_NAME . '",
                    "DEFAULT_CURRENCY"      :   "' . DEFAULT_CURRENCY . '",
                    "REFERRAL_SIGNUP_BONUS" :   "' . REFERRAL_SIGNUP_BONUS . '",
                    "FACEBOOK_URL"          :   "' . FACEBOOK_URL . '",
                    "TWITTER_URL"           :   "' . TWITTER_URL . '",
                    "LINKEDIN_URL"          :   "' . LINKEDIN_URL . '",
                    "INSTAGRAM_URL"         :   "' . INSTAGRAM_URL . '",
                    "CompanyName"           :   "Stat Action Sports",
                    "Name"                  :   "' . $emailData['Name'] . '",
                    "EmailText"             :   "' . $emailData['EmailText'] . '",
                    "PhoneNumber"           :   "' . $emailData['PhoneNumber'] . '",
                    "Title"                 :   "' . $emailData['Title'] . '",
                    "Message"               :   "' . $emailData['Message'] . '",
                    "ContestName"           :   "' . $emailData['ContestName'] . '",
                    "SeriesName"            :   "' . $emailData['SeriesName'] . '",
                    "InviteCode"            :   "' . $emailData['InviteCode'] . '",
                    "MatchNo"               :   "' . $emailData['MatchNo'] . '",
                    "TeamNameLocal"         :   "' . $emailData['TeamNameLocal'] . '",
                    "TeamNameVisitor"       :   "' . $emailData['TeamNameVisitor'] . '",
                    "Token"                 :   "' . $emailData['Token'] . '",
                    "DeviceTypeID"          :   "' . $emailData['DeviceTypeID'] . '",
                    "Amount"                :   "' . $emailData['Amount'] . '",
                    "ReferralCode"          :   "' . $emailData['ReferralCode'] . '",
                    "ReferralURL"           :   "' . $emailData['ReferralURL'] . '",
                    "date"                  :   "' . date('Y') . '"
                }
            }
            ],
            "from": {
                "email": "support@statactionsports.com"
                },

                "template_id"   : "' . $emailData['template_id'] . '",
                "subject"       : "' . $emailData['Subject'] . '",
                "content"       : [
                {
                    "type": "text/html",
                    "value": "and easy to do anywhere"
                }
                ]
            }');
    // sending email 
    $apiKey = '';
    $sg = new \SendGrid($apiKey);

    $response = $sg->client->mail()->send()->post($request_body);
    $response->statusCode();
    $response->body();
    $response->headers();
    // print_r($response);
    return $true;
}

/* ------------------------------ */

function sendPushMessage($UserID, $Message, $Data = array()) {
    if (!isset($Data['content_available'])) {
        $Data['content_available'] = 1;
    }
    $Obj = & get_instance();
    $Obj->db->select('U.UserTypeID, US.DeviceTypeID, US.DeviceToken');
    $Obj->db->from('tbl_users_session US');
    $Obj->db->from('tbl_users U');
    $Obj->db->where("US.UserID", $UserID);
    $Obj->db->where("US.UserID", "U.UserID", FALSE);
    $Obj->db->where("US.DeviceToken!=", '');
    $Obj->db->where('US.DeviceToken is NOT NULL', NULL, FALSE);
    if (!MULTISESSION) {
        $this->db->limit(1);
    }
    $Query = $Obj->db->get();
    //echo $Obj->db->last_query();
    if ($Query->num_rows() > 0) {
        foreach ($Query->result_array() as $Notifications) {
            if ($Notifications['DeviceTypeID'] == 2) { /* I phone */
                pushNotificationIphone($Notifications['DeviceToken'], $Notifications['UserTypeID'], $Message, 0, $Data);
            } elseif ($Notifications['DeviceTypeID'] == 3) { /* android */
                pushNotificationAndroid($Notifications['DeviceToken'], $Notifications['UserTypeID'], $Message, $Data);
            }
        }
    }
}

/* ------------------------------ */
/* ------------------------------ */

function pushNotificationAndroid($DeviceIDs, $UserTypeID, $Message, $Data = array()) {
    //API URL of FCM
    $URL = 'https://fcm.googleapis.com/fcm/send';
    /* ApiKey available in:  Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key */
    if ($UserTypeID == 2) {
        if (ENVIRONMENT == 'production') {
            $ApiKey = 'AIzaSyDZvMfF2HbbG_tuEOQQjzeBDXa7EKPth5M';
        } else {
            $ApiKey = 'AIzaSyDZvMfF2HbbG_tuEOQQjzeBDXa7EKPth5M';
        }
    } else {
        if (ENVIRONMENT == 'production') {
            $ApiKey = 'AIzaSyBe5p4qjA1aID7H0gGADnnhQXspHzIgrLk';
        } else {
            $ApiKey = 'AIzaSyBe5p4qjA1aID7H0gGADnnhQXspHzIgrLk';
        }
    }
    $Fields = array('registration_ids' => array($DeviceIDs), 'data' => array("Message" => $Message, "Data" => $Data));
    //header includes Content type and api key
    $Headers = array('Content-Type:application/json', 'Authorization:key=' . $ApiKey);
    $Ch = curl_init();
    curl_setopt($Ch, CURLOPT_URL, $URL);
    curl_setopt($Ch, CURLOPT_POST, true);
    curl_setopt($Ch, CURLOPT_HTTPHEADER, $Headers);
    curl_setopt($Ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($Ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($Ch, CURLOPT_POSTFIELDS, json_encode($Fields));
    $Result = curl_exec($Ch);
    $obj = & get_instance();
    /* Save Log */
    $PushData = array('Body' => json_encode(array_merge($Headers, $Fields), 1), 'DeviceTypeID' => '3', 'Return' => $Result, 'EntryDate' => date("Y-m-d H:i:s"),);
    @$obj->db->insert('log_pushdata', $PushData);
    if ($Result === FALSE) {
        die('FCM Send Error: ' . curl_error($Ch));
    }
    curl_close($Ch);
    return $Result;
}

/* ------------------------------ */
/* ------------------------------ */

function pushNotificationIphone($DeviceToken = '', $UserTypeID, $Message = '', $Badge = 1, $Data = array()) {
    $Badge = ($Badge == 0 ? 1 : 0);
    $Pass = '123456';
    $Body['aps'] = $Data;
    $Body['aps']['alert'] = $Message;
    $Body['aps']['badge'] = (int) $Badge;
    // if ($sound)//$Body['aps']['sound'] = $sound;
    /* End of Configurable Items */
    $Ctx = @stream_context_create();
    // assume the private key passphase was removed.
    stream_context_set_option($Ctx, 'ssl', 'passphrase', $Pass);

    if (ENVIRONMENT == 'production') {
        $Certificate = 'app2-ck-live.pem';
        @stream_context_set_option($Ctx, 'ssl', 'local_cert', $Certificate);
        $Fp = @stream_socket_client('ssl://gateway.push.apple.com:2195', $Err, $Errstr, 60, STREAM_CLIENT_CONNECT, $Ctx); //For Live
    } else {
        $Certificate = 'app2-ck-dev.pem';
        @stream_context_set_option($Ctx, 'ssl', 'local_cert', $Certificate);
        $Fp = @stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $Err, $Errstr, 60, STREAM_CLIENT_CONNECT, $Ctx); //For Testing
    }

    if (!$Fp) {
        return "Failed to connect $Err $Errstr";
    } else {
        try {
            $obj = & get_instance();
            /* Save Log */
            $PushData = array('Body' => json_encode($Body, 1), 'DeviceTypeID' => '2', 'Return' => $Certificate, 'EntryDate' => date("Y-m-d H:i:s"),);
            @$obj->db->insert('log_pushdata', $PushData);
            $Payload = @json_encode($Body, JSON_NUMERIC_CHECK);
            $Msg = @chr(0) . @pack("n", 32) . @pack('H*', @str_replace(' ', '', $DeviceToken)) . @pack("n", @strlen($Payload)) . $Payload;
            @fwrite($Fp, $Msg);
            @fclose($Fp);
        } catch (Exception $E) {
            return 'Caught exception';
        }
    }
}

/* ------------------------------ */
/* ------------------------------ */

function dump($Response, $json = false) {
    if (!$json) {
        echo "<pre>";
        print_r($Response);
        echo "</pre>";
    } else {
        echo json_encode($Response);
    }
    exit;
}

/* ------------------------------ */
/* ------------------------------ */

function sendSMS($input = array()) {
    //Your authentication key
    $authKey = "6a077d91f259674b21e60cffda125dae";
    //Multiple mobiles numbers separated by comma
    $mobileNumber = $input['PhoneNumber'];
    //Sender ID,While using route4 sender id should be 6 characters long.
    $senderId = "BLKSMS";
    //Your message to send, Add URL encoding here.
    $message = $input['Text'];
    //Define route
    $route = "4";
    //Prepare you post parameters
    $postData = array('authkey' => $authKey, 'mobiles' => $mobileNumber, 'message' => $message, 'sender' => $senderId, 'route' => $route);
    if (ENVIRONMENT == 'production') {
        $url = urlencode("http://smsgateway.ca/SendSMS.aspx?CellNumber=$mobileNumber&AccountKey=zP70k0f8S70AacoogxnU3Q7WVnh460yx&MessageBody=$message");
    } else {
        $url = "http://sms.bulksmsserviceproviders.com/api/send_http.php";
    }
    $url = "http://sms.bulksmsserviceproviders.com/api/send_http.php";
    // init the resource
    $ch = curl_init();
    curl_setopt_array($ch, array(CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData
            //,CURLOPT_FOLLOWLOCATION => true
    ));
    //Ignore SSL certificate verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //get response
    $output = curl_exec($ch);
    //Print error if any
    if (curl_errno($ch)) {
        echo 'error:' . curl_error($ch);
    }
    curl_close($ch);
    //echo $output;
}

/* ------------------------------ */
/* ------------------------------ */

function sendMail($Input = array()) {
    $CI = & get_instance();
    $CI->load->library('email');
    $config['protocol'] = SMTP_PROTOCOL;
    $config['smtp_host'] = SMTP_HOST;
    $config['smtp_port'] = SMTP_PORT;
    $config['smtp_user'] = SMTP_USER;
    $config['smtp_pass'] = SMTP_PASS;
    $config['charset'] = "utf-8";
    $config['mailtype'] = "html";
    $config['wordwrap'] = TRUE;
    $config['smtp_crypto'] = SMTP_CRYPTO;
    $CI->email->initialize($config);
    $CI->email->set_newline("\r\n");
    $CI->email->clear();
    $CI->email->from(FROM_EMAIL, FROM_EMAIL_NAME);
    $CI->email->reply_to(NOREPLY_EMAIL, NOREPLY_NAME);
    $CI->email->to($Input['emailTo']);

    if (defined('TO_BCC') && !empty(TO_BCC)) {
        $CI->email->bcc(TO_BCC);
    }

    if (!empty($Input['emailBcc'])) {
        $CI->email->bcc($Input['emailBcc']);
    }

    $CI->email->subject($Input['emailSubject']);
    $CI->email->message($Input['emailMessage']);
    if (@$CI->email->send()) {
        return true;
    } else {
        //echo $CI->email->print_debugger();
        return false;
    }
}

/* ------------------------------ */
/* ------------------------------ */

function emailTemplate($HTML) {
    $CI = & get_instance();
    return $CI->load->view("emailer/layout", array("HTML" => $HTML), TRUE);
}

/* ------------------------------ */
/* ------------------------------ */

function checkDirExist($DirName) {
    if (!is_dir($DirName))
        mkdir($DirName, 0777, true);
}

/* ------------------------------ */
/* ------------------------------ */

function validateEmail($Str) {
    return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $Str)) ? FALSE : TRUE;
}

/* ------------------------------ */
/* ------------------------------ */

function validateDate($Date) {
    if (strtotime($Date)) {
        return true;
    } else {
        return false;
    }
}

/* ------------------------------ */
/* ------------------------------ */

function paginationOffset($PageNo, $PageSize) {
    if (empty($PageNo)) {
        $PageNo = 1;
    }
    $offset = ($PageNo - 1) * $PageSize;
    return $offset;
}

/* ------------------------------ */
/* ------------------------------ */

function get_guid() {
    if (function_exists('com_create_guid')) {
        return strtolower(com_create_guid());
    } else {
        mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
        return strtolower($uuid);
    }
}

/* ------------------------------ */
/* ------------------------------ */

function dateDiff($FromDateTime, $ToDateTime) {
    $start = date_create($FromDateTime);
    $end = date_create($ToDateTime); // Current time and date
    return $diff = date_diff($start, $end);
    echo 'The difference is ';
    echo $diff->y . ' years, ';
    echo $diff->m . ' months, ';
    echo $diff->d . ' days, ';
    echo $diff->h . ' hours, ';
    echo $diff->i . ' minutes, ';
    echo $diff->s . ' seconds';
    // Output: The difference is 28 years, 5 months, 19 days, 20 hours, 34 minutes, 36 seconds
    echo 'The difference in days : ' . $diff->days;
    // Output: The difference in days : 10398
}

/* ------------------------------ */
/* ------------------------------ */

function diffInHours($startdate, $enddate) {
    $starttimestamp = strtotime($startdate);
    $endtimestamp = strtotime($enddate);
    $difference = abs($endtimestamp - $starttimestamp) / 3600;
    return $difference;
}

/* ------------------------------ */
/* ------------------------------ */

function array_keys_exist(array $needles, array $StrArray) {
    foreach ($needles as $needle) {
        if (in_array($needle, $StrArray))
            return true;
    } return false;
}

/* ------------------------------ */

/* ------------------------------ */
if (!function_exists('convertEstDateTime')) {

    function convertEstDateTime($Datetime = "") {
        $from = 'UTC';
        $to = 'America/New_York';
        $format = 'Y-m-d H:i:s';
        $date = $Datetime; // UTC time
        date_default_timezone_set($from);
        $newDatetime = strtotime($date);
        date_default_timezone_set($to);
        $newDatetime = date($format, $newDatetime);
        date_default_timezone_set('UTC');
        return $newDatetime; //EST time
    }

}

/* ------------------------------ */
/* ------------------------------ */

function footballGetConfiguration($Key) {
    $Football = array();

    $Football['ProFootballPreSeasonOwners'] = array(
        array('Owners' => 16, "RosterSize" => 4, "Start" => 4, "Batch" => 0),
        array('Owners' => 10, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 3, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 2, "RosterSize" => 8, "Start" => 8, "Batch" => 0)
    );

    $Football['ProFootballRegularSeasonOwners'] = array(
        array('Owners' => 16, "RosterSize" => 4, "Start" => 4, "Batch" => 0),
        array('Owners' => 10, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 3, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 2, "RosterSize" => 8, "Start" => 8, "Batch" => 0)
    );

    $Football['ProFootballPlayoffs'] = array(
        array('Owners' => 16, "RosterSize" => 4, "Start" => 4, "Batch" => 0),
        array('Owners' => 10, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 2, "RosterSize" => 8, "Start" => 8, "Batch" => 0)
    );
    /*$Football['ProFootballRegularSeasonOwners'] = array(
        array('Owners' => 4, "RosterSize" => 6, "Start" => 4, "Batch" => 2),
        array('Owners' => 5, "RosterSize" => 5, "Start" => 4, "Batch" => 1),
        array('Owners' => 6, "RosterSize" => 4, "Start" => 3, "Batch" => 1),
        array('Owners' => 7, "RosterSize" => 4, "Start" => 3, "Batch" => 1),
        array('Owners' => 8, "RosterSize" => 4, "Start" => 3, "Batch" => 1)
    );*/

    /*$Football['ProFootballPlayoffs'] = array(
        array('Owners' => 2, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 3, "RosterSize" => 4, "Start" => 4, "Batch" => 0),
        array('Owners' => 4, "RosterSize" => 3, "Start" => 3, "Batch" => 0)
    );*/

    $Football['CollegeFootballRegularSeason'] = array(
        array('Owners' => 6, "RosterSize" => 10, "Start" => 8, "Batch" => 2),
        array('Owners' => 7, "RosterSize" => 10, "Start" => 7, "Batch" => 3),
        array('Owners' => 8, "RosterSize" => 10, "Start" => 6, "Batch" => 4),
        array('Owners' => 9, "RosterSize" => 9, "Start" => 5, "Batch" => 3),
        array('Owners' => 10, "RosterSize" => 8, "Start" => 5, "Batch" => 3),
        array('Owners' => 11, "RosterSize" => 8, "Start" => 5, "Batch" => 3),
        array('Owners' => 12, "RosterSize" => 8, "Start" => 5, "Batch" => 3)
    );

    $Football['CollegeFootballPower5RegularSeason'] = array(
        array('Owners' => 6, "RosterSize" => 6, "Start" => 4, "Batch" => 2),
        array('Owners' => 7, "RosterSize" => 6, "Start" => 4, "Batch" => 2),
        array('Owners' => 8, "RosterSize" => 6, "Start" => 4, "Batch" => 2)
    );

    $Football['CollegeFootballBowlsAfterSeason'] = array(
        array('Owners' => 6, "RosterSize" => 10, "Start" => 10, "Batch" => 0),
        array('Owners' => 7, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 8, "RosterSize" => 8, "Start" => 8, "Batch" => 0)
    );

    return (isset($Football[$Key])) ? $Football[$Key] : array();
}

/* ------------------------------ */
/* ------------------------------ */

function footballGetConfigurationPrivate($Key='ProFootballRegularSeasonOwners') {
    $Football = array();

    $Football['ProFootballRegularSeasonOwners'] = array(
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 8, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 10, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 12, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 11, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 2, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 3, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 4, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 5, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 7, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 9, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
    );
    return (isset($Football[$Key])) ? $Football[$Key] : array();
}


/* ------------------------------ */
/* ------------------------------ */

function footballGetConfigurationPlayersRooster($Size) {
    $Football = array();
    $Football[16] = array('QB' => 1, "RB" => 1, "WR" => 1, "WR/TE" => 1);
    $Football[10] = array('QB' => 1, "RB" => 1, "WR" => 2, "TE" => 1,"FLEX"=>1);
    $Football[3] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);
    $Football[2] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);
    /*- For Private Contest -*/ 
    $Football[6] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);
    $Football[8] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);

    return (isset($Football[$Size])) ? $Football[$Size] : array();
}

function footballGetConfigurationPlayersRoosterPrivateAll($Size) {
    $Football = array();
    $Football[10] = array('QB' => 1, "RB" => 1, "WR" => 2, "TE" => 1,"FLEX"=>1);
    $Football[6] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);
    /*- For Private Contest -*/ 
    $Football[12] = array('QB' => 1, "RB" => 1, "WR" => 2, "TE" => 1,"FLEX"=>1);
    $Football[8] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);

    return (isset($Football[$Size])) ? $Football[$Size] : array();
}

function footballGetConfigurationPlayersRoosterPrivate($Size) {
    $Football = array();
    $Football[6] = array('QB' => 1, "RB" => 1, "WR" => 2, "TE" => 1,"FLEX"=>1);
    $Football[8] = array('QB' => 1, "RB" => 2, "WR" => 3, "TE" => 1,"FLEX"=>1);

    return (isset($Football[$Size])) ? $Football[$Size] : array();
}

function GetRosterDetails($Size) {
    $Roster = array();
    // $Roster[10] = array(
    //                     ['FullName'=>'Quarterback', 'ShortName'  =>'QB','Player' => 1], 
    //                     ['FullName'=>'Running Backs', 'ShortName'=>'RB','Player' => 1], 
    //                     ['FullName'=>'Wide Receivers', 'ShortName'=>'WR','Player'=> 2], 
    //                     ['FullName'=>'Tight End', 'ShortName'=>'TE','Player' => 1],
    //                     ['FullName'=>'Flex', 'ShortName'=>'FLEX','Player' => 1],
    //                 );
    $Roster[6] = array(
                        ['FullName'=>'Quarterback', 'ShortName'  =>'QB','Player' => 1], 
                        ['FullName'=>'Running Backs', 'ShortName'=>'RB','Player' => 1], 
                        ['FullName'=>'Wide Receivers', 'ShortName'=>'WR','Player'=> 2], 
                        ['FullName'=>'Tight End', 'ShortName'=>'TE','Player' => 1],
                        ['FullName'=>'Flex', 'ShortName'=>'FLEX','Player' => 1],
                    );
    // $Roster[6] = array(
    //                     ['FullName'=>'Quarterback', 'ShortName'  =>'QB','Player' => 1], 
    //                     ['FullName'=>'Running Backs', 'ShortName'=>'RB','Player' => 2], 
    //                     ['FullName'=>'Wide Receivers', 'ShortName'=>'WR','Player'=> 3], 
    //                     ['FullName'=>'Tight End', 'ShortName'=>'TE','Player' => 1],
    //                     ['FullName'=>'Flex', 'ShortName'=>'FLEX','Player' => 1],
    //                 );
    $Roster[8] = array(
                        ['FullName'=>'Quarterback', 'ShortName'  =>'QB','Player' => 1], 
                        ['FullName'=>'Running Backs', 'ShortName'=>'RB','Player' => 2], 
                        ['FullName'=>'Wide Receivers', 'ShortName'=>'WR','Player'=> 3], 
                        ['FullName'=>'Tight End', 'ShortName'=>'TE','Player' => 1],
                        ['FullName'=>'Flex', 'ShortName'=>'FLEX','Player' => 1],
                    );
    

    return (isset($Roster[$Size])) ? $Roster[$Size] : array();
}

/* Basketball Configuration */

function basketballGetConfiguration($Key) {
    $Basketball = array();

    $Basketball['ProBasketballRegularSeasonOwners'] = array(
        array('Owners' => 2, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 3, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 10, "RosterSize" => 6, "Start" => 6, "Batch" => 0),
        array('Owners' => 16, "RosterSize" => 4, "Start" => 4, "Batch" => 0)
    );

    $Football['ProBasketballPlayoffs'] = array(
        array('Owners' => 6, "RosterSize" => 8, "Start" => 8, "Batch" => 0),
        array('Owners' => 16, "RosterSize" => 4, "Start" => 4, "Batch" => 0)
    );

    return (isset($Basketball[$Key])) ? $Basketball[$Key] : array();
}

function basketballGetConfigurationPlayersRooster($Size) {
    $Basketball = array();
    $Basketball[2] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1, "C" => 1, "FLEX" => 3);
    $Basketball[3] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1, "C" => 1, "FLEX" => 3);
    $Basketball[6] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1, "C" => 1, "FLEX"=> 3);
    $Basketball[10] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1, "C" => 1, "FLEX" => 1);
    $Basketball[16] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF/C" => 1);

    return (isset($Basketball[$Size])) ? $Basketball[$Size] : array();
}

function basketballGetConfigurationPlayersRoosterPrivate($Size) {
    $Basketball = array();
    $Basketball[8] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF" => 1, "C" => 1, "FLEX"=> 3);
    $Basketball[4] = array('PG' => 1, "SG" => 1, "SF" => 1, "PF/C" => 1);

    return (isset($Basketball[$Size])) ? $Basketball[$Size] : array();
}

function GetIndexValues($Array, $field, $value)
{
    foreach($Array as $key => $ArrVal)
    {
        if ( $ArrVal[$field] === $value )
            return $key;
    }
    return false;
}
