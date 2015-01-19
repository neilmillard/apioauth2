<?php include ('header.php'); ?>
<div class="row page-header-box">
    <div class="col-xs-10">
        <h3><?php echo  $title ?></h3>
    </div>
    <div class="col-xs-2">
        <a href="#" id="btn-user-add" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Add User</a>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th>#</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email Address</th>
                    <th style="width:15%" class="text-center">Action</th>
                </tr>
                </thead>
                <tbody id="user-table">
                <?php foreach( $users as $user){ ?>
                <tr id="user-row-<?php echo $user['id'] ?>">
                    <td><?php echo $user['id'] ?></td>
                    <td><?php echo $user['first_name'] ?></td>
                    <td><?php echo $user['last_name'] ?></td>
                    <td><?php echo $user['email'] ?></td>
                    <td class="text-center">
                        <a data-id="<?php echo $user['id'] ?>" class="btn btn-xs btn-primary btn-user-edit" href="#"><i class="fa fa-edit fa-fw"></i>Edit</a>
                        <a data-id="<?php echo $user['id'] ?>" class="btn btn-xs btn-danger btn-user-delete" href="#"><i class="fa fa-times fa-fw"></i>Remove</a>
                    </td>
                </tr>
                <?php  } //endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="user-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="modal-title">User Form</h4>
            </div>
            <div class="modal-body">
                <?php include ('form.php'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-user-save" data-method="">Save</button>
            </div>
        </div>
    </div>
</div>
<?php include ('footer.php'); ?>