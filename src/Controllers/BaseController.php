<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Request;
use Ploofoodle\Core\Response;

abstract class BaseController
{
    public function __construct(
        protected Request $request,
        protected Response $response,
    ) {
    }

    protected function render(string $viewFile, array $vars = []): void
    {
        $viewPath = PLOO_BASE_PATH . '/src/Views/' . $viewFile;
        if (!is_file($viewPath)) {
            $this->response->html('View not found: ' . htmlspecialchars($viewFile, ENT_QUOTES, 'UTF-8'), 500);
            return;
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = (string)ob_get_clean();
        $this->response->html($content);
    }
}
