<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->options('(:any)', static function () {
    return;
});

$routes->get('/', 'View::index');
$routes->get('view/(:any)', 'View::index');