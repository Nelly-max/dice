@extends('layouts.hub')

@section('content')
    <div class="hub-content no-sidebar">
        <div class="">
            <div class="table-top">
                <div class="top">
                    <h2 class="table_heading">My Referals</h2>

                    <div class="right">
                        <div class="search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="deliverySearch" placeholder="search">
                        </div>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subdivision</th>
                            <th>Business</th>
                            <th>Referal Code</th>
                            <th>Percentage</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>REF001</td>

                            <td>Home City</td>

                            <td>RHM001A</td>

                            <td>M001DM</td>

                            <td>10%</td>

                            <td>14/11/2026</td>
                            
                            <td>Waiting</td>

                            <td
                                class="emptyBack"
                                title="view"
                            >
                                <i class="fa-solid fa-square-arrow-up-right"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

