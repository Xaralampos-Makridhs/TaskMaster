<?php

// Import the Database class from the specific namespace
use App\Config;

// Enable error reporting for development purposes (helps identify bugs quickly)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers: Allows different frontends (e.g., React, mobile apps) to communicate with this API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle HTTP OPTIONS preflight requests (browsers send this before actual requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the Composer autoloader and core logic files
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/functions.php';

// Load environment variables from the .env file (DB credentials, secret keys)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Instantiate the Database class and establish a connection
$database = new App\Config\Database();
$db = $database->getConnection();

// Store the current HTTP request method (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request URL to determine the API endpoint path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, 'index.php') !== false) {
    // If URL contains index.php, get everything after it
    $uri = explode('index.php', $requestUri)[1];
} else {
    // Otherwise, strip the base directory to find the specific route
    $uri = str_replace('/TaskManager/public', '', $requestUri);
}

// Default to root if the URI is empty
if (empty($uri)) {$uri = '/';}

// Decode JSON data sent in the request body (for POST and PUT requests)
$input = json_decode(file_get_contents("php://input"), true);

// Default response if no valid route is matched
$response = ["status" => false, "message" => "Invalid Route"];

// The Router: Directs requests to the correct function based on the URI
switch ($uri) {
    case '/user/register':
        if ($method === 'POST') {
            $response = registerUser($db, $input);
        }
        break;

    case '/user/login':
        if ($method === 'POST') {
            $response = loginUser($db, $input);
        }
        break;

    case '/tasks':
        // Validate the JWT Token before allowing any task-related actions
        $auth = validateToken();
        if (!$auth['status']) {
            http_response_code(401); // Return "Unauthorized" status
            $response = $auth;
            break;
        }

        // Extract the user ID from the decoded token (prevents user ID spoofing)
        $logged_in_user_id = $auth['data']->id;

        if ($method === 'POST') {
            // Automatically assign ownership to the logged-in user
            $input['user_id'] = $logged_in_user_id;
            $response = createTask($db, $input);
        }
        elseif ($method === 'GET') {
            // Retrieve ONLY tasks belonging to the user in the token
            $response = getTasks($db, $logged_in_user_id);
        }
        elseif ($method === 'DELETE') {
            // Get the task ID from the URL parameters
            $task_id = $_GET['id'] ?? $_GET['task_id'] ?? null;
            if ($task_id) {
                // Pass both Task ID and User ID to ensure ownership check
                $response = deleteTask($db, $task_id, $logged_in_user_id);
            } else {
                $response = ["status" => false, "message" => "Task ID is required"];
            }
        }
        elseif ($method === 'PUT') {
            $task_id = $_GET['id'] ?? $_GET['task_id'] ?? null;
            if ($task_id && $input) {
                // Perform the update only if the user owns the task
                $response = updateTask($db, $task_id, $input, $logged_in_user_id);
            } else {
                $response = ["status" => false, "message" => "Task ID and Data required"];
            }
        }
        break;
}

// Convert the final response array to JSON and send it back to the client
echo json_encode($response);
