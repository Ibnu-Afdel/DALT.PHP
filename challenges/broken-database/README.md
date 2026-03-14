# Challenge: Broken Database

## Difficulty: Beginner

## Description

Database queries are failing or returning unexpected results. Some queries return no data, while others cause SQL errors.

## Bug Symptoms

1. Queries return empty results even though data exists
2. SQL injection vulnerability in user input handling
3. `findOrFail()` doesn't abort when record is missing
4. Parameter binding is not working correctly

## Expected Behavior

- Queries should return correct results
- User input should be safely escaped via parameter binding
- `findOrFail()` should abort with 404 when record doesn't exist
- All queries should use prepared statements

## Learning Objective

Understand how database queries work, parameter binding prevents SQL injection, and result fetching methods differ.

## Files to Investigate

- `framework/Core/Database.php` - Query execution and result fetching
- `framework/Core/DatabaseManager.php` - Connection setup
- `Http/controllers/posts/show.php` - Example controller with queries
- `config/database.php` - Database configuration

## Debugging Hints

1. Check if parameter binding is used (`:placeholder` syntax)
2. Verify the difference between `find()`, `findOrFail()`, and `get()`
3. Look for string concatenation in queries (SQL injection risk)
4. Add `dd($db->statement)` after queries to inspect PDOStatement
5. Test queries directly in SQLite CLI to verify syntax

## Questions to Ask

- Are all user inputs using parameter binding?
- What's the difference between `find()` and `findOrFail()`?
- How does PDO prevent SQL injection?
- What does `query()` return?
- When should you use `get()` vs `find()`?

## Success Criteria

- All queries use parameter binding
- No SQL injection vulnerabilities
- `findOrFail()` aborts correctly when record is missing
- Queries return expected results
- Code follows security best practices

## Related Lesson

**Lesson 05: Database System**

## Solution

(Hidden until you attempt the challenge)

The solution will be revealed after you've debugged the issue.
