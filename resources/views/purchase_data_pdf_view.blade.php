<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Bootstrap 5 Table Example</title>
</head>
<body>

<div class="container mt-5">
    <h2>Purchase Order List</h2>
    <table class="table table-striped mt-5">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Order Id</th>
                <th scope="col">Vendor Name</th>
                <th scope="col">Warehouse Name</th>
                <th scope="col">Total Quantity</th>
                <th scope="col">Order Date</th>
                <th scope="col">Product Barcode</th>
                <th scope="col">Product Name</th>
                <th scope="col">Quantity</th>
            </tr>
        </thead>
        <tbody>
        @php
        @endphp
            @foreach($purchase_data as $key => $order)
            <tr>
                <th scope="row">{{ $key + 1 }}</th>
                <td>{{ $order->idpurchase_order }}</td>
                <td>{{ $order->vendor_name }}</td>
                <td>{{ $order->warehouse_name }}</td>
                <td>{{ $order->total_quantity }}</td>
                <td>{{ $order->order_date }}</td>
                @foreach($order->products as $key => $product)
                  @if($key == 0)
                    <td>{{ $product->barcode }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->quantity }}</td>
                   </tr> 
                  @else 
                    <tr>
                        <th scope="row"></th>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ $product->barcode }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->quantity }}</td>
                    </tr>
                  @endif  
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>