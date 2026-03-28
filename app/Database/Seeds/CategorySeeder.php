<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'Salary', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
            ['name' => 'Bonus', 'type' => 'income'],

            ['name' => 'Rent', 'type' => 'expense'],
            ['name' => 'Food', 'type' => 'expense'],
            ['name' => 'Transport', 'type' => 'expense'],
            ['name' => 'Bills', 'type' => 'expense'],
            ['name' => 'Health', 'type' => 'expense'],
            ['name' => 'Entertainment', 'type' => 'expense'],
            ['name' => 'Shopping', 'type' => 'expense'],
        ];

        $this->db->table('categories')->insertBatch($data);
    }
}