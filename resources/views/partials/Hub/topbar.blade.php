<div class="topbar" id="fixed">
    <div>
        <i class='bx bx-menu-alt-left close-sidebar' id="menu"></i>
        <i class="bx bx-menu menu-sidebar"></i>
    </div>
    <h2>{{ auth('customer')->user()->username }}</h2>
    <div class="count-holder" onclick="showModal('Notif')">
        <i class='bx bx-bell icon' style="color: #d71d00"></i>
        <h4 class="counter">4</h4>
    </div>
</div>