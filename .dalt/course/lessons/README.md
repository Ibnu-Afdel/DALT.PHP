# Lessons

This directory contains educational lessons that teach backend web development concepts.

Each lesson explains a specific concept in the DALT.PHP framework and prepares learners to debug related challenges.

## Structure

Each lesson is a self-contained directory with:
- `meta.json` - **Required.** Metadata (title, description, icon, color)
- `README.md` - Lesson content in Markdown

### Adding a New Lesson

1. Create a folder: `lessons/NN-topic-name/` (e.g. `06-validation`)
2. Add `meta.json`:
   ```json
   {
     "title": "Validation",
     "description": "Learn how to validate user input",
     "icon": "middleware",
     "color": "blue"
   }
   ```
   Icon keys: `lifecycle`, `routing`, `middleware`, `auth`, `database`, `session`
3. Add `README.md`. The platform auto-discovers it.

## Lesson Flow

Lessons should be completed in order:
1. **lesson-01-request-lifecycle** - How HTTP requests flow through the framework
2. **lesson-02-routing** - How URLs map to controllers
3. **lesson-03-middleware** - How middleware protects routes
4. **lesson-04-authentication** - How login/logout works
5. **lesson-05-database** - How database queries execute

## Learning Approach

Each lesson:
1. Explains the concept clearly
2. Shows the relevant framework code
3. Demonstrates with examples
4. Prepares you for debugging challenges

After completing a lesson, you'll be ready to tackle the corresponding challenge.
