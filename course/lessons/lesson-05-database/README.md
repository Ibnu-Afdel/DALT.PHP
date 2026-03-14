# Lesson 05: Database System

## Overview

The database layer handles all data persistence in DALT.PHP. Understanding how queries work is essential for debugging data-related issues.

## Learning Objectives

By the end of this lesson, you will understand:
- How database connections are established
- How to execute queries safely
- The difference between `find()`, `findOrFail()`, and `get()`
- How parameter binding prevents SQL injection
- Common database issues and how to fix them

## Database Architecture

### Database Drivers

DALT.PHP supports three database drivers:
- **SQLite** (default) - Zero setup, file-based
- **PostgreSQL** - Production-ready relational database
- **MySQL** - Popular relational database

Configuration: `config/database.php`

### Database Connection

The database connection is created during bootstrap:

```php
// framework/Core/bootstrap.php
$container->bind('Core\Database', function () use ($dbConfig) {
    return DatabaseManager::create($dbConfig['database']);
});
```

Access it anywhere:
```php
$db = App::resolve(Database::class);
```

## Database Class (`framework/Core/Database.php`)

The Database class wraps PDO for simpler usage:

```php
class Database {
    public $connection;  // PDO instance
    public $statement;   // PDOStatement

    public function query($query, $params = [])
    {
        $this->statement = $this->connection->prepare($query);
        $this->statement->execute($params);
        return $this;
    }

    public function find()
    {
        return $this->statement->fetch();
    }

    public function findOrFail()
    {
        $result = $this->find();
        if (!$result) {
            abort(404);
        }
        return $result;
    }

    public function get()
    {
        return $this->statement->fetchAll();
    }
}
```

## Query Methods

### `query()` - Execute a Query

```php
$db->query('SELECT * FROM posts WHERE id = :id', ['id' => 1]);
```

**Returns:** `$this` (for method chaining)

### `find()` - Fetch One Row

```php
$post = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => 1])
           ->find();

// Result: ['id' => 1, 'title' => 'Hello', 'body' => 'World']
// Or: false if not found
```

**Returns:** Associative array or `false`

### `findOrFail()` - Fetch One Row or Abort

```php
$post = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => 1])
           ->findOrFail();

// Result: ['id' => 1, 'title' => 'Hello', 'body' => 'World']
// Or: Aborts with 404 if not found
```

**Returns:** Associative array or aborts

**Use when:** You expect the record to exist (e.g., showing a post)

### `get()` - Fetch All Rows

```php
$posts = $db->query('SELECT * FROM posts')->get();

// Result: [
//     ['id' => 1, 'title' => 'First'],
//     ['id' => 2, 'title' => 'Second'],
// ]
```

**Returns:** Array of associative arrays

## Query Examples

### Select One Record

```php
$user = $db->query('SELECT * FROM users WHERE email = :email', [
    'email' => 'user@example.com'
])->find();

if ($user) {
    echo $user['email'];
} else {
    echo 'User not found';
}
```

### Select All Records

```php
$posts = $db->query('SELECT * FROM posts ORDER BY created_at DESC')->get();

foreach ($posts as $post) {
    echo $post['title'];
}
```

### Insert Record

```php
$db->query('INSERT INTO posts (title, body, user_id) VALUES (:title, :body, :user_id)', [
    'title' => 'New Post',
    'body' => 'Content here',
    'user_id' => 1
]);
```

### Update Record

```php
$db->query('UPDATE posts SET title = :title WHERE id = :id', [
    'title' => 'Updated Title',
    'id' => 1
]);
```

### Delete Record

```php
$db->query('DELETE FROM posts WHERE id = :id', [
    'id' => 1
]);
```

### Complex Query with Joins

```php
$posts = $db->query('
    SELECT posts.*, users.email 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.published = :published
    ORDER BY posts.created_at DESC
', [
    'published' => 1
])->get();
```

## Parameter Binding (SQL Injection Prevention)

### ❌ WRONG - SQL Injection Vulnerable

```php
// NEVER DO THIS!
$id = $_GET['id'];
$db->query("SELECT * FROM posts WHERE id = $id");

// Attacker can inject: ?id=1 OR 1=1
// Query becomes: SELECT * FROM posts WHERE id = 1 OR 1=1
// Returns all posts!
```

### ✅ CORRECT - Parameter Binding

```php
$id = $_GET['id'];
$db->query('SELECT * FROM posts WHERE id = :id', [
    'id' => $id
]);

// PDO escapes the value safely
// Even if $id = "1 OR 1=1", it's treated as a string
```

**Always use parameter binding for user input!**

## Database Configuration

### SQLite (Default)

```php
// config/database.php
return [
    'database' => [
        'driver' => 'sqlite',
        'database' => 'public/database/app.sqlite'
    ]
];
```

### PostgreSQL

```php
return [
    'database' => [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'dbname' => 'dalt_php_app',
        'username' => 'postgres',
        'password' => '',
        'charset' => 'utf8'
    ]
];
```

### MySQL

```php
return [
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'dalt_php_app',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]
];
```

## Migrations

Migrations create database tables:

```php
// database/migrations/001_create_users_table.php
public function up()
{
    $this->db->query('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
}
```

Run migrations:
```bash
php artisan migrate
```

## Common Database Issues

### Issue 1: Query Returns No Results
**Cause:** Wrong SQL syntax or no matching records  
**Debug:** Check SQL query and parameters

### Issue 2: "Table doesn't exist"
**Cause:** Migrations not run  
**Fix:** Run `php artisan migrate`

### Issue 3: "Database connection failed"
**Cause:** Wrong configuration  
**Debug:** Check `config/database.php`

### Issue 4: SQL Injection Vulnerability
**Cause:** Not using parameter binding  
**Fix:** Always use `:placeholder` syntax

### Issue 5: "Call to a member function on null"
**Cause:** `find()` returned `false`, then accessing array key  
**Fix:** Use `findOrFail()` or check if result exists

## Debugging Database Queries

### Technique 1: Dump Query Result
```php
$result = $db->query('SELECT * FROM posts')->get();
dd($result);
```

### Technique 2: Check PDO Statement
```php
$db->query('SELECT * FROM posts WHERE id = :id', ['id' => 1]);
dd($db->statement);
```

### Technique 3: Verify Parameters
```php
$params = ['id' => $_GET['id']];
dd($params);
$db->query('SELECT * FROM posts WHERE id = :id', $params);
```

### Technique 4: Test Query in SQLite CLI
```bash
sqlite3 public/database/app.sqlite
sqlite> SELECT * FROM posts;
```

## Best Practices

1. **Always use parameter binding** - Never concatenate user input
2. **Use `findOrFail()` for expected records** - Automatic 404 handling
3. **Check for `false` with `find()`** - Handle missing records gracefully
4. **Use transactions for multiple queries** - Ensure data consistency
5. **Index frequently queried columns** - Improve performance

## Key Files

- **`framework/Core/Database.php`** - Query execution
- **`framework/Core/DatabaseManager.php`** - Connection setup
- **`config/database.php`** - Database configuration
- **`database/migrations/`** - Table definitions

## Practice Exercise

1. Create a new migration for a `posts` table
2. Insert a post using `query()`
3. Fetch all posts using `get()`
4. Fetch one post using `findOrFail()`
5. Update the post
6. Delete the post

## Next Steps

- **Challenge: Broken Database** - Debug query issues
- Apply your knowledge to real debugging scenarios

## Summary

The database system in DALT.PHP:
1. **Connection** - Established during bootstrap
2. **Query execution** - Via `query()` method with parameter binding
3. **Result fetching** - `find()`, `findOrFail()`, or `get()`
4. **Security** - Parameter binding prevents SQL injection
5. **Configuration** - Supports SQLite, PostgreSQL, MySQL

Understanding the database layer is essential for building data-driven applications and debugging query issues.
