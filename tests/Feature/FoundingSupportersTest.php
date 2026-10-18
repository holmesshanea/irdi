<?php

use App\Models\FoundingSupporter;

it('displays only supporters who consented to public recognition', function () {
    FoundingSupporter::create(['display_name' => 'Visible Founder', 'tier' => 'founder', 'is_public' => true]);
    FoundingSupporter::create(['display_name' => 'Private Supporter', 'tier' => 'charter', 'is_public' => false]);

    $this->get(route('founding-supporters'))
        ->assertOk()
        ->assertSee('Visible Founder')
        ->assertDontSee('Private Supporter')
        ->assertSee('Launch Supporters');
});

it('escapes public display names', function () {
    FoundingSupporter::create(['display_name' => '<script>alert(1)</script>', 'tier' => 'launch', 'is_public' => true]);

    $this->get(route('founding-supporters'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});
