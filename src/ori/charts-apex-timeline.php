<?php include 'partials/main.php' ?>

    <head>
        <?php
    $subTitle = "Apex Timeline Chart";
    include 'partials/title-meta.php'; ?>

        <?php include 'partials/head-css.php' ?>
    </head>

    <body>
        <!-- Begin page -->
        <div class="wrapper">

            <?php 
    $subTitle = "Apex Timeline Chart";
    include 'partials/topbar.php'; ?>
<?php include 'partials/main-nav.php'; ?>

            <!-- ==================================================== -->
            <!-- Start Page Content here -->
            <!-- ==================================================== -->
            <div class="page-content">

                <!-- Start Content-->
                <div class="container">

                    <div class="row">
                        <div class="col-xl-9">
                           <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title anchor mb-1" id="overview">
                                        Overview 
                                    </h5>
                                    
                                    <p class="mb-0"><span class="fw-medium">Find the JS file for the following chart at:</span> <code> assets/js/components/apexchart-timeline.js</code></p>
                                </div><!-- end card-body -->
                            </div><!-- end card -->

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3 anchor" id="basic">Basic Timeline</h4>
                                    <div dir="ltr">
                                        <div id="basic-timeline" class="apex-charts"></div>
                                    </div>
                                </div>
                                <!-- end card body-->
                            </div>
                            <!-- end card -->

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3 anchor" id="distributed">Distributed Timeline </h4>
                                    <div dir="ltr">
                                        <div id="distributed-timeline" class="apex-charts"></div>
                                    </div>
                                </div>
                                <!-- end card body-->
                            </div>
                            <!-- end card -->

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3 anchor" id="multi-series">Multi Series Timeline</h4>

                                    <div dir="ltr">
                                        <div id="multi-series-timeline" class="apex-charts"></div>
                                    </div>
                                </div>
                                <!-- end card body-->
                            </div>
                            <!-- end card -->

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3 anchor" id="advanced">Advanced Timeline</h4>
                                    <div dir="ltr">
                                        <div id="advanced-timeline" class="apex-charts"></div>
                                    </div>
                                </div>
                                <!-- end card body-->
                            </div>
                            <!-- end card -->

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3 anchor" id="group-rows">Multiple Series - Group Rows</h4>
                                    <div dir="ltr">
                                        <div id="group-rows-timeline" class="apex-charts"></div>
                                    </div>
                                </div>
                                <!-- end card body-->
                            </div>
                            <!-- end card -->
                        </div> <!-- end col -->

                        <div class="col-xl-3">
                            <div class="card docs-nav">
                                <ul class="nav bg-transparent flex-column">
                                    <li class="nav-item">
                                        <a href="#basic" class="nav-link">Basic Timeline</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#distributed" class="nav-link">Distributed Timeline</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#multi-series" class="nav-link">Multi Series Timeline</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#advanced" class="nav-link">Advanced Timeline</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#group-rows" class="nav-link">Multiple Series - Group Rows</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end row -->

                </div> <!-- container -->

                <?php include "partials/footer.php" ?>
                
            </div>
            <!-- ==================================================== -->
            <!-- End Page content -->
            <!-- ==================================================== -->


        </div>
        <!-- END wrapper -->

         <?php include "partials/vendor-scripts.php" ?>

        <!-- Apex Chart Timeline Demo js -->
        <script src="assets/js/components/apexchart-timeline.js"></script>

    </body>
</html>