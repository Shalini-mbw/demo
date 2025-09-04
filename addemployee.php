<?php include('include/head.php'); ?>
<?php require 'data/dbconfig.php';
$buttonText = isset($buttonText) ? $buttonText : 'Submit';
$name = '';
$email = '';
$designation = '';
$mobile = '';
$role = '';
$active = '';
if (isset($_GET['id'])) {
  $id = filter_var($_GET['id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $sql = "SELECT * FROM employee WHERE id='$id' ";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = htmlspecialchars($row['name']);
    $email = htmlspecialchars($row['email']);
    $platform = $row['department'];
    $designation = htmlspecialchars($row['designation']);
    $address = htmlspecialchars($row['address']);
    $mobile = htmlspecialchars($row['mobile']);
    $role = htmlspecialchars($row['role']); // Fetch the role from the database
    $active = htmlspecialchars($row['isenable']);
    $password = htmlspecialchars($row['password']);
    $buttonText = 'Update'; // Set button text to 'Update'
  }
}





$sql = "SELECT * FROM employee ORDER BY id ASC ";
$result = $conn->query($sql);

$data = array();

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Add each row to the data array
    $data[] = array(

      'id' => $row['id'],
      'name' => $row['name'],
      'email' => $row['email'],
      'designation' => $row['designation'],
      'mobile' => $row['mobile'],
      'role' => $row['role'],
      'isenable' => $row['isenable'],
      'picture' => $row['picture']
    );
  }
}
$response = array('data' => $data);
$json = json_encode($response, JSON_PRETTY_PRINT);
//echo json_encode($response, JSON_PRETTY_PRINT);

$file = 'assets/json/employee_data.json';


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


            <?php if ($AddNewEmployee === 'Enable') : ?>
            <!-- Estimate Form code Start-->
            <!-- <div class="select_button_section">
          <button class="singleUpload show" download>Add Single User</button>
          <?php if ($BulkUser === 'Enable') : ?>
            <button class="bulkUpload" download>Bulk User Upload</button>
          <?php endif; ?>
        </div> -->
            <div class="single_form_sec">
                <form id="addEmp">
                    <div class="d-flex justify-content-center">
                        <div class="col-md-12">
                            <div class="card mb-9">
                                <div class="card-body">
                                    <div class="row" id="SameNameAlert" style="display:none;">
                                        <div class="col-12">
                                            <div class="alert alert-danger" role="alert">
                                                <p id="Same_name_alert_para"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gy-5">
                                        <!-- Left Column Start -->
                                        <div class="col-md-6">
                                            <input type="hidden" id="hiddenId"
                                                value="<?php echo empty($id) ? '' : htmlspecialchars($id); ?>">
                                            <input type="hidden" id="hiddenpass"
                                                value="<?php echo empty($password) ? '' : htmlspecialchars($password); ?>">

                                            <!-- Name and Phone Number Start -->
                                            <div class="form-floating form-floating-outline mb-3">
                                                <input type="text" id="name" class="form-control"
                                                    value="<?php echo empty($name) ? '' : htmlspecialchars($name); ?>"
                                                    placeholder="John Doe" required maxlength="40" pattern="[A-Za-z\s]+"
                                                    title="Only letters and spaces are allowed." />
                                                <label for="name">Client or Company Name *</label>
                                            </div>

                                            <div class="row" id="SameEmailAlert" style="display:none;">
                                                <div class="col-12">
                                                    <div class="alert alert-danger" role="alert">
                                                        <p id="Same_Email_alert_para"></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-floating form-floating-outline mb-3 mt-4">
                                                <input type="email" id="email" class="form-control"
                                                    value="<?php echo empty($email) ? '' : htmlspecialchars($email); ?>"
                                                    placeholder="example@gmail.com" required maxlength="60"
                                                    title="Please enter a valid email address (e.g., example@gmail.com)" />
                                                <label for="email">Email *</label>
                                            </div>



                                            <?php

                        $sql1 = "SELECT name FROM department";
                        $result = $conn->query($sql1);
                        $platform = isset($platform) ? $platform : '';
                        echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                        echo ' <select id="department" name="department" class="select2 form-select task_type_add" data-allow-clear="true" onchange="Add_department(this.value);" required>';
                        echo ' <option value="">Select</option>';
                        echo ' <option value="New_Department">Create New Department ➕ </option>';
                        if ($result->num_rows > 0) {
                          $departMent = [];
                          while ($row = $result->fetch_assoc()) {
                            $name = htmlspecialchars($row['name']);
                            $departMent[] = $name;
                          }
                          sort($departMent);
                          foreach ($departMent as $name) {
                            $isSelected = ($name === $platform) ? ' selected' : '';
                            echo '<option value="' . $name . '"' . $isSelected . '>' . $name . '</option>';
                          }
                        } else {
                          echo '<option value="">No Type found.</option>';
                        }

                        echo '  </select>';
                        echo '   <label for="collapsible-state">Department *</label>';

                        echo ' </div>';
                        ?>
                                            <?php

                        $sql1 = "SELECT name FROM designation";
                        $result = $conn->query($sql1);
                        $designation = isset($designation) ? $designation : '';
                        echo ' <div class="form-floating form-floating-outline mb-3 mt-6">';
                        echo ' <select id="designation" name="designation" class="select2 form-select task_type_add" data-allow-clear="true" onchange="Add_designation(this.value);" required>';
                        echo ' <option value="">Select</option>';
                        echo ' <option value="New_Designation">Create New Designation ➕ </option>';
                        if ($result->num_rows > 0) {
                          $design = [];
                          while ($row = $result->fetch_assoc()) {
                            $name = htmlspecialchars($row['name']);
                            $design[] = $name;
                          }
                          sort($design);
                          foreach ($design as $name) {
                            $isSelected = ($name === $designation) ? ' selected' : '';
                            echo '<option value="' . $name . '"' . $isSelected . '>' . $name . '</option>';
                          }
                        } else {
                          echo '<option value="">No Type found.</option>';
                        }

                        echo '  </select>';
                        echo '   <label for="collapsible-state">Designation *</label>';

                        echo ' </div>';
                        ?>

                                            <!-- <div class="form-floating form-floating-outline mb-3 mt-4">
                        <input type="text" id="designation" class="form-control"
                          value="<?php echo empty($designation) ? '' : htmlspecialchars($designation); ?>"
                          placeholder="Designer" />
                        <label for="designation">Designation *</label>
                      </div> -->


                                            <div class="row" id="SameMobileAlert" style="display:none;">
                                                <div class="col-12">
                                                    <div class="alert alert-danger" role="alert">
                                                        <p id="Same_Mobile_alert_para"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-3 mt-4">
                                                <input type="text" id="mobile" class="form-control phone-mask"
                                                    value="<?php echo empty($mobile) ? '' : htmlspecialchars($mobile); ?>"
                                                    placeholder="6587998941" aria-label="658 799 8941" required
                                                    pattern="\d{10}" maxlength="10"
                                                    title="Please enter a valid 10-digit phone number." />
                                                <label for="mobile">Phone No *</label>
                                            </div>

                                            <div class="form-floating form-floating-outline mb-3 mt-5">
                                                <div class="card-body p-0">
                                                    <label for="excel_file mb-5">Profile Pic: <small>( Only 200KB
                                                            )</small></label>
                                                    <input type="file" class="form-control mt-2" name="profile_file"
                                                        id="profile_file" onchange="validateFile()"
                                                        <?php echo isset($buttonText) && $buttonText == 'Update' ? '' : ''; ?>>
                                                </div>
                                                <div id="fileError" class="text-danger" style="display:none;"></div>
                                            </div>

                                        </div>

                                        <!-- Left Column End -->
                                        <div class="col-md-6">

                                            <!-- -------Admin | Employee-------- -->
                                            <?php if ($UserRoles === 'Enable') : ?>
                                            <div class="form-floating form-floating-outline mb-4 mt-4 row">
                                                <div class="col-md-3 mb-md-0 mb-5">
                                                    <h5 class="Address_ship_headline employee_h5 mb-1"> Role Rights *
                                                    </h5>
                                                </div>
                                                <div class="col-md-3 mb-md-0 mb-5">
                                                    <div class="form-check custom-option custom-option-basic checked">
                                                        <label
                                                            class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                            for="admin">
                                                            <input type="radio" required name="role"
                                                                class="form-check-input" value="admin" id="admin"
                                                               
                                                                <?php echo ($role === 'admin') ? 'checked' : ''; ?>>
                                                            <span class="custom-option-header inner_ship_selct">
                                                                <span class="h6 mb-0">Admin</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check custom-option custom-option-basic">
                                                        <label
                                                            class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                            for="employee">
                                                            <input type="radio" required name="role"
                                                                class="form-check-input" value="user" id="user"
                                                                <?php echo ($role === 'user') ? 'checked' : ''; ?>>
                                                            <span class="custom-option-header inner_ship_selct">
                                                                <span class="h6 mb-0">User</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- <div class="col-md-3">
                                                    <div class="form-check custom-option custom-option-basic">
                                                        <label
                                                            class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                            for="client">
                                                            <input type="radio" required name="role"
                                                                class="form-check-input" value="client" id="client"
                                                                <?php echo ($role === 'client') ? 'checked' : ''; ?>>
                                                            <span class="custom-option-header inner_ship_selct">
                                                                <span class="h6 mb-0">Client</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div> -->
                                            </div>
                                            <?php endif; ?>


                                            <!-- -------Active Inactive-------- -->

                                            <div class="form-floating form-floating-outline mb-4 row">
                                                <div class="col-md-3 mb-md-0 mb-5">
                                                    <h5 class="Address_ship_headline employee_h5 mb-1">User's Status *
                                                    </h5>
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="switch switch-primary">
                                                        <!-- Set the checkbox as checked if the active status is '1' -->
                                                        <input type="checkbox" class="switch-input" id="status-toggle"
                                                            <?php echo ($active === '1') ? 'checked' : 'required'; ?>>
                                                        <span class="switch-toggle-slider">
                                                            <span class="switch-on">
                                                                <i class="ri-check-line"></i> Active
                                                            </span>
                                                            <span class="switch-off">
                                                                <i class="ri-close-line"></i> Inactive
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Hidden input field to submit status as a value of 1 or 0 (Active/Inactive) -->
                                            <input type="hidden" name="status" id="status-value"
                                                value="<?php echo ($active === '1') ? '1' : '0'; ?>">

                                            <script>
                                            // When the checkbox is toggled, update the hidden input field
                                            $('#status-toggle').change(function() {
                                                var statusValue = $('#status-value');
                                                statusValue.val(this.checked ? '1' : '0');
                                            });
                                            </script>


                                            <div
                                                class="form-floating employee_txtBox_main form-floating-outline mt-5 mb-4">
                                                <textarea class="form-control employee_details_txtBox" id="address"
                                                    style="height: 287px;" placeholder="Enter Address here" 
                                                    maxlength="100"><?php echo htmlspecialchars(empty($address) ? '' : $address); ?></textarea>
                                                <div id="Address_charCount" class="char-count-overlay">/100</div>
                                                <label for="address">Address</label>
                                            </div>



                                        </div>

                                        <!-- Right Column End -->
                                    </div>

                                    <!-- Buttons -->

                                    <div class="col-12 d-flex justify-content-end mt-4">
                                        <button type="button" id="cancelbtn"
                                            class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                                        <button type="submit" id="submit"
                                            value="<?php echo ($buttonText === 'Update') ? 'Updateemp' : 'Addemp'; ?>"
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
            </div>
            <div class="bulk_form_sec">

                <form id='bulkAddEmp'>
                    <div class="d-flex justify-content-center">
                        <div class="col-md-12">
                            <div class="card mb-9">
                                <div class="samplefilesection">
                                    <a href="uploads/user_bulk_upload.xlsx" class="linkforSamplebtn" download=""><i
                                            class="ri-file-excel-2-line"></i> &nbsp;Download Sample Format</a>
                                </div>
                                <div class="card-body">
                                    <label for="excel_file">Choose Excel file:</label>
                                    <br />
                                    <input type="file" class="form-control" name="excel_file" id="excel_file" required>
                                    <div class="col-12 d-flex justify-content-end mt-4 mb-5 pl-5">
                                        <button type="submit" id="bulkSubmit" class="btn btn-primary"
                                            value="bulkUpload">Bulk Upload</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>


            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <div class="mt-5" id="progressContainer" style="display:none;">
                <div class="d-flex justify-content-center">
                    <div class="col-md-12">
                        <div class="card mb-9">
                            <div class="card-body">
                                <div class="ce_ixelgen_progress_bar block">
                                    <div class="progress_bar">
                                        <div class="progress_bar_item grid-x">
                                            <div class="item_label cell auto">Uploading User Excel</div>
                                            <div class="item_value cell shrink">0%</div>
                                            <div class="item_bar cell">
                                                <div class="progress progress-bar-success" data-progress="0"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <style>
            .ce_ixelgen_progress_bar {
                max-width: 800px;
                margin: 0 auto;
            }

            .ce_ixelgen_progress_bar .progress_bar_item {
                margin-bottom: 2rem;
            }

            .ce_ixelgen_progress_bar .item_label,
            .ce_ixelgen_progress_bar .item_value {
                font-size: 1.2rem;
                font-weight: 600;
                color: #333;
                margin-bottom: 0.5rem;
            }

            .ce_ixelgen_progress_bar .item_value {
                font-weight: 400;
                text-align: end;
            }

            .ce_ixelgen_progress_bar .item_bar {
                position: relative;
                height: 1.5rem;
                width: 100%;
                background-color: #b3b3b3;
                border-radius: 4px;
            }

            .ce_ixelgen_progress_bar .item_bar .progress {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 0;
                height: 1.5rem;
                margin: 0;
                background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
                background-size: 40px 40px;
                border-radius: 4px;
                transition: width 100ms ease;
            }

            .progress-bar-success {
                background-color: #53D500;
            }
            </style>

            <script>
            $(document).ready(function() {
                progress_bar();
            });

            function progress_bar() {
                var speed = 30;
                var items = $('.progress_bar').find('.progress_bar_item');

                items.each(function() {
                    var item = $(this).find('.progress');
                    var itemValue = item.data('progress');
                    var i = 0;
                    var value = $(this);

                    var count = setInterval(function() {
                        if (i <= itemValue) {
                            var iStr = i.toString();
                            item.css({
                                'width': iStr + '%'
                            });
                            value.find('.item_value').html(iStr + '%');
                        } else {
                            clearInterval(count);
                        }
                        i++;
                    }, speed);
                });
            }
            </script>


            <!-- -----Data Table assignTask Start------ -->
            <div class="mt-5">
                <!-- Data Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-datatable table-responsive">
                                    <table class="datatables-employee table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>S.No</th>
                                                <th>Profile</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Designation</th>
                                                <th>Mobile</th>
                                                <th>Role</th>

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


            <!-- Add New DepartMent Card Modal -->
            <div class="modal fade custom_model" id="Add_department_Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <form id="department_add" class="row g-5">
                                <!-- Account Details -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-center">Add Department </h6>
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
                                                    <input type="text" name="name_dep" id="name_dep" value=""
                                                        placeholder="Digital Marketing" class="form-control" required />
                                                    <label for="name_dep">Department Name</label>
                                                </div>
                                                <!-- <label for="task">Web design</label> -->
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset" class="btn btn-outline-secondary me-4 waves-effect"
                                                onclick="close_popUp('Add_department_Modal');">Cancel</button>
                                            <button type="submit" id="Add_dept_submit" value="AddDepartment"
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

            <!-- Add New Designation Card Modal -->
            <div class="modal fade custom_model" id="Add_designation_Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <form id="designation_add" class="row g-5">
                                <!-- Account Details -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-center">Add Designation</h6>
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
                                                    <input type="text" name="name_des" id="name_des" value=""
                                                        placeholder="Digital Marketing" class="form-control" required />
                                                    <label for="name_des">Designation Name</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="col-12 d-flex justify-content-center mt-12">
                                            <button type="reset" class="btn btn-outline-secondary me-4 waves-effect"
                                                onclick="close_popUp('Add_designation_Modal');">Cancel</button>
                                            <button type="submit" id="Add_dest_submit" value="AddDesignation"
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


            <script>
            $('#bulkAddEmp').on('submit', function(e) {
                e.preventDefault();
                //$('.event_trigger_loader').addClass('active');

                const excel_file = $('#excel_file')[0];
                const file = excel_file.files[0];
                const submit = $('#bulkSubmit').val();

                const formData = new FormData();
                formData.append('excel_file', file);
                formData.append('submit', submit);

                $('#progressContainer').show();
                // Start by setting the progress bar to 0% initially
                updateProgressBar();

                $.ajax({
                    url: 'function.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(e) {
                            if (e.lengthComputable) {
                                var percentComplete = (e.loaded / e.total) *
                                50; // Max upload progress is 50%
                                updateProgressBar(percentComplete);
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        console.log('Server response:', response);

                        // Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                            $('#progressContainer').hide();
                        }, 1000);

                        // Parse the JSON response
                        let jsonResponse;
                        try {
                            jsonResponse = JSON.parse(response);
                        } catch (error) {
                            showModalWithParams(`Failed to parse response: ${response}`, 'false');
                            return;
                        }

                        // Check if the response status is success
                        if (jsonResponse.status === 'success') {
                            showModalWithParams(`${jsonResponse.count} users inserted.`, 'true');
                        } else {
                            showModalWithParams(jsonResponse.message ||
                                'Unexpected error occurred.', 'false');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                            $('#progressContainer').hide();
                        }, 1000);

                        showModalWithParams(`An error occurred: ${textStatus}`, 'false');
                    }
                });
            });




            function updateProgressBar() {
                const progressBar = document.querySelector('.progress');
                const progressValue = document.querySelector('.item_value');

                function fetchProgress() {
                    fetch('function.php?action=getProgress')
                        .then(response => response.json())
                        .then(data => {
                            const progress = data.progress;
                            progressBar.style.width = progress + '%';
                            progressBar.setAttribute('data-progress', progress);
                            progressValue.textContent = Math.floor(progress) + '%'; // Removes decimals

                            if (progress < 100) {
                                setTimeout(fetchProgress, 1000); // Poll every second
                            }
                        })
                        .catch(error => console.error('Error fetching progress:', error));
                }

                fetchProgress();
            }




            $('#addEmp').on('submit', function(e) {
                e.preventDefault();

                //Add the Preloader
                $('.event_trigger_loader').addClass('active');


                // Get form values
                const name = $('#name').val();
                const email = $('#email').val();
                const designation = $('#designation').val();
                const mobile = $('#mobile').val();
                const address = $('#address').val();
                const department = $('#department').val();
                const role = $('input[name="role"]:checked').val();
                const status = $('#status-value').val();
                const submit = $('#submit').val();
                const hiddenId = $('#hiddenId').val();
                const hiddenpass = $('#hiddenpass').val();


                // Assuming you have all the necessary variables already defined
                const PicfileInput = $('#profile_file')[0]; // Get the file input element
                const Pic_file = PicfileInput.files[0]; // Get the first file from the input

                const formData = new FormData();
                formData.append('name', name);
                formData.append('email', email);
                formData.append('department', department);
                formData.append('designation', designation);
                formData.append('address', address);
                formData.append('mobile', mobile);
                formData.append('role', role);
                formData.append('status', status); // Include selected radio button value
                formData.append('hid', hiddenId);
                formData.append('submit', submit);
                formData.append('hpass', hiddenpass);
                formData.append('file', Pic_file); // Append the file input

                // Submit the form data using Fetch API
                fetch('function.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(result => {

                        // console.log('Server response:', result);


                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        const trimmedResult = result.trim();

                        // Split the response string into an array based on commas
                        let responseArray = trimmedResult.split(',');

                        // Initialize an empty string to store the combined error messages
                        let combinedMessage = '';

                        // Check each element in the response array
                        responseArray.forEach(item => {

                            item = item.trim(); // Remove any extra spaces

                            // Check if the response contains each type of duplicate and append the message
                            if (item === 'duplicate name') {

                                combinedMessage +=
                                    `${name} is already exist, Please add Extra Surename.<br>`;
                                console.log(combinedMessage);
                            }
                            if (item === 'duplicate mobile') {
                                combinedMessage +=
                                    `${mobile} is already used by ${name}, Please try another mobile number.<br>`;
                            }
                            if (item === 'duplicate email') {
                                combinedMessage +=
                                    `${email} is already used, Please try another email.<br>`;
                            }
                        });


                        if (trimmedResult === 'success') {
                            showModalWithParams(`${name} Added`, 'true');

                        } else if (trimmedResult === 'Limit Exhausted') {
                            showModalWithParams(
                                'User license is exhausted. Please buy more or contact MBW.', 'false');
                        } else if (trimmedResult === 'updated') {
                            showModalWithParams(`${name} Updated`, 'true');

                        } else if (combinedMessage !== '') {
                            $('#SameNameAlert').show();
                            $('#Same_name_alert_para').html(combinedMessage);

                            // Hide after 3 seconds
                            setTimeout(function() {
                                $('#SameNameAlert').hide();
                            }, 4000);
                        } else {
                            //  alert('Unexpected response from the server: ' + trimmedResult);
                            showModalWithParams(trimmedResult, 'fasle');
                        }

                    })
                    .catch(error => {

                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        showModalWithParams(`An error occurred: ${error}`, 'false');
                    });

            });


            function validateFile() {
                const PicfileInput = $('#profile_file')[0];
                const fileError = $('#fileError');
                const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.webp)$/i;

                // Clear previous error messages
                fileError.hide();
                fileError.text('');

                // Get the selected file
                const file = PicfileInput.files[0];

                // Check if a file is selected
                if (file) {
                    // Validate file format
                    if (!allowedExtensions.exec(file.name)) {
                        fileError.text('Invalid file format. Please upload a .jpg, .jpeg, .webp, or .png file.');
                        fileError.show();
                        PicfileInput.value = ''; // Clear the input
                        return false; // Return false when validation fails
                    }

                    const maxSizeInBytes = 200 * 1024; // 200KB
                    if (file.size > maxSizeInBytes) {
                        fileError.text('File size must be less than 200KB.');
                        fileError.show();
                        PicfileInput.value = ''; // Clear the input
                        return false; // Return false when validation fails
                    }
                    //Validate image resolution (exact resolution of 1351x421)
                    const img = new Image();
                    const reader = new FileReader();

                    // Asynchronous check for image resolution
                    return new Promise((resolve, reject) => {
                        reader.onload = function(event) {
                            img.src = event.target.result;
                            img.onload = function() {
                                // Check if the image resolution is exactly 1080x1080
                                if (img.width !== 1080 || img.height !== 1080) {
                                    fileError.text(
                                    'Image resolution must be exactly 1080x1080 pixels.');
                                    fileError.show();
                                    PicfileInput.value = ''; // Clear the input
                                    reject(false); // Return false when validation fails
                                } else {
                                    resolve(true); // All validations passed, return true
                                }
                            };
                            img.onerror = function() {
                                fileError.text('Failed to load the image. Please try another file.');
                                fileError.show();
                                PicfileInput.value = ''; // Clear the input
                                reject(false); // Return false when validation fails
                            };
                        };

                        reader.readAsDataURL(file);
                    });
                } else {
                    return false; // Return false if no file is selected
                }
            }



            $(document).ready(function() {
                // Initially show the single form section and hide the bulk form
                $('.single_form_sec').addClass('show').show();
                $('.bulk_form_sec').hide();
                $('.singleUpload').addClass('show');

                // Toggle for singleUpload form
                $('.singleUpload').click(function(e) {
                    e.preventDefault();
                    const $singleForm = $('.single_form_sec');
                    const $bulkForm = $('.bulk_form_sec');

                    // Check if the single form is not visible
                    if (!$singleForm.is(':visible')) {
                        // Hide the bulk form and show the single form
                        $bulkForm.slideUp(300, function() {
                            $bulkForm.removeClass('show');
                            $singleForm.slideDown(300, function() {
                                $singleForm.addClass('show');
                            });
                        });
                        // Update button visibility
                        $('.bulkUpload').removeClass('show');
                        $('.singleUpload').addClass('show');
                    }
                });

                // Toggle for bulkUpload form
                $('.bulkUpload').click(function(e) {
                    e.preventDefault();
                    const $singleForm = $('.single_form_sec');
                    const $bulkForm = $('.bulk_form_sec');

                    // Check if the bulk form is not visible
                    if (!$bulkForm.is(':visible')) {
                        // Hide the single form and show the bulk form
                        $singleForm.slideUp(300, function() {
                            $singleForm.removeClass('show');
                            $bulkForm.slideDown(300, function() {
                                $bulkForm.addClass('show');
                            });
                        });
                        // Update button visibility
                        $('.singleUpload').removeClass('show');
                        $('.bulkUpload').addClass('show');
                    }
                });



                // Out side of the pop Up click remove the selected values
                $('#Add_department_Modal').on('hidden.bs.modal', function() {
                    $('.task_type_add').val(null).trigger('change');
                });

                // Out side of the pop Up click remove the selected values
                $('#Add_designation_Modal').on('hidden.bs.modal', function() {
                    $('.task_type_add').val(null).trigger('change');
                });


                // Address Text Box Count Text
                const $textarea = $('#address');
                const $Address_charCount = $('#Address_charCount');

                // Update character count on input
                $textarea.on('input', function() {
                    const remaining = 100 - $textarea.val().length;
                    $Address_charCount.text(remaining);
                });



            });


            // ----------   Add New Department Show PopUp   ----------   

            function Add_department(value) {
                if (value == "New_Department") {
                    $('#Add_department_Modal').modal('show');
                }

            }



            // ----------   Add New Designation Show PopUp   ----------   

            function Add_designation(value) {
                if (value == "New_Designation") {
                    $('#Add_designation_Modal').modal('show');
                }

            }


            //  -------    close_popUp function   ---------

            function close_popUp(id_of_popup) {
                let Id = '#'.concat("", id_of_popup);
                $(Id).modal('hide');
                $('.task_type_add').val(null).trigger('change');
            }


            // ----------  Send Department To Function.Php----------   

            $('#department_add').on('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission


                //Add the Preloader
                $('.event_trigger_loader').addClass('active');

                // Get form values
                const name = $('#name_dep').val();
                const submit = $('#Add_dept_submit').val();

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
                        } else if (trimmedResult === 'updated') {
                            showModalWithParams(`${name} Updated`, 'true');
                        } else {
                            alert('Unexpected response from the server: ' + trimmedResult);
                            showModalWithParams(`${trimmedResult}`, 'false');
                        }
                        $('#Add_department_Modal').modal('hide');
                    })
                    .catch(error => {
                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        showModalWithParams(`An error occurred: ${error}`, 'false');
                        $('#Add_department_Modal').modal('hide');
                    });
            });


            // ----------  Send Designation To Function.Php ----------   

            $('#designation_add').on('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                //Add the Preloader
                $('.event_trigger_loader').addClass('active');

                // Get form values
                const name = $('#name_des').val();
                const submit = $('#Add_dest_submit').val();

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
                        } else if (trimmedResult === 'updated') {
                            showModalWithParams(`${name} Updated`, 'true');
                        } else {
                            alert('Unexpected response from the server: ' + trimmedResult);
                            showModalWithParams(`${trimmedResult}`, 'false');
                        }
                        $('#Add_designation_Modal').modal('hide');
                    })
                    .catch(error => {
                        //Remove the Preloader
                        setTimeout(function() {
                            $('.event_trigger_loader').removeClass('active');
                        }, 1000);

                        showModalWithParams(`An error occurred: ${error}`, 'false');
                        $('#Add_designation_Modal').modal('hide');
                    });
            });
            </script>

            <!-- / Content -->
            <?php include('include/footer.php'); ?>
            <script src="assets/js/employee.js"></script>