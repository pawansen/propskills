<?php 
    $VERSION = strtotime("now");
    header('X-Frame-Options: sameorigin');
    $a = $_SERVER['REQUEST_URI']; 
    $a = str_replace("/propskills/",'',$a);
    if(strpos($a, '?') !== false){
        $a = substr($a, 0, strpos($a, "?"));
    }
    $data = array_values(array_filter(explode('/',$a)));
    $PathName = '';
    if(count($data) > 0){
        $PathName = $data[count($data)-1];
    }
    switch ($_SERVER['SERVER_NAME']) {
        case 'localhost':
        $base_url = 'http://localhost/propskills/';
        //$api_url = 'http://54.254.226.187/propskills/api/';
        $api_url = 'http://localhost/propskills/api/';
        break;
        case '159.65.8.30':
        $base_url = 'http://159.65.8.30/propskills/';
        $api_url = 'http://159.65.8.30/propskills/api/';
        break;
        case 'mwdemoserver.com':
        $base_url = 'http://mwdemoserver.com/propskills/';
        $api_url = 'http://mwdemoserver.com/propskills/api/';
        break;  
        default :
        $_SERVER['CI_ENV'] = 'production';
        $base_url = 'https://www.propskills.com/';
        $api_url = 'https://www.propskills.com/api/';
        break;
    }
?>
<?php echo "<title>propskills</title>"; ?>