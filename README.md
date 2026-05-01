# PHP Task Manager API

A simple RESTful Task Manager API built with PHP, MySQL and JWT authentication.  
Users can register, log in, and manage their own tasks securely.

---

## 🚀 Features

- User registration
- User login with JWT authentication
- Secure password hashing (BCRYPT)
- Protected routes using JWT
- Full CRUD for tasks (Create, Read, Update, Delete)
- Task ownership per user (authorization enforced)
- PDO database connection
- Environment configuration with Dotenv
- Seeder with Faker (dummy data)
- CORS support (frontend/mobile ready)

---

## 🛠 Technologies Used

- PHP
- MySQL
- PDO
- Composer
- Firebase JWT
- Dotenv
- Faker

---

## ⚙️ Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/task-manager-api.git
cd task-manager-api
2. Install dependencies
composer install
3. Create .env file
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=root
DB_PASS=your_password
DB_PORT=3306
```


JWT_SECRETKEY=your_secret_key

🗄 Database Setup
Run the following SQL:
```bash
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```


▶️ Run the Project

Using PHP built-in server:
```bash
php -S localhost:8000 -t public
```

🔗 API Endpoints
🧑 Register User

```bash
POST /user/register
{
  "username": "john",
  "email": "john@example.com",
  "password": "123456"
}
```
🔐 Login User
POST /user/login
```bash
{
  "email": "john@example.com",
  "password": "123456"
}
```

Response:
```bash
{
  "status": true,
  "token": "JWT_TOKEN"
}
```

📋 Get Tasks
GET /tasks

Header:

Authorization: Bearer YOUR_TOKEN

➕ Create Task
POST /tasks

Header:

Authorization: Bearer YOUR_TOKEN
```bash
{
  "title": "Finish project",
  "description": "Complete API",
  "status": "pending",
  "priority": "high",
  "due_date": "2026-06-01"
}
```
✏️ Update Task
PUT /tasks?id=1

Header:

Authorization: Bearer YOUR_TOKEN
```bash
{
  "title": "Updated task",
  "description": "Updated description",
  "status": "in_progress",
  "priority": "medium",
  "due_date": "2026-06-10"
}
```

❌ Delete Task
DELETE /tasks?id=1

Header:

Authorization: Bearer YOUR_TOKEN

🌱 Seeder (Dummy Data)

Creates:

5 users
30 tasks

Run:
```bash
php database/seed.php
```

Default password for all users: 123456

📁 Project Structure
```bash
TaskManager/
├── public/
│   └── index.php
├── src/
│   ├── Config/
│   │   └── Database.php
│   └── functions.php
├── database/
│   └── seed.php
├── vendor/
├── .env
├── composer.json
└── README.md
```
🔐 Security
Passwords hashed using password_hash()
Verified with password_verify()
JWT authentication for protected routes
Prepared statements (PDO) to prevent SQL Injection
Input sanitization against XSS

⚠️ Important Note
Users can only access and manage their own tasks through JWT authentication.

🐞 Known Issue

In index.php, the functions:

deleteTask()
updateTask()

are called with a user ID parameter but the functions do not accept it.
