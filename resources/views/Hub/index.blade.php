@extends('layouts.hub')

@section('content')
    <div class="hub-content no-sidebar">
        <div class="hub-dash">
            <div class="middle-content">
                <div class="main-tiles">
                    <div class="tile" style="--tbclr:#18CE77">
                        <h4>Purchases</h4>
                        <h2>254</h2>
                    </div>
                    <div class="tile" style="--tbclr:#89A3FF">
                        <h4>Bookings</h4>
                        <h2>14</h2>
                    </div>
                    <div class="tile" style="--tbclr:#FF7F7F">
                        <h4>Services</h4>
                        <h2>64</h2>
                    </div>
                </div>

                <div class="other-tiles">
                    <div class="other-tile">
                        <div class="header">
                            <h5>Jobs</h5>
                            <h3 style="--clr:#95EFC7">23</h3>
                        </div>
                        <div class="dougnut">
                            <canvas class="my-chart" id="jobChart"></canvas>
                        </div>
                        <div class="footing">
                            <div class="foot">
                                <h4 class="foot-head">Completed</h4>
                                <h4 style="--clr:#40DDFF">21</h4>
                            </div>
                            <div class="foot">
                                <h4  class="foot-head">Pending</h4>
                                <h4 style="--clr:#FF9393">3</h4>
                            </div>
                        </div>
                    </div>
                    <div class="other-tile">
                        <div class="header">
                            <h5>Invoices</h5>
                            <h3 style="--clr:#AEBAE8">23</h3>
                        </div>
                        <div class="dougnut">
                            <canvas class="my-chart" id="invoiceChart"></canvas>
                        </div>
                        <div class="footing">
                            <div class="foot">
                                <h4 class="foot-head">Paid</h4>
                                <h4 style="--clr:#40DDFF">21</h4>
                            </div>
                            <div class="foot">
                                <h4 class="foot-head">Unpaid</h4>
                                <h4 style="--clr:#FF9393">3</h4>
                            </div>
                        </div>
                    </div>
                    <div class="other-tile">
                        <div class="header">
                            <h5>Ratings</h5>
                            <h3 style="--clr:#F8C9C9">23</h3>
                        </div>
                        <div class="dougnut">
                            <canvas class="my-chart" id="ratingChart"></canvas>
                        </div>
                        <div class="footing">
                            <div class="foot">
                                <h4 class="foot-head">Satisfied</h4>
                                <h4 style="--clr:#40DDFF">21</h4>
                            </div>
                            <div class="foot">
                                <h4 class="foot-head">Unsatisfied</h4>
                                <h4 style="--clr:#FF9393">3</h4>
                            </div>
                        </div>
                    </div>

                </div> 

                <div class="charts">
                    <div class="graphs">
                        <canvas class="line-graph" id="lineGraph"></canvas>
                    </div>
                </div>

            </div>
            <div class="right-content">
                <div class="right-content">
                    <div class="calendar-area">
                        <div class="leftCalandar-side">
                        <div class="calendar">
                            <div class="month">
                            <i class="fas fa-angle-left prevBtn"></i>
                            <div class="date">November 2023</div>
                            <i class="fas fa-angle-right nextBtn"></i>
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
                                    <input type="text"placeholder="Event Time To"class="event-time-to"/>
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
    </div>
@endsection