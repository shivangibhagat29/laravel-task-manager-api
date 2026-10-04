# Laravel Task Manager REST API

![tests](https://github.com/shivangibhagat29/laravel-task-manager-api/actions/workflows/tests.yml/badge.svg)

A versioned REST API (projects → tasks) built with **Laravel + Sanctum**, written the way a mobile or web app backend should be: token auth, ownership rules, validation, filtering, pagination and tests.

It is a clean demo written for this portfolio. Client code I've worked on is private.

## What it shows

| Area | How it's done |
|---|---|
| Auth | Laravel **Sanctum** bearer tokens (register / login / logout / me), per-device tokens |
| Authorization | `ProjectPolicy`: users only see and change their own data (403 otherwise) |
| Nested resources | `/projects/{project}/tasks/{task}` with **scoped bindings**, so a task from another project returns 404 |
| Validation | Form Requests, enum validation (`Rule::enum`), password rules |
| Output control | API Resources, so no internal columns leak |
| Querying | Filter by `status`, `overdue`, `search`; whitelisted `sort`; capped pagination; `withCount` to avoid N+1 |
| Data model | Enums for status/priority, FK cascades, composite index `(project_id, status)` |
| Security | Rate limiting (6/min on login/register, 60/min on the API), same error for wrong email or wrong password |
| Tests | 20 feature tests (auth, ownership, filters, validation); CI on GitHub Actions |

## Endpoints (`/api/v1`)

| Method | Endpoint | Auth |
|---|---|---|
| POST | `/register` | – |
| POST | `/login` | – |
| GET | `/me` | Bearer |
| POST | `/logout` | Bearer |
| GET / POST | `/projects` | Bearer |
| GET / PATCH / DELETE | `/projects/{id}` | Bearer |
| GET / POST | `/projects/{id}/tasks` | Bearer |
| GET / PATCH / DELETE | `/projects/{id}/tasks/{taskId}` | Bearer |

Query examples: `?status=todo`, `?overdue=1`, `?sort=-due_date`, `?per_page=10`, `/projects?search=web`

## Try it

```bash
# login (seeded user)
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Accept: application/json" -d "email=demo@example.com&password=password"

# use the token
curl http://127.0.0.1:8000/api/v1/projects \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

## Run locally

```bash
git clone https://github.com/shivangibhagat29/laravel-task-manager-api.git
cd laravel-task-manager-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
php artisan serve
```

## Key files

- `routes/api.php`: versioned routes, rate limits
- `app/Http/Controllers/Api/*`: Auth, Project, Task controllers
- `app/Policies/ProjectPolicy.php`: ownership rules
- `app/Http/Requests/*`, `app/Http/Resources/*`: validation and output
- `tests/Feature/*`: auth, project and task tests

## Author

**Shivangi**, Senior PHP/Laravel Developer · [LinkedIn](https://www.linkedin.com/in/shivangi-bhagat)
