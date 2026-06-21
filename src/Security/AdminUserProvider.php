<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AdminUserProvider implements UserProviderInterface
{
    public function __construct(
        private string $adminPassword,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ('admin' !== $identifier) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return new AdminUser($this->passwordVerifier());
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return new AdminUser($this->passwordVerifier());
    }

    public function supportsClass(string $class): bool
    {
        return AdminUser::class === $class || is_subclass_of($class, AdminUser::class);
    }

    private function passwordVerifier(): string
    {
        return hash('sha256', $this->adminPassword);
    }
}
