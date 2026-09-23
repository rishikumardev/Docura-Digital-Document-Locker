<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function request_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function verify_csrf(): void {
    start_secure_session();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        json_response(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.'], 419);
    }
}

function clean_original_name(string $name): string {
    $name = trim(basename($name));
    $name = preg_replace('/[^\w.\- ()]+/u', '_', $name) ?: 'document';
    return mb_substr($name, 0, 200);
}

function activity(PDO $conn, int $userId, string $action, ?string $documentName = null, ?string $details = null): void {
    $stmt = $conn->prepare('INSERT INTO activity (user_id, action, document_name, details) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $documentName, $details]);
}

function user_exists_by_email(PDO $conn, string $email): ?array {
    $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}
