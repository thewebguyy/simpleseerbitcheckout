<?php
// Check if the registration form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the submitted form data
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  
  // Perform validation on the submitted data
  $errors = array();
  
  // Validate name
  if (empty($name)) {
    $errors['name'] = 'Name is required';
  }
  
  // Validate email
  if (empty($email)) {
    $errors['email'] = 'Email is required';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
  }
  
  // Validate password
  if (empty($password)) {
    $errors['password'] = 'Password is required';
  }
  
  // If there are no validation errors, proceed with registration
  if (empty($errors)) {
    // TODO: Store the user's registration information in a database or perform other necessary actions
    
    // Generate a unique token for email confirmation
    $token = md5(uniqid());
    
    // TODO: Save the token in the database or another data storage
    
    // Send confirmation email to the user
    $subject = "Email Confirmation";
    $message = "Click the link below to confirm your email:\n\n";
    $message .= "http://yourdomain.com/confirm_email.php?token=" . $token;
    $headers = "From: yourname@example.com";
    
    // Uncomment the line below to send the email
    // mail($email, $subject, $message, $headers);
    
    // Redirect the user to a success page or display a success message
    header('Location: registration_success.php');
    exit;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <h1>Register</h1>
  </header>
  <div class="register-container">
    <form action="register.php" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo isset($name) ? $name : ''; ?>">
        <?php if (isset($errors['name'])) : ?>
          <span class="error"><?php echo $errors['name']; ?></span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>">
        <?php if (isset($errors['email'])) : ?>
          <span class="error"><?php echo $errors['email']; ?></span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password">
        <?php if (isset($errors['password'])) : ?>
          <span class="error"><?php echo $errors['password']; ?></span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="profile_photo">Profile Photo:</label>
        <input type="file" name="profile_photo">
      </div>
      <button type="submit">Register</button>
    </form>
  </div>
</body>
</html>
