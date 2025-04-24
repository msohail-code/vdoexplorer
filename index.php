<?php
$address = $_GET['address'] ?? '0xA1077a294dDE1B09bB078844df40758a5D0f9a27';

function getTransactions($address) {
    $url = "https://api.scan.pulsechain.com/api/v2/addresses/$address/transactions?filter=to%20%7C%20from";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

$data = getTransactions($address);
$txns = $data['items'] ?? [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>PulseChain Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
</head>
<body class="p-4 bg-light">

<div class="container">
    

    <?php if (!empty($txns)): ?>
        <h4>All Transactions</h4>
        <div class="text-center mb-3">
            <button id="reloadBtn" class="btn btn-primary">
                🔄 Reload
            </button>
        </div>

        <div class="d-none d-md-block table-responsive">
        
    <!-- Desktop Table -->
    <table id="txTable" class="table table-striped table-bordered align-middle w-100">
        <thead>
            <tr>
                <th>Txn Hash</th>
                <th>Method</th>
                <th>Block</th>
                <th>Age</th>
                <th>From</th>
                <th>To</th>
                <th>Value</th>
                <th>Txn Fee</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($txns as $txn): ?>
                <tr>
                    <td class="text-break"><?php echo substr($txn['hash'], 0, 15) . '...'; ?></td>
                    <td><?php echo $txn['method'] ?? 'Transfer'; ?></td>
                    <td><?php echo $txn['block']; ?></td>
                    <td><?php echo $txn['age'] ?? 'N/A'; ?></td>
                    <td class="text-break small"><?php echo substr($txn['from']['hash'], 0, 15) . '...'; ?></td>
                    <td class="text-break small"><?php echo substr($txn['to']['hash'], 0, 15) . '...' ?? 'Contract'; ?></td>
                    <td><?php echo number_format(hexdec($txn['fee']['value']) / 1e18, 6) . ' PLS'; ?></td>
                    <td><?php echo number_format((hexdec($txn['gas_price']) * $txn['gas_used']) / 1e18, 6) . ' PLS'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile Cards -->
<div class="d-md-none">
    <?php foreach ($txns as $txn): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <p><strong>Txn Hash:</strong> <?php echo $txn['hash']; ?></p>
                <p><strong>Method:</strong> <?php echo $txn['method'] ?? 'Transfer'; ?></p>
                <p><strong>Block:</strong> <?php echo $txn['block']; ?></p>
                <p><strong>Age:</strong> <?php echo $txn['age'] ?? 'N/A'; ?></p>
                <p><strong>From:</strong> <span class="text-break"><?php echo $txn['from']['hash']; ?></span></p>
                <p><strong>To:</strong> <span class="text-break"><?php echo $txn['to']['hash'] ?? 'Contract'; ?></span></p>
                <p><strong>Value:</strong> <?php echo number_format(hexdec($txn['fee']['value']) / 1e18, 6) . ' PLS'; ?></p>
                <p><strong>Txn Fee:</strong> <?php echo number_format((hexdec($txn['gas_price']) * $txn['gas_used']) / 1e18, 6) . ' PLS'; ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>



    <?php else: ?>
        <div class="alert alert-warning">No transactions found or unable to fetch data.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#txTable').DataTable({
            "pageLength": 15,
            "lengthChange": false,
            "order": [[ 2, "desc" ]]
        });
    });

    // Move search bar container to flex layout
    

    $('#reloadBtn').on('click', function() {
        location.reload();
    });
</script>




<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js')
        .then(() => console.log('Service Worker registered'))
        .catch(err => console.error('Service Worker registration failed:', err));
}
</script>

</body>
</html>
