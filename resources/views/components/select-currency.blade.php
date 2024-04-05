@props(['title'=> null])

@php
    $label = 'All'; 

    if($title){
        $label = $title; 
    }
@endphp

<select name="currency_id" class="form-control">
    <option value="">{{ __($label) }}</option>
    @foreach($currencies as $currency)
        <option value="{{ $currency->id }}" @selected(request()->currency_id == $currency->id)>{{ $currency->currency_code }}</option>
    @endforeach
</select>