   @extends('layouts.modal')

    @section('modal')
    <main>

        <div class="modal" id="deleteItem">
            <div class="modal-overlay"></div>
            <div class="pop-up no-sidebar">
                <i class="fa-solid fa-xmark close" onclick="closeModal()"></i>
                <i class="fa-solid fa-folder-plus icon allow" style="--clr:#FFD5D5"></i>
                <h3 id="modalTitle">Confirm Action</h3>
                <div class="pop-up-data">
                    <h2>Are you sure you want to remove this item from your cart?</h2>

                    <div class="btns">
                        <button style="color:red" class="btn btn-danger" id="confirmDeleteCartItem">
                            <i class="fa-regular fa-circle-xmark"></i> Remove Item
                        </button>

                        <button style="color:#72d5ff"class="btn-close" onclick="closeModal()">
                            <i class="fa-regular fa-circle-xmark"></i> cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <!-- /////////Alert Popups//////////// -->
        <!-- Success Modal -->
        <!-- <div class="modal" id="success">
            <div class="modal-overlay"></div>
            <div class="pop-up no-sidebar">
                <div class="pop-up-data confirm-action" style="background: var(--green)">
                    <i class="fa-solid fa-folder-plus icon allow" style="--clr:#FFD5D5"></i>
                    <h3 id="modalTitle">Success</h3>

                    <h2 id="modalMessage" class="allow">
                        Manufacturer added successfully
                    </h2>

                    <div class="btns">
                        <button style="color:#72d5ff" onclick="closeModal()">
                            <i class="fa-regular fa-circle-xmark"></i> ok
                        </button>
                    </div>
                </div>
            </div>
        </div> -->
    </main>
    @endsection
