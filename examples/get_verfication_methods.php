<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
date_default_timezone_set('UTC');


require __DIR__ . '/../vendor/autoload.php';

use Checkpoint\Checkpoint;

if (isset($_POST['username'])) {
    $data['username'] = $_POST['username'];
    $data['password'] = $_POST['password'];
}else {
    header("location:index.php");
    exit();
}

$debug = false;
$truncatedDebug = false;

$checkpoint = new Checkpoint($data, $debug, $truncatedDebug);
//$checkpoint->setProxy('199.189.151.65:54191');

try {
    $response = $checkpoint->login($data['username'], $data['password']);
}catch(Exception $e) {
    if ($checkpoint->isCheckpointRequired($e->getMessage())) {
        $methods = $checkpoint->getCheckpointMethods($e);
        echo "<h2>Choose where you want to get the code</h2>";
        echo "<form action='send_verification_code.php' method='post'>
                 <input type='hidden' name='hash' value='" . $methods['hash'] . "'>
                 <input type='hidden' name='csrf' value='" . $methods['csrf_token'] . "'>
                 <input type='hidden' name='username' value='" . $data['username'] . "'>
                 <input type='hidden' name='password' value='" . $data['password'] . "'>
                 <input type='hidden' name='url' value='" . $methods['url'] . "'>
                 <select name='method'>";
        foreach($methods['methods']['0']['values'] as $field) {
            echo "<option value='" . $field['value'] . "'>'" . $field['label'] . "'</option>";
        }

        echo "</select>
           <input type='submit' value='send code' >";
    } else {
        echo 'Something went wrong: ' . $e->getMessage() . "\n <a href='index.php'>Try again</a>";
        exit();
    }

    exit(0);
}

/* if everything goes right, script comes here */
try {

    $user = $checkpoint->account->getCurrentUser()->getUser();

  

    echo "<h2>Login Successful!</h2>";
    print_r("<pre>");
    $user->printJson();
    print_r("</pre>");
} catch(Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage() . "\n";
}

echo "<br />";
echo "<br /><h3>Response</h3><br />";
print_R($response);
?>
