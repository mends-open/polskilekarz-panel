@php($script = file_get_contents(resource_path('js/chatwoot-dashboard-context.js')))

@if ($script)
    <script type="module">
{!! $script !!}
    </script>
@endif
