# Frontend

This directory will contain the Vue 3 UI that guides users through lessons and challenges.

## Purpose

The frontend provides:
- Interactive lesson navigation
- Challenge selection and progress tracking
- Code editor integration
- Real-time feedback on debugging attempts
- Visual representation of the request lifecycle

## Technology Stack

- **Vue 3** - Component framework
- **Tailwind CSS v4** - Styling
- **Vite** - Build tool and dev server

## Structure (To Be Implemented)

```
frontend/
├── src/
│   ├── components/     # Vue components
│   ├── views/          # Page views
│   ├── router/         # Vue Router configuration
│   ├── stores/         # State management
│   └── App.vue         # Root component
├── public/             # Static assets
└── package.json        # Dependencies
```

## Integration with Backend

The Vue frontend will:
1. Display lesson content from `lessons/`
2. Present challenges from `challenges/`
3. Communicate with the PHP backend via API
4. Show real-time debugging feedback
5. Track user progress

## Development

(To be documented when frontend is implemented)

```bash
cd frontend
npm install
npm run dev
```

## Note

This directory is currently a placeholder. The frontend will be built in a future milestone.
