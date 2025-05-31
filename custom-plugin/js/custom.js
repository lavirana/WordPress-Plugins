jQuery(document).ready(function(){
    //list member data table
    new DataTable('#tbl-member');

    //member form validation
    jQuery("#mms-frm-add-member").validate();
});

