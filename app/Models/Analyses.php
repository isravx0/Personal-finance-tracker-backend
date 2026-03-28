<?php

namespace App\Models;

use CodeIgniter\Model;

class Analyses extends Model
{
    protected $table = 'financial_analysis';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'monthly_income',
        'rent',
        'food',
        'transport',
        'bills',
        'entertainment',
        'other_expenses',
        'target_savings',
        'total_expenses',
        'remaining_balance',
        'actual_savings',
        'advice'
    ];
}