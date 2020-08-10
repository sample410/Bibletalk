<div id="gs_menu" class="psa-starter__menu">

<?php foreach($steps as $step_id=>$label) { ?>
    <a class="psa-starter__menu-item <?php if($step_id==$step) echo 'active';?>" href="<?php echo admin_url('admin.php?page=peepso-getting-started&section=peepso&step='.$step_id);?>">
        <?php echo $label;?>
    </a>
<?php } ?>

</div>

<div id="gs_container" class="psa-starter__content">