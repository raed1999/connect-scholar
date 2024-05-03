<?php
require __DIR__ . "/vendor/autoload.php";
require_once 'Research.php';
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
    echo Auth::login($username, $password);
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
    ]);

    echo Research::create(
        $data['title'],
        $data['year'],
        $data['abstract'],
        $data['citations'],
        $data['authors'],
    );
});

Router::get('/research/read', function(){
    $id = request()->get('id');

    echo Research::read($id);
});


/* Dispatch */
Router::run();




/* Student::create("John", "Doe", "2");
Student::create("James", "Bond", "2"); */

/* Research::create(
    "The title of defense", 
    "2023", 
    "Sample Abstract",
    [],
    ["7","23"]
); */

/* Research::create(
    "Teaching the code to code.", 
    "2022", 
    "Sample Abstract",
    ["Algorithms and Complexity of things..."],
    [7,23]
);
 */

 /* Authentication */
/*  Auth::register('sample@gmail.com','password123','Ralfh Edwin', 'Panti', 2); */
/*  Auth::login('sample@gmail.com','password123'); */
