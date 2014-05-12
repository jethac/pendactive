<?php
require_once "../php/func.php";

/**
 *  Given an array of form
 *
 *  array(
 *      user_string => setup_task
 *  )
 *
 *  where   user_string     string describing the setup task, to be displayed to
 *                          the user
 *          setup_task      name of function to execute, of function signature *
 *                              function hurf(&$flag, &$str, &$odd)
 *
 *  executes the given setup tasks and prints associated markup.
 */
function do_setup_tasks($arr) {
    $idx = 0;
    foreach ($arr as $str => $func) {
        $ok = true;
        $infostr = "";
        $classstr = "even";
        if($idx++ % 2 == 1)
            $classstr = "odd";
        ?>
        <dt class="<?php echo $classstr; ?>"><?php echo $str; ?></dt>
        <dd class="flag"><?php
            $func($ok, $infostr);
        ?></dd>
        <?php
        if(!$ok) {
            ?>
            <dd class="dead"><pre><?php
            echo $infostr;
            ?></pre></dd>
            <?php
            die();
        }
    }
}

$dblink;
$dbsql;
function connect_to_db(&$flag, &$str) {
    global $dblink;

    $dblink = @mysql_connect(
        CONFIG_DB_HOSTNAME,
        CONFIG_DB_USER,
        CONFIG_DB_PASSWORD
    );
    show_result($dblink);

    if(!$dblink) {
        $flag = false;
        $str = mysql_error();
    } else {
        $flag = true;
        mysql_select_db(CONFIG_DB_NAME, $dblink);
    }
}

function create_tables(&$flag, &$str) {
    $dbsql = file_get_contents("db.sql");
    $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $dbsql);
    foreach ($queries as $query)
    {
        if (strlen(trim($query)) > 0)
        {
            $r = mysql_query($query);
            if (!$r) {
                $flag = false;
                $str = mysql_error();
                break;
            }
        }
    }
    show_result($r);
}

function create_default_users(&$flag, &$str) {
    $r = mysql_query("INSERT INTO users (id,name) VALUES (".NOTICE_ID.',"Notice")');
    show_result($r);
    if (!$r) {
        $flag = false;
        $str = mysql_error();
    }
}

function check_data_dir(&$flag, &$str) {
    $testfile = CONFIG_DATADIR . '/test';
    $handle = @fopen($testfile, "w");

    if (!$handle) {
        $flag = false;
        $str = $php_errormsg;
    }

    fclose($handle);
    $unlink = @unlink($testfile);
    if (!$unlink) {
        $flag = false;
        $str = $php_errormsg;
    }

    show_result($flag);
}

function create_avatar_dir(&$flag, &$str) {
    $r = @mkdir(CONFIG_DATADIR.'/avatars');
    show_result($r);
    if (!$r) {
        $flag = false;
        $str = $php_errormsg;
    }
}

function create_thumbs_dir(&$flag, &$str) {
    $r = mkdir(CONFIG_DATADIR.'/thumbs');
    show_result($r);
    if (!$r) {
        $flag = false;
        $str = $php_errormsg;
    }
}

function copy_default_avatars(&$flag, &$str) {
    $source = array(
        "../images/notice_avatar.jpg",
        "../images/anybody_avatar.jpg",
        "../images/default_avatar.jpg"
    );
    $dest = array(
        CONFIG_DATADIR . '/avatars/' . NOTICE_ID . '.jpg',
        CONFIG_DATADIR . '/avatars/' . ANYBODY_ID . '.jpg',
        CONFIG_DATADIR . '/avatars/' . DEFAULT_ID . '.jpg'
    );

    $r = false;
    for ($i = 0; $i < count($source); $i++) {
        $success = @copy($source[$i], $dest[$i]);
        $r |= $success;

        if(!$success) {
            $flag = false;
            $str = $php_errormsg;
        }
    }
    show_result($r);
}

function show_result($passed)
{
    $resultClass = "result";
    $resultText = "OK";


    if (!$passed)
    {
        $resultClass = "result result-failed";
        $resultText = "Failed";
    }
    ?>
    <span class="<?php echo $resultClass; ?>"><?php echo $resultText; ?></span>
    <?php
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta name="viewport" content="width=860">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="../css/setup.css" />
    <link rel="stylesheet" href="../css/font-awesome.css">
    <link href='http://fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
    <title>settings</title>
    <script type="text/javascript">

        // insert google analytics code here!

    </script>
</head>
<body class="setup">

    <h1 class="pagetitle">Pendactive Setup</h1>

    <dl class="initsteps">
        <?php
        do_setup_tasks(array(
            'Checking MYSQL connection...'  => 'connect_to_db',
            'Creating TABLES...'            => 'create_tables',
            'Creating default users...'     => 'create_default_users',
            'Checking data directory...'    => 'check_data_dir',
            'Creating avatars directory...' => 'create_avatar_dir',
            'Creating thumbs directory...'  => 'create_thumbs_dir',
            'Creating default avatars...'   => 'copy_default_avatars'
        ));
        ?>
        <dt class="success">SUCCESS!</dt>
        <?php
        mysql_close($dblink);
        ?>
    </dl>

</body>
</html>