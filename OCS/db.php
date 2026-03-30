<?php

$DB_HOST = 'localhost:4306';
$DB_NAME = 'canteen_db8';
$DB_USER = 'root';
$DB_PASS = '';

$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$connection) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}


