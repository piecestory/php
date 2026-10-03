<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns;

/** ServiceRequest accessors for models with reference, name, phone, email and locale columns. */
trait IsServiceRequest
{
    public function requestReference(): string
    {
        return (string) $this->getAttribute('reference');
    }

    public function contactName(): string
    {
        return (string) $this->getAttribute('name');
    }

    public function contactPhone(): string
    {
        return (string) $this->getAttribute('phone');
    }

    public function contactEmail(): ?string
    {
        $email = $this->getAttribute('email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function messageLocale(): string
    {
        return (string) ($this->getAttribute('locale') ?? 'ar');
    }
}
