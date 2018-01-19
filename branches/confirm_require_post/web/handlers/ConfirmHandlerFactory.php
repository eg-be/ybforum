<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/ConfirmUserHandler.php';
require_once __DIR__.'/ConfirmUpdateEmailHandler.php';

/**
 * Description of ConfirmHandlerFactory
 *
 * @author eli
 */
class ConfirmHandlerFactory 
{
    public static function CreateHandler()
    {
        $type = filter_input(INPUT_GET, Mailer::PARAM_TYPE, FILTER_UNSAFE_RAW);
        
        if($type === Mailer::VALUE_TYPE_CONFIRM_USER)
        {
            return new ConfirmUserHandler();
        }
        else if($type === Mailer::VALUE_TYPE_UPDATEEMAIL)
        {
            return new ConfirmUpdateEmailHandler();
        }
        else
        {
            throw new InvalidArgumentException('Invalid type', 400);
        }
    }
}
