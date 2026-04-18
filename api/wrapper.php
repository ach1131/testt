<?php
ini_set('display_errors', 1);
include_once '../../config.php';
header('Content-Type: application/json');

class Response
{
	public $error;
	public $access;

	function __construct($error, $access)
	{
		$this->error = $error;
		$this->access = $access;
	}
}

function echoResponse($response)
{
	echo json_encode($response);
}

$access = 0;

if (isset($_SESSION['userId'])) {
	$find = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
	if ($find) {
		if ($find->access >= 3) {
			$access = 1;
			if ($find->access >= 5) {
				$access = 2;
			}
			return echoResponse(new Response(false, $access));
		}
	}
}

echoResponse(new Response(true, "Недостаточно прав!"));
