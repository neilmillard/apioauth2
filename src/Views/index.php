<?php require 'header.php' ?>

    <p>Welcome to my home page for a context sensitive login/logout sample application.</p>

<?php if (!empty($user)):?>
    <p>Hi <?=$user?>
<?php endif;?>

<?php require 'footer.php' ?>