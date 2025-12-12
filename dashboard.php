<?php
//Start the user session
session_start();
//If the session id is empty meaning they didnt login correctly send them back to the login page
if (empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    die("Redirecting to index.php");
}

//Get sqlite database
$dbfile = __DIR__ . '/demo.sqlite';

try {
    //Connect to sqlite db
    $connection = new PDO('sqlite:' . $dbfile);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Get logged in users info
    $sql_query = $connection->prepare("SELECT username, created_at FROM users WHERE id = ?");
    $sql_query->execute([$_SESSION['user_id']]);
    $user = $sql_query->fetch();

    //End session is user not found
    if (!$user) {
        die("User not found.");
    }
    $username = $user['username'];

    //Count number of newsletter subscribers
    $subscriberCount = $connection->query("SELECT COUNT(*) FROM newsletter")->fetchColumn();

    //Handle subscription
    $subscriptionMessage = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_newsletter'])) {
        //Check if user is already in newsletter table
        $sql_query = $connection->prepare("SELECT COUNT(*) FROM newsletter WHERE userEmail = ?");
        $sql_query->execute([$username]);
        //If any rows were returned we know they are already subscribed
        if ($sql_query->fetchColumn() > 0) {
            $subscriptionMessage = "You've already subscribed to the newsletter.";
        } else {
            //Insert user as a new row into the table and increase subscriber count
            $sql_query = $connection->prepare("INSERT INTO newsletter (userEmail) VALUES (?)");
            $sql_query->execute([$username]);
            $subscriptionMessage = "Thanks for subscribing!";
            $subscriberCount++; // reflect updated count
        }
    }
} catch (Exception $e) {
    die("DB error: " . htmlspecialchars($e->getMessage()));
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Add our nav bar on the top -->
    <?php include 'Navbar.php'; ?>

    <div class="container">
        <div class="content">
            <!-- Greet user and display title -->
            <h3>Hello, <span><?= htmlspecialchars($username) ?></span>!</h3>
            <h1>Welcome to The Finance Hub</h1>

            <!-- Website info -->
            <p class="about-text">
                The Finance Hub is your all-in-one platform for connecting with financial advisors and accessing tailor made courses for your financial literacy.
                Stay informed, make better decisions, and grow your wealth with confidence.
            </p>

        <!-- Display number of people already subscribed -->
        <div class="newsletter-box mt-4">
        <p>Join the other <strong><?= $subscriberCount ?></strong> user(s) and sign up for the monthly newsletter, as well as stay informed about changes made to your account.</p>

                <!-- Display subscription message after attempting to subscribe to newsletter -->
                <?php if ($subscriptionMessage !== ''): ?>
                    <p class="message"><?= htmlspecialchars($subscriptionMessage) ?></p>
                <?php endif; ?>

        <!-- Button to subscribe -->
            <form method="post">
                <button type="submit" name="subscribe_newsletter" class="btn">Subscribe</button>
            </form>
        </div>
    </div>

</body>

</html>