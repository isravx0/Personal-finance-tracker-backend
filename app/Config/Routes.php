<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// CORS preflight
$routes->options('(:any)', static function () {
    return;
});

// frontend entry
$routes->get('/', 'View::index');
$routes->get('view/(:any)', 'View::index');
$routes->get('(:any)', 'View::index');