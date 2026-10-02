@php($rtl = app()->getLocale() === 'ar')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border-top:1px solid #e3d9cb;font-size:14px;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #e3d9cb;">{{ $item->translate('name') }} × {{ $item->quantity }}</td>
            <td style="padding:10px 0;border-bottom:1px solid #e3d9cb;text-align:{{ $rtl ? 'left' : 'right' }};white-space:nowrap;">{{ \App\Support\Money\Money::amount((string) $item->line_total) }} {{ \App\Support\Money\Money::currency() }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:10px 0;font-weight:bold;">{{ __('orders.page.total') }}</td>
        <td style="padding:10px 0;font-weight:bold;text-align:{{ $rtl ? 'left' : 'right' }};white-space:nowrap;">{{ \App\Support\Money\Money::amount((string) $order->grand_total) }} {{ \App\Support\Money\Money::currency() }}</td>
    </tr>
    @if (bccomp((string) $order->amount_paid, '0', 2) > 0 && bccomp($order->balanceDue(), '0', 2) > 0)
        <tr>
            <td style="padding:4px 0;color:#5e544b;">{{ __('orders.page.paid') }}</td>
            <td style="padding:4px 0;color:#5e544b;text-align:{{ $rtl ? 'left' : 'right' }};">{{ \App\Support\Money\Money::amount((string) $order->amount_paid) }} {{ \App\Support\Money\Money::currency() }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;">{{ __('orders.page.balance') }}</td>
            <td style="padding:4px 0;text-align:{{ $rtl ? 'left' : 'right' }};">{{ \App\Support\Money\Money::amount($order->balanceDue()) }} {{ \App\Support\Money\Money::currency() }}</td>
        </tr>
    @endif
</table>
