<?php
// templates/header.php
// Dipanggil di awal setiap halaman: include 'templates/header.php';
// Variabel yang bisa di-set sebelum include:
//   $pageTitle  = 'Judul Halaman'
//   $activePage = 'dashboard' | 'kasir' | 'riwayat' | 'employee' | 'settings'
if (!isset($pageTitle)) $pageTitle = 'Kasir Toko';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aplikasi Kasir Toko - Point of Sale modern berbasis web">
    <title><?= htmlspecialchars($pageTitle) ?> — Kasir Toko</title>
    <link rel="icon" href="data:,">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': '#4DB9F2',
                        'brand-dark': '#1e3a5f',
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- SheetJS (Excel export) -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-100">
<!-- Layout Wrapper -->
<div class="flex">
