<?php

namespace App\Models;

use CodeIgniter\Model;

class Transactions extends Model {
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'user_id',
        'category_id',
        'type',
        'amount',
        'transaction_date',
        'note',
    ];
}