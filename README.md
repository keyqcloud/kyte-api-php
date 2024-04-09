# Kyte API PHP Client

The Kyte API PHP Client is a comprehensive and easy-to-use PHP client for interacting with the Kyte API. It encapsulates all the necessary methods for making HTTP requests to the Kyte API, including authentication, session management, and standard CRUD operations.

## Features

- Supports all basic CRUD operations (Create, Read, Update, Delete).
- Handles authentication using API keys.
- Manages session tokens transparently.
- Easy to integrate and use in any PHP-based application.

## Requirements

- PHP 7.4 or higher
- cURL extension enabled

## Installation

Currently, you need to manually include this client in your PHP project. Place the `Client.php` file in your project's appropriate directory and include it using PHP's `require` or `autoload` mechanism.

```php
require_once 'path/to/Client.php';
```

## Usage

### Initialization

First, initialize the client with your Kyte API credentials and endpoint.

```php
use Kyte\Api\Client;

$publicKey = 'your-public-key';
$privateKey = 'your-private-key';
$kyteAccount = 'your-kyte-account';
$kyteIdentifier = 'your-kyte-identifier';
$kyteEndpoint = 'your-kyte-endpoint';
$kyteAppId = 'your-kyte-app-id'; // Optional

$client = new Client($publicKey, $privateKey, $kyteAccount, $kyteIdentifier, $kyteEndpoint, $kyteAppId);
```

### Making Requests

#### Create a Session

```php
$sessionDetails = $client->createSession($username, $password);
```

#### POST Request

```php
$response = $client->post('model', $data);
```

#### GET Request

```php
$response = $client->get('model', 'field', 'value');
```

#### PUT Request

```php
$response = $client->put('model', 'field', 'value', $data);
```

#### DELETE Request

```php
$response = $client->delete('model', 'field', 'value');
```

## Error Handling

The client throws exceptions when it encounters errors. Make sure to catch these exceptions to handle errors gracefully in your application.

```php
try {
    $response = $client->get('model', 'field', 'value');
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Support

For support and feature requests, please send an email to [info@keyqcloud.com](mailto:info@keyqcloud.com).

## Contributing

We welcome contributions to this client. Please send your pull requests to the repository.

## License

This client is licensed under the MIT License - see the LICENSE file for details.
