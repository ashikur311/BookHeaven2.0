<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'id' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['type'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($type) || empty($name)) {
            throw new Exception('Type and name are required');
        }

        $table = '';
        $idField = '';
        $nameField = 'name';

        switch ($type) {
            case 'writer':
                $table = 'writers';
                $idField = 'writer_id';
                break;
            case 'genre':
                $table = 'genres';
                $idField = 'genre_id';
                break;
            case 'category':
                $table = 'categories';
                $idField = 'id';
                break;
            case 'language':
                $table = 'languages';
                $idField = 'language_id';
                break;
            default:
                throw new Exception('Invalid type specified');
        }

        // Check if item already exists
        $stmt = $pdo->prepare("SELECT $idField FROM $table WHERE $nameField = ?");
        $stmt->execute([$name]);
        $existing = $stmt->fetch();

        if ($existing) {
            $response = [
                'success' => true,
                'message' => 'Item already exists',
                'id' => $existing[$idField]
            ];
        } else {
            // Insert new item
            $insertData = [$name];
            $columns = [$nameField];
            $placeholders = ['?'];
            
            if ($type === 'language') {
                $columns[] = 'status';
                $placeholders[] = '?';
                $insertData[] = $status;
            }

            $columnsStr = implode(', ', $columns);
            $placeholdersStr = implode(', ', $placeholders);

            $stmt = $pdo->prepare("INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)");
            $stmt->execute($insertData);

            $response = [
                'success' => true,
                'message' => 'Item added successfully',
                'id' => $pdo->lastInsertId()
            ];
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}
echo json_encode($response);