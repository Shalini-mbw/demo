<?php include('include/head.php'); ?>
<?php
require 'data/dbconfig.php';

$response = ["data" => []];

// Fetch jobs
//$sql = "SELECT * FROM job_assignments";
 if ($JWT_userRole == "admin") 
 {
    $sql = "SELECT ja.*, e.name AS added_by_name 
            FROM job_assignments ja
            LEFT JOIN employee e ON ja.user_id = e.id;";
 }
 else if ($JWT_userRole == "client") {

    $sql = "SELECT ja.*, e.name AS added_by_name 
            FROM job_assignments ja
            LEFT JOIN employee e ON ja.user_id = e.id
            WHERE ja.user_id = ?";
                  
 }

// Prepare the SQL statement
$stmt = $conn->prepare($sql);


// Check if the role is "client" to bind the user_id dynamically
if ($JWT_userRole == "client") {
    $stmt->bind_param('s', $JWT_userID); // 's' indicates the type is a string
}

// Execute the statement
$stmt->execute();


$result = $stmt->get_result();

if ($result->num_rows > 0) {
  while ($job = $result->fetch_assoc()) {
    $jobId = $job["id"];

    // // Fetch assigned users
    // $assignees = [];
    // $assigneeQuery = "SELECT * FROM job_assignments WHERE id = ?";
    // $stmt = $conn->prepare($assigneeQuery);
    // $stmt->bind_param("i", $jobId);
    // $stmt->execute();
    // $assigneeResult = $stmt->get_result();

    // while ($assignee = $assigneeResult->fetch_assoc()) {
    //   $assignees[] = [
    //     "id" => $assignee["id"],
    //     "user_id" => $assignee["user_id"],
    //     "job_name" => $assignee["job_name"],
    //     "final_date" => $assignee["final_date"],
    //     "status" => $assignee["status"] ?? "Pending",
    //     "created_at" => $assignee["created_at"],
    //     "description" => $assignee["description"]
    //   ];
    // }

    // Fetch files
    $files = [];
    $fileQuery = "SELECT * FROM job_files WHERE job_id = ?";
    $stmt = $conn->prepare($fileQuery);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $fileResult = $stmt->get_result();

    while ($file = $fileResult->fetch_assoc()) {
      $files[] = [
        "id" => $file["id"],
        "file_name" => $file["file_name"],
        "file_path" => $file["file_path"],
        "uploaded_by" => $file["uploaded_by"],
        "uploaded_at" => $file["uploaded_at"]
      ];
    }

    // Fetch comments
    $comments = [];
    $commentQuery = "SELECT jc.*, e.name AS user_name 
                     FROM job_comments jc
                     LEFT JOIN employee e ON jc.commented_by = e.id
                     WHERE jc.job_id = ?;";

    $stmt = $conn->prepare($commentQuery);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $commentResult = $stmt->get_result();

    while ($comment = $commentResult->fetch_assoc()) {
      $comments[] = [
        "id" => $comment["id"],
        "commented_by" => $comment["commented_by"],
        "commented_by_name" => $comment["user_name"],
        "comment" => $comment["comment"],
        "commented_at" => $comment["commented_at"]
      ];
    }

    // Compile response data
    $response["data"][] = [
      "id" => $job["id"],
      "job_name" => $job["job_name"],
      "job_type" => $job["job_type"],
      "reference_link" => $job["reference_link"],
      "final_date" => $job["final_date"],
      "file_count" => $job["file_count"],
      "price" => $job["price"],
      "priority" => $job["priority"],
      "description" => $job["description"],
      "status" => $job["status"],
      "added_by" => $job["user_id"],
      "added_by_name" => $job["added_by_name"],
      "created_at" => $job["created_at"],
      "updated_at" => $job["updated_at"],
      "job_num" => $job["job_no"],
      "service_req" => $job["service"],
     "job_id" => $job["job_id"],
    "review_page" => $job["review_page"],
     "completed_date" => $job["completed_date"],
     "client_id" => $job["client_id"],
   
    
      "files" => $files,
      "comments" => $comments
    ];
  }
}



// Prepare the final response with data wrapped in the 'data' key
$json = json_encode($response, JSON_PRETTY_PRINT);

// Write to file
$file = 'assets/json/AddJobs.json';

if (file_put_contents($file, $json)) {
  // echo "Data successfully written to $file";
} else {
  echo "Failed to write data to $file";
}

?>

<div class="content-wrapper">
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">

            <!-- -----Data Table assignTask Start------ -->
            <div class="mt-5">
                <!-- Data Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-datatable">

                                    <div class="dropdown">
                                             <!-- <button class="btn btn-primary dropdown-toggle" type="button"
                                            id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            All Status
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="All">
                                                    <i class="fas fa-circle" style="color: #000;"></i> All Status</a>
                                            </li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Pending">
                                                    <i class="fas fa-circle" style="color: red;"></i> Pending</a></li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="In Progress">
                                                    <i class="fas fa-circle" style="color: blue;"></i> In Progress</a>
                                            </li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Hold">
                                                    <i class="fas fa-circle" style="color: orange;"></i> Hold</a></li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Completed">
                                                    <i class="fas fa-circle" style="color: green;"></i> Completed</a>
                                            </li>
                                        </ul> -->
                                        <?php if ($JobsExcel === 'Enable'): ?>
                                        <button id="exportToExcel" class="btn btn-primary" style="margin: 20px;">Export
                                            to
                                            Excel</button>
                                        <?php endif; ?>
                                    </div>

                                    <table class="datatables-Project table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>S.No</th>
                                                <th>Client Id</th>
                                                <th>Job Id</th>
                                                <th>Service Request</th>
                                                <th>Job/Case Name</th>
                                                <th>Job/Case Number</th>
                                                <th>Job Type</th>
                                                <th>Job Priority</th>
                                                <th>Job date</th>
                                                <th>Job Due Date</th>
                                                <th>Job Link</th>
                                                <th>Upload Files</th>
                                                <th>PDF Pages</th>
                                                <th>Review Page</th>
                                                <th>Cost</th>
                                                <th> Completed Date</th>
                                                <th>Job Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="assigned_table">
                                            <!-- Your dynamic rows will go here -->
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<!-- -----Data Table assignTask Start------ -->

<!-- Add New Task Type Card Modal -->
<div class="modal fade custom_model" id="update_admin_jobs_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <form id="Admin_job_update" class="row g-5">
                    <!-- Account Details -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-center">Update Client Job By Admin</h6>
                            <hr class="mt-0" />
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="col-md-12 mb-4">
                                <div class="form-floating form-floating-outline">
                                    <input type="hidden" id="JobhiddenId" value="">

                                    <div class="form-floating form-floating-outline mb-3">
                                        <input type="number" id="review_pageCount" class="form-control"
                                            placeholder=" Review Page Count" name="review_pageCount">
                                        <label for="pageCount">Review Page Count</label>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-3">
                                        <input type="number" id="JobCost" class="form-control" placeholder="Job Cost"
                                            name="JobCost" pattern="\d{1,6}" maxlength="6" min="0" max="999999"
                                            oninput="validateJobCost(this)">
                                        <label for="JobCost">Job Cost</label>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-3">
                                        <select name="ChangeStatus_Job" id="Jobchangestatus"
                                            class="form-control select2" required>
                                            <option value="Pending">Pending</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Hold">Hold</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                        <label for="changestatus">Choose Job Status</label>
                                    </div>

                                    <!-- Date -->
                                    <div class="form-floating form-floating-outline mb-3">
                                        <input type="text" required style="color: #393939;" id="completed_date"
                                            class="form-control flatpickr_date_current_date" placeholder="DD-MM-YYYY"
                                            readonly="readonly" />
                                        <label for="date">Completed Date</label>
                                    </div>

                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-12 d-flex justify-content-center mt-12">
                                <button type="reset"
                                    class="btn btn-outline-secondary me-4 waves-effect data-bs-dismiss=modal">Cancel</button>
                                <button type="submit" id="update_job_status_others" value="UpdateJobOthers"
                                    class="btn btn-primary">Update</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--Add New Task Type Card Modal -->

<!-- Add New Task Type Card Modal -->
<div class="modal fade custom_model" id="Add_reply_Modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <form id="Job_comment_repli_form" class="row g-5">
                    <!-- Account Details -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-center">Add Job New Comment </h6>
                            <hr class="mt-0" />
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="col-md-12 mb-4">
                                <div class="form-floating form-floating-outline">
                                    <input type="hidden" id="Job_hiddenId"
                                        value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">

                                    <div class="form-floating form-floating-outline mb-3">
                                        <textarea class="form-control employee_details_txtBox" id="Comment_repli"
                                            style="height: 160px;" placeholder="Enter New Comment here"
                                            maxlength="100"></textarea>
                                        <div id="Address_charCount" class="char-count-overlay">/100</div>
                                    </div>

                                    <!-- <label for="task">Web design</label> -->
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-12 d-flex justify-content-center mt-12">
                                <button type="reset"
                                    class="btn btn-outline-secondary me-4 waves-effect data-bs-dismiss=modal">Cancel</button>
                                <button type="submit" id="Add_comment_submit" value="AddComment"
                                    class="btn btn-primary">Add Comment</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--Add New Task Type Card Modal -->

<!--Add files download to icon -->
<!--Add files download to icon -->
<!-- Large Modal -->

<div class="modal fade custom_model" id="update_admin_jobs_model1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
        <div class="modal-content">
            <button type="button" class="btn-close m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            <!-- <div class="modal-header border-bottom-0">
                <h4 class="modal-title mx-auto" id="exampleModalLabel3">Candidate</h4>
            </div> -->
            <div class="modal-body">
                <form id="Admin_job_update_file" class="row g-4">

                    <!-- File Table Section (Optional) -->
                    <div class="col-12 mt-4">
                        <h6 class="text-center">Files Download</h6>
                        <hr class="mt-0" />
                        <div class="table-responsive text-nowrap">

                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-primary btn-sm" id="downloadAllBtn">
                                    Download All Files
                                </button>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <!-- <th><input type="checkbox" id="selectAllCheckbox"></th> -->
                                        <!-- <th><input type="checkbox" id="selectAllCheckbox" /></th> -->
                                        <th><input type="checkbox" id="selectAllCheckbox" /></th>
                                        <th>File Name</th>
                                        <th>Uploaded At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <tr>
                                        <td colspan="4" class="text-center">No Job files available</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<!--Add files download to icon -->
<!--Add files download to icon -->

<!-- View Work Status Card Modal -->
<div class="modal fade custom_model" id="Project_sts_Notes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <div class="text-center mb-6">
                    <h4 class="mb-2">Commented Text</h4>
                </div>

                <div class="text-center view_status_notes mb-6">
                    <div id="view_notes_para"></div>
                </div>

            </div>
        </div>
    </div>
</div>
<!--View Work Status Card Modal -->

<!-- --Data tables-- -->

<?php include('include/footer.php'); ?>

<script src="assets/vendor/libs/quill/quill.js"></script>
<script src="assets/vendor/libs/quill/katex.js"></script>
<script src="assets/js/forms-editors.js"></script>
<script src="assets/js/addjobs.js"></script>

<script>
$('#Admin_job_update').on('submit', function(e) {
    e.preventDefault();
    //Add the Preloader
    $('.event_trigger_loader').addClass('active');
    var jobId = $('#JobhiddenId').val();
    var review_pageCount = $('#review_pageCount').val();
    var completed_date = $('#completed_date').val();
    var jobCost = $('#JobCost').val();
    var jobStatus = $('#Jobchangestatus').val();
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            job_id: jobId,
            review_page: review_pageCount,
            price: jobCost,
            completed_date: completed_date,
            status: jobStatus,
            action: "Update_job_submit"
        },
        dataType: "json",
        success: function(response) {
            //Remove the Preloader
            setTimeout(function() {
                $('.event_trigger_loader').removeClass('active');
            }, 1000);
            if (response.status === "success") {
                showModalWithParams('Job updated successfully!', 'true');
                $('#update_admin_jobs_model').modal('hide'); // Close modal
            } else {
                alert("Error updating job.");
            }
        },
        error: function() {
            //Remove the Preloader
            setTimeout(function() {
                $('.event_trigger_loader').removeClass('active');
            }, 1000);
            alert("An error occurred while updating the job.");
        }
    });
});
$(document).on('click', '.update_admin_jobs1', function(e) {
    e.preventDefault();
    var jobId = $(this).data("id"); // Get job ID from button
    $('#JobhiddenId').val(jobId); // Set job ID in hidden input
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            job_id: jobId,
            action: "Fetch_job_Othes_details"
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                // Populate the modal fields
                $('#review_pageCount').val(response.data.review_page);
                var completedDate = response.data.completed_date;
                var dateObject = new Date(completedDate);
                // Format the date as "DD-MM-YYYY"
                var formattedDate = ('0' + dateObject.getDate()).slice(-2) + '-' + ('0' + (
                    dateObject.getMonth() + 1)).slice(-2) + '-' + dateObject.getFullYear();
                $('#JobCost').val(response.data.price);
                $('#Jobchangestatus').val(response.data.status).trigger(
                    'change'); // Set status with Select2
                // Open modal
                $('#update_admin_jobs_model').modal('show');
            } else {
                echo("Error fetching job details.");
            }
        },
        error: function() {
            echo("An error occurred while fetching the job details.");
        }
    });
});
// <! -------------- download files assign job -------------- >
$(document).on('click', '.update_admin_jobs', function(e) {
    e.preventDefault();
    var jobId = $(this).data("id"); // Get job ID from button
    $('#JobhiddenId').val(jobId); // Set job ID in hidden input
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            job_id: jobId,
            action: "Fetch_job_Othes_details12"
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                var fileDataArray = response.data; // assuming this is an array of files
                var fileTableBody = $(".table-border-bottom-0");
                // Clear previous entries
                fileTableBody.empty();
                // Loop through each file in the response
                console.log(fileDataArray);
                fileDataArray.forEach(function(fileData) {
                    // Constructing file path dynamically
                    var downloadLink = "";
                    if (fileData.file_path) {
                        downloadLink =
                            `https://drive.google.com/uc?id=${fileData.file_path}&export=download`;
                        //`https://drive.google.com/uc?id=${fileData.file_path}&export=download`;
                    } else {
                        downloadLink = "#"; // Fallback if no file_path is provided
                    }
                    // Append new file entry
                    var fileRow = `
                                        <tr>
                                          <td>
                                                <input type="checkbox" class="file-checkbox" data-url="${downloadLink}" />
                                            </td>
                                            <td>
                                                <i class="ri-file-line ri-22px text-danger me-4"></i>
                                                <span class="fw-medium">${fileData.file_name}</span>
                                            </td>
                                         <td>
                                                <span class="fw-medium">${fileData.uploaded_at}</span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                        <i class="ri-more-2-line"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item waves-effect" href="${downloadLink}" download>
                                                            <i class="ri-download-2-line me-1"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                    fileTableBody.append(fileRow);
                });
                // Open modal
                $('#update_admin_jobs_model1').modal('show');
            } else {
                $('#update_admin_jobs_model1').modal('show');
                // echo("Error fetching job details.");
            }
        },
        error: function() {
            echo("An error occurred while fetching the job details.");
        }
    });
});
// <! -------------- download files assign job -------------- >
// <! -------------- Uploaded File View Model -------------- >
$(document).on('click', '.view_all_uploaded_file', function(e) {
    e.preventDefault();
    $('#whole_job_file_view_Modal').modal('show');
});
// <! -------------- Uploaded File View Model -------------- >
// <! -------------- Reply My Task Modal Open -------------- >
$(document).on('click', '.reply_my_task', function(e) {
    e.preventDefault();
    var jobId = $(this).data("job-id");
    $('#Job_hiddenId').val(jobId);
    $('#Add_reply_Modal').modal('show');
});
$(document).on('submit', '#Job_comment_repli_form', function(e) {
    e.preventDefault();
    //Add the Preloader
    $('.event_trigger_loader').addClass('active');
    let jobId = $('#Job_hiddenId').val();
    let comment = $('#Comment_repli').val().trim();
    if (comment === "") {
        alert("Please enter a comment before submitting.");
        return;
    }
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            job_id: jobId,
            comment: comment,
            action: "AddJobComments"
        },
        dataType: "json",
        beforeSend: function() {
            $('#Add_task_type_submit').prop('disabled', true).text(
                "Adding...");
        },
        success: function(response) {
            if (response.success) {
                showModalWithParams('Comment added successfully!', 'true');
                $('#Add_reply_Modal').modal('hide'); // Close modal
                $('#Comment_repli').val(""); // Clear the textarea
            } else {
                alert("Error: " + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", xhr.responseText);
            alert("An error occurred while adding the comment.");
        },
        complete: function() {
            $('#Add_task_type_submit').prop('disabled', false).text(
                "Add Comment");
            //Remove the Preloader
            setTimeout(function() {
                $('.event_trigger_loader').removeClass('active');
            }, 1000);
        }
    });
});
// <! -------------- Reply My Task Modal Open -------------- >
// <! -------------- View Notes for Any One -------------- >
$(document).on('click', '.view_Notes_details', function(e) {
    e.preventDefault();
    $('#view_notes_para').html("");
    var commentId = $(this).data("comment-id"); // Get comment ID
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            comment_id: commentId,
            action: "Comment_description_show"
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $('#view_notes_para').html(response
                    .comment); // Show full comment in modal
                $('#Project_sts_Notes').modal('show'); // Open modal
            } else {
                alert("Error fetching comment. Please try again.");
            }
        },
        error: function() {
            alert("An error occurred while fetching the comment.");
        }
    });
});
$(document).on("click", ".edit_add_job_btn", function() {
    let jobId = new URL($(this).attr('href')).searchParams.get('edit_id');
    alert("The jobId is: " + jobId);
    //let jobId = $(this).data("id");
    // // Scroll to the top of the page
    // window.scrollTo({
    //     top: 0, // Scroll to the top
    //     left: 0, // No horizontal scroll
    //     behavior: 'smooth' // Smooth scroll
    // });
    $('#hid_job_id').val(jobId);
    $.ajax({
        url: "include/handlers/ClientJobHandler.php",
        type: "POST",
        data: {
            id: jobId,
            action: "EditJobFetchAll"
        },
        dataType: "json",
        success: function(data) {
            if (data) {
                // Populate Main Project Details
                $("#id").val(data.id);
                $("#name").val(data.job_name);
                $("#platform").val(data.job_type).trigger('change');
                $("#service").val(data.service).trigger('change');
                $("#linkurl").val(data.reference_link);
                $("#jobNo").val(data.job_no);
                $("#JobFileCount").val(data.file_count);
                $("#date").val(data.final_date);
                $("#JobFiles").val(data.file_name);
                $("#Job-full-editor").val(data.description);
                $('#JobFiles').prop('required', false); // To add agai
                $("#Jobchangestatus").val(data.status).trigger('change');
                // Append content to editor
                appendContentToEditor('#Job-full-editor', data.description);
                $('.periorty_form .form-check').removeClass('checked');
                if (data.priority === "Regular") {
                    $('#critical_job_radio').prop('checked', true).closest(
                        '.form-check').addClass('checked');
                } else if (data.priority === "Rush") {
                    $('#high_periority_radio').prop('checked', true)
                        .closest('.form-check').addClass('checked');
                }
                $("#submit_job_btn").val("Updatejob").text("Update");
            }
        },
        error: function(error) {
            console.error("Error fetching project:", error);
        },
    });
});
// <! -------------- View Notes for Any One -------------- >
$(document).ready(function() {
    const $textarea = $('#Comment_repli');
    const $Address_charCount = $('#Address_charCount');
    // Update character count on input
    $textarea.on('input', function() {
        const remaining = 100 - $textarea.val().length;
        $Address_charCount.text(remaining);
    });
    document.getElementById('exportToExcel').addEventListener('click', function() {
        // Initialize DataTable
        const dataTable = $('.datatables-Project').DataTable();
        // Get all data from DataTable
        const data = dataTable.rows().data().toArray();
        // Prepare data for Excel
        const exportData = data.map((row, index) => {
            const rowData = {
                'S.No': index + 1
            };
            // If job_name exists, use job-related headers
            if (row.job_name) {
                rowData['Job Name'] = row.job_name || '';
                rowData['Job Type'] = row.job_type || '';
                rowData['Status'] = row.status || '';
                rowData['Reference Link'] = row.reference_link || '';
                rowData['Final Date'] = row.final_date || '';
                rowData['File Count'] = row.file_count || '';
                rowData['Price'] = row.price || '';
                rowData['Priority'] = row.priority || '';
                rowData['Description'] = row.description || '';
                rowData['Added By'] = row.added_by_name || '';
                rowData['Created At'] = row.created_at || '';
                rowData['Updated At'] = row.updated_at || '';
                rowData['Job Number'] = row.job_num || '';
                rowData['Service Request'] = row.service_req || '';
                rowData['Review Page'] = row.review_page || '';
                rowData['Completed Date'] = row.completed_date || '';
            }
            return rowData;
        });
        // Format current date as dd-mm-yyyy
        let currentDate = new Date();
        let day = String(currentDate.getDate()).padStart(2, '0'); // Add leading zero if necessary
        let month = String(currentDate.getMonth() + 1).padStart(2,
            '0'); // Get month (0-11, so add 1)
        let year = currentDate.getFullYear();
        let formattedDate = `${day}-${month}-${year}`;
        // Define headers for the Excel export
        let headers = [
            'S.No', 'Job Name', 'Job Type', 'Status', 'Reference Link', 'Final Date',
            'File Count',
            'Price', 'Priority', 'Description', 'Added By', 'Created At', 'Updated At',
            'Job Number', 'Service Request', 'Review Page', 'Completed Date'
        ];
        // Prepare file name
        let fileNameBase = 'View Jobs Report';
        // Convert to worksheet and workbook
        const worksheet = XLSX.utils.json_to_sheet(exportData, {
            header: headers
        });
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Report');
        // Export to Excel
        XLSX.writeFile(workbook, `${fileNameBase}-${formattedDate}.xlsx`);
    });
});

function validateJobCost(input) {
    // Remove any non-digit characters
    input.value = input.value.replace(/\D/g, '');
    // Ensure it's at most 6 digits long
    if (input.value.length > 6) {
        input.value = input.value.slice(0, 6);
    }
}

//-------------------------select checkbox file download----------------------------------------

document.getElementById('selectAllCheckbox').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

//-------------------------select  file download-----S-----------------------------------

document.getElementById('downloadAllBtn').addEventListener('click', async function() {
    const checkboxes = document.querySelectorAll('.file-checkbox:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one file.');
        return;
    }

    if (!confirm(`Download ${checkboxes.length} files?`)) return;

    // Create a visible progress UI
    const progress = document.createElement('div');
    progress.style.position = 'fixed';
    progress.style.bottom = '20px';
    progress.style.right = '20px';
    progress.style.padding = '10px';
    progress.style.background = 'white';
    progress.style.border = '1px solid #ccc';
    progress.style.borderRadius = '5px';
    progress.style.zIndex = '1000';
    document.body.appendChild(progress);

    // Download files one by one
    for (let i = 0; i < checkboxes.length; i++) {
        const fileURL = checkboxes[i].getAttribute('data-url');
        if (!fileURL || fileURL === "#") continue;

        progress.textContent = `Downloading file ${i+1} of ${checkboxes.length}...`;

        await new Promise(resolve => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = fileURL;
                link.download = '';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();

                // Clean up after a delay
                setTimeout(() => {
                    document.body.removeChild(link);
                    resolve();
                }, 10000);
            }, 0);
        });
    }

    progress.textContent = 'Downloads complete!';
    setTimeout(() => document.body.removeChild(progress), 10000);
});


</script>