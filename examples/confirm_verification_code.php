<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
date_default_timezone_set('UTC');
require __DIR__ . '/../vendor/autoload.php';

use Checkpoint\Checkpoint;

// ///// CONFIG ///////

$username = '';
$csrf = '';
$method = 1;

// inputs comes here, if you want to test disable redirection

if (isset($_POST['username'])) {
    $data['username'] = $_POST['username'];
    $data['password'] = $_POST['password'];
    $data['csrf'] = $_POST['csrf'];
    $data['url'] = $_POST['url'];
    $data['hash'] = $_POST['hash'];
    $code = $_POST['code'];
}
else {
    header("location:index.php");
    exit();
}

$debug = false;
$truncatedDebug = false;

// ////////////////////

$checkpoint = new Checkpoint($data, $debug, $truncatedDebug);
//$checkpoint->setProxy('199.189.151.65:54191');
$response = $checkpoint->ConfirmVerificationCode($code);

if ($response->status == 'ok') {
    $user = $checkpoint->account->getCurrentUser()->getUser();

    echo "<h2>Login Successful!</h2>";
    print_r("<pre>");
    $user->printJson();
    print_r("</pre>");


} else {
    echo 'code maybe wrong try again';
    echo "<form action='confirm_verification_code.php' method='post'>
         <input type='hidden' name='hash' value='" . $data['hash'] . "'>
         <input type='hidden' name='csrf' value='" . $data['csrf'] . "'>
         <input type='hidden' name='username' value='" . $data['username'] . "'>
         <input type='hidden' name='password' value='" . $data['password'] . "'>
         <input type='hidden' name='url' value='" . $data['url'] . "'>
         <input type='input' name='code' value=''>
         <input type='submit' value='confirm code' >";
}

echo "<br />";
echo "<br /><h3>Response</h3><br />";
print_R($response);
?>
