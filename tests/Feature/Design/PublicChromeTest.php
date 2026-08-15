<?php

it('exposes only working public destinations', function () {
    $this->get('/')->assertOk();
    $this->get('/certificates/verify')->assertOk();
    $this->get('/login')->assertOk();
});

it('does not invent catalog destinations that have no page', function () {
    $this->get('/categories')->assertNotFound();
    $this->get('/instructors')->assertNotFound();
    $this->get('/about')->assertNotFound();
    $this->get('/contact')->assertNotFound();
});
