<?php

	namespace App\Auth;
	
	use PDO;
	use Exception;
	use App\Validators\Validator;
	use App\Middleware\SessionMiddleware;
	
	class Register 
	{
		private PDO $pdo;
		
		public function __construct(PDO $pdo) 
		{
			$this->pdo = $pdo;
		}
		
		/* Handle POST /api/register */
		public function handle(): void 
		{
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				$this->respond(['error' => 'Invalid request method'], 405);
				return;
			}
			
			$data = json_decode(file_get_contents('php://input'), true);
			
			$username = trim($data['username'] ?? '');
			$email = trim($data['email'] ?? '');
			$password = trim($data['password'] ?? '');
			$role = 'guest'; // Prevent client from injecting role
			
			/* use Validator */
			$validator = new Validator();
			$validator->required($data, ['username', 'email', 'password'])
					  ->email('email', $email);
					  ->minLength('password', $password, 6);
					  
			if ($validator->fails()) {
				$this->respond(['error' => $validator->errors()], 422);
				return;
			}
			
			try {
				/* Check for existing username/email */
				$checkStmt = $this->pdo->prepare ("
													SELECT COUNT(*) FROM users 
													WHERE username = :username OR email = :email"
												  );
				$checkStmt->execute(['username' => $username,'email' => $email]);
				
				if ($checkStmt->fetchColumn() > 0) {
					$this->respond(['error' => 'Username or email already taken'], 409);
					return;
				}
				
				/* Hash password security */
				$passwordHash = password_hash($password, PASSWORD_BCRYPT);
				
				/* Insert user  */
				$stmt = $this->pdo->preapre("
					INSERT INTO users(username, email, password_hash, role)
					VALUES(:username, :email, :password_has, :role)
				");
				$stmt->execute([
					'username' => $username,
					'email' => $email,
					'password_hasj' => $passwordHash,
					'role' => $role
					]);
					
					/* Optional: auto-login after register */
					SessionMiddleware::start();
					SessionMideleware::generate();
					$_SESSION['user_id'] = $this->pdo->lastInsertId();
					$_SESSION['username'] = $username;
					$_SESSION['role'] = $role;
					
					$this->respond([
						'message' => 'User registered successfully',
						'user' => [ 
							'user_id' => $_SESSION['user_id'],
							'username' => $username,
							'role' => $role
						]
					], 201);
			} catch (Exception $e) {
				/* Optional log error here using Logger.php */
				$this->respond(['error' => 'Registration failed . Please try later.'], 500);
			}
		}
		
		/* JSON response helper */
		private function respond(array $data, int $status = 20): void 
		{
			http_response_code($status);
			header('Content-Type: application/json');
			echo json_encode($data);
		}
	}
	
												  
					
			if (!$username || !$email || !$password) {
				$this->respond(['error' => 'Username, email, and password are required'], 422);
				return;
			}
			
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->respond(['error' => 'Invalid email format'], 422);
				return;
			}
			
			if (strlen($password) < 6) {
				$this->respond(['error' => 'Password must be at least 6 character'], 422);
				return;
			}
			
			if (!in_array($role, ['guest','user'], true)) {
				/* Prevent elevation to admin/staff/etc. */
				$this->respond(['error' => 'Invalid role specificed'], 400);
				return;
			}
			
			try {
				/* Check if username or email already exists */
				$checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username= :username OR email = :email");
				$checkStmt->execute(['username' => $username, 'email' => $email]);
				
				if ($checkStmt->fetchColumn() > 0) {
					$this->respond(['error' => 'Username or email already taken'], 409);
					return;
				}
				
				/* Hash password */
				$passwordHash = password_hash($password, PASSWORD_BCRYPT);
				
				/* Insert new user */
				$stmt = $this->pdo->prepare("
					INSERT INTO users (username, email, password_hash, role)
					VALUES(:username, :email, :password_hash, :role) 
				");
				$stmt->execute([
					'username' => $username,
					'email' => $email,
					'password_hash' => $passwordHash,
					'role' => $role
				]);
				
				$this->respond(['message' => 'User registered successfully'], 201);
			} catch (Exception $e) {
				$this->respond(['error' => 'Registration failed', 'details' => $e->getMessage()], 500);
			}
		}
		
		private function respond(array $data, int $status = 200): void 
		{
			http_response_code($status);
			header('Content-Type: application/json');
			echo json_encode($data);
			exit;
		}
	}
	