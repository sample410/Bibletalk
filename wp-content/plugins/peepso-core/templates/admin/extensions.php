<?php

$categories = array();
$products = array();
$data = array();

if (is_object($plugins) && count($plugins->products) > 0) {
	foreach ($plugins->products as $product) {
	    $is_bundle = FALSE;
		$product_categories = array();
		$product_tags = array();

		// product data
		$id = $product->info->id;


		$title = addslashes($product->info->title);

		// Exclude Foundation, Profiles, BlogPosts, Reactions, Hashtags & MarkDown
        if(in_array($id, array(231, 2882624, 72278, 79603, 43454, 2676480))) { continue; }

        // Exclude CM
        if(strstr($product->info->title, 'CM ')) {
            continue;
        }

		$content = addslashes(wp_strip_all_tags($product->info->content));
		$content = trim(preg_replace('/\s+/', ' ', $content));

		// product categories
		$product->button_label = __('Get this addon', 'peepso-core');

		if (is_array($product->info->category)) {
			foreach ($product->info->category as $category) {
				$product_categories[] = $category->slug;
				$categories[$category->slug] = $category->name;

				if ($category->slug == 'bundle') {
                    $is_bundle = TRUE;
					$product->button_label = __('Get this bundle', 'peepso-core');
				}
			}
		}

		// if has bundle already
        if(PeepSo::get_option('bundle',0) ) {
            $product->button_label = __('Download', 'peepso-core');
            $product->info->link = 'https://www.peepso.com/peepso-ultimate/';
        }

		// product tags
		$product->is_installed = FALSE;
		if ($product->info->tags) {
			foreach ($product->info->tags as $tag) {
				$product_tags[] = strtolower($tag->name);

				if (class_exists($tag->slug)) {
					$product->is_installed = TRUE;
				}
			}
		}


        // Exclude Bundles
		if(!$is_bundle) {

            $data[] = array(
                'id' => $id,
                'title' => $title,
                'categories' => $product_categories,
                'tags' => $product_tags,
                'content' => $content
            );

            $products[implode('.',$product_categories).'.'.$title] = $product;
        }
	}
}

ksort($products);

// sort category list
asort($categories);
$categories = array_merge(array('all' => 'All'), $categories);

?>

<div class="ps-js-extensions">
    <center>
        <?php if(0==PeepSo::get_option('bundle',0)) { ?>
            <div class="psa-starter__bundle">
                <?php

                $bundle = get_transient('peepso_config_licenses_bundle');

                if (!strlen($bundle)) {
                    $url = PeepSoAdmin::PEEPSO_URL . '/peepsotools-integration-json/peepso_config_licenses_bundle.html';

                    // Attempt contact with PeepSo.com without sslverify
                    $resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => FALSE));

                    // In some cases sslverify is needed
                    if (is_wp_error($resp)) {
                        $resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => TRUE));
                    }

                    if (is_wp_error($resp)) {

                    } else {
                        $bundle = $resp['body'];
                        set_transient('peepso_config_licenses_bundle', $bundle, 3600 * 24);
                    }
                }

                echo $bundle;
                ?>
            </div>
        <?php } ?>
    </center>
	<?php if (count($categories) > 0) { ?>
	<ul class="ps-extensions__tabs ps-js-tabs">
		<div class="ps-extensions__search">
			<input type="text" value="" placeholder="<?php echo __('Enter a keyword...', 'peepso-core'); ?>" />
		</div>
		<?php foreach ($categories as $key => $value) {

		    if(in_array(strtolower($key), array('thirdparty','bundle','foundation'))) {
		        continue;
		    }

        ?>
		<li <?php echo $key === 'all' ? ' class="active"' : ''; ?>>
			<a href="#" onclick="return false;" class="plugin-type ps-js-tab" title="<?php echo $value; ?>" data-slug="<?php echo $key; ?>"><?php echo $value; ?></a>
		</li>
		<?php } ?>
		<li class="ps-extensions__hide ps-js-toggle-installed">
			<a href="#" onclick="return false;">
				<i class="dashicons dashicons-visibility"></i>
				<span><?php echo __('Hide Active Plugins', 'peepso-core'); ?></span>
			</a>
		</li>
	</ul>
	<?php } ?>

	<div class="row ps-extensions peepso-extensions ps-js-list">
		<?php

		if (count($products) > 0) {
			foreach ($products as $key => $product) {

			    // BpMigrator
			    if(46027 == $product->info->id) { continue; }

                if(!PeepSo::get_option('bundle',0) ) {
                    $product->info->link = 'https://peepso.com/?post_type=download&p=' . $product->info->id;
                }


				if (!$product->is_installed) {
				?>
				<div class="col-md-4 ps-extension__item ps-js-extension" data-id="<?php echo $product->info->id; ?>">
					<div class="edd-extension">
						<a class="ps-extension__image" title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>">
							<img width="880" height="440" title="<?php echo $product->info->title; ?>" alt="<?php echo str_replace(' ', '-', strtolower($product->info->title)) . '-image'; ?>" class="attachment-showcase size-showcase wp-post-image" src="<?php echo $product->info->thumbnail; ?>">
						</a>
						<div class="ps-extension__desc ps-js-description">
                            <h3><a title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>"><?php echo $product->info->title; ?></a><br/><small><?php $key=explode('.',$key);echo ucfirst($key[0]); ?></small></h3>
							<p><?php echo wp_trim_words($product->info->content, 30); ?></p>
						</div>
						<a class="ps-extension__btn" target="_blank" title="<?php echo $product->info->title; ?>" href="<?php echo $product->info->link; ?>"><?php echo $product->button_label; ?></a>
					</div>
				</div>
				<?php } else { ?>
				<div class="col-md-4 ps-extension__item ps-extension__item--installed ps-js-extension ps-js-installed" data-id="<?php echo $product->info->id; ?>">
					<div class="edd-extension">
						<a class="ps-extension__image" title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>">
							<img width="880" height="440" title="<?php echo $product->info->title; ?>" alt="<?php echo str_replace(' ', '-', strtolower($product->info->title)) . '-image'; ?>" class="attachment-showcase size-showcase wp-post-image" src="<?php echo $product->info->thumbnail; ?>">
						</a>
						<div class="ps-extension__desc ps-js-description">
                            <h3><a title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>"><?php echo $product->info->title; ?></a><br/><small><?php $key=explode('.',$key);echo ucfirst($key[0]); ?></small></h3>

							<p><?php echo wp_trim_words($product->info->content, 30); ?></p>
						</div>
						<span class="ps-extension__btn"><?php echo __('Installed and Activated', 'peepso-core'); ?></span>
						<div class="ps-extension__check"><i class="dashicons dashicons-yes"></i></div>
					</div>
				</div>
				<?php

				}
			}
		} else {
			echo __('Please try again later', 'peepso-core');
		}

		?>
		<script>
		peepsoextdata = <?php echo json_encode( array(
			'spinner' => PeepSo::get_asset('images/ajax-loader.gif'),
			'extensions' => $data
		)); ?>;
		</script>
		<div class="all-installed col-md-12 ps-js-all-installed" style="display:none;"><?php echo __('All plugins in this category are already installed and activated on your site. To view other plugins, not yet installed or activated on your site, click the button below.', 'peepso-core'); ?></div>
	</div>
</div>
