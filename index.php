<?php
require __DIR__ . "/vendor/autoload.php";
require_once 'Research.php';
require_once 'Admin.php';
require_once 'Clerk.php';
require_once 'Student.php';
require_once 'Auth.php';

use Leaf\Router;
use Leaf\Http\Request;
use Leaf\Http\Response;
use MiladRahimi\PhpRouter\Routing\Route;

$response = new Leaf\Http\Response;

/* Auth */

Router::get('/register', function () {
    $data = request()->get([
        'username',
        'password',
        'firstName',
        'lastName',
        'yearLevel',
        'middleName',
    ]);
    echo Auth::register(
        $data['username'],
        $data['password'],
        $data['firstName'],
        $data['lastName'],
        $data['yearLevel'],
        $data['middleName'],
    );
});

Router::get('/login', function () {
    $username = request()->get('username');
    $password = request()->get('password');
    $userType = request()->get('userType');

    echo Auth::login($username, $password, $userType);
});


Router::get('/logout', function () {
    echo 'Logout';
});


/* Research */

Router::get('/research/all', function () {
    echo Research::all();
});

Router::get('/research/create', function () {
    $data = request()->get([
        'title',
        'year',
        'abstract',
        'citations',
        'authors',
        'keywords',
    ]);

    echo Research::create(
        $data['title'],
        $data['year'],
        $data['abstract'],
        $data['citations'],
        $data['authors'],
        $data['keywords'],
    );
});

Router::get('/research/read', function () {
    $id = request()->get('id');

    echo Research::read($id);
});


Router::get('/research/searchByTitle', function () {
    $query = request()->get('query');

    echo Research::searchByTitle($query);
});

Router::get('/research/searchByAuthor', function () {
    $query = request()->get('query');

    echo Research::searchByAuthor($query);
});

Router::get('/research/searchByYear', function () {
    $query = request()->get('query');

    echo Research::searchByYear($query);
});

Router::get('/research/searchByKeyword', function () {
    $query = request()->get('query');

    echo Research::searchByKeyword($query);
});

/* Admin */
Router::get('/admin/create', function () {
    $username = request()->get('username');
    $password = request()->get('password');

    echo Admin::create($username, $password);
});


/* Clerk */
Router::get('/clerk/create', function () {
    $firstName = request()->get('firstName');
    $lastName = request()->get('lastName');
    $username = request()->get('username');
    $password = request()->get('password');

    echo Clerk::create($firstName, $lastName, $username, $password);
});

Router::get('/clerk/approveStudentAccount', function () {
    $username = request()->get('username');

    echo Clerk::approveStudentAccount($username);
});


/* Dispatch */
Router::run();
