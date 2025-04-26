<?php
$address =  '0x1c5f8e8E84AcC71650F7a627cfA5B24B80f44f00';

function getTransactions($address) {
    $url = "https://api.scan.pulsechain.com/api/v2/addresses/$address/transactions?filter=to%20%7C%20from&limit=200";

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
                <th>Time</th>
                <th>Block</th>
                <th>Age</th>
                <th>From</th>
                <th>To</th>
                <th>Tokens Transfer</th>
              

                <th>Txn Fee</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($txns as $i => $txn): ?>
                <tr>
                    <td class="text-break"><?php echo substr($txn['hash'], 0, 15) . '...'; ?></td>
                    <td><?php echo $txn['method'] ?? 'Transfer'; ?></td>
                    <td>
    <?php 
    $dateTime = new DateTime($txn['timestamp']);
    echo $dateTime->format('d M Y, H:i');
    ?>
</td>
                    <td><?php echo $txn['block']; ?></td>
                    <td><?php echo $txn['age'] ?? 'N/A'; ?></td>
                    <td class="text-break small"><?php echo substr($txn['from']['hash'], 0, 15) . '...'; ?></td>
                    <td class="text-break small"><?php echo substr($txn['to']['hash'], 0, 15) . '...' ?? 'Contract'; ?></td>


                    <td><?php
                    if ($txn['token_transfers'] != null) {
                        foreach ($txn['token_transfers'] as $transfer) {
                            $symbol = $transfer['token']['symbol'] ?? 'Unknown';
                            $value = $transfer['value'] ?? 0;
                            $decimals = $transfer['token']['decimals'] ?? 18;
                            $amount = $value / pow(10, $decimals);
                            echo "<div><strong>{$symbol}:</strong> " . number_format($amount, 6) . "</div>";
                        }
                    } else {
                        // If it's a simple PLS transfer
                        $amountPLS = hexdec($txn['value']) / 1e18;
                        echo "<div>" . number_format($amountPLS, 6) . "</div>";
                    }
                    ?></td>
   

                    <td><?php echo number_format((hexdec($txn['gas_price']) * $txn['gas_used']) / 1e18, 6) . ' PLS'; ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary view-transaction" data-hash="<?php echo $txn['hash']; ?>">
                            View
                        </button>
                    </td>
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




<div class="modal fade" id="txnModal" tabindex="-1" aria-labelledby="txnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="txnModalLabel">Transaction Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="transactionDetailsTable" class="table table-bordered table-sm">
          <tbody>
            <!-- Details will be injected here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>













<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
    $(document).ready(function() {
    $(document).on('click', '.view-transaction', function() {
        var hash = $(this).data('hash');
        var tableBody = $('#transactionDetailsTable tbody');
        tableBody.empty();

        // Fetch transaction details using hash
        $.ajax({
            url: 'https://api.scan.pulsechain.com/api/v2/transactions/' + hash,
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                $.each(response, function(key, value) {
                    if (typeof value === 'object' && value !== null) {
                        value = '<pre>' + JSON.stringify(value, null, 2) + '</pre>';
                    }
                    tableBody.append(
                        '<tr>' +
                            '<th>' + key.replace(/_/g, ' ').toUpperCase() + '</th>' +
                            '<td>' + value + '</td>' +
                        '</tr>'
                    );
                });

                var txnModal = new bootstrap.Modal(document.getElementById('txnModal'));
                txnModal.show();
            },
            error: function() {
                tableBody.html('<tr><td colspan="2" class="text-danger">Failed to fetch transaction details.</td></tr>');
                var txnModal = new bootstrap.Modal(document.getElementById('txnModal'));
                txnModal.show();
            }
        });
    });
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
