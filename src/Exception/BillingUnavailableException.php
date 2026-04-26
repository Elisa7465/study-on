<?php

namespace App\Exception;

class BillingUnavailableException extends \Exception
{
    public function __construct(string $message = 'Сервис временно недоступен.')
    {
        parent::__construct($message);
    }
}