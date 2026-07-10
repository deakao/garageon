@php
    $shareUrl = $quote->publicUrl();
@endphp

<div style="font-family: Arial, sans-serif; color: #18181b; line-height: 1.6;">
    <p>Olá, {{ $quote->customer->name }}.</p>

    <p>Segue o orçamento da {{ $quote->tenant->name }} para aprovação:</p>

    <p>
        <a href="{{ $shareUrl }}" style="display: inline-block; border-radius: 12px; background: #ffc400; color: #000; font-weight: 700; padding: 12px 18px; text-decoration: none;">
            Ver orçamento
        </a>
    </p>

    <p>Valor: R$ {{ number_format((float) $quote->total, 2, ',', '.') }}</p>

    <p>Se o botão não abrir, copie este link:<br>{{ $shareUrl }}</p>

    <p>{{ $quote->tenant->name }}</p>
</div>
