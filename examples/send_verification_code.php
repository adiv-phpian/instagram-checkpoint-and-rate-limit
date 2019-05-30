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

if (isset($_POST['username'])) {
    $data['username'] = $_POST['username'];
    $data['password'] = $_POST['password'];
    $data['csrf'] = $_POST['csrf'];
    $data['url'] = $_POST['url'];
    $data['hash'] = $_POST['hash'];
    $method = $_POST['method'];
} else {
    header("location:index.php");
    exit();
}

$debug = false;
$truncatedDebug = false;

// ////////////////////

$checkpoint = new Checkpoint($data, $debug, $truncatedDebug);
//$checkpoint->setProxy('199.189.151.65:54191');
$response = $checkpoint->send_verification_code($method);
$response = json_decode($response);

if ($response->status == 'ok') {
    echo "Enter the verification code";
    echo "<form action='confirm_verification_code.php' method='post'>
      <input type='hidden' name='hash' value='" . $data['hash'] . "'>
      <input type='hidden' name='csrf' value='" . $data['csrf'] . "'>
      <input type='hidden' name='username' value='" . $data['username'] . "'>
      <input type='hidden' name='password' value='" . $data['password'] . "'>
      <input type='hidden' name='url' value='" . $data['url'] . "'>
       <input type='input' name='code' value=''>
       <input type='submit' value='confirm code' >";
} else {
    echo 'something is wrong, try again. delete cookies files for this user if possible.';
    echo '<a href="index.php">Try again</a>';
}

echo "<br />";
echo "<br /><h3>Response</h3><br />";
print_R($response);
?>
