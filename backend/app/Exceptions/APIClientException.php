<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class APIClientException extends Exception
{

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function render(Request $request): Response
    {
        return response(["message" => $this->message], 500);
    }
}
