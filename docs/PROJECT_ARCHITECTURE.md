# DALT Project Architecture

DALT is an interactive backend debugging playground that teaches developers how web frameworks work by fixing intentionally broken systems.

## Project Vision

Instead of just reading about how frameworks work, learners will:
1. Study a concept through a lesson
2. Debug a broken implementation of that concept
3. Understand the system by fixing it

This "learn by debugging" approach provides deeper understanding than traditional tutorials.

## System Components

DALT consists of four main components:

### 1. Backend Framework (The Debugging Target)

**Location:** `framework/`, `public/`, `config/`, `routes/`

**Purpose:** A simple, readable PHP framework that learners will debug.

**Key Features:**
- HTTP request lifecycle
- Routing system
- Middleware pipeline
- Authentication
- Database queries
- Session management

**Design Principles:**
- Keep it simple and traceable
- No magic or hidden abstractions
- Every component fits in one file
- Clear error messages

**Stability:** The core framework must remain stable because it's the debugging target.

### 2. Lessons (Educational Content)

**Location:** `lessons/`

**Purpose:** Teach backend concepts before debugging them.

**Structure:**
```
lessons/
├── lesson-01-request-lifecycle/
│   └── README.md
├── lesson-02-routing/
│   └── README.md
├── lesson-03-middleware/
│   └── README.md
├── lesson-04-authentication/
│   └── README.md
└── lesson-05-database/
    └── README.md
```

**Content:**
- Concept explanation
- Code examples
- Framework file references
- Visual diagrams
- Preparation for challenges

### 3. Challenges (Broken Scenarios)

**Location:** `challenges/`

**Purpose:** Provide intentionally broken code for debugging practice.

**Structure:**
```
challenges/
├── broken-routing/
│   ├── README.md
│   ├── bug-description.md
│   └── solution.md
├── broken-auth/
├── broken-middleware/
├── broken-database/
└── broken-session/
```

**Each Challenge Contains:**
- Bug description
- Expected behavior
- Actual (broken) behavior
- Learning objective
- Debugging hints
- Solution explanation

**Challenge Flow:**
1. User reads the bug description
2. User traces the code to find the issue
3. User fixes the bug
4. System validates the fix
5. User compares with solution

### 4. Frontend UI (Vue 3 Interface)

**Location:** `frontend/` (to be implemented)

**Purpose:** Guide users through lessons and challenges.

**Features:**
- Lesson navigation
- Challenge selection
- Progress tracking
- Code editor integration
- Request lifecycle visualization
- Real-time feedback

**Technology:**
- Vue 3 for components
- Tailwind CSS v4 for styling
- Vite for development

## How Components Interact

```
┌─────────────────────────────────────────────────────────────┐
│                         User                                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    Vue Frontend                              │
│  - Displays lessons                                          │
│  - Presents challenges                                       │
│  - Tracks progress                                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  Lesson Content                              │
│  (Markdown files in lessons/)                                │
└─────────────────────────────────────────────────────────────┘

                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                 Challenge System                             │
│  - Loads broken scenario                                     │
│  - User debugs the backend                                   │
│  - System validates fix                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  PHP Backend                                 │
│  (The system being debugged)                                 │
│  - Runs with intentional bugs                                │
│  - User fixes the bugs                                       │
│  - Learns by understanding the fix                           │
└─────────────────────────────────────────────────────────────┘
```

## User Journey

### Step 1: Choose a Lesson
User selects a lesson from the frontend (e.g., "Routing")

### Step 2: Study the Concept
Frontend displays lesson content explaining how routing works

### Step 3: Select a Challenge
User chooses the corresponding challenge (e.g., "Broken Routing")

### Step 4: Read Bug Description
Frontend shows what's broken and what should happen

### Step 5: Debug the Backend
User opens framework files and traces the bug

### Step 6: Fix the Bug
User modifies the code to fix the issue

### Step 7: Test the Fix
User runs the application to verify it works

### Step 8: Compare Solution
Frontend reveals the solution and explanation

### Step 9: Move to Next Challenge
User progresses to the next lesson/challenge

## Learning Progression

```
Lesson 1: Request Lifecycle
    ↓
Challenge: Broken Request Handling
    ↓
Lesson 2: Routing
    ↓
Challenge: Broken Routing
    ↓
Lesson 3: Middleware
    ↓
Challenge: Broken Middleware
    ↓
Lesson 4: Authentication
    ↓
Challenge: Broken Auth
    ↓
Lesson 5: Database
    ↓
Challenge: Broken Database
```

## Technical Architecture

### Backend (PHP)
- **Framework:** Custom minimal PHP framework
- **Database:** SQLite (zero setup)
- **Server:** PHP built-in server
- **Dependencies:** Composer packages

### Frontend (Vue 3)
- **Framework:** Vue 3 with Composition API
- **Styling:** Tailwind CSS v4
- **Build:** Vite
- **State:** Pinia (for progress tracking)

### Deployment (Future)
- **Container:** Docker for easy setup
- **Volumes:** Persist user progress
- **Ports:** PHP server + Vite dev server

## File Organization

```
DALT.PHP/
├── framework/          # Core PHP framework (debugging target)
├── lessons/            # Educational content
├── challenges/         # Broken scenarios
├── frontend/           # Vue UI (to be built)
├── docs/               # Documentation
├── public/             # Web root
├── resources/          # Views and assets
├── routes/             # Route definitions
├── config/             # Configuration
├── database/           # Migrations
└── storage/            # Logs and data
```

## Development Phases

### Phase 1: Foundation (Current)
- ✅ Framework architecture documented
- ✅ Directory structure created
- ✅ Lesson/challenge skeleton prepared
- ⏳ Core framework stable

### Phase 2: Content Creation
- Create lesson content
- Write challenge scenarios
- Implement broken code
- Write solutions

### Phase 3: Frontend Development
- Build Vue UI
- Implement lesson viewer
- Create challenge interface
- Add progress tracking

### Phase 4: Integration
- Connect frontend to backend
- Implement validation system
- Add real-time feedback
- Polish user experience

### Phase 5: Docker & Distribution
- Create Dockerfile
- Setup docker-compose
- Write installation guide
- Prepare for public release

## Design Principles

1. **Simplicity First** - Keep the framework simple and readable
2. **Learn by Doing** - Debugging teaches better than reading
3. **Progressive Difficulty** - Start easy, gradually increase complexity
4. **Clear Feedback** - Always tell users what's wrong and why
5. **No Magic** - Every abstraction should be understandable
6. **Beginner Friendly** - Assume no prior framework knowledge

## Success Metrics

A user successfully learns when they can:
- Trace an HTTP request through the framework
- Identify where routing happens
- Understand middleware execution order
- Debug authentication issues
- Fix database query problems
- Explain the request lifecycle

## Future Enhancements

- More advanced challenges
- Performance debugging scenarios
- Security vulnerability challenges
- API development lessons
- Testing and TDD challenges
- Deployment scenarios

---

This architecture transforms DALT.PHP from a simple framework demo into an interactive learning platform that teaches backend development through hands-on debugging.
