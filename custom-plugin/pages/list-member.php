<?php

global $wpdb;

$all_members = $wpdb->get_results("SELECT * from {$wpdb->prefix}mms_form_data",ARRAY_A);

?>


<div class="container">
  <h2>All Members</h2>
  <div class="panel panel-primary">
    <div class="panel-heading">All Members</div>
    <div class="panel-body">            
  <table class="table" id="tbl-member">
    <thead>
      <tr>
        <th>#ID</th>
        <th>#Name</th>
        <th>#Email</th>
        <th>#Gender</th>
        <th>#Designation</th>
        <th>#Action</th>
      </tr>
    </thead>
    <tbody>
        <?php if(count($all_members) > 0 ){

foreach($all_members as $all_member){ ?>
  <tr>
        <td><?php echo $all_member['id']; ?></td>
        <td><?php echo $all_member['name']; ?></td>
        <td><?php echo $all_member['email']; ?></td>
        <td><?php echo ucfirst($all_member['gender']); ?></td>
        <td><?php echo $all_member['designation']; ?></td>
        <td>
            <a href="admin.php?page=member-system&action=edit&empId=<?php echo $all_member['id']; ?>" class="btn btn-warning">Edit</a>
            <a href="admin.php?page=member-system&action=view&empId=<?php echo $all_member['id']; ?>" class="btn btn-info">View</a>
            <a href="admin.php?page=list-member&action=delete&empId=<?php echo $all_member['id']; ?>" class="btn btn-danger">Delete</a>
        </td>
      </tr>
<?php
}
        }else{
echo "No member found";
        } ?>
    
    </tbody>
  </table>

    </div>
  </div>
</div>




