<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Aset Seluruh Cabang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #f4f6f8;
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #212529;
            padding-top: 1rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar .logo {
            text-align: center;
            padding: 1rem 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid #444;
        }

        .sidebar .logo h4 {
            font-weight: 700;
            color: #fff;
            margin: 0;
        }

        .sidebar .logo .logo-icon {
            font-size: 2rem;
            color: #6861ce;
            margin-bottom: 0.5rem;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #343a40;
            padding-left: 25px;
        }

        .main {
            margin-left: 250px;
            padding: 2rem;
        }

        .navbar-custom {
            margin-left: 250px;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .table thead {
            background: linear-gradient(to right, #007bff, #00bcd4);
            color: white;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="sidebar text-white">
        <div class="logo">
            <div class="logo-icon">
                <i class="bi bi-building"></i>
            </div>
            <h4>Panel Admin</h4>
        </div>
        <a href="dashboard_admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="data_aset.php"><i class="bi bi-box"></i> Data Aset</a>
        <a href="log_keputusan.php"><i class="bi bi-building"></i> Log Keputusan</a>
        <a href="rencana_pengadaan.php"><i class="bi bi-journal-text"></i> Rencana Pengadaan</a>
        <a href="notifikasi.php"><i class="bi bi-check-circle"></i> Notifikasi</a>
        <a href="lampiran.php"><i class="bi bi-paperclip"></i> Lampiran</a>
        <a href="riwayat_laporan.php"><i class="bi bi-book"></i> Riwayat Laporan</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">Data Aset Seluruh Cabang</span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h4 class="mb-0">Data Aset</h4>
                <div id="exportButtons" class="d-flex gap-2 flex-wrap"></div>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="filterCabangButton" data-bs-toggle="dropdown" aria-expanded="false">
                        Filter Cabang
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterCabangButton">
                        <li><a class="dropdown-item" href="#" data-cabang="">Semua Cabang</a></li>
                        <li><a class="dropdown-item" href="#" data-cabang="Gunung Mas">Gunung Mas</a></li>
                        <li><a class="dropdown-item" href="#" data-cabang="Kapuas">Kapuas</a></li>
                        <li><a class="dropdown-item" href="#" data-cabang="Pulang Pisau">Pulang Pisau</a></li>
                        <li><a class="dropdown-item" href="#" data-cabang="Katingan">Katingan</a></li>
                    </ul>
                </div>
                <a href="tambah_aset.php" class="btn btn-success">+ Tambah Aset</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="asetTable" class="table table-striped table-hover align-middle rounded-3 overflow-hidden">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Aset</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Cabang</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#asetTable').DataTable({
                ajax: 'get_aset.php',
                columns: [{
                        data: 'kode_aset'
                    },
                    {
                        data: 'nama_aset'
                    },
                    {
                        data: 'kategori'
                    },
                    {
                        data: 'deskripsi'
                    },
                    {
                        data: 'nama_cabang'
                    },
                    {
                        data: 'kondisi',
                        render: function(data) {
                            let badgeClass = data === 'baik' ? 'success' : (data === 'rusak' ? 'danger' : 'warning');
                            return `<span class="badge bg-${badgeClass}">${data}</span>`;
                        }
                    },
                    {
                        data: 'id',
                        render: function(id) {
                            return `<a href="edit_aset.php?id=${id}" class="btn btn-warning btn-sm rounded-pill">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>`;
                        }
                    }
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success rounded-pill shadow-sm me-2 mb-2',
                        text: '<i class="bi bi-file-earmark-excel-fill"></i> Excel',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Hanya kolom Kode, Nama Aset, Kategori, Deskripsi, Cabang, Kondisi
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        className: 'btn btn-danger rounded-pill shadow-sm me-2 mb-2',
                        text: '<i class="bi bi-file-earmark-pdf-fill"></i> PDF',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Hanya kolom Kode, Nama Aset, Kategori, Deskripsi, Cabang, Kondisi
                        }
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary rounded-pill shadow-sm me-2 mb-2',
                        text: '<i class="bi bi-printer-fill"></i> Cetak',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Hanya kolom Kode, Nama Aset, Kategori, Deskripsi, Cabang, Kondisi
                        }
                    },
                    {
                        extend: 'copyHtml5',
                        className: 'btn btn-primary rounded-pill shadow-sm mb-2',
                        text: '<i class="bi bi-clipboard-fill"></i> Salin',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Hanya kolom Kode, Nama Aset, Kategori, Deskripsi, Cabang, Kondisi
                        }
                    }
                ],
                language: {
                    search: "🔍 Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "→",
                        previous: "←"
                    },
                    zeroRecords: "🚫 Data tidak ditemukan"
                }
            });

            // Refresh otomatis setiap 5 detik
            setInterval(function() {
                table.ajax.reload(null, false);
            }, 5000);

            // Filter berdasarkan cabang
            $('.dropdown-item').click(function(e) {
                e.preventDefault();
                var cabang = $(this).data('cabang');
                if (cabang === '') {
                    // Jika memilih "Semua Cabang", tampilkan semua data
                    table.search('').columns().search('').draw();
                } else {
                    // Filter berdasarkan nilai cabang yang dipilih
                    table.column(4).search(cabang).draw();
                }
            });
        });
    </script>
</body>

</html>