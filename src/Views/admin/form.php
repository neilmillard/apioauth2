<form role="form" class="form-horizontal" id="user-form-data">
    <input type="hidden" id="user_id" name="id">

    <div class="form-group">
        <label for="user_email" class="col-lg-4 control-label">Login Email</label>
        <div class="col-lg-8">
            <input type="text" class="input-sm form-control validate[required]" name="email" id="user_email" placeholder="Login Email">
        </div>
    </div>

    <div class="form-group">
        <label for="first_name" class="col-lg-4 control-label">First Name</label>
        <div class="col-lg-8">
            <input type="text" class="input-sm form-control validate[required]" name="first_name" id="user_first_name" placeholder="First Name">
        </div>
    </div>

    <div class="form-group">
        <label for="last_name" class="col-lg-4 control-label">Last Name</label>
        <div class="col-lg-8">
            <input type="text" class="input-sm form-control" name="last_name" id="user_last_name" placeholder="Last Name">
        </div>
    </div>

    <div class="form-group">
        <label for="group" class="col-lg-4 control-label">Group</label>
        <div class="col-lg-8">
            <select name="group" id="group" multiple="true" class="input-sm form-control">
                <?php foreach ($groups as $group){ ?>
                <option value="<?php echo $group.id ?>"><?php echo $group.name ?></option>
                <?php } //endforeach ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="password" class="col-lg-4 control-label">Password</label>
        <div class="col-lg-8">
            <input type="password" class="input-sm form-control validate[required,minSize[6],maxSize[50]]" name="password" id="password" placeholder="Password">
        </div>
    </div>

    <div class="form-group">
        <label for="confirm_password" class="col-lg-4 control-label">Confirm Password</label>
        <div class="col-lg-8">
            <input type="password" class="input-sm form-control validate[required,equals[password]]" name="confirm_password" id="confirm_password" placeholder="Confirm password">
        </div>
    </div>
</form>