<?php
declare(strict_types=1);
namespace App\Service;

use App\ORM\User;
use App\ORM\RefreshToken;
use App\Exception\ValidationException;
use App\Exception\UnauthorizedException;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService {
    public function register(array $data): array {
        $existing = User::find(['email' => $data['email']]);
        if (!empty($existing)) {
            throw new ValidationException('Email already exists');
        }

        $user = User::transaction(function () use ($data) {
            $user = new User([
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'address' => $data['address'] ?? ''
            ]);
            $user->save();

            return $this->generateTokens($user);
        });

        return $user;
    }

    public function login(array $data): array {
        $results = User::find(['email' => $data['email']]);
        if (empty($results)) {
            throw new UnauthorizedException('Invalid credentials');
        }
        $user = $results[0];
        if (!password_verify($data['password'], $user->password)) {
            throw new UnauthorizedException('Invalid credentials');
        }
        return $this->generateTokens($user);
    }

    public function refresh(array $data): array {
        $results = RefreshToken::find(['token' => $data['refresh_token']]);
        if (empty($results)) {
            throw new UnauthorizedException('Invalid refresh token');
        }

        $refreshToken = $results[0];
        if($refreshToken->expires_at < new DateTimeImmutable()) {
            throw new UnauthorizedException('Refresh token expired');
        }

        $user = $refreshToken->getUser();
        if(!$user) {
            throw new UnauthorizedException('User not found');
        }

        $refreshToken->delete();
        return $this->generateTokens($user);
    }

    public function logout(string $refreshToken): void {
        $results = RefreshToken::find(['token' => $refreshToken]);
        if (empty($results)) {
            return;
        }

        $refreshToken = $results[0];
        $refreshToken->delete();
    }

    public function validateAccessToken(string $token): array {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return (array)$decoded;
        } catch(\Throwable $e) {
            throw new UnauthorizedException('Invalid or expired access token');
        }
    }

    private function generateTokens(User $user): array {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);
        return [
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => JWT_EXPIRY
        ];
    }

    private function generateAccessToken(User $user): string {
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
        ];
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    private function generateRefreshToken(User $user): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);
        $refreshToken = new RefreshToken([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
        $refreshToken->save();
        return $token;
    }
}
