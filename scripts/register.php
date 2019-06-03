<?php
/**
 * This script handles registration and payment
 *
 * PHP version 7.2
 *
 * @category Registration_And_Payment
 * @package  Registration_And_Payment
 * @author   Benson Imoh,ST <benson@stbensonimoh.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://stbensonimoh.com
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
// echo json_encode($_POST);
//pull in the database
// require '../config.php';
// require './Paystack.php';
// require './DB.php';
$currency = "NGN";
$amount = 150000 * 100;
// // Capture Post Data that is coming from the form
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$ownABusiness = $_POST['ownABusiness'];
$typeOfBusiness = $_POST['typeOfBusiness'];
$lengthOfExistence = $_POST['lengthOfExistence'];
$referringChannel = $_POST['referringChannel'];
$referrer = $_POST['referrer'];
$details = array(
    "firstName" => $firstName,
    "lastName" => $lastName,
    "email" => $_POST['email'],
    "phone" => $phone,
    "ownABusiness" => $ownABusiness,
    "typeOfBusiness" => $typeOfBusiness,
    "lengthOfExistence" => $lengthOfExistence,
    "referringChannel" => $referringChannel,
    "firstConference" => $firstConference,
    "referrer" => $referrer
);
$db = new DB($host, $db, $username, $password);
// First check to see if user is in the Database
if ($db->userExists($email, "businessity_adsworkshop")) {
    // Check to see if the user has paid
    if ($db->userExistsAndPaid($email, "businessity_adsworkshop")) {
        echo json_encode("user_exists");
    } else {
        // User has registered but hasn't paid so initiatlize payment
        $paystack = new Paystack($paystackKey);
        // throw an exception if there was a problem completing the request,
        // else returns an object created from the json response
        $trx = $paystack->transaction->initialize(
            [
            'amount'=> $amount, /* 20 naira */
            'email'=> $email,
            'currency' => $currency,
            'callback_url' => 'https://businessitygroup.com/adsworkshop/scripts/verify.php',
            'metadata' => json_encode(
                [
                'custom_fields'=> [
                    [
                    'display_name'=> "First Name",
                    'variable_name'=> "first_name",
                    'value'=> $firstName
                    ],
                    [
                    'display_name'=> "Last Name",
                    'variable_name'=> "last_name",
                    'value'=> $lastName
                    ],
                    [
                    'display_name'=> "Mobile Number",
                    'variable_name'=> "mobile_number",
                    'value'=> $phone
                    ]
                ]
                ]
            )
            ]
        );
        echo json_encode($trx->data->authorization_url);
    }
} else {
    // Insert the user into the database
    if ($db->insertUser("businessity_adsworkshop", $details)) {
        $paystack = new Paystack($paystackKey);
        // throw an exception if there was a problem completing the request,
        // else returns an object created from the json response
        $trx = $paystack->transaction->initialize(
            [
            'amount'=> $amount, /* 20 naira */
            'email'=> $email,
            'currency' => $currency,
            'callback_url' => 'https://businessitygroup.com/adsworkshop/scripts/verify.php',
            'metadata' => json_encode(
                [
                'custom_fields'=> [
                    [
                    'display_name'=> "First Name",
                    'variable_name'=> "first_name",
                    'value'=> $firstName
                    ],
                    [
                    'display_name'=> "Last Name",
                    'variable_name'=> "last_name",
                    'value'=> $lastName
                    ],
                    [
                    'display_name'=> "Mobile Number",
                    'variable_name'=> "mobile_number",
                    'value'=> $phone
                    ]
                ]
                ]
            )
            ]
        );
        echo json_encode($trx->data->authorization_url);
    }
}
