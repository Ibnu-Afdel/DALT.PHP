<?php

return [
    'title' => 'DALT Fullstack',
    'description' => 'Build one real application across React, TypeScript, DALT and PostgreSQL.',
    'parts' => [
        '00' => ['title' => 'Web fundamentals', 'purpose' => 'Understand the browser/server system.', 'lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'], 'milestones' => [['id' => 'B00', 'title' => 'Trace the system', 'route' => '/learn/fullstack/build/b00', 'prerequisites' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa']]]],
        '01' => ['title' => 'Modern JavaScript', 'purpose' => 'Remove JavaScript friction before TypeScript and React.', 'lessons' => ['22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure'], 'milestones' => [['id' => 'B01', 'title' => 'JavaScript readiness', 'route' => '/learn/fullstack/build/b01', 'prerequisites' => ['22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure']]]],
        '02' => ['title' => 'TypeScript foundations', 'purpose' => 'Model application data with useful, explicit types.', 'lessons' => ['24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries'], 'milestones' => [['id' => 'B02', 'title' => 'Type the future application', 'route' => '/learn/fullstack/build/b02', 'prerequisites' => ['24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries']]]],
        '03' => ['title' => 'React foundations', 'purpose' => 'Build the first issue-tracker interface.', 'lessons' => ['29-fs03-1-components-jsx-and-typed-props', '30-fs03-2-state-and-events', '31-fs03-3-forms-and-state-design', '32-fs03-4-tailwind-and-accessible-ui'], 'milestones' => [['id' => 'B03', 'title' => 'The local issue tracker']]],
        '04' => ['title' => 'React and server', 'purpose' => 'Connect the interface to a server deliberately.', 'lessons' => [], 'milestones' => [['id' => 'B04', 'title' => 'First full-stack loop']]],
        '05' => ['title' => 'DALT API and PostgreSQL', 'purpose' => 'Make the application persistent and useful.', 'lessons' => [], 'milestones' => [['id' => 'B05', 'title' => 'Persistent application']]],
        '06' => ['title' => 'Testing, users and authentication', 'purpose' => 'Protect a multi-user application with evidence.', 'lessons' => [], 'milestones' => [['id' => 'B06', 'title' => 'Multi-user protected system']]],
        '07' => ['title' => 'React structure, routing and testing', 'purpose' => 'Make the frontend navigable and dependable.', 'lessons' => [], 'milestones' => [['id' => 'B07', 'title' => 'Navigable tested application']]],
        '08' => ['title' => 'Server and application state', 'purpose' => 'Choose state boundaries on purpose.', 'lessons' => [], 'milestones' => [['id' => 'B08', 'title' => 'Intentional state architecture']]],
        '09' => ['title' => 'Advanced React and tooling', 'purpose' => 'Keep a growing frontend maintainable.', 'lessons' => [], 'milestones' => [['id' => 'B09', 'title' => 'Maintainable frontend']]],
        '10' => ['title' => 'Docker', 'purpose' => 'Run the whole system in a real environment.', 'lessons' => [], 'milestones' => [['id' => 'B10', 'title' => 'Containerized full stack']]],
        '11' => ['title' => 'PostgreSQL deeper', 'purpose' => 'Make database-aware product decisions.', 'lessons' => [], 'milestones' => [['id' => 'B11', 'title' => 'Database-aware application']]],
        '12' => ['title' => 'Capstone', 'purpose' => 'Audit, explain, and freeze the finished system.', 'lessons' => [], 'milestones' => [['id' => 'C01', 'title' => 'System audit'], ['id' => 'C02', 'title' => 'Complete one workflow'], ['id' => 'C03', 'title' => 'Failure-path hardening'], ['id' => 'C04', 'title' => 'Test hardening'], ['id' => 'C05', 'title' => 'Performance review'], ['id' => 'C06', 'title' => 'Laravel comparison'], ['id' => 'C07', 'title' => 'Explain and freeze']]],
    ],
];
