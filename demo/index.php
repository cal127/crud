<?php

use CRUD\Page;
use CRUD\Model;


require_once 'boot.php';


$page = isset($_GET['page']) ? $_GET['page'] : null;

switch ($page) {
    case 'classes':
        $crud = Page::listall(
            Model::factory('Cls'),
            array('name', 'teacher.name'),
            'index.php?page=class&id='
        );

        break;

    case 'class':
        $crud = Page::detail(
            Model::factory('Cls'),
            $_GET['id'],
            array('name', 'teacher.name'),
            1,
            array(
                'students' => array(
                    array('name'),
                    '/index.php?page=student&id='
                )
            )
        );
        
        break;

    case 'teachers':
        $crud = Page::listall(
            Model::factory('Teacher'),
            array('name'),
            'index.php?page=teacher&id='
        );
        
        break;

    case 'teacher':
        $crud = Page::detail(
            Model::factory('Teacher'),
            $_GET['id'],
            array('name')
        );
        
        break;

    case 'student':
        $crud = Page::detail(
            Model::factory('Student'),
            $_GET['id'],
            array('name')
        );
        
        break;

    default:
        print '<h1>Some High School</h1>';
        print '<ul>';
        print '<li><a href="/index.php?page=classes">Classes</a></li>';
        print '<li><a href="/index.php?page=teachers">Teachers</a></li>';
        print '</ul>';
        exit;
}

require_once 'template.php';
