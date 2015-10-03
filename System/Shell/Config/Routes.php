<?php
$routes = array();

$routes['help'] = 'make/help';
$routes['generatePhpDocs'] = 'phpDocs/generatePhpDocs';
$routes['generatePhpDocs/(:any)'] = 'phpDocs/generatePhpDocs/$1';