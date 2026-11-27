<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($data)) {
        respond(['error' => 'Please send a valid JSON request.'], 400);
    }
    return $data;
}

function text_value(array $data, string $key, int $maxLength, bool $required = true): string
{
    $value = $data[$key] ?? '';
    if (!is_string($value)) {
        respond(['error' => 'Please check the information you entered.'], 422);
    }
    $value = trim($value);
    $length = preg_match_all('/./us', $value);
    if ($length === false || ($required && $value === '') || $length > $maxLength ||
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
        respond(['error' => 'Please check the required fields and text lengths.'], 422);
    }
    return $value;
}

function optional_email(array $data, string $key): string
{
    $email = strtolower(text_value($data, $key, 254, false));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Enter a valid email address.'], 422);
    }
    return $email;
}

function optional_philippine_phone(array $data, string $key): string
{
    $phone = text_value($data, $key, 32, false);
    if ($phone === '') {
        return '';
    }
    if (!preg_match('/^\+?[0-9(). -]+$/', $phone)) {
        respond(['error' => 'Enter a valid Philippine mobile number.'], 422);
    }
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (is_string($digits) && preg_match('/^09[0-9]{9}$/D', $digits)) {
        return $digits;
    }
    if (is_string($digits) && preg_match('/^639[0-9]{9}$/D', $digits)) {
        return '0' . substr($digits, 2);
    }
    respond(['error' => 'Use Philippine mobile format 09XXXXXXXXX or +639XXXXXXXXX.'], 422);
}

function date_value(array $data, string $key): ?string
{
    $value = $data[$key] ?? '';
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_string($value)) {
        respond(['error' => 'Choose a valid date.'], 422);
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) ||
        $date->format('Y-m-d') !== $value || $date > new DateTimeImmutable('today')) {
        respond(['error' => 'Choose a valid date that is not in the future.'], 422);
    }
    return $value;
}

function scheduled_value(array $data, string $key): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
        respond(['error' => 'Choose a valid appointment date and time.'], 422);
    }
    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$dateTime || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) ||
        $dateTime->format('Y-m-d\TH:i') !== $value) {
        respond(['error' => 'Choose a valid appointment date and time.'], 422);
    }
    return $dateTime->format('Y-m-d H:i:s');
}

function current_user(): array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        respond(['error' => 'Please sign in to continue.'], 401);
    }
    return $_SESSION['user'];
}

function verify_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        respond(['error' => 'Your session has expired. Refresh the page and try again.'], 403);
    }
}

function patient_exists(PDO $pdo, int $patientId, int $staffUserId): bool
{
    $statement = $pdo->prepare('SELECT id FROM patients WHERE id = :id AND staff_user_id = :staff_user_id');
    $statement->execute(['id' => $patientId, 'staff_user_id' => $staffUserId]);
    return (bool) $statement->fetchColumn();
}

function patient_id_value(PDO $pdo, array $data, string $key, int $staffUserId, bool $allowArchived = false): int
{
    $id = filter_var($data[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || !patient_exists($pdo, (int) $id, $staffUserId) ||
        (!$allowArchived && !patient_is_active($pdo, (int) $id, $staffUserId))) {
        respond(['error' => 'Select a patient from your records.'], 422);
    }
    return (int) $id;
}

function patient_is_active(PDO $pdo, int $patientId, int $staffUserId): bool
{
    $statement = $pdo->prepare(
        'SELECT id FROM patients WHERE id = :id AND staff_user_id = :staff_user_id AND archived_at IS NULL'
    );
    $statement->execute(['id' => $patientId, 'staff_user_id' => $staffUserId]);
    return (bool) $statement->fetchColumn();
}

function appointment_query(): string
{
    return 'SELECT a.id, a.patient_id, a.reason, a.notes, a.scheduled_at, a.status,
                   a.completed_at, a.created_at,
                   p.full_name AS patient_name, p.phone AS patient_phone
            FROM appointments a
            JOIN patients p ON p.id = a.patient_id AND p.staff_user_id = a.staff_user_id
            WHERE a.staff_user_id = :staff_user_id';
}

function fetch_appointment(PDO $pdo, int $id, int $staffUserId): ?array
{
    $statement = $pdo->prepare(appointment_query() . ' AND a.id = :id');
    $statement->execute(['staff_user_id' => $staffUserId, 'id' => $id]);
    $appointment = $statement->fetch();
    return $appointment ?: null;
}

try {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET' && $action === 'bootstrap') {
        respond(['user' => $_SESSION['user'] ?? null, 'csrf' => $_SESSION['csrf']]);
    }

    if ($method === 'POST' && in_array($action, ['register', 'login', 'logout'], true)) {
        verify_csrf();
        $data = input();
        if ($action === 'register') {
            $name = text_value($data, 'name', 60);
            $email = strtolower(text_value($data, 'email', 254));
            $password = $data['password'] ?? null;
            if (mb_strlen($name, 'UTF-8') < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(['error' => 'Enter a name of at least two characters and a valid email.'], 422);
            }
            if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
                respond(['error' => 'Your password must be between 8 and 72 characters.'], 422);
            }
            $pdo = db();
            try {
                $statement = $pdo->prepare('INSERT INTO staff_users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    respond(['error' => 'An account with that email already exists.'], 409);
                }
                throw $exception;
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'email' => $email];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            respond(['user' => $_SESSION['user'], 'csrf' => $_SESSION['csrf']], 201);
        }

        if ($action === 'login') {
            $email = strtolower(text_value($data, 'email', 254));
            $password = $data['password'] ?? null;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '' || strlen($password) > 72) {
                respond(['error' => 'The email or password is incorrect.'], 401);
            }
            $statement = db()->prepare('SELECT id, name, email, password_hash FROM staff_users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                respond(['error' => 'The email or password is incorrect.'], 401);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            respond(['user' => $_SESSION['user'], 'csrf' => $_SESSION['csrf']]);
        }

        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        respond(['success' => true, 'csrf' => $_SESSION['csrf']]);
    }

    $user = current_user();
    $staffUserId = (int) $user['id'];
    if ($method === 'GET' && $action === 'patients') {
        $statement = db()->prepare(
            'SELECT p.id, p.full_name, p.date_of_birth, p.phone, p.email, p.address,
                    p.allergies, p.medical_notes, p.archived_at, p.created_at,
                    COUNT(a.id) AS appointment_count
             FROM patients p
             LEFT JOIN appointments a ON a.patient_id = p.id AND a.staff_user_id = p.staff_user_id
             WHERE p.staff_user_id = :staff_user_id
             GROUP BY p.id ORDER BY p.full_name ASC'
        );
        $statement->execute(['staff_user_id' => $staffUserId]);
        respond(['patients' => $statement->fetchAll()]);
    }

    if ($method === 'GET' && $action === 'appointments') {
        $statement = db()->prepare(appointment_query() .
            " ORDER BY FIELD(a.status, 'scheduled', 'completed', 'cancelled'),
             a.scheduled_at ASC, a.id DESC");
        $statement->execute(['staff_user_id' => $staffUserId]);
        respond(['appointments' => $statement->fetchAll()]);
    }

    if ($method === 'GET' && $action === 'dashboard') {
        $pdo = db();
        $statement = $pdo->prepare(
            "SELECT
              (SELECT COUNT(*) FROM appointments WHERE staff_user_id = :today_user
                AND status = 'scheduled' AND DATE(scheduled_at) = CURDATE()) AS today_count,
              (SELECT COUNT(*) FROM appointments WHERE staff_user_id = :week_user
                AND status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                AND completed_at < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)) AS completed_week,
              (SELECT COUNT(*) FROM patients WHERE staff_user_id = :patient_user AND archived_at IS NULL) AS patient_count"
        );
        $statement->execute([
            'today_user' => $staffUserId,
            'week_user' => $staffUserId,
            'patient_user' => $staffUserId,
        ]);
        $stats = $statement->fetch();
        respond([
            'today_appointments' => (int) $stats['today_count'],
            'completed_this_week' => (int) $stats['completed_week'],
            'patient_count' => (int) $stats['patient_count'],
        ]);
    }

    if ($method !== 'POST') {
        respond(['error' => 'This request is not supported.'], 405);
    }
    verify_csrf();
    $data = input();
    $pdo = db();

    if ($action === 'create-patient' || $action === 'update-patient') {
        $id = null;
        if ($action === 'update-patient') {
            $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                respond(['error' => 'That patient record could not be found.'], 404);
            }
        }
        $name = text_value($data, 'full_name', 120);
        if (mb_strlen($name, 'UTF-8') < 2) {
            respond(['error' => 'Patient name must contain at least two characters.'], 422);
        }
        $dob = date_value($data, 'date_of_birth');
        $phone = optional_philippine_phone($data, 'phone');
        $email = optional_email($data, 'email');
        $address = text_value($data, 'address', 255);
        $allergies = text_value($data, 'allergies', 2000, false);
        $notes = text_value($data, 'medical_notes', 2000, false);
        if ($action === 'create-patient') {
            $statement = $pdo->prepare(
                'INSERT INTO patients (staff_user_id, full_name, date_of_birth, phone, email, address, allergies, medical_notes)
                 VALUES (:staff_user_id, :full_name, :date_of_birth, :phone, :email, :address, :allergies, :medical_notes)'
            );
            $statement->execute([
                'staff_user_id' => $staffUserId, 'full_name' => $name, 'date_of_birth' => $dob,
                'phone' => $phone, 'email' => $email, 'address' => $address,
                'allergies' => $allergies, 'medical_notes' => $notes,
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $statement = $pdo->prepare(
                'UPDATE patients SET full_name = :full_name, date_of_birth = :date_of_birth,
                 phone = :phone, email = :email, address = :address, allergies = :allergies,
                 medical_notes = :medical_notes WHERE id = :id AND staff_user_id = :staff_user_id'
            );
            $statement->execute([
                'full_name' => $name, 'date_of_birth' => $dob, 'phone' => $phone,
                'email' => $email, 'address' => $address, 'allergies' => $allergies,
                'medical_notes' => $notes, 'id' => $id, 'staff_user_id' => $staffUserId,
            ]);
            if ($statement->rowCount() === 0 && !patient_exists($pdo, (int) $id, $staffUserId)) {
                respond(['error' => 'That patient record could not be found.'], 404);
            }
        }
        $statement = $pdo->prepare(
            'SELECT id, full_name, date_of_birth, phone, email, address, allergies, medical_notes, archived_at
             FROM patients WHERE id = :id AND staff_user_id = :staff_user_id'
        );
        $statement->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
        respond(['patient' => $statement->fetch()], $action === 'create-patient' ? 201 : 200);
    }

    if (in_array($action, ['archive-patient', 'restore-patient'], true)) {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !patient_exists($pdo, (int) $id, $staffUserId)) {
            respond(['error' => 'That patient record could not be found.'], 404);
        }
        $archivedAt = $action === 'archive-patient' ? 'CURRENT_TIMESTAMP' : 'NULL';
        $statement = $pdo->prepare(
            'UPDATE patients SET archived_at = ' . $archivedAt . ' WHERE id = :id AND staff_user_id = :staff_user_id'
        );
        $statement->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
        $statement = $pdo->prepare('SELECT archived_at FROM patients WHERE id = :id AND staff_user_id = :staff_user_id');
        $statement->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
        respond(['success' => true, 'archived_at' => $statement->fetchColumn() ?: null]);
    }

    if ($action === 'delete-patient') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !patient_exists($pdo, (int) $id, $staffUserId)) {
            respond(['error' => 'That patient record could not be found.'], 404);
        }
        $check = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE patient_id = :patient_id AND staff_user_id = :staff_user_id');
        $check->execute(['patient_id' => $id, 'staff_user_id' => $staffUserId]);
        if ((int) $check->fetchColumn() > 0) {
            respond(['error' => 'This patient has appointment history. Archive the record instead of deleting it.'], 409);
        }
        $statement = $pdo->prepare('DELETE FROM patients WHERE id = :id AND staff_user_id = :staff_user_id');
        $statement->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
        respond(['success' => true]);
    }

    if ($action === 'create-appointment' || $action === 'update-appointment') {
        $id = null;
        if ($action === 'update-appointment') {
            $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                respond(['error' => 'That appointment could not be found.'], 404);
            }
        }
        $reason = text_value($data, 'reason', 160);
        $notes = text_value($data, 'notes', 2000, false);
        $scheduledAt = scheduled_value($data, 'scheduled_at');
        if ($action === 'create-appointment') {
            $patientId = patient_id_value($pdo, $data, 'patient_id', $staffUserId);
            $statement = $pdo->prepare(
                'INSERT INTO appointments (staff_user_id, patient_id, reason, notes, scheduled_at)
                 VALUES (:staff_user_id, :patient_id, :reason, :notes, :scheduled_at)'
            );
            $statement->execute([
                'staff_user_id' => $staffUserId, 'patient_id' => $patientId,
                'reason' => $reason, 'notes' => $notes, 'scheduled_at' => $scheduledAt,
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $status = $data['status'] ?? 'scheduled';
            if (!is_string($status) || !in_array($status, ['scheduled', 'completed', 'cancelled'], true)) {
                respond(['error' => 'Choose a valid appointment status.'], 422);
            }
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT patient_id FROM appointments WHERE id = :id AND staff_user_id = :staff_user_id FOR UPDATE');
            $lock->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
            $previous = $lock->fetch();
            if (!$previous) {
                $pdo->rollBack();
                respond(['error' => 'That appointment could not be found.'], 404);
            }
            $patientId = patient_id_value($pdo, $data, 'patient_id', $staffUserId, true);
            if ($patientId !== (int) $previous['patient_id']) {
                $pdo->rollBack();
                respond(['error' => 'A patient cannot be changed while editing an appointment. Cancel and create a new appointment instead.'], 422);
            }
            $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
            $statement = $pdo->prepare(
                'UPDATE appointments SET patient_id = :patient_id, reason = :reason, notes = :notes,
                 scheduled_at = :scheduled_at, status = :status, completed_at = :completed_at
                 WHERE id = :id AND staff_user_id = :staff_user_id'
            );
            $statement->execute([
                'patient_id' => $patientId, 'reason' => $reason, 'notes' => $notes,
                'scheduled_at' => $scheduledAt,
                'status' => $status, 'completed_at' => $completedAt, 'id' => $id,
                'staff_user_id' => $staffUserId,
            ]);
            $pdo->commit();
        }

        $appointment = fetch_appointment($pdo, (int) $id, $staffUserId);
        if (!$appointment) {
            respond(['error' => 'The appointment could not be loaded.'], 500);
        }
        respond(['appointment' => $appointment], $action === 'create-appointment' ? 201 : 200);
    }

    if ($action === 'delete-appointment') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            respond(['error' => 'That appointment could not be found.'], 404);
        }
        $statement = $pdo->prepare('DELETE FROM appointments WHERE id = :id AND staff_user_id = :staff_user_id');
        $statement->execute(['id' => $id, 'staff_user_id' => $staffUserId]);
        if ($statement->rowCount() !== 1) {
            respond(['error' => 'That appointment could not be found.'], 404);
        }
        respond(['success' => true]);
    }

    respond(['error' => 'The requested action was not found.'], 404);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('MediSched API error: ' . $exception->getMessage());
    respond(['error' => 'Something went wrong. Please try again in a moment.'], 500);
}
