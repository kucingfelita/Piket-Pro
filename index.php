<?php
session_start();

// Simple router
$page = $_GET['page'] ?? 'login';

            // Controller logic
            switch ($page) {
                case 'login':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        require 'config/database.php';
                        $username = $_POST['username'] ?? '';
                        $password = $_POST['password'] ?? '';

                        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        $user = $stmt->fetch();

                        if ($user && password_verify($password, $user['password'])) {
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_full_name'] = $user['full_name'];
                            $_SESSION['user_role'] = $user['role'];

                            // Redirect to the appropriate dashboard
                            if ($user['role'] === 'admin') {
                                header('Location: index.php?page=admin_dashboard');
                            } else {
                                header('Location: index.php?page=siswa_dashboard');
                            }
                            exit;
                        } else {
                            // Login failed
                            $_SESSION['error_message'] = "Username atau password salah.";
                            header('Location: index.php?page=login');
                            exit;
                        }
                    }
                    break;

                case 'logout':
                    session_destroy();
                    header('Location: index.php?page=login');
                    exit;

                case 'admin_dashboard':
                case 'siswa_dashboard':
                case 'atur_jadwal':
                case 'jadwal':
                case 'lapor_piket':
                case 'konfirmasi_laporan':
                case 'laporan_bulanan':
                    // These pages have their own access control checks at the top of their files
                    break;

                case 'hapus_jadwal':
                    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                        // Redirect non-admins to login
                        header('Location: index.php?page=login');
                        exit;
                    }
                    require 'config/database.php';
                    $id = $_GET['id'] ?? 0;
                    if ($id > 0) {
                        $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
                        $stmt->execute([$id]);
                    }
                    // Redirect back to the schedule management page
                    header('Location: index.php?page=atur_jadwal');
                    exit;

                default:
                    // For any other page, redirect to login if user is not authenticated
                    if (!isset($_SESSION['user_id'])) {
                        // If the page doesn't exist, it will be caught by the file_exists check later
                        // This just protects authenticated pages
                    }
                    break;
            }

// Include header
require_once 'templates/layouts/header.php';

// Include page content
$pagePath = "templates/pages/{$page}.php";
if (file_exists($pagePath)) {
    require_once $pagePath;
} else {
    // Halaman 404 sederhana
    echo "<div class='alert alert-danger'>Halaman tidak ditemukan.</div>";
}

// Include footer
require_once 'templates/layouts/footer.php';

?>