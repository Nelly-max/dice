   

    @section('modal')
    <main>

        <div class="modal" id="deleteItem">
            <div class="modal-overlay"></div>
            <div class="pop-up no-sidebar">
                <i class="fa-solid fa-xmark close" onclick="closeModal()"></i>

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

        <div class="modal fade"
     id="deleteCartItemModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Remove Item
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">
                Are you sure you want to remove this item from your cart?
            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        class="btn btn-danger"
                        id="confirmDeleteCartItem">
                    Remove Item
                </button>

            </div>

        </div>
    </div>

</div>


        <!-- /////////Alert Popups//////////// -->
        <!-- Success Modal -->
        <div class="modal" id="success">
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
        </div>
    </main>
    @endsection
