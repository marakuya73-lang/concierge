<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAdminController extends AbstractController
{
    protected function validateAdminCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin_action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
