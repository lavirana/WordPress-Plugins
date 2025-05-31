    <?php

    $message = "";
    $status =  "";
    if ($_SERVER['REQUEST_METHOD'] == "POST"  && isset($_POST['btn_submit'])) {

        // FORM SUBMITTED
        global $wpdb;

        $name =  sanitize_text_field($_POST['name']);
        $email = sanitize_text_field($_POST['email']);
        $phoneNo = sanitize_text_field($_POST['phoneNo']);
        $gender = sanitize_text_field($_POST['gender']);
        $designation = sanitize_text_field($_POST['designation']);

        //Insert Command
        $wpdb->insert("{$wpdb->prefix}mms_form_data", array(
            "name" => $name,
            "email" => $email,
            "phoneNo" => $phoneNo,
            "gender" => $gender,
            "designation" => $designation
        ));
        $last_inserted_id = $wpdb->insert_id;
        if ($last_inserted_id > 0) {
            $message = "Member saved successfully";
            $status = 1;
        } else {
            $message = "Failed to saved an member";
            $status = 0;
        }
    }
    ?>
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>Add Member</h2>
                <div class="panel panel-primary">
                    <div class="panel-heading">Add Member</div>
                    <div class="panel-body">
                <?php  if(!empty($message)){  
                        if($status == 1){  ?>
                        <div class="alert alert-success" ><?php echo $message; ?></div>
                        <?php
                        }else{ ?>
                        <div class="alert alert-danger" ><?php echo $message; ?></div>
                        <?php
                        }   
                  } ?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=member-system" method="post" id="mms-frm-add-member">
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" class="form-control" id="name" placeholder="Enter name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phoneNo">Phone no:</label>
                                <input type="text" class="form-control" id="phoneNo" placeholder="Enter Phone Number" name="phoneNo" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender:</label>
                                <select name="gender" id="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="designation">Designation</label>
                                <input type="password" class="form-control" id="designation" placeholder="Enter Designation" name="designation" required>
                            </div>
                            <div class="checkbox">
                                <label><input type="checkbox" name="remember"> Remember me</label>
                            </div>
                            <button type="submit" class="btn btn-success" name="btn_submit">Submit</button>
                        </form>


                    </div>
                </div>
            </div>
        </div>
    </div>