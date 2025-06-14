$(function(){
    // Initialize DataTable
    const partylistTable = $('#partylistsTable').DataTable({
        responsive: true
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Function to check if modifications are allowed
    function isModificationAllowed() {
        return window.canModify || false;
    }

    // Function to get modification message
    function getModificationMessage() {
        return window.modificationMessage || 'Modifications are not allowed';
    }

    // Function to show error message using SweetAlert
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message || 'An error occurred',
            showConfirmButton: true
        });
    }

    // Function to show success message using SweetAlert
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    }

    // Function to show warning message using SweetAlert
    function showWarning(message) {
        Swal.fire({
            icon: 'warning',
            title: 'Access Denied',
            text: message,
            showConfirmButton: true
        });
    }

    // Function to handle server errors
    function handleServerError() {
        showError('Server error occurred. Please try again.');
    }

    // Function to get partylist data
    function getRow(id) {
        if (!isModificationAllowed()) return;

        $.ajax({
            type: 'POST',
            url: `${BASE_URL}administrator/pages/includes/modals/controllers/PartylistController.php`,
            data: {id: id, action: 'get'},
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    $('.partylist_id').val(response.data.id);
                    $('#edit_name').val(response.data.name);
                    $('.partylist_name').html(response.data.name);
                } else {
                    showError(response.message);
                }
            },
            error: handleServerError
        });
    }

    // Prevent modal from showing if modifications are not allowed
    $('#addnew, #edit').on('show.bs.modal', function(e) {
        if (!isModificationAllowed()) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    // Edit button click handler
    $(document).on('click', '.edit-partylist', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!isModificationAllowed()) {
            showWarning(getModificationMessage());
            return;
        }
        
        $('#edit').modal('show');
        getRow($(this).data('id'));
    });

    // Delete button click handler
    $(document).on('click', '.delete-partylist', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!isModificationAllowed()) {
            showWarning(getModificationMessage());
            return;
        }

        const partylistId = $(this).data('id');
        const partylistName = $(this).closest('tr').find('td:first').text();
        
        Swal.fire({
            title: 'Delete Partylist',
            text: `Are you sure you want to delete partylist "${partylistName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: `${BASE_URL}administrator/pages/includes/modals/controllers/PartylistController.php`,
                    data: { id: partylistId, action: 'delete' },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.error) {
                            showSuccess('Partylist has been deleted successfully!');
                        } else {
                            showError(response.message);
                        }
                    },
                    error: handleServerError
                });
            }
        });
    });

    // Form submission handler function
    function handleFormSubmission(form, action) {
        if (!isModificationAllowed()) {
            showWarning(getModificationMessage());
            return;
        }

        const formData = form.serialize();
        const isEdit = action === 'edit';
        const partylistName = isEdit ? $('#edit_name').val() : $('#add_name').val();

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    showSuccess(`Partylist "${partylistName}" has been ${isEdit ? 'updated' : 'added'} successfully!`);
                    form.closest('.modal').modal('hide');
                } else {
                    showError(response.message);
                }
            },
            error: handleServerError
        });
    }

    // Add form submission
    $('#addnew form').submit(function(e) {
        e.preventDefault();
        handleFormSubmission($(this), 'add');
    });

    // Edit form submission
    $('#edit form').submit(function(e) {
        e.preventDefault();
        handleFormSubmission($(this), 'edit');
    });
});
