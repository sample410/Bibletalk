<div class="psa-starter__page psa-starter__page--split">
    <div class="psa-starter__column">
        <div class="psa-starter__welcome">
            <?php echo  __('Great! You’re done with the basics. You can start using your community right away, everything is already in place. Now for the finishing touches here is a list of things you should definitely check out:','peepso-core');?>
        </div>
        <hr>
		<div class="psa-starter__header">
			<h1 class="psa-starter__header-title"><?php echo  __('Widgets','peepso-core');?></h1>
			<p><?php echo  sprintf(__('PeepSo comes with a number of widgets. Place them in desired widget positions to give your community a more complete feel.<br/>You can manage widgets %s.','peepso-core'),'<a href="'.admin_url('widgets.php').'" target="_blank">'.__('here','peepso-core').' <i class="fa fa-external-link"></i></a>');?></p>
			<ul>
				<li><?php echo  __('PeepSo Profile Widget','peepso-core');?></li>
				<li><?php echo  __('PeepSo Latest Members Widget','peepso-core');?></li>
				<li><?php echo  __('PeepSo Online Members Widget','peepso-core');?></li>
				<li><?php echo  __('PeepSo User Bar Widget','peepso-core');?></li>
				<!--<li><?php /*echo  __('PeepSo Login Widget','peepso-core');*/?></li>-->
			</ul>
		</div>

		<hr class="psa-hr psa-hr--dashed">

		<div class="psa-starter__header">
			<h1 class="psa-starter__header-title"><?php echo  __('Menus','peepso-core');?></h1>
			<p><?php echo  sprintf(__('Now that PeepSo created its pages you might want to at least add a new menu item to link to your community.<br/>You can set manage menus %s.','peepso-core'),'<a href="'.admin_url('nav-menus.php').'" target="_blank">'.__('here','peepso-core').' <i class="fa fa-external-link"></i></a>');?></p>
		</div>

		<hr class="psa-hr psa-hr--dashed">

		<div class="psa-starter__header">
			<h1 class="psa-starter__header-title"><?php echo  __('Your community','peepso-core');?></h1>
		    <p><a href="<?php echo PeepSo::get_page('activity');?>" target="_blank"><?php echo __('Your Community','peepso-core');?> <i class="fa fa-external-link"></i></a></a> - <?php echo __('take a look at your community now!','peepso-core');?></p>
		    <p><a href="<?php echo admin_url('admin.php?page=peepso_config');?>" target="_blank"><?php echo __('Configuration','peepso-core');?> <i class="fa fa-external-link"></i></a></a> - <?php echo __('configure every aspect of your community.','peepso-core');?></p>
		    <p><a href="<?php echo admin_url('admin.php?page=peepso');?>" target="_blank"><?php echo __('Dashboard','peepso-core');?> <i class="fa fa-external-link"></i></a></a> - <?php echo __('get an overview of the latest posts, comments, reports and more.','peepso-core');?></p>

		</div>
    </div>
