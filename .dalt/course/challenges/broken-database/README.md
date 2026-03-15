# Challenge: Broken Database

## Difficulty: Beginner

## Setup Instructions

1. **Backup your current Database class:**
   ```bash
   cp framework/Core/Database.php framework/Core/Database.php.backup
   ```

2. **Copy the broken Database class:**
   ```bash
   cp challenges/broken-database/framework/Core/Database.php framework/Core/
   ```

3. **Copy the controller files:**
   ```bash
   cp -r challenges/broken-database/Http/controllers/posts Http/controllers/
   ```

4. **Add routes (append to routes/routes.php):**
   ```bash
   cat challenges/broken-database/routes/routes.php >> routes/routes.php
   ```

5. **Start the server:**
   ```bash
   php artisan serve
   ```

6. **Test the broken database:**
   - Visit http://localhost:8000/posts (SQL injection vulnerability!)
   - Visit http://localhost:8000/posts/1 (query fails!)

## Concept: How Database Queries Work

The Database class wraps PDO for executing SQL queries safely:

1. **Prepare statement** - SQL with placeholders
2. **Bind parameters** - Values safely escaped
3. **Execute query** - Run against database
4. **Fetch results** - Get data back

**Critical:** Always use parameter binding to prevent SQL injection!

## The Bugs

### Bug #1: SQL Injection Vulnerability

**Symptom:** User input is concatenated directly into SQL query.

**What's happening:**
```php
// BROKEN - SQL injection!
$query = "SELECT * FROM posts WHERE id = " . $_GET['id'];
$this->statement = $this->connection->prepare($query);
$this->statement->execute();

// CORRECT - parameter binding
$query = "SELECT * FROM posts WHERE id = :id";
$this->statement = $this->connection->prepare($query);
$this->statement->execute(['id' => $_GET['id']]);
```

**Why it's dangerous:**
```
Normal: ?id=1 → SELECT * FROM posts WHERE id = 1
Attack: ?id=1 OR 1=1 → SELECT * FROM posts WHERE id = 1 OR 1=1 (returns all!)
```

### Bug #2: Parameters Not Passed to execute()

**Symptom:** Queries with parameters fail or return no results.

**What's happening:**
```php
// BROKEN - params ignored
public function query($query, $params = [])
{
    $this->statement = $this->connection->prepare($query);
    $this->statement->execute(); // Missing $params!
    return $this;
}

// CORRECT
public function query($query, $params = [])
{
    $this->statement = $this->connection->prepare($query);
    $this->statement->execute($params); // Pass params!
    return $this;
}
```

## Learning Objectives

After fixing this challenge, you will understand:
- Why SQL injection is dangerous
- How parameter binding prevents attacks
- How PDO prepared statements work
- Why parameters must be passed to execute()
- How to debug database queries

## Debugging Hints

1. **Check the query** - Add `dd($query, $params)` before prepare()
2. **Test SQL injection** - Try `?id=1 OR 1=1` in the URL
3. **Verify parameters** - Add `dd($this->statement)` after execute()
4. **Check PDO errors** - Look for PDO exceptions

## Files to Investigate

- `framework/Core/Database.php` - Query execution (Bugs are here!)
- `Http/controllers/posts/show.php` - Uses parameter binding
- `Http/controllers/posts/index.php` - Vulnerable to SQL injection

## How to Fix

### Fix #1: Remove SQL Injection Vulnerability

Never concatenate user input into queries:
```php
// In Http/controllers/posts/index.php
// BROKEN
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM posts WHERE title LIKE '%" . $search . "%'";
$posts = $db->query($query)->get();

// FIXED
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM posts WHERE title LIKE :search";
$posts = $db->query($query, ['search' => "%$search%"])->get();
```

### Fix #2: Pass Parameters to execute()

```php
// In framework/Core/Database.php
public function query($query, $params = [])
{
    $this->statement = $this->connection->prepare($query);
    $this->statement->execute($params); // Fixed: pass $params
    return $this;
}
```

## Success Criteria

When fixed correctly:
- ✅ All queries use parameter binding
- ✅ No SQL injection vulnerabilities
- ✅ Queries with parameters work correctly
- ✅ Search functionality works safely

## Testing Your Fix

### Test SQL Injection Protection:
```bash
# Should only return post 1
curl "http://localhost:8000/posts/1"

# Should NOT return all posts (injection blocked)
curl "http://localhost:8000/posts?search=1' OR '1'='1"
```

### Test Parameter Binding:
```bash
# Should work correctly
curl "http://localhost:8000/posts?search=test"
```

## Cleanup

After completing the challenge:
```bash
# Restore original Database class
cp framework/Core/Database.php.backup framework/Core/Database.php

# Remove challenge controllers (optional)
rm -rf Http/controllers/posts
```

## Related Lesson

**Lesson 05: Database System** - Study this before attempting the challenge.

## Next Challenge

After mastering database security, try **Challenge: Broken Session**
