<?php
header('Access-Control-Allow-Origin: http://localhost:5173'); // Replace with your frontend origin
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Adjust allowed methods based on your API
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Adjust allowed headers if needed

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

Router::post('/register', function () {
    $data = request()->get([
        'username',
        'password',
        'firstname',
        'lastname',
        'yearlevel',
        'middlename',
    ]);
    echo Auth::register(
        $data['username'],
        $data['password'],
        $data['firstname'],
        $data['lastname'],
        $data['yearlevel'],
        $data['middlename'],
    );
});

Router::post('/login', function () {
    $username = request()->get('username');
    $password = request()->get('password');
    $userType = request()->get('usertype');

    echo Auth::login($username, $password, $userType);
});


Router::get('/logout', function () {
    echo 'Logout';
});

/* Research */

Router::get('/research/all', function () {
    echo Research::all();
});

Router::get('popularResearch', function () {
    echo Research::popularResearch();
});

Router::post('/research/create', function () {
    $data = request()->get([
        'id',
        'title',
        'year',
        'abstract',
        /* 'citations', */
        'authors',
        'keywords',
    ]);

    echo Research::create(
        $data['id'],
        $data['title'],
        2024,
        $data['abstract'],
        /* $data['citations'], */
        $data['authors'],
        $data['keywords'],
    );
});

Router::get('/research/read', function () {
    $id = request()->get('id');

    echo Research::read($id);
});

Router::post('/research/getResearch', function () {
    $userid = request()->get('userid');

    echo Research::getResearch($userid);
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

Router::post('/clerk/approveResearch', function () {
    $paperId = request()->get('paperId');
    $status = request()->get('status');

    echo Clerk::approveResearch($paperId, $status);
});

Router::get('/clerk/disapproveResearch', function () {
    $title = request()->get('title');

    echo Clerk::disapproveResearch($title);
});




/* Dispatch */
Router::run();
