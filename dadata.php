<?php

class TooManyRequests extends Exception
{
}

class Dadata
{
    private $clean_url = "https://cleaner.dadata.ru/api/v1/clean";
    private $suggest_url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
    private $token;
    private $secret;
    private $handle;

    public function __construct($token, $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public function init()
    {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $this->token,
            "X-Secret: " . $this->secret,
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    public function clean($type, $value)
    {
        $url = $this->clean_url . "/$type";
        $fields = array($value);
        return $this->executeRequest($url, $fields);
    }

    public function suggest($type, $fields)
    {
        $url = $this->suggest_url . "/suggest/$type";
        return $this->executeRequest($url, $fields);
    }

    public function close()
    {
        curl_close($this->handle);
    }

    private function executeRequest($url, $fields)
    {
        curl_setopt($this->handle, CURLOPT_URL, $url);
        if ($fields != null) {
            curl_setopt($this->handle, CURLOPT_POST, 1);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        } else {
            curl_setopt($this->handle, CURLOPT_POST, 0);
        }
        $result = $this->exec();
        $result = json_decode($result, true);
        return $result;
    }

    private function exec()
    {
        $result = curl_exec($this->handle);
        $info = curl_getinfo($this->handle);
        if ($info['http_code'] == 429) {
            throw new TooManyRequests();
        } elseif ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}

$token = "6a92bb6a1ca5e0ee60f5e8fc443e0a03c0649980";
$secret = "a92d7c65c4bba9aff2843da2b3df6869e97d2bcd";

$dadata = new Dadata($token, $secret);
$dadata->init();

// Проверяем, есть ли данные в POST или GET запросе
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
    $user_second_name = isset($_POST['user_second_name']) ? $_POST['user_second_name'] : '';
    $user_last_name = isset($_POST['user_last_name']) ? $_POST['user_last_name'] : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_name = isset($_GET['user_name']) ? $_GET['user_name'] : '';
    $user_second_name = isset($_GET['user_second_name']) ? $_GET['user_second_name'] : '';
    $user_last_name = isset($_GET['user_last_name']) ? $_GET['user_last_name'] : '';
} else {
    // Обработка ошибки, если запрос не POST и не GET
}

$result = $dadata->clean("name", $user_name . " " . $user_second_name . " " . $user_last_name);

echo '<pre>';
print_r($result);
echo '</pre>';

$dadata->close();