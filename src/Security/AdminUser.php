<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $passwordVerifier = '',
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getPassword(): string
    {
        return $this->passwordVerifier;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'admin';
    }
}
