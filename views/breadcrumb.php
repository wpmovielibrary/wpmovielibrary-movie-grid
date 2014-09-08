
				<ul id="wpmlmg-breadcrumb-list" class="wpmlmg-breadcrumb-list">

					<?php foreach ( $default as $letter ) : ?><li id="wpmlmg-breadcrumb-list-item-<?php echo $letter ?>" class="wpmlmg-breadcrumb-list-item<?php if ( strtolower( $letter ) == strtolower( $current ) ) echo ' active'; ?>"><?php if ( in_array( $letter, $letters ) ) { ?><a href="<?php echo $url . $letter; ?>"><?php echo $letter; ?></a><?php } else { echo $letter; } ?></li><?php endforeach; ?>
				</ul>
