<?php include 'partials/main.php' ?>

<head>
     <?php
    $subTitle = "Maintenance";
    include 'partials/title-meta.php'; ?>

       <?php include 'partials/head-css.php' ?>
</head>

<body class="vh-100">

     <div class="d-flex flex-column h-100 p-3">
          <div class="d-flex flex-column flex-grow-1">
               <div class="row h-100">
                    <div class="col-xxl-7">
                         <div class="row align-items-center justify-content-center h-100">
                              <div class="col-lg-10">
                                   <div class="auth-logo mb-3 text-center">
                                        <a href="index.php" class="logo-dark">
                                             <img src="assets/images/logo-dark.png" height="24" alt="logo dark">
                                        </a>

                                        <a href="index.php" class="logo-light">
                                             <img src="assets/images/logo-light.png" height="24" alt="logo light">
                                        </a>
                                   </div>
                                   <div class="mx-auto text-center">
                                        <img src="assets/images/maintenance-2.png" alt="" class="img-fluid my-3" height="700" width="700">
                                   </div>
                                   <h2 class="fw-bold text-center lh-base">We are currently performing maintenance</h2>
                                   <p class="text-muted text-center mt-1 mb-4">We're making the system more awesome.
                                        We'll be back shortly.</p>

                                   <div class="text-center">
                                        <a href="index.php" class="btn btn-primary">Back To Home</a>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <div class="col-xxl-5 d-none d-xxl-flex">
                         <div class="card h-100 mb-0 overflow-hidden">
                              <div class="d-flex flex-column h-100">
                                   <img src="assets/images/small/img-10.jpg" alt="" class="w-100 h-100">
                              </div>
                         </div> <!-- end card -->
                    </div>
               </div>
          </div>
     </div>

       <?php include 'partials/vendor-scripts.php' ?>

</body>

</html>