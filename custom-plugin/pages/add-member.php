    <?php

    $message = "";
    $status =  "";
    $action = "";
    $mem = "";
    $title = "ADD MEMBER";

    //find request for view and edit
    if(isset($_GET['action']) && isset($_GET['mem'])){

        global $wpdb;
        $mem = $_GET['mem'];
                //Action : Edit
                if($_GET['action'] == 'edit'){
                    $action = "edit";
                    $title = "Edit Member";
                }

                //Action : View
                if($_GET['action'] == 'view'){
                    $action = "view";
                    $title = "View Member";
                }
                //Single employee information
$member = $wpdb->get_row(
    $wpdb->prepare("SELECT * from {$wpdb->prefix}mms_form_data where id = %d", $mem), ARRAY_A
);


    }

    //save form data
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
                <h2><?php if(isset($title)) { echo $title; } ?></h2>
                <div class="panel panel-primary">
                 
                    <div class="panel-heading"><?php if(isset($title)) { echo $title; } ?></div>
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
                                <input type="text" value="<?php if($action == 'view') echo $member['name'];  ?>" class="form-control" id="name" placeholder="Enter name" name="name" required <?php if($action == 'view'){ echo "readonly='readonly'"; } ?>>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" value="<?php if($action == 'view') echo $member['email'];  ?>"  class="form-control" id="email" placeholder="Enter email" name="email" required <?php if($action == 'view'){ echo "readonly='readonly'"; } ?>>
                            </div>
                            <div class="form-group">
                                <label for="phoneNo">Phone no:</label>
                                <input type="text" value="<?php if($action == 'view') echo $member['phoneNo'];  ?>"  class="form-control" id="phoneNo" placeholder="Enter Phone Number" name="phoneNo" required <?php if($action == 'view'){ echo "readonly='readonly'"; } ?>>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender:</label>
                                <select <?php if($action == 'view'){ echo "disabled"; } ?> name="gender" id="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male"
                                    <?php if($action == 'view' && $member['gender'] == 'male'){ echo "selected"; } ?>
                                    >Male</option>
                                    <option value="female"
                                    <?php if($action == 'view' && $member['gender'] == 'female'){ echo "selected"; } ?>
                                    >Female</option>
                                    <option <?php if($action == 'view' && $member['gender'] == 'other'){ echo "selected"; } ?> value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="designation">Designation</label>
                                <input type="text" value="<?php if($action == 'view') echo $member['designation'];  ?>"  class="form-control" id="designation" placeholder="Enter Designation" name="designation" required <?php if($action == 'view'){ echo "readonly='readonly'"; } ?>>
                            </div>
                            <?php if($action != 'view'){ ?>
                                <button type="submit" class="btn btn-success" name="btn_submit">Submit</button>
                          <?php  } ?>      
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>