<style>
    form {
  max-width: 500px;
  background: #fff;
  padding: 20px 24px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  font-family: Arial, sans-serif;
}

h3 {
  font-size: 20px;
  color: #333;
  margin-bottom: 20px;
}

p {
  margin-bottom: 16px;
}

label {
  font-weight: bold;
  display: block;
  margin-bottom: 6px;
  color: #444;
}

input[type="file"] {
  display: block;
  width: 100%;
  padding: 6px;
  font-size: 14px;
}

button[type="submit"] {
  padding: 10px 16px;
  background-color: #2271b1;
  color: white;
  font-weight: 600;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

button[type="submit"]:hover {
  background-color: #135e96;
}


</style>
<p id="show_upload_message" ></p>
<form action="javascript:void(0)" id="frm-csv-upload" enctype="multipart/form-data">
    <p>
        <label for="csv_data_file">Upload CSV File</label>
        <input type="file" name="csv_data_file" id="csv_data_file">
        <input type="hidden" name="action" value="cdu_submit_form_data">
    </p>
    <p>
        <button type="submit">Upload CSV</button>
    </p>
</form>