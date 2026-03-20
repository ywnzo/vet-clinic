<?php
declare(strict_types=1);
namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AuthService;
use App\ORM\ORM;
use App\Exception\ValidationException;
use App\Exception\UnauthorizedException;
use PDO;

class AuthServiceTest extends TestCase {
    private PDO $pdo;
    private AuthService $service;

    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema($this->pdo);
        ORM::setPDO($this->pdo);
        $this->service = new AuthService();
    }

    private function createSchema(PDO $pdo): void {
        $pdo->exec(<<<'SQL'
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                surname TEXT,
                email TEXT UNIQUE,
                password TEXT,
                address TEXT,
                role TEXT DEFAULT 'user',
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );
            SQL
        );

        $pdo->exec(<<<'SQL'
            CREATE TABLE refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            SQL
        );
    }

    private function queryUserByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function queryRefreshToken(string $token): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE token = :token');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function testRegisterLoginRefreshLogoutFlow(): void {
        $data = [
            'name' => 'Test',
            'surname' => 'Testington',
            'email' => 'test@example.com',
            'password' => 'password',
            'address' => '123 Test Street'
        ];

        $regResult = $this->service->register($data);
        $this->assertIsArray($regResult);
        $this->assertArrayHasKey('user', $regResult);
        $this->assertArrayHasKey('access_token', $regResult);
        $this->assertArrayHasKey('refresh_token', $regResult);

        $user = $this->queryUserByEmail($data['email']);
        $this->assertNotNull($user);
        $this->assertTrue(password_verify($data['password'], $user['password']));

        $loginResult = $this->service->login([
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        $this->assertIsArray($loginResult);
        $this->assertArrayHasKey('user', $loginResult);
        $this->assertArrayHasKey('access_token', $loginResult);
        $this->assertArrayHasKey('refresh_token', $loginResult);
        $this->assertEquals($data['email'], $loginResult['user']['email']);

        $oldRefresh = $loginResult['refresh_token'];
        $this->assertNotEmpty($oldRefresh);
        $row = $this->queryRefreshToken($oldRefresh);
        $this->assertNotNull($row);

        $refreshResult = $this->service->refresh(['refresh_token' => $oldRefresh]);
        $this->assertIsArray($refreshResult);
        $this->assertArrayHasKey('access_token', $refreshResult);
        $this->assertArrayHasKey('refresh_token', $refreshResult);

        $oldRow = $this->queryRefreshToken($oldRefresh);
        $this->assertNull($oldRow, 'Old refresh token should be removed after refresh');

        $newRefresh = $refreshResult['refresh_token'];
        $this->assertNotEquals($oldRefresh, $newRefresh, 'New refresh token should be different from old one');
        $newRow = $this->queryRefreshToken($newRefresh);
        $this->assertNotNull($newRow, 'New refresh token should exist after refresh');

        $this->service->logout($newRefresh);
        $afterLogoutRow = $this->queryRefreshToken($newRefresh);
        $this->assertNull($afterLogoutRow, 'Refresh token should be removed after logout');
    }

    public function testRegisterFailsWithDuplicateEmail(): void {
        $data = [
            'name' => 'Test',
            'surname' => 'Testington',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $this->service->register($data);
        $this->expectException(ValidationException::class);
        $this->service->register($data);
    }

    public function testLoginFailsWithInvalidCredentials(): void {
        $data = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];
        $this->expectException(UnauthorizedException::class);
        $this->service->login($data);
    }

    public function testValidateAccessTokenDecodesToken(): void {
        $data = [
            'name' => 'Test',
            'surname' => 'Testington',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $result = $this->service->register($data);
        $accessToken = $result['access_token'];

        $claims = $this->service->validateAccessToken($accessToken);
        $this->assertIsArray($claims);
        $this->assertArrayHasKey('sub', $claims);
        $this->assertArrayHasKey('email', $claims);
        $this->assertEquals($claims['email'], $data['email']);

        $this->expectException(UnauthorizedException::class);
        $this->service->validateAccessToken('invalid.token.here');
    }

    public function testRefreshTokenExpiredOrMissing(): void {
        $data = [
            'name' => 'Test',
            'surname' => 'Testington',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $user = $this->service->register($data);
        $userID = $user['user']['id'];

        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() - 3600);
        $stmt = $this->pdo->prepare('INSERT INTO refresh_tokens (token, user_id, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$token, $userID, $expiresAt]);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Refresh token expired');
        $this->service->refresh(['refresh_token' => $token]);
    }
}
