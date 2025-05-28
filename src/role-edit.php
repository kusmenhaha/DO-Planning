<?php
include 'services/config.php'; // koneksi ke database

// Query masing-masing dropdown sekali saja
$nikResult = $conn->query("SELECT DISTINCT nik FROM users ORDER BY nik ASC");
$namalengkapResult = $conn->query("SELECT DISTINCT nama_lengkap FROM users ORDER BY nama_lengkap ASC");
$emailResult = $conn->query("SELECT DISTINCT email FROM users ORDER BY email ASC");
$roleResult = $conn->query("SELECT DISTINCT role FROM users ORDER BY role ASC");
$workplaceResult = $conn->query("SELECT DISTINCT workplace FROM users ORDER BY workplace ASC");

include 'partials/main.php';
?>

<head>
    <?php
    $subTitle = "Role Add";
    include 'partials/title-meta.php';
    include 'partials/head-css.php';
    ?>
</head>

<body>
    <div class="wrapper">
        <?php
        $subTitle = "Create User Account";
        include 'partials/topbar.php';
        include 'partials/main-nav.php';
        ?>

        <div class="page-content">
            <div class="container-xxl">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Form Create Account</h4>
                            </div>
                            <form method="POST" action="role-update.php">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- NIK -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="nik" class="form-label">NIK</label>
                                                <select id="nik" name="nik" class="form-control" required>
                                                    <option value="">-- Pilih NIK --</option>
                                                    <?php while ($row = $nikResult->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($row['nik']) ?>"><?= htmlspecialchars($row['nik']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Nama Lengkap -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                                <select id="nama_lengkap" name="nama_lengkap" class="form-control" required>
                                                    <option value="">-- Pilih Nama Lengkap --</option>
                                                    <?php while ($row = $namalengkapResult->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($row['nama_lengkap']) ?>"><?= htmlspecialchars($row['nama_lengkap']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <select id="email" name="email" class="form-control" required>
                                                    <option value="">-- Pilih Email --</option>
                                                    <?php while ($row = $emailResult->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Role -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="role" class="form-label">Role</label>
                                                <select id="role" name="role" class="form-control" required>
                                                    <option value="">-- Pilih Role --</option>
                                                    <option value="Admin">Admin</option>
                                                    <option value="User">User</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Workplace -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="workplace" class="form-label">Work Place</label>
                                                <select id="workplace" name="workplace" class="form-control" required>
                                                    <option value="">-- Tempat Bekerja --</option>
                                                    <?php while ($row = $workplaceResult->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($row['workplace']) ?>"><?= htmlspecialchars($row['workplace']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" id="status_active" name="status" value="Active" required>
                                                    <label class="form-check-label" for="status_active">Active</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" id="status_inactive" name="status" value="Inactive" required>
                                                    <label class="form-check-label" for="status_inactive">Inactive</label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer border-top">
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'partials/footer.php'; ?>
        </div>
    </div>

    <?php include 'partials/vendor-scripts.php'; ?>
</body>

</html>
