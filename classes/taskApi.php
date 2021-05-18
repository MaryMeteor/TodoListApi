<?php


class TaskApi
{
    public static function getAction($uri)
    {
        $type = array_shift($uri);
        $version = array_shift($uri);
        $method = array_shift($uri);
        $request_method = $_SERVER['REQUEST_METHOD'];
        switch ($version) {
            case 'v1':
                switch ($request_method) {
                    case 'POST':
                        switch ($method) {
                            case 'list':
                                return 'getTaskList';
                                break;
                            case 'add':
                                return 'addTask';
                                break;
                            case 'update':
                                return 'updateTask';
                                break;
                            case 'delete':
                                return 'deleteTask';
                                break;
                        }
                        break;
                    default:
                        return false;
                }
                break;
        }
    }

    public static function getTaskList()
    {
        if (isset($_SESSION['TDL_USER']) AND (int)$_SESSION['TDL_USER'] > 0) $result = ['data' => [], 'status' => 200];
        else {
            $result['data']['error'] = 'Unauthorized';
            $result['status'] = 401;
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);
        $sql = "SELECT * FROM task WHERE user=:user";
        if (isset($_POST['status']) AND ctype_digit($_POST['status'])) $sql .= " AND status=" . (int)$_POST['status'];
        if (isset($_POST['date_create']) AND Helper::validDate($_POST['date_create'])) $sql .= " AND date_create BETWEEN '" . $_POST['date_create'] . " 00:00:00' AND '" . $_POST['date_create'] . " 23:59:59'";
        $sql .= " ORDER BY id DESC";
        $st = $conn->prepare($sql);
        $st->bindValue(":user", (int)$_SESSION['TDL_USER'], PDO::PARAM_INT);
        if ($st->execute()) {
            $result['data']['result'] = true;
            $result['data']['list'] = [];
            while ($taskData = $st->fetch(PDO::FETCH_ASSOC)) {
                $result['data']['list'][] = $taskData;
            }
        } else {
            $result['data']['error'] = 'Internal Server Error';
            $result['status'] = 500;
        }

        return $result;
    }

    public static function addTask()
    {
        if (isset($_SESSION['TDL_USER']) AND (int)$_SESSION['TDL_USER'] > 0) $result = ['data' => [], 'status' => 200];
        else {
            $result['data']['error'] = 'Unauthorized';
            $result['status'] = 401;
            return $result;
        }

        if (isset($_POST['name'])) $name = Helper::testInput($_POST['name']);
        else $name = '';

        if (!$name) {
            $result['data']['error'] = 'Parameters required';
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);

        $sql = "INSERT INTO task (`name`,`user`) VALUES (:name,:user)";
        $st = $conn->prepare($sql);
        $st->bindValue(":name", $name, PDO::PARAM_STR);
        $st->bindValue(":user", (int)$_SESSION['TDL_USER'], PDO::PARAM_INT);
        if ($st->execute()) {
            $result['data']['result'] = true;
            $result['data']['taskID'] = $conn->lastInsertId();
        } else {
            $result['data']['error'] = 'Internal Server Error';
            $result['status'] = 500;
        }

        return $result;
    }

    public static function updateTask()
    {
        if (isset($_SESSION['TDL_USER']) AND (int)$_SESSION['TDL_USER'] > 0) $result = ['data' => [], 'status' => 200];
        else {
            $result['data']['error'] = 'Unauthorized';
            $result['status'] = 401;
            return $result;
        }

        if (isset($_POST['id'])) $id = (int)Helper::testInput($_POST['id']);
        else $id = 0;
        if (isset($_POST['name'])) $name = Helper::testInput($_POST['name']);
        else $name = '';
        if (isset($_POST['status'])) $status = (int)Helper::testInput($_POST['status']);
        else $status = false;

        if (!$id OR (!$name AND !$status)) {
            $result['data']['error'] = 'Parameters required';
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);

        $sql = "UPDATE task SET ";
        $update_ar = [];
        if ($name) $update_ar[] = "name=:name";
        if ($status !== false) $update_ar[] = "status=:status";
        $sql .= implode(',', $update_ar) . " WHERE id=:id AND user=:user";
        $st = $conn->prepare($sql);
        if ($name) $st->bindValue(":name", $name, PDO::PARAM_STR);
        if ($status !== false) $st->bindValue(":status", $status, PDO::PARAM_INT);
        $st->bindValue(":user", (int)$_SESSION['TDL_USER'], PDO::PARAM_INT);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        if ($st->execute()) {
            $result['data']['result'] = true;
        } else {
            $result['data']['error'] = 'Internal Server Error';
            $result['status'] = 500;
        }

        return $result;
    }

    public static function deleteTask()
    {
        if (isset($_SESSION['TDL_USER']) AND (int)$_SESSION['TDL_USER'] > 0) $result = ['data' => [], 'status' => 200];
        else {
            $result['data']['error'] = 'Unauthorized';
            $result['status'] = 401;
            return $result;
        }

        if (isset($_POST['id'])) $id = (int)Helper::testInput($_POST['id']);
        else $id = 0;

        if (!$id) {
            $result['data']['error'] = 'Parameters required';
            return $result;
        }

        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_LOGIN, DB_PASS);

        $sql = "DELETE FROM task WHERE id=:id AND user=:user";
        $st = $conn->prepare($sql);
        $st->bindValue(":user", (int)$_SESSION['TDL_USER'], PDO::PARAM_INT);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        if ($st->execute()) {
            $result['data']['result'] = true;
        } else {
            $result['data']['error'] = 'Internal Server Error';
            $result['status'] = 500;
        }

        return $result;
    }
}