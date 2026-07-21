@if ($paginator->hasPages())
    <nav class="pag" role="navigation" aria-label="Paginación">
        <p class="pag__info">
            Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong>
            de <strong>{{ $paginator->total() }}</strong> resultados
        </p>

        <div class="pag__nav">
            @if ($paginator->onFirstPage())
                <span class="pag__link pag__link--disabled" aria-disabled="true">&laquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pag__link" aria-label="{{ __('pagination.previous') }}">&laquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pag__link pag__link--puntos" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pag__link pag__link--activo" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pag__link" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pag__link" aria-label="{{ __('pagination.next') }}">&raquo;</a>
            @else
                <span class="pag__link pag__link--disabled" aria-disabled="true">&raquo;</span>
            @endif
        </div>
    </nav>
@endif
