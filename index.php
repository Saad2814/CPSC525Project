<?php

//Start user session
session_start();

//Find sqlite database file
$dbfile = __DIR__ . '/demo.sqlite';
try {
    $connection = new PDO('sqlite:' . $dbfile);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Create the tables
    //Create user, appointment, advisor, transaction, and newsletter tables
    $connection->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS appointment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            advEmail TEXT NOT NULL,
            userEmail TEXT NOT NULL,
            date TEXT NOT NULL,
            time TEXT NOT NULL,
            notes TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS advisor (
            email TEXT PRIMARY KEY,
            expertise TEXT NOT NULL,
            phonenumber TEXT NOT NULL,
            address TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userEmail TEXT NOT NULL,
            course TEXT NOT NULL,
            ccnum TEXT NOT NULL,
            cvv TEXT NOT NULL,
            expDate TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS newsletter (
            userEmail TEXT PRIMARY KEY
        );
    ");

    //Two sample users
    $users = [
        ['alice99', 'password123'],
        ['bob_182', 'hunter2']
    ];

    //Add users into the users table
    foreach ($users as [$user, $pass]) {
        $sql_query = $connection->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $sql_query->execute([$user]);
        if ($sql_query->fetchColumn() == 0) {
            $sql_query = $connection->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $sql_query->execute([$user, $pass]);
        }
    }

    //Sample advisors
    $advisors = [
        ['michael.james@gmail.com', 'Debt Planning', '587-436-7788', '111 Coral Ave'],
        ['estellebright@gmail.com', 'Investments', '403-876-9023', '89 Evergreen Dr'],
        ['gerald.albright@outlook.com', 'Tax Planning', '909-671-8923', '1010 Rundle St']
    ];

    //Add advisors into the advisors table
    foreach ($advisors as [$email, $expertise, $phone, $address]) {
        $sql_query = $connection->prepare("SELECT COUNT(*) FROM advisor WHERE email = ?");
        $sql_query->execute([$email]);
        if ($sql_query->fetchColumn() == 0) {
            $sql_query = $connection->prepare("INSERT INTO advisor (email, expertise, phonenumber, address) VALUES (?, ?, ?, ?)");
            $sql_query->execute([$email, $expertise, $phone, $address]);
        }
    }

    //Sample appointments
    $appointments = [
        ['michael.james@gmail.com', 'alice99', '2025-11-12', '10:00', 'I want to meet virtually'],
        ['estellebright@gmail.com', 'alice99', '2025-11-13', '14:30', 'I would like to talk over coffee'],
        ['gerald.albright@outlook.com', 'alice99', '2025-11-14', '09:00', 'I need help with saving'],
        ['michael.james@gmail.com', 'bob_182',   '2025-11-12', '11:00', 'I need investing tips'],
        ['estellebright@gmail.com', 'bob_182',   '2025-11-13', '15:00', 'I want help with budget planning'],
        ['gerald.albright@outlook.com', 'bob_182',   '2025-11-14', '10:30', 'I would like some career advice']
    ];

    //Add appointments into the appointments table
    foreach ($appointments as [$advEmail, $userEmail, $date, $time, $notes]) {
        $sql_query = $connection->prepare("SELECT COUNT(*) FROM appointment WHERE advEmail = ? AND userEmail = ? AND date = ? AND time = ? AND notes = ?");
        $sql_query->execute([$advEmail, $userEmail, $date, $time, $notes]);
        if ($sql_query->fetchColumn() == 0) {
            $sql_query = $connection->prepare("INSERT INTO appointment (advEmail, userEmail, date, time, notes) VALUES (?, ?, ?, ?, ?)");
            $sql_query->execute([$advEmail, $userEmail, $date, $time, $notes]);
        }
    }

    //Sample transactions
    $transactions = [
        [1, 'alice99', 'Investing 101 - $99.99', '1234 1234 1234 1234', '999', '2026-06'],
        [2, 'bob_182', 'Finance 301 - $299.99', '1111 2222 3333 4444', '676', '2027-09']
    ];

    //Add transactions into the transactions table
    foreach ($transactions as [$id, $userEmail, $course, $ccnum, $cvv, $expDate]) {
        $sql_query = $connection->prepare("SELECT COUNT(*) FROM transactions WHERE id = ? AND userEmail = ? AND course = ? AND ccnum = ? AND cvv = ? AND expDate = ?");
        $sql_query->execute([$id, $userEmail, $course, $ccnum, $cvv, $expDate]);
        if ($sql_query->fetchColumn() == 0) {
            $sql_query = $connection->prepare("INSERT INTO transactions (id, userEmail, course, ccnum, cvv, expDate) VALUES (?, ?, ?, ?, ?, ?)");
            $sql_query->execute([$id, $userEmail, $course, $ccnum, $cvv, $expDate]);
        }
    }
} catch (Exception $e) {
    die("DB error: " . htmlspecialchars($e->getMessage()));
}

//Handle new account registration
if (!empty($_POST['new_username']) && !empty($_POST['new_password'])) {
    $sql_query = $connection->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    try {
        //If no exceptions occur get the ID of the newly created user and sign in
        $sql_query->execute([$_POST['new_username'], $_POST['new_password']]);
        $newUserId = $connection->lastInsertId();
        $_SESSION['user_id'] = $newUserId;
        header("Location: dashboard.php");
        exit;
    } catch (Exception $e) {
        $message = "Username already exists.";
    }
}

//Login logic
$message = $message ?? '';
if (!empty($_POST['username']) && !empty($_POST['password']) && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    //INTENTIONALLY VULNERABLE SQL
    //The user input $username and $password are placed directly into the SQL string
    //This means whatever the user types becomes part of the query itself
    //Because there's no sanitizing or parameterization, an attacker can inject SQL code 
    //like ' OR 1=1-- which will change the logic of the WHERE clause
    //This will cause the query to always evaluate to true, letting an attacker login without the correct password
    //query starts as 'WHERE username = $username AND password = $password' but becomes 'WHERE (username = $username AND password = $password) OR 1=1
    $sql = "SELECT id, username FROM users WHERE username = '$username' AND password = '$password'";
    //----------------------------------------------------------------------------------------------
    //SAFE SQL QUERY
    //This version of the query is safe as it parameterizes the username and password input
    //making sure the original query is unaffected
    //$sql = "SELECT id, username FROM users WHERE username = ? AND password = ?";
    //$sql_query = $connection->prepare($sql);
    //$sql_query->execute([$username, $password]);
    //$returned_rows = $sql_query->fetchAll();


    $rows = $connection->query($sql)->fetchAll();
    foreach ($rows as $row) {
        //Ensure username matches returned row (this means the sql injection can only be placed in the password field)
        if (isset($row['id']) && $row['username'] === $username) {
            $_SESSION['user_id'] = $row['id'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $message = "Invalid credentials.";
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f5f7fa;
        }

        .login-wrap {
            max-width: 420px;
            margin: 48px auto;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        }

        .toggle {
            text-align: center;
            margin-bottom: 20px;
        }

        .toggle button {
            margin: 0 5px;
        }

        /* More styling for login/new account buttons*/
        #showLogin,
        #showRegister {
            background: #a6e8a0;
            color: #000;
            border: none;
        }

        .active-btn {
            background: #5cb85c !important;
            border: 2px solid #2e6b2e !important;
        }
    </style>

</head>

<body>
    <h1 style="text-align: center; font-weight: bold; font-size: 48px;">The Finance Hub</h1>

    <div class="container login-wrap">
        <div class="card">

            <!-- Toggle -->
            <div class="toggle">
                <button id="showLogin" class="btn btn-sm active-btn">Sign In</button>
                <button id="showRegister" class="btn btn-sm">Create Account</button>
            </div>

            <!-- Display error messages -->
            <?php if ($message !== ''): ?>
                <div class="message-box"><strong><?= htmlspecialchars($message) ?></strong></div>
            <?php endif; ?>

            <!-- Login -->
            <form id="loginForm" method="post" autocomplete="off" novalidate>
                <input type="hidden" name="login" value="1">

                <div class="form-group">
                    <label>Username</label>
                    <input name="username" class="form-control" type="text" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input name="password" class="form-control" type="password" required>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <!-- Register -->
            <form id="registerForm" method="post" autocomplete="off" style="display:none;">
                <div class="form-group">
                    <label>Username</label>
                    <input name="new_username" class="form-control" type="text">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input name="new_password" class="form-control" type="password">
                </div>

                <button type="submit" class="btn btn-success">Create Account</button>
            </form>

            <hr>

        </div>
    </div>

    <script>
        //Show login block
        document.getElementById("showLogin").onclick = function() {
            document.getElementById("loginForm").style.display = "block";
            document.getElementById("registerForm").style.display = "none";
            this.classList.add("active-btn");
            document.getElementById("showRegister").classList.remove("active-btn");
        };

        //Show register block
        document.getElementById("showRegister").onclick = function() {
            document.getElementById("loginForm").style.display = "none";
            document.getElementById("registerForm").style.display = "block";
            this.classList.add("active-btn");
            document.getElementById("showLogin").classList.remove("active-btn");
        };
    </script>

</body>

</html>