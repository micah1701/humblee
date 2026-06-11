<?php
$_app_root = realpath(__DIR__ . '/../../..') . '/';
$_app_path = $_app_root . 'humblee/';
require_once $_app_path . 'vendor/autoload.php'; // to load composer files
require_once $_app_path . 'configuration/config.php';

ini_set('display_errors', 1);
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
        $_rdbms = $_ENV['config']['RDBMS'] ?? 'mysql';
        $_port = (is_int($_ENV['config']['db_port']) && $_ENV['config']['db_port'] > 0)
            ? $_ENV['config']['db_port']
            : ($_rdbms === 'pgsql' ? 5432 : 3306);

        if ($_rdbms === 'pgsql') {
            $pg_schema = (isset($_ENV['config']['db_schema']) && $_ENV['config']['db_schema'] !== '' && !is_null($_ENV['config']['db_schema']))
                ? $_ENV['config']['db_schema']
                : 'public';

            $connectionString = "host=" . $_ENV['config']['db_host']
                . " dbname=" . $_ENV['config']['db_name']
                . " port=" . $_port
                . " user=" . $_ENV['config']['db_username']
                . " password=" . $_ENV['config']['db_password'];
            $connection = pg_connect($connectionString) or die('Error connecting to PostgreSQL server.<br><br>Make sure you have the correct settings configured in <code>/core/config.php</code>.</body></html>');

            pg_query($connection, "SET search_path TO " . pg_escape_identifier($connection, $pg_schema)) or die('Error setting search_path: ' . pg_last_error($connection) . '</body></html>');
        } else {
            $connection = mysqli_connect(
                $_ENV['config']['db_host'],
                $_ENV['config']['db_username'],
                $_ENV['config']['db_password'],
                $_ENV['config']['db_name'],
                $_port
            ) or die('Error connecting to MySQL server: ' . mysqli_connect_error() . '<br><br>Make sure you have the correct settings configured in <code>/core/config.php</code>.</body></html>');
            mysqli_select_db($connection, $_ENV['config']['db_name']) or die('Error selecting MySQL database: ' . mysqli_error($connection) . '<br><br>Make sure you have created a database and configured the settings in <code>/core/config.php</code>.</body></html>');
        }

        // Check if a table already exists, if not, install the whole database
        if ($_rdbms === 'pgsql') {
            $result = pg_query_params(
                $connection,
                "SELECT 1 FROM information_schema.tables WHERE table_schema = $1 AND table_name = $2",
                [$pg_schema, _table_content_types]
            ) or die("error " . pg_last_error($connection));
            $table_exists = (pg_num_rows($result) !== 0);
        } else {
            $result = mysqli_query($connection, "SHOW TABLES LIKE '" . _table_content_types . "';")
                or die("error " . mysqli_error($connection));
            $table_exists = (mysqli_num_rows($result) !== 0);
        }

        if ($table_exists) {
            $database_created = "Already Exists";
        } else {
            if ($_rdbms === 'pgsql') {
                $sql = file_get_contents('database_pgsql.sql');
                pg_query($connection, $sql) or die("Error creating schema: " . pg_last_error($connection));
            } else {
                $sql = file_get_contents('database_mysql.sql');
                if (mysqli_multi_query($connection, $sql)) {
                    while (mysqli_next_result($connection)) {
                        if (!mysqli_more_results($connection)) {
                            break;
                        }
                    }
                }
            }
            $database_created = "Created";
        }

        // Check if a user exists, if not, add them
        if ($_rdbms === 'pgsql') {
            $result = pg_query($connection, "SELECT id FROM " . _table_users . " LIMIT 1")
                or die("Error " . pg_last_error($connection));
            $user_exists = (pg_num_rows($result) !== 0);
        } else {
            $result = mysqli_query($connection, "SELECT * FROM `" . _table_users . "` LIMIT 1")
                or die("Error " . mysqli_error($connection));
            $user_exists = (mysqli_num_rows($result) !== 0);
        }

        if ($user_exists) {
            $user_created = "A user already exists";
        } else {
            if (isset($_POST['email']) && trim($_POST['email']) != "" && trim($_POST['password']) != "" && trim($_POST['name']) != "") {
                require_once $_app_path . 'vendor/j4mie/idiorm/idiorm.php'; // idiorm class for database management

                if ($_rdbms === 'pgsql') {
                    $_pg_schema_dsn = (isset($_ENV['config']['db_schema']) && $_ENV['config']['db_schema'] !== '')
                        ? ";options='--search_path=" . $_ENV['config']['db_schema'] . "'"
                        : '';
                    ORM::configure('pgsql:host=' . $_ENV['config']['db_host'] . ';dbname=' . $_ENV['config']['db_name'] . $_pg_schema_dsn);
                } else {
                    ORM::configure('mysql:host=' . $_ENV['config']['db_host'] . ';dbname=' . $_ENV['config']['db_name']);
                }
                ORM::configure('username', $_ENV['config']['db_username']);
                ORM::configure('password', $_ENV['config']['db_password']);

                $usersObj = new \Humblee\Model\Users;
                $user_id = $usersObj->createUser($_POST['name'], $_POST['email'], $_POST['email'], $_POST['password']);

                if (!$user_id || !is_numeric($user_id)) {
                    echo htmlspecialchars((string)$user_id, ENT_QUOTES, 'UTF-8');
                    exit("EROR! could not create new user");
                }

                $newRoles = [1, 2, 9]; // assign master user to admin, content, and developer roles
                $roles_created = "(roles: ";
                foreach ($newRoles as $newRole) {
                    $role = ORM::for_table(_table_user_roles)->create();
                    $role->role_id = $newRole;
                    $role->user_id = $user_id;
                    $role->save();

                    $roles_created .= $newRole . ",";
                }
                $roles_created = rtrim($roles_created, ",") . ")";
                $user_created = "Created new ID: " . $user_id . " " . $roles_created;
            } else {
                $user_created = "None";
                $show_form = true;
            }
        }

        //generate the secret encrpytion key
        $encryption_key_generated = "";
        $keyfilename = $_app_root . $_ENV['config']['crypto_key'];

        if (!file_exists($keyfilename)) {
            $dirname = dirname($keyfilename);
            if (!is_dir($dirname)) {
                if (!mkdir($dirname, 0755, true)) {
                    $encryption_folder_generated = "Could not create directory (" . $dirname . ") for encryption key. Check folder permissions";
                } else {
                    $encryption_folder_generated = "Folder created";
                }
            } else {
                $encryption_folder_generated = "Folder exists";
            }

            $file_content = '<?php';
            $file_content .= "\n\n/**\n * THIS FILE WAS AUTO GENERATED AT THE TIME OF INSTALL\n *\n * DO NOT MODIFY THIS FILE!\n *\n";
            $file_content .= " * Do not store this file in a public repo.\n * Keep a secure backup — losing this file means losing access to all encrypted media.\n *\n */\n\n";
            $file_content .= 'return hex2bin(\'' . bin2hex(random_bytes(32)) . '\');';

            $handle = fopen($keyfilename, 'w') or die('Could not create encrpytion file at:  ' . $keyfilename);
            if (!fwrite($handle, $file_content)) {
                $encryption_key_generated = "Could not save generated encrpytion key. Check folder permissions";
            } else {
                $encryption_key_generated = "Key Generated";
            }
        } else {
            $encryption_folder_generated = "Folder Exists";
            $encryption_key_generated = "Key Exists";
        }

        echo "Database: " . htmlspecialchars($database_created, ENT_QUOTES, 'UTF-8');
        echo "<br>";
        echo "User: " . htmlspecialchars($user_created, ENT_QUOTES, 'UTF-8');
        echo "<br>";
        echo "Encryption Key Directory: " . htmlspecialchars($encryption_folder_generated, ENT_QUOTES, 'UTF-8');
        echo "<br>";
        echo "Encryption key: " . htmlspecialchars($encryption_key_generated, ENT_QUOTES, 'UTF-8');
        echo "<br>";

        if (isset($show_form) && $show_form !== false) {
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
        } else {
        ?>
            <br>
            <a href="../../admin">Log In</a>
        <?php
        }

        if (isset($_GET['go-nuke'])) {
            if ($_rdbms === 'pgsql') {
                pg_query($connection, 'TRUNCATE TABLE ' . _table_users . ' CASCADE')
                    or die("error blowing away users: " . pg_last_error($connection));
                pg_query($connection, 'TRUNCATE TABLE ' . _table_user_roles . ' CASCADE')
                    or die("error removing roles: " . pg_last_error($connection));
            } else {
                mysqli_query($connection, 'TRUNCATE TABLE `' . _table_users . '`;') or die("error blowing away users: " . mysqli_error($connection));
                mysqli_query($connection, 'TRUNCATE TABLE  `' . _table_user_roles . '`;') or die("error removing roles: " . mysqli_error($connection));
            }
            echo "boom!";
        }

        ?>

    </div>
</body>

</html>