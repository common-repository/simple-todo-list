<?php
/*
 * Plugin Name: Simple Todo List
 * Plugin URI: http://wordpress.org/plugins/simple-todo-list
 * Description: A very simple todo list plugin for the WordPress dashboard.
 * Author: George Gecewicz
 * Version: 1.0.1
 * Author URI: https://profiles.wordpress.org/ggwicz/
 * License: GPLv2 or later
 * Text Domain: simple-todo-list
 *
 * Copyright 2015 George Gecewicz
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

class Simple_Todo_List_Plugin {

	const VERSION     = '1.0.1';
	const POST_TYPE   = 'simple_todo_list';
	const STATUS_META = '_simple_todo_list_item_status';

	static $instance  = false;

	function __construct() {
		include_once plugin_dir_path( __FILE__ ) . 'vendor/slimdown/slimdown.php';

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init',                  array( $this, 'init' ) );
		add_action( 'wp_dashboard_setup',    array( $this, 'wp_dashboard_setup' ) );
		
		$this->ajax_hooks();
	}

	function admin_enqueue_scripts() {
		wp_register_style( 'simple-todo-list', plugin_dir_url( __FILE__ ) . 'assets/admin.css', array( 'dashicons' ), self::VERSION, 'all' );
		wp_register_script( 'simple-todo-list', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array( 'jquery', 'underscore' ), self::VERSION, true );

		wp_localize_script( 'simple-todo-list', 'simple_todo_list_i18n', array(
			'clear_list' => esc_html__( 'Are you sure you want to clear the list? This will delete all Todo items!', 'simple-todo-list' )
		) );
	}

	function ajax_hooks() {
		foreach ( array( 'add_item', 'clear_list', 'delete_item', 'do_item', 'undo_item' ) as $action ) {
			add_action( 'wp_ajax_simple_todo_list_' . $action, array( $this, 'wp_ajax_' . $action ) );
		}
	}

	function get_items() {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => 'modified'
		);

		return get_posts( apply_filters( 'simple_todo_list_get_items_args', $args ) );
	}

	static function get_instance() {
		if ( ! self::$instance instanceof Simple_Todo_List_Plugin )
			self::$instance = new self;

		return self::$instance;
	}

	function init() {

		$args = array(
			'show_ui'  => false,
			'public'   => true,
			'supports' => array( 'title', 'custom-fields' )
		);

		register_post_type( self::POST_TYPE, apply_filters( 'simple_todo_list_post_type', $args ) );
	}

	function wp_add_dashboard_widget() {
		$items = $this->get_items();

		wp_enqueue_style( 'simple-todo-list' );
		wp_enqueue_script( 'simple-todo-list' );

		include plugin_dir_path( __FILE__ ) .  'views/list.php';
	}

	function wp_ajax_add_item() {
		if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) )
			wp_send_json_error();

		check_ajax_referer( 'simple-todo-list-add-item', 'nonce' );

		if ( ! isset( $_POST['content'][0]['value'] ) )
			wp_send_json_error();

		$post_id = wp_insert_post( array(
			'post_type'    => self::POST_TYPE,
			'post_title'   => sprintf( esc_html__( 'Todo item from %s', 'simple-todo-list' ), current_time( 'mysql' ) ),
			'post_content' => Slimdown::render( wp_strip_all_tags( trim( $_POST['content'][0]['value'] ) ) )
		) );

		if ( $post_id ) {
			$new_post = get_post( $post_id );

			wp_send_json_success( array(
				'post_id'        => $post_id,
				'post_content'   => $new_post->post_content,
				'time_diff'      => esc_html( sprintf( __( '%s ago', 'simple-todo-list' ), human_time_diff( mysql2date( 'U', $new_post->post_date ), current_time( 'timestamp' ) ) ) ),
				'complete_nonce' => wp_create_nonce( 'simple-todo-list-complete-item-' . $post_id ), 
				'do_item_nonce'  => wp_create_nonce( 'simple-todo-list-do-item-' . $post_id ),
				'delete_nonce'   => wp_create_nonce( 'simple-todo-list-delete-item-' . $post_id ),
				'delete_string'  => esc_html__( 'Delete', 'simple-todo-list' )
			) );
		}

		wp_send_json_error();
	}

	function wp_ajax_clear_list() {

		if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['clear_ids'] ) )
			wp_send_json_error();

		check_ajax_referer( 'simple-todo-list-clear-list', 'nonce' );

		if ( empty( $_POST['clear_ids'] ) || ! is_array( $_POST['clear_ids'] ) )
			wp_send_json_error();

		$to_delete = array_map( 'absint', $_POST['clear_ids'] );

		foreach ( $to_delete as $key => $post_id ) {
			$deleted = wp_delete_post( $post_id, true );

			if ( $deleted || 0 == $post_id ) {
				unset( $to_delete[ $key ] );
			}
		}

		if ( empty( $to_delete ) )
			wp_send_json_success();

		wp_send_json_error();
	}

	function wp_ajax_do_item() {

		if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['post_id'] ) )
			wp_send_json_error();

		$post_id = absint( $_POST['post_id'] );

		check_ajax_referer( 'simple-todo-list-do-item-' . $post_id, 'nonce' );

		$updated = update_post_meta( $post_id, self::STATUS_META, 'is_done' );
		
		if ( true == $updated )
			wp_send_json_success();

		wp_send_json_error();
	}

	function wp_ajax_delete_item() {

		if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['post_id'] ) )
			wp_send_json_error();

		$post_id = absint( $_POST['post_id'] );

		check_ajax_referer( 'simple-todo-list-delete-item-' . $post_id, 'nonce' );

		$deleted_post = wp_delete_post( $post_id, true );

		if ( false !== $deleted_post )
			wp_send_json_success( array( 'post_id' => $post_id ) );

		wp_send_json_error();
	}

	function wp_ajax_undo_item() {

		if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['post_id'] ) )
			wp_send_json_error();

		$post_id = absint( $_POST['post_id'] );

		check_ajax_referer( 'simple-todo-list-do-item-' . $post_id, 'nonce' );
		
		$deleted = delete_post_meta( $post_id, self::STATUS_META );

		if ( true == $deleted )
			wp_send_json_success();

		wp_send_json_error();
	}

	function wp_dashboard_setup() {
		wp_add_dashboard_widget( 'simple_todo_list', esc_html__( 'Todo List', 'simple-todo-list' ), array( $this, 'wp_add_dashboard_widget' ) );
	}
}

add_action( 'plugins_loaded', array( 'Simple_Todo_List_Plugin', 'get_instance' ) );