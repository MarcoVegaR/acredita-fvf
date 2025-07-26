<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ config('app.url') }}/favicon.ico?v=2" class="logo" alt="FVF Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
