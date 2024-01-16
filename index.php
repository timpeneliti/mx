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

    // Cek apakah domain sudah ada di database
    $check_duplicate_query = "SELECT id FROM domains WHERE domain_name = '$domain'";
    $result_duplicate = $conn->query($check_duplicate_query);

    if ($result_duplicate->num_rows == 0) {
        // Simpan hasil ke database
        $insert_query = "INSERT INTO domains (domain_name, mx_record) VALUES ('$domain', '" . json_encode($mx_records) . "')";
        $conn->query($insert_query);

        // Tampilkan hasil
        $result_message = "MX Records for $domain:";

        // Redirect ke halaman utama untuk menghindari duplikat saat merefresh
        header("Location: index.php");
        exit();
    } else {
        $result_message = "Domain $domain already exists in the database.";
    }
}

// Ambil data dari database
$select_query = "SELECT id, domain_name, mx_record FROM domains";
$result = $conn->query($select_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX Record Checker</title>
    <style>
        .mx-list {
            list-style: none;
            padding: 0;
        }

        .mx-record {
            display: none;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h2>MX Record Checker</h2>

    <?php
    if (isset($result_message)) {
        echo "<p>$result_message</p>";
        if (is_array($mx_records)) {
            echo "<ul>";
            foreach ($mx_records as $record) {
                echo "<li><a href='javascript:void(0);' class='show-mx-record' data-domain='{$domain}'>{$domain}</a></li>";
                echo "<div class='mx-record'>"; // container for MX records
                echo "<ul>";
                foreach ($record as $mx) {
                    echo "<li>Priority: {$mx['pri']}, Mail Server: {$mx['target']}</li>";
                }
                echo "</ul>";
                echo "</div>";
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

    <?php
    // Tampilkan list dengan link delete
    if ($result->num_rows > 0) {
        echo "<h3>Existing Domains:</h3>";
        echo "<ul class='mx-list'>";
        while ($row = $result->fetch_assoc()) {
            $delete_link = "delete.php?id=" . $row['id'];
            echo "<li>";
            echo "<a href='javascript:void(0);' class='show-mx-record' data-domain='{$row['domain_name']}'>{$row['domain_name']}</a>";
            echo "<div class='mx-record' style='display:none;'>"; // container for MX records
            $mx_records = json_decode($row['mx_record'], true);
            echo "<ul>";
            foreach ($mx_records as $mx) {
                echo "<li>Priority: {$mx['pri']}, Mail Server: {$mx['target']}</li>";
            }
            echo "</ul>";
            echo "</div>";
            echo " - <a href='$delete_link'>Delete</a>"; // Add this line for delete link
            echo "</li>";
        }
        echo "</ul>";
    }
    ?>

    <script>
        // JavaScript to show/hide MX records when clicking on domain name
        document.addEventListener('DOMContentLoaded', function () {
            var showButtons = document.querySelectorAll('.show-mx-record');

            showButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var mxRecord = this.nextElementSibling;
                    mxRecord.style.display = mxRecord.style.display === 'none' ? 'block' : 'none';
                });
            });
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi ke database
$conn->close();
?>
