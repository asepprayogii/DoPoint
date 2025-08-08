<?php
function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function display_error($message) {
    return '<div class="error">'.$message.'</div>';
}

/**
 * Menentukan level user berdasarkan jumlah reward yang telah diterima.
 *
 * Aturan:
 * - Master: >= 10 reward diterima
 * - Pejuang: >= 5 reward diterima
 * - Pemula: lainnya
 */
function getLevel(int $numAcceptedRewards): string {
    if ($numAcceptedRewards >= 10) {
        return 'Master';
    }
    if ($numAcceptedRewards >= 5) {
        return 'Pejuang';
    }
    return 'Pemula';
}
?>