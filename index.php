<?php
class application
{

    // SQL QUERY FOR USER TABLE
    // CREATE TABLE `tbl_users` ( `user_id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT, `user_name` varchar(64), `user_password_hash` varchar(255), `user_email` varchar(64));
    // CREATE UNIQUE INDEX `user_name_UNIQUE` ON `tbl_users` (`user_name` ASC);
    // CREATE UNIQUE INDEX `user_email_UNIQUE` ON `tbl_users` (`user_email` ASC);
    
    public $feedback = "";
    
    private $user_is_logged_in = false;
    
    private $db_type = "mysql";
    private $db_sql_path = "localhost";
    private $db_name = "user";
    private $db_username = "root";
    private $db_password = "";
    private $db_connection = null;
    
    public function __construct()
    {
        if ($this->performMinimumRequirementsCheck()) {
            $this->runApplication();
        }
    }
    private function performMinimumRequirementsCheck()
    {
        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            echo "Sorry, Simple PHP Login does not run on a PHP version older than 5.3.7 !";
        } elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
            require_once("libraries/password_compatibility_library.php");
            return true;
        } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return true;
        }
        return false;
    }
    public function runApplication()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "register") {
            $this->doRegistration();
            $this->showPageRegistration();
        } else {
            $this->doStartSession();
            $this->performUserLoginAction();
            if ($this->getUserLoginStatus()) {
                $this->showPageLoggedIn();
            } else {
                $this->showPageLoginForm();
            }
        }
        if (isset($_GET["action"]) && $_GET["action"] == "input") {
            $this->showPageAddPassword();
        }
    }
    private function createDatabaseConnection()
    {
        try {
            $this->db_connection = new PDO($this->db_type . ':host=' . $this->db_sql_path . ';dbname=' . $this->db_name, $this->db_username, $this->db_password);
            return true;
        } catch (PDOException $e) {
            $this->feedback = "PDO database connection problem: " . $e->getMessage();
        } catch (Exception $e) {
            $this->feedback = "General problem: " . $e->getMessage();
        }
        return false;
    }
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['user_name']) && ($_SESSION['user_is_logged_in'])) {
            $this->doLoginWithSessionData();
        } elseif (isset($_POST["login"])) {
            $this->doLoginWithPostData();
        }
    }
    private function doStartSession()
    {
        if(session_status() == PHP_SESSION_NONE) session_start();
    }
    private function doLoginWithSessionData()
    {
        $this->user_is_logged_in = true;
    }
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->feedback = "<p style='margin-bottom: 0rem;text-align: center;font-weight: bold;background: rgb(255,0,0);'>You were just logged out.</p>";
    }
    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewUser();
            }
        }
        return false;
    }
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->feedback = "Password field was empty.";
        }
        return false;
    }
    private function checkPasswordCorrectnessAndLogin()
    {
        $sql = 'SELECT user_name, user_email, user_password_hash
                FROM tbl_user
                WHERE user_name = :user_name OR user_email = :user_name
                LIMIT 1';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $_POST['user_name']);
        $query->execute();
        $result_row = $query->fetchObject();
        if ($result_row) {
            if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_email'] = $result_row->user_email;
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
                return true;
            } else {
                $this->feedback = "Wrong password.";
            }
        } else {
            $this->feedback = "This user does not exist.";
        }
        return false;
    }
    private function checkRegistrationData()
    {
        if (!isset($_POST["register"])) {
            return false;
        }
        if (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && strlen($_POST['user_password_new']) >= 6
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->feedback = "Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->feedback = "Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->feedback = "Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->feedback = "Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->feedback = "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->feedback = "Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->feedback = "Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->feedback = "Your email address is not in a valid email format";
        } else {
            $this->feedback = "An unknown error occurred.";
        }
        return false;
    }
    private function createNewUser()
    {
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_email = htmlentities($_POST['user_email'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);
        echo "<script>console.log(" . $user_password_hash . ")</script>";
        $sql = 'SELECT * FROM tbl_user WHERE user_name = :user_name OR user_email = :user_email';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_email', $user_email);
        $query->execute();
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->feedback = "Sorry, that username / email is already taken. Please choose another one.";
        } else {
            $sql = 'INSERT INTO tbl_user (user_name, user_password_hash, user_email)
                    VALUES(:user_name, :user_password_hash, :user_email)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':user_email', $user_email);
            $registration_success_state = $query->execute();
            if ($registration_success_state) {
                $this->feedback = "Your account has been created successfully. You can now log in.";
                return true;
            } else {
                $this->feedback = "Sorry, your registration failed. Please go back and try again.";
            }
        }
        return false;
    }
    private function createDataEntry()
    {
        // Create connection
        $conn = new mysqli($this->db_connection, $this->db_username, $this->db_password, $this->db_name);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $sql = "INSERT INTO tbl_input (input) VALUES ('test')";
        
        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        
        $conn->close();
    }
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }
    private function showPageLoggedIn()
    {
        if ($this->feedback) {
            echo $this->feedback;
        }
        echo '<head>';
        echo '<title>Password Manager</title>';
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';//Bootstrap
        echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>';//Bootstrap
        echo '</head>';
        echo '<nav class="navbar navbar-dark bg-primary">';
        echo '<div style="color: white;">' . $_SESSION['user_name'] .'</div>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=input"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Add Password</button></a>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Register new account</button></a>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Logout</button></a>';
        echo '</nav>';
        include('passwords.php');
    }
    private function showPageLoginForm()
    {
        if ($this->feedback) {
            echo $this->feedback;
        }
        echo '<head>';
        echo '<link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">';
        echo '<title>Login</title>';
        echo '<link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sign-in/">';
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
        echo '</head>';

        echo '<p style="text-align: center;font-size: 2em;font-weight: bold;color: #fff;background-color: #0069d9;">Password Manager</p>';
        echo '<div style="width: 25%; height: auto; margin: 2% 38%;">';
        echo '<form class="form-signin" method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
        echo '<h1 class="h3 mb-3 font-weight-normal">Login</h1>';
        echo '<label for="login_input_username" class="sr-only">Username (or email)</label> ';
        echo '<input type="text" id="login_input_username" class="form-control" placeholder="Username" name="user_name" required /> ';
        echo '<label for="login_input_password" class="sr-only">Password</label> ';
        echo '<input type="password" id="login_input_password" class="form-control" placeholder="Password" name="user_password" required /> ';
        echo '<input class="btn btn-lg btn-primary btn-block" type="submit" name="login" value="Log in" />';
        echo '</form>';
        echo '</div>';
    }
    private function showPageRegistration()
    {
        if ($this->feedback) {
            echo $this->feedback;
        }
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Register New User</title>';
        echo '<link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sign-in/">';
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
        echo '</head>';

        echo '<nav class="navbar navbar-dark bg-primary">';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Homepage</button></a>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Logout</button></a>';
        echo '</nav>';
        echo '<div style="width: 25%; height: auto; margin: 2% 38%;">';
        echo '<h2>Registration</h2>';
        echo '<form action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" method="post" class="form-signin" name="registerform">';
        echo '<p>';
        echo '<label for="login_input_username" class="sr-only">Username (only letters and numbers, 2 to 64 characters)</label>';
        echo '<input class="form-control" placeholder="Username" autocomplete="off" id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />';
        echo '</p>';
        echo '<p>';
        echo '<label for="login_input_email" class="sr-only">User\'s email</label>';
        echo '<input class="form-control" placeholder="User Email" autocomplete="off" id="login_input_email" type="email" name="user_email" required />';
        echo '</p>';
        echo '<p>';
        echo '<label for="login_input_password_new" class="sr-only">Password (min. 6 characters)</label>';
        echo '<input placeholder="Password" id="login_input_password_new" class="login_input form-control" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />';        
        echo '</p>';
        echo '<p>';
        echo '<label for="login_input_password_repeat" class="sr-only">Repeat password</label>';
        echo '<input placeholder="Password" id="login_input_password_repeat" class="login_input form-control" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />';
        echo '</p>';
        echo '<input type="submit" class="btn btn-lg btn-primary btn-block" name="register" value="Register" >';

        echo '</form>';
        echo '</div>';

        // echo '<h2>Registration</h2>';
        // echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        // echo '<label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label>';
        // echo '<input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />';
        // echo '<label for="login_input_email">User\'s email</label>';
        // echo '<input id="login_input_email" type="email" name="user_email" required />';
        // echo '<label for="login_input_password_new">Password (min. 6 characters)</label>';
        // echo '<input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />';
        // echo '<label for="login_input_password_repeat">Repeat password</label>';
        // echo '<input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />';
        // echo '<input type="submit" name="register" value="Register" />';
        // echo '</form>';
    }
    private function showPageAddPassword()
    {
        if ($this->feedback) {
            echo $this->feedback;
        }
        ob_end_clean();
        
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Add Password</title>';
        echo '<link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sign-in/">';
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
        echo '</head>';

        echo '<nav class="navbar navbar-dark bg-primary">';
        echo '<div style="color: white;">' . $_SESSION['user_name'] . '</div>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '><button type="button" class="btn btn-primary" style="border: solid black 1px;">Passwords</button></a>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout"><button type="button" class="btn btn-primary" style="border: solid black 1px;">Logout</button></a>';
        echo '</nav>';
        echo '<div style="width: 25%; height: auto; margin: 2% 38%;">';
        echo '<form action="insertData.php" method="post" class="form-signin">';
        echo '<p>';
        echo '<label for="name" class="sr-only">Name:</label>';
        echo '<input type="text" name="name" id="name" class="form-control" placeholder="Name" autocomplete="off">';
        echo '</p>';
        echo '<p>';
        echo '<label for="username" class="sr-only">Username:</label>';
        echo '<input type="text" name="user_name" id="username" class="form-control" placeholder="Username" autocomplete="off">';
        echo '</p>';
        echo '<p>';
        echo '<label for="password" class="sr-only">Password</label>';
        echo '<input type="text" name="pass_word" id="password" class="form-control" placeholder="Password" autocomplete="off">';
        echo '</p>';
        echo '<input type="submit" value="Submit" class="btn btn-lg btn-primary btn-block">';
        echo '</form>';
        echo '</div>';
    }
}
$application = new application();