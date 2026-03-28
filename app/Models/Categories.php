<?php

namespace App\Models;

use CodeIgniter\Model;

class Categories extends Model {
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = ['name', 'type'];
}