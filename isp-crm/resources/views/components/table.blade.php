@props([
    'headers' => [],
    'hoverable' => true,
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-secondary-200']) }}>
    <table class="min-w-full divide-y divide-secondary-200">
        @if(count($headers) > 0 || isset($header))
            <thead class="bg-secondary-50">
                <tr>
                    @isset($header)
                        {{ $header }}
                    @else
                        @foreach($headers as $header)
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                                {{ $header }}
                            </th>
                        @endforeach
                    @endisset
                </tr>
            </thead>
        @endif

        <tbody class="bg-white divide-y divide-secondary-200 {{ $striped ? 'divide-y-0' : '' }}">
            {{ $slot }}
        </tbody>

        @isset($footer)
            <tfoot class="bg-secondary-50 border-t border-secondary-200">
                {{ $footer }}
            </tfoot>
        @endisset
    </table>
</div>

{{-- Table Row Component --}}
@php
if (!function_exists('table_row_class')) {
    function table_row_class($hoverable, $striped) {
        $classes = [];
        if ($hoverable) $classes[] = 'hover:bg-secondary-50';
        if ($striped) $classes[] = 'odd:bg-white even:bg-secondary-50';
        return implode(' ', $classes);
    }
}
@endphp
