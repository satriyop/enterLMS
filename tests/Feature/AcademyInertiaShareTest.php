<?php

use App\Domain\Shared\Academy;
use Inertia\Testing\AssertableInertia;

it('shares academy capabilities on public pages without the preset name', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('academy.features.offerings', false)
            ->where('academy.features.attendance', false)
            ->where('academy.identity.scheme', 'email')
            ->where('academy.labels.learner', 'Learner')
            ->missing('academy.preset')
        );
});

it('shares academic labels after the install preset is swapped', function () {
    Academy::using('academic');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('academy.features.offerings', true)
            ->where('academy.features.attendance', false)
            ->where('academy.labels.offering', 'Kelas')
            ->where('academy.identity.scheme', 'nim')
            ->missing('academy.preset')
        );
});
