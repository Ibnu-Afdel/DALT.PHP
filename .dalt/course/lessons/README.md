# Lessons

This directory contains educational lessons that teach backend web development concepts.

Each lesson explains a specific concept in the DALT.PHP framework and prepares learners to debug related challenges.

## Structure

Each lesson is a self-contained directory with:
- `meta.json` - **Required.** Validated metadata (title, description, order, icon, color, prerequisites)
- `README.md` - Lesson content in Markdown

### Adding a New Lesson

1. Create a folder: `lessons/NN-topic-name/` (e.g. `06-validation`)
2. Add `meta.json`:
   ```json
   {
     "title": "Validation",
     "description": "Learn how to validate user input",
     "order": 18,
     "icon": "middleware",
     "color": "blue",
     "prerequisites": ["03-middleware"]
   }
   ```
   Icon keys: `lifecycle`, `routing`, `middleware`, `auth`, `database`, `session`, `docker`, `shield`, `eye`
3. Add `README.md`. The platform auto-discovers it.

`order` must be a unique positive integer. Every prerequisite must name an existing lesson with an earlier order. Invalid metadata stops catalog loading with an error that identifies the item and field; it is never silently skipped.

## Lesson Flow

Catalog display and previous/next navigation follow explicit `order`, not directory enumeration. Learning dependencies follow each lesson's `prerequisites`; this supports the framework, Docker, and PostgreSQL paths without pretending they form one strictly linear track.

The current catalog contains 17 lessons. `meta.json` is the authoritative sequence and dependency model, so this contributor guide does not duplicate a list that can become stale.

## Learning Approach

Each lesson:
1. Explains the concept clearly
2. Shows the relevant framework code
3. Demonstrates with examples
4. Prepares you for debugging challenges

After completing a lesson, use the challenges whose `lesson` field links to it. A lesson may have zero, one, or several related challenges.
