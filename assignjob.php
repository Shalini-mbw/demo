<?php include('include/head.php'); ?>
<?php
require 'data/dbconfig.php';

$AddNewProject = '';
$ProjectReply = '';
$pid =  $JWT_userID;
$permissionsql = "SELECT * FROM permissions WHERE userID='$pid' ";
$resultPermission = $conn->query($permissionsql);
if ($resultPermission->num_rows > 0) {
  $row = $resultPermission->fetch_assoc();
  $AddNewProject = htmlspecialchars($row['AddNewProject']);
  $ProjectReply = htmlspecialchars($row['ProjectReply']);
}

if ($ProjectReply === 'Enable' && isset($_GET['id'])) {
  $AddNewProject = 'Enable'; // Force Enable if conditions are met
}



function getTimeRemaining($deadlineDate, $deadlineTime)
{
  $currentDateTime = new DateTime();
  $deadlineDateTime = new DateTime($deadlineDate . ' ' . $deadlineTime);
  $interval = $currentDateTime->diff($deadlineDateTime);

  return [
    'days' => $interval->days,
    'hours' => $interval->h,
    'minutes' => $interval->i
  ];
}


// <-- -----------------------STORE DB DATA TO JSON FOR TABLE ----------------------------- -->



$response = ["data" => []];

// Fetch jobs
//$sql = "SELECT * FROM job_assignments";
$sql = "SELECT ja.*, e.name AS added_by_name 
        FROM job_assignments ja
        LEFT JOIN employee e ON ja.user_id = e.id;";

$result = $conn->query($sql);

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

//Prepare the final response with data wrapped in the 'data' key
$json = json_encode($response, JSON_PRETTY_PRINT);

// Write to file
$file = 'assets/json/AddJobs.json';

if (file_put_contents($file, $json)) {
  // echo "Data successfully written to $file";
} else {
  echo "Failed to write data to $file";
}






// <-- -----------------------STORE DB DATA TO JSON FOR TABLE ----------------------------- -->

?>
<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <!-- Estimate Form code Start-->
            <form id="addclientJob">
                <div class="d-flex justify-content-center">
                    <div class="col-md-12">
                        <div class="card mb-9">
                            <div class="card-body">

                                <div class="row" id="SameEmailAlert" style="display:none;">
                                    <div class="col-12">
                                        <div class="alert alert-danger" role="alert">
                                            <p id="Same_Email_alert_para"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row gy-5">
                                    <!-- Left Column Start -->
                                    <div class="col-md-6">


                                        <!-- --- hidden Feilds for Update --- -->
                                        <input type="hidden" value="" id="hid_job_id">


                                        <!-- Name and Phone Number Start -->
                                        <div class="form-floating form-floating-outline mb-3">
                                            <input type="text" maxlength="30" required id="name" class="form-control"
                                                placeholder="MBW" />
                                            <label for="name">Job/Case Name *</label>
                                        </div>



                                        <!-- Name and Phone Number End -->
                                        <?php

                    $sql1 = "SELECT type FROM task_type";
                    $result = $conn->query($sql1);
                    $platform = isset($platform) ? $platform : 'Live';
                    echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                    echo ' <select required id="platform" name="platform" class="select2 form-select plateform_create" data-allow-clear="true" onchange="Add_task_type(this.value);">';
                    echo ' <option value="">Select</option>';

                    if ($result->num_rows > 0) {
                      $options = [];
                      while ($row = $result->fetch_assoc()) {
                        $name = htmlspecialchars($row['type']);
                        $options[] = $name; // Add name to options array
                      }
                      // Sort the options array alphabetically
                      sort($options);

                      foreach ($options as $name) {
                        $isSelected = ($name === $platform) ? ' selected' : '';
                        echo '<option value="' . $name . '"' . $isSelected . '>' . $name . '</option>';
                      }
                    } else {
                      echo '<option value="">No Type found.</option>';
                    }
                    echo '  </select>';
                    echo '   <label for="collapsible-state">Job Type </label>';
                    echo ' </div>';
                    ?>




                                        <?php

                        $sql1 = "SELECT service  FROM service_req";
                        $result = $conn->query($sql1);
                        $service = isset($service) ? $service : '';
                        echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                        echo ' <select required id="service" name="service" class="select2 form-select service_create" data-allow-clear="true" onchange="Add_Service(this.value);">';
                        echo ' <option value="">Select</option>';
                        echo ' <option value="New_Service">Create New service ➕ </option>';

                        if ($result->num_rows > 0) {
                        $options = [];
                        while ($row = $result->fetch_assoc()) {
                            $name = htmlspecialchars($row['service']);
                            $options[] = $name; // Add name to options array
                        }
                        // Sort the options array alphabetically
                        sort($options);

                        foreach ($options as $name) {
                            $isSelected = ($name === $service) ? ' selected' : '';
                            echo '<option value="' . $name . '"' . $isSelected . '>' . $name . '</option>';
                        }
                        } else {
                        echo '<option value="">No Type found.</option>';
                        }
                        echo '  </select>';
                        echo '   <label for="collapsible-state">Service Request *</label>';
                        echo ' </div>';
                        ?>







                                        <!-- Left Column End -->

                                        <!-- Details Start -->

                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <div class="mt-4"><label>Job Link</label></div>
                                            <input type="url" id="linkurl" class="form-control mt-3"
                                                placeholder="https://example.com"
                                                pattern="^(https?://)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}(/.*)?$" />
                                        </div>
                                      
                                      <div class="row mt-2 mb-3 p-0">

                                            <div class="col-md mb-md-0 mb-5">
                                                <div
                                                    class="form-check custom-option custom-option-label custom-option-basic checked">
                                                    <label class="form-check-label custom-option-content"
                                                        for="critical_job_radio">
                                                        <input name="customRadioTemp" class="form-check-input" checked
                                                            type="radio" value="Regular" id="critical_job_radio">
                                                        <span class="custom-option-header">Regular

                                                        </span>

                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div
                                                    class="form-check custom-option custom-option-label custom-option-basic">
                                                    <label class="form-check-label custom-option-content"
                                                        for="high_periority_radio">
                                                        <input name="customRadioTemp" class="form-check-input"
                                                            type="radio" value="Rush" id="high_periority_radio">
                                                        <span class="custom-option-header">Rush

                                                        </span>

                                                    </label>
                                                </div>
                                            </div>

                                        </div>


                                    </div>


                                    <!-- Right Column Start -->
                                    <div class="col-md-6 ">

                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <input type="Number" id="jobNo" class="form-control mt-3" placeholder="" />
                                            <label for="jobNo">Job/Case Number </label>
                                        </div>



                                        <!-- Date and Time Start -->
                                        <div class="form-floating form-floating-outline mb-6">
                                            <input type="text" required style="color: #393939;" id="date"
                                                class="form-control flatpickr_date_current_date"
                                                placeholder="DD-MM-YYYY" readonly="readonly" />
                                            <label for="date">Job Due Date </label>
                                        </div>
                                        <!-- Date and Time End -->



                                        <!-- <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <div class="card-body p-0">
                                                <label for="JobFiles mb-5">Choose files:<small> (File Saved for 30 Days
                                                        Only)</small></label>
                                                <input type="file" multiple class="form-control mt-2" name="JobFiles"
                                                    id="JobFiles">
                                            </div>
                                        </div> -->

                                        <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <div class="card-body p-0">
                                                <label for="JobFiles mb-5">Choose files:</label>
                                                <input type="file" multiple class="form-control mt-2"
                                                    onchange="validateFile()" name="JobFiles" id="JobFiles" required>
                                            </div>
                                            <div id="fileError" class="text-danger" style="display:none;"></div>
                                        </div>

                                        <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <input type="number" id="JobFileCount" class="form-control"
                                                placeholder="Enter a Page Count" pattern="\d{2}" maxlength="2">
                                            <label for="name">Files Page Count </label>
                                        </div>
                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <div class="mt-4"><label>Uploaded files:</label></div>
                                            <input type="text" id="upload_files" class="form-control mt-3"
                                                placeholder="Ags" />
                                        </div>

                                        <!-- Details End -->
                                    </div>
                                    <!-- Right Column End -->


                                    <div class="periorty_form w-50">
                                        
                                    </div>




                                    <div class="col-12 d-flex justify-content-start mt-7">
                                        <div class="card" style="width: 100%; box-shadow: none;">
                                            <div class="card-body project_editor_text p-0">
                                                <div id="Job-full-editor">

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" id="ErrorForProjectValidation" style="display:none;">
                                        <div class="col-12">
                                            <div class="alert alert-danger" role="alert">
                                                <p id="ProjectValidation_alert_para"></p>
                                            </div>
                                        </div>
                                    </div>



                                    <!-- Buttons -->
                                    <div class="col-12 d-flex justify-content-end mt-4">
                                        <button type="button" id="cancelbtn"
                                            class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                                        <button type="submit" id="submit_job_btn" value="AddClientJob"
                                            class="btn btn-primary">Submit </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Estimate Form code End-->
            </form>



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
                                                    <textarea class="form-control employee_details_txtBox"
                                                        id="Comment_repli" style="height: 160px;"
                                                        placeholder="Enter New Comment here" maxlength="100"></textarea>
                                                    <div id="Address_charCount" class="char-count-overlay">/100</div>
                                                </div>

                                                <!-- <label for="task">Web design</label> -->
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset"
                                                class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
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


            <!-- Whole job Files View Modal -->
            <div class="modal fade custom_model" id="whole_job_file_view_Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content p-10">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <form id="task_type_add" class="row g-5">
                                <!-- Account Details -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-center">View All Uploaded Job Files</h6>
                                        <hr class="mt-0" />
                                    </div>
                                </div>

                                <div class="row mt-5">
                                    <div class="col-12">
                                        <div class="col-md-12 mb-4">
                                            <div class="form-floating form-floating-outline">

                                                <input type="hidden" id="hiddenId"
                                                    value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">


                                                <div class="form-floating form-floating-outline mb-3">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 80%;">File Name</th>
                                                                <th style="width: 20%;">Download</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="table-border-bottom-0">
                                                            <tr>
                                                                <td>
                                                                    <i
                                                                        class="ri-file-excel-2-line ri-22px text-danger me-4"></i><span
                                                                        class="fw-medium">ShaliniProducts.xlsx</span>
                                                                </td>
                                                                <td>
                                                                    <a class="dropdown-item waves-effect"
                                                                        href="javascript:void(0);"><i
                                                                            class="ri-file-download-line me-1"></i></a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <i
                                                                        class="ri-file-excel-2-line ri-22px text-danger me-4"></i><span
                                                                        class="fw-medium">MechinaryProducts.xlsx</span>
                                                                </td>
                                                                <td>
                                                                    <a class="dropdown-item waves-effect"
                                                                        href="javascript:edit_single_file_btn();"><i
                                                                            class="ri-file-download-line me-1"></i></a>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>


                                                <div class="form-floating form-floating-outline mb-3">
                                                    <lebel for="NewFile_Single">Add Files</lebel>
                                                    <input type="file" class="form-control mt-2" name="NewFile_Single"
                                                        id="NewFile_Single" required>
                                                </div>


                                                <!-- Buttons -->
                                                <div class="col-12 d-flex justify-content-center mt-12">
                                                    <button type="reset"
                                                        class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                                                    <button type="submit" id="NewFile_edit_submit" value="NewFile_edit"
                                                        class="btn btn-primary">Submit</button>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Whole job Files View Modal -->



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
                                                    <input type="number" id="pageCount" class="form-control"
                                                        placeholder="Page Count" name="PageCount" pattern="\d*">
                                                    <label for="pageCount">Page Count</label>
                                                </div>

                                                <div class="form-floating form-floating-outline mb-3">
                                                    <input type="number" id="JobCost" class="form-control"
                                                        placeholder="Job Cost" name="JobCost" pattern="\d{1,6}"
                                                        maxlength="6" min="0" max="999999"
                                                        oninput="validateJobCost(this)">
                                                    <label for="JobCost">Job Cost</label>
                                                </div>

                                                <div class="form-floating form-floating-outline mb-3">
                                                    <select name="ChangeStatus_Job" id="Jobchangestatus"
                                                        class="form-control select2" required>
                                                        <option value="Pending">Pending</option>
                                                        <option value="In Progress">InProgress</option>
                                                        <option value="Hold">Hold</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                    <label for="changestatus">Choose Job Status</label>
                                                </div>


                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset"
                                                class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
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


            <!--Add files download to icon -->
            <div class="modal fade custom_model" id="update_admin_jobs_model1" tabindex="-1" aria-hidden="true">
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

                                <div class="row all_project_files">
                                    <div class="row mt-3">
                                        <div class="table-responsive text-nowrap">
                                            <table class="table">
                                                <!-- Table Headers (Always Visible) -->
                                                <thead>
                                                    <tr>
                                                        <th>File Name</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <!-- Dynamic rows will be added here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>




            <!--Add files download to icon -->




            <!-- Add New service Card Modal -->
            <div class="modal fade custom_model" id="Add_Service_Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <form id="service_add" class="row g-5">
                                <!-- Account Details -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-center">Add Service Request</h6>
                                        <hr class="mt-0" />
                                    </div>
                                </div>

                                <div class="row mt-5">
                                    <div class="col-12">
                                        <div class="col-md-12 mb-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="hidden" id="hiddenId"
                                                    value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">


                                                <div class="form-floating form-floating-outline mb-3">
                                                    <input type="text" name="name_service" id="name_service" value=""
                                                        placeholder="Digital Marketing" class="form-control" required />
                                                    <label for="name_des">Service Request Name</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset" class="btn btn-outline-secondary me-4 waves-effect"
                                                onclick="close_popUp('Add_service_Modal');">Cancel</button>
                                            <button type="submit" id="Add_Service_submit" value="AddService"
                                                class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!--Add New DepartMent Card Modal -->











            <!-- / Content -->
            <?php include('include/footer.php'); ?>

            <!-- Page JS -->
            <script src="assets/vendor/libs/quill/quill.js"></script>
            <script src="assets/vendor/libs/quill/katex.js"></script>
            <script src="assets/js/forms-editors.js"></script>


            <!-- --Data tables-- -->
            <!-- <script src="assets/js/addjobs.js"></script> -->
            <script>
            var $template = $('.repeater-item-template');
            var addedItems = {};

            $(document).ready(function() {
                var queryParams = new URLSearchParams(window.location.search);

                // Check if the 'id' parameter is present in the URL
                if (queryParams.has('id')) {
                    json_data();
                }



                //  <!------ Change Status Work Order table Popup   -------->

                $(document).on('click', '.Change_status_work', function(e) {
                    e.preventDefault();
                    var SubId = $(this).data('id');
                    var ProjectPId = $(this).data('pid');

                    $('#hidden_subtask_id').val('');
                    $('#hidden_subtask_id').val(SubId);
                    $('#hidden_project_pid').val('');
                    $('#hidden_project_pid').val(ProjectPId);


                    $('#Project_sts_change').modal('show');

                });

                //  <!------ Change Status Work Order table Popup   -------- >


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


                // <! -------------- View Notes for Any One -------------- >



                // <! -------------- Uploaded File View Model -------------- >

                $(document).on('click', '.view_all_uploaded_file', function(e) {
                    e.preventDefault();

                    $('#whole_job_file_view_Modal').modal('show');

                });

                // <! -------------- Uploaded File View Model -------------- >



                // <! -------------- Update Jobs Status & Price model Open -------------- >


                // $(document).on('click', '.update_admin_jobs1', function(e) {
                //     e.preventDefault();

                //     var jobId = $(this).data("id"); // Get job ID from button
                //     $('#JobhiddenId').val(jobId); // Set job ID in hidden input

                //     $.ajax({
                //         url: "include/handlers/ClientJobHandler.php",
                //         type: "POST",
                //         data: {
                //             job_id: jobId,
                //             action: "Fetch_job_Othes_details"
                //         },
                //         dataType: "json",
                //         success: function(response) {
                //             if (response.status === "success") {
                //                 // Populate the modal fields
                //                 $('#pageCount').val(response.data.file_count);
                //                 $('#JobCost').val(response.data.price);
                //                 $('#Jobchangestatus').val(response.data.status).trigger(
                //                     'change'); // Set status with Select2

                //                 // Open modal
                //                 $('#update_admin_jobs_model').modal('show');
                //             } else {
                //                 alert("Error fetching job details.");
                //             }
                //         },
                //         error: function() {
                //             alert("An error occurred while fetching the job details.");
                //         }
                //     });
                // });


                // $('#Admin_job_update').on('submit', function(e) {
                //     e.preventDefault();

                //     //Add the Preloader
                //     $('.event_trigger_loader').addClass('active');

                //     var jobId = $('#JobhiddenId').val();
                //     var pageCount = $('#pageCount').val();
                //     var jobCost = $('#JobCost').val();

                //     var jobStatus = $('#Jobchangestatus').val();

                //     $.ajax({
                //         url: "include/handlers/ClientJobHandler.php",
                //         type: "POST",
                //         data: {
                //             job_id: jobId,
                //             file_count: pageCount,
                //             price: jobCost,
                //             status: jobStatus,
                //             action: "Update_job_submit"
                //         },
                //         dataType: "json",
                //         success: function(response) {

                //             //Remove the Preloader
                //             setTimeout(function() {
                //                 $('.event_trigger_loader').removeClass('active');
                //             }, 1000);

                //             if (response.status === "success") {
                //                 showModalWithParams('Job updated successfully!', 'true');
                //                 $('#update_admin_jobs_model').modal('hide'); // Close modal
                //             } else {
                //                 alert("Error updating job.");
                //             }
                //         },
                //         error: function() {
                //             //Remove the Preloader
                //             setTimeout(function() {
                //                 $('.event_trigger_loader').removeClass('active');
                //             }, 1000);
                //             alert("An error occurred while updating the job.");
                //         }

                //     });
                // });


                // <! -------------- Update Jobs Status & Price model Open -------------- >

                // <! -------------- download files assign job -------------- >


                $(document).on('click', '.update_admin_jobs', function(e) {
                    e.preventDefault();

                    var jobId = $(this).data("id"); // Get job ID from button
                    // $('#JobhiddenId').val(jobId); // Set job ID in hidden input

                    $.ajax({
                        url: "include/handlers/ClientJobHandler.php",
                        type: "POST",
                        data: {
                            job_id: jobId,
                            action: "Fetch_job_Othes_details1"
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === "success") {
                                var fileDataArray = response
                                .data; // assuming this is an array of files
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
                                    } else {
                                        downloadLink =
                                        "#"; // Fallback if no file_path is provided
                                    }

                                    // Append new file entry
                                    var fileRow = `
                                        <tr>
                                            <td>
                                                <i class="ri-file-line ri-22px text-danger me-4"></i>
                                                <span class="fw-medium">${fileData.file_name}</span>
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
                                echo("Error fetching job details.");
                            }
                        },

                        error: function() {
                            echo("An error occurred while fetching the job details.");
                        }
                    });
                });


                // <! -------------- download files assign job -------------- >


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



                // <! -------------- Send Data to Hanlder File Add Job Open -------------- >


                $("#addclientJob").submit(async function(e) {
                    e.preventDefault();

                    //Add the Preloader
                    $('.event_trigger_loader').addClass('active');

                    let formData = new FormData();
                    let jobId = $("#hid_job_id").val(); // Hidden field for update


                    let allFieldsFilled = true;
                    let missingFields = [];

                    // Check if each required field is filled
                    if ($("#name").val().trim() === "") {
                        allFieldsFilled = false;
                        missingFields.push("Job Name");
                    }

                    if ($("#service").val().trim() === "") {
                        allFieldsFilled = false;
                        missingFields.push("Service Request");
                    }

                    // if ($("#platform").val().trim() === "") {
                    //     allFieldsFilled = false;
                    //     missingFields.push("Platform");
                    // }


                    // if (window.Client_job_fullEditor.getText().trim() === '') {
                    //     allFieldsFilled = false;
                    //     missingFields.push("Project Description");
                    // }

                    // if ($("input[name='customRadioTemp']:checked").val() === undefined) {
                    //     allFieldsFilled = false;
                    //     missingFields.push("Priority");
                    // }

                    // if ($("#date").val().trim() === "") {
                    //     allFieldsFilled = false;
                    //     missingFields.push("Date");
                    // }


                    // If any field is missing, show an alert and prevent form submission
                    if (!allFieldsFilled) {
                        let combinedMessage = "Please fill the following fields: " + missingFields
                            .join(", ");

                        // Show the alert and the message
                        $('#SameEmailAlert').show();
                        $('#Same_Email_alert_para').html(combinedMessage);

                        // Hide the alert after 3 seconds
                        setTimeout(function() {
                            $('#SameEmailAlert').hide();
                        }, 4000);

                        return; // Prevent form submission
                    }


                    // Get form input values and append them to FormData
                    formData.append("name", $("#name").val());
                    formData.append("platform", $("#platform").val());
                    formData.append("linkurl", $("#linkurl").val());
                    formData.append("project_description", encodeURIComponent($(
                        '#Job-full-editor .ql-editor').html()));
                    formData.append("priority", $("input[name='customRadioTemp']:checked").val());
                    formData.append("date", $("#date").val());
                    formData.append("file_page_count", $("#JobFileCount").val());
                   formData.append("upload_files", $("#upload_files").val());
                    formData.append("service", $("#service").val());
                    formData.append("jobNo", $("#jobNo").val());

                    // Determine action based on job ID
                    if (jobId) {
                        formData.append("action", "UpdateClientProject");
                        formData.append("job_id", jobId);
                    } else {
                        formData.append("action", "AddClientProject");
                    }

                    // Convert main files to FormData
                    let mainFiles = $("#JobFiles")[0]?.files || [];
                    for (let i = 0; i < mainFiles.length; i++) {
                        formData.append(`files[${i}]`, mainFiles[i]);
                    }

                    // Send data as AJAX request
                    $.ajax({
                        url: "include/handlers/ClientJobHandler.php",
                        type: "POST",
                        data: formData,
                        processData: false, // Required for FormData
                        contentType: false, // Required for FormData
                        beforeSend: function() {
                            $("#submit_job_btn").prop("disabled", true).text(
                                "Submitting...");
                        },
                        success: function(response) {
                            if (jobId) {
                                showModalWithParams('Project updated successfully!',
                                    'true');
                                window.location.href = 'assignedJob.php';
                            } else {
                                showModalWithParams('Project added successfully!',
                                    'true');
                                window.location.href = 'assignnedJob.php';
                            }
                        },

                        success: function(response) {
                            // Check if the jobId exists
                            if (jobId) {
                                // If jobId is set, show the modal with "Project updated successfully!"
                                showModalWithParams('Job updated successfully!',
                                    'true');
                            } else {
                                // If jobId is not set, show the modal with "Project added successfully!"
                                showModalWithParams('Job added successfully!',
                                    'true');
                            }

                            // Redirect to the 'assignedJob.php' page after a short delay to let the modal show
                            setTimeout(function() {
                                window.location.href = 'assignedJob.php';
                            },
                            4000); // 2000 milliseconds = 2 seconds (adjust the delay as needed)
                        },

                        error: function(xhr, status, error) {
                            console.error("Error:", xhr.responseText);
                            alert("An error occurred while submitting the form.");
                        },
                        complete: function() {
                            $("#submit_job_btn").prop("disabled", false).text("Submit");
                            //Remove the Preloader
                            setTimeout(function() {
                                $('.event_trigger_loader').removeClass(
                                    'active');
                            }, 1000);
                        }
                    });
                });

                //    function validateFile() {
                //     const fileInput = $('#JobFiles')[0];
                //     const fileError = $('#fileError');
                //     const allowedExtensions = /\.(jpg|jpeg|png|webp|pdf)$/i;
                //     const maxSizeInBytes = 20 * 1024 * 1024; // 20MB

                //     // Clear previous errors
                //     fileError.hide().text('');

                //     const files = fileInput.files;
                //     if (!files.length) return true;

                //     for (let i = 0; i < files.length; i++) {
                //         const file = files[i];

                //         // ✅ Check file extension
                //         if (!allowedExtensions.test(file.name)) {
                //             fileError.text(`"${file.name}" is not a valid file type. Allowed: .jpg, .jpeg, .png, .webp, .pdf.`);
                //             fileError.show();
                //             fileInput.value = ''; // Clear input
                //             return false;
                //         }

                //         // ✅ Check file size
                //         if (file.size > maxSizeInBytes) {
                //             fileError.text(`"${file.name}" exceeds 2MB limit.`);
                //             fileError.show();
                //             fileInput.value = ''; // Clear input
                //             return false;
                //         }

                //         // ✅ If image, check resolution
                //         if (/\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
                //             const reader = new FileReader();
                //             reader.onload = function(e) {
                //                 const img = new Image();
                //                 img.onload = function() {
                //                     if (img.width !== 1080 || img.height !== 1080) {
                //                         fileError.text(`"${file.name}" must be exactly 1080x1080 pixels.`);
                //                         fileError.show();
                //                         fileInput.value = ''; // Clear input
                //                     }
                //                 };
                //                 img.onerror = function() {
                //                     fileError.text(`Could not load "${file.name}".`);
                //                     fileError.show();
                //                     fileInput.value = '';
                //                 };
                //                 img.src = e.target.result;
                //             };
                //             reader.readAsDataURL(file);
                //         }
                //     }

                //     return true;
                // }



                // <! --------------Edit  Send Data to Hanlder File Add Job Close -------------- >



                var url = window.location.href;

                var urlParams = new URLSearchParams(window.location.search);

                var edit_id = urlParams.get('edit_id');

                if (edit_id) {


                    // $(document).on("click", ".edit_add_job_btn", function() {

                    //     let jobId = new URL($(this).attr('href')).searchParams.get('edit_id');
                    //     alert("The jobId is: " + jobId);
                    //let jobId = $(this).data("edit_id");

                    // // Scroll to the top of the page
                    // window.scrollTo({
                    //     top: 0, // Scroll to the top
                    //     left: 0, // No horizontal scroll
                    //     behavior: 'smooth' // Smooth scroll
                    // });

                    // $('#hid_job_id').val(jobId);

                    $.ajax({
                        url: "include/handlers/ClientJobHandler.php",
                        type: "POST",

                        data: {
                            id: edit_id,
                            action: "EditJobFetchAll"
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data) {
                                // Populate Main Project Details
                                // $("#id").val(data.id);
                                // $("#name").val(data.job_name);
                                // $("#platform").val(data.job_type).trigger('change');
                                // $("#service").val(data.service).trigger('change');
                                // $("#linkurl").val(data.reference_link);
                                // $("#jobNo").val(data.job_no);
                                // $("#JobFileCount").val(data.file_count);
                                // $("#date").val(data.final_date);
                                // $("#JobFiles").val(data.file_name);
                                // $("#Job-full-editor").val(data.description);

                                $("#hid_job_id").val(data.id);
                                $("#name").val(data.job_name);
                                //$("#platform").val(data.job_type).trigger('change');
                                $("#service").val(data.service).trigger('change');
                                $("#linkurl").val(data.reference_link);
                                $("#JobFileCount").val(data.file_count);
                               $("#upload_files").val(data.file_name);
                                $("#date").val(data.final_date);
                                appendContentToEditor('#Job-full-editor', data.description);
                                $("#jobNo").val(data.job_no);
                                $("#JobFiles").val(data.file_name);



                                $('#JobFiles').prop('required', false); // To add agai

                                //$("#Jobchangestatus").val(data.status).trigger( 'change');

                                // Append content to editor


                                // $('.periorty_form .form-check').removeClass('checked');
                                $('#critical_job_radio').prop('checked',
                                    false); // Uncheck "Regular"
                                $('#high_periority_radio').prop('checked', false); // Uncheck "Rush"


                                if (data.priority === "Regular") {
                                    $('#critical_job_radio').prop('checked', true)
                                        .closest(
                                            '.form-check').addClass('checked');
                                } else if (data.priority === "Rush") {
                                    $('#high_periority_radio').prop('checked', true)
                                        .closest('.form-check').addClass('checked');
                                }


                                $("#submit_job_btn").val("Update job").text("Update");
                                $("#platform").val(data.job_type).trigger('change');
                            }
                        },
                        error: function(error) {
                            console.error("Error fetching project:", error);
                        },
                    });
                    //});
                }

                const $textarea = $('#Comment_repli');
                const $Address_charCount = $('#Address_charCount');

                // Update character count on input
                $textarea.on('input', function() {
                    const remaining = 100 - $textarea.val().length;
                    $Address_charCount.text(remaining);
                });


                $('#Add_Service_Modal').on('hidden.bs.modal', function() {
                    $('.service_create').val(null).trigger('change');
                });


            });
            //-------------validate a job------------------

            function validateJobCost(input) {
                // Remove any non-digit characters
                input.value = input.value.replace(/\D/g, '');

                // Ensure it's at most 6 digits long
                if (input.value.length > 6) {
                    input.value = input.value.slice(0, 6);
                }
            }



            function Add_Service(value) {
                if (value == "New_Service") {
                    $('#Add_Service_Modal').modal('show');
                }

            }


            // ============= Radio Button selected colour change function =============
            document.addEventListener("DOMContentLoaded", function() {
                const radioButtons = document.querySelectorAll(".form-check-input");

                radioButtons.forEach((radio) => {
                    radio.addEventListener("change", function() {
                        // Remove previous colors
                        document.querySelectorAll(".custom-option").forEach((el) => {
                            el.classList.remove("critical", "high", "medium", "low");
                        });

                        // Apply a new class based on selection
                        if (this.checked) {
                            let parent = this.closest(".custom-option");
                            if (this.value === "Critical") parent.classList.add("critical");
                            if (this.value === "High") parent.classList.add("high");
                            if (this.value === "Medium") parent.classList.add("medium");
                            if (this.value === "Low") parent.classList.add("low");
                        }
                    });
                });
            });

            // ============= Radio Button selected colour change function =============



            $('#service_add').on('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                //Add the Preloader
                $('.event_trigger_loader').addClass('active');

                // Get form values
                const name = $('#name_service').val();
                const submit = $('#Add_Service_submit').val();

                // Submit the form data using Fetch API
                fetch('function.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'name': name,
                            'submit': submit,

                        })
                    })
                    .then(response => response.text())
                    .then(result => {
                        // Log the response from PHP
                        console.log('Server response:', result);

                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        // Trim any extra whitespace from the result
                        const trimmedResult = result.trim();

                        // Handle the response text
                        if (trimmedResult === 'success') {

                            showModalWithParams(`${name} Added`, 'true');
                        } else if (trimmedResult === 'duplicateName') {
                            showModalWithParams(`${name} is already exist`, 'false');
                        } else {
                            alert('Unexpected response from the server: ' + trimmedResult);
                            showModalWithParams(`${trimmedResult}`, 'false');
                        }
                        $('#Add_service_Modal').modal('hide');
                    })
                    .catch(error => {
                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        showModalWithParams(`An error occurred: ${error}`, 'false');
                        $('#Add_service_Modal').modal('hide');
                    });
            });

            function appendContentToEditor(selector, content) {
                var editor = new Quill(selector, {
                    theme: 'snow'
                });

                // Set the description into the Quill editor
                editor.root.innerHTML = content;
            }
            </script>

            <script>
            function validateFile() {
            const fileInput = $('#JobFiles')[0];
            const fileError = $('#fileError');
            const allowedExtensions = /\.(jpg|jpeg|png|webp|pdf)$/i;
            const maxSizeInBytes = 2 * 1024 * 1024 * 1024; // 2GB

            // Clear previous errors
            fileError.hide().text('');

            const files = fileInput.files;
            if (!files.length) return true;

            for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // ✅ Check file extension
        if (!allowedExtensions.test(file.name)) {
            fileError.text(
                `"${file.name}" is not a valid file type. Allowed: .jpg, .jpeg, .png, .webp, .pdf.`
            );
            fileError.show();
            fileInput.value = ''; // Clear input
            return false;
        }
              
               // ✅ Check file name length
                    if (file.name.length > 50) {
                        fileError.text(`"${file.name}" has too long name. Limit is 50 characters only allowed.`);
                        fileError.show();
                        fileInput.value = '';
                        return false;
                    }


        // ✅ Check file size
        if (file.size > maxSizeInBytes) {
            fileError.text(`"${file.name}" exceeds 2GB limit.`);
            fileError.show();
            fileInput.value = ''; // Clear input
            return false;
        }
    }

    return true;
}
            </script>