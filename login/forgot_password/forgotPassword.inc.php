<?php

include "datacon.php";
    
 if(mysqli_connect_errno())
 {
     echo "Failed to connect to MYSQli:".mysqli_connect_error();
 }

 if(isset($_POST['getCode']))
 {
    $role=mysqli_real_escape_string($conn,$_POST['role']);

     //Error Handlers
     //Checking for empty fields

     if(empty($email_address))
     {
         echo "<script> alert('Check Details'); window.location='//forgot_password/' </script> ";  
         exit();
     }
     
    else
    {
        $sql="SELECT  FROM password_reset_code WHERE user_email= '$email_address'";
        $result= mysqli_query($conn, $sql);
        $row= mysqli_fetch_array($result);
        $code_status=$row['code_status'];
        if($code_status==1)
        {
            $sql="DELETE FROM password_reset_code WHERE user_email = '$email_address' ";
            $result= mysqli_query($conn, $sql);
            $sql="SELECT user_email FROM user_logs WHERE user_email= '$email_address'";
            $result= mysqli_query($conn, $sql);
            $row= mysqli_fetch_array($result);
            $check_email_address=$row['user_email'];   
        
            if($email_address==$check_email_address)
            {
                $user_email=$check_email_address;                        
                $confirm_code=substr(str_shuffle("0123456789"), -4);
                
                $hashed_password= password_hash($confirm_code, PASSWORD_DEFAULT);  
                $query="INSERT INTO password_reset_code (user_email, password_reset_code) VALUES ('$email_address','$hashed_password') ";
                $result=mysqli_query($conn, $query);
                if(!$result)
                {
                    echo "Not Inserted";
                    echo "Errormessage:".mysqli_error($conn);
                                
                }    
                echo "<script> alert('Use this code to reset your password:$confirm_code'); window.location='/timetableGenerator/reset_password/' </script> ";  
                exit();
                                                         
                    
                 }
                 else
                 {
                    echo "<script> alert('Check your details'); window.location='/timetableGenerator/forgot_password/' </script> ";  
                    exit();

                 }
        }
        else
        {
            $sql="SELECT user_email FROM user_logs WHERE user_email= '$email_address'";
            $result= mysqli_query($conn, $sql);
            $row= mysqli_fetch_array($result);
            $check_email_address=$row['user_email'];   
        
            if($email_address==$check_email_address)
            {
                $user_email=$check_email_address;                        
                $confirm_code=substr(str_shuffle("0123456789"), -4);
                
                $hashed_password= password_hash($confirm_code, PASSWORD_DEFAULT);  
                $query="INSERT INTO password_reset_code (user_email, password_reset_code) VALUES ('$email_address','$hashed_password') ";
                $result=mysqli_query($conn, $query);
                if(!$result)
                {
                    echo "Not Inserted";
                    echo "Errormessage:".mysqli_error($conn);
                                
                }    
                echo "<script> alert('Use this code to reset your password:$confirm_code'); window.location='/timetableGenerator/reset_password/' </script> ";  
                exit();
                                                         
                    
                 }
                 else
                 {
                    echo "<script> alert('Check your details'); window.location='/timetableGenerator/forgot_password/' </script> ";  
                    exit();

                 }

        }
    }
        

}


else
{
                echo "<script> alert('Check Details'); window.location='/timetableGenerator/forgot_password/';</script> ";
}

 
?> 