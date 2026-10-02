{{-- Email shell: table layout and inline styles (what email clients support); direction follows the language. --}}
@props(['title'])
@php($rtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f7f2ea;color:#1c1612;font-family:Tahoma,'Segoe UI',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f2ea;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fcfaf6;border:1px solid #e3d9cb;">
                    <tr>
                        <td align="center" style="background:#16110e;padding:24px;">
                            <div style="color:#c9a46a;font-family:Georgia,serif;font-size:20px;letter-spacing:4px;" dir="ltr">PIECE &amp; STORY</div>
                            <div style="color:#c9a46a;font-size:14px;margin-top:4px;">قطعة وقصة</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;font-size:15px;line-height:1.8;text-align:{{ $rtl ? 'right' : 'left' }};">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #e3d9cb;padding:20px 28px;font-size:12px;color:#5e544b;text-align:center;">
                            {{ __('ui.brand') }} — {{ __('site.footer.hours') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
