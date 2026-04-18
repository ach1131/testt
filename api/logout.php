<?php
class Response
{
    public $error;
    public $message;

    function __construct($error, $message)
    {
        $this->error = $error;
        $this->message = $message;
    }
}

function echoResponse($response)
{
    echo json_encode($response);
}
require_once '../config.php';

session_unset();
header('Content-Type: application/json');

echoResponse(new Response(false, "Успешный выход!"));
redirectTo('login.php');
