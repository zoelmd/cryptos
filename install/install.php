<?php

$dbhost = input('dbhost');
$dbusername = input('dbusername');
$dbname = input('dbname');
$dbprefix = input('dbprefix');
$dbpassword = input('dbpassword');
$fullname = input('fullname');
$username = input('username');
$email = input('email');
$password = input('password');
$confirmPassword = input('confirm_password');
$appID = input('app_id');
$error = false;
$errorType = '';

function hash_make($content)
{
    require_once "../includes/libraries/password.php";
    return password_hash($content, PASSWORD_BCRYPT, array('cost' => 10));
}

class InstallDatabase {
    private static $instance;

    private $host;
    private $dbName;
    private $username;
    private $password;

    public $db;
    private $driver;

    private $dbPrefix;

    public function __construct($host,$dbName,$dbUsername,$dbPassword,$dbPrefix) {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $dbUsername;
        $this->password = $dbPassword;
        $this->dbPrefix = $dbPrefix;
        try {
            $this->db = new \PDO("mysql:host={$this->host};dbname={$this->dbName}", $this->username, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            exit($e->getMessage());
        }
    }

    public function query($query) {
        $args = func_get_args();
        array_shift($args);
        if (isset($args[0]) and is_array($args[0])) {
            $args = $args[0];
        }
        $response = $this->db->prepare($query);
        $response->execute($args);

        return $response;
    }

    public function lastInsertId(){
        return $this->db->lastInsertId();
    }

    public function prepare($query) {

        $args = func_get_args();

        $response = $this->db->prepare($query);
        return $response;
    }

}

//test for
try {
    $db = new InstallDatabase($dbhost,$dbname,$dbusername,$dbpassword,'');
   //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Exception $e) {
    ///exit($e->getMessage());
    $error = true;
    $errorType = 'db';
   // exit('am her');
}

if (!$error) {
    if ($password != $confirmPassword) {
        $error = true;
        $errorType = 'password';
    } else {
        $configHolderContent = file_get_contents('../config-holder.php');
        $configHolderContent = str_replace(array('{host}','{username}','{name}','{password}','{installed}'), array(
            $dbhost,$dbusername,$dbname,$dbpassword,1
        ), $configHolderContent);

        file_put_contents('../config.php', $configHolderContent);


        $sqlContent = file_get_contents('sql.txt');
        $db->query($sqlContent);

        $db = new mysqli($dbhost, $dbusername, $dbpassword, $dbname);

        //insert rate_app_id
        $db->query("INSERT INTO settings (setting_key,setting_value) VALUES('rate_app_id','$appID')");
        //print_r($db->db->errorInfo());

        //add admin
        $password = hash_make($password);


        $time = time();

        $db->query("INSERT INTO members (username,email,password,full_name,role,date_created)
        VALUES('$username','$email','$password','$fullname','1','$time')");

        //install the coins and currencies
        $coins = json_decode(file_get_contents('coins.json'), true);

        $currencies =  json_decode(file_get_contents('currencies.json'), true);
        foreach($coins as $coin) {

            $expected = array(
                'symbol' => '',
                'name' => '',
                'logo' => '',
                'proof_type' => '',
                'website' => '',
                'twitter' => '',
                'description' => '',
                'features' => '',
                'technology' => '',
                'total_supply' => ''
            );
            /**
             * @var $symbol
             * @var $name
             * @var $logo
             * @var $proof_type
             * @var $website
             * @var $twitter
             * @var $description
             * @var $features
             * @var $technology
             * @var $total_supply
             */
            extract(array_merge($expected, $coin));
            $small = 'assets/images/coins/small/'.$logo;
            $logo = 'assets/images/coins/'.$logo;


            $time = time();
            $db->query("INSERT INTO coins (last_time,symbol,name,logo,logo_small,proof_type,website,twitter,description,features,tech,total_supply)
        VALUES('$time','$symbol','$name','$logo','$small','$proof_type','$website','$twitter','$description','$features','$technology','$total_supply')");
        }

        foreach($currencies as $currency) {
            $expected = array(
                'symbol' => '',
                'name' => '',
                'symbol_native' => '',
                'code' => ''
            );
            /**
             * @var $symbol
             * @var $name
             * @var $symbol_native
             * @var $code
             */
            extract(array_merge($expected, $currency));
            $time = time();
            $db->query("INSERT INTO currencies (symbol,name,code,code_native,last_time)
        VALUES('$symbol','$name','$code','$symbol_native','$time')");
        }
        $homeUrl = url('?step=4');

        header("location:".$homeUrl);

    }
}

//header("location:".url('?step'))