function paywithSeerbit() {
  // Show loading indicator
  showLoadingIndicator();

  var retryCount = 0;
  var maxRetries = 3;

  function initializePayment() {
    // Set a timeout for payment initialization
    var paymentTimeout = setTimeout(function() {
      // Hide loading indicator
      hideLoadingIndicator();

      // Handle timeout scenario
      handlePaymentInitializationError('Timeout');
    }, 30000); // Set the timeout duration (in milliseconds) for payment initialization

    SeerbitPay({
      "public_key": "sbpub_yywuuuywyyttwttwy",
      "tranref": new Date().getTime(),
      "currency": "NGN",
      "country": "NG",
      "amount": "150.00",
      "email": "test@emaildomain.com",
      "setAmountByCustomer": false,
      "full_name": "John Doe",
      "tokenize": false,
      "planId": "123456abcd",
      "callbackurl": "http://yourdomain.com"
    },
    function callback(response, closeModal) {
      // Clear the timeout
      clearTimeout(paymentTimeout);

      // Hide loading indicator
      hideLoadingIndicator();

      console.log(response); // Response of the transaction

      // Show payment completion notification to the user
      showPaymentCompletionNotification();
    },
    function close(close) {
      // Clear the timeout
      clearTimeout(paymentTimeout);

      // Hide loading indicator
      hideLoadingIndicator();

      console.log(close); // Transaction closed
    });
  }

  function retryPayment() {
    retryCount++;

    if (retryCount <= maxRetries) {
      // Retry payment initialization
      initializePayment();
    } else {
      // Hide loading indicator
      hideLoadingIndicator();

      // Handle maximum retry attempts reached
      handlePaymentInitializationError('Max Retries Reached');
    }
  }

  initializePayment();
}

function showLoadingIndicator() {
  // Implement your loading indicator logic here
  // Show a loading spinner or any visual indication that the payment is being processed
}

function hideLoadingIndicator() {
  // Implement your loading indicator logic here
  // Hide the loading spinner or remove the visual indication of the payment process
}

function showPaymentCompletionNotification() {
  // Implement your payment completion notification logic here
  // Show a notification to the user that the payment was successfully completed
}

function handlePaymentInitializationError(error) {
  // Handle various error scenarios, such as timeout or maximum retries reached
  if (error === 'Timeout') {
    // Handle timeout scenario
    console.error('Payment initialization timed out.');
    // Display error message to the user (optional)
    showErrorToUser('Payment initialization timed out. Please try again later.');
  } else if (error === 'Max Retries Reached') {
    // Handle maximum retries reached scenario
    console.error('Maximum retries reached. Please try again later.');
    // Display error message to the user (optional)
    showErrorToUser('Maximum retries reached. Please try again later.');
  } else {
    // Handle other error scenarios
    console.error('An error occurred during payment initialization.');
    // Display error message to the user (optional)
    showErrorToUser('An error occurred during payment initialization. Please try again later.');
  }
}

function showErrorToUser(errorMessage) {
  // Display the error message to the user
  // You can show it in an alert, toast, or any other UI element
  // For example:
  var errorElement = document.getElementById('error-message');
  errorElement.textContent = errorMessage;
  errorElement.style.display = 'block';
}
