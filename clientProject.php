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






// <-- -----------------------STORE DB DATA TO JSON FOR TABLE ----------------------------- -->

$response = ["data" => []];

// Fetch projects
$sql = "SELECT cp.*, e.name AS created_by_name, e.picture AS Profile_pic
        FROM client_projects cp
        LEFT JOIN employee e ON cp.created_by = e.id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($project = $result->fetch_assoc()) {
        $projectId = $project["id"];

        // Fetch assigned users
        $assignees = [];
        $assigneeQuery = "SELECT cpa.*, e.name AS employee_name, e.picture AS employee_picture 
                          FROM client_project_assignees cpa
                          LEFT JOIN employee e ON cpa.user_id = e.id
                          WHERE cpa.project_id = ?";
        $stmt = $conn->prepare($assigneeQuery);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $assigneeResult = $stmt->get_result();

        while ($assignee = $assigneeResult->fetch_assoc()) {

          $EachUserSubtaskFileQuery = "SELECT COUNT(*) as file_count FROM client_assignee_files WHERE project_id = ? AND user_id = ?";
          $eachUser_stmt = $conn->prepare($EachUserSubtaskFileQuery);
          $eachUser_stmt->bind_param("is", $projectId, $assignee["user_id"]);
          $eachUser_stmt->execute();
          $EachUser_subtaskFileResult = $eachUser_stmt->get_result()->fetch_assoc();
          $EachUser_subtaskFileCount = $EachUser_subtaskFileResult["file_count"] ?? 0;

            $assignees[] = [
                "id" => $assignee["id"],
                "name" => $assignee["employee_name"],
                "assignee_pic" => $assignee["employee_picture"],
                "deadlineDate" => $assignee["deadline_date"],
                "deadlineTime" => $assignee["deadline_time"],
                "SubTaskStatus" => $assignee["status"] ?? "Pending",
                "SubtaskNote" => null,
                "EachUserFileCount" => $EachUser_subtaskFileCount,
                "info" => $assignee["user_description"],
                "completed_link" => null
            ];
        }


        // if (isset($_GET['id'])) {
           // $projectId = intval($_GET['id']);
         
           // Fetch files[] for project
        //    $jobId = 47; // or from $_GET['id']
        //    $userId = $JWT_userID; // or however you're getting the logged-in user
           
           $fileQuery = "SELECT * FROM job_files WHERE job_id = ?";
           $stmt = $conn->prepare($fileQuery);
           $stmt->bind_param("i", $projectId);
           $stmt->execute();
           $fileResult = $stmt->get_result();
           
           $files = [];
           while ($file = $fileResult->fetch_assoc()) {
               $files[] = [
                   "id" => $file["id"],
                   "file_name" => $file["file_name"],
                   "file_path" => $file["file_path"],
                   "uploaded_at" => $file["uploaded_at"]
               ];
           } 
        
        
                            // $files = [];

                            // // Step 1: Get job IDs assigned to the user
                            // $jobIdQuery = "SELECT id FROM job_assignments WHERE user_id = ?";
                            // $stmt = $conn->prepare($jobIdQuery);
                            // $stmt->bind_param("i", $userId);
                            // $stmt->execute();
                            // $jobResult = $stmt->get_result();

                            // $jobIds = [];
                            // while ($row = $jobResult->fetch_assoc()) {
                            //     $jobIds[] = $row["id"];
                            // }

                            // // Step 2: Fetch files for those job IDs
                            // if (!empty($jobIds)) {
                            //     $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
                            //     $types = str_repeat('i', count($jobIds));
                                
                            //     $fileQuery = "SELECT * FROM job_files WHERE job_id IN ($placeholders)";
                            //     $stmt = $conn->prepare($fileQuery);
                            //     $stmt->bind_param("i", $jobIds);
                            //     $stmt->execute();
                            //     $fileResult = $stmt->get_result();

                            //     while ($file = $fileResult->fetch_assoc()) {
                            //         $files[] = [
                            //             "id" => $file["id"],
                            //             "file_name" => $file["file_name"],
                            //             "file_path" => $file["file_path"],
                            //             "uploaded_at" => $file["uploaded_at"]
                            //         ];
                            //     }
                            // }
        
           //$projectFileCount = count($files);

        // Count total project files
        $fileQuery = "SELECT COUNT(*) as file_count FROM client_project_files WHERE project_id = ?";
        $stmt = $conn->prepare($fileQuery);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $fileResult = $stmt->get_result()->fetch_assoc();
        $projectFileCount = $fileResult["file_count"] ?? 0;

        // Count total subtask user files
        $subtaskFileQuery = "SELECT COUNT(*) as file_count FROM client_assignee_files WHERE project_id = ?";
        $stmt = $conn->prepare($subtaskFileQuery);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $subtaskFileResult = $stmt->get_result()->fetch_assoc();
        $subtaskFileCount = $subtaskFileResult["file_count"] ?? 0;


        // Calculate total file count
        $totalFiles = $projectFileCount + $subtaskFileCount;

        $response["data"][] = [
            "id" => $project["id"],
            "pid" => generatePID($project["id"]),
            "priority" => $project["priority"],
            "name" => $project["name"],
            "type" => $project["type"],
            "assigned_by" => $project["created_by_name"],
            "assigned_by_pic" => $project["Profile_pic"],
            "pageCount" => $project["page_count"],
            "assignedTo" => $assignees[0]["name"] ?? "Unassigned",
            "deadlineDate" => $project["final_date"],
            "deadlineTime" => "00:00:00", // Modify as needed
            "ProjectStatus" => $project["status"],
            "CompletedDate" => null,
            "assignments" => $assignees,
            "totalFiles" => $totalFiles,
            "files" => $files // Include the files array // Add total file count here
            
        ];
    
    }
}

// Prepare the final response with data wrapped in the 'data' key
$json = json_encode($response, JSON_PRETTY_PRINT);

// Write to file
$file = 'assets/json/ClientProject.json';

if (file_put_contents($file, $json)) {
    // echo "Data successfully written to $file";
} else {
    echo "Failed to write data to $file";
}



// Function to generate unique PID
function generatePID($id)
{
  return $id . "." . substr(str_replace(".", "", microtime(true)), 0, 8);
}

// <-- -----------------------STORE DB DATA TO JSON FOR TABLE ----------------------------- -->





// ==============Fetch All Users to JSON================


$sql = "SELECT * FROM employee ORDER BY id ASC ";
$result = $conn->query($sql);

$data = array();

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Add each row to the data array
    $data[] = array(

      'value' => $row['id'],
      'name' => $row['name'],
      'avatar' => $row['picture'],
      'email' => $row['email']
    );
  }
}
$response = array('data' => $data);
$json = json_encode($response, JSON_PRETTY_PRINT);
//echo json_encode($response, JSON_PRETTY_PRINT);

$file = 'assets/json/users.json';


if (file_put_contents($file, $json)) {
  // echo "Data successfully written to $file";
} else {
  echo "Failed to write data to $file";
}



?>
<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <?php if ($AddNewProject === 'Enable') : ?>
            <!-- Estimate Form code Start-->
            <form id="ClientaddProject">
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
                                        <!-- //<input type="hidden" value="" id="hid_id"> -->
                                        <input type="hidden" value="" id="hid_project_id">

                                        <!-- Name and Phone Number Start -->
                                        <div class="form-floating form-floating-outline mb-3">
                                            <input type="text" required id="name" class="form-control" placeholder="MBW"
                                                maxlength="50" title="Only letters and spaces are allowed." />
                                            <label for="name">Project Name *</label>
                                        </div>
                                        <div class="form-floating form-floating-outline mb-6">
                                            <input type="text" required id="client_id" name="client_id"
                                                class="form-control" placeholder="CU0001" maxlength="50"
                                                title="Only letters and spaces are allowed." />
                                            <label for="name">Cliend Id*</label>
                                        </div>

                                        <!-- Name and Phone Number End -->
                                        <?php

                      $sql1 = "SELECT type FROM task_type";
                      $result = $conn->query($sql1);
                      $platform = isset($platform) ? $platform : '';
                      echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                      echo ' <select required id="platform" name="platform" class="select2 form-select plateform_create" data-allow-clear="true" onchange="Add_task_type(this.value);">';
                      echo ' <option value="">Select</option>';
                      if ($SettingAddDesignation  === 'Enable') {
                        echo ' <option value="New_task_type" class="add-new-project">Add New Project Division ➕ </option>';
                      }

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
                      echo '   <label for="collapsible-state">Project Type *</label>';
                      echo ' </div>';
                      ?>
                                        <!-- Left Column End -->

                                        <!-- Details Start -->



                                        <div class="client_periorty_form">
                                            <label>Client Priority:</label>
                                            <div class="form-floating form-floating-outline mb-6 mt-4 row">

                                                <div class="col-md-4 mb-md-0 mb-5">
                                                    <div class="form-check custom-option custom-option-basic checked">
                                                        <label
                                                            class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                            for="admin">
                                                            <input type="radio" required name="client_priority"
                                                                class="form-check-input" value="Regular" id="regular">
                                                            <span class="custom-option-header inner_ship_selct">
                                                                <span class="h6 mb-0">Regular</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check custom-option custom-option-basic">
                                                        <label
                                                            class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                            for="employee">
                                                            <input type="radio" required name="client_priority"
                                                                class="form-check-input" value="Rush" id="rush">
                                                            <span class="custom-option-header inner_ship_selct">
                                                                <span class="h6 mb-0">Rush</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>


                                    </div>

                                    <!-- Right Column Start -->
                                    <div class="col-md-6 ">
                                        <!-- Date and Time Start -->
                                        <div class="form-floating form-floating-outline mb-6 ">
                                            <input type="text" required style="color: #393939;"
                                                value="<?php echo empty($editDate) ? '' : htmlspecialchars($editDate); ?>"
                                                id="date" class="form-control flatpickr_date_current_date"
                                                placeholder="DD-MM-YYYY" readonly="readonly" />
                                            <label for="date">Final Date *</label>
                                        </div>

                                        <!-- Date and Time End -->
                                        <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <div class="card-body p-0">
                                                <label for="WholeProjectFiles mb-5">Choose files:</label>
                                                <input type="file" multiple class="form-control mt-2"
                                                    onchange="validateFile()" name="WholeProjectFiles"
                                                    id="WholeProjectFiles" >
                                            </div>
                                            <div id="fileError" class="text-danger" style="display:none;"></div>
                                        </div>

                                        <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <input type="number" id="JobFileCount" class="form-control" placeholder="30"
                                                maxlength="2" />
                                            <label for="JobFileCount">Files Page Count</label>
                                        </div>

                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <div class="mt-4"><label>Project Link:</label></div>
                                            <input type="url" id="linkurl" class="form-control mt-3"
                                                placeholder="https://example.com"
                                                pattern="^(https?://)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}(/.*)?$" />
                                        </div>

                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <div class="mt-4"><label>Uploaded files:</label></div>
                                            <input type="text" id="upload_files" class="form-control mt-3"
                                                placeholder="Ags" />
                                        </div>

                                        <!-- Details End -->
                                    </div>
                                    <!-- Right Column End -->

                                    <div class="col-12 d-flex justify-content-start mt-5">
                                        <div class="card" style="width: 100%; box-shadow: none;">
                                            <div class="card-body project_editor_text p-0">
                                                <div id="full-editor">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="periorty_form">
                                        <label>Admin Priority:</label>
                                        <div class="row mt-2 mb-3 p-0">
                                            <div class="col-md mb-md-0 mb-5">
                                                <div
                                                    class="form-check custom-option custom-option-label custom-option-basic">
                                                    <label class="form-check-label custom-option-content"
                                                        for="critical_job_radio">
                                                        <input name="customRadioTemp" class="form-check-input"
                                                            type="radio" value="Critical" id="critical_job_radio">
                                                        <span class="custom-option-header">Critical
                                                            <span class="h6 mb-0"></span>
                                                        </span>
                                                        <span class="custom-option-body">
                                                            <small>Immediate action required, top priority.</small>
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
                                                            type="radio" value="High Priority"
                                                            id="high_periority_radio">
                                                        <span class="custom-option-header">High Priority
                                                            <span class="h6 mb-0"></span>
                                                        </span>
                                                        <span class="custom-option-body">
                                                            <small>Attention needed within the next hours.</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div
                                                    class="form-check custom-option custom-option-label custom-option-basic checked">
                                                    <label class="form-check-label custom-option-content"
                                                        for="medium_periority_radio">
                                                        <input name="customRadioTemp" class="form-check-input"
                                                            type="radio" value="Medium Priority"
                                                            id="medium_periority_radio">
                                                        <span class="custom-option-header">Medium Priority
                                                            <span class="h6 mb-0"></span>
                                                        </span>
                                                        <span class="custom-option-body">
                                                            <small>Important but not time-sensitive now.</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div
                                                    class="form-check custom-option custom-option-label custom-option-basic">
                                                    <label class="form-check-label custom-option-content"
                                                        for="low_periority_radio">
                                                        <input name="customRadioTemp" class="form-check-input"
                                                            type="radio" value="Low Priority" id="low_periority_radio">
                                                        <span class="custom-option-header">Low Priority
                                                            <span class="h6 mb-0"></span>
                                                        </span>
                                                        <span class="custom-option-body">
                                                            <small>Can be handled when possible.</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!--Repeater Tag Employee Details -->
                                    <div class="col-md-12" style="margin: 0;">
                                        <div class="card-body p-0">
                                            <div class="">
                                                <div class="repeater-list" id="repeater-list">
                                                    <!-- This is the template for the repeater item -->
                                                    <div class="repeater-item-template project_emp_rpt"
                                                        style="display: none;">
                                                        <div class="repeater-wrapper pt-0 pt-md-9">
                                                            <div class="d-flex border rounded position-relative pe-0 mb-5"
                                                                style="background: #fffefe;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;">
                                                                <div class="row w-100 p-5 gx-5">
                                                                    <div class="col-md-7 col-12 mb-md-0 mb-4">
                                                                        <p class="h6 repeater-title">Specific User</p>
                                                                        <input type="text"
                                                                            class="form-control invoice-item-price mb-5 emp_name_readonly"
                                                                            readonly required />
                                                                        <!-- ---- This Hidden field is for Repeater concept------ -->
                                                                        <input type="hidden" class="repeater_emp_id">
                                                                        <div class="rpt_project_textarea">
                                                                            <!-- <textarea class="form-control mt-5 rpt_project_textbox" rows="2"
                                          placeholder="Project Assign Information for Specific Employee *"></textarea> -->
                                                                            <div
                                                                                class="card-body project_editor_text p-0">
                                                                                <div class="rpt-full-editor">
                                                                                    <h6>Quill Rich Text Editor</h6>
                                                                                    <p>
                                                                                        Cupcake ipsum dolor sit amet.
                                                                                        Halvah cheesecake chocolate bar
                                                                                        gummi bears cupcake. Pie
                                                                                        macaroon bear claw. Soufflé I
                                                                                        love candy canes I love cotton
                                                                                        candy I love.
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div
                                                                                class="char-count-overlay Rpt_charCount">
                                                                                /150</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-5 col-12 mb-md-0 mb-4">
                                                                        <div class="row mb-5 mt-5">
                                                                            <div class="col-md-12 col-12 mb-md-0 mb-4">
                                                                                <p class="h6 repeater-title">Choose File
                                                                                </p>
                                                                                <input type="file" multiple
                                                                                    class="form-control mt-2"
                                                                                    name="Rpt_User_Specific_files"
                                                                                    id="Rpt_User_Specific_files">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6 col-12 mb-md-0 mb-4">
                                                                                <p class="h6 repeater-title">Final Date
                                                                                    *</p>
                                                                                <input type="text"
                                                                                    placeholder="DD-MM-YYYY"
                                                                                    class="form-control flatpickr_date_rpt invoice-item-price empdeadlinedate  mb-5" />
                                                                                <!-- <div
                                                                                    style=" background: #1265A629; padding: 5px 13px; border-radius: 7px;">
                                                                                    <p class="h6 repeater-title">Total
                                                                                        Available
                                                                                        Time Form Now</p>
                                                                                    <p
                                                                                        class="mb-0 mt-2 text-heading remaining-time">
                                                                                        0 Days 00 Hours 00 Mins
                                                                                    </p>
                                                                                </div> -->
                                                                            </div>
                                                                            <div class="col-md-6 col-12 mb-md-0 mb-4">
                                                                                <p class="h6 repeater-title">Final Time
                                                                                    *</p>
                                                                                <input type="text" readonly="readonly"
                                                                                    placeholder="HH:MM"
                                                                                    class="form-control flatpickr_time_rpt empdeadlinetime invoice-item-qty" />
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                 <!-- <div
                                                                    class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                                                    <i
                                                                        class="ri-close-line cursor-pointer remove-repeater-item"></i>
                                                                </div> -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- This is the template for the repeater item -->
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

                                    <!--Repeater Tag Employee Details -->

                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline form-floating-select2 mt-5">
                                            <div class="form-floating form-floating-outline">
                                                <input id="TagifyUserList" name="TagifyUserList"
                                                    class="form-control h-auto" value="" />
                                                <label for="TagifyUserList">Tag Users *</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tag Employee End -->

                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline form-floating-select2 mt-5">
                                            <div class="form-floating form-floating-outline">

                                                <select name="ChangeStatus_Project" id="Projectchangestatus"
                                                    class="form-control select2" required>
                                                    <option>Pending</option>
                                                    <option>InProgress</option>
                                                    <option>Hold</option>
                                                    <option>Completed</option>
                                                </select>

                                            </div>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="col-12 d-flex justify-content-end mt-4">
                                        <button type="button" id="cancelbtn"
                                            class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                                        <button type="submit" id="Project_submit" value="AddProject"
                                            class="btn btn-primary">Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Estimate Form code End-->
            </form>
            <?php endif; ?>
            <!-- -----Data Table assignTask Start------ -->
            <?php if ($ProjectReply === 'Enable') : ?>
            <div class="mt-5">
                <!-- Data Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-datatable table-responsive">

                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle" type="button"
                                            id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            All Status
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="All"><i class="fas fa-circle" style="color: #000;"></i>
                                                    All Status</a></li>

                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Pending"><i class="fas fa-circle"
                                                        style="color: red;"></i> Pending</a></li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="InProgress">
                                                    <i class="fas fa-circle" style="color: blue;"></i> In Progress</a>
                                            </li>
                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Hold">
                                                    <i class="fas fa-circle" style="color: orange;"></i> Hold</a></li>

                                            <li><a class="dropdown-item dropdown-item-table-status" href="#"
                                                    data-status="Completed"><i class="fas fa-circle"
                                                        style="color: green;"></i> Completed</a></li>
                                        </ul>
                                    </div>

                                    <table class="datatables-Project table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>S.No</th>
                                                <th>Flag</th>
                                                <th>Project Name</th>
                                                <th>Project Type</th>
                                                <th>Assigned By</th>
                                                <th>Assigned To</th>
                                                <th>File Count</th>
                                                <th>Page<br>Count</th>
                                                <th>Deadline Date</th>
                                                <th>Status</th>
                                                <th>Files</th>
                                                <th>Actions</th>

                                            </tr>
                                        </thead>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Project Status Card Modal -->
                <div class="modal fade custom_model" id="Project_sts_change" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                        <div class="modal-content">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <form id="updateSubTaskForm">
                                <div class="modal-body p-0">
                                    <div class="text-center mb-6">
                                        <h4 class="mb-2">Project Task Status</h4>
                                        <p>You can change project status here....</p>
                                    </div>

                                    <!-- ------Alert for Required------- -->
                                    <div class="text-center mb-6 required_alert" style="display: none;">
                                        <div class="alert alert-danger" role="alert">
                                            <span id="alert_top_text"></span>
                                        </div>
                                    </div>
                                    <!-- ------Alert for Required------- -->

                                    <div class="col-12 fv-plugins-icon-container mb-3">
                                        <div class="input-group input-group-merge">
                                            <div class="form-floating form-floating-outline">
                                                <input type="hidden" id="hidden_subtask_id">
                                                <input type="hidden" id="hidden_project_pid">
                                                <select name="re_status"
                                                    class="select2 Status_select form-select form-select-lg Subtask_Status_select"
                                                    data-allow-clear="true" required>
                                                    <option value="">Select Status</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="InProgress">InProgress</option>
                                                    <option value="Completed">Completed</option>
                                                    <option value="Extend">Extend</option>
                                                </select>
                                                <label for="Subtask_Status_select">Choose Status *</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 fv-plugins-icon-container" id="Date_time_extended_status"
                                        style="display: none;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="input-group input-group-merge">
                                                    <div class="form-floating form-floating-outline">
                                                        <input type="text" id="next_date" placeholder="DD-MM-YYYY"
                                                            class="form-control flatpickr_date_current_date invoice-item-price Next_Deadline_date mb-5" />
                                                        <label for="next_date">Next Final Date *</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="input-group input-group-merge">
                                                    <div class="form-floating form-floating-outline">
                                                        <input type="text" id="next_time" readonly="readonly"
                                                            placeholder="HH:MM"
                                                            class="form-control flatpickr_time invoice-item-qty Next_Deadline_time" />
                                                        <label for="next_time">Next Final Time *</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 fv-plugins-icon-container mt-5 mb-5"
                                        id="note_task_status_parent">
                                        <div class="input-group input-group-merge">
                                            <div class="form-floating form-floating-outline">
                                                <textarea class="form-control" id="note_task_status"
                                                    style="height: 150px;"
                                                    placeholder="Add your commented here for this task..."></textarea>
                                                <label for="note_task_status" id="Project_Notes_label">Completed
                                                    Notes</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 fv-plugins-icon-container mb-5" id="task_completed_link"
                                        style="display: none;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="input-group input-group-merge">
                                                    <div class="form-floating form-floating-outline">
                                                        <input type="text" placeholder="Link for Completed works"
                                                            class="form-control mb-5 completed_link_input" />
                                                        <label for="next_date">Completed Link *</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 d-flex flex-wrap justify-content-center gap-4 row-gap-4">
                                        <button type="submit" name="UpdateSubTaskStatus"
                                            class="btn btn-primary cnf_move_work_order">Update Status</button>
                                        <button type="reset" class="btn btn-outline-secondary btn-reset waves-effect"
                                            data-bs-dismiss="modal" aria-label="Close">
                                            Cancel
                                        </button>
                                    </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!--/ Update Project Status Card Modal -->

            <!-- View Work Status Card Modal -->
            <div class="modal fade custom_model" id="Project_sts_Notes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <div class="text-center mb-6">
                                <h4 class="mb-2">Project Sub Task Details</h4>
                            </div>

                            <div class="text-center view_status_notes mb-6">
                                <div id="view_notes_para"></div>
                            </div>

                            <div class="text-center view_status_notes mb-6" id="Completed_links_ATag">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!--View Work Status Card Modal -->

            <!-- Add New Task Type Card Modal -->
            <div class="modal fade custom_model" id="Add_task_type_Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <form id="task_type_add" class="row g-5">
                                <!-- Account Details -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-center">Add Project Division </h6>
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
                                                    <input type="text" name="name_task_type" id="name_task_type"
                                                        value="" placeholder=" Add New Project Division Name"
                                                        class="form-control" required />
                                                    <label for="name_dep">Add New Project Division </label>
                                                </div>
                                                <!-- <label for="task">Web design</label> -->
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset" class="btn btn-outline-secondary me-4 waves-effect"
                                                onclick="close_popUp('Add_task_type_Modal');">Cancel</button>
                                            <button type="submit" id="Add_task_type_submit" value="Addtask"
                                                class="btn btn-primary">Submit</button>
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
            <!-- Large Modal -->
            <div class="modal fade custom_model" id="update_admin_jobs_model12" tabindex="-1" aria-hidden="true">
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

            <!-- Page JS -->
            <script src="assets/vendor/libs/quill/quill.js"></script>
            <script src="assets/vendor/libs/quill/katex.js"></script>
            <script src="assets/js/forms-editors.js"></script>

            <script src="assets/vendor/libs/tagify/tagify.js"></script>
            <script src="assets/js/forms-tagify.js"></script>

            <link href="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.6.0/tagify.min.css" rel="stylesheet">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.6.0/tagify.min.js"></script>

            <!-- / Content -->
            <?php include('include/footer.php'); ?>

            <!-- --Data tables-- -->
            <script src="assets/js/clientProject.js"></script>

            <script>
            // var $template = $('.repeater-item-template');
            // var addedItems = {};
            $(document).ready(function() {
                // ============Text Box Count Validation and Count Show==========
                const textarea = document.getElementById("details");
                const charCount = document.getElementById("charCount");
                // Update character count on input
                textarea.addEventListener("input", function() {
                    const remaining = 300 - textarea.value.length;
                    charCount.textContent = `${remaining}`;
                });
                $('#repeater-list').on('input', '.empdeadlinedate, .empdeadlinetime', function() {
                    var $item = $(this).closest('.repeater-wrapper');
                    var dateValue = $item.find('.empdeadlinedate').val();
                    var timeValue = $item.find('.empdeadlinetime').val();
                    if (dateValue && timeValue) {
                        calculateAndDisplayTimeRemaining($item, dateValue, timeValue);
                    }
                });
                // ============Text Box Count Validation and Count Show==========
                function calculateAndDisplayTimeRemaining($item, deadlineDate, deadlineTime) {
                    const now = new Date();
                    // Combine the date and time into a valid ISO 8601 string
                    const deadlineDateTime = new Date(deadlineDate.split('-').reverse().join('-') + 'T' +
                        deadlineTime);
                    const timeDiff = deadlineDateTime - now;
                    if (timeDiff > 0) {
                        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
                        // Update the display with the calculated time remaining
                        $item.find('.text-heading').text(`${days} Days ${hours} Hours ${minutes} Mins`);
                    } else {
                        // If the deadline has passed
                        $item.find('.text-heading').text('Time Over');
                    }
                }
                // ----------  Send Task Type To Function.Php----------   
                $('#task_type_add').on('submit', function(e) {
                    e.preventDefault(); // Prevent the default form submission
                    //Add the Preloader
                    $('.event_trigger_loader').addClass('active');
                    // Get form values
                    const name = $('#name_task_type').val();
                    const submit = $('#Add_task_type_submit').val();
                    // Submit the form data using Fetch API
                    fetch('function.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'task': name,
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
                            } else if (trimmedResult === 'updated') {
                                showModalWithParams(`${name} Updated`, 'true');
                            } else {
                                alert('Unexpected response from the server: ' + trimmedResult);
                                showModalWithParams(`${trimmedResult}`, 'false');
                            }
                            $('#Add_task_type_Modal').modal('hide');
                        })
                        .catch(error => {
                            //Remove the Preloader
                            setTimeout(function() {
                                $('.event_trigger_loader').removeClass('active');
                            }, 1000);
                            showModalWithParams(`An error occurred: ${error}`, 'false');
                            $('#Add_task_type_Modal').modal('hide');
                        });
                });
                // Listen for the modal hidden event
                $('#Add_task_type_Modal').on('hidden.bs.modal', function() {
                    $('.plateform_create').val(null).trigger('change');
                });
                // When the button with ID #Project_sts_change is clicked
                $('#Project_sts_change').on('hidden.bs.modal', function() {
                    // Reset all form fields inside the form with ID #updateSubTaskForm
                    $('#updateSubTaskForm')[0].reset(); // Reset the form itself
                    // Clear all Select2 values (if using the Select2 plugin)
                    $('.select2').val(null).trigger('change'); // Reset Select2 fields
                    // Clear the textarea value
                    $('#note_task_status').val(''); // Clear textarea
                    // Hide certain sections (you can toggle them or reset visibility)
                    $('#Date_time_extended_status').hide(); // Hide extended date/time section
                    $('#task_completed_link').hide(); // Hide the completed link section
                    // Clear any alert message and hide the alert box
                    $('#alert_top_text').text(''); // Clear alert text
                    $('.required_alert').hide(); // Hide the alert box
                    // Reset all dynamic fields or any content inside modal
                    $('#hidden_subtask_id').val(''); // Reset hidden fields
                    $('#hidden_project_pid').val(''); // Reset hidden fields
                    // Optionally, reset labels or any dynamic text inside the modal
                    $('#Project_Notes_label').text('Completed Notes'); // Reset label text
                });
                $("#repeater-list").on("input", ".rpt_project_textbox", function() {
                    const $textarea = $(this);
                    const $charCount = $textarea.siblings(".char-count-overlay");
                    const remaining = 150 - $textarea.val().length;
                    $charCount.text("/" + remaining);
                });
                $(".rpt_project_textbox").each(function() {
                    const $rpttextarea = $(this);
                    const $rptcharCount = $rpttextarea.closest(".rpt_project_textarea").find(
                        ".Rpt_charCount");
                    // Update character count on input
                    $rpttextarea.on("input", function() {
                        const rpt_remaining = 150 - $rpttextarea.val().length;
                        $rptcharCount.text(rpt_remaining);
                    });
                });
                document.addEventListener("DOMContentLoaded", function() {
                    // Initialize Tagify
                    const input = document.getElementById('TagifyUserList');
                    const tagify = new Tagify(input);
                    // Add event listener for form submission
                    document.getElementById("ClientaddProject").addEventListener("submit", function(
                        event) {
                        // Check if at least one tag is selected
                        if (tagify.value.length === 0) {
                            event.preventDefault(); // Prevent form submission
                            alert("Please tag at least one user.");
                        }
                    });
                });
            });
            // ============ Move Job to Client Project Processing Here ====================assignned job to projec move code
            $(document).ready(function() {
                var url = window.location.href;

                var urlParams = new URLSearchParams(window.location.search);

                var share_id = urlParams.get('share_id');

                if (share_id) {




                    $.ajax({
                        url: "include/handlers/ClientJobHandler.php",
                        type: "POST",

                        data: {
                            id: share_id,
                            action: "EditJobFetchAll"
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data) {


                                $("#hid_job_id").val(data.id);
                                $("#name").val(data.job_name);
                                $("#client_id").val(data.client_id);
                                //$("#platform").val(data.job_type).trigger('change');
                                $("#service").val(data.service).trigger('change');
                                $("#linkurl").val(data.reference_link);
                                $("#upload_files").val(data.file_name);
                                $("#JobFileCount").val(data.file_count);
                                $("#date").val(data.final_date);
                                appendContentToEditor('#Job-full-editor', data.description);
                                $("#jobNo").val(data.job_no);
                                $("#JobFiles").val(data.file_name);



                                $('#JobFiles').prop('required', false); // To add agai

                                //$("#Jobchangestatus").val(data.status).trigger( 'change');

                                // Append content to editor


                                $('.client_periorty_form .form-check').removeClass('checked');
                                $('#regular').prop('checked',
                                    false); // Uncheck "Regular"
                                $('#rush').prop('checked', false); // Uncheck "Rush"


                                if (data.priority === "Regular") {
                                    $('#regular').prop('checked', true)
                                        .closest(
                                            '.form-check').addClass('checked');
                                } else if (data.priority === "Rush") {
                                    $('#rush').prop('checked', true)
                                        .closest('.form-check').addClass('checked');
                                }


                                //$("#submit_job_btn").val("Update job").text("Update");
                                $("#platform").val(data.job_type).trigger('change');
                            }
                        },
                        error: function(error) {
                            console.error("Error fetching project:", error);
                        },
                    });

                }



            });
            // ----------   Add Task Type Show PopUp   ----------   
            function Add_task_type(value) {
                if (value == "New_task_type") {
                    $('#Add_task_type_Modal').modal('show');
                }
            }
            //  -------    close_popUp function   ---------
            function close_popUp(id_of_popup) {
                let Id = '#'.concat("", id_of_popup);
                $(Id).modal('hide');
                $('.plateform_create').val(null).trigger('change');
            }
            // ============= Add & Submit Form Data to Send Handler Page =============
            $("#ClientaddProject").submit(async function(e) {
                e.preventDefault();
                // Initialize loader
                $('.event_trigger_loader').addClass('active');
                // Prepare FormData object
                let formData = new FormData();
                // Detect if updating or adding a new project
              
                let update_project_id = $("#hid_project_id").val();
             

                if (update_project_id) {
                    formData.append("project_id", update_project_id);
                }
                formData.append("action", $("#Project_submit").val());
                formData.append("name", $("#name").val());
                formData.append("platform", $("#platform").val());
                formData.append("linkurl", $("#linkurl").val());
                formData.append("client_id", $("#client_id").val());
                formData.append("client_priority", $("input[name='client_priority']:checked").val());
                formData.append("project_description", encodeURIComponent($('#full-editor .ql-editor')
                    .html()));
                formData.append("priority", $("input[name='customRadioTemp']:checked").val());
                formData.append("date", $("#date").val());
                formData.append("file_page_count", $("#JobFileCount").val());
                formData.append("upload_files", $("#upload_files").val());
                formData.append("TagifyUserList", $("#TagifyUserList").val());
                formData.append("ChangeStatus_Project", $("#Projectchangestatus").val());
                //formData.append("linkurl", $("#linkurl").val());
                // Validate required fields before submitting
                let allFieldsFilled = true;
                let missingFields = [];
                // Check if required fields are filled
                if (!$("#name").val()) {
                    allFieldsFilled = false;
                    missingFields.push("Project Name");
                }
                if (!$("#platform").val()) {
                    allFieldsFilled = false;
                    missingFields.push("Platform");
                }
                // if (!$("#linkurl").val()) {
                //     allFieldsFilled = false;
                //     missingFields.push("Link URL");
                // }
                if (!$('#full-editor .ql-editor').html()) {
                    allFieldsFilled = false;
                    missingFields.push("Project Description");
                }
                if (!$("input[name='customRadioTemp']:checked").val()) {
                    allFieldsFilled = false;
                    missingFields.push("Priority");
                }
                if (!$("#date").val()) {
                    allFieldsFilled = false;
                    missingFields.push("Final Date");
                }
                // if (!$("#FilePageCount").val()) {
                //     allFieldsFilled = false;
                //     missingFields.push("File Page Count");
                // }
                if (!$("#TagifyUserList").val()) {
                    allFieldsFilled = false;
                    missingFields.push("Tag Users");
                }
                // Check if repeater fields are filled
                $(".project_cloned_rpt").each(function(index) {
                    let empId = $(this).find(".repeater_emp_id").val();
                    let deadlineDate = $(this).find(".empdeadlinedate").val();
                    let deadlineTime = $(this).find(".empdeadlinetime").val();
                    let projectInfo = $(this).find(".rpt-full-editor .ql-editor")
                        .html();
                    if (!deadlineDate || !deadlineTime || !projectInfo) {
                        allFieldsFilled = false;
                        missingFields.push(
                            "specific tag user filled a mandatory fields ");
                    }
                });
                // If not all fields are filled, show an alert and stop form submission
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
                    $('.event_trigger_loader').removeClass('active'); // Remove loader
                    return; // Prevent form submission
                }
                // Append main project files
                let mainFiles = $("#WholeProjectFiles")[0] ? $("#WholeProjectFiles")[0].files : [];
                for (let i = 0; i < mainFiles.length; i++) {
                    formData.append(`files[${i}]`, mainFiles[i]);
                }
                // Process repeater fields
                $(".project_cloned_rpt").each(function(index) {
                    formData.append(`repeater[${index}][empId]`, $(this).find(
                        ".repeater_emp_id").val());
                    formData.append(`repeater[${index}][deadlineDate]`, $(this).find(
                        ".empdeadlinedate").val());
                    formData.append(`repeater[${index}][deadlineTime]`, $(this).find(
                        ".empdeadlinetime").val());
                    formData.append(`repeater[${index}][projectInfo]`,
                        encodeURIComponent($(this).find(
                            ".rpt-full-editor .ql-editor").html()));
                    // Append repeater files
                    let mainFiles = ($("#WholeProjectFiles")[0] && $("#WholeProjectFiles")[0]
                        .files) || [];
                    for (let i = 0; i < mainFiles.length; i++) {
                        formData.append(`files[${i}]`, mainFiles[i]);
                    }
                });
                // Send AJAX request
                $.ajax({
                    url: "include/handlers/ClientProjectHandler.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $("#Project_submit").prop("disabled", true).text(
                            update_project_id ? "Updating..." : "Submitting...");
                    },
                    success: function(response) {
                        try {
                            let res = JSON.parse(response);
                            if (res.status === "success") {
                                showModalWithParams(res.message, 'true');
                                // Optionally reload or redirect after success
                            } else {
                                alert("Error: " + res.message);
                            }
                        } catch (error) {
                            console.error("Invalid JSON response:", response);
                            alert("An error occurred. Please try again.");
                        }
                        setTimeout(function() {
                            window.location.href = 'clientProject.php';
                        }, 6000); // 2000 milliseconds = 2 seconds (adjust the delay as needed)
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", xhr.responseText);
                        alert("An error occurred while submitting the form.");
                    },
                    complete: function() {
                        $("#Project_submit").prop("disabled", false).text(
                            update_project_id ? "Update" : "Submit");
                        setTimeout(() => $('.event_trigger_loader').removeClass(
                            'active'), 1000);
                    }
                });
            });
              
                  function validateFile() {
                const fileInput = $('#WholeProjectFiles')[0];
                const fileError = $('#fileError');
                const allowedExtensions = /\.(jpg|jpeg|png|webp|pdf)$/i;
                const maxSizeInBytes = 2 * 1024 * 1024 * 1024; // ✅ 2GB

                // Clear previous errors
                fileError.hide().text('');

                const files = fileInput.files;
                if (!files.length) return true;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // ✅ Check file extension
                    if (!allowedExtensions.test(file.name)) {
                        fileError.text(
                            `"${file.name}" is not a valid file type. Allowed: .jpg, .jpeg, .png, .webp, .pdf.`);
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


                    // ✅ Check file size (2GB limit)
                    if (file.size > maxSizeInBytes) {
                        fileError.text(`"${file.name}" exceeds 2GB limit.`);
                        fileError.show();
                        fileInput.value = ''; // Clear input
                        return false;
                    }

                    // ✅ If image, check resolution
                    if (/\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = new Image();
                            img.onload = function() {
                                if (img.width !== 1080 || img.height !== 1080) {
                                    fileError.text(`"${file.name}" must be exactly 1080x1080 pixels.`);
                                    fileError.show();
                                    fileInput.value = ''; // Clear input
                                }
                            };
                            img.onerror = function() {
                                fileError.text(`Could not load "${file.name}".`);
                                fileError.show();
                                fileInput.value = '';
                            };
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                }

                return true;
            }
                  
            // ============= Add & Submit Form Data to Send Handler Page =============
            // ============ Move Job to Client Project Processing Here ====================
            document.getElementById('JobFileCount').addEventListener('input', function(event) {
                // Ensure the value is numeric and prevent invalid characters
                const value = event.target.value;
                // Allow only numbers and restrict input length to 2 digits
                if (!/^\d*$/.test(value)) {
                    event.target.setCustomValidity("Please enter a valid number.");
                } else if (value.length > 2) {
                    event.target.setCustomValidity("Please enter a number with no more than 2 digits.");
                } else {
                    event.target.setCustomValidity(""); // Clear the custom validation if valid
                }
            });
            // ============= Edit Button Push Request to Handler for Page fetch Project data  =============
            $(document).on("click", ".edit_client_project_btn", function(e) {
                e.preventDefault();
                //alert("dsfdsf");
                let projectId = $(this).data("id");
                // Scroll to the top of the page
                window.scrollTo({
                    top: 0, // Scroll to the top
                    left: 0, // No horizontal scroll
                    behavior: 'smooth' // Smooth scroll
                });
                $.ajax({
                    url: "include/handlers/ClientProjectHandler.php",
                    type: "POST",
                    data: {
                        project_id: projectId,
                        action: "GetEditedProjectDetails"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                          
                            // ✅ Populate Main Project Details
                            $("#hid_project_id").val(data.id);
                            $("#name").val(data.name);
                            $("#client_id").val(data.client_id);
                            $("#platform").val(data.type).trigger('change');
                            $("#upload_files").val(data.uploaded_file);
                            $("#linkurl").val(data.link);
                            $("#JobFileCount").val(data.pageCount);
                            $("#Projectchangestatus").val(data.project_status).trigger(
                                'change');
                            //$("#TagifyUserList").val(data.assignedTo).trigger('change');
                            // Convert assigneers to tagData array
                            const tagDataArray = convertToTagData(data.assignments);
                            addtagusersinTogify(tagDataArray);
                            // ✅ Clear and Append Content to the Quill Editor
                            appendContentToEditor('#full-editor', data.description);
                            //console.log(data.assignments);
                            $("#date").val(data.deadlineDate);
                            // ✅ Clear previous checked class in priority selection
                            $('.periorty_form .form-check').removeClass('checked');
                            // ✅ Set Priority Selection
                            switch (data.Projectpriority) {
                                case "Critical":
                                    $('#critical_job_radio').prop('checked', true).closest(
                                        '.form-check').addClass('checked');
                                    break;
                                case "High Priority":
                                    $('#high_periority_radio').prop('checked', true).closest(
                                        '.form-check').addClass('checked');
                                    break;
                                case "Medium Priority":
                                    $('#medium_periority_radio').prop('checked', true).closest(
                                        '.form-check').addClass('checked');
                                    break;
                                case "Low Priority":
                                    $('#low_periority_radio').prop('checked', true).closest(
                                        '.form-check').addClass('checked');
                                    break;
                            }

                            $('.client_periorty_form .form-check').removeClass('checked');
                            $('#regular').prop('checked',
                                false); // Uncheck "Regular"
                            $('#rush').prop('checked', false); // Uncheck "Rush"


                            if (data.client_priority === "Regular") {
                                $('#regular').prop('checked', true)
                                    .closest(
                                        '.form-check').addClass('checked');
                            } else if (data.client_priority === "Rush") {
                                $('#rush').prop('checked', true)
                                    .closest('.form-check').addClass('checked');
                            }


                            $("#Project_submit").val("UpdateProject").text("Update");
                            // ✅ Prepare an array for Tagify tags
                            let newTags = [];
                            if (Array.isArray(data.assignments) && data.assignments.length >
                                0) {
                                let repeaterList = $("#repeater-list");
                                // ✅ Clear previous repeater items before adding new ones
                                repeaterList.find(".project_emp_rpt").not(
                                        ".repeater-item-template")
                                    .remove();
                                data.assignments.forEach((assignment) => {
                                    let repeaterItem = $(".repeater-item-template")
                                        .clone()
                                        .removeClass("repeater-item-template").show();
                                    repeaterItem.addClass('project_cloned_rpt');
                                    // ✅ Initialize Flatpickr
                                    repeaterItem.find('.flatpickr_date_rpt').flatpickr({
                                        monthSelectorType: 'static',
                                        dateFormat: 'd-m-Y',
                                        minDate: "today"
                                    });
                                    repeaterItem.find('.flatpickr_time_rpt').flatpickr({
                                        enableTime: true,
                                        noCalendar: true
                                    });
                                    // ✅ Populate fields
                                    repeaterItem.find('.emp_name_readonly').val(
                                        assignment
                                        .name);
                                    repeaterItem.find('.repeater_emp_id').val(assignment
                                        .id);
                                    repeaterItem.find('.rpt-full-editor').val(assignment
                                        .info);
                                    repeaterItem.find('.empdeadlinedate').val(assignment
                                        .deadlineDate);
                                    repeaterItem.find('.empdeadlinetime').val(assignment
                                        .deadlineTime);
                                    // ✅ Append the cloned repeater item to the list
                                    repeaterList.append(repeaterItem);
                                    addedItems[assignment.id] = repeaterItem;
                                    // let remainingTime = calculateRemainingTime(assignment.deadlineDate, assignment.deadlineTime);
                                    // repeaterItem.find('.remaining-time').text(remainingTime);
                                });
                                // function calculateRemainingTime(deadlineDate, deadlineTime) {
                                //         if (!deadlineDate || !deadlineTime) {
                                //             return "Invalid deadline data";
                                //              }
                                //              let timeParts = deadlineTime.split(":");
                                //                 if (timeParts.length < 2) {
                                //                     return "Invalid Time Format";
                                //                 }
                                //                 let formattedTime = `${timeParts[0]}:${timeParts[1]}`; // Keep only HH:MM
                                //                 console.log("Formatted Time (No Seconds):", formattedTime);
                                //           // Combine date and time into a single Date object
                                //           let deadline = new Date(`${deadlineDate}T${formattedTime}`);
                                //           console.log("Parsed Deadline DateTime:", deadline);
                                //           // Get the current date and time
                                //           let now = new Date();
                                //            // Check if deadline is a valid date
                                //                     if (isNaN(deadline.getTime())) {
                                //                         return "Invalid Date Format";
                                //                     }
                                //           // Calculate the difference in milliseconds
                                //           let diff = deadline - now;
                                //           // Check if the deadline is in the future
                                //           if (diff <= 0) {
                                //               return "Deadline has passed";
                                //           }
                                //           // Convert the difference from milliseconds to days, hours, and minutes
                                //           let days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                //           let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                //           let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                //           // Return the formatted string
                                //           return `${days} Days ${hours} Hours ${minutes} Mins`;
                                //       }
                                // }
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching project:", error);
                    }
                });
            });
            // ============= Edit Button Push Request to Handler for Page fetch Project data  =============
            $(document).on("click", ".djslkfjdsa", function(e) {
                e.preventDefault();
                alert("dsfdsf");
            });
            // ============= Radio Button selected colour change function =============
            document.addEventListener("DOMContentLoaded", function() {
                const radioButtons = document.querySelectorAll(".form-check-input");
                radioButtons.forEach((radio) => {
                    radio.addEventListener("change", function() {
                        // Remove previous colors
                        document.querySelectorAll(".custom-option").forEach((el) => {
                            el.classList.remove("critical", "high", "medium",
                                "low");
                        });
                        // Apply a new class based on selection
                        if (this.checked) {
                            let parent = this.closest(".custom-option");
                            if (this.value === "Critical") parent.classList.add("critical");
                            if (this.value === "High Priority") parent.classList.add(
                                "high");
                            if (this.value === "Medium Priority") parent.classList.add(
                                "medium");
                            if (this.value === "Low Priority") parent.classList.add("low");
                            if (this.value === "Rush") parent.classList.add("critical");
                            if (this.value === "Regular") parent.classList.add("low");
                        }
                    });
                });
            });
            // =============Radio Button selected colour change function =============
            // Function to convert assigneers data into tagData for Tagify
            function convertToTagData(assigneers) {
                return assigneers.map(assignee => {
                    // Construct the tag data object
                    return {
                        value: assignee.id, // Unique identifier for the tag
                        name: assignee.name, // Name to be displayed on the tag
                        email: assignee.email || 'default@example.com', // Add an email if available
                        avatar: assignee.avatar || 'path/to/default-avatar.jpg' // Avatar URL (if available)
                    };
                });
            }

            // <! -------------- download files assign job -------------- >
            $(document).on('click', '.update_admin_jobss', function(e) {
                e.preventDefault();
                var jobId = $(this).data("id"); // Get job ID from button
                $('#JobhiddenId').val(jobId); // Set job ID in hidden input
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
                                    downloadLink =
                                    "#"; // Fallback if no file_path is provided
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
                            $('#update_admin_jobs_model12').modal('show');
                        } else {
                            $('#update_admin_jobs_model12').modal('show');
                            // echo("Error fetching job details.");
                        }
                    },
                    error: function() {
                        echo("An error occurred while fetching the job details.");
                    }
                });
            });
            // <! -------------- download files assign job -------------- >

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