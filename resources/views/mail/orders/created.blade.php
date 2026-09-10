<x-mail::message>
🎉 Terima kasih telah melakukan pembelian di {{ config('app.name') }}!
<br />
🎮 Berikut adalah detail pesanan Anda:
- 🆔 ID Pesanan: **{{ $order->reference }}**
@if ($order->product)
- 🛒 Produk: **{{ $order->product->name }}**
@elseif ($order->items->isNotEmpty())
- 🛒 Produk:
@foreach ($order->items as $item)
  - {{ $item->product->name ?? 'Produk' }} x{{ $item->quantity }}
@endforeach
@endif
- 🧾 Total: **{{ numberToCurrency($order->total_amount) }}**
<br />
<x-mail::button :url="route('transaction.show', [
    'order' => $order->reference,
])">
Detail Pesanan
</x-mail::button>

Terima kasih telah memilih {{ config('app.name') }} untuk kebutuhan top up Anda. Jika Anda memiliki pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi tim dukungan kami.
</x-mail::message>
