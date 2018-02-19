<?php
    $_app_root = realpath(__DIR__ . '/../../..') . '/';
    $_app_path = $_app_root.'humblee/';
    require_once $_app_path.'vendor/autoload.php'; // to load composer files
    require_once $_app_path.'configuration/config.php';

    ini_set('display_errors',1);
    error_reporting(E_ALL);
?>
<html>
<head>
<meta charset="utf-8">
<title>Install Humblee Framework and CMS</title>

<link rel="stylesheet" type="text/css" href="../../node_modules/bulma/css/bulma.css">
</head>
<body>
<div class="container">

    <h1 class="title">Install Humblee Database</h1>
    <?php
       // Contect to database
        $connection = mysqli_connect($_ENV['config']['db_host'], $_ENV['config']['db_username'], $_ENV['config']['db_password']) or die('Error connecting to MySQL server: ' . mysqli_connect_error() .'<br><br>Make sure you have the correct settings configured in <code>/core/config.php</code>.</body></html>');

        // Select database
        mysqli_select_db($connection, $_ENV['config']['db_name']) or die('Error selecting MySQL database: ' . mysqli_error($connection) .'<br><br>Make sure you have created a database and configured the settings in <code>/core/config.php</code>.</body></html>');

        // Check if a table already exists, if not, install the whole database
        $result = mysqli_query($connection,'SHOW TABLES LIKE \''. _table_content_types.'\';') or die("error ". mysqli_error($connection));

        if( mysqli_num_rows($result) !== 0)
        {
            $database_created = "Already Exists";
        }
        else
        {
            $sql = file_get_contents('database.txt');

            if (mysqli_multi_query($connection,$sql))
            {
                while ($sql->next_result()) {;} // flush multi_queries
            }
            else
            {
                throw new Exception (mysqli_error);
            }
            $database_created = "Created";
        }

        // Check if a user exists, if not, add them
        $result = mysqli_query($connection,"SELECT * FROM `" . _table_users . "` LIMIT 1") or die("Error ". mysqli_error($connection));
        if( mysqli_num_rows($result) !==  0)
        {
            $user_created = "A user already exists";
        }
        else
        {
            if(isset($_POST['email']) && trim($_POST['email']) != "" && trim($_POST['password']) != "" && trim($_POST['name']) != "" )
            {
                require_once $_app_path.'models/users.php';
                require_once $_app_path.'vendor/j4mie/idiorm/idiorm.php'; // idiorm class for database management

                ORM::configure('mysql:host='. $_ENV['config']['db_host'] .';dbname=' .$_ENV['config']['db_name']);
                ORM::configure('username', $_ENV['config']['db_username']);
                ORM::configure('password', $_ENV['config']['db_password']);

                $usersObj = new Core_Model_Users;
                $user_id = $usersObj->createUser($_POST['name'],$_POST['email'],$_POST['email'],$_POST['password']);

                if(!$user_id || !is_numeric($user_id))
                {
                    echo $user_id;
                    exit("EROR! could not create new user");
                }

                $newRoles = array(1,2,9);
                $roles_created = "(roles: ";
                foreach($newRoles as $newRole)
                {
                    $role = ORM::for_table( _table_user_roles)->create();
					$role->role_id = $newRole;
					$role->user_id = $user_id;
					$role->save();

					$roles_created .= $newRole .",";
                }
                $roles_created = rtrim($roles_created,",") .")";
                $user_created = "Created new ID: ". $user_id . " ". $roles_created;
            }
            else
            {
                $user_created = "None";
                $show_form = true;
            }
        }

        //generate the secret encrpytion key
        $encryption_key_generated = "";
        $keyfilename = $_app_root . $_ENV['config']['crypto_key'];

        if(!file_exists($keyfilename))
        {
            $dirname = dirname($keyfilename);
            if (!is_dir($dirname))
            {
                if(!mkdir($dirname, 0755, true))
                {
                	$encryption_folder_generated = "Could not create directory (". $dirname .") for encryption key. Check folder permissions";
                }
                else
                {
                    $encryption_folder_generated = "Folder created";
                }
            }
            else
            {
                $encryption_folder_generated = "Folder exists";
            }

			$file_content = '<?php defined(\'include_only\') or die(\'No direct script access.\');';
			$file_content.= "\n\n /**\n * THIS FILE WAS AUTO GENERATED AT THE TIME OF INSTALL\n *\n * DO NOT MODIFY THIS FILE!\n *\n";
			$file_content.= " * Do not store this file in a public repo. \n * You may want to create a backup of this file and store it in a safe place.\n *\n */\n\n";
			$file_content.= '$_encryption_key = "'. random_bytes(32).'";';

			$handle = fopen($keyfilename, 'w') or die('Could not create encrpytion file at:  '.$keyfilename);
			if(!fwrite($handle, $file_content))
			{
				$encryption_key_generated = "Could not save generated encrpytion key. Check folder permissions";
			}
			else
			{
				$encryption_key_generated = "Key Generated";
			}
		}
		else
		{
			$encryption_folder_generated = "Folder Exists";
			$encryption_key_generated = "Key Exists";
		}

        echo "Database: ".$database_created;
        echo "<br>";
        echo "User: ".$user_created;
        echo "<br>";
        echo "Encryption Key Directory: ". $encryption_folder_generated;
        echo "<br>";
        echo "Encryption key: ".$encryption_key_generated;
        echo "<br>";

        if(isset($show_form) && $show_form !== false)
        {
    ?>
    <hr>
    <form action="" method="post">
        <h3>Create a master user for your site</h3>
			<div class="field">
				<label for="name">Full Name:</label>
				<input type="text" class="input" id="name" name="name" placeholder="John Smith">
			</div>
			<div class="field">
				<label for="email">Email Address:</label>
                <input type="email" class="input" id="email" name="email" placeholder="your.email@valid-domain.com">
			</div>
			<div class="field">
				<label for="password">Password:</label>
                <input type="text" class="input" id="password" name="password" placeholder="notPassword123" id="passwrod">
			</div>
        <br>
        <input class="button is-primary" type="submit" name="submit" value="Create Master User">
    </form>
    <?php
        }
        else
        {
    ?>
        <br>
        <a href="../../admin">Log In</a>
    <?php
        }

    if(isset($_GET['go-nuke']))
    {
       mysqli_query($connection,'TRUNCATE TABLE `'. _table_users.'`;') or die("error blowing away users: ". mysqli_error($connection));
       mysqli_query($connection,'TRUNCATE TABLE  `'. _table_user_roles.'`;') or die("error removing roles: ". mysqli_error($connection));
       echo "boom!";
    }

    ?>

</div>
</body>
</html>