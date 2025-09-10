<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    // Root redirects to the appropriate dashboard/login depending on auth
    $response->assertStatus(302);
});
