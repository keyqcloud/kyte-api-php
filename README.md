# Kyte API PHP Client

The Kyte API PHP Client is a comprehensive and easy-to-use PHP client for interacting with the Kyte API. It encapsulates all the necessary methods for making HTTP requests to the Kyte API, including authentication, session management, and standard CRUD operations.

**Updated to match Kyte.JS v1.2.23**

## Features

- ✅ Supports all basic CRUD operations (Create, Read, Update, Delete)
- ✅ Client-side HMAC-SHA256 signature generation
- ✅ Automatic session token management
- ✅ API key handoff support (multi-tenant applications)
- ✅ Application ID support (x-kyte-appid header)
- ✅ Comprehensive error handling
- ✅ Easy to integrate and use in any PHP-based application

## Requirements

- PHP 7.4 or higher
- cURL extension enabled

## Installation

### Via Composer (Recommended)

```bash
composer require keyqcloud/kyte-api-php
```

### Manual Installation

Include the `Client.php` file in your project:

```php
require_once 'path/to/src/Api/Client.php';
```

## Usage

### Initialization

Initialize the client with your Kyte API credentials:

```php
use Kyte\Api\Client;

$publicKey = 'your-public-key';
$privateKey = 'your-private-key';
$account = 'your-account-number';
$identifier = 'your-api-identifier';
$endpoint = 'https://your-api-endpoint.com/api';
$appId = 'your-app-id'; // Optional

$client = new Client($publicKey, $privateKey, $account, $identifier, $endpoint, $appId);
```

### Authentication

#### Create a Session (Login)

```php
try {
    $response = $client->createSession('user@example.com', 'password');

    if (isset($response['data'])) {
        echo "Logged in as: " . $response['data'][0]['email'];
        echo "Session token: " . $client->getSessionToken();
    }
} catch (Exception $e) {
    echo 'Login failed: ' . $e->getMessage();
}
```

#### Destroy Session (Logout)

```php
$client->destroySession();
echo "Logged out successfully";
```

### CRUD Operations

#### GET Request - Fetch Single Record

```php
try {
    // GET /api/User/email/john@example.com
    $response = $client->get('User', 'email', 'john@example.com');

    if (isset($response['data']) && !empty($response['data'])) {
        $user = $response['data'][0];
        echo "Found user: " . $user['name'];
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### GET Request - Fetch All Records

```php
try {
    // GET /api/User
    $response = $client->get('User');

    foreach ($response['data'] as $user) {
        echo $user['name'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### POST Request - Create Record

```php
try {
    $data = [
        'name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com'
    ];

    // POST /api/User
    $response = $client->post('User', $data);

    echo "Created user with ID: " . $response['data'][0]['id'];
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### PUT Request - Update Record

```php
try {
    $data = [
        'name' => 'John Updated',
        'last_name' => 'Doe'
    ];

    // PUT /api/User/id/123
    $response = $client->put('User', 'id', '123', $data);

    echo "User updated successfully";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### DELETE Request - Delete Record

```php
try {
    // DELETE /api/User/id/123
    $response = $client->delete('User', 'id', '123');

    echo "User deleted successfully";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Advanced Features

### Custom Headers

You can pass custom headers to any request:

```php
$headers = ['X-Custom-Header: value'];
$response = $client->get('User', 'id', '123', $headers);
```

### Session Token Management

Get and set session tokens for session persistence:

```php
// Get current session token
$sessionToken = $client->getSessionToken();

// Restore session token (e.g., from cookie)
$client->setSessionToken($savedSessionToken);
$client->setTransactionToken($savedTxToken);
```

### API Key Handoff (Multi-Tenant)

The client automatically handles API key handoff for multi-tenant applications. When the API returns `kyte_pub`, `kyte_iden`, and `kyte_num` in the response, the client automatically switches to those credentials for subsequent requests.

## Error Handling

The client throws exceptions for all errors. Always wrap API calls in try-catch blocks:

```php
try {
    $response = $client->get('User', 'email', 'test@example.com');
    // Process response
} catch (Exception $e) {
    // Handle error
    error_log("API Error: " . $e->getMessage());
    // Show user-friendly message
    echo "An error occurred. Please try again.";
}
```

## Support

For support and feature requests, please send an email to [info@keyqcloud.com](mailto:info@keyqcloud.com).

## Contributing

We welcome contributions to this client. Please send your pull requests to the repository.

## License

This client is licensed under the MIT License - see the LICENSE file for details.
