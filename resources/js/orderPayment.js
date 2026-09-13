document.addEventListener('DOMContentLoaded', () => {

    const btn = document.getElementById('stkPushBtn');
    const phoneInput = document.getElementById('mpesa_phone');
    const invoiceInput = document.getElementById('invoice_id');
    const messageBox = document.getElementById('stkMessage');
       console.log({
    btn,
    phoneInput,
    invoiceInput,
    messageBox
});

    if (!btn) return;

    btn.addEventListener('click', async () => {

        const phone = phoneInput.value.trim();
        const invoiceId = invoiceInput.value;

        if (!phone) {
            messageBox.innerHTML = '<span class="text-danger">Please enter an M-Pesa number.</span>';
            phoneInput.focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending...';

        messageBox.innerHTML = 'Sending STK Push...';

        try {

            const response = await fetch('/payment/mpesa/stk-push', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },

                body: JSON.stringify({
                    invoice_id: invoiceId,
                    phone: phone
                })

            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Unable to send STK Push.');
            }

            messageBox.innerHTML = `
                <span class="text-success">
                    ${data.message}<br>
                    Check your phone and enter your M-Pesa PIN.
                </span>
            `;

            // Start checking payment status
            pollPaymentStatus(invoiceId);

        } catch (error) {

            console.error(error);

            messageBox.innerHTML = `
                <span class="text-danger">
                    ${error.message}
                </span>
            `;

        } finally {

            btn.disabled = false;
            btn.textContent = 'STK Push';

        }

    });

});


function pollPaymentStatus(invoiceUuid, messageBox)
{
    let elapsedSeconds = 0;
    const maxTimeoutSeconds = 300;

    const interval = setInterval(async () => {

        elapsedSeconds += 3;

        if (elapsedSeconds >= maxTimeoutSeconds) {

            clearInterval(interval);

            if (messageBox) {

                messageBox.innerHTML = `
                    <div style="color:#d9534f;
                                padding:10px;
                                background:#fdf7f7;
                                border:1px solid #d9534f;
                                border-radius:4px;
                                margin-bottom:15px;">
                        <strong>Session Expired:</strong>
                        Tracking closed. If you already made a payment,
                        please click below to verify.
                        <br>
                        <button
                            onclick="window.location.reload()"
                            style="margin-top:8px;
                                   padding:5px 10px;
                                   cursor:pointer;">
                            Verify Payment
                        </button>
                    </div>
                `;
            }

            return;
        }

        try {

            const response = await fetch(`/payment/status/${invoiceUuid}`);

            if (!response.ok) {
                throw new Error('Network error occurred');
            }

            const data = await response.json();

            if (data.paid && data.redirect) {

                clearInterval(interval);

                window.location.href = data.redirect;

            }

        } catch (error) {

            console.error('Status check failed:', error);

        }

    }, 3000);
}


// ----------------------------------
// Listen for invoice status Change
// ----------------------------------


// document.addEventListener('DOMContentLoaded', () => {
    
//     const paymentContainer = document.getElementById('payment-container');
    
//     // Safely exit if this script runs on a page without the payment element
//     if (!paymentContainer) return;

//     // Read the invoice UUID value embedded by Laravel
//     const invoiceUuid = paymentContainer.dataset.invoiceUuid;
    
//     let elapsedSeconds = 0;
//     const maxTimeoutSeconds = 300; // 5 minutes polling threshold limit

//     // Begin the recurring payment status check loop every 3 seconds
//     const checkInterval = setInterval(() => {
//         elapsedSeconds += 3;

//         // Gracefully handle timeout state without wiping out entire container HTML structures
//         if (elapsedSeconds >= maxTimeoutSeconds) {
//             clearInterval(checkInterval);
            
//             // Targeted prompt notification injection
//             const messageBox = document.getElementById('stkMessage');
//             if (messageBox) {
//                 messageBox.innerHTML = `
//                     <div style="color: #d9534f; padding: 10px; background: #fdf7f7; border: 1px solid #d9534f; border-radius: 4px; margin-bottom: 15px;">
//                         <strong>Session Expired:</strong> Tracking closed. If you already made a payment, please click below to verify.
//                         <br><button onclick="window.location.reload()" style="margin-top: 8px; cursor: pointer; padding: 5px 10px;">Verify Payment</button>
//                     </div>
//                 `;
//             }
//             console.warn('Payment polling closed: Session timed out.');
//             return;
//         }

//         fetch(`/payment/status/${invoiceUuid}`)
//             .then(response => {
//                 if (!response.ok) throw new Error('Network error occurred');
//                 return response.json();
//             })
//             .then(data => {
//                 // If payment is successfully confirmed, stop the loop and forward user
//                 if (data.paid && data.redirect) {
//                     clearInterval(checkInterval);
//                     window.location.href = data.redirect;
//                 }
//             })
//             .catch(error => console.error('Status check failed:', error));

//     }, 3000);
// });


// {/* <script>
//     const invoice = "{{ $invoice->uuid }}";
    
//     setInterval(() => {
    
//         fetch(`/payment/status/${invoice}`)
//             .then(response => response.json())
//             .then(data => {
    
//                 if (data.paid) {
//                     window.location.href = data.redirect;
//                 }
    
//             });
    
//     }, 3000);
// </script> */}
