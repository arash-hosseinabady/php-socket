<?php
include 'db.php';
$db = new db();
session_start();

if (isset($_GET['logout']) and $_GET['logout'] == 1) {
    if ($db->resetSessionTime($_SESSION['userId'])) {
        session_destroy();
        header('Location: /');
    }
} elseif (isset($_SESSION['userId'])) {
    $db->updateSessionTime($_SESSION['userId']);
    header('Location: messenger.php');
} elseif ($_POST) {
    $data = $_POST;
    if ($user = $db->checkUsername($data['username'])) {
        $_SESSION['userId'] = $user['id'];
        $_SESSION['username'] = $data['username'];
        header('Location: messenger.php');
    }
} elseif (isset($_GET['api']) and $_GET['api'] == 'getUser') {
    print_r(json_encode($db->getOnlineUser()));
    return;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="bootstrap.css" rel="stylesheet">
    <style type="text/css">
        input[type=text] {
            border: none;
            padding: 5px 5px;
            box-shadow: 2px 2px 2px #0000001c;
        }
    </style>
</head>
<body>

<div class="row">
    <div class="col-lg-3"></div>
    <div class="card text-center col-lg-6 mt-5">
        <div class="card-header">
            Login to messenger
        </div>
        <div class="card-body">
            <form action="" method="post" class="">
                <input class="col-lg-4 mr-2" type="text" name="username" id="username" placeholder="Your Username" maxlength="20"/>
                <button class="btn btn-success" id="login">Login</button>
                <div class="alert alert-danger mt-2" style="display: none">Please enter your username!</div>
            </form>
        </div>
    </div>
    <div class="col-lg-3"></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script language="javascript" type="text/javascript">
    $('#login').on('click', function (e) {
        var username = $('#username').val();
        if (username == "") {
            $('.alert').show();
            e.preventDefault();
        }
    });
</script>
</body>
</html>
