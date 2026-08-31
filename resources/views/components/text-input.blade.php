@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input border-gray-300']) }}>
