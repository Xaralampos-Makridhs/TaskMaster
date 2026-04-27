<?php

//Import the necessary classes from the Firebase JWT (JSON Web Token)
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

//Function to register a new user into the database
function registerUser(PDO $db, $data) {
    //Basic check to ensure all required fields are provided
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        return ["status" => false,
            "message" => "Όλα τα πεδία είναι υποχρεωτικά."];
    }

    //Validate email format
    if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){
        return [
            "status"=>false,
            "message"=>"Email is not valid"
        ];
    }

    //SQL statement
    $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password)";


    try {
        $stmt = $db->prepare($sql);

        //Sanitize input data to prevent Cross Site Scripting (XSS)
        $username = htmlspecialchars(strip_tags($data['username']));
        $email = htmlspecialchars(strip_tags($data['email']));

        //Security hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        //Bind Sanitized values to the SQL placeholders
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);


        if ($stmt->execute()) {
            return ["status" => true, "message" => "Ο χρήστης δημιουργήθηκε επιτυχώς."];
        }
    } catch (PDOException $e) {
        //Handle duplicate entry errors
        if ($e->getCode() == 23000) {
            return ["status" => false,
                "message" => "Το username ή το email χρησιμοποιείται ήδη."];
        }
        return ["status" => false,
            "message" => "Σφάλμα συστήματος κατά την εγγραφή."];
    }
    return ["status" => false,
        "message" => "Η εγγραφή απέτυχε."];
}

//Function to authenticate a user and issue with JWT Token
function loginUser(PDO $db, $data) {
    $email=$data['email'];
    $password=$data['password'];

    //Search for the user by their email address
    $sql="SELECT * FROM users WHERE email=:email";
    $stmt=$db->prepare($sql);
    $stmt->bindParam(':email',$email);
    $stmt->execute();
    $user=$stmt->fetch(PDO::FETCH_ASSOC);

    //Verify user existence and check if the password mathces the stored hash
    if($user && password_verify($password,$user['password_hash'])){
        $secret_key=$_ENV['JWT_SECRETKEY']; //Load secret key from the .env
        $issuer_claim="localhost"; //Issuer
        $issuedat_claim=time(); //Time of issuance
        $notbefore_claim=$issuedat_claim;
        $expireclaim=$issuedat_claim+3600; //Token expiration (1 hour from now)

        //Data payload to be encoded within the token
        $payload=array(
            "iss"=>$issuer_claim,
            "iat"=>$issuedat_claim,
            "nbf"=>$notbefore_claim,
            "exp"=>$expireclaim,
            "data"=>array(
                "id"=>$user['id'],
                "username"=>$user['username'],
                "email"=>$user['email']
            )
        );

        //Generate the JWT string  JS256
        $jwt=JWT::encode($payload,$secret_key,'HS256');

        return[
            "status"=>true,
            "message"=>"Login successfully",
            "token"=>$jwt,
            "expire_at"=>$expireclaim
        ];
    }else{
        return ["status"=>false,"message"=>"Invalid email or password"];
    }

}

//Function to validate the JWT Token from the request headers
function validateToken(){

    //Retrieve all headers from the incoming request
    $headers=getallheaders();

    //Look for the Authorization header(handling both cases-sensitive)
    $authHeader=$headers['Authorization'] ?? $headers['authorization'] ?? null;
    if(!$authHeader){
        return[
            "status"=>false,
            "message"=>"Access denied."
        ];
    }

    //Strip the 'Bearer ' prefix to get the raw token string
    $token=str_replace('Bearer ','',$authHeader);

    try{
        $secret_key=$_ENV['JWT_SECRETKEY'];
        //Decode the token using the secret key
        $decoded=JWT::decode($token,new Key($secret_key,'HS256'));

        return [
            "status"=>true,
            "data"=>$decoded->data //Return the user data stored inside the token
        ];

    }catch (Exception $e){
        return[
            "status"=>false,
            "message"=>"Invalid or expired token"
        ];
    }
}

//Function to create the task
function createTask($db, $data) {
    if (empty($data['user_id']) || empty($data['title'])) {
        return ["status" => false,
            "message" => "User ID και τίτλος απαιτούνται."];
    }

    //SQL statement
    $sql = "INSERT INTO tasks (user_id, title, description, status, priority, due_date) 
                VALUES (:user_id, :title, :description, :status, :priority, :due_date)";

    try {
        $stmt = $db->prepare($sql);

        //Bind parametrs with HTML sanitization
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':title', htmlspecialchars(strip_tags($data['title'])));
        $stmt->bindParam(':description', htmlspecialchars(strip_tags($data['description'])));
        //Use bindValue for optional fields with default fallbacks
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':priority', $data['priority'] ?? 'medium');
        $stmt->bindParam(':due_date', $data['due_date']);

        if ($stmt->execute()) {
            return ["status" => true,
                "message" => "Το task δημιουργήθηκε."];
        }
    } catch (PDOException $e) {
        return ["status" => false,
            "message" => "Αποτυχία δημιουργίας task: " . $e->getMessage()];
    }
}

//Function to retrieve all tasks belonging to a specific user
function getTasks($db, $user_id) {
    try {
        //Only select tasks where the user_id mathces the logged-in user
        $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return ["status" => true,
            "data" => $stmt->fetchAll()];
    } catch (PDOException $e) {
        return ["status" => false,
            "message" => "Σφάλμα κατά την ανάκτηση των tasks."];
    }
}

//Function to delete a task(With Ownership check)
function deleteTask(PDO $db, $task_id)
{
    try {
        //SQL statement to delete task by their ID
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $task_id, PDO::PARAM_INT);
        $stmt->execute();

        //Check if a row was actually deleted (>0)
        //If it returns 0, the task either doesn't exist or doesn't belong to the user
        if ($stmt->rowCount() > 0) {
            return ["status" => true, "message" => "Task deleted successfully"];
        } else {
            return ["status" => false, "message" => "Task not found"];
        }
    } catch (PDOException $e) {
        return ["status" => false, "message" => "Database Error: " . $e->getMessage()];
    }
}

//Function to update a task(With Ownership Check)
function updateTask(PDO $db,$task_id,$data)
{
    try {
        //SQL statement to update task by their ID
        $sql = "UPDATE tasks SET 
                title=:title,
                description=:description,
                status=:status,
                priority=:priority,
                due_date=:due_date
            WHERE id=:id";

        $stmt = $db->prepare($sql);

        //Bind the new data values to the statement
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':id', $task_id, PDO::PARAM_INT);

        $stmt->execute();

        //Check if the update was successfully
        if ($stmt->rowCount() > 0) {
            return [
                "status" => true,
                "message" => "Task updated successfully"
            ];
        }
    } catch (PDOException $e) {
        return [
            "status" => false,
            "message" => "Database Error: " . $e->getMessage()
        ];
    }
}
