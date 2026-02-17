<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //

    protected function apiResponse($data, $status = 200)
    {
        return response()->json($data, $status);
    }

    protected function apiError($message, $status = 400)
    {
        return $this->apiResponse(['error' => $message], $status);
    }
}
