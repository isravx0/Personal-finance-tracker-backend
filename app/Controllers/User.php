<?php

namespace App\Controllers;

class User extends BaseController
{

	// construct function to load model
	public function __construct()
	{
		$this->Users = new \App\Models\Users();
	}

	public function login()
	{
		// define variables
		$data = [];
		$message = '';
		$status = false;


		// get data from request
		$data = $this->request->getJSON(true);

		// validate data
		if ($status = isset($data) && is_array($data) && count($data) > 0) {
			// validate data	
			$validation = \Config\Services::validation();
			$validation->setRules([
				'username' => 'required',
				'password' => 'required',
			]);

			if ($status = $validation->run($data)) {
				// check if user exists
				$user = $this->Users->where('username', $data['username'])->first();
				// check if user exists
				if ($status = isset($user) && is_array($user) && count($user) > 0) {
					// validate password
					if ($status = password_verify($data['password'], $user['password'])) {
						// save user data to session
						$session = \Config\Services::session();
						$session->set('user_id', $user['id']);
						$session->set('user_name', $user['name']);
						$session->set('user_email', $user['email']);
						$session->set('user_username', $user['username']);
						$message = 'User logged in successfully';
					} else {
						$message = 'Invalid password';
					}
				} else {
					$message = 'User not found';
				}

			} else {
				$message = $validation->getErrors();
			}
		} else {
			$message = 'Invalid data';
		}

		return $this->response->setJSON([
			'success' => $status,
			'message' => $message,
			'user' => $status ? [
				'id' => $user['id'],
				'name' => $user['name'],
				'email' => $user['email'],
				'username' => $user['username'],
			] : null,
		])->setStatusCode($status ? 200 : 400);
	}

	public function register()
	{
		// define variables
		$data = [];
		$status = false;
		$message = '';

		// get data from request
		$data = $this->request->getJSON(true);

		// check if data is valid
		if (isset($data) && is_array($data) && count($data) > 0) {
			// validate data
			$validation = \Config\Services::validation();
			$validation->setRules([
				'name' => 'required',
				'email' => 'required|valid_email',
				'username' => 'required',
				'password' => 'required|min_length[6]',
				'confirmPassword' => 'required|matches[password]',
			]);

			if ($status = $validation->run($data)) {
				// check if user email not already exists
				$user = $this->Users->where('email', $data['email'])->first();
				if (!$status = isset($user) && is_array($user) && count($user) > 0) {
					// save data to database
					$this->Users->save([
						'name' => $data['name'],
						'email' => $data['email'],
						'username' => $data['username'],
						'password' => password_hash($data['password'], PASSWORD_DEFAULT),
					]);
					$status = true;
					$message = 'User registered successfully';
				} else {
					$message = 'Email already exists';
					$status = false;
				}
			} else {
				$message = $validation->getErrors();
			}
		} else {
			$message = 'Invalid data';
		}

		return $this->response->setJSON([
			'success' => $status,
			'message' => $message,
		])->setStatusCode($status ? 201 : 400);
	}

	public function updateProfile()
	{
		$session = \Config\Services::session();
		$userId = $session->get('user_id');

		if (!$userId) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'User not logged in'
			])->setStatusCode(401);
		}

		$data = $this->request->getJSON(true);

		if (!$data || !is_array($data)) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Invalid data'
			])->setStatusCode(400);
		}

		$validation = \Config\Services::validation();

		$rules = [
			'name' => 'required',
			'username' => 'required',
			'email' => 'required|valid_email',
		];

		if (!empty($data['password'])) {
			$rules['password'] = 'required|min_length[6]';
			$rules['confirmPassword'] = 'required|matches[password]';
		}

		$validation->setRules($rules);

		if (!$validation->run($data)) {
			return $this->response->setJSON([
				'success' => false,
				'message' => $validation->getErrors()
			])->setStatusCode(400);
		}

		$updateData = [
			'name' => $data['name'],
			'username' => $data['username'],
			'email' => $data['email'],
		];

		if (!empty($data['password'])) {
			$updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}

		$updated = $this->Users->update($userId, $updateData);

		if (!$updated) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Failed to update profile'
			])->setStatusCode(400);
		}

		// refresh session data
		$updatedUser = $this->Users->find($userId);

		$session->set([
			'user_name' => $updatedUser['name'],
			'user_email' => $updatedUser['email'],
			'user_username' => $updatedUser['username'],
		]);

		return $this->response->setJSON([
			'success' => true,
			'message' => 'Profile updated successfully',
			'user' => [
				'id' => $updatedUser['id'],
				'name' => $updatedUser['name'],
				'email' => $updatedUser['email'],
				'username' => $updatedUser['username'],
			]
		])->setStatusCode(200);
	}

	public function logout()
	{
		// destroy session
		$session = \Config\Services::session();
		$session->destroy();

		return $this->response->setJSON([
			'success' => true,
			'message' => 'User logged out successfully',
		])->setStatusCode(200);

	}

	public function forgetPassword()
	{
		// define variables
		$data = [];
		$message = '';
		$status = false;

		// get data from request
		$data = $this->request->getJSON(true);

		// validate data
		if ($status = isset($data) && is_array($data) && count($data) > 0) {
			// validate data	
			$validation = \Config\Services::validation();
			$validation->setRules([
				'email' => 'required|valid_email',
			]);

			if ($status = $validation->run($data)) {
				// check if user exists
				$user = $this->Users->where('email', $data['email'])->first();
				// check if user exists
				if ($status = isset($user) && is_array($user) && count($user) > 0) {
					if ($status = $this->sendPasswordResetLink($data['email'])) {
						$message = 'Password reset link sent to email';
					} else {
						$message = 'Failed to send password reset link';
					}
				} else {
					$message = 'User not found';
				}

			} else {
				$message = $validation->getErrors();
			}
		} else {
			$message = 'Invalid data';
		}

		return $this->response->setJSON([
			'success' => $status,
			'message' => $message,
		])->setStatusCode($status ? 200 : 400);
	}

	// send password reset link to email
	private function sendPasswordResetLink($userEmail)
	{
		// define variables
		$status = false;

		if ($status = isset($userEmail) && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
			// generate password reset token
			$token = bin2hex(random_bytes(16));

			// save data to database
			$user = $this->Users->where('email', $userEmail)->first();

			if ($status = isset($user) && is_array($user) && count($user) > 0 && isset($user['id'])) {
				// update user with password reset token and expiration time (1 hour)
				$this->Users->update($user['id'], [
					'password_reset_token' => $token,
					'password_reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
				]);

				// set email config
				$email = \Config\Services::email();
				$email->setTo($userEmail);
				$email->setSubject('Password Reset Request');
				$email->setFrom('mintlypersonalfinancetracker@gmail.com', 'Personal Finance Tracker - Mintly Team');
				// create password reset link
				$resetLink = base_url('view/public/reset-password?token=' . $token);
				$emailContent = "<p>Dear user,</p>"
					. "<p>You have requested to reset your password. Please click the link below to reset your password:</p>"
					. "<p><a href='" . $resetLink . "'>Reset Password</a></p>"
					. "<p> Click the following link to reset your password: " . $resetLink . "</p>"
					. "<p>If you did not request this, please ignore this email.</p>"
					. "<p>Best regards,<br>Personal Finance Tracker - Mintly Team</p>";
				;
				$email->setMessage($emailContent);
				// send email
				$status = $email->send();
			}

			return $status;
		}
	}

	// reset password
	public function resetPassword()
	{
		// define variables
		$data = [];
		$message = '';
		$status = false;

		// get data from request
		$data = $this->request->getJSON(true);

		// validate data
		if ($status = isset($data) && is_array($data) && count($data) > 0) {
			// validate data	
			$validation = \Config\Services::validation();
			$validation->setRules([
				'token' => 'required',
				'password' => 'required|min_length[6]',
				'confirmPassword' => 'required|matches[password]',
			]);

			if ($status = $validation->run($data)) {
				// check if token is valid and not expired
				$user = $this->Users->where('password_reset_token', $data['token'])
					->where('password_reset_expires >=', date('Y-m-d H:i:s'))
					->first();

				if ($status = isset($user) && is_array($user) && count($user) > 0) {
					// update user's password
					$this->Users->update($user['id'], [
						'password' => password_hash($data['password'], PASSWORD_DEFAULT),
						'password_reset_token' => null,
						'password_reset_expires' => null,
					]);
					$message = 'Password reset successfully';
				} else {
					$message = 'Invalid or expired token';
				}

			} else {
				$message = $validation->getErrors();
			}
		} else {
			$message = 'Invalid data';
		}

		return $this->response->setJSON([
			'success' => $status,
			'message' => $message,
		])->setStatusCode($status ? 200 : 400);
	}

	public function session()
	{
		// check if user is logged in
		$session = \Config\Services::session();
		if ($session->get('user_id')) {
			return $this->response->setJSON([
				'success' => true,
				'message' => 'User is logged in',
				'user' => [
					'id' => $session->get('user_id'),
					'name' => $session->get('user_name'),
					'email' => $session->get('user_email'),
					'username' => $session->get('user_username'),
				],
			])->setStatusCode(200);
		} else {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'User is not logged in',
			])->setStatusCode(401);
		}
	}
}