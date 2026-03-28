<?php

namespace App\Controllers;

use App\Models\Transactions;
use App\Models\Categories;
use CodeIgniter\RESTful\ResourceController;

class Transaction extends ResourceController {
	public function categories() {
		$this->Categories = new Categories();
		$this->Model = new Transactions();
		return $this->respond($this->Categories->findAll());
	}

	public function index() {
		$session = session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->failUnauthorized('User not logged in');
		}


		$transactions = $this->Model
			->select('transactions.*, categories.name as category_name')
			->join('categories', 'categories.id = transactions.category_id')
			->where('transactions.user_id', $userId)
			->orderBy('transaction_date', 'DESC')
			->findAll();

		return $this->respond($transactions);
	}

	public function create() {
		$session = session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->failUnauthorized('User not logged in');
		}

		$rules = [
			'category_id'       => 'required|integer',
			'type'              => 'required|in_list[income,expense]',
			'amount'            => 'required|decimal',
			'transaction_date'  => 'required|valid_date',
			'note'              => 'permit_empty|string',
		];

		if (!$this->validate($rules)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		$data = [
			'user_id'          => $userId,
			'category_id'      => $this->request->getPost('category_id'),
			'type'             => $this->request->getPost('type'),
			'amount'           => $this->request->getPost('amount'),
			'transaction_date' => $this->request->getPost('transaction_date'),
			'note'             => $this->request->getPost('note'),
		];

		$this->Model->insert($data);

		return $this->respondCreated([
			'status'  => 'success',
			'message' => 'Transaction added successfully',
		]);
	}

	public function update($id = null) {
		$session = session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->failUnauthorized('User not logged in');
		}

		$transaction = $this->Model->find($id);

		if (!$transaction || $transaction['user_id'] != $userId) {
			return $this->failNotFound('Transaction not found');
		}

		$data = $this->request->getRawInput();

		$updateData = [
			'category_id'      => $data['category_id'] ?? $transaction['category_id'],
			'type'             => $data['type'] ?? $transaction['type'],
			'amount'           => $data['amount'] ?? $transaction['amount'],
			'transaction_date' => $data['transaction_date'] ?? $transaction['transaction_date'],
			'note'             => $data['note'] ?? $transaction['note'],
		];

		$this->Model->update($id, $updateData);

		return $this->respond([
			'status'  => 'success',
			'message' => 'Transaction updated successfully',
		]);
	}

	public function delete($id = null) {
		$session = session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->failUnauthorized('User not logged in');
		}

		$transaction = $this->Model->find($id);

		if (!$transaction || $transaction['user_id'] != $userId) {
			return $this->failNotFound('Transaction not found');
		}

		$this->Model->delete($id);

		return $this->respondDeleted([
			'status'  => 'success',
			'message' => 'Transaction deleted successfully',
		]);
	}
}