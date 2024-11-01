<?php

defined( 'WPINC' ) or die;

/**
 * The list for the dashboard widget.
 *
 * @var array $items
 */
?>

<div class="simple-todo-list-wrap">

	<div class="simple-todo-list-ajax-ui">
		<span class="spinner is-active alignleft"></span>
		<em><?php _e( 'Loading...', 'simple-todo-list' ); ?></em>
		<div class="clear"></div>
	</div>

	<div class="simple-todo-list-ajax-ui-blocker">
	</div>

	<section class="simple-todo-list-actions">
		<a href="#" class="alignleft simple-todo-list-action simple-todo-list-clear-list" data-todo-action="clear_list" data-wp-nonce="<?php echo esc_attr( wp_create_nonce( 'simple-todo-list-clear-list' ) ); ?>"><?php _e( 'Clear', 'simple-todo-list' ); ?></a>
		<a href='#' class="alignright simple-todo-list-cancel-add-item"><?php _e( 'Cancel', 'simple-todo-list' ); ?></a>
		<a href="#" class="alignright simple-todo-list-add-item-toggle"><?php _e( 'Add Item', 'simple-todo-list' ); ?></a>
		<div class="clear"></div>
	</section>

	<section class="simple-todo-list-add-item-form">
		<form id="simple-todo-list-create-new-item">
			<table class="form-table simple-todo-list-add-item-form">
				<tbody>
					<tr>
						<td colspan="2"><input type="text" id="simple-todo-list-new-item-content" name="simple_todo_list_new_item_content" placeholder="<?php esc_attr_e( 'Todo...', 'simple-todo-list' ); ?>" class="widefat"></td>
						<td colspan="1"><a href="#" class="button button-secondary alignright simple-todo-list-action simple-todo-list-add-item" data-todo-action="add_item" data-wp-nonce="<?php echo esc_attr( wp_create_nonce( 'simple-todo-list-add-item' ) );  ?>"><?php _e( 'Save', 'simple-todo-list' ); ?></a></td>
					</tr>
				</tbody>
			</table>
		</form>
		<div class="clear"></div>
	</section>

	<section class="simple-todo-list">

		<table class="form-table">
			<tbody>
			<?php foreach ( $items as $i => $item ) : ?>
				<?php $is_done = 'is_done' == get_post_meta( $item->ID, Simple_Todo_List_Plugin::STATUS_META, true ) ? true : false; ?>
				<tr data-todo-item-id="<?php echo absint( $item->ID ); ?>" class="<?php echo esc_attr( $is_done ? 'simple-todo-list-is-done' : '' ); ?>">
					<td colspan="3"><input type="checkbox" data-wp-nonce="<?php echo esc_attr( wp_create_nonce( 'simple-todo-list-do-item-' . $item->ID ) ); ?>" class="alignleft simple-todo-list-do-item"><span data-wp-nonce="<?php echo esc_attr( wp_create_nonce( 'simple-todo-list-do-item-' . $item->ID ) ); ?>" class="simple-todo-list-do-item dashicons dashicons-yes"></span> <span class="simple-todo-list-item-content"><?php echo wp_kses_post( Slimdown::render( $item->post_content ) ); ?></span></td>
					<td colspan="1">
						<span class="simple-todo-list-time-diff alignright"><?php printf( __( '%s ago', 'simple-todo-list' ), human_time_diff( mysql2date( 'U', $item->post_date ), current_time( 'timestamp') ) ); ?></span>
						<span class="simple-todo-list-quick-links alignright"><a href="#" class="simple-todo-list-action simple-todo-list-delete-item" data-todo-action="delete_item" data-wp-nonce="<?php echo esc_attr( wp_create_nonce( 'simple-todo-list-delete-item-' . $item->ID ) ); ?>"><button class="notice-dismiss" type="button"><span class="screen-reader-text"><?php esc_html_e( 'Delete this todo.', 'simple-todo-list' ); ?></span></button></a></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	
		<script type="text/template" id="simple-todo-list-single-todo">
			<tr data-todo-item-id="<%- data.post_id %>">
				<td colspan="3"><input type="checkbox" data-wp-nonce="<%- data.do_item_nonce %>" class="alignleft simple-todo-list-do-item"><span data-wp-nonce="<%- data.do_item_nonce %>" class="simple-todo-list-do-item dashicons dashicons-yes"></span> <span class="simple-todo-list-item-content"><%= data.post_content %></span></td>
				<td colspan="1">
					<span class="simple-todo-list-time-diff alignright"><%- data.time_diff %></span>
					<span class="simple-todo-list-quick-links alignright"><a href="#" class="simple-todo-list-action simple-todo-list-delete-item" data-todo-action="delete_item" data-wp-nonce="<%- data.delete_nonce %>"><button class="notice-dismiss" type="button"><span class="screen-reader-text"><%- data.delete_string %></span></button></a></span>
				</td>
			</tr>
		</script>
	</section>

</div><!-- /simple-todo-list-wrap -->