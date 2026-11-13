<?php

namespace Rk\Threadwell\Core;

class Application
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim(
            $path,
            DIRECTORY_SEPARATOR
        );
    }

    public function run(): void
    {
        echo 'Threadwell Application Started';
    }
}