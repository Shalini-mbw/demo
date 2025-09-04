<?php include('include/head.php'); ?>

<?php require 'data/dbconfig.php';


?>


<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">

            <form id="delReportlogForm">
                <div class="d-flex justify-content-center">
                    <div class="col-md-12">
                        <div class="card mb-9">
                            <div class="card-body">
                                <div class="row gy-5">
                                    <!-- Left Column Start -->
                                    <div class="col-md-6">
                                        <input type="hidden" id="hiddenId" value="">

                                        <div class="row mb-3">
                                            <h5 class="Address_ship_headline">Delete Log Report</h5>
                                        </div>





                                        <?php
                                        // Fetch names from the event table
                                        $sql2 = "SELECT name FROM employee";
                                        $result = $conn->query($sql2);


                                        // Initialize an associative array to hold unique names
                                        $root = [];

                                        // Add event names to the options
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $root[$row['name']] = htmlspecialchars($row['name']);
                                            }
                                        }



                                        // Sort the unique options alphabetically
                                        $uniqueOptions = array_values($root);
                                        sort($uniqueOptions);

                                        // Build the dropdown
                                        echo '<div class="form-floating form-floating-outline mb-5 mt-3">';
                                        echo '<select id="selectEmpName" name="selectEmpName"  class="select2 form-select" data-allow-clear="true">';
                                        echo '<option value="">All</option>';
                                        if (count($uniqueOptions) > 0) {
                                            foreach ($uniqueOptions as $name) {
                                                echo '<option value="' . $name . '">' . $name . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No names found.</option>';
                                        }
                                        echo '</select>';
                                        echo '<label for="selectEmpName">Choose - User Name</label>';
                                        echo '</div>';
                                        ?>




                                    </div>



                                </div>

                                <!-- Buttons -->
                                <div class="col-12 d-flex justify-content-end mb-6">
                                    <button type="reset"
                                        class="btn btn-outline-secondary me-4 waves-effect">Cancel</button>
                                    <button type="submit" id="submit" value="logReport"
                                        class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>


            <div class="row" style="margin-top: 50px;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-datatable table-responsive">
                                <?php if ($LogExcel === 'Enable'): ?>
                                    <button id="exportToExcel" class="btn btn-primary" style="margin: 20px;">Export to
                                        Excel</button>
                                <?php endif; ?>
                                <table class="datatables-logReport table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>S.No</th>
                                            <th>File Name</th>
                                            <th>Deleted By</th>
                                            <th>Deleted DateTime</th>
                                           

                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                <div id="noDatafound" style="padding: 20px;background: aliceblue;text-align: center;"><span>No data fetch. Please Select and View....</span></div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>





            <!-- / Content -->
            <?php include('include/footer.php'); ?>
            <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
            <script>


                document.addEventListener('DOMContentLoaded', function () {
                    // Form submission handling
                    document.getElementById('delReportlogForm').addEventListener('submit', function (e) {
                        e.preventDefault();


                        const selectEmpName = document.getElementById('selectEmpName').value;

                        const formData = new URLSearchParams({

                            'selectEmpName': selectEmpName,
                            'submit': 'deleteLogReport' // Ensure this is a string
                        });

                        fetch('function.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: formData.toString()
                        })
                            .then(response => response.json())
                            .then(result => {
                                // Log the entire response object
                                console.log('Response from server:', result);

                                if (result.status === 'success') {
                                    console.log(result.data);
                                    initializeDataTable('.datatables-logReport', result.data);
                                } else {
                                    console.error('Error:', result.message);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    });

                    document.getElementById('exportToExcel').addEventListener('click', function () {
                        const dataTable = $('.datatables-logReport').DataTable();

                        // Get all data from DataTable (not just the current page)
                        const allData = dataTable.rows().data().toArray();

                        // Log data to see if it's being retrieved correctly
                        //console.log(allData); 

                        // Prepare headers
                        const headers = [
                            'S.No', 'File Name', 'Deleted By', 'Deleted DateTime'

                        ];

                        // Prepare data for export
                        const exportData = allData.map((row, index) => {
                            // console.log(row); // Log each row to verify its structure
                            return {
                                'S.No': index + 1,
                                'File Name': row.fileURL || '',
                                'Deleted By': row.deleteBy || '',
                                'Deleted DateTime': row.deletedDateTime || ''
                                
                            };
                        });


                        console.log(exportData);

                        // Convert to worksheet and workbook only if exportData has data
                        if (exportData.length > 0) {
                            const worksheet = XLSX.utils.json_to_sheet(exportData, { header: headers });
                            const workbook = XLSX.utils.book_new();
                            XLSX.utils.book_append_sheet(workbook, worksheet, 'Report');
                            const logName = exportData.length > 0 ? exportData[0]['Deleted By'] : 'Delete Log'; 
                            const currentDate = new Date();
                            const formattedDate = ('0' + currentDate.getDate()).slice(-2) + '-' + 
                          ('0' + (currentDate.getMonth() + 1)).slice(-2) + '-' + 
                          currentDate.getFullYear();
                          // Default to 'User' if no name found
                            const fileName = `${logName} - Delete Log Report ${formattedDate}.xlsx`;

                            // Export to Excel
                            XLSX.writeFile(workbook, fileName);
                        } else {
                            alert("No data available to export.");
                        }
                    });


                    function initializeDataTable(selector, data) {
                        $('#noDatafound').hide();
                        $(selector).DataTable().clear().destroy(); // Clear and destroy any existing DataTable instance

                        const columns = [{
                            data: ''
                        },
                        {
                            data: 'ID'
                        },
                        {
                            data: 'fileURL'
                        },
                        {
                            data: 'deleteBy'
                        },
                        {
                            data: 'deletedDateTime'
                        }
                        ];

                        const columnDefs = [{
                            targets: 0,
                            render: function () {
                                return '';
                            }
                        },
                        {
                            targets: 1,
                            searchable: false,
                            orderable: true,
                            render: function (data, type, full, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            targets: 2,
                            responsivePriority: 1,
                            render: function (data, type, full) {
                                const filePath = full['fileURL'];
            const fileName = filePath.substring(filePath.lastIndexOf('/') + 1); // Get everything after the last '/'
            return '<span class="text-heading">' + fileName + '</span>';
                            }
                        },
                        {
                            targets: 3,
                            render: function (data, type, full) {
                                return '<span class="text-heading">' + full['deleteBy'] + '</span>';
                            }
                        },
                        {
                            targets: 4,
                            render: function (data, type, full) {
                                return '<span class="text-heading">' + full['deletedDateTime'] + '</span>';
                            }
                        }
                        
                        ];

                        $(selector).DataTable({
                            data: data,
                            columns: columns,
                            columnDefs: columnDefs,
                            order: [
                                [1, 'asc']
                            ],
                            language: {
                                sLengthMenu: '_MENU_',
                                search: ' ',
                                searchPlaceholder: 'Universal Search'
                            },
                            stateSave: true,
                            rowId: 'ID',
                            responsive: {
                                details: {
                                    display: $.fn.dataTable.Responsive.display.modal({
                                        header: function (row) {
                                            var data = row.data();
                                            return 'Details of ' + data['name'];
                                        }
                                    }),
                                    type: 'column',
                                    renderer: function (api, rowIdx, columns) {
                                        var data = $.map(columns, function (col) {
                                            return col.title !== '' // Do not show row in modal popup if title is blank
                                                ?
                                                '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                                '<td>' + col.title + ':</td>' +
                                                '<td>' + col.data + '</td>' +
                                                '</tr>' : '';
                                        }).join('');

                                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                                    }
                                }
                            }
                        });

                        // Additional styling adjustments
                        $('.dataTables_length').addClass('my-0');
                        $('.dt-action-buttons').addClass('pt-0');
                        $('.dataTables_filter input').addClass('ms-0');
                        $('.dt-buttons').addClass('d-flex flex-wrap');
                    }

                    function formatDate(date) {
                        const dateParts = date.split('-');
                        return `${String(dateParts[2]).padStart(2, '0')}-${String(dateParts[1]).padStart(2, '0')}-${dateParts[0]}`;
                    }

                    function formatTime(time) {
                        const timeParts = time.split(':');
                        let hours = parseInt(timeParts[0], 10);
                        const minutes = String(timeParts[1]).padStart(2, '0');
                        const period = hours >= 12 ? 'pm' : 'am';
                        hours = hours % 12 || 12;
                        return `${hours}:${minutes} ${period}`;
                    }


                });
            </script>