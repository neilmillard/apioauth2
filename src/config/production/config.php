<?php
return array(
    //apiKey can be found inside your account information
    //screen and requires a one time generation
    'apiKey'         => $_ENV['APIKEY'],
    //AccountId is the Id of one of your accounts
    //To later change this use OandaWrap::nav_account_set($accountId)
    'accountId'      => $_ENV['ACCOUNTID']

);

