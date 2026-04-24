<?php
// Cache buster: 1728383334
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="text-center">Password Hash Generator</h3>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Masukkan Password:</label>
                            <input type="text" class="form-control" id="password" name="password" placeholder="Contoh: password123" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Generate Hash</button>
                        </div>
                    </form>

                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
                        $password = $_POST['password'];
                        // Hash the password using the default algorithm (currently bcrypt)
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        echo '<div class="mt-4 alert alert-info">';
                        echo '<h4>Hasil:</h4>';
                        echo '<p class="mb-1"><strong>Password Anda:</strong> ' . htmlspecialchars($password) . '</p>';
                        echo '<p class="mb-2"><strong>Hash yang Dihasilkan:</strong></p>';
                        echo '<textarea class="form-control" rows="3" readonly>' . $hash . '</textarea>';
                        echo '<p class="mt-3 mb-0"><em>Salin hash ini dan gunakan di kolom \'password\' pada database Anda.</em></p>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="card-footer text-muted">
                    Script ini menggunakan fungsi <code>password_hash()</code> bawaan PHP untuk keamanan terbaik.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
