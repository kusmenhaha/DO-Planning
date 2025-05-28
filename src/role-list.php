<?php include 'partials/main.php'; ?>

<head>
    <?php
    $subTitle = "USER Table";
    include 'partials/title-meta.php'; ?> 

    <!-- Gridjs Plugin css -->
    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <?php include 'partials/head-css.php'; ?>
</head>
<script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
<body>
    <div class="wrapper">
        <?php 
            // $subTitle = "Gridjs Table";
            include 'partials/topbar.php';
            include 'partials/main-nav.php'; 
        ?>

        <div class="page-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3" id="search">USER LIST</h5>
                                <div class="table-responsive py-2">
                                    <div id="table-search"></div>
                                </div>
                                <!-- Tombol Hapus Semua Data -->
                                <!-- <form method="POST" action="item-bank-delete.php" id="delete-form">
                                    <button class="btn btn-danger mt-3" type="submit" name="delete_all" id="delete-all-data">Hapus Semua Data</button>
                                </form> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/footer.php'; ?>
    </div>

    <?php include 'partials/vendor-scripts.php'; ?>

    <script src="assets/vendor/gridjs/gridjs.umd.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    new gridjs.Grid({
        columns: [
            "ID", "NIK", "Nama Lengkap", "Email", "Role", "Work Place", "Status"
        ],
        search: true,
        pagination: {
            enabled: true,
            limit: 10
        },
        sort: true,
        server: {
            url: 'get-users.php',
            then: data => {
                console.log("Data:", data);
                return data;
            }
        },
        style: {
            th: {
                'background-color': '#f8f9fa',
                'text-align': 'center'
            },
            td: {
                'text-align': 'center'
            }
        }
    }).render(document.getElementById("table-search"));
});
</script>

</body>
</html>
