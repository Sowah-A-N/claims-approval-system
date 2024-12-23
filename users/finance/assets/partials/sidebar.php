<!-- Sidebar Start -->
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div>
      <div class="brand-logo d-flex align-items-center justify-content-between">
        <a href="/doc-approval-system/users/admin" class="text-nowrap logo-img">
          <?php if($pageTitle && $pageTitle == "Admin Dashboard"):?>
          <!--img src="./assets/images/logos/dark-logo.svg" width="180" alt="" /-->
			<h3 class="text-muted">Admin Dashboard</h3> 
          <?php else: ?>
          <img src="./assets/images/logos/dark-logo.svg" width="180" alt="" />
          <?php endif; ?>
        </a>
        <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
          <i class="ti ti-x fs-8"></i>
        </div>
      </div>
      <!-- Sidebar navigation-->
      <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
        <ul id="sidebarnav">
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">HOME</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="/users/finance" aria-expanded="false">
              <span>
                <i class="ti ti-layout-dashboard"></i>
              </span>
              <span class="hide-menu">Dashboard</span>
            </a>
          </li>
         
          <li class="sidebar-item">
            <a class="sidebar-link" href="/users/finance/pages/logout" aria-expanded="false">
              <span>
                <i class="ti ti-logout"></i>
              </span>
              <span class="hide-menu">LOGOUT</span>
            </a>
          </li>
        </ul>       
      </nav>
      <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
  </aside>
  <!--  Sidebar End -->