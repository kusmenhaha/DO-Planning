<?php include 'partials/main.php' ?>

<head>
     <?php
    $subTitle = "Role Add";
    include 'partials/title-meta.php'; ?> 
       <?php include 'partials/head-css.php' ?>
</head>

<body>

     <!-- START Wrapper -->
     <div class="wrapper">

          <?php 
    $subTitle = "Create User Account";
    include 'partials/topbar.php'; ?>
<?php include 'partials/main-nav.php'; ?> 
          <!-- ==================================================== -->
          <!-- Start right Content here -->
          <!-- ==================================================== -->
          <div class="page-content">

               <!-- Start Container Fluid -->
               <div class="container-xxl">

                    <div class="row">
                         <div class="col-lg-12">
                              <div class="card">
                                   <div class="card-header">
                                        <h4 class="card-title">Form Create Account</h4>
                                   </div>
                                   <form method="POST" action="role-save.php">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="user-name" class="form-label">Nama Lengkap</label>
                    <input type="text" id="user-name" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Email" required pattern="^[a-zA-Z0-9._%+-]+@mail\.com$" title="Email harus menggunakan domain @mail.com">

                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>

            
            </div>

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

               <div class="col-lg-6">
                 <div class="mb-3">
              <label for="workplace" class="form-label">Work Place</label>
              <select id="workplace" name="workplace" class="form-control" required>
                  <option value="">-- Tempat Bekerja --</option>
                   <option value="DC - Rancaekek">DC - Rancaekek</option>
                    <option value="WU Tower">WU Tower</option>           
               </select>
               </div>
               </div>

        </div>
        <div class="col-lg-6">
                 <div class="mb-3">
                     <label class="form-label">Status</label>
                     <div class="form-check">
                         <input class="form-check-input" type="checkbox" id="status_active" name="status" value="Active">
                         <label class="form-check-label" for="status_active">Active
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="status_inactive" name="status" value="Inactive">
            <label class="form-check-label" for="status_inactive">
                Inactive
            </label>
        </div>
    </div>
               </div>
    </div>

    <div class="card-footer border-top">
        <button type="submit" class="btn btn-primary">Create User</button>
    </div>
    
</form>

                                   
                                   
                              </div>
                         </div>
                    </div>

                    
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