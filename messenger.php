<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
}

include 'db.php';
$db = new db();
$users = $db->getUserList();
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="bootstrap.css" rel="stylesheet">
    <style type="text/css">
        .chat-wrapper {
            font: bold 11px/normal 'lucida grande', tahoma, verdana, arial, sans-serif;
            background: #efddb9;
            padding: 20px;
            margin: 20px auto;
            box-shadow: 2px 2px 2px 0px #00000017;
            max-width: 700px;
            min-width: 500px;
            border-radius: 15px;
        }

        .message-box {
            width: 97%;
            display: none;
            height: 300px;
            background: #e6eaef;
            /*box-shadow: inset 0px 0px 2px #00000017;*/
            overflow: auto;
            padding: 10px;
            border-radius: 5px;
            border: 2px solid #d6d6d6;
        }

        .user-panel {
            margin-top: 10px;
        }

        input[type=text] {
            border: none;
            padding: 5px 5px;
            box-shadow: 2px 2px 2px #0000001c;
        }

        input[type=text]#name {
            width: 20%;
        }

        input[type=text]#message {
            width: 60%;
        }

    </style>
</head>
<body>
<div class="col-lg-12">

    <div class="chat-wrapper">
        <div class="col-lg-12">
            Welcome <?= $_SESSION['username'] ?>!
            <div class="float-right"><a href="index.php?logout=1">logout</a></div>
        </div>
        <div class="form-row">
            <div class="form-inline">
                <label class="my-1 mr-2 ml-2" for="receiver">send message to:</label>
                <select class="custom-select my-1 mr-lg-5" name="receiver" id="receiver">
                    <?php foreach ($users as $key => $user): ?>
                        <?php if ($user != $_SESSION['username']): ?>
                            <option value="<?= $key ?>"><?= $user ?></option>
                        <?php endif ?>
                    <?php endforeach ?>

                </select>
                <div id="newMsgAlert" class="alert alert-success mx-sm-1 my-1" style="display: none"></div>
            </div>
        </div>

        <?php foreach ($users as $key => $user): ?>
            <?php if ($user != $_SESSION['username']): ?>
                <div class="message-box" id="message-box-<?= $key ?>"></div>
            <?php endif ?>
        <?php endforeach ?>

        <div class="user-panel">
            <input type="hidden" id="userId" value="<?= $_SESSION['userId'] ?>"/>
            <input type="hidden" id="sessionId" value="<?= session_id() ?>"/>
            <div class="form-inline">
                <input class="form-control col-lg-10 mx-sm-1" type="text" name="message" id="message"
                       placeholder="Type your message here..." maxlength="100"/>
                <button class="btn btn-success mx-sm-3" id="send-message">Send</button>
                <div id="msgAlert" class="alert alert-danger col-lg-10 mx-sm-1 mt-2" style="display: none">
                    Enter Some message Please!
                </div>
            </div>
        </div>
    </div>

    <script src="jquery.js"></script>
    <script language="javascript" type="text/javascript">
        var wsUri = "ws://localhost:9000/demo/server.php";
        //create a new WebSocket object.
        //    websocket = new WebSocket(wsUri);

        websocket = new WebSocket(wsUri);

        websocket.onopen = function (ev) { // connection is open
            get_message();
        };

        var sessionId = $('#sessionId').val(); //session id
        var user_id = $('#userId').val(); //user id
        var receiver = $('#receiver').val();
        var msgBox = $('#message-box-' + receiver + '');
        $('.message-box').hide();
        msgBox.show();
        var msgBc;
        //    prepareMsgBox();

        $('#receiver').on('change', function () {
            msgBox = $('#message-box-' + this.value + '');
            receiver = this.value;
            $('.message-box').hide();
            msgBox.show();
            msgBox.empty();
//        prepareMsgBox();
            get_message();
        });

        function prepareMsgBox() {
            websocket = new WebSocket(wsUri);
            $('.message-box').hide();
            msgBox.show();

            websocket.onopen = function (ev) { // connection is open
                get_message();
            };
        }

        // Message received from server
        websocket.onmessage = function (ev) {
            var response = JSON.parse(ev.data); //PHP sends Json data

            var res_type = response.type; //message type
            var user_message = response.message; //message text
//        var user_name = response.name; //user name
//        var user_color = response.color; //color

            switch (res_type) {
                case 'getMessage':
                    if (response.sessionId == sessionId) {
                        if ((response.senderId == user_id && response.receiverId == receiver) ||
                            (response.senderId == receiver && response.receiverId == user_id)) {
                            msgBc = '#f6f9dd';
                            if (response.receiverId == receiver) {
                                msgBc = '#8aff6a99';
                                msgBox.append('<div style="margin: 20px; text-align: right;"><span class="user_message" style="padding: 5px; border-radius: 5px;background-color: ' + msgBc + '">' + user_message + '</span></div>');
                            } else {
                                msgBox.append('<div style="margin: 20px"><span class="user_message" style="padding: 5px; border-radius: 5px;background-color: ' + msgBc + '">' + user_message + '</span></div>');
                            }
                        }
                    }
                    break;
                case 'usermsg':
                    if (response.receiverId == user_id && response.senderId != receiver) {
                        $('#newMsgAlert').html('new message from ' + response.sender + '!');
                        $('#newMsgAlert').show();
                        $("#newMsgAlert").fadeTo(2000, 500).slideUp(500, function () {
                            $("#newMsgAlert").slideUp(500);
                        });
                    }
                    if ((response.senderId == user_id && response.receiverId == receiver) ||
                        (response.senderId == receiver && response.receiverId == user_id)) {
                        msgBc = '#f6f9dd';
                        if (response.receiverId == receiver) {
                            msgBc = '#8aff6a99';
                            msgBox.append('<div style="margin: 20px; text-align: right;"><span class="user_message" style="padding: 5px; border-radius: 5px;background-color: ' + msgBc + '">' + user_message + '</span></div>');
                        } else {
                            msgBox.append('<div style="margin: 20px"><span class="user_message" style="padding: 5px; border-radius: 5px;background-color: ' + msgBc + '">' + user_message + '</span></div>');
                        }
                    }
                    break;
                case 'system':
                    msgBox.append('<div style="color:#bbbbbb">' + user_message + '</div>');
                    break;
            }
            msgBox[0].scrollTop = msgBox[0].scrollHeight; //scroll message

        };

        websocket.onerror = function (ev) {
            msgBox.append('<div class="system_error">Error Occurred - ' + ev.data + '</div>');
        };
        websocket.onclose = function (ev) {
//            msgBox.append('<div class="system_msg">Connection Closed</div>');
        };

        //Message send button
        $('#send-message').click(function () {
            send_message();
        });

        //User hits enter key
        $("#message").on("keydown", function (event) {
            if (event.which == 13) {
                send_message();
            }
        });

        //get message
        function get_message() {
            //prepare json data
            var msg = {
                type: 'getMessage',
                senderId: user_id,
                receiverId: receiver,
                sessionId: sessionId
            };
            //convert and send data to server
            websocket.send(JSON.stringify(msg));
        }

        //Send message
        function send_message() {
            var message_input = $('#message'); //user message text
            $('#msgAlert').hide();

            if (message_input.val() == "") { //emtpy message?
                $('#msgAlert').show();
                return;
            }

            //prepare json data
            var msg = {
                type: 'newMessage',
                message: message_input.val(),
                senderId: user_id,
                receiverId: receiver
            };
            //convert and send data to server
            websocket.send(JSON.stringify(msg));
            message_input.val(''); //reset message input
        }
    </script>
</div>
</body>
</html>
