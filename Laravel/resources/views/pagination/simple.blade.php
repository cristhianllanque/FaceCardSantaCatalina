@if ($paginator->hasPages())
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="disabled"><span>{{ $element }}</span></span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active"><span>{{ $page }}</span></span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
@endif
