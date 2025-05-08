<?php

declare(strict_types=1);

it('returns a redirect response', function (): void {
    $response = $this->get('/');

    $response->assertOk();
});
