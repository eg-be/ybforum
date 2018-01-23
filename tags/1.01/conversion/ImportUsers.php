<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// run with php -d zend.assertions=1

require_once 'DataImporter.php';

function PrintAssertOptions()
{
    echo 'Assert active: ' . assert_options(ASSERT_ACTIVE) . "\n";
    echo 'Assert warning: ' . assert_options(ASSERT_WARNING) . "\n";
    echo 'Assert bail: ' . assert_options(ASSERT_BAIL) . "\n";
    echo 'Assert quiet eval: ' . assert_options(ASSERT_QUIET_EVAL) . "\n";
    echo 'Assert callback: ' . assert_options(ASSERT_CALLBACK) . "\n";
}

function SetAssertOptions()
{
    assert_options(ASSERT_ACTIVE, true);
    assert_options(ASSERT_BAIL, true);
}

try
{
    SetAssertOptions();
    PrintAssertOptions();
    $imp = new DataImporter();
    $imp->ImportUsers();
    echo 'Done' . "\n";
}
catch(Exception $ex)
{
    echo 'Failed: ' . $ex->getMessage() . "\n";
}