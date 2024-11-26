<?php
    // Include session handling or database connection setup
    // include_once "../../includes/conn.inc.php";

    // Set the page title
    $pageTitle = "Reports";

    // Include database connection
    include_once "../../includes/conn.inc.php";

    // Query to fetch daily submitted claims
    $dailySubmittedClaimsQuery = "SELECT date, COUNT(*) AS total_submitted
                                  FROM claim_data
                                  GROUP BY date;";

    // Execute query
    $dailySubmittedClaimsResult = mysqli_query($conn, $dailySubmittedClaimsQuery);

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

    // Close connection
    mysqli_close($conn);
?>

<?php
    $claimsStatusByDate = "SELECT cd.date, COUNT(cd.claimId) AS total_submitted, 
                            COUNT(cc.claimId) AS total_completed
                            FROM claim_data cd
                            LEFT JOIN completed_claims cc ON cd.claimId = cc.claimId
                            GROUP BY cd.date;";

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
    
    // Create a new Chart
    var ctx = document.getElementById('dailySubmittedClaims').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar', // Changed to bar chart type
        data: {
            labels: dailySubmittedClaimsData.labels,
            datasets: [{
                label: 'Daily Submitted Claims',
                data: dailySubmittedClaimsData.data,
                backgroundColor: 'rgba(54, 162, 235, 0.375 )', // Semi-transparent blue
                borderColor: 'rgba(54, 162, 235, 1)', // Blue
                borderWidth: 1        }]
        },
        options: {
            scales: {
                x: {
                    //type: 'time',
                    time: {
                        unit: 'day',
                        tooltipFormat: 'MMM DD, YYYY',
                        displayFormats: {
                            day: 'MMM DD, YYYY'
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    // ticks: {
                    //     source: 'data'
                    // }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Daily Submitted Claims'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            responsive: true,
            maintainAspectRatio: true,
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10
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
