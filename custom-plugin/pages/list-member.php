<!DOCTYPE html>
<html lang="en">
<head>
  <title>All Members</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?php echo MMS_PLUGIN_URL ?>css/bootstrap.min.css">
 <link rel="stylesheet" href="<?php echo MMS_PLUGIN_URL ?>css/dataTables.min.css">
 
</head>
<body>
 
<div class="container">
  <h2>All Members</h2>
  <div class="panel panel-primary">
    <div class="panel-heading">All Members</div>
    <div class="panel-body">
  <p>The .table class adds basic styling (light padding and only horizontal dividers) to a table:</p>            
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
      <tr>
        <td>22</td>
        <td>Ashish</td>
        <td>ashish@gmail.com</td>
        <td>Male</td>
        <td>Tech Lead</td>
        <td>
            <a href="javascript:void(0);" class="btn btn-warning">Edit</a>
            <a href="javascript:void(0);" class="btn btn-info">View</a>
            <a href="javascript:void(0);" class="btn btn-danger">Delete</a>
        </td>
      </tr>
      <tr>
        <td>22</td>
        <td>Ashish</td>
        <td>ashish@gmail.com</td>
        <td>Male</td>
        <td>Coder</td>
        <td>
            <a href="javascript:void(0);" class="btn btn-warning">Edit</a>
            <a href="javascript:void(0);" class="btn btn-info">View</a>
            <a href="javascript:void(0);" class="btn btn-danger">Delete</a>
        </td>
      </tr>
    </tbody>
  </table>

    </div>
  </div>
</div>
<script src="<?php echo MMS_PLUGIN_URL ?>js/jquery.min.js"></script>
<script src="<?php echo MMS_PLUGIN_URL ?>js/bootstrap.min.js"></script>
<script src="<?php echo MMS_PLUGIN_URL ?>js/dataTables.min.js"></script>
<script>
    jQuery(document).ready(function(){
        new DataTable('#tbl-member');
    });
</script>
</body>
</html>





