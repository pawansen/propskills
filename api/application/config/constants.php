<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  |--------------------------------------------------------------------------
  | Display Debug backtrace
  |--------------------------------------------------------------------------
  |
  | If set to TRUE, a backtrace will be displayed along with php errors. If
  | error_reporting is disabled, the backtrace will not display, regardless
  | of this setting
  |
 */
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
  |--------------------------------------------------------------------------
  | File and Directory Modes
  |--------------------------------------------------------------------------
  |
  | These prefs are used when checking and setting modes when working
  | with the file system.  The defaults are fine on servers with proper
  | security, but you may wish (or even need) to change the values in
  | certain environments (Apache running a separate process for each
  | user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
  | always be used to set the mode correctly.
  |
 */
defined('FILE_READ_MODE') OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') OR define('DIR_WRITE_MODE', 0755);

/*
  |--------------------------------------------------------------------------
  | File Stream Modes
  |--------------------------------------------------------------------------
  |
  | These modes are used when working with fopen()/popen()
  |
 */
defined('FOPEN_READ') OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
  |--------------------------------------------------------------------------
  | Exit Status Codes
  |--------------------------------------------------------------------------
  |
  | Used to indicate the conditions under which the script is exit()ing.
  | While there is no universal standard for error codes, there are some
  | broad conventions.  Three such conventions are mentioned below, for
  | those who wish to make use of them.  The CodeIgniter defaults were
  | chosen for the least overlap with these conventions, while still
  | leaving room for others to be defined in future versions and user
  | applications.
  |
  | The three main conventions used for determining exit status codes
  | are as follows:
  |
  |    Standard C/C++ Library (stdlibc):
  |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
  |       (This link also contains other GNU-specific conventions)
  |    BSD sysexits.h:
  |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
  |    Bash scripting:
  |       http://tldp.org/LDP/abs/html/exitcodes.html
  |
 */
defined('EXIT_SUCCESS') OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code



/* ---------Site Settings-------- */
/* ------------------------------ */

/* Site Related Settings */
define('SITE_NAME', 'propskills');
define('SITE_CONTACT_EMAIL', 'mwadmin@mailinator.com');
define('MULTISESSION', true);
define('PHONE_NO_VERIFICATION', true);
define('DATE_FORMAT', "%Y-%m-%d %H:%i:%s"); /* dd-mm-yyyy */
define('SPORTS_FILE_PATH', FCPATH . 'uploads/sports.txt');
define('SPORTS_FILE_PATH_SPORTS', FCPATH . 'uploads/sports_');
//define('SPORTS_API_NAME', 'ENTITY');
define('SPORTS_API_NAME', 'FOOTBALL');
define('SPORTS_TEAM_LOGO', 'asset/img/default.svg');

define('DEFAULT_SOURCE_ID', 1);
define('DEFAULT_DEVICE_TYPE_ID', 1);
define('DEFAULT_CURRENCY', '$');
define('REFERRAL_SIGNUP_BONUS', 40);
define('DEFAULT_PLAYER_CREDITS', 6.5);
define('DEFAULT_TIMEZONE', '-05:00');
define('PAGESIZE_MAX', 101);
define('PAGESIZE_DEFAULT', 50);

/* Social */
define('FACEBOOK_URL', 'https://www.facebook.com');
define('TWITTER_URL', 'https://twitter.com');
define('LINKEDIN_URL', 'https://www.linkedin.com');
define('GOOGLE_PLUS_URL', 'https://plus.google.com');

/* Entity Sports API Details */
define('SPORTS_API_URL_ENTITY', 'https://rest.entitysport.com');
define('SPORTS_API_ACCESS_KEY_ENTITY', 'fb37b027631f9e881101d85e2e08bd3e');
define('SPORTS_API_SECRET_KEY_ENTITY', '*******');

/* Cricket API Sports API Details */
define('SPORTS_API_URL_CRICKETAPI', 'https://rest.cricketapi.com');
define('SPORTS_API_ACCESS_KEY_CRICKETAPI', 'a2839160a177fb09aee5685ba0bb1735');
define('SPORTS_API_SECRET_KEY_CRICKETAPI', '********');
define('SPORTS_API_APP_ID_CRICKETAPI', 'exact11');
define('SPORTS_API_DEVICE_ID_CRICKETAPI', '*****');

/* Football API Sports API Details */
define('SPORTS_API_NFL', FALSE);
define('SPORTS_API_NCAAF', FALSE);
define('SPORTS_API_NFL_GOALSERVE', TRUE);

/* For NBA */
define('SPORTS_API_NBA_GOALSERVE', TRUE);

define('SPORTS_API_URL_FOOTBALL', 'https://api.sportsdata.io');

/* Goalserve API Sports API Details */
define('SPORTS_API_URL_GOALSERVE', 'http://www.goalserve.com/getfeed/ba5620ba27174558067d08d849f99c15');

/* PayUMoney Details */
define('PAYUMONEY_MERCHANT_KEY', 'x2kILrKy');
define('PAYUMONEY_MERCHANT_ID', '6487911');
define('PAYUMONEY_SALT', '*****');

/** paypal token set * */
define('PAYPAL_TOKEN_SANDBOX', 'access_token$sandbox$zyxj7qfcjbxpq6d9$1e35a2ec81cd48837040db38c4754fba');
//define('PAYPAL_TOKEN', 'access_token$production$yb9khxtqkwtjb68y$265398d6231f892ae72ddbebbcc8d25b');
define('PAYPAL_TOKEN', 'AV_yscjWHpmCe1SrMQxvm1ME2cwNcCfHwpdfedOcqmthvfEB-8VeoqQraAlqKdwFIIhkJ1gdor79TbaX');

/* SMS API Details */
define('SMS_API_URL', 'https://login.bulksmsgateway.in/sendmessage.php');
define('SMS_API_USERNAME', '****');
define('SMS_API_PASSWORD', '****');

/* SENDINBLUE SMS API Details */
define('SENDINBLUE_SMS_API_URL', 'https://api.sendinblue.com/v3/transactionalSMS/sms');
define('SENDINBLUE_SMS_SENDER', '***');
define('SENDINBLUE_SMS_API_KEY', '***');


/* MSG91 SMS API Details */
define('MSG91_AUTH_KEY', '267776A1tp4SEco5c8c8d48');
define('MSG91_SENDER_ID', 'FSCULT');
define('MSG91_FROM_EMAIL', 'info@cricket.com');

switch (ENVIRONMENT) {
    case 'local':
        /* Paths */
        define('SITE_HOST', 'http://localhost/');
        define('ROOT_FOLDER', 'propskills/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '******');
        define('SMTP_PASS', '******');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '*****');
        define('PAYTM_MERCHANT_KEY', 'LJEavW4BU!t_Jgbx');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://test.payu.in/_payment');

        break;
    case 'testing':

        /* Paths */
        define('SITE_HOST', 'http://159.65.8.30/');
        define('ROOT_FOLDER', 'propskills/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '******');
        define('SMTP_PASS', '******');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', true);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '******');
        define('PAYTM_MERCHANT_KEY', 'LJEavW4BU!t_Jgbx');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://test.payu.in/_payment');
        break;
    case 'demo':
        /* Paths */
        define('SITE_HOST', 'http://54.254.226.187/');
        define('ROOT_FOLDER', 'propskills/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '******');
        define('SMTP_PASS', '******');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '***');
        define('PAYTM_MERCHANT_KEY', '***');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://test.payu.in/_payment');
        break;
    case 'production':
        /* Paths */
        define('SITE_HOST', 'https://www.propskills.com/');
        define('ROOT_FOLDER', '');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '******');
        define('SMTP_PASS', '******');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '****');
        define('PAYTM_MERCHANT_KEY', 'G#aXO******tkPi@B');
        define('PAYTM_DOMAIN', 'securegw.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail109');
        define('PAYTM_WEBSITE_WEB', '*****');
        define('PAYTM_WEBSITE_APP', 'APPPROD');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://secure.payu.in/_payment');
        break;
}

define('BASE_URL', SITE_HOST . ROOT_FOLDER . 'api/');
define('ASSET_BASE_URL', BASE_URL . 'asset/');
define('PROFILE_PICTURE_URL', BASE_URL . 'uploads/profile/picture');
define('DRAFT_TIME', 30);
define('DRAFT_TIME_USER', 30);

