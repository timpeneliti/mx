<?php
// index.php

// Koneksi ke database
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "ping";

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fungsi untuk mengecek MX record (sesuaikan dengan kebutuhan)
function check_mx_records($domain) {
    $mx_records = [];
    if (getmxrr($domain, $mx_records, $mx_weights)) {
        // Gabungkan hasil prioritas dan mail server
        $merged_records = array_map(function($target, $pri) {
            return ['target' => $target, 'pri' => $pri];
        }, $mx_records, $mx_weights);

        // Urutkan MX record berdasarkan prioritas
        usort($merged_records, function($a, $b) {
            return $a['pri'] - $b['pri'];
        });

        return $merged_records;
    } else {
        return "No MX records found";
    }
}

// Proses form ketika disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil domain dari form
    $domain = $_POST['domain'];

    // Lakukan pengecekan MX record
    $mx_records = check_mx_records($domain);

    // Simpan hasil ke database
    $insert_query = "INSERT INTO domains (domain_name, mx_record) VALUES ('$domain', '" . json_encode($mx_records) . "')";
    $conn->query($insert_query);

    // Tampilkan hasil
    $result_message = "MX Records for $domain:";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX Record Checker</title>
</head>
<body>
    <h2>MX Record Checker</h2>

    <?php
    if (isset($result_message)) {
        echo "<p>$result_message</p>";
        if (is_array($mx_records)) {
            echo "<ul>";
            foreach ($mx_records as $record) {
                echo "<li>Priority: {$record['pri']}, Mail Server: {$record['target']}</li>";
            }
            echo "</ul>";
        }
    }
    ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <label for="domain">Domain:</label>
        <input type="text" name="domain" id="domain" required>
        <button type="submit">Check MX Record</button>
    </form>
</body>
</html>

<?php
// Tutup koneksi ke database
$conn->close();
?>
