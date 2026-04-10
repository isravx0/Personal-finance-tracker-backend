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

// Analysis routes
$routes->get('analysis/latest', 'Analysis::latest');
$routes->get('analysis/history', 'Analysis::history');
$routes->post('analysis/analyze', 'Analysis::analyze');
$routes->get('analysis/export-pdf', 'Analysis::exportHistoryPdf');
$routes->get('analysis/export-csv', 'Analysis::exportHistoryCsv');