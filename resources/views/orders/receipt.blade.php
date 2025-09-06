<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h2>Resto Receipt</h2>
    <p>Order ID: {{ $order->id }}</p>
    <p>Table: {{ $order->table->code }}</p>
    <p>Order By: {{ $order->order_by }}</p>
    <table border="1">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->food->name }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ $item->price }}</td>
                <td>{{ $item->subtotal }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p>Total: {{ $order->total_price }}</p>
</body>
</html>
