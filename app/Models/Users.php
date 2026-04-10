<?php

namespace App\Models;

use CodeIgniter\Model;

class Users extends Model {
	protected $table = 'users';
	protected $primaryKey = 'id';
	protected $useAutoIncrement = true;
	protected $allowedFields = [
		'name',
		'email',
		'username',
		'password',
		'password_reset_token',
		'password_reset_expires',
		'two_factor_enabled',
		'two_factor_code',
		'two_factor_expires',
		'two_factor_attempts',
		'two_factor_verified',
		'deleted_at',
	];
	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';
	protected $useSoftDeletes = true;
	protected $deletedField = 'deleted_at';
}