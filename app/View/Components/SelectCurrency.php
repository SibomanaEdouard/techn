<?php

namespace App\View\Components;

use App\Models\Currency;
use Illuminate\View\Component;

class SelectCurrency extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */

    public $currencies;

    public function __construct()
    {
        $this->currencies = Currency::enable()->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    { 
        return view('components.select-currency');
    }
}
