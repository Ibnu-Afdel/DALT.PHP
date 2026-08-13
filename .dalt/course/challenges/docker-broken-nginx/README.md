# Challenge: Broken Nginx Config

## Difficulty: Easy — 2 missing directives

## What This Challenge Is

The Docker stack has Nginx as the web server and PHP-FPM handling PHP execution. Nginx receives HTTP requests and needs to forward `.php` files to PHP-FPM — but the configuration is missing the two directives that make this happen.

Without these, Nginx serves `.php` files as plain text (or returns a 502 Bad Gateway error), and DALT.PHP never runs.

Run the challenge to load the broken config into your project:

```bash
php artisan challenge:start docker-broken-nginx
```

Then open `nginx/default.conf` in your editor and add the two missing directives inside the `location ~ \.php$` block.

## Background: How Nginx and PHP-FPM Work Together

Nginx does not run PHP. It's a web server that handles HTTP. When a request arrives for a `.php` file, Nginx uses the FastCGI protocol to hand it off to PHP-FPM, which does run PHP.

```
Browser
  │  HTTP request: GET /index.php
  ▼
Nginx (nginx:alpine container, port 80)
  │  FastCGI: "here's a PHP file to run"
  ▼
PHP-FPM (app container, port 9000)
  │  Runs the PHP code
  ▼
Response back through Nginx to browser
```

The `location ~ \.php$` block in the Nginx config is where you configure this handoff.

## The Two Missing Directives

### 1. `fastcgi_pass`

Tells Nginx where to send PHP requests. In Docker Compose, containers talk to each other using service names as hostnames.

Your `docker-compose.yml` will have a service named `app` (the PHP-FPM container). PHP-FPM listens on port `9000` by default.

The directive:
```nginx
fastcgi_pass app:9000;
```

### 2. `fastcgi_param SCRIPT_FILENAME`

Tells PHP-FPM the full filesystem path of the PHP file to execute. Without this, PHP-FPM doesn't know which file to run.

The document root inside the `app` container is `/var/www/html/public` (matching the `WORKDIR` + `public/` subdirectory). The `$fastcgi_script_name` variable is Nginx's built-in variable for the script path.

The directive:
```nginx
fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name;
```

## Files Involved

- `nginx/default.conf` — the only file you need to edit

Both directives go inside the `location ~ \.php$ { ... }` block, after `include fastcgi_params;`.

## Verify Your Solution

```bash
php artisan challenge:verify
```

The verifier checks:
- `fastcgi_pass app:9000;` is present
- `SCRIPT_FILENAME` is set
- No `# TODO` comments remain

These confirm the config's *shape* — the right directives, in the right form — not that Nginx actually loads it or forwards a request correctly. Nginx isn't running during `challenge:verify`; there's no container to test against yet. See "Testing With the Full Stack" below to confirm the real behavior once you have one.

## Testing With the Full Stack

After verifying, you can test the full stack once you have a `docker-compose.yml` (covered in Lesson 08). For now, confirming the verifier passes is the goal.

## Common Mistakes

**Wrong service name in fastcgi_pass:**
If your PHP-FPM container is named differently in `docker-compose.yml`, the hostname must match. The convention is `app`.

**Missing semicolons:**
Nginx configs require a semicolon at the end of every directive. Forgetting one causes the entire Nginx config to fail to load.

**`$document_root$fastcgi_script_name` pattern:**
Some tutorials use `fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name`. This works if `root` is set correctly, but the explicit path `/var/www/html/public$fastcgi_script_name` is more reliable inside Docker where paths must be unambiguous.

## What Happens If You Get It Wrong

**No `fastcgi_pass`:** Nginx doesn't know where to send PHP requests. You'll see a `502 Bad Gateway` error.

**No `SCRIPT_FILENAME`:** PHP-FPM receives the request but can't find the file. You'll see a blank page or a PHP error about the script not being found.

## Next Steps

After completing both Phase 1 challenges:
- **Lesson 08: Docker Compose** — tie all three containers together into one command: `docker compose up`
