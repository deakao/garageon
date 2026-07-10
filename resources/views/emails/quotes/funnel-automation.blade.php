@php
    $shareUrl = $quote->publicUrl();
@endphp

<div style="font-family: Arial, sans-serif; color: #18181b; line-height: 1.6;">
    {!! nl2br(e($renderedBody)) !!}

    <p style="margin-top: 24px;">
        <a href="{{ $shareUrl }}" style="display: inline-block; border-radius: 12px; background: #ffc400; color: #000; font-weight: 700; padding: 12px 18px; text-decoration: none;">
            Ver orçamento
        </a>
    </p>
</div>
