<?php


class UserApi
{
    public static function getAction($uri)
    {
        array_shift($uri);
        $version = array_shift($uri);
        $method = array_shift($uri);
        $request_method = $_SERVER['REQUEST_METHOD'];
        switch ($version) {
            case 'v1':
                switch ($request_method) {
                    case 'GET':
                        switch ($method) {
                            case 'get':
                                return 'getUser';
                                break;
                        }
                        break;
                    case 'POST':
                        switch ($method) {
                            case 'login':
                                return 'userLogin';
                                break;
                            case 'registration':
                                return 'userRegistration';
                                break;
                        }
                        break;
                    default:
                        return false;
                }
                break;
        }
        return false;
    }

    public static function getUser()
    {
        $result = ['data' => ['result' => false], 'status' => 200];

        if (isset($_SESSION['TDL_USER']) AND (int)$_SESSION['TDL_USER'] > 0) {
            $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);
            $sql = "SELECT id,name FROM user WHERE id=:id";
            $st = $conn->prepare($sql);
            $st->bindValue(":id", (int)$_SESSION['TDL_USER'], PDO::PARAM_INT);
            $st->execute();

            if ($userData = $st->fetch(PDO::FETCH_ASSOC)) {
                $result['data']['result'] = true;
                $result['data']['user'] = $userData;
            }
        }

        return $result;
    }

    public static function userRegistration()
    {
        $result = ['data' => ['result' => false], 'status' => 200];
        if (isset($_POST['name'])) $name = Helper::testInput($_POST['name']);
        else $name = '';
        if (isset($_POST['login'])) $login = Helper::testInput($_POST['login']);
        else $login = '';
        if (isset($_POST['password'])) $password = Helper::testInput($_POST['password']);
        else $password = '';

        if (!$name OR !$login OR !$password) {
            $result['data']['error'] = 'Parameters required';
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);

        $sql = "SELECT id FROM user WHERE login=:login";
        $st = $conn->prepare($sql);
        $st->bindValue(":login", $login, PDO::PARAM_STR);
        $st->execute();

        if ($userData = $st->fetch(PDO::FETCH_ASSOC)) {
            $result['data']['error'] = 'Login is already in use';
        } else {
            $sql = "INSERT INTO user (`name`,`login`,`password`) VALUES (:name,:login,:password)";
            $st = $conn->prepare($sql);
            $st->bindValue(":name", $name, PDO::PARAM_STR);
            $st->bindValue(":login", $login, PDO::PARAM_STR);
            $st->bindValue(":password", md5($password), PDO::PARAM_STR);
            if ($st->execute()) $result['data']['result'] = true;
            else {
                $result['data']['error'] = 'Internal Server Error';
                $result['status'] = 500;
            }
        }

        return $result;
    }

    public static function userLogin()
    {
        $result = ['data' => ['result' => false], 'status' => 200];
        if (isset($_POST['login'])) $login = Helper::testInput($_POST['login']);
        else $login = '';
        if (isset($_POST['password'])) $password = Helper::testInput($_POST['password']);
        else $password = '';

        if (!$login OR !$password) {
            $result['data']['error'] = 'Parameters required';
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);

        $sql = "SELECT id,name FROM user WHERE login=:login AND password=:password";
        $st = $conn->prepare($sql);
        $st->bindValue(":login", $login, PDO::PARAM_STR);
        $st->bindValue(":password", md5($password), PDO::PARAM_STR);
        $st->execute();

        if ($userData = $st->fetch(PDO::FETCH_ASSOC)) {
            $result['data']['result'] = true;
            $result['data']['user'] = $userData;
            $_SESSION['TDL_USER'] = $userData['id'];
        } else {
            $result['data']['error'] = 'User or login are not correct';
        }

        return $result;
    }
}