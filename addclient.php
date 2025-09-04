<?php include('include/head.php'); ?>
<?php require 'data/dbconfig.php'; 

$buttonText = isset($buttonText) ? $buttonText : 'Submit';
$name = '';
$email = '';
$designation = '';
$mobile = '';
$role = '';
$active = '';
$cuid='';
$ref = '';
$ref_name = '';

if (isset($_GET['id'])) {
  $id = filter_var($_GET['id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  //$sql = "SELECT * FROM employee WHERE id='$id' ";
  $sql = "SELECT * FROM employee WHERE id='$id' AND role='client'";
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
    $cuid = htmlspecialchars($row['cuid']);
    $ref = htmlspecialchars($row['ref']);
    $ref_name = htmlspecialchars($row['ref_name']);
    $password = htmlspecialchars($row['password']);
    $buttonText = 'Update'; // Set button text to 'Update'
  }
}




$sql = "SELECT * FROM employee WHERE role='client' ORDER BY id ASC";
//$sql = "SELECT * FROM employee ORDER BY id ASC ";
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
      'cuid'=>$row['cuid'],
      'ref' => $row['ref'],
      'ref_name' => $row['ref_name'],
      'picture' => $row['picture']
    );
  }
}
$response = array('data' => $data);
$json = json_encode($response, JSON_PRETTY_PRINT);
//echo json_encode($response, JSON_PRETTY_PRINT);

$file = 'assets/json/client_data.json';


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
            <form id="Addcli">
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
                                            <label for="name">Client Name *</label>
                                        </div>
                                        <div class="form-floating form-floating-outline mb-3">
                                            <input type="text" id="name" class="form-control"
                                                value="<?php echo empty($name) ? '' : htmlspecialchars($name); ?>"
                                                placeholder="MBW" required maxlength="40" pattern="[A-Za-z\s]+"
                                                title="Only letters and spaces are allowed." />
                                            <label for="name"> Company Name *</label>
                                        </div>

                                        <!-- <div class="row" id="SameEmailAlert" style="display:none;">
                                            <div class="col-12">
                                                <div class="alert alert-danger" role="alert">
                                                    <p id="Same_Email_alert_para"></p>
                                                </div>
                                            </div>
                                        </div> -->

                                        <div class="form-floating form-floating-outline mb-3 mt-4">
                                            <input type="email" id="email" class="form-control"
                                                value="<?php echo empty($email) ? '' : htmlspecialchars($email); ?>"
                                                placeholder="example@gmail.com" required maxlength="60"
                                                title="Please enter a valid email address (e.g., example@gmail.com)" />
                                            <label for="email">Email *</label>
                                        </div>

                                        <!-- <div class="row" id="SameMobileAlert" style="display:none;">
                                            <div class="col-12">
                                                <div class="alert alert-danger" role="alert">
                                                    <p id="Same_Mobile_alert_para"></p>
                                                </div>
                                            </div>
                                        </div> -->

                                        <div class="form-floating form-floating-outline mb-3 mt-5">
                                            <div class="card-body p-0">
                                                <label for="excel_file mb-5">Profile Pic: <small>( Only 200kb
                                                        )</small></label>
                                                <input type="file" class="form-control mt-2" name="profile_file"
                                                    id="profile_file" onchange="validateFile()"
                                                    <?php echo isset($buttonText) && $buttonText == 'Update' ? '' : ''; ?>>
                                            </div>
                                            <div id="fileError" class="text-danger" style="display:none;"></div>
                                        </div>

                                        <div class="form-floating form-floating-outline mb-4 mt-4 row">
                                            <div class="col-md-3 mb-md-0 mb-6">
                                                <h5 class="Address_ship_headline employee_h5 mb-1"> Reference*
                                                </h5>
                                            </div>
                                            <div class="col-md-4 mb-md-0 mb-5">
                                                <div class="form-check custom-option custom-option-basic checked">
                                                    <label
                                                        class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                        for="admin">
                                                        <input type="radio" required name="ref"
                                                            class="form-check-input" value="insurence" id="insurence"
                                                            checked <?php echo ($ref === 'insurence') ? 'checked' : ''; ?>>
                                                        <span class="custom-option-header inner_ship_selct">
                                                            <span class="h6 mb-0">Insurence</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check custom-option custom-option-basic">
                                                    <label
                                                        class="form-check-label estimate_address custom-option-content add_emp_radio"
                                                        for="employee">
                                                        <input type="radio" required name="ref"
                                                            class="form-check-input" value="docter" id="docter"
                                                             <?php echo ($ref === 'docter') ? 'checked' : ''; ?>>
                                                        <span class="custom-option-header inner_ship_selct">
                                                            <span class="h6 mb-0">Doctor</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="form-floating form-floating-outline mb-3">
                                            <input type="text" id="ref_name" class="form-control" name="ref_name"
                                                value="<?php echo empty($ref_name) ? '' : htmlspecialchars($ref_name); ?>"
                                                placeholder="John Doe" required maxlength="40" pattern="[A-Za-z\s]+"
                                                title="Only letters and spaces are allowed." />
                                            <label for="ref_name">Reference Name *</label>
                                        </div>

                                    </div>

                                    <!-- Left Column End -->
                                    <div class="col-md-6">

                                        <label for="mobile mb-5">Phone No :</label>
                                        <div class="form-floating form-floating-outline mb-3">
                                            <input type="tel" id="mobile" class="form-control phone-mask"
                                                value="<?php echo empty($mobile) ? '' : htmlspecialchars($mobile); ?>"
                                                placeholder="Enter phone number" aria-label="Phone Number" />
                                        </div>

                                        <div class="form-floating employee_txtBox_main form-floating-outline mt-5 mb-4">
                                            <textarea class="form-control employee_details_txtBox" id="address"
                                                style="height: 180px;" placeholder="Enter Address here"
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
                                        value="<?php echo ($buttonText === 'Update') ? 'Updatecli' : 'Addcli'; ?>"
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

            <!-- -----Data Table assignTask Start------ -->
            <div class="mt-5">
                <!-- Data Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-datatable table-responsive">
                                    <table class="datatables-client table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <!-- <th>S.No</th> -->
                                                <th>Client ID</th>
                                                <th>Profile</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <!-- <th>Designation</th> -->
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
            <!-- -----Data Table assignTask Start------ -->

            <link rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

            <script>
                $('#Addcli').on('submit', function(e) {
                    e.preventDefault();
                    //Add the Preloader
                    $('.event_trigger_loader').addClass('active');
                    // Get form values
                    const name = $('#name').val();
                    const email = $('#email').val();
                    const designation = "client";
                    const mobileNumber = $('#mobile').val();
                    const dialCode = $('.iti__selected-dial-code').html();
                    const fullMobile = mobileNumber ? dialCode + mobileNumber : '';
                    const address = $('#address').val();
                    const department = $('#department').val();
                    const role = $('input[name="role"]:checked').val();
                    const status = $('#status-value').val();
                    const ref = $('input[name="ref"]:checked').val();
                    const ref_name = $('#ref_name').val();
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
                    formData.append('mobile', fullMobile);
                    formData.append('role', role);
                    formData.append('status', status); // Include selected radio button value
                    formData.append('hid', hiddenId);
                    formData.append('ref', ref);
                    formData.append('ref_name', ref_name);
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
                                    'User license is exhausted. Please buy more or contact MBW.',
                                    'false');
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
                $(document).ready(function() {
                    // $('#DataTables_Table_0').DataTable({
                    //     destroy: true // This allows reinitialization
                    // });
                    // Address Text Box Count Text
                    const $textarea = $('#address');
                    const $Address_charCount = $('#Address_charCount');
                    // Update character count on input
                    $textarea.on('input', function() {
                        const remaining = 100 - $textarea.val().length;
                        $Address_charCount.text(remaining);
                    });
                });
                var input = document.querySelector("#mobile");
                var iti = intlTelInput(input, {
                    initialCountry: "in", // Automatically detect country
                    separateDialCode: true, // Display the country code with the phone number
                    preferredCountries: ['us', 'gb', 'in'], // Optional, prioritize specific countries
                });
                // Optional: Validate input and show an error if the phone number is invalid
                // input.addEventListener('blur', function() {
                //     if (!iti.isValidNumber()) {
                //         alert("Please enter a valid phone number.");
                //     }
                // });
            </script>

        </div>
    </div>
</div>

<script src="assets/js/client.js"></script>

<!-- Include Cloudflare CDN for intl-tel-input -->

<?php include('include/footer.php'); ?>