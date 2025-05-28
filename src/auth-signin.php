<?php
include('services/session.php'); ?>


<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
     <?php
     $subTitle = "Sign In";
     include 'partials/title-meta.php'; 
     include 'partials/head-css.php'; 
     ?>
</head>

<body class="h-100">
     <div class="d-flex flex-column h-100 p-3">
          <div class="d-flex flex-column flex-grow-1">
               <div class="row h-100">
                    <div class="col-xxl-7">
                         <div class="row justify-content-center h-100">
                              <div class="col-lg-6 py-lg-5">
                                   <div class="d-flex flex-column h-100 justify-content-center">
                                        <div class="auth-logo mb-4">
                                             <a href="index.php" class="logo-dark">
                                                  <img src="assets/images/logo-dark.png" height="24" alt="logo dark">
                                             </a>
                                             <a href="index.php" class="logo-light">
                                                  <img src="assets/images/logo-light.png" height="24" alt="logo light">
                                             </a>
                                        </div>

                                        <h2 class="fw-bold fs-24">Sign In</h2>
                                        <p class="text-muted mt-1 mb-4">Enter your email address and password to access admin panel.</p>

                                        <!-- Menampilkan error jika ada -->
                                        <?php if (isset($_SESSION['error'])): ?>
                                            <div class="alert alert-danger">
                                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-5">
                                             <form method="POST" class="authentication-form">
                                                  <div class="mb-3">
                                                       <label class="form-label" for="example-email">Email</label>
                                                       <input type="email" id="example-email" name="email" class="form-control bg-" placeholder="Enter your email" required>
                                                  </div>
                                                  <div class="mb-3">
                                                       <a href="auth-password.php" class="float-end text-muted text-unline-dashed ms-1">Reset password</a>
                                                       <label class="form-label" for="example-password">Password</label>
                                                       <input type="password" id="example-password" name="password" class="form-control" placeholder="Enter your password" required>
                                                  </div>
                                                  <div class="mb-3">
                                                       <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="checkbox-signin">
                                                            <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                                       </div>
                                                  </div>
                                                  <div class="mb-1 text-center d-grid">
                                                       <button class="btn btn-soft-primary" type="submit" name="login">Sign In</button>
                                                  </div>

                                             </form>
                                        </div>

                                        <!-- <p class="text-danger text-center">Don't have an account? <a href="auth-signup.php" class="text-dark fw-bold ms-1">Sign Up</a></p> -->
                                   </div>
                              </div>
                         </div>
                    </div>

                    <div class="col-xxl-5 d-none d-xxl-flex">
                         <div class="card h-100 mb-0 overflow-hidden">
                              <div class="d-flex flex-column h-100">
                                   <img src="assets/images/small/img-10.jpg" alt="" class="w-100 h-100">
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
<script>
document.getElementById('resetPasswordLink').addEventListener('click', function() {
    // Menampilkan modal Hubungi Admin
    var myModal = new bootstrap.Modal(document.getElementById('contactAdminModal'), {
        keyboard: false
    });
    myModal.show();
});
</script>

     <?php include 'partials/vendor-scripts.php'; ?>
</body>
</html>
