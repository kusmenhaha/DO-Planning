<?php include 'partials/main.php' ?>


<head>
     <?php
    $subTitle = "Dashboard";
    include 'partials/title-meta.php'; ?>

       <?php include 'partials/head-css.php' ?>
</head>

<body>

     <!-- START Wrapper -->
     <div class="wrapper">

          <?php 
    $subTitle = "Welcome Admin!";
    include 'partials/topbar.php'; ?>
<?php include 'partials/main-nav.php'; ?>

          <!-- ==================================================== -->
          <!-- Start right Content here -->
          <!-- ==================================================== -->
          <div class="page-content">

               <!-- Start Container Fluid -->
               <div class="container-fluid">

                    <!-- Start here.... -->
                    <div class="row">
                         <div class="col-xxl-5">
                              <div class="row">
                                   <div class="col-12">
                                        <div class="alert alert-primary text-truncate mb-3" role="alert">
                                             We regret to inform you that our server is currently experiencing technical difficulties.
                                        </div>
                                   </div>

                                    <!-- end col -->
                              </div> <!-- end row -->
                         </div> <!-- end col -->

                      
               <!-- End Container Fluid -->

                 <?php include 'partials/footer.php' ?>

          </div>
          <!-- ==================================================== -->
          <!-- End Page Content -->
          <!-- ==================================================== -->

     </div>
     <!-- END Wrapper -->

       <?php include 'partials/vendor-scripts.php' ?>

     <!-- Vector Map Js -->
     <script src="assets/vendor/jsvectormap/jsvectormap.min.js"></script>
     <script src="assets/vendor/jsvectormap/maps/world-merc.js"></script>
     <script src="assets/vendor/jsvectormap/maps/world.js"></script>

     <!-- Dashboard Js -->
     <script src="assets/js/pages/dashboard.js"></script>

</body>

</html>