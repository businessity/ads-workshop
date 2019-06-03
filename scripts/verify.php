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
    if ($db->updatePaid("businessity_adsworkshop", $details, "email", $email)) {

        //Query the database with Customer email to get phone number;
        if ($db->userExists($email, "businessity_adsworkshop")) {
            // Select the user
            $result = $db->userSelect($email, "businessity_adsworkshop");

            // get the phone number
            foreach ($result as $key => $value) {
                ${$key} = $value;
            }
        }

        $name = $firstName . " " . $lastName;
        require './emails.php';

        //Send SMS
        $notify->viaSMS("Businessity", "Dear {$firstName} {$lastName}, thank you for registering for the Businessity Ads Workshop. Look out for updates in your email and our social media pages. See you at the Workshop!", $phone);

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

        $newsletter->insertIntoList("171492", $emails);

        // Send Email
        $notify->viaEmail("info@businessitygroup.com", "Businessity", $email, $name, $emailBodyFinal, "Successful Registration for Businessity Ads Workshop");

        header('Location: ../success.html');
    }
}
