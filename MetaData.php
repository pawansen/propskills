<?php 
    $VERSION = strtotime("now");
    header('X-Frame-Options: sameorigin');
    $a = $_SERVER['REQUEST_URI']; 
    $a = str_replace("/proskills/",'',$a);
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
        $base_url = 'http://localhost/proskills/';
        //$api_url = 'http://54.254.226.187/proskills/api/';
        $api_url = 'http://localhost/proskills/api/';
        break;
        case '54.254.226.187':
        $base_url = 'http://54.254.226.187/proskills/';
        $api_url = 'http://54.254.226.187/proskills/api/';
        break;
        case 'mwdemoserver.com':
        $base_url = 'http://mwdemoserver.com/proskills/';
        $api_url = 'http://mwdemoserver.com/proskills/api/';
        break;  
        default :
        $_SERVER['CI_ENV'] = 'production';
        $base_url = 'https://www.proskills.com/';
        $api_url = 'https://www.proskills.com/api/';
        break;
    }
?>
<?php echo "<title>Proskills</title>"; ?>