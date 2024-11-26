<?php
    // Include session handling (if needed)

    // Set the page title
    $pageTitle = "Reports";

    // Include database connection
    include_once "../../includes/conn.inc.php";

    // Query to fetch daily submitted claims
    $dailySubmittedClaimsQuery = "
        SELECT date, COUNT(*) AS total_submitted
        FROM claim_data
        GROUP BY date
    ";
    $topFiveClaimantsQuery = "SELECT userId, COUNT(*) AS submitted_count 
                                FROM claim_details 
                                GROUP BY userId 
                                ORDER BY submitted_count DESC LIMIT 5;";
                                
    // Execute query
    $dailySubmittedClaimsResult = mysqli_query($conn, $dailySubmittedClaimsQuery);
    $topFiveClaimantsResult = mysqli_query($conn,$topFiveClaimantsQuery);

    // Initialize arrays to store labels and data for chart
    $dailySubmittedClaimsData = [
        'labels' => [],
        'data' => []
    ];

    // Fetch results and populate data arrays
    while ($row = mysqli_fetch_assoc($dailySubmittedClaimsResult)) {
        $dailySubmittedClaimsData['labels'][] = $row['date'];
        $dailySubmittedClaimsData['data'][] = $row['total_submitted'];
    }
?>

<!DOCTYPE html>
<html lang="en">

<?php include "../../assets/partials/head.php"; ?>

<body>
    <?php include '../../assets/partials/sidebar.php'; ?>

    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        <div class="body-wrapper">
            <?php include '../../assets/partials/header.php'; ?>

            <div class="container-fluid">
                <h3>Daily Submitted Claims</h3>
                <div>
                    <canvas id="dailySubmittedClaims"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // PHP to JavaScript data conversion
        var dailySubmittedClaimsData = <?php echo json_encode($dailySubmittedClaimsData); ?>;

        // Create a new Chart with data
        var ctx = document.getElementById('dailySubmittedClaims').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailySubmittedClaimsData.labels,
                datasets: [{
                    label: 'Total Submitted Claims',
                    data: dailySubmittedClaimsData.data,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    x: {
                        //type: 'time', // Use 'time' for time-based data
                        time: {
                            parser: 'YYYY-MM-DD', // Specify your date format
                            tooltipFormat: 'MMM DD, YYYY', // Format for tooltips
                            unit: 'day', // Display unit: 'day', 'week', 'month', etc.
                            displayFormats: {
                                day: 'MMM DD, YYYY' // Format for display on X-axis
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        ticks: {
                            source: 'data' // Use 'data' as source to ensure correct scale
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Daily Submitted Claims'
                        }
                    }
                }
            }
        });
    </script>

    <script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebarmenu.js"></script>
    <script src="../../assets/js/app.min.js"></script>
    <script src="../../assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>
