$(function(){
    // Initialize DataTable
    const candidateTable = $('#candidatesTable').DataTable({
        responsive: true,
        columnDefs: [
            { 
                targets: 0, // Photo column
                orderable: false
            }
        ]
    });

    // Initialize tooltips with custom options
    $('[data-toggle="tooltip"]').tooltip({
        template: '<div class="tooltip" role="tooltip"><div class="tooltip-inner" style="background-color: #000;"></div></div>',
        placement: 'top',
        trigger: 'hover',
        container: 'body'
    });

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
    function handleServerError(xhr, status, error) {
        console.error('Error:', error);
        console.log('Response:', xhr.responseText);
        showError('Server error occurred. Please try again.');
    }

    // Function to handle file input change
    function handleFileInputChange(input) {
        const file = input.files[0];
        const photoPreview = $(input).siblings('.photo-preview');
        
        if (file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                showError('Invalid file type. Only JPG, JPEG & PNG files are allowed.');
                input.value = '';
                return;
            }

            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showError('File size exceeds 2MB limit.');
                input.value = '';
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.html(`<img src="${e.target.result}" class="img-thumbnail" width="100">`);
            };
            reader.readAsDataURL(file);
        } else {
            photoPreview.empty();
        }
    }

    // Function to get candidate data
    function getRow(id) {
        if (!isModificationAllowed()) return;

        $.ajax({
            type: 'POST',
            url: `${BASE_URL}administrator/pages/includes/modals/controllers/CandidateController.php`,
            data: {id: id, action: 'get'},
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    const data = response.data;
                    $('.candidate_id').val(data.id);
                    $('#edit_firstname').val(data.firstname);
                    $('#edit_lastname').val(data.lastname);
                    $('#edit_position').val(data.position_id);
                    $('#edit_partylist').val(data.partylist_id);
                    $('#edit_platform').val(data.platform);
                    $('.fullname').html(data.firstname + ' ' + data.lastname);
                    
                    // Update photo preview with proper path handling
                    let photoUrl;
                    if (data.photo && data.photo !== 'assets/images/profile.jpg') {
                        photoUrl = `${BASE_URL}administrator/${data.photo}`;
                    } else {
                        photoUrl = `${BASE_URL}administrator/assets/images/profile.jpg`;
                    }
                    $('#current_photo').html(`
                        <div class="mt-2">
                            <label>Current Photo:</label><br>
                            <img src="${photoUrl}" class="img-thumbnail" width="150">
                        </div>
                    `);
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
    $(document).on('click', '.edit-candidate', function(e) {
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
    $(document).on('click', '.delete-candidate', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!isModificationAllowed()) {
            showWarning(getModificationMessage());
            return;
        }

        const candidateId = $(this).data('id');
        const candidateName = $(this).closest('tr').find('td:nth-child(2)').text();
        
        Swal.fire({
            title: 'Delete Candidate',
            text: `Are you sure you want to delete candidate "${candidateName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: `${BASE_URL}administrator/pages/includes/modals/controllers/CandidateController.php`,
                    data: {
                        id: candidateId,
                        action: 'delete'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.error) {
                            showSuccess(`Candidate "${candidateName}" has been deleted successfully!`);
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

        // Create FormData object for file upload
        const formData = new FormData(form[0]);
        const isEdit = action === 'edit';
        const firstName = isEdit ? $('#edit_firstname').val() : $('#add_firstname').val();
        const lastName = isEdit ? $('#edit_lastname').val() : $('#add_lastname').val();
        const fullName = `${firstName} ${lastName}`;

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    showSuccess(`Candidate "${fullName}" has been ${isEdit ? 'updated' : 'added'} successfully!`);
                    form.closest('.modal').modal('hide');
                } else {
                    showError(response.message);
                }
            },
            error: handleServerError
        });
    }

    // File input change handlers
    $('#add_photo').change(function() {
        handleFileInputChange(this);
    });

    $('#edit_photo').change(function() {
        handleFileInputChange(this);
    });

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