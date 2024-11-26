<?php
session_start();

require_once __DIR__ . '/../../../../vendor/autoload.php';
//require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

include_once '../../includes/conn.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claimId'])) {
    $claimId = $_POST['claimId'];
	echo '<script>alert('. $claimId .')</script>';

    // Prepare and execute the SQL query to fetch the claim data
    //$sql = "SELECT
      //          cd.claimId,
      //          ud.userId,
     //           ud.first_name,
     //           ud.last_name,
     //           ud.other_names,
       //         ud.phone_number,
         //       ud.department AS user_department,
           //     ud.rank,
             //   ud.rate,
             //   cd.programme,
             //   cd.course,
           //     cdata.date AS claim_date,
   //             cdata.start_time,
     //           cdata.end_time,
     //           cdata.periods,
    //            bd.bank_name,
    //            bd.bank_branch,
    //            bd.account_number,
    //            bd.account_name
    //        FROM
//                claim_details cd
  //          JOIN
    //            user_details ud ON cd.userId = ud.userId
      //      JOIN
        //        claim_data cdata ON cd.claimId = cdata.claimId
//            JOIN
  //              user_bank_details bd ON ud.userId = bd.userId
    //        WHERE
      //          cd.claimId = ?";
	
	//Prepare and execute the SQL query to fetch the claim data
    $sql =	'SELECT
				cd.claimId,
				ud.userId,
				ud.first_name,
				ud.last_name,
				ud.other_names,
				ud.phone_number,
				ud.department AS user_department,
				ud.rank,
				ud.rate,
				cd.programme,
				cd.course,
				cdata.date AS claim_date,
				cdata.start_time,
				cdata.end_time,
				cdata.periods,
				bd.bank_name,
				bd.bank_branch,
				bd.account_number,
				bd.account_name
			FROM
				claim_details cd
			JOIN
				user_details ud ON cd.userId = ud.userId
			JOIN
				claim_data cdata ON cd.claimId = cdata.claimId
			JOIN
				user_bank_details bd ON ud.userId = bd.userId
			WHERE
				cd.claimId = ?;';


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $claimId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $claimDataList = $result->fetch_all(MYSQLI_ASSOC);

        // Path to the Word template
        $templatePath = __DIR__ . '/../../../../uploads/claim_form_template.docx';
        if (file_exists($templatePath)) {
            $templateProcessor = new TemplateProcessor($templatePath);

                // Set user and claim details from the first row
            $firstRow = $claimDataList[0];
            $templateProcessor->setValue('first_name', $firstRow['first_name']);
            $templateProcessor->setValue('last_name', $firstRow['last_name']);
            $templateProcessor->setValue('other_names', $firstRow['other_names']);
            $templateProcessor->setValue('phone_number', $firstRow['phone_number']);
            $templateProcessor->setValue('user_department', $firstRow['user_department']);
            $templateProcessor->setValue('rank', $firstRow['rank']);
            $templateProcessor->setValue('rate', $firstRow['rate']);
            $templateProcessor->setValue('programme', $firstRow['programme']);
            $templateProcessor->setValue('course', $firstRow['course']);
			
			// Initialize grand total
            $grandTotal = 0;

            // Handle multiple claim data entries
            $templateProcessor->cloneBlock('claim_data_block', 0, true, false, $claimDataList);
            foreach ($claimDataList as $index => $claimData) {
				
				   $periods = $claimData['periods'];
                $rate = $firstRow['rate']; // Assuming rate is constant for all periods in the claim
                
                $result = $periods * $rate;
                
                // Accumulate grand total
                $grandTotal += $result;
                
                // Set claim data and calculated result
                $templateProcessor->setValue('claim_date#' . ($index + 1), $claimData['claim_date']);
                $templateProcessor->setValue('start_time#' . ($index + 1), $claimData['start_time']);
                $templateProcessor->setValue('end_time#' . ($index + 1), $claimData['end_time']);
                $templateProcessor->setValue('periods#' . ($index + 1), $claimData['periods']);
				$templateProcessor->setValue('result#' . ($index + 1), $result);
            }
			
			// Set the grand total in the template
            $templateProcessor->setValue('grand_total', $grandTotal);

            // Additional bank details to be set
            $templateProcessor->setValue('bank_name', $firstRow['bank_name']);
            $templateProcessor->setValue('bank_branch', $firstRow['bank_branch']);
            $templateProcessor->setValue('account_number', $firstRow['account_number']);
            $templateProcessor->setValue('account_name', $firstRow['account_name']);
        

            // Save the populated Word document
            $outputPath = tempnam(sys_get_temp_dir(), 'claim_') . ".docx";
            $templateProcessor->saveAs($outputPath);
			
			

            // Output the file for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename='.$firstRow['last_name'].', '.$firstRow['first_name'].' - '.																				$firstRow['course'] . '.docx"');			
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($outputPath));
            ob_clean();
            flush();
            readfile($outputPath);
            unlink($outputPath); // Delete the temp file
            exit;
        } else {
            echo "Template file not found.";
        }
    } else {
        echo "No claim found with the provided claimId.";
    }
} else {
    echo "Invalid request.";
}
