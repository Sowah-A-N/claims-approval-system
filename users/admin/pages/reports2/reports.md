Claims Submitted and Approved by Date

    Description: Shows the number of claims submitted and approved over time.

    SELECT cd.date, COUNT(cd.claimId) AS total_submitted, 
       COUNT(cc.claimId) AS total_completed
FROM claim_data cd
LEFT JOIN completed_claims cc ON cd.claimId = cc.claimId
GROUP BY cd.date;
Representation: Line chart showing trends over time with two lines (submitted vs completed).

Flagged Claims Report

    Description: Details on all flagged claims including the reason and stage at which they were flagged.

SELECT fc.claimId, fc.userId, d.dept_name, fc.programme, fc.course, fc.flagged_by, fc.flagged_at_stage, fc.flagged_msg
FROM flagged_claims fc
JOIN department d ON fc.department = d.deptId;

Representation: Table with columns for claim ID, user ID, department, programme, course, flagged by, flagged at stage, and message.

Claims by Department and Programme

    Description: Number of claims per department and programme.

    SELECT d.dept_name, c.programme, COUNT(cd.claimId) AS total_claims
FROM claim_details cd
JOIN department d ON cd.department = d.deptId
JOIN course c ON cd.course = c.id
GROUP BY d.dept_name, c.programme;

Representation: Bar chart with departments on the x-axis and the number of claims on the y-axis, color-coded by programme.

Approver Efficiency Report

    Description: Tracks the number of claims each approver has processed and the average time taken.

    SELECT ad.email, ar.name AS approver_rank, COUNT(cd.claimId) AS total_processed, 
       AVG(TIMESTAMPDIFF(MINUTE, cd.time_submitted, cc.time_completed)) AS avg_time_minutes
FROM approver_details ad
JOIN claim_details cd ON ad.id = cd.stage
JOIN completed_claims cc ON cd.claimId = cc.claimId
JOIN approver_ranks ar ON ad.approver_rank = ar.rank
GROUP BY ad.email, ar.name;

Representation: Table with columns for approver email, rank, total processed claims, and average processing time.

Department Overview Report

    Description: Provides a summary of all departments including faculty name and head of department.

    SELECT dept_name, faculty_name, head_of_dept FROM department;
Representation: Table with columns for department name, faculty name, and head of department.