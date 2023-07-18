<?php
// Handle the payment initialization and redirect to SeerBit checkout page

// Retrieve the transaction details from the request
$publicKey = $_POST['SBPUBK_DQ24K6T5TI1WOAOYPWWYMGMHKDRVEGPW'];
$tranref = $_POST['tranref'];
$currency = $_POST['currency'];
$country = $_POST['country'];
$amount = $_POST['amount'];
$email = $_POST['email'];
$setAmountByCustomer = $_POST['setAmountByCustomer'];
$fullName = $_POST['full_name'];
$tokenize = $_POST['tokenize'];
$planId = $_POST['planId'];
$callbackurl = $_POST['callbackurl'];

// Implement the necessary logic to initialize the payment
// and redirect the user to the SeerBit checkout page

try {
  // Set the maximum execution time to handle timeout
  set_time_limit(30); // Set the maximum time (in seconds) you want the script to execute

  // Perform any necessary validation or business logic here

  // Redirect to the SeerBit checkout page
  $redirectUrl = 'https://checkout.seerbitapi.com/checkout/'; // Replace with the actual checkout URL
  header('Location: ' . $redirectUrl);
  exit();
} catch (Exception $e) {
  // Handle any errors that occur during payment initialization
  // Log the error message for debugging purposes
  error_log('Payment Initialization Error: ' . $e->getMessage());

  // Display an error message to the user or redirect to an error page
  // Replace with your own error handling code or redirect
  echo 'An error occurred during payment initialization. Please try again later.';
}
?>
