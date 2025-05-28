<?php include 'partials/main.php' ?>

     <head>
          <?php
    $subTitle = "404";
    include 'partials/title-meta.php'; ?>

            <?php include 'partials/head-css.php' ?>
     </head>

     <body>

          <!-- START Wrapper -->
          <div class="wrapper">

               <?php 
    $subTitle = "Page 404 (alt)";
    include 'partials/topbar.php'; ?>
<?php include 'partials/main-nav.php'; ?>

               <!-- ==================================================== -->
               <!-- Start right Content here -->
               <!-- ==================================================== -->
               <div class="page-content">

                    <!-- Start Container Fluid -->
                    <div class="container-xxl">

                         <!-- Start here.... -->

                         <div class="row align-items-center justify-content-center">
                              <div class="col-xl-5">
                                   <div class="card">
                                        <div class="card-body px-3 py-4">
                                             <div class="row align-items-center justify-content-center h-100">
                                                  <div class="col-lg-10">                                                      
                                                       <div class="mx-auto text-center">
                                                            <img src="assets/images/404-error.png" alt="" class="img-fluid my-3">
                                                       </div>
                                                       <h3 class="fw-bold text-center lh-base">Ooops! The Page You're Looking For Was Not Found</h3>
                                                       <p class="text-muted text-center mt-1 mb-4">Sorry, we couldn't find the page you were looking for. We suggest that you return to main sections</p>
                                                       <div class="text-center">
                                                            <a href="index.php" class="btn btn-primary">Back To Home</a>
                                                       </div>
                                                  </div>
                                             </div>
                                        </div> <!-- end card-body -->
                                   </div> <!-- end card -->
                                   
                              </div> <!-- end col -->
                         </div> <!-- end row -->
                         
                    </div>
                    <!-- End Container Fluid -->

                 <?php include 'partials/footer.php' ?>

               </div>
               <!-- ==================================================== -->
               <!-- End Page Content -->
               <!-- ==================================================== -->

          </div>
          <!-- END Wrapper -->

            <?php include 'partials/vendor-scripts.php' ?>

     </body>

</html>