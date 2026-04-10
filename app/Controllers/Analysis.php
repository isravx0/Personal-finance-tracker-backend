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
		$userId = session()->get('id');
		$saveData = [
			'id' => isset($userId) ? $userId : null,
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
		$userId = $session->get('id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		$analysis = $this->Model
			->where('id', $userId)
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
		$userId = $session->get('id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		$analyses = $this->Model
			->where('id', $userId)
			->orderBy('created_at', 'DESC')
			->findAll();

		return $this->response->setJSON([
			'success' => true,
			'data' => $analyses
		]);
	}

	public function exportHistoryPdf() {
		$session = \Config\Services::session();
		$userId = $session->get('id');
		$userName = session()->get('name') ?? 'User';

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		// Fetch analysis history
		$analyses = $this->Model
			->where('id', $userId)
			->orderBy('created_at', 'DESC')
			->findAll();

		if (!$analyses) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'No analysis records found'
			])->setStatusCode(404);
		}

		// Create PDF using TCPDF
		require_once(ROOTPATH . 'vendor/autoload.php');
		
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(15, 15, 15);
		$pdf->SetAutoPageBreak(TRUE, 15);
		$pdf->AddPage();

		// Add title
		$pdf->SetFont('helvetica', 'B', 18);
		$pdf->SetTextColor(45, 138, 78);
		$pdf->Cell(0, 10, 'Financial Analysis History', 0, 1, 'C');

		// Add user info
		$pdf->SetFont('helvetica', '', 10);
		$pdf->SetTextColor(100, 100, 100);
		$pdf->Cell(0, 5, 'User: ' . htmlspecialchars($userName), 0, 1);
		$pdf->Cell(0, 5, 'Generated: ' . date('F d, Y H:i:s'), 0, 1);
		$pdf->Ln(5);

		// Table header
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->SetFillColor(45, 138, 78);
		$pdf->SetTextColor(255, 255, 255);
		
		$headers = ['Date', 'Income', 'Expenses', 'Savings', 'Target', 'Balance'];
		$w = [35, 25, 25, 25, 25, 30];
		
		foreach ($headers as $i => $header) {
			$pdf->Cell($w[$i], 7, $header, 1, 0, 'C', true);
		}
		$pdf->Ln();

		// Table data
		$pdf->SetFont('helvetica', '', 9);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFillColor(240, 240, 240);
		
		$fill = false;
		$totalIncome = 0;
		$totalExpenses = 0;
		$totalSavings = 0;

		foreach ($analyses as $analysis) {
			$totalIncome += $analysis['monthly_income'];
			$totalExpenses += $analysis['total_expenses'];
			$totalSavings += $analysis['actual_savings'];

			$pdf->Cell($w[0], 6, date('M d, Y', strtotime($analysis['created_at'])), 1, 0, 'C', $fill);
			$pdf->Cell($w[1], 6, '€' . number_format($analysis['monthly_income'], 2), 1, 0, 'R', $fill);
			$pdf->Cell($w[2], 6, '€' . number_format($analysis['total_expenses'], 2), 1, 0, 'R', $fill);
			$pdf->Cell($w[3], 6, '€' . number_format($analysis['actual_savings'], 2), 1, 0, 'R', $fill);
			$pdf->Cell($w[4], 6, '€' . number_format($analysis['target_savings'] ?? 0, 2), 1, 0, 'R', $fill);
			$pdf->Cell($w[5], 6, '€' . number_format($analysis['remaining_balance'], 2), 1, 0, 'R', $fill);
			$pdf->Ln();
			$fill = !$fill;
		}

		// Add summary
		$pdf->Ln(5);
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->SetTextColor(45, 138, 78);
		$pdf->Cell(0, 5, 'Summary', 0, 1);

		$pdf->SetFont('helvetica', '', 9);
		$pdf->SetTextColor(0, 0, 0);
		$totalRecords = count($analyses);
		$avgIncome = $totalRecords > 0 ? $totalIncome / $totalRecords : 0;
		$avgExpenses = $totalRecords > 0 ? $totalExpenses / $totalRecords : 0;
		$avgSavings = $totalRecords > 0 ? $totalSavings / $totalRecords : 0;

		$pdf->Cell(0, 5, 'Total Records: ' . $totalRecords, 0, 1);
		$pdf->Cell(0, 5, 'Average Monthly Income: €' . number_format($avgIncome, 2), 0, 1);
		$pdf->Cell(0, 5, 'Average Total Expenses: €' . number_format($avgExpenses, 2), 0, 1);
		$pdf->Cell(0, 5, 'Average Savings: €' . number_format($avgSavings, 2), 0, 1);

		// Output PDF
		$filename = 'analysis_history_' . date('Y-m-d_H-i-s') . '.pdf';
		$pdf->Output($filename, 'D'); // 'D' forces download
	}

	public function exportHistoryCsv() {
		$session = \Config\Services::session();
		$userId = $session->get('id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Not authenticated'
			])->setStatusCode(401);
		}

		// Fetch analysis history
		$analyses = $this->Model
			->where('id', $userId)
			->orderBy('created_at', 'DESC')
			->findAll();

		if (!$analyses) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'No analysis records found'
			])->setStatusCode(404);
		}

		// Create CSV with clean header format
		$csv = "Date;Monthly Income;Total Expenses;Actual Savings;Savings Target;Remaining Balance;Advice\n";

		foreach ($analyses as $analysis) {
			$date = date('d/m/Y', strtotime($analysis['created_at']));
			$income = number_format($analysis['monthly_income'], 2);
			$expenses = number_format($analysis['total_expenses'], 2);
			$savings = number_format($analysis['actual_savings'], 2);
			$target = number_format($analysis['target_savings'] ?? 0, 2);
			$remaining = number_format($analysis['remaining_balance'], 2);
			$advice = str_replace('"', '""', $analysis['advice']); // escape quotes
			$advice = '"' . $advice . '"';

			$csv .= $date . ';' . $income . ';' . $expenses . ';' . $savings . ';' . $target . ';' . $remaining . ';' . $advice . "\n";
		}

		return $this->response
			->setHeader('Content-Type', 'text/csv')
			->setHeader('Content-Disposition', 'attachment; filename="analysis_history.csv"')
			->setBody($csv);
	}
}