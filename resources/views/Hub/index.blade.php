@extends('layouts.hub')

@section('content')
    <div class="hub-content no-sidebar">
        <div class="hub-dash">
            <div class="middle-content">
                @php
                    $hubCustomer = auth('customer')->user();
                    $isRider = \App\Models\Hub\Rider::where('customer_id', $hubCustomer->id)->exists();
                    $isMarketer = \App\Models\Hub\Marketer::where('customer_id', $hubCustomer->id)->exists();
                @endphp

                <div class="main-tiles">
                    <div class="tile" style="--tbclr:#16a34a">
                        <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                        <h4>Purchases</h4>
                        <h2>0</h2>
                    </div>
                    <div class="tile" style="--tbclr:#4f6bed">
                        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                        <h4>Bookings</h4>
                        <h2>0</h2>
                    </div>
                    <div class="tile" style="--tbclr:#e5484d">
                        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                        <h4>Services</h4>
                        <h2>0</h2>
                    </div>
                </div>

                {{-- Quick actions --}}
                <section class="hub-card hub-quick">
                    <h2 class="hub-card-title">Quick actions</h2>
                    <div class="hub-quick-grid">
                        <a href="{{ route('home') }}" class="hub-quick-item" style="--q:#E8590C">
                            <i class="fa-solid fa-store" aria-hidden="true"></i>
                            <span><strong>Shop now</strong><small>Browse shops near you</small></span>
                        </a>
                        <a href="{{ route('hub.orders') }}" class="hub-quick-item" style="--q:#16a34a">
                            <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                            <span><strong>My orders</strong><small>Track and review orders</small></span>
                        </a>
                        <a href="{{ route('hub.account.index') }}" class="hub-quick-item" style="--q:#4f6bed">
                            <i class="fa-regular fa-circle-user" aria-hidden="true"></i>
                            <span><strong>Profile</strong><small>Update your details</small></span>
                        </a>
                        @if ($isRider)
                            <a href="{{ route('hub.deliveries') }}" class="hub-quick-item" style="--q:#0891b2">
                                <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                                <span><strong>Deliveries</strong><small>Your delivery jobs</small></span>
                            </a>
                        @else
                            <a href="{{ route('hub.account.rider.index') }}" class="hub-quick-item" style="--q:#0891b2">
                                <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                                <span><strong>Become a rider</strong><small>Earn delivering orders</small></span>
                            </a>
                        @endif
                        @if ($isMarketer)
                            <a href="{{ route('hub.referrals') }}" class="hub-quick-item" style="--q:#db2777">
                                <i class="fa-solid fa-link" aria-hidden="true"></i>
                                <span><strong>Referrals</strong><small>Shops you brought in</small></span>
                            </a>
                        @else
                            <a href="{{ route('hub.account.marketer.index') }}" class="hub-quick-item" style="--q:#db2777">
                                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                <span><strong>Become a marketer</strong><small>Earn commission on referrals</small></span>
                            </a>
                        @endif
                    </div>
                </section>
            </div>

            <div class="right-content">
                <div class="calendar-area">
                    <div class="leftCalandar-side">
                        <div class="calendar">
                            <div class="month">
                                <i class="fas fa-angle-left prev"></i>
                                <div class="date">November 2023</div>
                                <i class="fas fa-angle-right next"></i>
                            </div>
                            <div class="weekdays">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>
                            <div class="days"></div>
                            <div class="goto-today">
                                <div class="goto">
                                    <input type="text" placeholder="mm/yyyy" class="date-input" />
                                    <button class="goto-btn">Go</button>
                                </div>
                                <button class="today-btn">Today</button>
                            </div>
                        </div>
                    </div>
                    <div class="rightCalendar-side">
                        <div class="today-date">
                            <div class="event-day">wed</div>
                            <div class="event-date">11th November 2023</div>
                        </div>
                        <div class="events"></div>
                        <div class="add-event-wrapper">
                            <div class="add-event-header">
                                <div class="title">Add Event</div>
                                <i class="fas fa-times close"></i>
                            </div>
                            <div class="add-event-body">
                                <div class="add-event-input">
                                    <input type="text" placeholder="Event Name" class="event-name" />
                                </div>
                                <div class="add-event-input">
                                    <input type="text" placeholder="Event Time From" class="event-time-from"/>
                                </div>
                                <div class="add-event-input">
                                    <input type="text" placeholder="Event Time To" class="event-time-to"/>
                                </div>
                            </div>
                            <div class="add-event-footer">
                                <button class="add-event-btn">Add Event</button>
                            </div>
                        </div>
                    </div>
                    <button class="add-event">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection