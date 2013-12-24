<?php

use \CRUD\AJAX;


require_once 'boot.php';


switch ($_GET['op']) {
    case 'create': print AJAX::create(); break;
    case 'read'  : print AJAX::read()  ; break;
    case 'update': print AJAX::update(); break;
    case 'delete': print AJAX::delete(); break;
    case 'upload': print AJAX::upload(); break;
}
