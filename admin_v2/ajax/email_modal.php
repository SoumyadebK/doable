<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #39b54a; color: white;">
                <h5 class="modal-title" id="emailModalLabel">
                    <i class="fa fa-envelope" aria-hidden="true"></i> Send Email to <span id="emailCustomerName"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="emailStatus" style="display:none;" class="alert"></div>

                <form id="emailForm" method="POST" action="send_email.php">
                    <input type="hidden" name="action" value="send_email">
                    <input type="hidden" name="appointment_id" id="emailAppointmentId">
                    <input type="hidden" name="customer_id" id="emailCustomerId">
                    <input type="hidden" name="email_type" id="emailType">

                    <div class="form-group">
                        <label for="emailSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="emailSubject" name="email_subject"
                            placeholder="Enter email subject" required>
                    </div>

                    <div class="form-group">
                        <label for="emailMessage">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="emailMessage" name="email_message"
                            rows="6" placeholder="Enter your message here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Customer Information:</label>
                        <div class="well well-sm" style="background-color: #f9fafb; padding: 10px; border-radius: 4px;">
                            <p><strong>Name:</strong> <span id="displayCustomerName"></span></p>
                            <p><strong>Email:</strong> <span id="displayCustomerEmail"></span></p>
                            <p><strong>Phone:</strong> <span id="displayCustomerPhone"></span></p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="sendEmail()">
                    <i class="fa fa-paper-plane" aria-hidden="true"></i> Send Email
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openEmailModal(appointmentId, customerId, customerName, customerEmail, customerPhone, emailType) {
        // Set form values
        document.getElementById('emailAppointmentId').value = appointmentId;
        document.getElementById('emailCustomerId').value = customerId;
        document.getElementById('emailType').value = emailType || 'appointment';

        // Display customer information
        document.getElementById('emailCustomerName').textContent = customerName;
        document.getElementById('displayCustomerName').textContent = customerName;
        document.getElementById('displayCustomerEmail').textContent = customerEmail;
        document.getElementById('displayCustomerPhone').textContent = customerPhone || 'N/A';

        // Set default subject and message
        document.getElementById('emailSubject').value = 'Appointment Details from Doable';
        document.getElementById('emailMessage').value =
            'Dear ' + customerName + ',\n\n' +
            'Thank you for choosing Doable. We look forward to serving you.\n\n' +
            'Please find your appointment details below.\n\n' +
            'Best regards,\n' +
            'Doable Team';

        // Hide previous status
        document.getElementById('emailStatus').style.display = 'none';

        // Show modal
        $('#emailModal').modal('show');
    }

    function sendEmail() {
        var form = document.getElementById('emailForm');
        var formData = new FormData(form);

        // Validate form
        var subject = document.getElementById('emailSubject').value.trim();
        var message = document.getElementById('emailMessage').value.trim();

        if (!subject) {
            showEmailStatus('error', 'Please enter a subject.');
            return;
        }

        if (!message) {
            showEmailStatus('error', 'Please enter a message.');
            return;
        }

        // Show loading state
        var sendBtn = document.querySelector('#emailModal .btn-success');
        var originalText = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Sending...';
        sendBtn.disabled = true;

        // Hide previous status
        document.getElementById('emailStatus').style.display = 'none';

        fetch('send_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showEmailStatus('success', '✓ ' + data.message);
                    // Close modal after 3 seconds
                    setTimeout(function() {
                        $('#emailModal').modal('hide');
                        // Reset form
                        document.getElementById('emailForm').reset();
                    }, 3000);
                } else {
                    showEmailStatus('error', '✗ ' + data.message);
                }
            })
            .catch(error => {
                showEmailStatus('error', '✗ An error occurred while sending the email. Please try again.');
                console.error('Error:', error);
            })
            .finally(() => {
                sendBtn.innerHTML = originalText;
                sendBtn.disabled = false;
            });
    }

    function showEmailStatus(type, message) {
        var statusDiv = document.getElementById('emailStatus');
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
        statusDiv.textContent = message;
    }
</script>

<style>
    /* Additional styling for the modal */
    .well-sm {
        min-height: 20px;
        padding: 9px;
        margin-bottom: 20px;
        background-color: #f5f5f5;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
        -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .05);
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .05);
    }

    .well-sm p {
        margin: 3px 0;
    }

    #emailStatus {
        margin-bottom: 15px;
    }
</style>