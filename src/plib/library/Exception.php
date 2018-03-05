<?php
// Copyright 1999-2018. Plesk International GmbH.

class Modules_Route53_Exception extends pm_Exception
{
    public $awsCode = null;

    public function __construct($message, $command, $context = [], Exception $previous = null)
    {
        if (isset($context['code'])) {
            $this->awsCode = $context['code'];
        }
        if (isset($context['message'])) {
            $message = $context['message'];
        }
        parent::__construct($message, 0, $previous);
    }
}
