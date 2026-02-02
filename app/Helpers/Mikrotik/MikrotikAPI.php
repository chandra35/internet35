<?php

namespace App\Helpers\Mikrotik;

use Exception;

/**
 * Mikrotik RouterOS API Client
 * Supports both ROS 6 and ROS 7
 */
class MikrotikAPI
{
    private $socket;
    private $host;
    private $port;
    private $timeout;
    private $connected = false;
    private $debug = false;
    private $rosVersion = null;
    private $rosMajorVersion = null;

    public function __construct()
    {
        $this->timeout = 5;
    }

    /**
     * Connect to RouterOS API
     */
    public function connect(string $host, string $username, string $password, int $port = 8728, bool $ssl = false): bool
    {
        $this->host = $host;
        $this->port = $port;

        $context = stream_context_create();
        
        if ($ssl) {
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
            $protocol = 'ssl://';
        } else {
            $protocol = '';
        }

        $this->socket = @stream_socket_client(
            $protocol . $host . ':' . $port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($this->socket === false) {
            throw new Exception("Cannot connect to {$host}:{$port} - {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Try login
        if ($this->login($username, $password)) {
            $this->connected = true;
            $this->detectVersion();
            return true;
        }

        return false;
    }

    /**
     * Login to RouterOS - supports both old and new auth methods
     */
    private function login(string $username, string $password): bool
    {
        // First try ROS 6.43+ method (plain text)
        $response = $this->command('/login', [
            '=name=' . $username,
            '=password=' . $password
        ]);

        if (isset($response[0]) && $response[0] === '!done') {
            return true;
        }

        // If we got a challenge, use old method (ROS < 6.43)
        if (isset($response[0]) && $response[0] === '!done' && isset($response[1])) {
            foreach ($response as $line) {
                if (strpos($line, '=ret=') === 0) {
                    $challenge = substr($line, 5);
                    $response = $this->command('/login', [
                        '=name=' . $username,
                        '=response=00' . md5(chr(0) . $password . pack('H*', $challenge))
                    ]);
                    return isset($response[0]) && $response[0] === '!done';
                }
            }
        }

        // Check for trap (error)
        foreach ($response as $line) {
            if (strpos($line, '!trap') === 0) {
                return false;
            }
        }

        return isset($response[0]) && $response[0] === '!done';
    }

    /**
     * Detect RouterOS version
     */
    private function detectVersion(): void
    {
        $resource = $this->getSystemResource();
        if (isset($resource['version'])) {
            $this->rosVersion = $resource['version'];
            // Extract major version (6 or 7)
            preg_match('/^(\d+)/', $resource['version'], $matches);
            $this->rosMajorVersion = isset($matches[1]) ? (int) $matches[1] : null;
        }
    }

    /**
     * Send command to RouterOS
     */
    public function command(string $command, array $attributes = []): array
    {
        $this->write($command);
        
        foreach ($attributes as $attr) {
            $this->write($attr);
        }
        
        $this->write('');
        
        return $this->read();
    }

    /**
     * Execute command and parse response
     */
    public function exec(string $command, array $params = []): array
    {
        $attributes = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $attributes[] = $value;
            } else {
                $attributes[] = '=' . $key . '=' . $value;
            }
        }

        $response = $this->command($command, $attributes);
        return $this->parseResponse($response);
    }

    /**
     * Parse API response into array
     */
    private function parseResponse(array $response): array
    {
        $result = [];
        $current = [];

        foreach ($response as $line) {
            if ($line === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif ($line === '!done') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                break;
            } elseif ($line === '!trap') {
                $current['_error'] = true;
            } elseif (strpos($line, '=') === 0) {
                $parts = explode('=', substr($line, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }

        return $result;
    }

    /**
     * Write to socket
     */
    private function write(string $command): void
    {
        $length = strlen($command);

        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            fwrite($this->socket, chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            fwrite($this->socket, chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            fwrite($this->socket, chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        }

        fwrite($this->socket, $command);
    }

    /**
     * Read from socket
     */
    private function read(): array
    {
        $response = [];

        while (true) {
            $byte = ord(fread($this->socket, 1));
            
            if ($byte < 0x80) {
                $length = $byte;
            } elseif ($byte < 0xC0) {
                $length = (($byte & 0x3F) << 8) + ord(fread($this->socket, 1));
            } elseif ($byte < 0xE0) {
                $length = (($byte & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } elseif ($byte < 0xF0) {
                $length = (($byte & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } else {
                $length = (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            }

            if ($length > 0) {
                $line = '';
                while ($length > 0) {
                    $chunk = fread($this->socket, min($length, 8192));
                    $line .= $chunk;
                    $length -= strlen($chunk);
                }
                $response[] = $line;
            } else {
                if (end($response) === '!done' || end($response) === '!trap' || end($response) === '!fatal') {
                    break;
                }
            }
        }

        return $response;
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Get RouterOS version
     */
    public function getVersion(): ?string
    {
        return $this->rosVersion;
    }

    /**
     * Get major version number
     */
    public function getMajorVersion(): ?int
    {
        return $this->rosMajorVersion;
    }

    /**
     * Check if ROS 7+
     */
    public function isRos7(): bool
    {
        return $this->rosMajorVersion >= 7;
    }

    /**
     * Get system resource
     */
    public function getSystemResource(): array
    {
        $result = $this->exec('/system/resource/print');
        return $result[0] ?? [];
    }

    /**
     * Get system identity
     */
    public function getIdentity(): string
    {
        $result = $this->exec('/system/identity/print');
        return $result[0]['name'] ?? '';
    }

    /**
     * Disconnect from RouterOS
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
