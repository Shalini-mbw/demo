<?php include('include/head.php'); ?>

<?php require 'data/dbconfig.php';

$AddNewSaleTask = '';
$TaskReply = '';
$pid =  $JWT_userID;
$permissionsql = "SELECT * FROM permissions WHERE userID='$pid' ";
$resultPermission = $conn->query($permissionsql);
if ($resultPermission->num_rows > 0) {
  $row = $resultPermission->fetch_assoc();
  $AddNewSaleTask = htmlspecialchars($row['AddNewSaleTask']);
  $TaskReply = htmlspecialchars($row['TaskReply']);
}


if ($TaskReply === 'Enable' && isset($_GET['id'])) {
  $AddNewSaleTask = 'Enable'; // Force Enable if conditions are met
}


$buttonText = isset($buttonText) ? $buttonText : 'Submit';
if (isset($_GET['id'])) {
  $id = $_GET['id'];
  $sql = "SELECT * FROM event WHERE task_id='$id' ORDER BY id DESC";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $id = $row['id'];
      $currentStatus = $row['status'];
      $name = $row['name'];
      $phone = $row['phone'];
      $platform = $row['platform'];
      $details = $row['details'];
      $hemail = $row['assignedBy'];
      $date = $row['date'];
      $time = $row["time"];
      $tagemployee = $row["tagemployee"];
      $status = $row["customertype"];
      $hguid =  $row["task_id"];
      $buttonText = 'Update';

      $dateObj = new DateTime($date);
      $editDate = $dateObj->format('d-m-Y');
    }
  }
}

$sql = "
SELECT 
    event.id AS event_id,
    event.name,
    event.phone,
    event.platform,
    event.date,
    event.time,
    event.assignedBy,
    event.status,
    event.task_id,
    TRIM(event.task_id) AS task_id,
    task_descriptions.id AS tid,
    TRIM(task_descriptions.taskid) AS taskid,
    task_descriptions.details AS task_details,
    task_descriptions.status AS task_status,
    task_descriptions.createdon AS task_createdon,
    task_descriptions.addedBy AS task_addedBy
FROM event
LEFT JOIN task_descriptions ON TRIM(event.task_id) = TRIM(task_descriptions.taskid)
WHERE event.tagemployee LIKE '%" . $conn->real_escape_string($JWT_adminName) . "%'
ORDER BY 
    event.id ASC,
    task_descriptions.id DESC
";

$result = $conn->query($sql);

// Prepare an array to hold the rows
$data = array();

// Fetch rows and organize them into parent and child structure
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Debugging line to print each row
    //  print_r($row); 

    $event_id = $row['event_id'];


    if (!isset($data[$event_id])) {
      $data[$event_id] = array(
        'id' => $event_id,
        'name' => $row['name'],
        'phone' => $row['phone'],
        'platform' => $row['platform'],
        'date' => $row['date'],
        'time' => $row['time'],
        'status' => $row['status'],
        'task_id' => $row['task_id'],
        'tasks' => array()
      );
    }

    // If task_id exists, add it to the tasks array
    if ($row['tid']) {
      $data[$event_id]['tasks'][] = array(
        'task_id' => $row['tid'],
        'task_details' => $row['task_details'],
        'task_status' => $row['task_status'],
        'task_createdon' => $row['task_createdon'],
        'task_addedBy' => $row['task_addedBy'],
      );
    }
  }
} else {
  echo "<script>console.log('No results found for fetch Json');</script>";
}

// Encode the data as JSON
$response = array('data' => array_values($data));
$json = json_encode($response, JSON_PRETTY_PRINT);

$file = 'assets/json/assign_data.json';

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

      <!-- Estimate Form code Start-->
      <?php if ($AddNewSaleTask === 'Enable') : ?>
        <form id="eventForm">
          <div class="d-flex justify-content-center">
            <div class="col-md-12">
              <div class="card mb-9">
                <div class="card-body">
                  <!-- -------Alert For Validation------ -->
                  <div class="row" id="SameDateAlert" style="display:none;">
                    <div class="col-12">
                      <div class="alert alert-danger" role="alert">
                        <p id="Same_Date_alert_para"></p>
                      </div>
                    </div>
                  </div>
                  <!-- -------Alert For Validation------ -->
                  <div class="row gy-5">
                    <!-- Left Column Start -->
                    <div class="col-md-6">
                      <input type="hidden" id="hiddenId" value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">
                      <input type="hidden" id="hiddenEmail" value="<?php echo empty($hemail) ? '' : htmlspecialchars($hemail); ?>">
                      <input type="hidden" id="hguid" value="<?php echo empty($hguid) ? '' : htmlspecialchars($hguid); ?>">
                      <!-- <div class="row mb-3"> -->


                      <!-- Name and Phone Number Start -->
                      <div class="form-floating form-floating-outline mb-3">
                        <input type="text" id="name" class="form-control"
                          value="<?php echo empty($name) ? '' : htmlspecialchars($name); ?>"
                          placeholder="John Doe" required maxlength="40" pattern="[A-Za-z\s]+"
                          title="Only letters and spaces are allowed" />
                        <label for="name">Customer name* </label>
                      </div>

                      <div class="form-floating form-floating-outline mb-5 mt-9" id="status">
                        <select id="customertype" class="select2 form-select customertype" data-allow-clear="true" required>
                          <option value="">Select</option>
                          <option value="New" <?php echo (isset($status) && $status === 'New') ? 'selected' : ''; ?>>New</option>
                          <option value="Existing" <?php echo (isset($status) && $status === 'Existing') ? 'selected' : ''; ?>>Existing</option>
                        </select>
                        <label for="customertype">Customer Type *</label>
                      </div>

                      <div class="form-floating form-floating-outline mb-3 mt-4">
                        <input type="text" id="phone" class="form-control phone-mask"
                          value="<?php echo empty($phone) ? '' : htmlspecialchars($phone); ?>"
                          placeholder="658 799 8941" aria-label="658 799 8941" required
                          maxlength="10" pattern="\d{10}"
                          title="Phone number must be 10 digits." />
                        <label for="phone">Phone No *</label>
                      </div>
                      <!-- Name and Phone Number End -->
                      <?php

                      $sql1 = "SELECT type FROM task_type";
                      $result = $conn->query($sql1);
                      $platform = isset($platform) ? $platform : '';
                      echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                      echo ' <select id="platform" name="platform" class="select2 form-select task_type_add" data-allow-clear="true" required>';
                      echo ' <option value="">Select</option>';
                      if ($AddDepartment === 'Enable') {
                        echo ' <option value="New_task_type">Create New Type ➕ </option>';
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
                      // echo ' <option value="Others">Others</option>';
                      echo '  </select>';
                      echo '   <label for="collapsible-state">Appointment Type *</label>';

                      echo ' </div>';
                      ?>

                      <!-- <div class="form-floating form-floating-outline mb-4 mt-11 hided_other_task_type">
                        <input type="text" id="other_type" class="form-control" value="" required
                          placeholder="Task Name" />
                        <label for="other_type">Other Appointments *</label>
                      </div> -->


                      <div class="row mt-3">
                        <div class="col-md mb-md-0 mb-5">
                          <div class="form-check custom-option customRadioIcon1 custom-option-icon <?php echo (isset($currentStatus) && $currentStatus === 'Pending') ? 'checked' : ''; ?>">
                            <label class="form-check-label custom-option-content" for="customRadioIcon1">
                              <span class="custom-option-body">
                                <i class="ri-rocket-line"></i>
                                <span class="custom-option-title mb-2">Pending</span>
                                <small>In Progress, Ongoing, Under Review</small>
                              </span>
                              <input name="customRadioIcon-01" required class="form-check-input" type="radio" value="Pending" id="customRadioIcon1" <?php echo (isset($currentStatus) && $currentStatus === 'Pending') ? 'checked' : 'checked'; ?>>
                            </label>
                          </div>
                        </div>
                        <div class="col-md mb-md-0 mb-5">
                          <div class="form-check custom-option custom-option-icon <?php echo (isset($currentStatus) && $currentStatus === 'Follow Up') ? 'checked' : ''; ?>">
                            <label class="form-check-label custom-option-content" for="customRadioIcon2">
                              <span class="custom-option-body">
                                <i class="ri-user-follow-line"></i>
                                <span class="custom-option-title mb-2">Follow Up</span>
                                <small> Timely, Important, Check-in </small>
                              </span>
                              <input name="customRadioIcon-01" class="form-check-input" type="radio" value="Follow Up" id="customRadioIcon2" <?php echo (isset($currentStatus) && $currentStatus === 'Follow Up') ? 'checked' : ''; ?>>
                            </label>
                          </div>
                        </div>
                        <div class="col-md">
                          <div class="form-check custom-option custom-option-icon <?php echo (isset($currentStatus) && $currentStatus === 'Completed') ? 'checked' : ''; ?>">
                            <label class="form-check-label custom-option-content" for="customRadioIcon3">
                              <span class="custom-option-body">
                                <i class="ri-vip-crown-line"></i>
                                <span class="custom-option-title mb-2">Completed</span>
                                <small>Success, Closed, Fulfilled</small>
                              </span>
                              <input name="customRadioIcon-01" class="form-check-input" type="radio" value="Completed" id="customRadioIcon3" <?php echo (isset($currentStatus) && $currentStatus === 'Completed') ? 'checked' : ''; ?>>
                            </label>
                          </div>
                        </div>
                        <div class="col-md">
                          <div class="form-check custom-option custom-option-icon <?php echo (isset($currentStatus) && $currentStatus === 'Not Interested') ? 'checked' : ''; ?>">
                            <label class="form-check-label custom-option-content" for="customRadioIcon4">
                              <span class="custom-option-body">
                                <i class="ri-user-unfollow-line"></i>
                                <span class="custom-option-title mb-2">Not Interested</span>
                                <small>Declined, No, Thanks</small>
                              </span>
                              <input name="customRadioIcon-01" class="form-check-input" type="radio" value="Not Interested" id="customRadioIcon4" <?php echo (isset($currentStatus) &&  $currentStatus === 'Not Interested') ? 'checked' : ''; ?>>
                            </label>
                          </div>
                        </div>
                      </div>

                      <!-- Left Column End -->
                    </div>


                    <!-- Right Column Start -->
                    <div class="col-md-6 ">
                      <!-- Date and Time Start -->
                      <div class="form-floating form-floating-outline mb-3">
                        <input type="text" style="color: #393939;"
                          value="<?php echo empty($editDate) ? '' : htmlspecialchars($editDate); ?>" id="date"
                          class="form-control flatpickr_past_10_date" placeholder="DD-MM-YYYY" readonly="readonly" />
                        <label for="date">Date *</label>
                      </div>
                      <div class="form-floating form-floating-outline mb-4">
                        <input type="text" id="time" value="<?php echo empty($time) ? '' : htmlspecialchars($time); ?>"
                          class="form-control flatpickr_time" placeholder="HH:MM" readonly="readonly" placeholder="HH:MM" />
                        <label for="time">Time *</label>
                      </div>
                      <!-- Date and Time End -->
                      <!-- Details Start -->
                      <div class="form-floating sales_txtBox_main form-floating-outline">
                        <textarea class="form-control sales_details_txtBox" id="details" style="height: 150px;" maxlength="200"
                          placeholder="Enter Description here" required><?php echo htmlspecialchars(empty($details) ? '' : $details); ?></textarea>
                        <div id="Appoinment_charCount" class="char-count-overlay">/200</div>
                        <label for="details">Appointment Description *</label>
                      </div>
                      <?php

                      $sql1 = "SELECT name FROM employee WHERE isenable = 1";
                      $result = $conn->query($sql1);

                      $selectedEmployees = [];
                      if (isset($tagemployee) && !empty($tagemployee)) {
                        // Trim whitespace and ensure names are split correctly
                        $selectedEmployees = array_map('trim', explode(',', $tagemployee));
                      }

                      echo '<div class="form-floating form-floating-outline form-floating-select2 mt-6">';
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

                      <!-- Details End -->
                    </div>

                    <!-- Right Column End -->
                  </div>

                  <!-- Buttons -->
                  <div class="col-12 d-flex justify-content-end mt-4">
                    <button type="button" id="cancelbtn" class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                    <button type="submit" id="submit" value="<?php echo ($buttonText === 'Update') ? '2' : '1'; ?>"
                      class="btn btn-primary">
                      <?php echo htmlspecialchars($buttonText); ?>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Estimate Form code End-->
        </form>
      <?php endif; ?>


      <?php if ($TaskReply === 'Enable') : ?>
        <!-- -----Data Table assignTask Start------ -->
        <div class="mt-5">
          <!-- Data Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="card-datatable table-responsive">

                    <div class="dropdown">
                      <button class="btn btn-primary dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        All Status
                      </button>
                      <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                        <li><a class="dropdown-item Sales-appointmet-status" href="#" data-status="All"><i class="fas fa-circle" style="color: #000;"></i> All Status</a></li>
                        <li><a class="dropdown-item Sales-appointmet-status" href="#" data-status="Pending"><i class="fas fa-circle" style="color: orange;"></i> Pending</a></li>
                        <li><a class="dropdown-item Sales-appointmet-status" href="#" data-status="Follow Up"><i class="fas fa-circle" style="color: yellow;"></i> Follow Up</a></li>
                        <li><a class="dropdown-item Sales-appointmet-status" href="#" data-status="Completed"><i class="fas fa-circle" style="color: green;"></i> Completed</a></li>
                        <li><a class="dropdown-item Sales-appointmet-status" href="#" data-status="Not Interested"><i class="fas fa-circle" style="color: red;"></i> Not Intrested</a></li>
                      </ul>
                    </div>

                    <table class="datatables-customers table">
                      <thead>
                        <tr>
                          <th></th>
                          <th>S.No</th>
                          <th>Full Name</th>
                          <th>Phone Number</th>
                          <th>Platform</th>
                          <th>Date</th>
                          <th>Time</th>
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
        </div>
      <?php endif; ?>



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
                    <h6 class="text-center">Add Appointment Type </h6>
                    <hr class="mt-0" />
                  </div>
                </div>

                <div class="row mt-5">
                  <div class="col-12">
                    <div class="col-md-12 mb-4">
                      <div class="form-floating form-floating-outline">
                        <input type="hidden" id="hiddenId" value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">


                        <div class="form-floating form-floating-outline mb-3">
                          <input type="text" name="name_task_type" id="name_task_type"
                            value="" placeholder="Digital Marketing"
                            class="form-control" required />
                          <label for="name_dep">Appointment Type Name</label>
                        </div>
                        <!-- <label for="task">Web design</label> -->
                      </div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12 d-flex justify-content-center mt-12">
                      <button type="reset" class="btn btn-outline-secondary me-4 waves-effect" onclick="close_popUp('Add_task_type_Modal');">Cancel</button>
                      <button type="submit" id="Add_task_type_submit" value="Addtask" class="btn btn-primary">Submit</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!--Add New Task Type Card Modal -->



      <script>
        // Function to get query parameters from the URL
        function getQueryParam(param) {
          const urlParams = new URLSearchParams(window.location.search);
          return urlParams.get(param);
        }

        window.onload = function() {
          // Get the query parameter 'id'
          const idParam = getQueryParam('id');

          // Get references to the divs and the radio button
          //const existingDiv = document.getElementById('existing');
          const statusDiv = document.getElementById('status');
          //const radioButton = document.getElementById('customRadioTemp2');

          // Show the divs if the 'id' query parameter is present
          if (idParam) {
            // existingDiv.style.display = 'block';
            statusDiv.style.display = 'block';

            // Check the radio button
            // if (radioButton) {
            //   radioButton.checked = true;
            // }
          }
        };


        $(document).ready(function() {

          $('.task_type_add').on('select2:select', function(e) {
            var selectedValue = e.params.data.id;
            if (selectedValue == "Others") {
              $('.hided_other_task_type').addClass('show');
            } else if (selectedValue == "New_task_type") {
              //Open the pop up for add new Task type
              $('#Add_task_type_Modal').modal('show');
            } else {
              $('.hided_other_task_type').removeClass('show');
            }
          });




          document.getElementById('name').addEventListener('input', function() {
            // Check if the input length exceeds 50 characters
            const nameInput = document.getElementById('name');
            if (nameInput.value.length > 50) {
              //alert("Customer name cannot exceed 50 characters.");
              // Optionally trim the text to 50 characters
              nameInput.value = nameInput.value.substring(0, 50);
            }
          });



          // <! -------------- Once I Selected New Default Processing Status Selected  -------------- >

          $('.customertype').on('select2:select', function(e) {
            var selectedValue = e.params.data.id;

            if (selectedValue == "New") {
              $('#customRadioIcon1').prop('checked', true);
              $('.customRadioIcon1').addClass('checked');
            } else {
              $('#customRadioIcon1').prop('checked', false);
              $('.customRadioIcon1').removeClass('checked');
            }
          });

          // <! -------------- Once I Selected New Default Processing Status Selected  -------------- >



          // ----------  Send Department To Function.Php----------   

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

          // Out side of the pop Up click remove the selected values
          $('#Add_task_type_Modal').on('hidden.bs.modal', function() {
            $('.task_type_add').val(null).trigger('change');
          });


          const $textarea = $('#details');
          const $Appoinment_charCount = $('#Appoinment_charCount');

          // Update character count on input
          $textarea.on('input', function() {
            const remaining = 200 - $textarea.val().length;
            $Appoinment_charCount.text(remaining);
          });


        });


        //  -------    close_popUp function   ---------

        function close_popUp(id_of_popup) {
          let Id = '#'.concat("", id_of_popup);
          $(Id).modal('hide');
          $('.task_type_add').val(null).trigger('change');
        }
      </script>


      <!-- / Content -->
      <?php include('include/footer.php'); ?>


      <!-- --Data tables-- -->
      <script src="assets/js/assign_task_admin.js"></script>