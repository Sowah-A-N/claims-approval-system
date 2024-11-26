<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
	  <a class="sidebar-brand brand-logo d-flex align-items-center text-decoration-none" href="./">
			<h3 class="mb-0 text-light">User</h3>
		</a>

    <?php if($pageTitle !== "User Dashboard") :?>
      <a class="sidebar-brand brand-logo-mini" href="../../"></a>
    <?php else :?>
      <a class="sidebar-brand brand-logo-mini" href="."></a>
    <?php endif ;?>

  </div>
  <ul class="nav">
    <li class="nav-item profile">
      <div class="profile-desc">
        <div class="profile-pic">
          <div class="count-indicator">
            <img class="img-xs rounded-circle " src="./assets/images/faces/face15.jpg" alt="">
            <span class="count bg-success"></span>
          </div>
          <div class="profile-name">
            <h5 class="mb-0 font-weight-normal"><?php echo $_SESSION['full_name'] ?? "No user found!!" ?></h5>
            <span><?php echo $_SESSION['role'] ?? "No role found!!" ?></span>
          </div>
        </div>
        <a href="#" id="profile-dropdown" data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-right sidebar-dropdown preview-list" aria-labelledby="profile-dropdown">
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-cog text-primary"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1 text-small">Account settings</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-onepassword  text-info"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1 text-small">Change Password</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-calendar-today text-success"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1 text-small">To-do list</p>
            </div>
          </a>
        </div>
      </div>
    </li>
    <li class="nav-item nav-category">
      <span class="nav-link">Navigation</span>
    </li>
    <li class="nav-item menu-items">
      <a class="nav-link" href="/users/user/">
        <span class="menu-icon">
          <i class="mdi mdi-home" style="font-size: 1.5rem;"></i>
        </span>
        <span class="menu-title text-white">Home</span>
      </a>
    </li>
    <li class="nav-item menu-items">

    <?php if($pageTitle !== "User Dashboard" && $pageTitle == "File New Claim"):?>
      <a class="nav-link" href="/users/user/pages/fileNewClaim">
    <?php else :?>
      <a class="nav-link" href="/users/user/pages/fileNewClaim">
    <?php endif ;?>

        <span class="menu-icon">
          <i class="mdi mdi-plus" style="font-size: 1.5rem;"></i>
        </span>
        <span class="menu-title text-white">File New Claim</span>
        <i class="menu-arrow"></i>
      </a>
    </li>

    <li class="nav-item menu-items">
    <!-- <?php if($pageTitle !== "User Dashboard" && $pageTitle == "My Claims"):?>
      <a class="nav-link" href="./">
    <?php else :?>
      <a class="nav-link" href="./pages/myClaims">
    <?php endif ;?> -->
    <a class="nav-link" href="/users/user/pages/myClaims">

    <span class="menu-icon">
          <i class="mdi mdi-file-document" style="font-size: 1.5rem;"></i>
        </span>
        <span class="menu-title text-white">My Claims</span>
        <i class="menu-arrow"></i>
      </a>
    </li>
    
    <li class="nav-item menu-items">
      <a class="nav-link" href="/users/user/pages/settings">
        <span class="menu-icon">
          <i class="mdi mdi-cog" style="font-size: 1.5rem;"></i>
        </span>
        <span class="menu-title text-white">Settings</span>
        <i class="menu-arrow"></i>
      </a>
    </li>
   
    <li class="nav-item menu-items">
      <a class="nav-link" href="/users/user/pages/logout/">
        <span class="menu-icon">
          <i class="mdi mdi-logout" style="font-size: 1.5rem;"></i>
        </span>
        <span name='logout' class="menu-title text-white">Logout</span>
      </a>
    </li>
  </ul>
</nav>