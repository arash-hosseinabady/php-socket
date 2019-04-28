<?php
include 'config.php';

class db
{
    public $con;
    function connect()
    {
        $this->con = mysqli_connect(HOSTNAME, DB_USER, DB_PASS, DB_NAME);
        if (mysqli_connect_errno()) {
            return false;
        }

        return true;
    }

    function disconnect($conn = null)
    {
        if ($conn) {
            mysqli_close($conn);
        } else {
            mysqli_close($this->con);
        }
    }

    function checkUsername($username) {
        try {
            if ($this->connect()) {
                $query = mysqli_query($this->con, "SELECT * FROM `user` WHERE BINARY username = '$username'");
                $result = mysqli_fetch_assoc($query);
                if ($result) {
                    $this->disconnect();
                    $this->updateSessionTime($result['id']);
                    return $result;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    function resetSessionTime($userId) {
        try {
            if ($this->connect()) {
                $query = mysqli_query($this->con, "UPDATE `user` SET session_time = '0' WHERE id = '$userId'");
                if ($query) {
                    $this->disconnect();
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    function updateSessionTime($userId) {
        try {
            if ($this->connect()) {
                $time = time();
                if (mysqli_query($this->con, "UPDATE `user` SET session_time = $time WHERE id = '$userId'")) {
                    $this->disconnect();
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    function sendMessage($text, $senderId, $receiverId) {
        if ($text && $senderId && $receiverId) {
            try {
                if ($this->connect()) {
                    $query = mysqli_query($this->con, "INSERT INTO messages(text, sender_id, receiver_id)" .
                        "VALUES ('$text', '$senderId', '$receiverId')");
                    if ($query) {
                        $this->disconnect();
                        $this->updateSessionTime($senderId);
                        return true;
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    function getUserList() {
        try {
            if ($this->connect()) {
                $query = mysqli_query($this->con, "SELECT id, username FROM user");
                $list = [];
                while ($row = mysqli_fetch_assoc($query)) {
                    $list[$row['id']] = $row['username'];
                }
                $this->disconnect();
                return $list;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    function getOnlineUser() {
        try {
            if ($this->connect()) {
                $time = time();
                $query = mysqli_query($this->con, "SELECT id, username FROM user WHERE $time - session_time <= 1440");
                $list = [];
                while ($row = mysqli_fetch_assoc($query)) {
                    $list[] = [
                        'id' => $row['id'],
                        'username' => $row['username']
                    ];
                }
                $this->disconnect();
                return $list;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    function getUserById($userId) {
        try {
            if ($this->connect()) {
                $query = mysqli_query($this->con, "SELECT id, username FROM user WHERE id = $userId");
                $result = mysqli_fetch_assoc($query);
                if ($result) {
                    $this->disconnect();
                    return $result;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    function getConversation($senderId, $receiverId) {
        $users = $this->getUserList();
        try {
            if ($this->connect()) {
                $query = mysqli_query($this->con, "SELECT * FROM messages " .
                    "WHERE (sender_id = $senderId AND receiver_id = $receiverId) OR (sender_id = $receiverId AND receiver_id = $senderId)");

                $conversations = [];
                while ($row = mysqli_fetch_assoc($query)) {
                    $conversations[] = [
                        'text' => $row['text'],
                        'sender' => $users[$row['sender_id']],
                        'sender_id' => $row['sender_id'],
                        'receiver' => $users[$row['receiver_id']],
                        'receiver_id' => $row['receiver_id'],
                    ];
                }

                $this->disconnect();
                return $conversations;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}