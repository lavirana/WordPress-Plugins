<?php

global $wpdb;
$message = "";


//Delete Member
if($_SERVER['REQUEST_METHOD'] == "POST"){
  if(isset($_POST['mem_del_id']) && !empty($_POST['mem_del_id'])){
$wpdb->delete("{$wpdb->prefix}mms_form_data", array(
    "id" => intval($_POST['mem_del_id'])
));
$message = "Mmember has been deleted successfully";
  }
}
$all_members = $wpdb->get_results("SELECT * from {$wpdb->prefix}mms_form_data",ARRAY_A);

?>


<div class="container">
  <h2>All Members</h2>
  <div class="panel panel-primary">
    <div class="panel-heading">All Members</div>
    <div class="panel-body">     
    
    <?php if(!empty($message)){
        ?>
<div class="alert alert-success">
    <?php echo $message; ?>
</div>

<?php
    } ?>
    
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
            <a href="admin.php?page=member-system&action=edit&mem=<?php echo $all_member['id']; ?>" class="btn btn-warning">Edit</a>

            <form method="post" id="frm-delete-member-<?php echo $all_member['id']; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=list-member">
                <input type="hidden" name="mem_del_id" value="<?php echo $all_member['id']; ?>">
            </form>
            <a href="admin.php?page=member-system&action=view&mem=<?php echo $all_member['id']; ?>" class="btn btn-info">View</a>
            <a href="javascript:void(0);" onclick="if(confirm('Are you sure you want to delete?')){
            jQuery('#frm-delete-member-<?php echo $all_member['id']; ?>').submit();
            }" class="btn btn-danger">Delete</a>
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




