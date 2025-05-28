<?php include 'partials/main.php' ?>

     <head>
          <?php
    $subTitle = "Coming Soon";
    include 'partials/title-meta.php'; ?>

            <?php include 'partials/head-css.php' ?>
     </head>

     <body class="vh-100">

          <div class="d-flex flex-column h-100 p-3">
               <div class="d-flex flex-column flex-grow-1">
                    <div class="row h-100">
                         <div class="col-xxl-7">
                              <div class="row align-items-center justify-content-center h-100">
                                   <div class="col-lg-8">
                                        <div class="auth-logo mb-3 text-center">
                                             <a href="index.php" class="logo-dark">
                                                  <img src="assets/images/logo-dark.png" height="24" alt="logo dark">
                                             </a>
     
                                             <a href="index.php" class="logo-light">
                                                  <img src="assets/images/logo-light.png" height="24" alt="logo light">
                                             </a>
                                        </div>
                                        <div class="mx-auto text-center">
                                             <img src="assets/images/coming-soon.png" alt="" class="h-auto my-3" style="max-width: 80%;">
                                        </div>
                                        <div class="row text-center my-4">
                                             <div class="col border-end">
                                                  <h3 id="days" class="fw-bold fs-35">00</h3>
                                                  <p class="text-uppercase fw-semibold mb-0">Days</p>
                                             </div>
                                             <div class="col border-end">
                                                  <h3 id="hours" class="fw-bold fs-35">00</h3>
                                                  <p class="text-uppercase fw-semibold mb-0">Hours</p>
                                             </div>
                                             <div class="col border-end">
                                                  <h3 id="minutes" class="fw-bold fs-35">00</h3>
                                                  <p class="text-uppercase fw-semibold mb-0">Minutes</p>
                                             </div>
                                             <div class="col">
                                                  <h3 id="seconds" class="fw-bold fs-35">00</h3>
                                                  <p class="text-uppercase fw-semibold mb-0">Seconds</p>
                                             </div>
                                        </div>                                       
                                        <p class="text-muted text-center mt-1 mb-4">Exciting news is on the horizon! We're thrilled to announce that something incredible is coming your way very soon. Our team has been hard at work behind the scenes, crafting something special just for you.</p>
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

          <!-- Page Js -->
          <script src="assets/js/pages/coming-soon.js"></script>

     </body>

</html>