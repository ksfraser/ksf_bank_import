<?php
/**
 * Bank Import Module Initialization
 *
 * @package Bank Import
 * @author Kevin Fraser
 * /

// Define security areas
if (!defined('SA_BANKFILEVIEW')) {
    define('SA_BANKFILEVIEW', 100);
}
if (!defined('SA_ADMINPARSERS')) {
    define('SA_ADMINPARSERS', 1051); // Unique value for AdminParsers permission
}
    /* */
?>
