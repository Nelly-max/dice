{{--
    Delivery request card for riders. Same ids on every page:
      #accept (shown with .active), #pickupStore, #rqMeta, #acceptDeliveryBtn,
      #acceptProgress (shrinks as time runs out), #acceptCountdown.
    Driven by riderLocation.js on Hub pages and by rider-request.js everywhere else
    (partials/rider-request). A sound plays while it is showing.
--}}
<div class="toast notificationAlert rq-card" id="accept" role="alertdialog" aria-labelledby="rqTitle" aria-describedby="pickupStore">
    <div class="rq-inner">
        <div class="rq-head">
            <span class="rq-pulse" aria-hidden="true"><i class="fa-solid fa-motorcycle"></i></span>
            <div class="rq-text">
                <strong id="rqTitle">New delivery request</strong>
                <small id="pickupStore">Pickup Store:</small>
                <small class="rq-meta" id="rqMeta" hidden></small>
            </div>
        </div>

        <button type="button" class="btn deliveryAcceptBtn rq-accept" id="acceptDeliveryBtn">
            <div class="acceptProgress" id="acceptProgress"></div>
            <i class="fa-regular fa-circle-check" aria-hidden="true"></i>
            <span id="acceptCountdown">Accept Delivery (15)</span>
        </button>
    </div>
</div>
