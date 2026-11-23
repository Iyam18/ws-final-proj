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

function character_length(string $value): int
{
    $count = preg_match_all('/./us', $value);
    if ($count === false) {
        respond(['error' => 'Please check the text you entered.'], 422);
    }
    return $count;
}

function text_value(array $data, string $key, int $maxLength, bool $required = true): string
{
    $value = $data[$key] ?? '';
    if (!is_string($value)) {
        respond(['error' => 'Please check the information you entered.'], 422);
    }

    $value = trim($value);
    if ($required && $value === '') {
        respond(['error' => 'Please fill in all required fields.'], 422);
    }
    if (character_length($value) > $maxLength || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
        respond(['error' => 'One of the fields contains invalid or overly long text.'], 422);
    }
    return $value;
}

function date_value(array $data, string $key): ?string
{
    $value = $data[$key] ?? null;
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_string($value)) {
        respond(['error' => 'Choose a valid due date.'], 422);
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        respond(['error' => 'Choose a valid due date.'], 422);
    }
    return $value;
}

function priority_value(array $data, string $key): string
{
    $value = $data[$key] ?? 'medium';
    if (!is_string($value) || !in_array($value, ['low', 'medium', 'high'], true)) {
        respond(['error' => 'Choose a valid task priority.'], 422);
    }
    return $value;
}

function recurrence_value(array $data, string $key): string
{
    $value = $data[$key] ?? 'none';
    if (!is_string($value) || !in_array($value, ['none', 'daily', 'weekly', 'monthly'], true)) {
        respond(['error' => 'Choose a valid repeat schedule.'], 422);
    }
    return $value;
}

function next_due_date(?string $date, string $recurrence): ?string
{
    if ($recurrence === 'none') {
        return null;
    }
    $next = new DateTimeImmutable($date ?? 'today');
    $today = new DateTimeImmutable('today');
    do {
        if ($recurrence === 'daily') {
            $next = $next->modify('+1 day');
        } elseif ($recurrence === 'weekly') {
            $next = $next->modify('+1 week');
        } else {
            $firstOfMonth = $next->modify('first day of next month');
            $day = min((int) $next->format('j'), (int) $firstOfMonth->format('t'));
            $next = $firstOfMonth->setDate(
                (int) $firstOfMonth->format('Y'),
                (int) $firstOfMonth->format('n'),
                $day
            );
        }
    } while ($next < $today);
    return $next->format('Y-m-d');
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

try {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET' && $action === 'bootstrap') {
        respond(['user' => $_SESSION['user'] ?? null, 'csrf' => $_SESSION['csrf']]);
    }

    if ($method === 'GET' && $action === 'tasks') {
        $user = current_user();
        $statement = db()->prepare(
            "SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
               t.recurrence, t.completed_at, t.created_at
             FROM tasks t
             WHERE t.user_id = :user_id
             ORDER BY FIELD(t.status, 'pending', 'completed'),
               FIELD(t.priority, 'high', 'medium', 'low'),
               t.due_date IS NULL ASC, t.due_date ASC,
               t.created_at DESC, t.id DESC"
        );
        $statement->execute(['user_id' => $user['id']]);
        respond(['tasks' => $statement->fetchAll()]);
    }

    if ($method === 'GET' && $action === 'dashboard') {
        $user = current_user();
        $statement = db()->prepare(
            "SELECT
               SUM(CASE WHEN status = 'pending' AND due_date = CURDATE() THEN 1 ELSE 0 END) AS due_today,
               SUM(CASE WHEN status = 'pending' AND priority = 'high' THEN 1 ELSE 0 END) AS priority_count,
               SUM(CASE WHEN status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                 AND completed_at < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
                 THEN 1 ELSE 0 END) AS completed_this_week
             FROM tasks WHERE user_id = :user_id"
        );
        $statement->execute(['user_id' => $user['id']]);
        $stats = $statement->fetch();
        respond([
            'due_today' => (int) ($stats['due_today'] ?? 0),
            'high_priority' => (int) ($stats['priority_count'] ?? 0),
            'completed_this_week' => (int) ($stats['completed_this_week'] ?? 0),
            'weekly_goal' => 10,
        ]);
    }

    if ($method !== 'POST') {
        respond(['error' => 'This request is not supported.'], 405);
    }

    verify_csrf();
    $data = input();

    if ($action === 'register') {
        $name = text_value($data, 'name', 60);
        $email = strtolower(text_value($data, 'email', 254));
        $password = $data['password'] ?? null;

        if (character_length($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(['error' => 'Enter a name of at least 2 characters and a valid email address.'], 422);
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            respond(['error' => 'Your password must be between 8 and 72 characters.'], 422);
        }

        $pdo = db();
        $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
        try {
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

        $statement = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        if (!$user) {
            respond([
                'error' => 'There’s no account registered with that email yet.',
                'code' => 'account_not_found',
            ], 404);
        }
        if (!password_verify($password, $user['password_hash'])) {
            respond(['error' => 'The email or password is incorrect.'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        respond(['user' => $_SESSION['user'], 'csrf' => $_SESSION['csrf']]);
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        respond(['success' => true, 'csrf' => $_SESSION['csrf']]);
    }

    $user = current_user();
    $pdo = db();

    if ($action === 'create-task') {
        $title = text_value($data, 'title', 120);
        $description = text_value($data, 'description', 1000, false);
        $priority = priority_value($data, 'priority');
        $dueDate = date_value($data, 'due_date');
        $recurrence = recurrence_value($data, 'recurrence');
        if (character_length($title) > 120 || character_length($description) > 1000) {
            respond(['error' => 'Task text is too long.'], 422);
        }

        $statement = $pdo->prepare(
            'INSERT INTO tasks (user_id, title, description, priority, due_date, recurrence)
             VALUES (:user_id, :title, :description, :priority, :due_date, :recurrence)'
        );
        $statement->execute([
            'user_id' => $user['id'],
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'due_date' => $dueDate,
            'recurrence' => $recurrence,
        ]);
        $id = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare(
            'SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
               t.recurrence, t.completed_at, t.created_at
             FROM tasks t
             WHERE t.id = :id AND t.user_id = :user_id'
        );
        $statement->execute(['id' => $id, 'user_id' => $user['id']]);
        respond(['task' => $statement->fetch()], 201);
    }

    if ($action === 'update-task') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            respond(['error' => 'That task could not be found.'], 404);
        }

        $changes = [];
        $parameters = ['id' => $id, 'user_id' => $user['id']];
        if (array_key_exists('title', $data)) {
            $changes[] = 'title = :title';
            $parameters['title'] = text_value($data, 'title', 120);
        }
        if (array_key_exists('description', $data)) {
            $changes[] = 'description = :description';
            $parameters['description'] = text_value($data, 'description', 1000, false);
        }
        if (array_key_exists('priority', $data)) {
            $changes[] = 'priority = :priority';
            $parameters['priority'] = priority_value($data, 'priority');
        }
        if (array_key_exists('due_date', $data)) {
            $changes[] = 'due_date = :due_date';
            $parameters['due_date'] = date_value($data, 'due_date');
        }
        if (array_key_exists('recurrence', $data)) {
            $changes[] = 'recurrence = :recurrence';
            $parameters['recurrence'] = recurrence_value($data, 'recurrence');
        }
        $wasPending = false;
        $statusTransaction = false;
        if (array_key_exists('status', $data)) {
            if (!is_string($data['status']) || !in_array($data['status'], ['pending', 'completed'], true)) {
                respond(['error' => 'Choose a valid task status.'], 422);
            }
            $pdo->beginTransaction();
            $statusTransaction = true;
            $statement = $pdo->prepare(
                'SELECT status, title, description, priority, due_date, recurrence
                 FROM tasks WHERE id = :id AND user_id = :user_id FOR UPDATE'
            );
            $statement->execute(['id' => $id, 'user_id' => $user['id']]);
            $previous = $statement->fetch();
            if (!$previous) {
                $pdo->rollBack();
                respond(['error' => 'That task could not be found.'], 404);
            }
            $wasPending = $previous['status'] === 'pending' && $data['status'] === 'completed';
            $changes[] = 'status = :status';
            $parameters['status'] = $data['status'];
            $changes[] = 'completed_at = :completed_at';
            $parameters['completed_at'] = $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        }
        if ($changes === []) {
            respond(['error' => 'There are no task changes to save.'], 422);
        }

        $statement = $pdo->prepare('UPDATE tasks SET ' . implode(', ', $changes) . ' WHERE id = :id AND user_id = :user_id');
        $statement->execute($parameters);
        $nextTaskId = null;
        if ($wasPending && $previous['recurrence'] !== 'none') {
            $nextDate = next_due_date($previous['due_date'], $previous['recurrence']);
            $statement = $pdo->prepare(
                'INSERT INTO tasks (user_id, title, description, priority, due_date, recurrence)
                 VALUES (:user_id, :title, :description, :priority, :due_date, :recurrence)'
            );
            $statement->execute([
                'user_id' => $user['id'],
                'title' => $previous['title'],
                'description' => $previous['description'],
                'priority' => $previous['priority'],
                'due_date' => $nextDate,
                'recurrence' => $previous['recurrence'],
            ]);
            $nextTaskId = (int) $pdo->lastInsertId();
        }
        if ($statusTransaction) {
            $pdo->commit();
        }
        $statement = $pdo->prepare(
            'SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
               t.recurrence, t.completed_at, t.created_at
             FROM tasks t
             WHERE t.id = :id AND t.user_id = :user_id'
        );
        $statement->execute(['id' => $id, 'user_id' => $user['id']]);
        $task = $statement->fetch();
        if (!$task) {
            respond(['error' => 'That task could not be found.'], 404);
        }
        $response = ['task' => $task];
        if ($nextTaskId !== null) {
            $statement->execute(['id' => $nextTaskId, 'user_id' => $user['id']]);
            $response['next_task'] = $statement->fetch();
        }
        respond($response);
    }

    if ($action === 'delete-task') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            respond(['error' => 'That task could not be found.'], 404);
        }
        $statement = $pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $id, 'user_id' => $user['id']]);
        if ($statement->rowCount() !== 1) {
            respond(['error' => 'That task could not be found.'], 404);
        }
        respond(['success' => true]);
    }

    respond(['error' => 'The requested action was not found.'], 404);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Daymark API error: ' . $exception->getMessage());
    respond(['error' => 'Something went wrong. Please try again in a moment.'], 500);
}
