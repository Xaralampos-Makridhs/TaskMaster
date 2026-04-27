<?php
//Include Composer autoloader to load dependencies(Dotenv and Faker)
require_once __DIR__ . '/../vendor/autoload.php';
//Include the Database configuration class
require_once __DIR__ . '/../src/Config/Database.php';

//Import the required classes into the current scope
use App\config\Database;
use Faker\Factory;

//Initialize Dotenv to load environment variables from the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

//Create a new Database instance and establish a connection
$database=new Database();
$db=$database->getConnection();

//Initialize the faker library to generate random data(names,emails etc)
$faker=Factory::create();

try{
    //Array to store the IDs of the users we are about to create
    $userIds=[];

    //Loop to create 5 dummy users
    for($i=0;$i<5;$i++){

        //SQL statement
        $sql="INSERT INTO users (username,email,password_hash) VALUES (:username,:email,:password)";
        $stmt=$db->prepare($sql);

        //Hash a default password for all users
        $password=password_hash('123456',PASSWORD_BCRYPT);

        //Generate a random username and a unique,safe email address
        $username=$faker->userName;
        $email=$faker->unique()->safeEmail;

        //Bind the actual values to the SQL placeholders
        $stmt->bindParam(':username',$username);
        $stmt->bindParam(':email',$email);
        $stmt->bindParam(':password',$password);

        //Execute the statement to insert the values into the database
        $stmt->execute();

        //Get the ID of the user last created and add it in the array
        $userIds[]=$db->lastInsertId();
    }

    //Define the values of the ENUM status and priority
    $statuses=['pending','in_progress','completed'];
    $priorities=['low','medium','high'];

    //Loop to create 30 dummy tasks
    for($i=0;$i<30;$i++){

        //SQL statement
        $sql="INSERT INTO tasks (user_id,title,description,status,priority,due_date) VALUES (:user_id,:title,:description,:status,:priority,:due_date)";
        $stmt=$db->prepare($sql);

        //Pick a random user ID from the users we created above
        $randomUserId=$userIds[array_rand($userIds)];
        //Generate fake task content (short sentence for title,paragraph for description)
        $title=$faker->sentence(4);
        $description=$faker->paragraph;
        //Randomly select status and priority from the defined arrays
        $status=$statuses[array_rand($statuses)];
        $priority=$priorities[array_rand($priorities)];
        //Generate a random date between now and one month from now
        $dueDate=$faker->dateTimeBetween('now','+1 month')->format('Y-m-d');

        //Bind all the parameters to the statement
        $stmt->bindParam(':user_id',$randomUserId);
        $stmt->bindParam(':title',$title);
        $stmt->bindParam(':description',$description);
        $stmt->bindParam(':status',$status);
        $stmt->bindParam(':priority',$priority);
        $stmt->bindParam(':due_date',$dueDate);

        //Execute the statement
        $stmt->execute();
    }
}catch (Exception $e){
    echo 'Failed: '.$e->getMessage();
}
