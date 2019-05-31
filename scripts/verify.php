<?php
/**
 * This script verifies the transactions and returns a reference
 *
 * PHP version 7.2
 *
 * @category Form_Processors
 * @package  Form_Processor
 * @author   Benson Imoh,ST <benson@stbensonimoh.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://stbensonimoh.com
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php';
require './Paystack.php';
require './DB.php';
require './Notify.php';
require './Newsletter.php';

$db = new DB($host, $db, $username, $password);
$notify = new Notify($smstoken, $emailHost, $emailUsername, $emailPassword, $SMTPDebug, $SMTPAuth, $SMTPSecure, $Port);
$newsletter = new Newsletter($apiUserId, $apiSecret);

$date = date("Y-m-d H:i:s");

$details = array(
    "paid" => "yes",
    "paid_at" => $date,
);

// Initialize Transaction
$paystack = new Paystack($paystackKey);
// the code below throws an exception if there was a problem completing the request,
// else returns an object created from the json response
$trx = $paystack->transaction->verify(
    [
     'reference'=>$_GET['reference']
    ]
);
// status should be true if there was a successful call
if (!$trx->status) {
    exit($trx->message);
}
if ('success' === $trx->data->status) {
    $email = $trx->data->customer->email;

    // Update the database with paid
    if ($db->updatePaid("awlccanada2019", $details, "email", $email)) {

        //Query the database with Customer email to get phone number;
        if ($db->userExists($email, "awlccanada2019")) {
            // Select the user
            $result = $db->userSelect($email, "awlccanada2019");

            // get the phone number
            foreach ($result as $key => $value) {
                ${$key} = $value;
            }
        }

        $name = $firstName . " " . $lastName;
        require './emails.php';

        //Send SMS
        $notify->viaSMS("AWLO Int", "Dear {$firstName} {$lastName}, thank you for registering for African Women in Leadership Conference Canada 2019. Look out for updates on awlo.org/awlc and our social media pages. See you in Canada!", $phone);

        /**
         * Add User to the SendPule mailing List
         */
        $emails = array(
                array(
                    'email'         =>  $email,
                    'variables'     =>  array(
                    'phone'         =>  $phone,
                    'name'          =>  $firstName,
                    'lastName'      =>  $lastName
                )
            )
        );

        $newsletter->insertIntoList("2369370", $emails);

        // Send Email
        $notify->viaEmail("info@awlo.org", "African Women in Leadership Organisation", $email, $name, $emailBodyDelegate, "Successful Registration for #AWLCCanada2019");

        header('Location: ../success.html');
    }
}
