<aside class="sidebar">
    <!-- <div class="links-holder"> -->
    @php $customer = auth('customer')->user(); @endphp
    <ul class="main-links">
        <div class="logo-details">
            <a href="{{ route('hub.index') }}" class="logo">
                <img
                    src="{{ $customer->profile_image
                        ? rtrim(config('app.media_url'), '/') . '/' . ltrim($customer->profile_image, '/')
                        : rtrim(config('app.media_url'), '/') . '/media/img/Customer/Profiles/user.png' }}"
                    alt="Customer Profile"
                >
            </a>
        </div>

        <div class="extra-links-toggle toggle-items active">
            <h4>Extra Links</h4>
            <div class="link-toggle toggle-items-btn">
                <div class="toggle-btn"></div>
            </div>
        </div>

        <li class="link {{ request()->routeIs('hub.index') ? 'active' : '' }} dashboard">
            <div class="icon-link">
                <a href="{{ route('hub.index') }}" class="">
                    <i class='bx bxs-dashboard icon' style="--clr: #134893"></i>
                    <span  class="link_name">Dashboard</span>
                </a>
            </div>
        </li>

        <li class="link {{ request()->routeIs('hub.orders') ? 'active' : '' }}">
            <div class="icon-link">
                <a href="{{ route('hub.orders') }}" class="link">
                    <div class="count-holder">
                        <i class='bx bx-basket icon' style="color: #00d768"></i>
                        <h4 class="counter order">2</h4>
                    </div>
                    <span class="link_name">Orders</span>
                </a>
            </div>
        </li>

        <li class="link">
            <div class="icon-link arrowOpen">
                <a href="qcity.html" class="link">
                    <i class="fa-solid fa-bag-shopping icon" style="color: #fc744a"></i>
                    <span class="link_name">Purchases</span>
                </a>
            </div>
        </li>

        <li class="link">
            <div class="icon-link arrowOpen">
                <a href="qcity.html" class="link">
                    <i class='bx bx-link icon'  style="color: #fc4aa0"></i>
                    <span class="link_name">Referals</span>
                </a>
            </div>
        </li>

        <li class="link">
            <div class="icon-link arrowOpen">
                <a href="booking.html" class="link">
                    <i class='bx bx-book' style="color: #0076d7"></i>
                    <span class="link_name">Bookings</span>
                </a>
            </div>
        </li>


        <li class="link {{ request()->routeIs('hub.deliveries') ? 'active' : '' }} extra-link">
            <div class="icon-link arrowOpen">
                <a href="{{ route('hub.deliveries') }}" class="link">
                    <div class="count-holder">
                        <i class="fa-solid fa-motorcycle" style="color: #00b3d7"></i>
                        <h4 class="counter order">12</h4>
                    </div>
                    <span class="link_name">Deliveries</span>
                </a>
            </div>
        </li>                  
        
        <li class="link {{ request()->routeIs('hub.account.*') ? 'active' : '' }}">
            <div class="icon-link arrowOpen">
                <a href="{{ route('hub.account.index') }}" class="link">
                    <i class='bx bxs-user-account icon' style="--clr: #0e6d9c"></i>
                    <span class="link_name">Account</span>
                </a>
            </div>
        </li>

        <li class="link bottom-item last">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
    
                <button type="submit" class="logout">
                    <i class="bx bx-log-out"></i>
                    <span class="link_name">Logout</span>
                </button>
            </form>
        </li>

    </ul>
</aside>