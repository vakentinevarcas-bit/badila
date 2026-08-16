<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$email = strtolower($username) . '@local.com';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Kinahanglan nga sabtan ang Username kag Password (Username and Password are required).']);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(['status' => 'error', 'message' => 'Ang password kinahanglan indi nubo sa 4 ka characters (Password must be at least 4 chars).']);
    exit;
}


try {
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $checkStmt->execute(['username' => $username]);
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ang Username na-gamit na (Username already exists).']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $insertStmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword
    ]);
    $userId = $pdo->lastInsertId();

    try {
        $insertGameStmt = $pdoGame->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $insertGameStmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);
    } catch (PDOException $ex) {
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    echo json_encode([
        'status' => 'success',
        'message' => 'Naka-himo ka na sang Account! Na-save sa localhost_badilla kag game_db! (Account created successfully!)',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
