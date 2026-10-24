<?php
namespace App;

use PDO; // <-- Добавьте эту строку

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    public function register(string $login, string $password, string $bg, string $font): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (login, password, bg_color, font_color) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$login, $hash, $bg, $font]);
    }

    public function login(string $login, string $password): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function updateSettings(int $id, string $bg, string $font): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET bg_color = ?, font_color = ? WHERE id = ?");
        return $stmt->execute([$bg, $font, $id]);
    }

    public function setRememberToken(int $id, ?string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$token, $id]);
    }

    public function getUserByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE remember_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}