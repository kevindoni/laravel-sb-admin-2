<?php

namespace App\Services;

use App\Models\Router;
use Exception;

class MikrotikApiService
{
    private $socket;
    private $router;
    private $connected = false;

    public function __construct(Router $router = null)
    {
        $this->router = $router;
    }

    public function connect(Router $router = null): bool
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            throw new Exception('Router not specified');
        }

        try {
            $this->socket = @fsockopen($this->router->host, $this->router->port, $errno, $errstr, 5);
            
            if (!$this->socket) {
                throw new Exception("Cannot connect to {$this->router->host}:{$this->router->port} - $errstr");
            }

            // Login to router
            $this->write('/login');
            $response = $this->read();
            
            if (empty($response)) {
                throw new Exception('No response from router');
            }

            $this->write('/login', [
                '=name=' . $this->router->username,
                '=password=' . $this->router->password
            ]);

            $response = $this->read();
            
            if (isset($response[0]) && $response[0] == '!done') {
                $this->connected = true;
                $this->router->update(['last_connected_at' => now()]);
                return true;
            }

            throw new Exception('Authentication failed');

        } catch (Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    public function getSystemInfo(): array
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        $this->write('/system/resource/print');
        $response = $this->read();
        
        return $this->parseResponse($response);
    }

    public function getHotspotUsers(): array
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        $this->write('/ip/hotspot/user/print');
        $response = $this->read();
        
        return $this->parseResponse($response);
    }

    public function createHotspotUser(array $userData): bool
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        $command = ['/ip/hotspot/user/add'];
        
        foreach ($userData as $key => $value) {
            $command[] = "=$key=$value";
        }

        $this->write($command[0], array_slice($command, 1));
        $response = $this->read();

        return isset($response[0]) && $response[0] == '!done';
    }

    public function updateHotspotUser(string $username, array $userData): bool
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        // Find user ID first
        $this->write('/ip/hotspot/user/print', ['?name=' . $username]);
        $users = $this->parseResponse($this->read());

        if (empty($users)) {
            throw new Exception("User $username not found");
        }

        $userId = $users[0]['.id'] ?? null;
        if (!$userId) {
            throw new Exception("Cannot get user ID for $username");
        }

        // Update user
        $command = ['/ip/hotspot/user/set'];
        $command[] = "=.id=$userId";
        
        foreach ($userData as $key => $value) {
            $command[] = "=$key=$value";
        }

        $this->write($command[0], array_slice($command, 1));
        $response = $this->read();

        return isset($response[0]) && $response[0] == '!done';
    }

    public function deleteHotspotUser(string $username): bool
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        // Find user ID first
        $this->write('/ip/hotspot/user/print', ['?name=' . $username]);
        $users = $this->parseResponse($this->read());

        if (empty($users)) {
            return true; // Already deleted
        }

        $userId = $users[0]['.id'] ?? null;
        if (!$userId) {
            throw new Exception("Cannot get user ID for $username");
        }

        // Delete user
        $this->write('/ip/hotspot/user/remove', ["=.id=$userId"]);
        $response = $this->read();

        return isset($response[0]) && $response[0] == '!done';
    }

    public function getActiveConnections(): array
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        $this->write('/ip/hotspot/active/print');
        $response = $this->read();
        
        return $this->parseResponse($response);
    }

    public function disconnectUser(string $username): bool
    {
        if (!$this->connected) {
            throw new Exception('Not connected to router');
        }

        $this->write('/ip/hotspot/active/print', ['?user=' . $username]);
        $sessions = $this->parseResponse($this->read());

        foreach ($sessions as $session) {
            $sessionId = $session['.id'] ?? null;
            if ($sessionId) {
                $this->write('/ip/hotspot/active/remove', ["=.id=$sessionId"]);
                $this->read();
            }
        }

        return true;
    }

    private function write(string $command, array $arguments = []): void
    {
        $data = $this->encodeLength(strlen($command)) . $command;
        
        foreach ($arguments as $arg) {
            $data .= $this->encodeLength(strlen($arg)) . $arg;
        }
        
        $data .= $this->encodeLength(0);
        
        fwrite($this->socket, $data);
    }

    private function read(): array
    {
        $response = [];
        
        while (true) {
            $length = $this->decodeLength();
            
            if ($length === 0) {
                break;
            }
            
            $response[] = fread($this->socket, $length);
        }
        
        return $response;
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        } elseif ($length < 0x4000) {
            return chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            return chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
    }

    private function decodeLength(): int
    {
        $byte = ord(fread($this->socket, 1));
        
        if ($byte < 0x80) {
            return $byte;
        } elseif ($byte < 0xC0) {
            return (($byte & 0x7F) << 8) + ord(fread($this->socket, 1));
        } elseif ($byte < 0xE0) {
            return (($byte & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($byte < 0xF0) {
            return (($byte & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        
        return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
    }

    private function parseResponse(array $response): array
    {
        $parsed = [];
        $current = [];
        
        foreach ($response as $line) {
            if ($line == '!done' || $line == '!trap' || $line == '!fatal') {
                if (!empty($current)) {
                    $parsed[] = $current;
                    $current = [];
                }
            } elseif (substr($line, 0, 1) == '=') {
                $pos = strpos($line, '=', 1);
                if ($pos !== false) {
                    $key = substr($line, 1, $pos - 1);
                    $value = substr($line, $pos + 1);
                    $current[$key] = $value;
                }
            }
        }
        
        if (!empty($current)) {
            $parsed[] = $current;
        }
        
        return $parsed;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}