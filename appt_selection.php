<?php
//Start the user session
session_start();
//If the session id is empty meaning they didnt login correctly send them back to the login page
if (empty($_SESSION['user_id'])) {
    header("Location: appt_selection.php");
    die("Redirecting to index.php");
}

//Get sqlite database
$dbfile = __DIR__ . '/demo.sqlite';

try {
    //Connect to sqlite db
    $connection = new PDO('sqlite:' . $dbfile);
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

//Add an appointment
if (isset($_POST['add_appt'])) {
    //remove leading and trailing white space from entered fields
    $advEmail = trim($_POST['advEmail'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    //If any fields are empty prompt user to fill them in
    if ($advEmail === '' || $date === '' || $time === '') {
        $message = 'Please fill out all required fields.';
    } else {
        //Add new appointment into database
        try {

            //INTENTIONALLY VULNERABLE SQL
            //All user inputs are placed directly into the SQL string
            //All fields except notes are constrained input fields (ex: advisor drop down list, date, time)
            //Thus notes is the only field that an attacker can use to perform an SQL injection
            //This means whatever the user types into notes can become part of the query itself
            //Because there's no sanitizing or parameterization, an attacker can inject SQL code 
            //like '|| (SELECT phonenumber || ' - ' || address FROM advisor WHERE email = 'advisor@example.com') ||' which will change the logic of the WHERE clause
            //This will cause the query to dump the advisor's personal information into the notes section of the appointment record
            $sql = "INSERT INTO appointment (advEmail, userEmail, date, time, notes) VALUES ('$advEmail', '$username', '$date', '$time', '$notes')";
            //----------------------------------------------------------------------------------------------
            //SAFE SQL QUERY
            //This version of the query is safe as it parameterizes the input fields, specifically notes which is the only text field input
            //making sure the original query is unaffected
            //$sql = "INSERT INTO appointment (advEmail, userEmail, date, time, notes) VALUES (?, ?, ?, ?, ?)";
            //$sql_query = $connection->prepare($sql);
            //$sql_query->execute([$advEmail, $username, $date, $time, $notes]);

            $sql_query = $connection->prepare($sql);
            $sql_query->execute();
            $message = 'Appointment booked successfully.';
        } catch (Exception $e) {
            $message = 'Error booking appointment: ' . htmlspecialchars($e->getMessage());
        }
    }
}

//Load the advisors with only their email and expertise
$advisors = [];
try {
    $advisors = $connection->query("SELECT email, expertise  FROM advisor ORDER BY email ASC")->fetchAll();
} catch (Exception $e) {
    $message = "Error loading advisors: " . htmlspecialchars($e->getMessage());
}

//Load the user's appointments
$appointments = [];
try {
    //query db and get all appointments from table with userEmail = User
    $sql_query = $connection->prepare("SELECT advEmail, date, time, notes FROM appointment WHERE userEmail = ? ORDER BY date DESC, time ASC");
    $sql_query->execute([$username]);
    $appointments = $sql_query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error loading appointments: " . htmlspecialchars($e->getMessage());
}


?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Add our nav bar on the top -->
    <?php include 'Navbar.php'; ?>

    <div class="container">
        <!-- print message when making a appointment -->
        <?php if ($message !== ''): ?>
            <div class="message-box">
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>



        <!-- Form for entering new appointment info -->
        <form method="post" action="">
            <!-- Title-->
            <h3>Book an Appointment with an Advisor</h3>

            <!-- Dropdown to select a financial advisor from the table -->
            <label for="advEmail">Select Advisor</label>
            <select name="advEmail" id="advEmail" required>
                <option value="">-- choose an advisor --</option>
                <?php foreach ($advisors as $adv): ?>
                    <option value="<?= htmlspecialchars($adv['email']) ?>">
                        <?= htmlspecialchars($adv['email']) ?> (<?= htmlspecialchars($adv['expertise']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Text field for entering meeting notes -->
            <label for="notes">Meeting Notes</label>
            <input type="text" name="notes" id="notes" placeholder="Optional">

            <!-- Date field for date -->
            <label for="date">Date</label>
            <input type="date" name="date" id="date" required>

            <!-- Get todays date so the user cant pick a date in the past-->
            <script>
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('date').setAttribute('min', today);
            </script>

            <!-- Time field for appointment time -->
            <label for="time">Time</label>
            <input type="time" name="time" id="time" required>

            <!--Button to submit appointment-->
            <button type="submit" name="add_appt" class="btn">Book Appointment</button>
        </form>

        <!-- Display your current appointments -->
        <div class="appointments">
            <h3>Your Appointments</h3>
            <!-- If you have no appointments dispaly this message -->
            <?php if (count($appointments) === 0): ?>
                <p>No appointments booked yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <!-- Table labels -->
                            <th>Advisor Email</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Meeting Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Print appointment info to table-->
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['advEmail']) ?></td>
                                <td><?= htmlspecialchars($a['date']) ?></td>
                                <td><?= htmlspecialchars($a['time']) ?></td>
                                <td><?= htmlspecialchars($a['notes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Print all advisors -->
        <div class="advisors">
            <h3>Available Advisors</h3>
            <table>
                <thead>
                    <tr>
                        <!--Table Labels -->
                        <th>Email</th>
                        <th>Expertise</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Print only the email and expertise of all advisors-->
                    <?php foreach ($advisors as $adv): ?>
                        <tr>
                            <td><?= htmlspecialchars($adv['email']) ?></td>
                            <td><?= htmlspecialchars($adv['expertise']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>