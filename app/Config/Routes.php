<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// CORS preflight requests - must be first
$routes->options('(:any)', static function () {
    return;
});

// Apply CORS filter globally
$routes->setAutoRoute(true, ['filter' => 'cors']);

$routes->get('/', 'View::index');
$routes->get('/view/*', 'View::index');