<?php

class Api
{
    public $apiName = '';
    public $requestUri = [];

    protected $method = ''; //GET|POST|PUT|DELETE
    protected $action = ''; //Название метод для выполнения

    public function __construct()
    {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        //Массив GET параметров разделенных слешем
        $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

        //Определение метода запроса
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }
    }

    public function run()
    {
        array_shift($this->requestUri);
        $requestUri = $this->requestUri;
        $type = array_shift($this->requestUri);

        if (isset(getallheaders()['Pass'])) $pass = trim(getallheaders()['Pass']);

        if (!$pass OR $pass != KEY) {
            header("HTTP/1.1 401 " . $this->requestStatus(401));
            throw new RuntimeException($this->requestStatus(401), 401);
        }

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/classes/' . $type . 'Api.php')) {
            header("HTTP/1.1 404 " . $this->requestStatus(404));
            throw new RuntimeException($this->requestStatus(404), 404);
        } else {
            $this->apiName = $type;
            require_once 'classes/' . $type . 'Api.php';
        }

        $class_name = $type . 'Api';

        $this->action = $class_name::getAction($requestUri);

        if (!$this->action) {
            header("HTTP/1.1 405 " . $this->requestStatus(405));
            throw new RuntimeException($this->requestStatus(405), 405);
        }

        $func = $class_name::{$this->action}();
        $this->response($func['data'], $func['status']);
    }

    protected function response($data, $status = 500)
    {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        echo json_encode($data);

        if (isset($conn)) $conn = null;
    }

    private function requestStatus($code)
    {
        $status = [
            200 => 'OK',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];
        return ($status[$code]) ? $status[$code] : $status[500];
    }
}