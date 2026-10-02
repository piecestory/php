<?php

declare(strict_types=1);

it('responds on the health endpoint', function (): void {
    $this->get('/up')->assertOk();
});
