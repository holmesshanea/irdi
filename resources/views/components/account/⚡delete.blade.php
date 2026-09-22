<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    // Retained so previously opened forms cannot call the old destructive action.
    public string $password = '';

    public function deleteAccount(): void
    {
        abort(403);
    }
};
?>

<div></div>
