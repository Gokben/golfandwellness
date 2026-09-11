<?php

namespace App\Support;

final class AgentKnowledge
{
    public static function visible(array $rows, string $owner, bool $admin): array
    {
        return array_values(array_filter($rows, fn ($row) => $row['owner'] === $owner || ($row['scope'] === 'shared' && ($admin || $row['status'] === 'approved'))));
    }

    public static function context(array $rows, string $owner): array
    {
        return array_values(array_filter($rows, fn ($row) => $row['status'] === 'approved' && ($row['scope'] === 'shared' || $row['owner'] === $owner)));
    }

    public static function transaction(callable $callback): mixed
    {
        $directory = storage_path('app/private/golf-agent');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('Agent storage unavailable');
        $lock = fopen($directory.'/knowledge.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new \RuntimeException('Agent storage unavailable');
        try {
            $path = $directory.'/knowledge.json';
            $state = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : ['rows' => [], 'audit' => []];
            $before = $state;
            $result = $callback($state);
            if ($state !== $before) {
                $temp = tempnam($directory, 'save-');
                try {
                    if (file_put_contents($temp, json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) === false || !chmod($temp, 0600) || !rename($temp, $path)) throw new \RuntimeException('Agent save failed');
                } finally { if (is_file($temp)) unlink($temp); }
            }
            return $result;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}
