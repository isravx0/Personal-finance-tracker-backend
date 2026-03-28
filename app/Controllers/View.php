<?php

namespace App\Controllers;

class View extends BaseController {
    public function index() {
        return view('frontend/index.html');
    }
}