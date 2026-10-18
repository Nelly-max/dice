@extends('layouts.hub')

@section('content')
    <div class="hub-content no-sidebar">
        <div>
            <div class="table-top">
                <div class="top">
                    <h2 class="table_heading">My Referrals</h2>

                    <div class="right">
                        <!-- <div class="search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="deliverySearch" placeholder="Search referrals...">
                        </div> -->
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subdivision</th>
                            <th>Business</th>
                            <th>Referral Code</th>
                            <th>Percentage</th>
                            <th>Date</th>
                            <th>Status</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($referrals as $referral)
                            <tr>
                                <td>
                                    {{ 'REF' . str_pad($referral->id, 3, '0', STR_PAD_LEFT) }}
                                </td>

                                <td>{{ $referral->subdivision_name ?? 'Unknown subdivision' }}</td>

                                <td>
                                    {{ $referral->business_account ?? '—' }}
                                </td>

                                <td>
                                    {{ $referral->referral_code ?? '—' }}
                                </td>

                                <td>
                                    {{ number_format((float) ($referral->discount ?? 0), 2) }}%
                                </td>

                                <td>
                                    {{ $referral->created_at?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                     {{ $referral->status ?? 'pending' }}
                                </td>

                                <!-- <td class="emptyBack" title="View">
                                    <i class="fa-solid fa-square-arrow-up-right"></i>
                                </td> -->
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    No referrals found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection