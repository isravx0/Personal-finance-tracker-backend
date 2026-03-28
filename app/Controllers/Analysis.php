<?php

namespace App\Controllers;

use App\Models\Analyses;

class Analysis extends BaseController
{

	public function __construct()
	{
		$this->Model = new Analyses();
	}

	public function analyze()
	{
		$data = $this->request->getJSON(true);
		$status = false;
		$message = '';

		if (!isset($data) || !is_array($data) || empty($data)) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Invalid data'
			])->setStatusCode(400);
		}

		$validation = \Config\Services::validation();
		$validation->setRules([
			'monthly_income' => 'required|decimal',
			'rent' => 'permit_empty|decimal',
			'food' => 'permit_empty|decimal',
			'transport' => 'permit_empty|decimal',
			'bills' => 'permit_empty|decimal',
			'entertainment' => 'permit_empty|decimal',
			'other_expenses' => 'permit_empty|decimal',
			'target_savings' => 'permit_empty|decimal',
		]);

		if (!$validation->run($data)) {
			return $this->response->setJSON([
				'success' => false,
				'message' => $validation->getErrors()
			])->setStatusCode(400);
		}

		$monthlyIncome = (float) ($data['monthly_income'] ?? 0);
		$rent = (float) ($data['rent'] ?? 0);
		$food = (float) ($data['food'] ?? 0);
		$transport = (float) ($data['transport'] ?? 0);
		$bills = (float) ($data['bills'] ?? 0);
		$entertainment = (float) ($data['entertainment'] ?? 0);
		$otherExpenses = (float) ($data['other_expenses'] ?? 0);
		$targetSavings = (float) ($data['target_savings'] ?? 0);

		$totalExpenses = $rent + $food + $transport + $bills + $entertainment + $otherExpenses;
		$remainingBalance = $monthlyIncome - $totalExpenses;
		$actualSavings = $remainingBalance > 0 ? $remainingBalance : 0;

		$advice = $this->generateAdvice($monthlyIncome, $totalExpenses, $actualSavings, $targetSavings);
		$userId = session()->get('user_id'); // Example of getting user ID from session
		$saveData = [
			'user_id' => isset($userId) ? $userId : null,
			'monthly_income' => isset($monthlyIncome) ? $monthlyIncome : null,
			'rent' => isset($rent) ? $rent : null,
			'food' => isset($food) ? $food : null,
			'transport' => isset($transport) ? $transport : null,
			'bills' => isset($bills) ? $bills : null,
			'entertainment' => isset($entertainment) ? $entertainment : null,
			'other_expenses' => isset($otherExpenses) ? $otherExpenses : null,
			'target_savings' => isset($targetSavings) ? $targetSavings : null,
			'total_expenses' => isset($totalExpenses) ? $totalExpenses : null,
			'remaining_balance' => isset($remainingBalance) ? $remainingBalance : null,
			'actual_savings' => isset($actualSavings) ? $actualSavings : null,
			'advice' => isset($advice) ? $advice : null,
		];

		$this->Model->insert($saveData);

		$status = true;
		$message = 'Analysis completed successfully';

		return $this->response->setJSON([
			'success' => $status,
			'message' => $message,
			'data' => $saveData
		])->setStatusCode(200);
	}

	private function generateAdvice($income, $expenses, $actualSavings, $targetSavings)
	{
		if ($income <= 0) {
			return 'Please enter a valid monthly income.';
		}

		if ($expenses > $income) {
			return 'Your expenses are higher than your income. Try reducing non-essential spending like entertainment or other expenses.';
		}

		if ($actualSavings >= $targetSavings && $targetSavings > 0) {
			return 'Great job! You are meeting your savings target.';
		}

		if ($actualSavings > 0 && $actualSavings < $targetSavings) {
			return 'You are saving money, but you are below your target. Try reducing some monthly expenses.';
		}

		if ($actualSavings == 0) {
			return 'You currently have no savings left after expenses. Review your budget carefully.';
		}

		return 'Your finances look stable. Keep tracking your income and expenses.';
	}

	public function latest() {
		$session = \Config\Services::session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		$analysis = $this->Model
			->where('user_id', $userId)
			->orderBy('created_at', 'DESC')
			->first();

		if (!$analysis) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'No analysis found'
			]);
		}

		return $this->response->setJSON([
			'success' => true,
			'data' => $analysis
		]);
	}

	public function history() {
		$session = \Config\Services::session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		$analyses = $this->Model
			->where('user_id', $userId)
			->orderBy('created_at', 'DESC')
			->findAll();

		return $this->response->setJSON([
			'success' => true,
			'data' => $analyses
		]);
	}
}