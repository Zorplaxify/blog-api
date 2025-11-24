````
# Blog API

Laravel backend API for blog posts with Sanctum authentication, featuring full CRUD operations, search, filtering, and comprehensive testing.

---

## Features

- Token-based Authentication with Laravel Sanctum
- Full CRUD Operations for blog posts
- Advanced Search & Filtering by title, content, and user
- Pagination & Sorting with customizable parameters
- Caching System for improved performance
- Rate Limiting for API protection
- Comprehensive Test Suite with extensive test coverage
- RESTful API Design with proper HTTP status codes
- Postman Collection for easy testing
- Token Ability System with scope-based permissions
- Automatic Token Pruning for expired sessions

---

## Installation

### 1. Clone repository
```
git clone [your-repo-url]
cd blog-api
````

### 2. Install dependencies

```
composer install
```

### 3. Setup environment

```
cp .env.example .env
php artisan key:generate
```

### 4. Configure database in `.env`

```
DB_CONNECTION=mysql
DB_HOST=[HERE DB SERVER IP]
DB_PORT=3306
DB_DATABASE=[database name]
DB_USERNAME=[user name]
DB_PASSWORD= [user password]
```

### 5. Run migrations and seed with test data

```
php artisan migrate --seed
```

### 6. Start development server

```
php artisan serve
```

### 7. Run tests to verify installation

```
php artisan test
```
---

## Authentication

### Test User (Auto-created by seeder)

* Email: `test[unique_id]@example.com` (auto-generated)
* Password: `password` (factory default)

### Register New User

**POST** `/api/auth/register`
Content-Type: `application/json`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

### Login (Get Token)

**POST** `/api/auth/login`
Content-Type: `application/json`

```json
{
  "email": "test@example.com",
  "password": "Password123!"
}
```

Response includes authentication token:

```json
{
  "token": "your_api_token_here",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com"
  }
}
```

### Logout

**POST** `/api/auth/logout`
Authorization: `Bearer your_api_token_here`

---

## API Endpoints

### Public Endpoints

* **Get All Posts** (with search, filter, pagination)
  `GET /api/posts?search=keyword&user_id=1&sort=title&direction=asc&per_page=10`

* **Get Single Post**
  `GET /api/posts/{id}`

## Token Abilities

All tokens include these permissions:
- `posts:read` - View all posts
- `posts:write-own` - Create and manage your own posts
- `profile:read` - Read user profile information
- `auth:logout` - Logout capability

### Protected Endpoints (Require Authentication)

* **Create Post**

```
POST /api/posts
Authorization: Bearer your_token
Content-Type: application/json
```

```json
{
  "title": "My Post Title",
  "content": "This is the post content..."
}
```

* **Update Post (Only post owner)**

```
PUT /api/posts/{id}
Authorization: Bearer your_token
Content-Type: application/json
```

```json
{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

* **Delete Post (Only post owner)**

```
DELETE /api/posts/{id}
Authorization: Bearer your_token
```

---

## API Parameters

**Query Parameters for `GET /api/posts`**

* `search` - Search in title and content
* `user_id` - Filter by user ID
* `sort` - Sort field (title, created_at, etc.)
* `direction` - Sort direction (asc, desc)
* `per_page` - Items per page (default: 10)

---

## Example Requests

```
# Search posts
curl "http://localhost:8000/api/posts?search=laravel"

# Filter by user and sort
curl "http://localhost:8000/api/posts?user_id=1&sort=created_at&direction=desc"

# Pagination
curl "http://localhost:8000/api/posts?per_page=5&page=2"
```

---

## Testing

### Run Test Suite

```
php artisan test
php artisan test --coverage-clover=coverage.xml
```

**Test Features Covered**

* User registration & authentication
* Post CRUD operations
* Authorization (users can only edit their own posts)
* Validation & error handling
* Rate limiting
* Performance with large datasets
* XSS Prevention with HTMLPurifier content sanitization
* Automatic Token Pruning for expired sessions
* Security Headers middleware protection
* Token Ability System with scope-based permissions
* Input sanitization & validation

---

## Postman Collection

Import `Blog-API.postman_collection.json` from the root directory into Postman for:

* Pre-configured API requests
* Environment variables setup
* Example requests and responses
* Easy testing workflow

**Workflow**

1. Register/Login to get authentication token
2. Set environment variable `token` with the received token
3. Test all endpoints with pre-configured requests

---

## Database Schema

**Users**

* `id`, `name`, `email`, `password`, `remember_token`, `timestamps`

**Posts**

* `id`, `title`, `content`, `user_id`, `timestamps`

---

## Technologies Used

* Laravel 10+ - PHP Framework
* Laravel Sanctum - API Authentication
* MySQL - Database
* PHPUnit - Testing Framework
* Postman - API Testing & Documentation
* HTMLPurifier - XSS Prevention & Content Sanitization

---

## Performance Features

* Query Caching - 60-second cache for post listings
* Eager Loading - Users loaded with posts to prevent N+1 queries
* Pagination - Prevents memory issues with large datasets
* Rate Limiting - Protection against API abuse

---

## Security Features

### Advanced Protection Measures

- **XSS Prevention** - HTML content sanitization using HTMLPurifier
- **SQL Injection Protection** - Eloquent ORM with parameter binding
- **Mass Assignment Protection** - Strict `$fillable` properties
- **Input Validation** - Comprehensive FormRequest validation
- **CORS Protection** - Configured CORS middleware

### Authentication & Authorization

- **Token-Based Auth** - Laravel Sanctum with ability-based permissions
- **Token Expiration** - Tokens expire after 7 days
- **Automatic Token Pruning** - Expired tokens automatically removed (configurable timeframe)
- **Legacy Token Cleanup** - Tokens without expiry date removed after 30 days
- **Limited Token Abilities** - Scope-based access control:
  - `posts:read` - View posts
  - `posts:write-own` - Create/edit own posts  
  - `profile:read` - Read user profile
  - `auth:logout` - Logout capability

### Rate Limiting & Abuse Prevention

| Endpoint | Limit | Window |
|----------|-------|--------|
| Registration | 6 requests | 1 hour |
| Login | 10 requests | 1 minute |
| Post Creation | 20 requests | 1 minute |
| Public API | 120-300 requests | 1 minute |

### Password Security

- **Strong Password Requirements**:
  - Minimum 8 characters
  - Letters (both uppercase and lowercase)
  - Numbers
  - Mixed case
  - Symbols (in production)
  - Uncompromised password check

### Data Protection

- **Email Privacy** - User emails never exposed in public endpoints
- **Secure Headers** - XSS protection, content type options
- **Safe Caching** - Cache key sanitization prevents DoS attacks

### Automated Security Maintenance

- **Scheduled Token Cleanup** - Expired tokens automatically pruned
- **Security Testing** - Comprehensive test suite covering:
  - XSS attack prevention
  - SQL injection attempts
  - Rate limiting enforcement
  - Authentication bypass attempts

### Security Headers

X-Content-Type-Options: nosniff
X-Frame-Options: DENY  
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin

### Content Security & Validation

- **HTML Content Sanitization** - HTMLPurifier removes dangerous HTML/scripts
- **Input Validation** - Comprehensive FormRequest validation with pre-validation sanitization
- **Mass Assignment Protection** - Strict `$fillable` properties with `user_id` prohibition

### Token Management

- **7-Day Token Expiry** - Automatic token expiration
- **Token Ability System** - Scope-based permissions:
  - `posts:read` - View posts
  - `posts:write-own` - Create/edit own posts
  - `profile:read` - Read user profile  
  - `auth:logout` - Logout capability
- **Automatic Token Pruning** - Expired tokens automatically removed

---

## Deployment Ready

* GitHub Actions CI/CD pipeline
* Comprehensive test suite
* Environment configuration
* Database migrations & seeders
* Production optimizations

**Quick Start:** Clone, run `php artisan migrate --seed`, and use the test credentials to explore the API immediately.
