    <div class="container">
        <div class="row" >
            <div class="col-sm-12" >
            <h2>Add Member</h2>
        <div class="panel panel-primary">
            <div class="panel-heading">Add Member</div>
            <div class="panel-body">

                <form action="javascript:void(0);" method="post" id="mms-frm-add-member">
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
                        <input type="password" class="form-control" id="phoneNo" placeholder="Enter Phone Number" name="phoneNo" required>
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
                    <button type="submit" class="btn btn-success">Submit</button>
                </form>


            </div>
        </div>
            </div>
        </div>
    </div>