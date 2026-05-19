# Blog Microservice Infrastructure

Docker Compose середовище для pet project з одним gateway Nginx перед сервісами.

## Вхідні URL

```text
Public blog UI: http://localhost:8000
Admin UI:       http://localhost:8000/admin/login
Auth API:       http://localhost:8000/api/auth/*
Blog API:       http://localhost:8000/api/blog/*
```

Gateway маршрути:

```text
/api/auth/*  -> auth-nginx
/api/blog/*  -> blog-nginx
/admin/*     -> admin-nginx
/*           -> web-nginx
```

## Структура

```text
.
├── docker/
│   ├── gateway/
│   │   └── default.conf
│   ├── nginx/
│   │   ├── admin/default.conf
│   │   ├── auth/default.conf
│   │   ├── blog/default.conf
│   │   └── web/default.conf
│   └── php-fpm/
│       └── Dockerfile
├── services/
│   ├── admin/
│   ├── auth/
│   ├── blog/
│   └── web/
└── docker-compose.yml
```

## Сервіси

| Сервіс | Docker host | Локальний порт | Призначення |
| --- | --- | --- | --- |
| Gateway Nginx | `gateway-nginx` | `8000` | Єдиний зовнішній entrypoint |
| Web Nginx | `web-nginx` | - | Публічний UI блогу |
| Web PHP-FPM | `web-php-fpm` | - | Laravel frontend для блогу |
| Auth Nginx | `auth-nginx` | - | Внутрішній Nginx для `auth` |
| Auth PHP-FPM | `auth-php-fpm` | - | Laravel API авторизації |
| Blog Nginx | `blog-nginx` | - | Внутрішній Nginx для `blog` |
| Blog PHP-FPM | `blog-php-fpm` | - | Laravel API постів, категорій, тегів, коментарів |
| Admin Nginx | `admin-nginx` | - | Внутрішній Nginx для `admin` |
| Admin PHP-FPM | `admin-php-fpm` | - | Laravel Blade адмінка |
| Blog MySQL | `mysql` | `3306` | База `blog` |
| Auth MySQL | `auth-mysql` | `3307` | База `auth` |
| Admin MySQL | `admin-mysql` | `3308` | База `admin` |
| RabbitMQ | `rabbitmq` | `5672`, `15672` | Черги і UI |
| Elasticsearch | `elasticsearch` | `9200` | Пошук, буде підключатись пізніше |

## Запуск

```bash
cp .env.example .env
docker compose up -d --build
docker compose ps
```

Після першого запуску:

```bash
docker compose exec auth-php-fpm php artisan migrate
docker compose exec blog-php-fpm php artisan migrate
docker compose exec admin-php-fpm php artisan migrate
```

## Public Web

`web` сервіс лежить у `services/web` і не має власної бізнес-бази. Він зберігає тільки файлову session і ходить у внутрішні API:

```env
AUTH_SERVICE_URL=http://auth-nginx
BLOG_SERVICE_URL=http://blog-nginx
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Поточні сторінки:

```text
GET  /
GET  /posts
GET  /posts/{post}
GET  /categories/{category}
GET  /login
POST /login
GET  /register
POST /register
POST /logout
GET  /write
POST /write
POST /posts/{post}/comments
```

Нові автори реєструються через `auth` сервіс. Статті створюються як `draft`, а публікація лишається за адмінкою.

## Admin UI

```text
http://localhost:8000/admin/login
```

Локальний admin-користувач:

```text
email: admin@example.com
password: password123
```

Поточні розділи:

```text
Dashboard
Posts
Comments
Categories
Tags
```

## Auth API

```text
POST /api/auth/register
POST /api/auth/login
GET  /api/auth/me
POST /api/auth/logout
```

Публічна реєстрація завжди створює користувача з роллю `author`. Роль `admin` не можна отримати через `/api/auth/register`.

## Blog API

```text
GET    /api/blog/categories
POST   /api/blog/categories
GET    /api/blog/categories/{category}
PUT    /api/blog/categories/{category}
PATCH  /api/blog/categories/{category}
DELETE /api/blog/categories/{category}

GET    /api/blog/tags
POST   /api/blog/tags
GET    /api/blog/tags/{tag}
PUT    /api/blog/tags/{tag}
PATCH  /api/blog/tags/{tag}
DELETE /api/blog/tags/{tag}

GET    /api/blog/posts
POST   /api/blog/posts
GET    /api/blog/posts/{post}
PUT    /api/blog/posts/{post}
PATCH  /api/blog/posts/{post}
DELETE /api/blog/posts/{post}

GET    /api/blog/posts/{post}/comments
POST   /api/blog/posts/{post}/comments
GET    /api/blog/comments?status=pending|approved|rejected
PUT    /api/blog/comments/{comment}
PATCH  /api/blog/comments/{comment}
DELETE /api/blog/comments/{comment}

PATCH  /api/blog/posts/{post}/publish
PATCH  /api/blog/posts/{post}/archive
PATCH  /api/blog/comments/{comment}/approve
PATCH  /api/blog/comments/{comment}/reject
```

Write endpoints у `blog` потребують Bearer token з `auth`. `author_id` для posts/comments береться автоматично з `/api/auth/me`.

## Тести

```bash
docker compose exec auth-php-fpm php artisan test
docker compose exec blog-php-fpm php artisan test
docker compose exec admin-php-fpm php artisan test
docker compose exec web-php-fpm php artisan test
```

## Корисні команди

```bash
docker compose exec web-php-fpm php artisan optimize:clear
docker compose exec web-php-fpm composer install
docker compose logs -f gateway-nginx
docker compose logs -f web-nginx
docker compose logs -f web-php-fpm
docker compose down
docker compose down -v
```
