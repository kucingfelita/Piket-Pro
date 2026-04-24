<?php
$pageTitle = "Login";

// Logika login akan ditambahkan di sini nanti
// ...

?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4">Login PiketPro</h3>
                
                <?php if (isset($_SESSION['error_message'])):
                ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>

                <form action="index.php?page=login" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">NIS / Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
