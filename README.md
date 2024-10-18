# Advanced Task Management API

## Introduction

The Advanced Task Management API provides a robust framework for managing tasks within projects, incorporating advanced features such as task dependencies, real-time notifications, and comprehensive security measures. This API supports various task types, including Bugs, Features, and Improvements, and facilitates detailed performance analysis through periodic reporting.

Built with Laravel, this API leverages JWT for secure authentication, includes mechanisms to protect against common security vulnerabilities, and manages user roles and permissions effectively.

## Key Features

### 1. **Task Management**
- **Task Creation and Updates**: Create and update tasks with attributes like title, description, type, status, priority, and due date.
- **Task Dependencies**: Automatically set a task’s status to "Blocked" if it depends on another task that is incomplete.
- **Auto Reset**: Change the status of dependent tasks from "Blocked" to "Open" when their prerequisites are completed.
- **Soft Delete**: Support for soft deletion of tasks, allowing restoration while retaining historical data.

### 2. **Commenting and Attachments**
- **Comments**: Add comments to tasks for improved collaboration.
- **Attachments**: Upload and manage file attachments securely, including encryption and virus checking.

### 3. **User and Role Management**
- **User Assignment**: Assign tasks to users and manage user roles to control access permissions.
- **Role-Based Permissions**: Implement a role management system to define specific permissions for users based on their roles.

### 4. **Reporting and Performance Analysis**
- **Daily Task Reports**: Generate periodic reports for analysis.
- **Multiple Report Types**: Support for generating various reports, including completed tasks, overdue tasks, and tasks by user.

### 5. **Advanced Security Features**
- **JWT Authentication**: Secure access to the API using JWT tokens.
- **Rate Limiting**: Protect the API from DDoS attacks through rate limiting.
- **CSRF Protection**: Ensure protection against Cross-Site Request Forgery (CSRF) attacks.
- **XSS and SQL Injection Protection**: Utilize Laravel's built-in mechanisms to prevent XSS and SQL Injection attacks.

---

## API Endpoints

### Authentication Endpoints
- **POST /api/auth/login**  
  Authenticate a user and issue a JWT token for further API requests.

- **POST /api/auth/logout**  
  Log out the currently authenticated user and invalidate the token.

### Task Endpoints
- **POST /api/tasks**  
  Create a new task.

- **PUT /api/tasks/{id}/status**  
  Update the status of a specific task.

- **PUT /api/tasks/{id}/reassign**  
  Reassign a task to a different user.

- **POST /api/tasks/{id}/comments**  
  Add a comment to a specific task.

- **POST /api/tasks/{id}/attachments**  
  Attach a file to a task.

- **GET /api/tasks/{id}**  
  Retrieve detailed information about a specific task.

- **GET /api/tasks**  
  View all tasks with advanced filters (e.g., type, status, due date, priority).

- **POST /api/tasks/{id}/assign**  
  Assign a task to a user.

- **GET /api/reports/daily-tasks**  
  Generate a daily report of tasks.

- **GET /api/tasks?status=Blocked**  
  View overdue and pending tasks due to dependencies.

### Additional Endpoints
- **GET /api/users**  
  Retrieve a list of all users.

- **POST /api/users**  
  Create a new user in the system.

- **GET /api/roles**  
  Retrieve a list of all roles.

---

## Database Structure

### Tasks Table
| Column Name   | Data Type | Description                               |
|---------------|-----------|-------------------------------------------|
| `id`          | Integer   | Primary key                               |
| `title`       | String    | Title of the task                         |
| `description` | Text      | Detailed description of the task          |
| `type`        | String    | Type of task (Bug, Feature, Improvement)  |
| `status`      | String    | Current status of the task                |
| `priority`    | String    | Task priority (Low, Medium, High)         |
| `due_date`    | DateTime  | Due date for the task                     |
| `assigned_to` | Integer   | Foreign key referencing the user assigned |

### Users Table
| Column Name | Data Type | Description               |
|-------------|-----------|---------------------------|
| `id`        | Integer   | Primary key               |
| `name`      | String    | User's full name          |
| `email`     | String    | Unique email for the user |
| `password`  | String    | Hashed password           |

### Comments Table
| Column Name        | Data Type | Description                                                                        |
|--------------------|-----------|------------------------------------------------------------------------------------|
| `id`               | Integer   | Primary key for the comments table                                                 |
| `content`          | String    | The content of the comment                                                         |
| `commentable_id`   | Integer   | ID of the parent model (e.g., task) the comment belongs to (generated by `morphs`) |
| `commentable_type` | String    | Type of the parent model (e.g., Task) (generated by `morphs`)                      |
| `user_id`          | Integer   | Foreign key referencing the `id` column in the `users` table                       |

### Attachments Table
| Column Name       | Data Type | Description                                                                           |
|-------------------|-----------|---------------------------------------------------------------------------------------|
| `id`              | Integer   | Primary key for the attachments table                                                 |
| `file_path`       | String    | Path to the uploaded file                                                             |
| `attachable_id`   | Integer   | ID of the parent model (e.g., task) the attachment belongs to (generated by `morphs`) |
| `attachable_type` | String    | Type of the parent model (e.g., Task) (generated by `morphs`)                         |
| `user_id`         | Integer   | Foreign key referencing the `id` column in the `users` table                          |

### Task Dependencies Table
| Column Name  | Data Type | Description                                                                                                                             |
|--------------|-----------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `id`         | Integer   | Primary key for the task_dependencies table                                                                                             |
| `task_id`    | Integer   | Foreign key referencing the `id` column in the `tasks` table, indicating the task that has dependencies                                 |
| `depends_on` | Integer   | Foreign key referencing the `id` column in the `tasks` table, indicating the task that must be completed before the task_id can proceed |
| `created_at` | Timestamp | The date and time the task dependency record was created                                                                                |
| `updated_at` | Timestamp | The date and time the task dependency record was last updated                                                                           |





### Comments and Attachments
- **Comments**: Polymorphic relationship to store comments associated with tasks.
- **Attachments**: Polymorphic relationship to handle attached files securely.

---

## Relationships
- **Tasks and Users**: `belongsTo` relationship indicating the user assigned to a task.
- **Tasks and Comments/Attachments**: Polymorphic relationships for comments and attachments.

---

## Installation and Setup

### Prerequisites
- PHP >= 8.0
- Composer
- Laravel >= 9.x
- MySQL or another compatible database

### Installation Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/Dralve/Advanced-Task-Management-Api.git

2. **Navigate to the Project Directory**

    ```bash
    cd Advanced-Task-Management-Api
    ```

3. **Install Dependencies**

    ```bash
    composer install
    ```

4. **Set Up Environment Variables**

   Copy the `.env.example` file to `.env` and configure your database and other environment settings.

    ```bash
    cp .env.example .env
    ```

   Update the `.env` file with your database credentials and other configuration details.


5. **Run Migrations**

    ```bash
    php artisan migrate
    ```

6. **Seed the Database**

    ```bash
    php artisan db:seed
    ```

7. **Start the Development Server**

    ```bash
    php artisan serve
    ```

## Error Handling

Customized error messages and responses are provided to ensure clarity and user-friendly feedback.

## Documentation

All code is documented with appropriate comments and DocBlocks. For more details on the codebase, refer to the inline comments.

## Contributing

Contributions are welcome! Please follow the standard pull request process and adhere to the project's coding standards.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
