<?php
//Start the user session
session_start();
//If the session id is empty meaning they didnt login correctly send them back to the login page
if (empty($_SESSION['user_id'])) {
    header("Location: transactions.php");
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
    $user = $sql_query->fetch(PDO::FETCH_ASSOC);
    //End session is user not found
    if (!$user) {
        die("User not found.");
    }
    $username = $user['username'];
} catch (Exception $e) {
    die("DB connection error: " . htmlspecialchars($e->getMessage()));
}

$message = '';

//Add new transaction to db
if (isset($_POST['add_trans'])) {
    //Remove extra spaces
    $course = trim($_POST['course'] ?? '');
    $ccnum = trim($_POST['ccnum'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $expDate = trim($_POST['expDate'] ?? '');

    //If any fields are empty prompt user to fill them in
    if ($course === '' || $ccnum === '' || $cvv === '' || $expDate === '') {
        $message = 'Please fill out all required fields.';
    } else {
        try {
            //INTENTIONALLY VULNERABLE SQL
            //All user inputs are placed directly into the SQL string
            //All fields except credit carc number (ccnum) are constrained input fields (ex: cvv, expDate, course)
            //Thus ccnum is the only field that an attacker can use to perform an SQL injection
            //This means whatever the user types into ccnum can become part of the query itself
            //Because there's no sanitizing or parameterization, an attacker can inject SQL code 
            //like '|| (SELECT group_concat(course || ' - ' || ccnum || ' - ' || cvv || ' - ' || expDate, '; ') FROM transactions) ||' which will change the logic of the WHERE clause
            //This will cause the query to dump all transaction records into the ccnum section of the transaction record
            $sql = "INSERT INTO transactions (userEmail, course, ccnum, cvv, expDate) VALUES ('$username', '$course', '$ccnum', '$cvv', '$expDate')";
            //----------------------------------------------------------------------------------------------
            //SAFE SQL QUERY
            //This version of the query is safe as it parameterizes the input fields, specifically ccnum which is the only unconstrained text field input
            //making sure the original query is unaffected
            //$sql = "INSERT INTO transactions (userEmail, course, ccnum, cvv, expDate) VALUES (?, ?, ?, ?, ?)";
            //$sql_query = $connection->prepare($sql);
            //$sql_query->execute([$username, $course, $ccnum, $cvv, $expDate]);

            $sql_query = $connection->prepare($sql);
            $sql_query->execute();
            $message = 'Transaction recorded successfully.';
        } catch (Exception $e) {
            $message = 'Error recording transaction: ' . htmlspecialchars($e->getMessage());
        }
    }
}

//Load user's previous transactions
$transactions = [];
try {
    //get transactions from the table where the userEmail = to logged in user
    $sql_query = $connection->prepare("SELECT course, ccnum, cvv, expDate FROM transactions WHERE userEmail = ? ORDER BY id DESC");
    $sql_query->execute([$username]);
    $transactions = $sql_query->fetchAll();
} catch (Exception $e) {
    $message = "Error loading transactions: " . htmlspecialchars($e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transactions</title>

    <!-- Styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- Add our nav bar on the top -->
    <?php include 'Navbar.php'; ?>

    <div class="container">


        <!-- print message when making a transaction -->
        <?php if ($message !== ''): ?>
            <div class="message-box">
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>


        <form method="post" action="">
            <h3>Enroll in a Course</h3>

            <!-- Drop down menu for selecting which course to buy -->
            <div class="form-group">
                <label for="course">Select Course</label>
                <select name="course" id="course" class="form-control" required>
                    <option value="">-- Choose a course --</option>
                    <option value="Investing 101 - $99.99">Investing 101 - $99.99</option>
                    <option value="Finance 301 - $299.99">Finance 301 - $299.99</option>
                    <option value="How to Budget 101 - $49.99">How to Budget 101 - $49.99</option>
                    <option value="Becoming a Financial Advisor - $125.00">Becoming a Financial Advisor - $125.00</option>
                    <option value="Learning Taxes - $25.00">Learning Taxes - $25.00</option>
                </select>
            </div>


            <div class="form-inline d-flex align-items-end" style="gap: 12px; margin-bottom: 16px;">
                <!-- field to enter credit card number -->
                <div class="form-group" style="flex:1; display:flex; flex-direction:column;">
                    <label for="ccnum">Credit Card Number</label>
                    <input type="text" name="ccnum" id="ccnum" class="form-control" required placeholder="Enter in format: XXXX XXXX XXXX XXXX" style="width:100%; height:38px;">
                </div>

                <!-- Field to enter cvv -->
                <!-- Field input validation to prevent SQL injection -->
                <div class="form-group" style="width:120px; display:flex; flex-direction:column;">
                    <label for="cvv">CVV</label>
                    <input type="number" name="cvv" id="cvv" class="form-control" required placeholder="123" min="100" max="999" style="width:100%; height:38px;">
                </div>

                <!-- field to enter cc expiration date -->
                <div class="form-group" style="width:220px; display:flex; flex-direction:column;">
                    <label for="expDate">Expiration Date</label>
                    <input type="month" name="expDate" id="expDate" class="form-control" required style="width:100%; height:38px;">
                </div>
            </div>

            <!-- Button to submit-->
            <div style="margin-top:12px">
                <button type="submit" name="add_trans" class="btn">Submit Payment</button>
            </div>
        </form>

        <!-- Display your previous transaction -->
        <div class="announcement-display" style="margin-top:20px">
            <h3>Your Course Transactions</h3>
            <?php if (count($transactions) === 0): ?>
                <p>No transactions recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <!-- Labels-->
                                <th>Course</th>
                                <th>Card Number</th>

                            </tr>
                        </thead>
                        <tbody>
                            <!-- Go through each transaction -->
                            <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['course']) ?></td>
                                    <td>
                                        <!-- mask all digits except last 4 of cc -->
                                        <?php
                                        $cc = $row['ccnum'];
                                        //only mask first 12 digits of cc + 3 for spaces
                                        $masked = 'XXXX XXXX XXXX ' . substr($cc, 15);
                                        echo $masked
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>