<?php

/**
 * Created by PhpStorm.
 * User: chenjian
 * Date: 15/4/2
 * Time: 10:29
 */
class ErrorController extends BaseController {
    public function errorAction(Exception $exception)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $this->jsonReturn($message, $code);
    }
}