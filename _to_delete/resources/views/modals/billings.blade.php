    @extends('CookingGas.layouts.modalLayout')

    @section('modal')
    <main>
        <div class="capsule modal" id="billingDetails">
    <div class="modal-overlay"></div>
    <i class="fa-solid fa-xmark close" onclick="closeModal()"></i>

    <div class="pop-up no-sidebar">
        <div class="pop-up-data">
            {{-- Dynamically generate the route: water.billing.pay, cookinggas.billing.pay, etc. --}}
            @php 
                $subRoute = session('subdivision_key', 'cookinggas') . '.billing.pay'; 
            @endphp

            <form class="form" method="POST" action="{{ route($subRoute) }}">
                @csrf

                <h2 class="heading">Billing Details</h2>

                {{-- Hidden fields --}}
                <input type="hidden" name="tariff_id" value="{{ $tariff->id }}">
                <input type="hidden" name="business_id" value="{{ $business->id }}">
                <input type="hidden" name="main_branch_account" value="{{ $business->main_branch_account }}">
                
                {{-- Use subscription_amount here because it contains the calculated/discounted price --}}
                <input type="hidden" name="amount" value="{{ $subscription->subscription_amount ?? $tariff->amount }}">

                {{-- MPesa number --}}
                <div class="inputBox">
                    <input type="tel" name="mpesa-number" placeholder="" required>
                    <label>Enter MPesa Number</label>
                </div>

                <div class="inputBox">
                    <input type="submit" value="Pay via MPesa">
                </div>
            </form>
        </div>
    </div>
</div>

    </main>
    @endsection