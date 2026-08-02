<?php
if (!function_exists('SendRcon')) {
    function SendRcon($KeyOrCommands, ?array $DataArray = null, ?string $host = null, ?int $port = null): bool
    {
        global $rcon_host, $rcon_port;

        $host = $host ?? ($rcon_host ?? '127.0.0.1');
        $port = $port ?? ($rcon_port ?? 3001);

        if (is_string($KeyOrCommands)) {
            $commands = [['key' => $KeyOrCommands, 'data' => $DataArray ?? []]];
        } else {
            $commands = $KeyOrCommands;
        }

        if (empty($commands)) {
            return false;
        }

        $errno  = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            3, 
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );

        if ($socket === false) {
            return false;
        }

        stream_set_blocking($socket, false);

        $read   = null;
        $write  = [$socket];
        $except = null;

        $ready = @stream_select($read, $write, $except, 0, 1800000);

        if ($ready === false || $ready === 0) {
            fclose($socket);
            return false;
        }

        $success = true;

        foreach ($commands as $cmd) {
            $payload = json_encode([
                'key'  => $cmd['key'],
                'data' => $cmd['data'] ?? []
            ], JSON_UNESCAPED_UNICODE) . "\n";

            $written = @fwrite($socket, $payload);

            if ($written === false) {
                $success = false;
                break;
            }
        }

        fclose($socket);
        return $success;
    }
}
