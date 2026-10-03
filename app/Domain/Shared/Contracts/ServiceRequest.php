<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * A request a customer sends the team (Personal Finder, "sell with us"): it has a reference number,
 * contact details and a language, and the customer is kept informed by SMS and email.
 * Implemented by models through the IsServiceRequest trait.
 */
interface ServiceRequest
{
    /** "finder" or "consignment": selects the message texts (lang/requests.php). */
    public function requestKind(): string;

    /** Prefix of the reference number, e.g. "PF". */
    public static function referencePrefix(): string;

    public function requestReference(): string;

    public function contactName(): string;

    /** E.164 mobile number. */
    public function contactPhone(): string;

    public function contactEmail(): ?string;

    public function messageLocale(): string;
}
