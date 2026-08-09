<?php

namespace App\Exception;

use App\Enum\ErrorCode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(public readonly ErrorCode $errorCode, int $status = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($status, $errorCode->value);
    }
}