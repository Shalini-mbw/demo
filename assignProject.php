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

$buttonText = isset($buttonText) ? $buttonText : 'Submit';

// <-- -----------------------EDIT DB DATA FUNCTION ADD GET DATA FOR FETCH INPUT FIELD  ----------------------------- -->

if (isset($_GET['id'])) {
  $id = $_GET['id'];

  // Prepare SQL query with JOIN and use a placeholder for the ProjectId
  $sql = "
      SELECT p.*, a.Name, a.DeadlineDate AS assignDeadlineDate, a.DeadlineTime AS assignDeadlineTime, a.Information 
      FROM project p
      LEFT JOIN assignproject a ON p.ProjectId = a.ProjectId
      WHERE p.ProjectId = ?
  ";

  // Prepare the statement
  $stmt = $conn->prepare($sql);

  // Bind the parameter
  $stmt->bind_param("s", $id); // Use "s" for string, as $id is a string

  // Execute the statement
  $stmt->execute();

  // Get the result
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Extract data from the result
    $id = $row['id'];
    $status = $row['ProjectStatus'];
    $name = $row['name'];
    $link = $row['linkurl'];
    $platform = $row['type'];
    $details = $row['details'];
    $Guid = $row['ProjectId'];
    $date = $row['deadlineDate'];
    $time = $row['deadlineTime'];
    $tagemployee = $row['assignedTo'];
    $hiddenassigned = $row['assignedBy'];


    $dateObj = new DateTime($date);
    $editDate = $dateObj->format('d-m-Y');


    // Split the assigned employees string into an array
    $assignedEmployees = explode(',', $tagemployee);

    // Initialize an array to store employee details
    $employeeDetails = [];

    // Prepare a statement to fetch details for each employee
    $employeeSql = "
        SELECT id, Name, DeadlineDate, DeadlineTime, Information 
        FROM assignproject WHERE ProjectId = ? AND Name = ? AND SubTaskStatus = 'Pending' ";
    $employeeStmt = $conn->prepare($employeeSql);

    foreach ($assignedEmployees as $employeeName) {
      // Trim any leading/trailing spaces
      $employeeName = trim($employeeName);

      // Bind the parameters for each employee
      $employeeStmt->bind_param("ss",  $Guid, $employeeName);
      $employeeStmt->execute();
      $employeeResult = $employeeStmt->get_result();

      if ($employeeResult->num_rows > 0) {
        // Fetch and store the employee details
        while ($employeeRow = $employeeResult->fetch_assoc()) {
          $timeRemaining = getTimeRemaining(
            $employeeRow['DeadlineDate'],
            $employeeRow['DeadlineTime']
          );
          $employeeRow['timeRemaining'] = $timeRemaining;
          if (isset($employeeRow['DeadlineDate']) && !empty($employeeRow['DeadlineDate'])) {
            try {
              // Create a DateTime object from the original date format
              $date = new DateTime($employeeRow['DeadlineDate']);
              // Format the date to 'DD/MM/YYYY'
              $employeeRow['DeadlineDate'] = $date->format('d-m-Y');
            } catch (Exception $e) {
              // Handle the exception if the date parsing fails
              $employeeRow['DeadlineDate'] = 'Invalid Date';
            }
          }
          $employeeDetails[] = $employeeRow;
        }
      }
    }



    $jsonData = json_encode($employeeDetails);

    // Create the response array with JSON data as a string
    $response = array('data' => json_decode($jsonData, true));

    // Encode the response array into JSON
    $json = json_encode($response, JSON_PRETTY_PRINT);

    // Define the path to the JSON file
    $file = 'assets/json/editProject.json';

    // Write the JSON data to the file
    if (file_put_contents($file, $json)) {
      // Data successfully written to file
      //echo "Data successfully written to $file";
    } else {
      // Failed to write data to file
      echo "<script>console.log('No results found $file for fetch Json');</script>";
    }

    //echo($jsonData);

    //echo '<script>document.addEventListener("DOMContentLoaded", function() {editTagEmployee("'.$jsonData.'");});</script>';
    $employeeStmt->close();
    $buttonText = 'Update';
  }
}

// <-- -----------------------EDIT DB DATA FUNCTION ADD GET DATA FOR FETCH INPUT FIELD  ----------------------------- -->


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

$sql = "
SELECT 
    p.id AS project_id,
    p.ProjectId As ProjectId,
    p.name AS project_name,
    p.type AS project_type,
    p.assignedTo AS project_assignedTo,
    p.assignedBy AS project_assignedBy,
    p.deadlineDate AS project_deadlineDate,
    p.deadlineTime AS project_deadlineTime,
    p.ProjectStatus AS ProjectStatus,
    p.CompletedDate AS CompletedDate,
    ap.id AS assignment_id,
    ap.Name AS assignment_name,
    ap.DeadlineDate AS assignment_deadlineDate,
    ap.DeadlineTime AS assignment_deadlineTime,
    ap.Information AS assignment_info,
    ap.SubTaskStatus AS SubTaskStatus,
    ap.SubtaskNote AS SubtaskNote,
    ap.CreateDateTime	 AS CreateDateTime,
    ap.completed_link	 AS completed_link  
    
FROM project p
INNER JOIN assignproject ap ON p.projectId = ap.projectId
WHERE p.assignedBy LIKE '%" . $conn->real_escape_string($JWT_adminName) . "%'
OR p.assignedTo LIKE '%" . $conn->real_escape_string($JWT_adminName) . "%'
ORDER BY p.id ASC, ap.id DESC
";

$result = $conn->query($sql);

$data = array();

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $project_id = $row['project_id'];

    // If the project_id does not exist in the data array, add it
    if (!isset($data[$project_id])) {
      $data[$project_id] = array(
        'id' => $project_id,
        'pid' => $row['ProjectId'],
        'name' => $row['project_name'],
        'type' => $row['project_type'],
        'assignedTo' => $row['project_assignedTo'],
        'assignedBy' => $row['project_assignedBy'],
        'deadlineDate' => $row['project_deadlineDate'],
        'deadlineTime' => $row['project_deadlineTime'],
        'ProjectStatus' => $row['ProjectStatus'],
        'CompletedDate' => $row['CompletedDate'],
        'assignments' => array()
      );
    }

    // If assignment exists, add it to the assignments array
    if ($row['assignment_id']) {
      $data[$project_id]['assignments'][] = array(
        'id' => $row['assignment_id'],
        'name' => $row['assignment_name'],
        'deadlineDate' => $row['assignment_deadlineDate'],
        'deadlineTime' => $row['assignment_deadlineTime'],
        'SubTaskStatus' => $row['SubTaskStatus'],
        'CreateDateTime' => $row['CreateDateTime'],
        'SubtaskNote' => $row['SubtaskNote'],
        'info' => $row['assignment_info'],
        'completed_link' => $row['completed_link']
      );
    }
  }
} else {
  // echo "No results found.";
}

// Prepare the final response with data wrapped in the 'data' key
$response = array('data' => array_values($data));
$json = json_encode($response, JSON_PRETTY_PRINT);

// Write to file
$file = 'assets/json/projects.json';

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
      <?php if ($AddNewProject === 'Enable') : ?>
        <!-- Estimate Form code Start-->
        <form id="addProject">
          <div class="d-flex justify-content-center">
            <div class="col-md-12">
              <div class="card mb-9">
                <div class="card-body">

                  <div class="row gy-5">
                    <!-- Left Column Start -->
                    <div class="col-md-6">
                      <input type="hidden" id="hiddenId"
                        value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">
                      <input type="hidden" id="hiddenstatus"
                        value="<?php echo empty($status) ? '' : htmlspecialchars($status); ?>">
                      <input type="hidden" id="hiddenGuid"
                        value="<?php echo empty($Guid) ? '' : htmlspecialchars($Guid); ?>">

                      <input type="hidden" id="hiddenassigned"
                        value="<?php echo empty($hiddenassigned) ? '' : htmlspecialchars($hiddenassigned); ?>">

                      <!-- Name and Phone Number Start -->
                      <div class="form-floating form-floating-outline mb-3">
                        <input type="text" required id="name" class="form-control"
                          value="<?php echo empty($name) ? '' : htmlspecialchars($name); ?>"
                          placeholder="MBW" />
                        <label for="name">Project Name *</label>
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

                      <div class="form-floating project_txtBox_main form-floating-outline ">
                        <textarea class="form-control project_details_txtBox" id="details" style="height: 150px;"
                          placeholder="Enter Project Details here" maxlength="300"
                          required><?php echo htmlspecialchars(empty($details) ? '' : $details); ?></textarea>
                        <label for="details">Project Details *</label>
                        <div id="charCount" class="char-count-overlay">/300</div>
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
                      <div class="form-floating form-floating-outline mb-3 mt-4">
                        <input required type="text" id="time"
                          value="<?php echo empty($time) ? '' : htmlspecialchars($time); ?>"
                          class="form-control flatpickr_time" placeholder="HH:MM"
                          readonly="readonly" />
                        <label for="time">Final Time *</label>
                      </div>
                      <!-- Date and Time End -->

                      <div class="form-floating form-floating-outline mb-3 mt-4">
                        <input type="url" id="linkurl" class="form-control"
                          value="<?php echo empty($link) ? '' : htmlspecialchars($link); ?>"
                          placeholder="https://example.com"
                          pattern="^(https?://)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}(/.*)?$" />
                        <label for="linkurl">Link</label>
                      </div>

                      <!-- Details End -->
                    </div>
                    <!-- Right Column End -->

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
                                      <input type="text" class="form-control invoice-item-price mb-5 emp_name_readonly"
                                        readonly />
                                      <!-- ---- This Hidden field is for Repeater concept------ -->
                                      <input type="hidden" class="repeater_emp_name">
                                      <div class="rpt_project_textarea">
                                        <textarea class="form-control mt-5 rpt_project_textbox" rows="2"
                                          placeholder="Project Assign Information for Specific Employee *"></textarea>
                                        <div class="char-count-overlay Rpt_charCount">/150</div>
                                      </div>
                                    </div>
                                    <div class="col-md-3 col-12 mb-md-0 mb-4">
                                      <p class="h6 repeater-title">Final Date *</p>
                                      <input type="text" placeholder="DD-MM-YYYY"
                                        class="form-control flatpickr_date_rpt invoice-item-price empdeadlinedate  mb-5" />
                                      <div
                                        style=" background: #1265A629; padding: 5px 13px; border-radius: 7px;">
                                        <p class="h6 repeater-title">Total Available
                                          Time Form Now</p>
                                        <p
                                          class="mb-0 mt-2 text-heading remaining-time">
                                          0 Days 00 Hours 00 Mins</p>
                                      </div>
                                    </div>
                                    <div class="col-md-2 col-12 mb-md-0 mb-4">
                                      <p class="h6 repeater-title">Final Time *</p>
                                      <input type="text" readonly="readonly"
                                        placeholder="HH:MM"
                                        class="form-control flatpickr_time_rpt empdeadlinetime invoice-item-qty" />
                                    </div>
                                  </div>
                                  <div
                                    class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                    <i
                                      class="ri-close-line cursor-pointer remove-repeater-item"></i>
                                  </div>
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

                    <!-- Tag Employee Start -->
                    <div class="col-md-6 ">

                      <?php

                      $sql1 = "SELECT name FROM employee WHERE  isenable = 1";
                      $result = $conn->query($sql1);

                      $selectedEmployees = [];
                      if (isset($tagemployee) && !empty($tagemployee)) {
                        // Trim whitespace and ensure names are split correctly
                        $selectedEmployees = array_map('trim', explode(',', $tagemployee));
                      }

                      echo '<div class="form-floating form-floating-outline form-floating-select2 mt-5">';
                      echo '<div class="position-relative">';
                      echo '<select required id="selectempoyees" class="select2 form-select" multiple>';

                      // Generate the options
                      if ($result->num_rows > 0) {
                        $AllNames = [];
                        while ($row = $result->fetch_assoc()) {
                          $name = htmlspecialchars($row['name']);
                          $AllNames[] = $name;
                        }
                        sort($AllNames);

                        foreach ($AllNames as $name) {
                          $isSelected = in_array($name, $selectedEmployees) ? ' selected' : '';
                          echo '<option value="' . $name . '"' . $isSelected . '>' . $name . '</option>';
                        }
                      } else {
                        echo '<option value="">No User found.</option>';
                      }

                      echo '</select>';
                      echo '</div>';
                      echo '<label for="selectempoyees">Tag User *</label>';
                      echo '</div>';

                      // Close the connection
                      $conn->close();
                      ?>

                    </div>

                    <!-- Tag Employee End -->



                    <!-- Buttons -->
                    <div class="col-12 d-flex justify-content-end mt-4">
                      <button type="button" id="cancelbtn"
                        class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                      <button type="submit" id="submit"
                        value="<?php echo ($buttonText === 'Update') ? 'UpdateProject' : 'AddProject'; ?>"
                        class="btn btn-primary">
                        <?php echo htmlspecialchars($buttonText); ?>
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
                            data-status="Completed"><i class="fas fa-circle"
                              style="color: green;"></i> Completed</a></li>
                        <li><a class="dropdown-item dropdown-item-table-status" href="#"
                            data-status="Pending"><i class="fas fa-circle"
                              style="color: red;"></i> Pending</a></li>
                      </ul>
                    </div>

                    <table class="datatables-Project table">
                      <thead>
                        <tr>
                          <th></th>
                          <th>S.No</th>
                          <th>Project Name</th>
                          <th>Project Type</th>
                          <th>Assigned To</th>
                          <th>Assigned By</th>
                          <th>Deadline Date</th>
                          <th>Status</th>
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






      <!-- / Content -->
      <?php include('include/footer.php'); ?>


      <!-- --Data tables-- -->
      <script src="assets/js/project.js"></script>
      <script>
        var $template = $('.repeater-item-template');
        var addedItems = {};

        $(document).ready(function() {
          var queryParams = new URLSearchParams(window.location.search);

          // Check if the 'id' parameter is present in the URL
          if (queryParams.has('id')) {
            json_data();
          }

          $('#selectempoyees').on('select2:select', function(e) {
            var selectedItem = e.params.data.text;
            var selectedValue = e.params.data.id;

            if (!addedItems[selectedValue]) {
              var $newItem = $template.clone().removeClass('repeater-item-template').show();
              $newItem.addClass('project_cloned_rpt');
              $newItem.find('.emp_name_readonly').val(selectedItem);
              $newItem.find('.repeater_emp_name').val(selectedItem);
              // Bind DatePicker Start
              $newItem.find('.flatpickr_date_rpt').flatpickr({
                monthSelectorType: 'static',
                dateFormat: 'd-m-Y',
                minDate: "today"
              });
              $newItem.find('.flatpickr_time_rpt').flatpickr({
                enableTime: true,
                noCalendar: true
              });
              $('.repeater-list').append($newItem);

              // Mark item as added
              addedItems[selectedValue] = $newItem;
            }
          });

          $('#selectempoyees').on('select2:unselect', function(e) {
            var unselectedValue = e.params.data.id;

            if (addedItems[unselectedValue]) {
              addedItems[unselectedValue].remove();
              delete addedItems[unselectedValue];
            }
          });

          $(document).on('click', '.remove-repeater-item', function() {
            var $itemToRemove = $(this).closest('.repeater-wrapper');
            var itemValue = $itemToRemove.find('.repeater_emp_name').val();

            // Remove from Select2
            $('#selectempoyees').find('option[value="' + itemValue + '"]').prop('selected',
              false).trigger('change');
            addedItems[itemValue].remove();
            delete addedItems[itemValue];

            // Remove from DOM
            $itemToRemove.remove();
          });



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
            $('#Completed_links_ATag').html("");

            var All_Notes = $(this).data('notes');

            const Exploded_Notes = All_Notes.split("_|_");
            //OutPut: -- > [name, Info, Date, TIme, Notes, Link]

            var Links = Exploded_Notes[5];
            var Notes = Exploded_Notes[4];

            if (Notes) {
              var Name = Exploded_Notes[0];
              var Info = Exploded_Notes[1];
              var Date = Exploded_Notes[2];
              var Time = Exploded_Notes[3];

              var htmlTable = `
                  <table class="table" style="text-align: left;">
                      <tbody>                      
                          <tr data-dt-row="0" data-dt-column="2">
                              <td>Employee Name</td> 
                              <td>
                                  <div class="d-flex flex-column">
                                      <div class="text-heading"><span class="fw-medium text-truncate">${Name}</span></div>
                                  </div>
                              </td>
                          </tr>
                          <tr data-dt-row="0" data-dt-column="3">
                              <td>Task Info</td>
                              <td><span class="text-heading">${Info}</span></td>
                          </tr>
                          <tr data-dt-row="0" data-dt-column="4">
                              <td>Final Date</td>
                              <td><span id="cmp_f_address">${Date}</span></td>
                          </tr>
                          <tr id="last_row_pop_tr" data-dt-row="0" data-dt-column="5">
                              <td>Final Time</td> 
                              <td>
                                  <div class="d-flex flex-column mb-2">${Time}</div>
                              </td>
                          </tr>
                          <tr id="last_row_pop_tr" data-dt-row="0" data-dt-column="5">
                              <td>Final Notes</td> 
                              <td>
                                  <div class="d-flex flex-column mb-2">${Notes}</div>
                              </td>
                          </tr>
                      </tbody>
                  </table>`;
              $('#view_notes_para').append(htmlTable);
            } else {
              $('#view_notes_para').text("This Subtask don't have any notes.");
            }

            if (Links) {
              var A_tag =
                `<a href="${Links}" target="_blank">View Completed File or Reference Links</a>`;
              $('#Completed_links_ATag').show();
              $('#Completed_links_ATag').append(A_tag);
            } else {
              $('#Completed_links_ATag').hide();
            }


            $('#Project_sts_Notes').modal('show');

          });

          // <! -------------- View Notes for Any One -------------- >


          $('.Subtask_Status_select').on('select2:select', function(e) {
            var selectedValue = e.params.data.id;

            //Pending
            if (selectedValue == "Pending") {
              $('#note_task_status_parent').hide();
              $('#task_completed_link').hide();
              $('#Date_time_extended_status').hide();
            }
            // InProgress 
            else if (selectedValue == "InProgress") {
              $('#Date_time_extended_status').hide();
              $('#task_completed_link').hide();
              $('#note_task_status_parent').show();
              $('#Project_Notes_label').text("InProgress Notes *");
            }
            // Extended  
            else if (selectedValue == "Extended") {

              $('#note_task_status_parent').show();
              $('#Project_Notes_label').text("Extended Reasons *");
              $('#task_completed_link').hide();
              $('#Date_time_extended_status').show();
            }
            // Completed 
            else {

              $('#note_task_status_parent').show();
              $('#Project_Notes_label').text("Completed Notes *");
              $('#task_completed_link').show();
              $('#Date_time_extended_status').hide();
            }
          });


          // <! -------------- Update Sub Task Status Start-------------- >

          $('#updateSubTaskForm').on('submit', function(e) {
            e.preventDefault();

            var Subtask_Status_select = $('.Subtask_Status_select').val().trim();

            if (Subtask_Status_select == "Pending") {
              var hiddenId = $('#hidden_subtask_id').val();
              var Project_Pid = $('#hidden_project_pid').val();
              UpdateTaskStatusToPending(Subtask_Status_select, hiddenId, Project_Pid);

              $('#Project_sts_change').modal('hide');
            } else if (Subtask_Status_select == "InProgress") {
              var hiddenId = $('#hidden_subtask_id').val();
              var Project_Pid = $('#hidden_project_pid').val();
              var note_task_status = $('#note_task_status').val().trim();
              UpdateTaskStatusToInProgress(Subtask_Status_select, note_task_status, hiddenId,
                Project_Pid);

              $('#Project_sts_change').modal('hide');
            }

            //Extended Precessing
            else if (Subtask_Status_select == "Extended") {

              $('#Date_time_extended_status').show();

              var Next_Deadline_date = $('.Next_Deadline_date').val().trim();
              var Next_Deadline_time = $('.Next_Deadline_time').val().trim();
              var note_task_status = $('#note_task_status').val().trim();
              var hiddenId = $('#hidden_subtask_id').val();
              var Project_Pid = $('#hidden_project_pid').val();


              if (Next_Deadline_date && Next_Deadline_time && note_task_status) {

                // Combine date and time into a single string
                // Split the date string into parts
                var dateParts = Next_Deadline_date.split('-'); // ["03", "10", "2024"]
                var day = dateParts[0];
                var month = dateParts[1] - 1; // Months are 0-indexed in JS
                var year = dateParts[2];

                // Combine date and time into a single Date object
                var selectedDateTime = new Date(year, month, day, ...Next_Deadline_time
                  .split(':').map(Number));
                var currentDateTime = new Date();

                if (selectedDateTime < currentDateTime) {
                  $('.required_alert').show();
                  $('#alert_top_text').text(
                    'The selected date and time must be after the current date and time.'
                  );
                } else {
                  UpdateTaskStatusToExtended(Subtask_Status_select, Next_Deadline_date,
                    Next_Deadline_time, note_task_status, hiddenId, Project_Pid);
                  $('#Project_sts_change').modal('hide');
                }
              } else {
                $('.required_alert').show();
                $('#alert_top_text').text(
                  'In Extended Status all input fields are mandatory..!');
                setTimeout(function() {
                  $('.required_alert').hide();
                }, 4000);
              }

            }

            //Completed Precessing
            else {
              var completed_link_input = $('.completed_link_input').val().trim();
              var note_task_status = $('#note_task_status').val().trim();
              var hiddenId = $('#hidden_subtask_id').val();
              var Project_Pid = $('#hidden_project_pid').val();
              if (completed_link_input) {
                UpdateTaskStatusToCompleted(Subtask_Status_select, note_task_status,
                  completed_link_input, hiddenId, Project_Pid);
                $('#Project_sts_change').modal('hide');
              } else {
                $('.required_alert').show();
                $('#alert_top_text').text(
                  'In Completed Status Completed Link input field is mandatory..!');
                setTimeout(function() {
                  $('.required_alert').hide();
                }, 4000);
              }

              $('.Date_time_extended_status').hide();
            }

          });


          function UpdateTaskStatusToExtended(StatusText, Next_Deadline_date, Next_Deadline_time,
            NotesText, SubTaskId, Project_Pid) {

            //Event Preloader
            $('.event_trigger_loader').addClass('active');

            const message = JSON.stringify({
              action: 'UpdateTaskStatusToExtended',
              Status: StatusText,
              ExtDate: Next_Deadline_date,
              ExtTime: Next_Deadline_time,
              Notes: NotesText,
              ProjectId: Project_Pid,
              TaskId: SubTaskId
            });
            // Send a POST request to the PHP script
            fetch('include/handlers/update_subtask.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: message,
              })
              .then(response => response.json())
              .then(data => {

                //Remove the Preloader
                setTimeout(function() {
                  $('.event_trigger_loader').removeClass('active');
                }, 1000);

                if (data.status === 'Update_Subtask_Success') {
                  showModalWithParams(`${data.message}`, 'true');
                } else if (data.error) {
                  showModalWithParams(`${data.error}`, 'false');
                }
              })
              .catch(error => {

                //Remove the Preloader
                setTimeout(function() {
                  $('.event_trigger_loader').removeClass('active');
                }, 1000);

                showModalWithParams(`An error occurred: ${error}`, 'false');
              });
          }


          // <! -------------- Update Sub Task Status End-------------- >


          function json_data() {
              $.ajax({
                  url: 'include/handlers/ServeJson.php', // PHP script to serve JSON data
                  data: { file: 'editProject' }, // Pass the file name as a parameter
                  type: 'GET',
                  dataType: 'json', // Specify that the data returned will be JSON
                  success: function(all_data) {
                      $.each(all_data.data, function(index, item) {
                          if (!addedItems[item.Name]) {
                              var $newItem = $template.clone().removeClass(
                                  'repeater-item-template').show();
                              $newItem.find('.emp_name_readonly').val(item.Name);
                              $newItem.find('.repeater_emp_name').val(item.Name);
                              $newItem.find('.empdeadlinedate').val(item.DeadlineDate);
                              $newItem.find('.empdeadlinetime').val(item.DeadlineTime);
                              $newItem.find('textarea').val(item.Information);

                              // Bind DatePicker Start
                              $newItem.find('.flatpickr_date_rpt').flatpickr({
                                  monthSelectorType: 'static',
                                  dateFormat: 'd-m-Y',
                                  minDate: "today"
                              });
                              $newItem.find('.flatpickr_time_rpt').flatpickr({
                                  enableTime: true,
                                  noCalendar: true
                              });
                              // Bind DatePicker End

                              // Calculate and display remaining time
                              calculateAndDisplayTimeRemaining($newItem, item.DeadlineDate,
                                  item.DeadlineTime);

                              $('.repeater-list').append($newItem);

                              // Mark item as added
                              addedItems[item.Name] = $newItem;
                          }
                      });
                  },
                  error: function(xhr, status, error) {
                      console.error("Error fetching data:", error);
                  }
              });
          }


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
            const $rptcharCount = $rpttextarea.closest(".rpt_project_textarea").find(".Rpt_charCount");

            // Update character count on input
            $rpttextarea.on("input", function() {
              const rpt_remaining = 150 - $rpttextarea.val().length;
              $rptcharCount.text(rpt_remaining);
            });
          });




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
      </script>