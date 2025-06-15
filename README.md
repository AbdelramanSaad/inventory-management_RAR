# Inventory Management and Audit System

## Overview

A comprehensive Inventory Management and Audit System built with Laravel 10. This system provides robust inventory tracking, audit logging, role-based access control, and real-time notifications for inventory changes.

## Features

- **Inventory Management**: Create, read, update, and delete inventory items with detailed information
- **Warehouse Management**: Associate inventory items with specific warehouses
- **Audit Logging**: Automatic logging of all inventory actions (creation, updates, deletions, stock adjustments)
- **Role-Based Access Control**: Different permission levels for administrators, warehouse managers, and staff
- **JWT Authentication**: Secure API endpoints with JSON Web Tokens
- **Real-time Notifications**: Instant alerts for low stock levels and other important events using Laravel WebSockets
- **Caching**: Performance optimization through strategic caching of inventory and audit data
- **File Uploads**: Support for uploading and managing product images
- **Comprehensive Testing**: Unit and feature tests for all major components

## System Architecture

The application follows a repository-service pattern with:

- **Models**: Database entities (InventoryItem, AuditLog, User, Warehouse)
- **Repositories**: Data access layer with caching mechanisms
- **Services**: Business logic layer
- **Controllers**: API endpoints with request validation
- **Resources**: JSON response formatting
- **Observers**: Automatic audit logging
- **Middleware**: Role-based access control
- **API Documentation**: Comprehensive API documentation using Swagger/OpenAPI
- **WebSockets**: Real-time communication using Laravel WebSockets

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- MySQL 5.7+ or PostgreSQL
- Node.js and NPM (for frontend assets)

### Setup Steps

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd inventory-management
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file and configure your database:
   ```bash
   cp .env.example .env
   ```

4. Generate application key and JWT secret:
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```

5. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

6. Link storage for file uploads:
   ```bash
   php artisan storage:link
   ```

7. Install Laravel WebSockets dependencies:
   ```bash
   composer require beyondcode/laravel-websockets
   ```

8. Publish WebSockets configuration:
   ```bash
   php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
   ```

9. Run WebSockets migrations:
   ```bash
   php artisan migrate
   ```

10. Start the WebSockets server:
    ```bash
    php artisan websockets:serve
    ```

11. In a separate terminal, start the Laravel development server:
    ```bash
    php artisan serve
    ```

## API Documentation

The API is fully documented using Swagger/OpenAPI. You can access the interactive documentation at:

```
http://localhost:8000/api/documentation
```

To generate the documentation, run:

```bash
php artisan l5-swagger:generate
```

### Authentication Endpoints

#### Login
- **URL**: `/api/auth/login`
- **Method**: `POST`
- **Body**: `{"email": "user@example.com", "password": "password"}`
- **Response**: JWT token, user information

#### Register
- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Body**: `{"name": "User Name", "email": "user@example.com", "password": "password", "password_confirmation": "password", "role": "staff", "warehouse_id": 1}`
- **Response**: User information

#### Logout
- **URL**: `/api/auth/logout`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Success message

#### Refresh Token
- **URL**: `/api/auth/refresh`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: New JWT token

#### User Profile
- **URL**: `/api/auth/profile`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: User information

### Inventory Item Endpoints

#### List Inventory Items
- **URL**: `/api/inventory-items`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**: `category`, `search`, `below_min_stock`, `warehouse_id`, `page`, `per_page`
- **Response**: Paginated inventory items

#### Get Inventory Item
- **URL**: `/api/inventory-items/{id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Inventory item details

#### Create Inventory Item
- **URL**: `/api/inventory-items`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Form data with inventory item details and optional image
- **Response**: Created inventory item

#### Update Inventory Item
- **URL**: `/api/inventory-items/{id}`
- **Method**: `PUT`
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Form data with inventory item details to update
- **Response**: Success message

#### Delete Inventory Item
- **URL**: `/api/inventory-items/{id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Success message

### Audit Log Endpoints

#### List Audit Logs
- **URL**: `/api/audit-logs`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**: `type`, `warehouse_id`, `page`, `per_page`
- **Response**: Paginated audit logs

## Role-Based Access Control

### Available Roles

- **Admin**: Full access to all features
- **Warehouse Manager**: Manage inventory items in assigned warehouse
- **Staff**: View inventory items and audit logs for assigned warehouse

### Authorization Gates

- **admin-actions**: Admin only
- **manage-inventory**: Admin and warehouse managers
- **view-inventory**: All authenticated users
- **view-warehouse**: Admin or users belonging to specific warehouse

## WebSockets Setup

### Client-Side Integration

To connect to WebSockets from the frontend:

```javascript
// Install Echo and Socket.io client
npm install --save laravel-echo pusher-js

// Initialize Laravel Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'local',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
});

// Listen for warehouse events
window.Echo.private(`warehouse.${warehouseId}`)
    .listen('.inventory.event', (e) => {
        console.log('Inventory event received:', e);
        // Handle the event (e.g., show notification)
    });
```

### Available Events

- `item_created`: When a new inventory item is created
- `item_updated`: When an inventory item is updated
- `stock_adjusted`: When the quantity of an item is changed
- `low_stock_alert`: When an item's quantity falls below the minimum stock level
- `item_deleted`: When an inventory item is deleted

## Testing

Run the test suite with:

```bash
php artisan test
```

Tests include:
- Feature tests for API endpoints
- Unit tests for services and repositories
- Authentication and authorization tests
- WebSocket notification tests
- Swagger documentation tests

## License

This project is licensed under the MIT License.
