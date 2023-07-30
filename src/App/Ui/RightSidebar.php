<?php

namespace App\Ui;

use Tk\Traits\SystemTrait;

class RightSidebar
{
    use SystemTrait;

    public static function getHtml(): string
    {
        $html = <<<HTML
<div>
  <div class="offcanvas offcanvas-end right-bar" tabindex="-1" id="theme-settings-offcanvas" data-bs-scroll="true" data-bs-backdrop="true">
    <div data-simplebar class="h-100">

      <!-- Tab panes -->
      <div class="tab-content pt-0">

        <div class="tab-pane active" id="settings-tab" role="tabpanel">
          <h6 class="fw-medium px-3 m-0 py-2 font-13 text-uppercase bg-light">
            <span class="d-block py-1">Theme Settings</span>
          </h6>

          <div class="p-3">
            <h6 class="fw-medium font-14 mb-2 pb-1">Color Scheme</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-bs-theme" value="light" id="light-mode-check" checked>
              <label class="form-check-label" for="light-mode-check">Light Mode</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-bs-theme" value="dark" id="dark-mode-check">
              <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
            </div>

            <!-- Width -->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Width</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-layout-width" value="fluid" id="fluid-check" checked>
              <label class="form-check-label" for="fluid-check">Fluid</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-layout-width" value="boxed" id="boxed-check">
              <label class="form-check-label" for="boxed-check">Boxed</label>
            </div>


            <!-- Topbar -->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Topbar</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-topbar-color" value="light" id="lighttopbar-check">
              <label class="form-check-label" for="lighttopbar-check">Light</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-topbar-color" value="dark" id="darktopbar-check" checked>
              <label class="form-check-label" for="darktopbar-check">Dark</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-topbar-color" value="brand" id="brandtopbar-check">
              <label class="form-check-label" for="brandtopbar-check">brand</label>
            </div>


            <!-- Menu positions -->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Menus Positon <small>(Leftsidebar and Topbar)</small></h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-layout-position" value="fixed" id="fixed-check" checked>
              <label class="form-check-label" for="fixed-check">Fixed</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-layout-position" value="scrollable" id="scrollable-check">
              <label class="form-check-label" for="scrollable-check">Scrollable</label>
            </div>


            <!-- Menu Color-->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Menu Color</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-menu-color" value="light" id="light-check" checked>
              <label class="form-check-label" for="light-check">Light</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-menu-color" value="dark" id="dark-check">
              <label class="form-check-label" for="dark-check">Dark</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-menu-color" value="brand" id="brand-check">
              <label class="form-check-label" for="brand-check">Brand</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-menu-color" value="gradient" id="gradient-check">
              <label class="form-check-label" for="gradient-check">Gradient</label>
            </div>


            <!-- size -->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Left Sidebar Size</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-sidebar-size" value="default" id="default-size-check" checked>
              <label class="form-check-label" for="default-size-check">Default</label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-sidebar-size" value="condensed" id="condensed-check">
              <label class="form-check-label" for="condensed-check">Condensed <small>(Extra Small size)</small></label>
            </div>

            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-sidebar-size" value="compact" id="compact-check">
              <label class="form-check-label" for="compact-check">Compact <small>(Small size)</small></label>
            </div>


            <!-- User info -->
            <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Sidebar User Info</h6>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="data-sidebar-user" value="true" id="sidebaruser-check">
              <label class="form-check-label" for="sidebaruser-check">Enable</label>
            </div>

            <div class="d-grid mt-4">
              <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
            </div>

          </div>

        </div>
      </div>

    </div> <!-- end slimscroll-menu-->
  </div>
  <div class="rightbar-overlay"></div>
</div>
HTML;
        return $html;
    }

}