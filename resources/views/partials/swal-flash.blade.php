@php
    $swal = session('swal');

    if (! $swal && $errors->any()) {
        $swal = [
            'icon' => 'error',
            'title' => $errors->count() === 1
                ? $errors->first()
                : 'Revisa los datos ingresados.',
        ];
    }
@endphp

@if ($swal)
    <script type="application/json" id="swal-flash">@json($swal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
@endif
