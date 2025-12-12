<?php
//Start the user session
session_start();
//If the session id is empty meaning they didnt login correctly send them back to the login page
if (empty($_SESSION['user_id'])) {
    header("Location: unsubscribe.php");
    die("Redirecting to loginForm.php");
}

//Get sqlite database
$dbfile = __DIR__ . '/demo.sqlite';

try {
    //Connect to sqlite db
    $connection = new PDO("sqlite:$dbfile");
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Get logged in users info
    $sql_query = $connection->prepare("SELECT username FROM users WHERE id = ?");
    $sql_query->execute([$_SESSION['user_id']]);
    $user = $sql_query->fetch();

    //End session is user not found
    if (!$user) {
        die("User not found.");
    }
    $username = $user['username'];
} catch (Exception $e) {
    die("DB connection error: " . htmlspecialchars($e->getMessage()));
}

$message = '';

//handle the unsubscribe request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    //trim any extra space from text field
    $email = trim($_POST['email']);
    //If the input was empty prompt the user to enter in their username
    if ($email === '') {
        $message = 'Please enter your username.';
    } else {
        try {
            $email = $_POST['email'];

            //check if the email exists before deleting
            $check = $connection->query("SELECT COUNT(*) FROM newsletter WHERE userEmail = '$email'");
            $exists = $check->fetchColumn();

            //INTENTIONALLY VULNERABLE SQL
            //The user input is placed directly into the SQL string
            //This means whatever the user types into ccnum can become part of the query itself
            //Because there's no sanitizing or parameterization, an attacker can inject SQL code 
            //like ' OR 1=1-- which will change the logic of the WHERE clause
            //This will cause the query to evalute to true for every row which will delete all users currently signed up for the newsletter and email list
            //preventing them from receiving emails about account changes and activity
            $sql = "DELETE FROM newsletter WHERE userEmail = '$email'";
            $connection->exec($sql);
            //----------------------------------------------------------------------------------------------
            //SAFE SQL QUERY
            //This version of the query is safe as it parameterizes the entered email
            //making sure the original query is unaffected
            //$sql = "DELETE FROM newsletter WHERE userEmail = ?";

            //Successful deletion
            if ($exists > 0) {
                $message = "You've been unsubscribed from the newsletter. You won't be informed about changes to your account.";
            //If no rows were found with that entered username then they arent apart of the newslist or entered in the wrong username
            } else {
                $message = "You're not part of the newsletter list. You're not being informed about changes to your account.";
            }
        } catch (Exception $e) {
            $message = 'Error unsubscribing: ' . htmlspecialchars($e->getMessage());
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Unsubscribe</title>
    <link rel="stylesheet" href="style.css">
</head>
<!-- Add our nav bar on the top -->
<?php include 'Navbar.php'; ?>

<div class="container">
    <!-- print message when unsubscribing -->
    <?php if ($message !== ''): ?>
        <div class="message-box">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <!-- text field to enter username to unsubscribe -->
    <form method="post" action="">
        <h3>Unsubscribe from Newsletter</h3>
        <label for="email">Enter your username to unsubscribe:</label>
        <input type="text" name="email" id="email" required placeholder="username">
        <br>
        <button type="submit" style="color: white;">Unsubscribe</button>
    </form>
</div>


</body>

</html>