<?php
include 'db.php';
$db = new db();

$host = 'localhost'; //host
$port = '9000'; //port
$null = NULL; //null var

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listning socket to the list
$clients = array($socket);

//start endless loop, so that our script doesn't stop
while (true) {
    //manage multipal connections
    $changed = $clients;
    //returns the socket resources in $changed array
    socket_select($changed, $null, $null, 0, 10);

    //check for new socket
    if (in_array($socket, $changed)) {
        $socketNew = socket_accept($socket); //accpet new socket
        $clients[] = $socketNew; //add socket to client array

        $header = socket_read($socketNew, 1024); //read data sent by the socket
        performHandshaking($header, $socketNew, $host, $port); //perform websocket handshake

        socket_getpeername($socketNew, $ip); //get ip address of connected socket
//        $response = mask(json_encode(array('type' => 'system', 'message' => $ip . ' connected'))); //prepare json data
//        sendMessage($response); //notify all users about new connection

        //make room for new socket
        $foundSocket = array_search($socket, $changed);
        unset($changed[$foundSocket]);
    }

    //loop through all connected sockets
    foreach ($changed as $changedSocket) {

        //check for any incomming data
        while (socket_recv($changedSocket, $buf, 1024, 0) >= 1) {
            $receivedText = unmask($buf); //unmask data
            $msg = json_decode($receivedText, true); //json decode

            if ($msg['type'] == 'newMessage') {
                $senderId = $msg['senderId']; //sender id
                $receiverId = $msg['receiverId']; //receiver id
                $userMessage = $msg['message']; //message text

                if ($senderId) {
                    if ($db->sendMessage($userMessage, $senderId, $receiverId)) {
                        $sender = $db->getUserById($senderId);
                        //prepare data to be sent to client
                        $responseText = mask(json_encode(array(
                            'type' => 'usermsg',
                            'sender' => $sender['username'],
                            'senderId' => $senderId,
                            'message' => $userMessage,
                            'receiverId' => $receiverId)));
                    } else {
                        $responseText = mask(json_encode(array(
                            'type' => 'system',
                            'message' => 'cannot send message!')));
                    }
                    sendMessage($responseText); //send data
                }
            } elseif ($msg['type'] == 'getMessage') {
                $sessionId = $msg['sessionId']; //sender id
                $senderId = $msg['senderId']; //sender id
                $receiverId = $msg['receiverId']; //receiver id
                $conversations = $db->getConversation($senderId, $receiverId);
                foreach ($conversations as $conversation) {
                    $responseText = mask(json_encode(array(
                        'type' => 'getMessage',
                        'sessionId' => $sessionId,
                        'senderId' => $conversation['sender_id'],
                        'sender' => $conversation['sender'],
                        'receiverId' => $conversation['receiver_id'],
                        'message' => $conversation['text'])));
                    sendMessage($responseText);
                }

            }
            break 2; //exist this loop
        }

//        $buf = @socket_read($changedSocket, 1024, PHP_NORMAL_READ);
//        if ($buf === false) { // check disconnected client
//            // remove client for $clients array
//            $foundSocket = array_search($changedSocket, $clients);
//            socket_getpeername($changedSocket, $ip);
//            unset($clients[$foundSocket]);
//
//            //notify all users about disconnected connection
////            $response = mask(json_encode(array('type' => 'system', 'message' => $ip . ' disconnected')));
////            sendMessage($response);
//        }
    }
}
// close the listening socket
socket_close($socket);

function sendMessage($msg)
{
    global $clients;
    foreach ($clients as $changedSocket) {
        @socket_write($changedSocket, $msg, strlen($msg));
    }
    return true;
}


//Unmask incoming framed message
function unmask($text)
{
    $length = ord($text[1]) & 127;
    if ($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

//Encode message for transfer to client.
function mask($text)
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } elseif ($length >= 65536) {
        $header = pack('CCNN', $b1, 127, $length);
    }
    return $header . $text;
}

//handshake new client.
function performHandshaking($receivedHeader, $clientConn, $host, $port)
{
    $headers = array();
    $lines = preg_split("/\r\n/", $receivedHeader);
    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    //hand shaking header
    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port/demo/shout.php\r\n" .
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($clientConn, $upgrade, strlen($upgrade));
}
