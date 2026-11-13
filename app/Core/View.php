<?php

namespace Rk\Threadwell\Core;

class View
{
    public function render(
        string $view,
        array $data = [],
        ?string $layout = 'layouts/main'
    ): string {
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException(
                "View not found: {$view}"
            );
        }

        extract($data);

        ob_start();

        require $viewPath;

        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutPath = dirname(__DIR__) . '/Views/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException(
                "Layout not found: {$layout}"
            );
        }

        ob_start();

        require $layoutPath;

        return ob_get_clean();
    }
}