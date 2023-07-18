<?php
session_start();

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleLoginFunctionality();
}

function handleLoginFunctionality()
{
    // Retrieve submitted form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Perform validation (You can customize the validation logic based on your requirements)
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        displayLoginForm($error);
        return;
    }

    // Perform authentication (Implement your own authentication logic here)

    // Assume authentication succeeds for demonstration purposes
    $user = authenticateUser($username, $password);
    if ($user) {
        // Store user information in session
        $_SESSION['user'] = $user;

        // Redirect to the home page or any other page you desire
        header('Location: home.php');
        exit();
    } else {
        $error = 'Invalid username or password.';
        displayLoginForm($error);
    }
}

function authenticateUser($username, $password)
{
    // Implement your authentication logic here
    // This function should check if the provided username and password are valid
    // You can connect to a database, check credentials, and return the user data if authenticated

    // Placeholder code for demonstration purposes
    // Replace this with your actual authentication logic
    $validUsername = 'admin';
    $validPassword = 'password';

    if ($username === $validUsername && $password === $validPassword) {
        // Return user data (e.g., user ID, name, email, etc.)
        return [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'johndoe@example.com'
        ];
    }

    return null;
}

function displayLoginForm($error = null)
{
    // Display the login form
    // You can customize the form structure and styling based on your requirements
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login</title>
    </head>
    <body>
        <h1>Login</h1>

        <?php if ($error) : ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit">Log In</button>
            </div>
        </form>

        <p>
            <a href="password_reset.php">Forgot Password?</a> | 
            <a href="logout.php">Log Out</a>
        </p>
    </body>
    </html>
    <?php
}
