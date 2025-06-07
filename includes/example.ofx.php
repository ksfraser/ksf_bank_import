<?php

require_once (__DIR__ . '/vendor/autoload.php' );
//require_once ('vendor/autoload.php' );
//require_once ('./lib/OfxParser/Parser.php' );

//use OfxParser\Parser;
//use lib\OfxParser\Parser;


$ofxParser = new OfxParser\Parser();
//$ofxParser = new Parser();
//var_dump( $ofxParser );

$ofx = $ofxParser->loadFromFile('test.qfx');
//$ofx = $ofxParser->loadFromFile('../../example.ofx');

//var_dump( $ofx->signOn );
//var_dump( $ofx );

$institute =  $ofx->signOn->institute;
$bank = (string) $institute->name;
$bankid = (string) $institute->id;
//var_dump( $institute );
var_dump( $bank );
var_dump( $bankid );



$bankAccount = reset($ofx->bankAccounts);
//var_dump( $bankAccount );

// Get the statement start and end dates
$startDate = $bankAccount->statement->startDate;
$endDate = $bankAccount->statement->endDate;

// Get the statement transactions for the account
$transactions = $bankAccount->statement->transactions;

var_dump( $transactions );

