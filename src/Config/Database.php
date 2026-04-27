<?php
//Define the namespace to organize the class and avoid naming conflicts
namespace App\Config;

//Import build-in PHP Data Objects classes for database interaction
use PDO;
use PDOException;

class Database{
    //Properties to store connection credentials(encapsulates as private)
    private $host; //Server address (e.g., localhost)
    private $db_name; //The name of the database
    private $username; //Database user(e.g., root)
    private $password; //Database password
    private $port; //Connection port (8889 for MAMP)
    private $conn; //This will hold the active PDO connection object

    public function __construct(){
        //Load credentials from environment variables for better security
        $this->host=$_ENV['DB_HOST'];
        $this->db_name=$_ENV['DB_NAME'];
        $this->username=$_ENV['DB_USER'];
        $this->password=$_ENV['DB_PASS'];
        $this->port=$_ENV['DB_PORT'];
    }

    //The main method to establish and return the database connection
    public function getConnection(){
        $this->conn=null;
        try{

            //Create a new PDO instance using the DSN (Data Source Name) string
            $this->conn=new PDO("mysql:host=".$this->host.";port=".$this->port.";dbname=".$this->db_name,$this->username,$this->password);
            //Full Unicode support(supports Greek and English)
            $this->conn->exec("set names utf8mb4");

            //Configure PDO to throw Exceptions when a database occurs error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            //Set the default fetch mode to Associative Array(return column names)
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }catch (PDOException $e){
            //If a connection fails,log the error message to the server's error log
            error_log("Connection error: ".$e->getMessage());
            return null;
        }
        //Return the connection object to be used by our functions
        return $this->conn;
    }
}
