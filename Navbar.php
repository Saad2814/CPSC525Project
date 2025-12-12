<!DOCTYPE html>
<html lang="en">

<head>
  <title>Navigation Bar</title>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- For Styling -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <style>
    .navbar-nav>li>a,
    .navbar-brand {
      padding-top: 12px;
      padding-bottom: 12px;
      height: 50px;
      display: flex;
      align-items: center;
    }

    .navbar-nav>li>a {
      font-size: 16px;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand" href="dashboard.php">Home</a>
      </div>
      <!-- list of pages we can go to-->
      <ul class="nav navbar-nav">
        <li><a href="appt_selection.php">Appointments</a></li>
        <li><a href="transactions.php">Transactions</a></li>
        <li><a href="unsubscribe.php">Unsubscribe</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <!-- user icon with username -->
        <li><a href="#"><span class="glyphicon glyphicon-user"></span> <?= htmlspecialchars($username) ?></a></li>
        <!-- logout button, returns us to index.php -->
        <li><a href="index.php"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
      </ul>
    </div>
  </nav>
</body>

</html>